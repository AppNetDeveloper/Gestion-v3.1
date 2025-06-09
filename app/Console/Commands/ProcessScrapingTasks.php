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
    protected $signature = 'scraping:process-loop {--sleep=30 : Segundos de espera entre ciclos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa tareas de scraping en un bucle continuo';

    /**
     * URL base de la API de scraping
     * 
     * @var string
     */
    protected $apiBaseUrl;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->apiBaseUrl = config('services.scraping_api.url');
    }

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
                    
                    // 4. Buscar una nueva tarea pendiente para procesar
                    $task = ScrapingTask::where('status', 'pending')
                                        ->orderBy('retry_attempts', 'asc') // Priorizar las que tienen menos intentos
                                        ->orderBy('created_at', 'asc') // Luego por orden de creación
                                        ->first();
                }
                
                // Si hay una tarea pendiente, procesarla
                if ($task) {
                    $this->processSingleTask($task);
                } else {
                    $this->info("No hay tareas pendientes para procesar. Esperando {$sleepSeconds} segundos...");
                }
                
            } catch (Throwable $e) {
                $this->error("Error en el bucle principal: " . $e->getMessage());
                Log::error("Error en el bucle principal del procesador de tareas", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            // Esperar antes del siguiente ciclo
            sleep($sleepSeconds);
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Process a single scraping task
     *
     * @param ScrapingTask $task
     * @return void
     */
    protected function processSingleTask(ScrapingTask $task): void
    {
        $this->info("Procesando tarea ID: {$task->id} - Tipo: {$task->type} - Intentos: {$task->retry_attempts}");
        
        try {
            // 1. Marcar la tarea como en proceso
            $task->update([
                'status' => 'processing',
                'last_attempt_at' => now()
            ]);
            
            // 2. Preparar los datos para enviar a la API
            $payload = $this->prepareTaskPayload($task);
            
            // 3. Enviar la tarea a la API de scraping
            $response = Http::timeout(30)->post($this->apiBaseUrl . '/tasks', $payload);
            
            // 4. Verificar la respuesta
            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['task_id'])) {
                    // Actualizar la tarea con el ID de la API
                    $task->update([
                        'api_task_id' => $responseData['task_id'],
                        'updated_at' => now()
                    ]);
                    
                    $this->info("✅ Tarea ID {$task->id} enviada correctamente a la API. API Task ID: {$responseData['task_id']}");
                    Log::info("Tarea enviada a la API", [
                        'task_id' => $task->id,
                        'api_task_id' => $responseData['task_id']
                    ]);
                } else {
                    $this->markTaskAsFailed($task, "La API no devolvió un task_id válido");
                }
            } else {
                $errorMessage = $response->body();
                $this->markTaskAsFailed($task, "Error de la API: " . substr($errorMessage, 0, 255));
            }
            
        } catch (ConnectionException $e) {
            $this->markTaskAsFailed($task, "Error de conexión con la API: " . $e->getMessage());
        } catch (Throwable $e) {
            $this->markTaskAsFailed($task, "Error al procesar la tarea: " . $e->getMessage());
            Log::error("Error al procesar tarea ID {$task->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Prepare the payload for the scraping API
     *
     * @param ScrapingTask $task
     * @return array
     */
    protected function prepareTaskPayload(ScrapingTask $task): array
    {
        // URL de callback para que la API nos notifique cuando termine
        $callbackUrl = URL::route('api.scraping.callback', ['task_id' => $task->id]);
        
        // Datos básicos que siempre se envían
        $payload = [
            'callback_url' => $callbackUrl,
            'task_type' => $task->type,
        ];
        
        // Añadir datos específicos según el tipo de tarea
        $taskData = json_decode($task->data, true) ?: [];
        
        // Combinar los datos de la tarea con el payload
        return array_merge($payload, $taskData);
    }
    
    /**
     * Mark a task as failed with the given error message
     *
     * @param ScrapingTask $task
     * @param string $errorMessage
     * @return void
     */
    protected function markTaskAsFailed(ScrapingTask $task, string $errorMessage): void
    {
        $maxAttempts = 3;
        $currentAttempts = (int) $task->retry_attempts;
        $attempts = $currentAttempts + 1;
        
        if ($attempts < $maxAttempts) {
            // Si aún no ha alcanzado el máximo de intentos, la ponemos en pending para reintento
            $task->update([
                'status' => 'pending',
                'retry_attempts' => $attempts,
                'api_task_id' => null,
                'error_message' => $errorMessage,
                'last_attempt_at' => now()
            ]);
            
            $this->warn("⚠️ Tarea ID {$task->id} falló pero será reintentada (Intento {$attempts}/{$maxAttempts}). Error: {$errorMessage}");
            Log::warning("Tarea ID {$task->id} falló pero será reintentada", [
                'attempts' => "{$attempts}/{$maxAttempts}",
                'error' => $errorMessage
            ]);
        } else {
            // Si ya alcanzó el máximo de intentos, la marcamos como fallida definitivamente
            $task->update([
                'status' => 'failed',
                'retry_attempts' => $attempts,
                'api_task_id' => null,
                'error_message' => $errorMessage,
                'failed_at' => now(),
                'last_attempt_at' => now()
            ]);
            
            $this->error("❌ Tarea ID {$task->id} falló definitivamente después de {$maxAttempts} intentos. Error: {$errorMessage}");
            Log::error("Tarea ID {$task->id} falló definitivamente", [
                'attempts' => "{$attempts}/{$maxAttempts}",
                'error' => $errorMessage
            ]);
        }
    }
    
    /**
     * Clean up old failed tasks (more than 10 days old)
     *
     * @return void
     */
    protected function cleanupOldFailedTasks(): void
    {
        $cutoffDate = now()->subDays(10);
        $oldFailedTasks = ScrapingTask::where('status', 'failed')
                                    ->where('failed_at', '<=', $cutoffDate)
                                    ->get();
        
        $deletedCount = 0;
        foreach ($oldFailedTasks as $task) {
            $task->delete();
            $deletedCount++;
        }
        
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
                $maxAttempts = 3;
                
                // Obtener el número actual de intentos y asegurar que se incremente
                $currentAttempts = (int) $task->retry_attempts;
                $attempts = $currentAttempts + 1;
                
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
                    // Incrementamos el contador de intentos para evitar bucles infinitos
                    $task->update([
                        'status' => 'pending',
                        'retry_attempts' => $attempts,  // Incrementamos el contador de intentos
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
                        'error_message' => 'Tiempo de espera agotado (15 minutos) - Sin contactos después de ' . $maxAttempts . ' intentos'
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
