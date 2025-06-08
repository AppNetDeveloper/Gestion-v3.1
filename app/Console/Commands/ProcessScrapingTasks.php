<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScrapingTask; // Asegúrate de que la ruta al modelo es correcta
use Illuminate\Support\Facades\Http; // Cliente HTTP de Laravel
use Illuminate\Support\Facades\Log; // Para registrar información y errores
use Illuminate\Support\Facades\URL; // Para generar la URL de callback
use Throwable; // Para capturar excepciones más generales
use Illuminate\Http\Client\ConnectionException; // Para errores de conexión específicos

class ProcessScrapingTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Firma del comando: php artisan scraping:process-loop
    protected $signature = 'scraping:process-loop {--sleep=30 : Segundos de espera entre cada ciclo}'; // Añadido opción para sleep

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa tareas de scraping pendientes en un bucle infinito'; // Descripción actualizada

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sleepSeconds = (int) $this->option('sleep'); // Obtener segundos de la opción o usar 30
        $this->info("Iniciando procesador de tareas de scraping en bucle (espera de {$sleepSeconds}s)...");

        // Bucle infinito
        while (true) {
            try {
                // 1. Primero, verificar tareas atascadas (más de 15 minutos sin actualizar)
                $this->checkStuckTasks();
                
                // 2. Verificar si ya hay una tarea en proceso
                $processingTask = ScrapingTask::where('status', 'processing')
                                            ->whereNotNull('api_task_id')
                                            ->first();
                
                if ($processingTask) {
                    // Check if the processing task has been running for more than 15 minutes
                    $stuckThreshold = now()->subMinutes(15);
                    if ($processingTask->updated_at <= $stuckThreshold) {
                        $this->info("Tarea ID {$processingTask->id} lleva más de 15 minutos en proceso. Verificando estado...");
                        $this->checkStuckTasks();
                    } else {
                        $timeElapsed = now()->diffInMinutes($processingTask->updated_at);
                        $timeLeft = max(0, 15 - $timeElapsed);
                        $this->info("Tarea ID {$processingTask->id} ya está en proceso. Tiempo restante: {$timeLeft} minutos...");
                    }
                    $task = null;
                } else {
                    // 3. Limpiar tareas fallidas antiguas (más de 10 días)
                $this->cleanupOldFailedTasks();
                
                // 4. Buscar tareas fallidas para reintentar (máximo 3 intentos)
                $failedTask = ScrapingTask::where('status', 'failed')
                                        ->where('retry_attempts', '<', 3)
                                        ->where(function($query) {
                                            $query->whereNull('last_attempt_at')
                                                  ->orWhere('last_attempt_at', '<=', now()->subMinutes(5));
                                        })
                                        ->orderBy('last_attempt_at', 'asc')
                                        ->first();

                    if ($failedTask) {
                        $task = $failedTask;
                        // Reset task for retry
                        $task->update([
                            'status' => 'processing',
                            'api_task_id' => null, // Clear previous task ID
                            'error_message' => null // Clear previous error
                        ]);
                        $this->info("Reintentando tarea fallida ID: {$task->id} (Intento " . ($task->retry_attempts + 1) . " de 3)");
                    } else {
                        // 4. Si no hay tareas fallidas, buscar tareas pendientes normales
                        $task = ScrapingTask::where('status', 'pending')
                                          ->whereNull('api_task_id')
                                          ->orderBy('created_at', 'asc')
                                          ->first();
                    }
                }

                if (!$task) {
                    $this->info('No hay tareas pendientes. Esperando...');
                    // No salimos del bucle, simplemente esperamos antes de volver a comprobar
                } else {
                    $this->info("Procesando Tarea ID: {$task->id} - Fuente: {$task->source} - Keyword: {$task->keyword}");

                    // --- Lógica para procesar UNA tarea ---
                    $this->processSingleTask($task);
                    // --- Fin lógica para procesar UNA tarea ---


                } // Fin if ($task)

            } catch (Throwable $e) {
                // Capturar cualquier excepción inesperada en el ciclo principal
                $this->error("Error inesperado en el bucle principal: " . $e->getMessage());
                Log::critical("Error crítico en el bucle de ProcessScrapingTasks: " . $e->getMessage(), ['exception' => $e]);
                // Esperar un poco más antes de reintentar en caso de error grave
                sleep(60); // Espera 1 minuto antes de reintentar el bucle
            }

            // Esperar antes de la siguiente iteración
            sleep($sleepSeconds);

        } // Fin while(true)

        // Nota: En teoría, nunca llegaría aquí en un bucle infinito
        return Command::SUCCESS;
    }

    /**
     * Procesa una única tarea de scraping.
     *
     * @param ScrapingTask $task
     * @return void
     */
    /**
     * Procesa una única tarea de scraping.
     *
     * @param ScrapingTask $task
     * @return void
     */
    protected function processSingleTask(ScrapingTask $task): void
    {
        // Actualizar el estado y los intentos
        $task->update([
            'status' => 'processing',
            'retry_attempts' => $task->status === 'failed' ? $task->retry_attempts + 1 : 0,
            'last_attempt_at' => now(),
        ]);
        
        try {
            // Determinar el endpoint y preparar payload
            $endpoint = '';
            $payload = [];
            $callbackUrl = '';
            $callbackRouteName = 'api.scraping.callback';
            $fallbackCallbackPath = '/api/scraping-callback';

            // Generar URL de callback
            try {
                $callbackUrl = route($callbackRouteName);
            } catch (\Exception $e) {
                Log::warning("[Task ID: {$task->id}] No se pudo generar la URL de callback. Usando URL base. Error: " . $e->getMessage());
                $appUrl = rtrim(config('app.url', 'http://localhost'), '/');
                $path = ltrim($fallbackCallbackPath, '/');
                $callbackUrl = $appUrl . '/' . $path;
            }

            // Configurar el endpoint y payload según la fuente
            switch ($task->source) {
                case 'google_ddg':
                    $endpoint = '/buscar-google-ddg-limpio';
                    $payload = [
                        'keyword' => $task->keyword,
                        'results' => 1000,
                        'callback_url' => $callbackUrl,
                    ];
                    break;
                case 'empresite':
                    $endpoint = '/buscar-empresite';
                    $payload = [
                        'actividad' => $task->keyword,
                        'provincia' => $task->region,
                        'paginas' => 100,
                        'callback_url' => $callbackUrl,
                    ];
                    break;
                case 'paginas_amarillas':
                    $endpoint = '/buscar-paginas-amarillas';
                    $payload = [
                        'actividad' => $task->keyword,
                        'provincia' => $task->region,
                        'paginas' => 1,
                        'callback_url' => $callbackUrl,
                    ];
                    break;
                default:
                    $error = "Fuente desconocida: {$task->source}";
                    $this->error("$error para Tarea ID: {$task->id}");
                    Log::error("$error para ScrapingTask ID {$task->id}.");
                    $this->markTaskAsFailed($task, $error);
                    return;
            }

            // Obtener URL del servidor Python
            $scrapingServerUrl = config('services.scraping.url');
            if (!$scrapingServerUrl) {
                throw new \Exception("La URL del servidor de scraping no está configurada");
            }

            // Construir URL completa de la API
            $baseApiUrl = rtrim($scrapingServerUrl, '/');
            $apiEndpoint = ltrim($endpoint, '/');
            $fullApiUrl = $baseApiUrl . '/' . $apiEndpoint;

            $this->info("Llamando a la API Python para Tarea ID {$task->id}: POST {$fullApiUrl}");
            Log::info("Llamando a API Python para Tarea ID {$task->id}", [
                'url' => $fullApiUrl,
                'payload' => $payload
            ]);

            // Realizar la petición POST a la API Python
            $response = Http::timeout(15)->post($fullApiUrl, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Verificar si la respuesta incluye un task_id
                if (isset($responseData['task_id'])) {
                    $task->update([
                        'api_task_id' => $responseData['task_id'],
                        'status' => 'processing',
                        'error_message' => null // Limpiar mensajes de error previos
                    ]);
                    
                    $this->info(sprintf(
                        'Tarea ID %d enviada correctamente. Task ID: %s. Posición en cola: %s',
                        $task->id,
                        $responseData['task_id'],
                        $responseData['queue_position'] ?? 'N/A'
                    ));
                    
                    Log::info("Tarea ID {$task->id} iniciada en API Python", [
                        'api_task_id' => $responseData['task_id'],
                        'queue_position' => $responseData['queue_position'] ?? null
                    ]);
                } else {
                    throw new \Exception('La API no devolvió un ID de tarea válido');
                }
            } else {
                throw new \Exception(sprintf(
                    'Error en la respuesta del servidor. Código: %s',
                    $response->status()
                ));
            }
            
        } catch (ConnectionException $e) {
            $error = 'No se pudo conectar al servidor de scraping: ' . $e->getMessage();
            Log::error($error);
            throw new \Exception($error);
        } catch (\Exception $e) {
            $error = 'Error al procesar la tarea: ' . $e->getMessage();
            Log::error($error, [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        } catch (Throwable $e) {
            // Capturar otras excepciones durante la llamada o procesamiento de respuesta
            $this->error("Excepción al procesar Tarea ID {$task->id}: " . $e->getMessage());
            Log::error("Excepción procesando Tarea ID {$task->id}: " . $e->getMessage(), ['exception' => $e]);
            $task->status = 'failed'; // Marcar como fallida ante excepciones inesperadas
            $task->save();
        }
    }
    
    /**
     * Marca una tarea como fallida y maneja los reintentos
     * 
     * @param ScrapingTask $task
     * @param string $error
     * @return void
     */
    protected function markTaskAsFailed(ScrapingTask $task, string $error): void
    {
        $maxAttempts = 3;
        $isNoContactsError = stripos($error, 'sin contactos') !== false || stripos($error, 'no contacts') !== false;
        
        // Si es un error de "sin contactos", marcamos como fallida sin reintentos
        if ($isNoContactsError) {
            $task->update([
                'status' => 'failed',
                'failed_at' => now(),
                'retry_attempts' => $maxAttempts,
                'error_message' => $error,
                'last_attempt_at' => now()
            ]);
            $this->warn("❌ Tarea ID {$task->id} falló (sin contactos). Marcada como fallida sin reintentos.");
            Log::warning("Tarea ID {$task->id} falló (sin contactos). Marcada como fallida sin reintentos.");
            return;
        }
        
        // Para otros errores, manejamos los reintentos
        $attempts = $task->retry_attempts + 1;
        
        if ($attempts >= $maxAttempts) {
            // Máximo de intentos alcanzado, marcar como fallida permanentemente
            $task->update([
                'status' => 'failed',
                'failed_at' => now(),
                'retry_attempts' => $attempts,
                'error_message' => $error,
                'last_attempt_at' => now()
            ]);
            $this->error("❌ Tarea ID {$task->id} falló después de {$maxAttempts} intentos. Error: {$error}");
            Log::error("Tarea ID {$task->id} falló después de {$maxAttempts} intentos", ['error' => $error]);
        } else {
            // Reintentar más tarde
            $task->update([
                'status' => 'pending',
                'retry_attempts' => $attempts,
                'last_attempt_at' => now(),
                'error_message' => $error,
            ]);
            $nextAttempt = now()->addMinutes(5 * $attempts); // Aumentar el tiempo de espera con cada intento
            $this->warn("⏳ Tarea ID {$task->id} falló (Intento {$attempts}/{$maxAttempts}). Próximo intento: {$nextAttempt->diffForHumans()}");
            Log::warning("Tarea ID {$task->id} falló (Intento {$attempts}/{$maxAttempts}). Próximo intento: {$nextAttempt}");
        }
        
        Log::error("Error en tarea de scraping", [
            'task_id' => $task->id,
            'error' => $error,
            'attempt' => $attempts ?? 1,
            'max_attempts' => $maxAttempts,
        ]);
    }
    
    /**
     * Verifica tareas que llevan más de 30 minutos sin actualizarse y actualiza su estado
     * según si tienen o no contactos asociados.
     *
     * @return void
     */
    /**
     * Clean up old failed tasks (older than 10 days with 3 or more retry attempts)
     *
     * @return void
     */
    protected function cleanupOldFailedTasks(): void
    {
        $threshold = now()->subDays(10);
        
        $deletedCount = ScrapingTask::where('status', 'failed')
                                    ->where('retry_attempts', '>=', 3)
                                    ->where('updated_at', '<=', $threshold)
                                    ->delete();
        
        if ($deletedCount > 0) {
            $this->info("✅ Se eliminaron {$deletedCount} tareas fallidas antiguas (más de 10 días).");
            Log::info("Se eliminaron {$deletedCount} tareas fallidas antiguas (más de 10 días)");
        }
    }
    
    /**
     * Check for stuck tasks and update their status based on contacts
     *
     * @return void
     */
    protected function checkStuckTasks(): void
    {
        // Reducir el tiempo de espera a 5 minutos para detectar tareas atascadas más rápido
        $threshold = now()->subMinutes(5);
        
        // Buscar tareas en procesamiento que no se hayan actualizado en más de 5 minutos
        $stuckTasks = ScrapingTask::where('status', 'processing')
                                ->where(function($query) use ($threshold) {
                                    $query->where('updated_at', '<=', $threshold)
                                         ->orWhereNull('updated_at');
                                })
                                ->get();
        
        $processedCount = 0;
        
        foreach ($stuckTasks as $task) {
            $this->info("Verificando tarea atascada ID: {$task->id} - Última actualización: {$task->updated_at}");
            
            try {
                // Verificar si la tarea tiene contactos asociados
                $hasContacts = $task->contacts()->exists();
                $attempts = $task->retry_attempts + 1;
                $maxAttempts = 3;
                
                // Registrar información de depuración
                $this->info("Verificando tarea atascada ID: {$task->id} - Estado actual: {$task->status} - Última actualización: {$task->updated_at} - Intentos: {$attempts}/{$maxAttempts}");
                Log::info("Verificando tarea atascada", [
                    'task_id' => $task->id,
                    'status' => $task->status,
                    'updated_at' => $task->updated_at,
                    'attempts' => "$attempts/$maxAttempts",
                    'has_contacts' => $hasContacts
                ]);
                
                if ($hasContacts) {
                    // Si tiene contactos, marcamos como completada
                    $task->update([
                        'status' => 'completed',
                        'last_attempt_at' => now(),
                        'api_task_id' => null
                    ]);
                    $this->info("✅ Tarea ID {$task->id} marcada como completada (tiene contactos).");
                    Log::info("Tarea ID {$task->id} marcada como completada automáticamente (tenía contactos).");
                } elseif ($attempts < $maxAttempts) {
                    // Si no tiene contactos pero aún tiene intentos, la ponemos en pending
                    $task->update([
                        'status' => 'pending',
                        'retry_attempts' => $attempts,
                        'last_attempt_at' => now(),
                        'api_task_id' => null,
                        'error_message' => 'Tiempo de espera agotado (15 minutos)'
                    ]);
                    $this->warn("🔄 Tarea ID {$task->id} puesta en cola para reintento (Intento {$attempts}/{$maxAttempts}).");
                    Log::warning("Tarea ID {$task->id} puesta en cola para reintento (Intento {$attempts}/{$maxAttempts})");
                } else {
                    // Si no tiene contactos y no quedan intentos, la marcamos como fallida
                    $task->update([
                        'status' => 'failed',
                        'failed_at' => now(),
                        'last_attempt_at' => now(),
                        'retry_attempts' => $attempts,
                        'api_task_id' => null,
                        'error_message' => 'Tiempo de espera agotado (15 minutos) - Sin contactos después de varios intentos'
                    ]);
                    $this->error("❌ Tarea ID {$task->id} marcada como fallida (sin contactos después de {$maxAttempts} intentos).");
                    Log::warning("Tarea ID {$task->id} marcada como fallida automáticamente (sin contactos después de {$maxAttempts} intentos).");
                }
                
                $processedCount++;
                
            } catch (\Exception $e) {
                Log::error("Error al verificar tarea atascada ID {$task->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->error("Error al verificar tarea ID {$task->id}: " . $e->getMessage());
            }
        }
        
        if ($processedCount > 0) {
            $this->info("✅ Se procesaron {$processedCount} tareas atascadas.");
        }
    }
}
