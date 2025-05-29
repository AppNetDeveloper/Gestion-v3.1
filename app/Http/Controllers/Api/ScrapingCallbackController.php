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

/**
 * Controlador para manejar los callbacks del servicio de scraping
 */
class ScrapingCallbackController extends Controller
{
    /**
     * Maneja el callback recibido desde la API de scraping Python.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Maneja el callback del servicio de scraping
     *
     * @OA\Post(
     *     path="/api/scraping/callback",
     *     summary="Recibe un callback del servicio de scraping",
     *     tags={"Scraping"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"task_id", "status", "fuente"},
     *             @OA\Property(property="task_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="status", type="string", enum={"completed", "failed"}),
     *             @OA\Property(property="error_message", type="string", nullable=true),
     *             @OA\Property(property="fuente", type="string"),
     *             @OA\Property(property="empresas", type="array", @OA\Items(type="object"), nullable=true),
     *             @OA\Property(property="datos", type="object", nullable=true),
     *             @OA\Property(property="resultados", type="array", @OA\Items(type="object"), nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Callback procesado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
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

        // Verificamos si la tarea ya estaba completada
        if ($task->status === 'completed') {
            Log::info(
                "Callback recibido para Tarea ID {$task->id} (API Task ID: {$apiTaskId}) " .
                "que ya estaba completada. Procesando datos adicionales."
            );
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

            // Si no hay resultados, manejamos según la fuente
            if (empty($resultsData)) {
                $singleCallbackSources = ['brave', 'duckduckgo', 'google'];
                $shouldMarkComplete = in_array(strtolower($validatedData['fuente']), $singleCallbackSources);
                
                if ($shouldMarkComplete) {
                    $task->status = 'completed';
                    $task->save();
                    Log::info("Tarea ID {$task->id}: callback procesado exitosamente. Tarea marcada como completada.");
                } else {
                    Log::info("Tarea ID {$task->id}: callback procesado exitosamente. Esperando más datos...");
                }
                
                DB::commit();

                return response()->json([
                    'status'  => 'success',
                    'message' => $shouldMarkComplete 
                        ? 'Callback procesado correctamente. Tarea completada.' 
                        : 'Callback procesado correctamente. Se pueden enviar más datos para esta tarea.'
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

                // Verificamos si debemos marcar como completada basado en la fuente
                // Algunas fuentes envían múltiples callbacks, otras solo uno
                $singleCallbackSources = ['brave', 'duckduckgo', 'google'];
                $shouldMarkComplete = in_array(strtolower($validatedData['fuente']), $singleCallbackSources);
                
                if ($shouldMarkComplete) {
                    $task->status = 'completed';
                    $task->save();
                    Log::info(
                        "Callback procesado para Tarea ID {$task->id}. " .
                        "Contactos nuevos: {$contactsAddedCount}. " .
                        "Relaciones añadidas: {$relationsCreatedCount}. " .
                        "Tarea marcada como completada."
                    );
                } else {
                    Log::info(
                        "Callback procesado para Tarea ID {$task->id}. " .
                        "Contactos nuevos: {$contactsAddedCount}. " .
                        "Relaciones añadidas: {$relationsCreatedCount}. " .
                        "La tarea permanece activa para más callbacks."
                    );
                }
                
                DB::commit();

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
