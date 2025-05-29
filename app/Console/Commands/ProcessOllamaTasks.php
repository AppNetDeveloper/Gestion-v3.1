<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OllamaTasker; // Asegúrate de que este modelo exista y esté configurado correctamente
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
// use GuzzleHttp\Psr7\Request; // No es estrictamente necesario importarlo si Pool lo maneja
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
            // Podrías añadir otras configuraciones por defecto para Guzzle aquí si es necesario
            // 'connect_timeout' => 10, // Ejemplo: tiempo de espera para la conexión inicial
            // 'read_timeout' => $timeout, // Tiempo de espera para leer el stream
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
        if ($concurrency <= 0) {
            $this->warn("OLLAMA_MULTI_TASK is set to {$concurrency}. Using default concurrency of 1.");
            $concurrency = 1;
        }

        $ollamaUrl = env('OLLAMA_URL');
        if (!$ollamaUrl) {
            $this->error("OLLAMA_URL environment variable is not set. Aborting.");
            return Command::FAILURE; // Usar Command::FAILURE para errores
        }
        $fullUrl = rtrim($ollamaUrl, '/') . '/api/chat'; // Asumiendo que seguimos con /api/chat

        $ollamaModel = env('OLLAMA_MODEL_DEFAULT');
        if (!$ollamaModel) {
            $this->error("OLLAMA_MODEL_DEFAULT environment variable is not set. Aborting.");
            return Command::FAILURE; // Usar Command::FAILURE para errores
        }

        // Bucle infinito
        while (true) {
            try {
                // Buscar tareas pendientes
                $tasks = OllamaTasker::whereNotNull('prompt')
                    ->where(function ($query) {
                        $query->whereNull('response')
                              ->orWhere('response', '');
                    })
                    //->whereNull('error') // Solo procesamos tareas sin error previo
                    ->orderBy('created_at', 'asc') // Procesar las más antiguas primero
                    ->limit($concurrency * 2) // Tomar un poco más para mantener el pool ocupado
                    ->get();

                if ($tasks->isEmpty()) {
                    $this->info("No pending Ollama tasks found. Sleeping for 0.5 seconds.");
                    sleep(0.5);
                    continue;
                }

                // Asegurar que $taskList es un array con índices numéricos secuenciales
                $taskList = array_values($tasks->all());

                // Generador de solicitudes asíncronas
                $requests = function () use ($taskList, $fullUrl, $ollamaModel) {
                    foreach ($taskList as $task) { // $index no se usa directamente para yield, pero es útil para depurar aquí
                        $prompt = $task->prompt;
                        $payload = [
                            'model'    => $ollamaModel,
                            'messages' => [
                                [
                                    'role'    => 'user',
                                    'content' => $prompt,
                                ],
                            ],
                            'stream'   => true, // Asegurarse de que el stream esté habilitado
                        ];

                        // Usar una función que devuelve una promesa
                        yield function () use ($fullUrl, $payload) {
                            return $this->client->postAsync($fullUrl, [
                                'json'    => $payload,
                                'headers' => [
                                    'Content-Type' => 'application/json',
                                    'Accept'       => 'application/x-ndjson', // Ollama suele devolver ndjson para streams
                                ],
                            ]);
                        };
                    }
                };

                $this->info("Processing " . count($taskList) . " tasks concurrently (max concurrency: {$concurrency}).");

                $pool = new Pool($this->client, $requests(), [
                    'concurrency' => $concurrency,
                    'fulfilled' => function ($response, $index) use ($taskList) {
                        // Se ha completado una solicitud. Procesamos la respuesta
                        if (!isset($taskList[$index])) {
                            $this->error("Task not found for fulfilled index {$index}. This should not happen.");
                            return;
                        }
                        $task = $taskList[$index];
                        try {
                            $bodyContent = $response->getBody()->getContents();

                            if (empty($bodyContent)) {
                                $this->error("No content (empty body) from API for task ID: {$task->id}");
                                $task->error = 'No content from API (empty body)';
                                $task->save();
                                return;
                            }

                            $combinedContent = '';
                            $lines = explode("\n", trim($bodyContent));
                            $ollamaStreamError = null; // Para almacenar el error específico de Ollama si ocurre

                            foreach ($lines as $line) {
                                $line = trim($line);
                                if (empty($line)) {
                                    continue;
                                }
                                $decoded = json_decode($line, true);

                                if ($decoded && isset($decoded['message']['content'])) {
                                    $combinedContent .= $decoded['message']['content'];
                                } elseif ($decoded && isset($decoded['error'])) {
                                    // Capturar el error del stream de Ollama
                                    $ollamaStreamError = "Ollama API stream error: " . $decoded['error'];
                                    $this->error("Ollama API returned an error in stream for task ID {$task->id}: " . $decoded['error']);
                                    // No guardamos aquí, dejamos que la lógica de combinedContent vacío lo maneje
                                    // o si hay contenido parcial, se guardará y el error también se puede registrar.
                                }
                                // $this->info("Decoded line for task {$task->id}: " . print_r($decoded, true)); // Descomentar para depuración detallada
                            }

                            if (empty($combinedContent)) {
                                // CORRECCIÓN: Cambiado de $this->warning a $this->warn
                                $this->warn("Raw API response for task ID {$task->id} (resulted in empty combined content): " . $bodyContent);
                                $this->error("Combined content empty after processing stream for task ID: {$task->id}");
                                // Priorizar el error de stream de Ollama si existe
                                $task->error = $ollamaStreamError ?: 'Combined content empty after processing stream';
                                $task->save();
                                return;
                            }

                            // Limpiar el contenido
                            $cleanContent = $this->cleanContent($combinedContent);
                            $task->response = $cleanContent;
                            $task->error = null; // Limpiar errores previos si la tarea se procesa con éxito
                            $task->save();

                            $this->info("Ollama task ID {$task->id} processed successfully.");

                        } catch (\Exception $e) { // Captura excepciones más generales durante el procesamiento de la respuesta
                            $this->error("Exception processing response for task ID {$task->id}: " . $e->getMessage());
                            $task->error = "PHP Exception: " . $e->getMessage();
                            $task->save();
                        }
                    },
                    'rejected' => function ($reason, $index) use ($taskList) {
                        // En caso de error en la solicitud (ej. timeout, error de conexión)
                        if (!isset($taskList[$index])) {
                            $this->error("Task not found for rejected index {$index}. This should not happen.");
                            return;
                        }
                        $task = $taskList[$index];
                        $errorMessage = 'Unknown error during Guzzle request';

                        if ($reason instanceof RequestException) {
                            $errorMessage = "RequestException: " . $reason->getMessage();
                            if ($reason->hasResponse() && $reason->getResponse()) {
                                $responseBody = $reason->getResponse()->getBody();
                                if ($responseBody) {
                                    // Es importante rebobinar el stream del cuerpo antes de leerlo si ya se leyó
                                    if ($responseBody->isReadable() && $responseBody->isSeekable()) {
                                        $responseBody->rewind();
                                    }
                                    $errorMessage .= " | Response: " . $responseBody->getContents();
                                }
                            }
                        } elseif ($reason instanceof \Exception) {
                            $errorMessage = get_class($reason) . ": " . $reason->getMessage();
                        }

                        $this->error("Error (rejected promise) processing task ID {$task->id}: {$errorMessage}");
                        $task->error = $errorMessage;
                        $task->save();
                    },
                ]);

                // Ejecutar todas las solicitudes concurrentes y esperar a que terminen
                $promise = $pool->promise();
                $promise->wait();

            } catch (\Throwable $e) { // Usar Throwable para capturar Exceptions y Errors
                $this->error("General exception in processing loop: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
                $this->error("Stack trace: " . $e->getTraceAsString()); // Añadir stack trace para más detalles
                // Considera si quieres parar el bucle o solo loguear y continuar
                sleep(30); // Esperar un poco más si hay un error general
            }

            $this->info("Loop finished, sleeping for 0.5 seconds before next check.");
            sleep(0.5);
        }

        return Command::SUCCESS; // o 0
    }

    /**
     * Remove content between <think> and </think>.
     *
     * @param string $text
     * @return string
     */
    private function cleanContent(string $text): string
    {
        // Esta expresión regular eliminará <think>...</think> y cualquier contenido entre ellos,
        // incluyendo saltos de línea si 's' está activado.
        return trim(preg_replace('/<think>.*?<\/think>/s', '', $text));
    }
}
