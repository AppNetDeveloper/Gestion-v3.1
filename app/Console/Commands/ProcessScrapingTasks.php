<?php

namespace App\Console\Commands;

use App\Models\ScrapingTask;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Throwable;

class ProcessScrapingTasks extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'scraping:run-worker {--sleep=15 : Segundos de espera entre ciclos de trabajo.}';

    /**
     * The console command description.
     */
    protected $description = 'Worker para despachar y gestionar tareas de scraping de forma continua y robusta.';

    /**
     * URL base de la API de scraping.
     */
    protected ?string $apiBaseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->apiBaseUrl = config('services.scraping.url');
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->apiBaseUrl) {
            $this->error('La URL de la API de scraping no está configurada en services.scraping.url');
            return Command::FAILURE;
        }

        $sleep = (int) $this->option('sleep');
        $this->info("Iniciando worker de scraping (ciclos de {$sleep}s)...");

        while (true) {
            try {
                $this->dispatchPendingTasks();
                $this->manageStuckTasks();
                $this->cleanupOldFailedTasks();
            } catch (Throwable $e) {
                Log::critical('Error fatal en el worker de scraping.', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error('Error fatal: ' . $e->getMessage());
            }

            $this->info("Ciclo completado. Esperando {$sleep} segundos...");
            sleep($sleep);
        }
    }

    /**
     * Busca y despacha tareas pendientes a la API de scraping.
     */
    private function dispatchPendingTasks(): void
    {
        $tasks = ScrapingTask::where('status', 'pending')
            ->orderBy('retry_attempts', 'asc')
            ->orderBy('created_at', 'asc')
            ->limit(10) // Procesar en lotes de 10 para no sobrecargar
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No hay tareas pendientes para despachar.');
            return;
        }

        $this->info("Se encontraron {$tasks->count()} tareas pendientes. Despachando...");

        foreach ($tasks as $task) {
            $this->sendTaskToApi($task);
        }
    }

    /**
     * Envía una tarea específica a la API de scraping.
     */
    private function sendTaskToApi(ScrapingTask $task): void
    {
        try {
            $task->update(['status' => 'processing', 'last_attempt_at' => now()]);

            $payload = $this->prepareTaskPayload($task);
            $response = Http::timeout(60)->post("{$this->apiBaseUrl}/buscar-google-ddg-limpio", $payload);

            Log::debug('Respuesta recibida de la API de Scraping', [
                'task_id' => $task->id,
                'status_code' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            if ($response->successful() && $response->json('task_id')) {
                $task->update(['api_task_id' => $response->json('task_id')]);
                $this->info(" -> Tarea #{$task->id} enviada. API_ID: {$task->api_task_id}");
            } else {
                $this->handleFailedAttempt($task, 'La API no devolvió un task_id o la respuesta falló.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (ConnectionException $e) {
            $this->handleFailedAttempt($task, 'Error de conexión con la API de scraping.', ['error' => $e->getMessage()]);
        } catch (Throwable $e) {
            $this->handleFailedAttempt($task, 'Error inesperado al despachar la tarea.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Gestiona tareas que han superado el tiempo máximo en estado 'processing'.
     */
    private function manageStuckTasks(): void
    {
        $timeout = config('scraping.timeout', 90);
        $stuckTasks = ScrapingTask::withCount('contacts')
            ->where('status', 'processing')
            ->where('updated_at', '<=', now()->subMinutes($timeout))
            ->get();

        if ($stuckTasks->isEmpty()) {
            return;
        }

        $this->warn("Se encontraron {$stuckTasks->count()} tareas atascadas (timeout: {$timeout} min). Gestionando...");

        foreach ($stuckTasks as $task) {
            if ($task->contacts_count > 0) {
                // La tarea se atascó, pero encontró datos. Es un éxito.
                $task->update([
                    'status' => 'completed',
                    'error_message' => "Completada por watchdog. La tarea superó el timeout pero tenía {$task->contacts_count} contactos.",
                ]);
                $this->info(" -> Tarea atascada #{$task->id} marcada como COMPLETADA (encontró contactos).");
            } else {
                // La tarea se atascó y no encontró nada. Se considera un intento fallido.
                $this->handleFailedAttempt($task, "La tarea superó el timeout de {$timeout} minutos sin generar resultados.");
            }
        }
    }

    /**
     * Gestiona un intento fallido, reintentando o marcando como fallo definitivo.
     */
    private function handleFailedAttempt(ScrapingTask $task, string $errorMessage, array $context = []): void
    {
        $maxRetries = config('scraping.max_retries', 3);
        $newAttemptCount = $task->retry_attempts + 1;

        if ($newAttemptCount < $maxRetries) {
            $task->update([
                'status' => 'pending', // Devolver a la cola para reintentar
                'retry_attempts' => $newAttemptCount,
                'api_task_id' => null, // Limpiar para que se genere uno nuevo
                'error_message' => $errorMessage,
            ]);
            $this->warn(" -> Tarea #{$task->id} falló. Se reintentará (intento {$newAttemptCount}/{$maxRetries}).");
            Log::warning('Intento de scraping fallido, se reintentará.', array_merge(['task_id' => $task->id, 'error' => $errorMessage], $context));
        } else {
            $task->update([
                'status' => 'failed',
                'retry_attempts' => $newAttemptCount,
                'failed_at' => now(),
                'error_message' => $errorMessage,
            ]);
            $this->error(" -> Tarea #{$task->id} ha fallado definitivamente tras {$newAttemptCount} intentos.");
            Log::error('La tarea de scraping falló definitivamente.', array_merge(['task_id' => $task->id, 'error' => $errorMessage], $context));
        }
    }

    /**
     * Prepara el payload para enviar a la API de scraping.
     */
    private function prepareTaskPayload(ScrapingTask $task): array
    {
        return [
            'callback_url' => URL::route('api.scraping.callback', ['task_id' => $task->id]),
            'task_type' => $task->source,
            'keyword' => $task->keyword,
            'task_id' => $task->id, // Enviar nuestro ID para trazabilidad
            'data' => json_decode($task->data, true) ?: [],
        ];
    }

    /**
     * Elimina tareas fallidas antiguas para mantener la base de datos limpia.
     */
    private function cleanupOldFailedTasks(): void
    {
        try {
            $cutoffDate = now()->subDays(15);
            $deletedCount = ScrapingTask::where('status', 'failed')
                ->where('failed_at', '<=', $cutoffDate)
                ->delete();

            if ($deletedCount > 0) {
                $this->info("Se eliminaron {$deletedCount} tareas fallidas con más de 15 días de antigüedad.");
            }
        } catch (Throwable $e) {
            Log::error('No se pudo realizar la limpieza de tareas antiguas.', ['error' => $e->getMessage()]);
        }
    }
}
