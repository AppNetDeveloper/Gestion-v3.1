<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ScrapingTask;   // Modelo de la tarea
use App\Models\Contact;        // Modelo de Contactos principal
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // Para transacciones
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;        // Para truncar cadenas
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

        // Validación del payload recibido
        try {
            $validatedData = $request->validate([
                'task_id'                 => 'required|uuid',
                'status'                  => 'required|string|in:completed,failed',
                'error_message'           => 'nullable|string',
                'fuente'                  => 'required|string',
                // Permitimos que vengan vacíos o ausentes
                'empresas'                => 'exclude_if:status,failed|nullable|array',
                'datos'                   => 'exclude_if:status,failed|nullable|array',
                'resultados'              => 'exclude_if:status,failed|nullable|array',
            ]);
        } catch (ValidationException $e) {
            Log::error('Payload de callback inválido:', [
                'errors'  => $e->errors(),
                'payload' => $request->all(),
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Payload inválido',
                'errors'  => $e->errors(),
            ], 422);
        }

        $apiTaskId    = $validatedData['task_id'];
        $status       = $validatedData['status'];
        $errorMessage = $validatedData['error_message'] ?? null;

        // Buscamos la tarea en la BD
        $task = ScrapingTask::where('api_task_id', $apiTaskId)->first();
        if (! $task) {
            Log::warning("Callback recibido para api_task_id no encontrado: {$apiTaskId}");
            return response()->json([
                'status'  => 'error',
                'message' => 'Tarea no encontrada',
            ], 404);
        }

        // Solo procesamos si está en 'processing'
        if ($task->status !== 'processing') {
            Log::warning(
                "Callback recibido para Tarea ID {$task->id} (API Task ID: {$apiTaskId}) " .
                "que no está en estado 'processing'. Estado actual: {$task->status}. Ignorando."
            );
            return response()->json([
                'status'  => 'ignored',
                'message' => 'La tarea ya no estaba en procesamiento.',
            ]);
        }

        if ($status === 'completed') {
            // Normalizamos todos los posibles payloads a un array de resultados uniforme
            $resultsData = [];

            if (! empty($validatedData['empresas'])) {
                $resultsData = $validatedData['empresas'];
            } elseif (! empty($validatedData['datos'])) {
                $resultsData = $validatedData['datos'];
            } elseif (! empty($validatedData['resultados'])) {
                foreach ($validatedData['resultados'] as $url => $data) {
                    $resultsData[] = [
                        'empresa'  => $data['nombre']   ?? 'No encontrado',
                        'correo'   => $data['correos'][0] ?? 'No encontrado',
                        'telefono' => $data['telefono'] ?? 'No encontrado',
                        'url'      => $url,
                        'fuente'   => $validatedData['fuente'] ?? 'desconocida',
                    ];
                }
            }

            // Si no hay resultados, marcamos completada y devolvemos sin errores
            if (empty($resultsData)) {
                $task->status = 'completed';
                $task->save();

                Log::info("Tarea ID {$task->id} completada sin resultados.");
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Callback procesado: no se encontraron empresas.'
                ]);
            }

            Log::info(
                "Procesando callback COMPLETADO para Tarea ID {$task->id}. " .
                "Resultados: " . count($resultsData)
            );

            $contactsAddedCount    = 0;
            $relationsCreatedCount = 0;

            DB::beginTransaction();
            try {
                foreach ($resultsData as $result) {
                    // Extraemos campos y normalizamos valores
                    $name  = $result['empresa']  ?? ($result['nombre'] ?? 'No encontrado');
                    $email = $result['correo']   ?? 'No encontrado';
                    $phone = $result['telefono'] ?? 'No encontrado';
                    $web   = $result['url']      ?? null;

                    $email = $email === 'No encontrado' ? null : $email;
                    $phone = $phone === 'No encontrado' ? null : $phone;
                    $name  = in_array($name, ['No encontrado', 'Sin nombre'], true) ? null : $name;

                    // Si no hay email ni teléfono, saltamos
                    if (empty($email) && empty($phone)) {
                        Log::info(
                            "[Task ID: {$task->id}] Saltando resultado sin email ni teléfono.",
                            ['result' => $result]
                        );
                        continue;
                    }

                    // Truncamos cadenas largas a 255 caracteres
                    $name = $name ? Str::limit($name, 255, '') : null;
                    $web  = $web  ? Str::limit($web,  255, '') : null;

                    // Buscamos contacto existente
                    $contact = Contact::where('user_id', $task->user_id)
                        ->where(function ($q) use ($email, $phone) {
                            if ($email) {
                                $q->orWhere('email', $email);
                            }
                            if ($phone) {
                                $q->orWhere('phone', $phone);
                            }
                        })
                        ->first();

                    if (! $contact) {
                        // Creamos nuevo contacto
                        $contact = Contact::create([
                            'user_id' => $task->user_id,
                            'name'    => $name  ?? 'Empresa sin nombre',
                            'email'   => $email,
                            'phone'   => $phone,
                            'web'     => $web,
                            'address' => null,
                            'telegram'=> null,
                        ]);
                        $contactsAddedCount++;
                        Log::info(
                            "[Task ID: {$task->id}] Nuevo contacto (ID: {$contact->id})",
                            ['email' => $email, 'phone' => $phone]
                        );
                    } else {
                        Log::info(
                            "[Task ID: {$task->id}] Contacto existente (ID: {$contact->id})",
                            ['email' => $email, 'phone' => $phone]
                        );
                    }

                    // Vinculamos la tarea y el contacto sin duplicados
                    $sync = $task->contacts()->syncWithoutDetaching([$contact->id]);
                    if (! empty($sync['attached'])) {
                        $relationsCreatedCount++;
                    }
                }

                // Marcamos la tarea como completada
                $task->status = 'completed';
                $task->save();

                DB::commit();
                Log::info(
                    "Callback procesado para Tarea ID {$task->id}. " .
                    "Contactos nuevos: {$contactsAddedCount}. " .
                    "Relaciones añadidas: {$relationsCreatedCount}."
                );

            } catch (Throwable $e) {
                DB::rollBack();
                Log::error(
                    "Error procesando callback Tarea ID {$task->id}: " . $e->getMessage(),
                    ['exception' => $e]
                );
                $task->status = 'failed';
                $task->save();

                return response()->json([
                    'status'  => 'error',
                    'message' => 'Error interno al procesar resultados'
                ], 500);
            }

        } elseif ($status === 'failed') {
            // Si la tarea de scraping falló
            Log::error(
                "Callback con estado 'failed' para Tarea ID {$task->id}. Mensaje: {$errorMessage}"
            );
            $task->status = 'failed';
            $task->save();
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Callback recibido y procesado'
        ]);
    }
}
