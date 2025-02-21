<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TaskerLinkedin;
use App\Models\OllamaTasker;
use Carbon\Carbon;

class ProcessLinkedinOllamaTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ollama:process-linkedin-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process LinkedIn tasks by saving them in ollama_taskers and checking for responses';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Starting infinite loop for processing LinkedIn tasks with Ollama integration.");

        // Bucle infinito para el procesamiento continuo.
        while (true) {
            try {
                // Buscamos tareas en tasker_linkedins que:
                // - Tengan prompt definido.
                // - Tengan status 'pending' (o el estado que determines)
                // - Y, o bien no tienen asignado ollama_tasker_id, o tienen asignado pero aun no se recibió respuesta.
                $tasks = TaskerLinkedin::where('status', 'pending')
                    ->whereNotNull('prompt')
                    ->where(function ($query) {
                        $query->whereNull('ollama_tasker_id')
                              ->orWhere(function ($query2) {
                                  $query2->whereNotNull('ollama_tasker_id')
                                         ->where(function ($q) {
                                             $q->whereNull('response')
                                               ->orWhere('response', '');
                                         });
                              });
                    })
                    ->get();

                if ($tasks->isEmpty()) {
                    $this->info("No pending tasks found. Sleeping for 10 seconds.");
                    sleep(10);
                    continue;
                }

                foreach ($tasks as $task) {
                    $this->info("Processing task ID: {$task->id}");

                     // Construir el prompt completo
                     $prefix = "Crea una publicación profesional y atractiva para LinkedIn utilizando los siguientes datos:";
                     $textArea = $task->prompt;
                     $suffix = "Mantén un tono profesional, cercano y humano. Emplea el idioma y lenguaje de los datos proporcionados. Usa un lenguaje claro, inspirador y persuasivo que motive a la acción. Si falta información, omite esa parte. Utiliza únicamente datos concretos y verificables, sin inventar nada ni dejar espacios incompletos. No agregues encabezados, comentarios o explicaciones adicionales.";
                     $fullPrompt = $prefix . " " . $textArea . " " . $suffix;



                    // Si la tarea aún no tiene asignado un ollama_tasker_id, la creamos.
                    if (is_null($task->ollama_tasker_id)) {
                        $this->info("No Ollama task associated. Creating new OllamaTasker for task ID {$task->id}.");

                        // Crear una nueva entrada en ollama_taskers.
                        // Se usa el campo 'model' de la tarea en ollama_taskers si se ha definido en la migración ALTER,
                        // de lo contrario se podría asignar env('OLLAMA_MODEL_DEFAULT') o dejarlo nulo.
                        $modelToUse = $task->model ?? env('OLLAMA_MODEL_DEFAULT');

                        $ollamaTask = new OllamaTasker();
                        $ollamaTask->prompt = $fullPrompt;
                        $ollamaTask->model = $modelToUse;
                        // En este ejemplo, inicialmente no habrá response ni error.
                        $ollamaTask->response = null;
                        $ollamaTask->error = null;
                        $ollamaTask->save();

                        // Actualizar la tarea original con el id generado.
                        $task->ollama_tasker_id = $ollamaTask->id;
                        $task->save();

                        $this->info("Created OllamaTasker with ID {$ollamaTask->id} for task ID {$task->id}.");
                    } else {
                        // Si ya tiene un ollama_tasker_id, buscamos la respuesta en la tabla ollama_taskers.
                        $this->info("Task ID {$task->id} already has OllamaTasker ID {$task->ollama_tasker_id}. Checking for response.");
                        $ollamaTask = OllamaTasker::find($task->ollama_tasker_id);
                        if ($ollamaTask) {
                            if (!empty($ollamaTask->response)) {
                                // Si se recibió respuesta, actualizamos la tarea en tasker_linkedins.
                                $cleanResponse = $this->cleanContent($ollamaTask->response);
                                $task->response = $cleanResponse;
                                // Puedes actualizar el status a 'processing' o 'completed' según convenga.
                                $task->status = 'processing';
                                $task->save();
                                $this->info("Task ID {$task->id} updated with response from OllamaTasker ID {$ollamaTask->id}.");
                            } else {
                                $this->info("OllamaTasker ID {$ollamaTask->id} still without response for task ID {$task->id}.");
                            }
                        } else {
                            // En caso de inconsistencia, reiniciamos el campo.
                            $this->error("OllamaTasker with ID {$task->ollama_tasker_id} not found. Resetting field for task ID {$task->id}.");
                            $task->ollama_tasker_id = null;
                            $task->save();
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error("Exception in processing loop: " . $e->getMessage());
            }
            // Esperar unos segundos antes de la siguiente iteración.
            sleep(5);
        }

        // Nunca se alcanza este return, pero se requiere por la firma.
        return 0;
    }

    /**
     * Remove content between <think> and </think>.
     *
     * @param string $text
     * @return string
     */
    private function cleanContent(string $text): string
    {
        return trim(preg_replace('/<think>.*?<\/think>/s', '', $text));
    }
}
