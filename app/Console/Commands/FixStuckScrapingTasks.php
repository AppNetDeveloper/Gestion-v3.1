<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScrapingTask;
use Illuminate\Support\Facades\Log;

class FixStuckScrapingTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraping:fix-stuck {--task_id= : ID específico de tarea a reparar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repara tareas de scraping atascadas en bucles infinitos de reintento';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $taskId = $this->option('task_id');
        
        if ($taskId) {
            // Arreglar una tarea específica
            $this->fixSpecificTask($taskId);
        } else {
            // Arreglar todas las tareas atascadas
            $this->fixAllStuckTasks();
        }

        return Command::SUCCESS;
    }

    /**
     * Arregla una tarea específica por su ID
     *
     * @param int $taskId
     * @return void
     */
    protected function fixSpecificTask($taskId)
    {
        $task = ScrapingTask::find($taskId);
        
        if (!$task) {
            $this->error("No se encontró la tarea con ID: {$taskId}");
            return;
        }
        
        $this->info("Reparando tarea ID: {$task->id} (Estado: {$task->status}, Intentos: {$task->retry_attempts})");
        
        // Verificar si la tarea está atascada en un bucle
        $isStuck = $task->status === 'processing' || 
                  ($task->status === 'pending' && $task->retry_attempts > 0 && $task->retry_attempts < 3);
        
        if (!$isStuck) {
            $this->info("La tarea ID: {$task->id} no parece estar atascada. Estado actual: {$task->status}");
            return;
        }
        
        // Verificar si tiene contactos
        $hasContacts = $task->contacts()->exists();
        
        if ($hasContacts) {
            // Si tiene contactos, marcarla como completada
            $task->update([
                'status' => 'completed',
                'last_attempt_at' => now(),
                'api_task_id' => null
            ]);
            $this->info("✅ Tarea ID {$task->id} marcada como completada (tiene contactos).");
            Log::info("Tarea ID {$task->id} reparada y marcada como completada (tenía contactos).");
        } else {
            // Si no tiene contactos, marcarla como fallida
            $task->update([
                'status' => 'failed',
                'failed_at' => now(),
                'last_attempt_at' => now(),
                'retry_attempts' => 3, // Establecer al máximo para evitar más reintentos
                'api_task_id' => null,
                'error_message' => 'Tarea marcada como fallida manualmente para detener bucle de reintentos'
            ]);
            $this->error("❌ Tarea ID {$task->id} marcada como fallida para detener bucle de reintentos.");
            Log::warning("Tarea ID {$task->id} reparada y marcada como fallida para detener bucle de reintentos.");
        }
    }

    /**
     * Arregla todas las tareas atascadas en el sistema
     *
     * @return void
     */
    protected function fixAllStuckTasks()
    {
        // Buscar tareas en procesamiento que podrían estar atascadas
        $stuckTasks = ScrapingTask::where(function($query) {
                            $query->where('status', 'processing')
                                  ->orWhere(function($q) {
                                      $q->where('status', 'pending')
                                        ->where('retry_attempts', '>', 0)
                                        ->where('retry_attempts', '<', 3);
                                  });
                        })
                        ->get();
        
        if ($stuckTasks->isEmpty()) {
            $this->info("No se encontraron tareas atascadas en el sistema.");
            return;
        }
        
        $this->info("Se encontraron {$stuckTasks->count()} tareas potencialmente atascadas.");
        
        $fixedCount = 0;
        
        foreach ($stuckTasks as $task) {
            $this->info("Analizando tarea ID: {$task->id} (Estado: {$task->status}, Intentos: {$task->retry_attempts})");
            
            // Verificar si tiene contactos
            $hasContacts = $task->contacts()->exists();
            
            if ($hasContacts) {
                // Si tiene contactos, marcarla como completada
                $task->update([
                    'status' => 'completed',
                    'last_attempt_at' => now(),
                    'api_task_id' => null
                ]);
                $this->info("✅ Tarea ID {$task->id} marcada como completada (tiene contactos).");
                Log::info("Tarea ID {$task->id} reparada y marcada como completada (tenía contactos).");
            } else {
                // Si no tiene contactos, marcarla como fallida
                $task->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'last_attempt_at' => now(),
                    'retry_attempts' => 3, // Establecer al máximo para evitar más reintentos
                    'api_task_id' => null,
                    'error_message' => 'Tarea marcada como fallida manualmente para detener bucle de reintentos'
                ]);
                $this->error("❌ Tarea ID {$task->id} marcada como fallida para detener bucle de reintentos.");
                Log::warning("Tarea ID {$task->id} reparada y marcada como fallida para detener bucle de reintentos.");
            }
            
            $fixedCount++;
        }
        
        $this->info("✅ Se repararon {$fixedCount} tareas atascadas.");
    }
}
