<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScrapingTask; // Asegúrate de que la ruta al modelo es correcta
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Throwable;
use Illuminate\Http\Client\ConnectionException;

/**
 * IMPORTANTE: Para que la lógica de tareas atascadas funcione correctamente,
 * asegúrate de que tu modelo App\Models\ScrapingTask tiene definida la relación
 * para contar los contactos asociados. Ejemplo:
 *
 * public function contacts() {
 * return $this->belongsToMany(Contact::class, 'contact_scraping_task', 'scraping_task_id', 'contact_id');
 * }
 */
class ProcessScrapingTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraping:process-loop {--sleep=15 : Segundos de espera entre ciclos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa tareas de scraping en un bucle continuo de forma robusta';

    /**
     * URL base de la API de scraping.
     * * @var string
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
        // Cargar la URL de la API desde la configuración, con un valor por defecto.
        $this->apiBaseUrl = config('services.scraping.url', 'http://localhost:9001');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sleepSeconds = (int) $this->option('sleep');
        $this->info("Iniciando procesador de tareas de scraping en bucle (espera de {$sleepSeconds}s)...");

        // Bucle infinito para procesar tareas continuamente.
        while (true) {
            try {
                // 1. Gestionar tareas atascadas que han superado el tiempo de espera de 15 minutos.
                $this->handleStuckTasks();

                // 3. Buscamos una nueva tarea pendiente.
                $this->info("Buscando una tarea pendiente...");
                
                $this->cleanupOldFailedTasks();

                // Buscar todas las tareas pendientes que NO tengan api_task_id asignado
                $pendingTasks = ScrapingTask::where('status', 'pending')
                    ->whereNull('api_task_id')
                    ->orderBy('retry_attempts', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->get();

                if ($pendingTasks->count() > 0) {
                    foreach ($pendingTasks as $task) {
                        $this->processSingleTask($task);
                    }
                } else {
                    $this->info("No hay tareas pendientes. Esperando {$sleepSeconds} segundos...");
                }
                // También buscar tareas stuck con api_task_id asignado más de 30 min y no finalizadas
                $this->closeTimedOutApiTasks();
            } catch (Throwable $e) {
                $this->error("Error fatal en el bucle principal: " . $e->getMessage());
                Log::critical("Error fatal en el bucle principal del procesador de tareas", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            sleep($sleepSeconds);
        }

        return Command::SUCCESS;
    }
    
    /**
     * Marca tareas con api_task_id asignado como 'finalizada' si han pasado más de 30 minutos y siguen abiertas.
     */
    protected function closeTimedOutApiTasks(): void
    {
        $timeoutThreshold = now()->subMinutes(30);
        $tasks = ScrapingTask::whereNotNull('api_task_id')
            ->whereIn('status', ['pending', 'processing'])
            ->where('updated_at', '<=', $timeoutThreshold)
            ->get();
        foreach ($tasks as $task) {
            $task->update([
                'status' => 'completed',
                'error_message' => 'Finalizada automáticamente por timeout de 30 minutos sin cierre.',
                'api_task_id' => null
            ]);
            $this->warn("⏱️ Tarea ID {$task->id} marcada como finalizada por timeout de 30 minutos.");
            Log::warning("Tarea ID {$task->id} marcada como finalizada por timeout de 30 minutos.", ['task_id' => $task->id]);
        }
    }

    /**
     * Procesa una única tarea de scraping.
     *
     * @param ScrapingTask $task
     * @return void
     */
    protected function processSingleTask(ScrapingTask $task): void
    {
        $this->info("Procesando tarea ID: {$task->id} - Fuente: {$task->source} - Intentos: {$task->retry_attempts}");
        
        try {
            // 1. Marcar la tarea como 'processing'.
            $task->update([
                'status' => 'processing',
                'last_attempt_at' => now(),
                'updated_at' => now()
            ]);
            
            $payload = $this->prepareTaskPayload($task);
            
            $apiUrl = rtrim($this->apiBaseUrl, '/') . '/buscar-google-ddg-limpio';
            $this->info("Enviando solicitud a: " . $apiUrl);
            Log::info("Enviando solicitud a la API", ['url' => $apiUrl, 'payload' => $payload]);
            
            $response = Http::timeout(15)->post($apiUrl, $payload);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['task_id'])) {
                    $task->update(['api_task_id' => $responseData['task_id']]);
                    $this->info("✅ Tarea ID {$task->id} enviada. API Task ID: {$responseData['task_id']}");
                    Log::info("Tarea enviada a la API", ['task_id' => $task->id, 'api_task_id' => $responseData['task_id']]);
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
            $this->markTaskAsFailed($task, "Error crítico al procesar la tarea: " . $e->getMessage());
            Log::error("Error crítico al procesar tarea ID {$task->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Verifica y gestiona tareas que han estado en 'processing' por más de 15 minutos.
     * Si una tarea atascada tiene contactos asociados, se marca como completada.
     * Si no, se intenta reintentar o se marca como fallida.
     *
     * @return void
     */
    protected function handleStuckTasks(): void
    {
        $stuckThreshold = now()->subMinutes(15);

        // Buscamos tareas atascadas y cargamos eficientemente el conteo de contactos asociados.
        $stuckTasks = ScrapingTask::where('status', 'processing')
                                ->where('updated_at', '<=', $stuckThreshold)
                                ->withCount('contacts') // Carga la propiedad 'contacts_count'
                                ->get();

        if ($stuckTasks->isEmpty()) {
            return; // No hay tareas atascadas, todo en orden.
        }

        $this->warn("Se encontraron {$stuckTasks->count()} tareas atascadas (más de 15 minutos). Gestionando...");

        foreach ($stuckTasks as $task) {
            $this->warn("⚠️ Tarea ID {$task->id} está atascada. Última actualización: {$task->updated_at}. Verificando contactos...");

            if ($task->contacts_count > 0) {
                // Si tiene contactos, el scraping funcionó pero el callback falló. La marcamos como completada.
                $task->update([
                    'status' => 'completed',
                    'error_message' => 'Completada manualmente. La tarea estaba atascada pero generó contactos.',
                    'api_task_id' => null
                ]);
                $this->info("✅ Tarea ID {$task->id} marcada como 'completada' porque se encontraron {$task->contacts_count} contactos asociados.");
                Log::info("Tarea atascada ID {$task->id} marcada como completada automáticamente por tener contactos.", ['task_id' => $task->id]);

            } else {
                // Si no tiene contactos, la tarea realmente falló. Procedemos con la lógica de reintento/fallo.
                $errorMessage = "La tarea superó el tiempo de espera de 15 minutos y fue cancelada (sin contactos generados).";
                $this->markTaskAsFailed($task, $errorMessage);
            }
        }
    }

    /**
     * Marca una tarea como fallida o la pone en cola para reintento.
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
        
        $updateData = [
            'retry_attempts' => $attempts,
            'api_task_id' => null,
            'error_message' => $errorMessage,
            'last_attempt_at' => now()
        ];
        
        if ($attempts < $maxAttempts) {
            // Aún quedan intentos: volver a estado 'pending'.
            $updateData['status'] = 'pending';
            $task->update($updateData);
            
            $this->warn("🔄 Tarea ID {$task->id} falló y será reintentada (Intento {$attempts}/{$maxAttempts}). Error: {$errorMessage}");
            Log::warning("Tarea ID {$task->id} falló y será reintentada", [
                'task_id' => $task->id,
                'attempts' => "{$attempts}/{$maxAttempts}",
                'error' => $errorMessage
            ]);
        } else {
            // Se agotaron los intentos: fallo definitivo.
            $updateData['status'] = 'failed';
            $updateData['failed_at'] = now();
            $task->update($updateData);
            
            $this->error("❌ Tarea ID {$task->id} falló definitivamente tras {$maxAttempts} intentos. Error: {$errorMessage}");
            Log::error("Tarea ID {$task->id} falló definitivamente", [
                'task_id' => $task->id,
                'attempts' => "{$attempts}/{$maxAttempts}",
                'error' => $errorMessage
            ]);
        }
    }

    /**
     * Prepara el payload para enviar a la API de scraping.
     *
     * @param ScrapingTask $task
     * @return array
     */
    protected function prepareTaskPayload(ScrapingTask $task): array
    {
        $callbackUrl = URL::route('api.scraping.callback', ['task_id' => $task->id]);
        
        $payload = [
            'callback_url' => $callbackUrl,
            'task_type' => $task->source,
            'keyword' => $task->keyword,
        ];
        
        $taskData = json_decode($task->data, true) ?: [];
        
        return array_merge($payload, $taskData);
    }

    /**
     * Elimina tareas fallidas que tienen más de 10 días de antigüedad.
     *
     * @return void
     */
    protected function cleanupOldFailedTasks(): void
    {
        try {
            $cutoffDate = now()->subDays(10);
            $deletedCount = ScrapingTask::where('status', 'failed')
                                        ->where('failed_at', '<=', $cutoffDate)
                                        ->delete();
            
            if ($deletedCount > 0) {
                $this->info("✅ Se eliminaron {$deletedCount} tareas fallidas antiguas (más de 10 días).");
                Log::info("Limpieza: Se eliminaron {$deletedCount} tareas fallidas antiguas.");
            }
        } catch (Throwable $e) {
            $this->error("No se pudo realizar la limpieza de tareas antiguas: " . $e->getMessage());
            Log::error("Error durante la limpieza de tareas fallidas antiguas", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
