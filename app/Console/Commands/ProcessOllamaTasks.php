<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OllamaTasker;
use GuzzleHttp\Client;

class ProcessOllamaTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ollama:process-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending Ollama tasks by calling the Ollama API continuously';

    /**
     * A Guzzle client instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // Configuramos Guzzle con un timeout largo (por ejemplo, 300 segundos)
        $this->client = new Client([
            'timeout' => 300,
        ]);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Starting infinite loop for processing Ollama tasks.");

        // Bucle infinito
        while (true) {
            try {
                // Buscar tareas pendientes en ollama_taskers:
                // tareas con prompt definido y sin respuesta (null o cadena vacía)
                $tasks = OllamaTasker::whereNotNull('prompt')
                                        ->where(function ($query) {
                                            $query->whereNull('response')
                                                ->orWhere('response', '');
                                        })
                                        ->whereNull('error') // Solo procesamos tareas sin error previo
                                        ->get();


                if ($tasks->isEmpty()) {
                    $this->info("No pending Ollama tasks found. Sleeping for 10 seconds.");
                    sleep(10);
                    continue;
                }

                foreach ($tasks as $task) {
                    $this->info("Processing Ollama task ID: {$task->id}");

                    try {
                        // Construir el prompt completo
                        $prefix = "Crea una publicación profesional y atractiva para LinkedIn, pero sin escribir nada de cabezal sobre te escribo una publicacion o algo parecido, pero sin poner aqui tienes una publicacion para linkedin etc, siguiendo estas directrices:";
                        $prompt = $task->prompt;
                        $suffix = "Mantén un tono profesional, cercano y humano. Usa un lenguaje claro, inspirador y persuasivo que motive a la acción. Si no tienes las informaciones para completar tus textos, no pongas la parte que te falta. Pon solo datos concretos y que tienes; no inventes nada y tampoco dejes partes para que el usuario las complete. Si no existen los datos como nombre, usuario, empresa, etc., no uses esto. Y no pones nunca Aqui tienes o Aqui esta  tu texto . Escribe directamente el texto. No pongas nada más que el texto, no pongas nada de comentarios o explicaciones adicionales.";
                        //$prompt = $prefix . " " . $textArea . " " . $suffix;

                        // Preparar la carga útil para la API de Ollama
                        $payload = [
                            'model'    => env('OLLAMA_MODEL_DEFAULT'),
                            'messages' => [
                                [
                                    'role'    => 'user',
                                    'content' => $prompt,
                                ],
                            ],
                        ];

                        $fullUrl = rtrim(env('OLLAMA_URL'), '/') . '/api/chat';
                        $this->info("Calling Ollama API for task ID {$task->id}");

                        // Realizar la petición con streaming
                        $response = $this->client->request('POST', $fullUrl, [
                            'json'   => $payload,
                            'stream' => true,
                            'headers' => [
                                'Content-Type' => 'application/json',
                            ],
                        ]);

                        $body = $response->getBody();
                        $combinedContent = '';
                        $buffer = '';

                        $this->info("Reading stream for task ID: {$task->id}");

                        while (!$body->eof()) {
                            // Leer un bloque (1024 bytes, por ejemplo)
                            $chunk = $body->read(1024);
                            $this->info("Chunk read (length " . strlen($chunk) . ") for task ID: {$task->id}");
                            $buffer .= $chunk;
                            while (($pos = strpos($buffer, "\n")) !== false) {
                                $line = substr($buffer, 0, $pos);
                                $buffer = substr($buffer, $pos + 1);
                                $line = trim($line);
                                if (empty($line)) {
                                    continue;
                                }
                                $this->info("Processing line for task ID {$task->id}: " . substr($line, 0, 100));
                                $decoded = json_decode($line, true);
                                if (!$decoded) {
                                    $this->error("Error decoding line for task ID {$task->id}: " . $line);
                                    continue;
                                }
                                if (isset($decoded['message']['content'])) {
                                    $this->info("Fragment: " . $decoded['message']['content'] . " for task ID: {$task->id}");
                                    $combinedContent .= $decoded['message']['content'];
                                }
                                if (isset($decoded['done']) && $decoded['done'] === true) {
                                    $this->info("Done flag found for task ID: {$task->id}. Ending stream read.");
                                    break 2; // Sale de ambos bucles
                                }
                            }
                        }

                        if (empty($combinedContent)) {
                            $this->error("No content found from API for task ID: {$task->id}");
                            $task->error = 'No content found from API';
                            // En caso de error, se guarda en la columna error
                            $task->save();
                            continue;
                        }

                        // Limpiar el contenido (eliminar secciones entre <think> y </think>)
                        $cleanContent = $this->cleanContent($combinedContent);
                        // Actualizamos la tarea en ollama_taskers con la respuesta recibida
                        $task->response = $cleanContent;
                        // Si se desea, se podría actualizar algún campo de status
                        $task->save();

                        $this->info("Ollama task ID {$task->id} processed successfully.");

                    } catch (\Exception $e) {
                        $this->error("Error processing Ollama task ID {$task->id}: " . $e->getMessage());
                        $task->error = $e->getMessage();
                        $task->save();
                    }
                }
            } catch (\Exception $e) {
                $this->error("Exception in processing loop: " . $e->getMessage());
            }
            // Espera unos segundos antes de la siguiente iteración
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
