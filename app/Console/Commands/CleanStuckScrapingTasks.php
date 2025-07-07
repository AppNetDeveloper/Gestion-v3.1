<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScrapingTask;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanStuckScrapingTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraping:clean-stuck-tasks {--minutes=5 : Número de minutos para considerar una tarea como atascada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia las tareas de scraping que están atascadas en estado "processing"';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $minutes = (int)$this->option('minutes');
        $threshold = now()->subMinutes($minutes);
        
        $this->info("Buscando tareas atascadas (más de {$minutes} minutos en procesamiento)...");
        
        // Buscar tareas atascadas
        $stuckTasks = ScrapingTask::where('status', 'processing')
                                ->where(function($query) use ($threshold) {
                                    $query->where('updated_at', '<=', $threshold)
                                          ->orWhereNull('updated_at');
                                })
                                ->get();
        
        if ($stuckTasks->isEmpty()) {
            $this->info('No se encontraron tareas atascadas.');
            return 0;
        }
        
        $this->info("Se encontraron {$stuckTasks->count()} tareas atascadas.");
        
        $bar = $this->output->createProgressBar($stuckTasks->count());
        $bar->start();
        
        $processed = 0;
        $now = now();
        
        foreach ($stuckTasks as $task) {
            try {
                // Verificar si la tarea tiene contactos
                $hasContacts = $task->contacts()->exists();
                $attempts = $task->retry_attempts + 1;
                $maxAttempts = 3;
                
                if ($hasContacts) {
                    // Si tiene contactos, marcar como completada
                    $task->update([
                        'status' => 'completed',
                        'last_attempt_at' => $now,
                        'api_task_id' => null,
                        'updated_at' => $now
                    ]);
                    $this->info("\n✅ Tarea ID {$task->id} marcada como completada (tiene contactos).");
                } elseif ($attempts < $maxAttempts) {
                    // Si no tiene contactos pero aún tiene intentos, poner en pending
                    $task->update([
                        'status' => 'pending',
                        'retry_attempts' => $attempts,
                        'last_attempt_at' => $now,
                        'api_task_id' => null,
                        'error_message' => 'Tiempo de espera agotado',
                        'updated_at' => $now
                    ]);
                    $this->warn("\n🔄 Tarea ID {$task->id} puesta en cola para reintento ({$attempts}/{$maxAttempts}).");
                } else {
                    // Si no tiene contactos y no quedan intentos, marcar como fallida
                    $task->update([
                        'status' => 'failed',
                        'failed_at' => $now,
                        'last_attempt_at' => $now,
                        'retry_attempts' => $attempts,
                        'api_task_id' => null,
                        'error_message' => 'Tiempo de espera agotado - Sin contactos después de varios intentos',
                        'updated_at' => $now
                    ]);
                    $this->error("\n❌ Tarea ID {$task->id} marcada como fallida (sin contactos después de {$maxAttempts} intentos).");
                }
                
                $processed++;
                
            } catch (\Exception $e) {
                Log::error("Error al limpiar tarea atascada ID {$task->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->error("\n❌ Error al procesar tarea ID {$task->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        
        $this->info("\n\n✅ Proceso completado. Se procesaron {$processed} tareas atascadas.");
        return 0;
    }
}
