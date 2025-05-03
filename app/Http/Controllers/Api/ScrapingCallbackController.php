<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ScrapingTask; // Modelo de la tarea
use App\Models\Contact;      // Modelo de Contactos principal
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // Para transacciones
use Illuminate\Validation\ValidationException;
use Throwable;

class ScrapingCallbackController extends Controller
{
    /**
     * Maneja el callback recibido desde la API de scraping Python.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleCallback(Request $request)
    {
        Log::info('Callback recibido de la API de Scraping:', $request->all());

        // Validación básica del payload recibido
        try {
            $validatedData = $request->validate([
                'task_id' => 'required|uuid',
                'status' => 'required|string|in:completed,failed',
                'error_message' => 'nullable|string',
                'fuente' => 'required|string',
                'empresas' => 'exclude_if:status,failed|required_without_all:datos,resultados|array',
                'datos' => 'exclude_if:status,failed|required_without_all:empresas,resultados|array',
                'resultados' => 'exclude_if:status,failed|required_without_all:empresas,datos|array',
            ]);
        } catch (ValidationException $e) {
            Log::error('Payload de callback inválido:', ['errors' => $e->errors(), 'payload' => $request->all()]);
            return response()->json(['status' => 'error', 'message' => 'Payload inválido', 'errors' => $e->errors()], 422);
        }

        $apiTaskId = $validatedData['task_id'];
        $status = $validatedData['status'];
        $errorMessage = $validatedData['error_message'] ?? null;

        $task = ScrapingTask::where('api_task_id', $apiTaskId)->first();

        if (!$task) {
            Log::warning("Callback recibido para un api_task_id no encontrado: {$apiTaskId}");
            return response()->json(['status' => 'error', 'message' => 'Tarea no encontrada'], 404);
        }

        if ($task->status !== 'processing') {
             Log::warning("Callback recibido para Tarea ID {$task->id} (API Task ID: {$apiTaskId}) que no está en estado 'processing'. Estado actual: {$task->status}. Ignorando callback.");
             return response()->json(['status' => 'ignored', 'message' => 'La tarea ya no estaba en procesamiento.']);
        }

        if ($status === 'completed') {
            $resultsData = [];
            if (!empty($validatedData['empresas'])) {
                $resultsData = $validatedData['empresas'];
            } elseif (!empty($validatedData['datos'])) {
                $resultsData = $validatedData['datos'];
            } elseif (!empty($validatedData['resultados'])) {
                 $tempResults = [];
                 foreach ($validatedData['resultados'] as $url => $data) {
                     $tempResults[] = [
                         'empresa' => $data['nombre'] ?? 'No encontrado',
                         'correo' => $data['correos'][0] ?? 'No encontrado',
                         'telefono' => $data['telefono'] ?? 'No encontrado',
                         'url' => $url,
                         'fuente' => $validatedData['fuente'] ?? 'google_ddg'
                     ];
                 }
                 $resultsData = $tempResults;
            }

            Log::info("Procesando callback COMPLETADO para Tarea ID {$task->id}. Resultados recibidos: " . count($resultsData));

            $contactsAddedCount = 0;
            $relationsCreatedCount = 0;

            DB::beginTransaction();
            try {
                foreach ($resultsData as $result) {
                    $name = $result['empresa'] ?? ($result['nombre'] ?? 'No encontrado');
                    $email = $result['correo'] ?? 'No encontrado';
                    $phone = $result['telefono'] ?? 'No encontrado';
                    $web = $result['url'] ?? null;

                    $email = ($email === 'No encontrado') ? null : $email;
                    $phone = ($phone === 'No encontrado') ? null : $phone;
                    $name = ($name === 'No encontrado' || $name === 'Sin nombre') ? null : $name;

                    if (empty($email) && empty($phone)) {
                        Log::info("[Task ID: {$task->id}] Saltando resultado sin email ni teléfono.", ['result' => $result]);
                        continue;
                    }

                    $contact = Contact::where('user_id', $task->user_id)
                                     ->where(function ($query) use ($email, $phone) {
                                         if (!empty($email)) {
                                             $query->orWhere('email', $email);
                                         }
                                         if (!empty($phone)) {
                                             $query->orWhere('phone', $phone);
                                         }
                                     })
                                     ->first();

                    if (!$contact) {
                        $contact = Contact::create([
                            'user_id' => $task->user_id,
                            'name' => $name ?? 'Empresa sin nombre',
                            'email' => $email,
                            'phone' => $phone,
                            'web' => $web,
                            'address' => null,
                            'telegram' => null,
                        ]);
                        $contactsAddedCount++;
                        Log::info("[Task ID: {$task->id}] Nuevo contacto creado (ID: {$contact->id})", ['email' => $email, 'phone' => $phone]);
                    } else {
                        Log::info("[Task ID: {$task->id}] Contacto existente encontrado (ID: {$contact->id})", ['email' => $email, 'phone' => $phone]);
                        // Opcional: Actualizar datos si es necesario
                    }

                    // *** CORREGIDO: Usar syncWithoutDetaching en lugar de attach ***
                    // Vincula la tarea y el contacto solo si la relación no existe ya.
                    $syncResult = $task->contacts()->syncWithoutDetaching([$contact->id]);
                    // Contar si se añadió una nueva relación
                    if (!empty($syncResult['attached'])) {
                        $relationsCreatedCount++;
                    }

                } // Fin foreach

                $task->status = 'completed';
                $task->save();

                DB::commit();
                Log::info("Callback procesado y transacción confirmada para Tarea ID {$task->id}. Contactos nuevos: {$contactsAddedCount}. Relaciones creadas/confirmadas: {$relationsCreatedCount}.");

            } catch (Throwable $e) {
                DB::rollBack();
                Log::error("Error durante el procesamiento del callback para Tarea ID {$task->id}: " . $e->getMessage(), ['exception' => $e]);
                $task->status = 'failed';
                $task->save();
                return response()->json(['status' => 'error', 'message' => 'Error interno al procesar los resultados'], 500);
            }

        } elseif ($status === 'failed') {
            Log::error("Callback recibido con estado 'failed' para Tarea ID {$task->id}. Mensaje: {$errorMessage}");
            $task->status = 'failed';
            $task->save();
        }

        return response()->json(['status' => 'success', 'message' => 'Callback recibido y procesado']);
    }
}
