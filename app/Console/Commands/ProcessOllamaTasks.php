<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OllamaTasker;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Exception\RequestException;

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

        // Usamos la variable OLLAMA_CONNECTION_TIMEOUT, por defecto 300 segundos si no está definida.
        $timeout = (int) env('OLLAMA_CONNECTION_TIMEOUT', 300);

        $this->client = new Client([
            'timeout' => $timeout,
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

        // Valor de concurrencia. Si no se define en .env, se usa 1.
        $concurrency = (int) env('OLLAMA_MULTI_TASK', 1);
        $fullUrl = rtrim(env('OLLAMA_URL'), '/') . '/api/chat';

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

                // Crear un array para relacionar el índice con la tarea correspondiente
                $taskList = $tasks->all();

                // Generador de solicitudes asíncronas
                $requests = function () use ($taskList, $fullUrl) {
                    foreach ($taskList as $task) {
                        // Construir el prompt completo (se pueden incluir prefijos y sufijos según necesidad)
                        $prompt = $task->prompt;
                        $payload = [
                            'model'    => env('OLLAMA_MODEL_DEFAULT'),
                            'messages' => [
                                [
                                    'role'    => 'user',
                                    'content' => $prompt,
                                ],
                            ],
                        ];

                        yield function () use ($fullUrl, $payload) {
                            return $this->client->postAsync($fullUrl, [
                                'json'    => $payload,
                                'headers' => [
                                    'Content-Type' => 'application/json',
                                ],
                            ]);
                        };
                    }
                };

                $this->info("Processing " . count($taskList) . " tasks concurrently (concurrency: {$concurrency}).");

                $pool = new Pool($this->client, $requests(), [
                    'concurrency' => $concurrency,
                    'fulfilled' => function ($response, $index) use ($taskList) {
                        // Se ha completado una solicitud. Procesamos la respuesta
                        $task = $taskList[$index];
                        try {
                            $bodyContent = $response->getBody()->getContents();

                            if (empty($bodyContent)) {
                                $this->error("No content found from API for task ID: {$task->id}");
                                $task->error = 'No content found from API';
                                $task->save();
                                return;
                            }

                            // Procesar cada línea y combinar los fragmentos
                            $combinedContent = '';
                            $lines = explode("\n", $bodyContent);
                            foreach ($lines as $line) {
                                $line = trim($line);
                                if (empty($line)) {
                                    continue;
                                }
                                $decoded = json_decode($line, true);
                                if (!$decoded || !isset($decoded['message']['content'])) {
                                    continue;
                                }
                                $combinedContent .= $decoded['message']['content'];
                            }

                            if (empty($combinedContent)) {
                                $this->error("Combined content empty for task ID: {$task->id}");
                                $task->error = 'Combined content empty';
                                $task->save();
                                return;
                            }

                            // Limpiar el contenido (eliminar secciones entre <think> y </think>)
                            $cleanContent = $this->cleanContent($combinedContent);
                            $task->response = $cleanContent;
                            $task->save();

                            $this->info("Ollama task ID {$task->id} processed successfully.");
                        } catch (\Exception $e) {
                            $this->error("Error processing response for task ID {$task->id}: " . $e->getMessage());
                            $task->error = $e->getMessage();
                            $task->save();
                        }
                    },
                    'rejected' => function ($reason, $index) use ($taskList) {
                        // En caso de error en la solicitud
                        $task = $taskList[$index];
                        $errorMessage = $reason instanceof RequestException
                            ? $reason->getMessage()
                            : 'Unknown error';
                        $this->error("Error processing task ID {$task->id}: {$errorMessage}");
                        $task->error = $errorMessage;
                        $task->save();
                    },
                ]);

                // Ejecutar todas las solicitudes concurrentes y esperar a que terminen
                $promise = $pool->promise();
                $promise->wait();

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
