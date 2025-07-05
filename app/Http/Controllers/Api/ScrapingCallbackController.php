<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ScrapingTask;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Controlador para manejar los callbacks del servicio de scraping
 */
class ScrapingCallbackController extends Controller
{
    /**
     * Maneja el callback del servicio de scraping
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleCallback(Request $request)
    {
        // 1. Logging inicial y extracción del task_id
        $taskId = $request->input('task_id', 'N/A');
        Log::info("[Scraping Callback] Recibido para task_id: {$taskId}");
        Log::debug("[Scraping Callback] Datos recibidos", ['data' => $request->all()]);
        
        // 2. Buscar la tarea correspondiente
        $task = ScrapingTask::where('api_task_id', $taskId)
                          ->orWhere('id', $taskId)
                          ->first();

        if (!$task) {
            Log::warning("[Scraping Callback] Tarea no encontrada: {$taskId}");
            return response()->json([
                'status'  => 'error',
                'message' => 'Tarea no encontrada'
            ], 404);
        }
        
        Log::info("[Scraping Callback] Tarea encontrada ID: {$task->id}");
        
        // 3. Extraer datos importantes
        $status = $request->input('status');
        $empresas = $request->input('empresas', []);
        $resultados = $request->input('resultados', []);
        
        // 4. Logging de estructura
        Log::info("[Scraping Callback] Estructura del payload", [
            'task_id' => $taskId,
            'status' => $status,
            'empresas_count' => is_array($empresas) ? count($empresas) : 'no es array',
            'resultados_count' => is_array($resultados) ? count($resultados) : 'no es array'
        ]);
        
        // 5. Procesar los datos en transacción
        DB::beginTransaction();
        try {
            $contactsAddedCount = 0;
            $contactsLinkedCount = 0;
            $datosAProcesar = [];
            
            // 5.1 Determinar qué datos procesar (empresas o resultados)
            if (is_array($empresas) && !empty($empresas)) {
                $datosAProcesar = $empresas;
                Log::info("[Scraping Callback] Procesando {$task->id}: " . count($datosAProcesar) . " empresas");
            } elseif (is_array($resultados) && !empty($resultados)) {
                $datosAProcesar = $resultados;
                Log::info("[Scraping Callback] Procesando {$task->id}: " . count($datosAProcesar) . " resultados");
            } else {
                Log::warning("[Scraping Callback] No hay datos para procesar en task_id: {$taskId}");
            }
            
            // 5.2 Procesar cada contacto
            foreach ($datosAProcesar as $dato) {
                // Extraer datos normalizados
                $nombre = $dato['nombre'] ?? $dato['empresa'] ?? 'Empresa sin nombre';
                $email = $dato['correo'] ?? null;
                $telefono = $dato['telefono'] ?? null;
                $web = $dato['url'] ?? null;
                
                // Verificar si tenemos datos mínimos
                if (empty($email) && empty($telefono)) {
                    Log::info("[Scraping Callback] Ignorando contacto sin email ni teléfono: {$nombre}");
                    continue;
                }
                
                // Buscar contacto existente por email o teléfono
                $contact = null;
                if (!empty($email)) {
                    $contact = Contact::where('email', $email)->first();
                }
                if (!$contact && !empty($telefono)) {
                    $contact = Contact::where('phone', $telefono)->first();
                }
                
                // Crear nuevo contacto si no existe
                if (!$contact) {
                    $contact = Contact::create([
                        'user_id' => $task->user_id,
                        'name'    => $nombre,
                        'email'   => $email,
                        'phone'   => $telefono,
                        'web'     => $web,
                        'address' => null,
                        'telegram'=> null,
                    ]);
                    $contactsAddedCount++;
                    Log::info("[Scraping Callback] Nuevo contacto creado ID: {$contact->id}", [
                        'nombre' => $nombre,
                        'email' => $email,
                        'telefono' => $telefono
                    ]);
                } else {
                    Log::info("[Scraping Callback] Contacto existente ID: {$contact->id}", [
                        'nombre' => $nombre,
                        'email' => $email,
                        'telefono' => $telefono
                    ]);
                }
                
                // Vincular contacto con la tarea (sin duplicar)
                $result = $task->contacts()->syncWithoutDetaching([$contact->id]);
                if (!empty($result['attached'])) {
                    $contactsLinkedCount++;
                }
            }
            
            // 5.3 Actualizar estado de la tarea si se encontraron contactos
            if ($contactsAddedCount > 0 || $contactsLinkedCount > 0) {
                $task->status = 'completed';
                $task->save();
                Log::info("[Scraping Callback] Tarea {$task->id} marcada como completada. Nuevos: {$contactsAddedCount}, Vinculados: {$contactsLinkedCount}");
            } else {
                Log::info("[Scraping Callback] Tarea {$task->id} procesada sin contactos nuevos");
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Callback procesado correctamente',
                'data' => [
                    'task_id' => $task->id,
                    'contactos_nuevos' => $contactsAddedCount,
                    'contactos_vinculados' => $contactsLinkedCount
                ]
            ]);
            
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("[Scraping Callback] Error procesando tarea {$task->id}: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error procesando callback: ' . $e->getMessage()
            ], 500);
        }
    }  

}
