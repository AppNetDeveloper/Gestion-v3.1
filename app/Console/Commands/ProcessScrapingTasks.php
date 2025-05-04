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
                // Buscar la tarea pendiente más antigua sin api_task_id
                $task = ScrapingTask::where('status', 'pending')
                                    ->whereNull('api_task_id')
                                    ->orderBy('created_at', 'asc')
                                    ->first();

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
    protected function processSingleTask(ScrapingTask $task): void
    {
        // Determinar el endpoint y preparar payload
        $endpoint = '';
        $payload = [];
        $callbackUrl = '';
        $callbackRouteName = 'api.scraping.callback'; // Nombre de la ruta de callback
        $fallbackCallbackPath = '/api/scraping-callback'; // Ruta fallback

        try {
            // Generar la URL de callback (¡Asegúrate que la ruta existe!)
             $callbackUrl = route($callbackRouteName);
        } catch (\Exception $e) {
             Log::warning("[Task ID: {$task->id}] No se pudo generar la URL de callback con nombre '{$callbackRouteName}'. Usando URL base. Error: " . $e->getMessage());
             // *** CORREGIDO: Eliminar barras duplicadas al construir fallback URL ***
             $appUrl = rtrim(config('app.url', 'http://localhost'), '/'); // Elimina / del final de APP_URL
             $path = ltrim($fallbackCallbackPath, '/'); // Elimina / del inicio de la ruta
             $callbackUrl = $appUrl . '/' . $path; // Une con una sola /
        }

        switch ($task->source) {
            case 'google_ddg':
                $endpoint = '/buscar-google-ddg-limpio';
                $payload = [
                    'keyword' => $task->keyword,
                    'results' => 100, // Ajustado, puedes hacerlo configurable
                    'callback_url' => $callbackUrl,
                ];
                break;
            case 'empresite':
                $endpoint = '/buscar-empresite';
                $payload = [
                    'actividad' => $task->keyword,
                    'provincia' => $task->region,
                    'paginas' => 5, // Ajustado, puedes hacerlo configurable
                    'callback_url' => $callbackUrl,
                ];
                break;
            case 'paginas_amarillas':
                $endpoint = '/buscar-paginas-amarillas';
                $payload = [
                    'actividad' => $task->keyword,
                    'provincia' => $task->region,
                    'paginas' => 5, // Ajustado, aunque Python use 1, enviamos lo solicitado
                    'callback_url' => $callbackUrl,
                ];
                break;
            default:
                $this->error("Fuente desconocida: {$task->source} para Tarea ID: {$task->id}");
                $task->status = 'failed';
                $task->save();
                Log::error("Fuente desconocida '{$task->source}' para ScrapingTask ID {$task->id}.");
                return; // Salir de esta función si la fuente es desconocida
        }

        // Obtener URL del servidor Python
        $scrapingServerUrl = config('services.scraping.url');
        if (!$scrapingServerUrl) {
             $this->error("La URL del servidor de scraping no está configurada en config/services.php (services.scraping.url) para Tarea ID: {$task->id}");
             Log::error("SCRAPING_SERVER_URL no configurada al procesar Tarea ID {$task->id}.");
             return;
        }
        // *** CORREGIDO: Eliminar barras duplicadas al construir URL de API ***
        $baseApiUrl = rtrim($scrapingServerUrl, '/');
        $apiEndpoint = ltrim($endpoint, '/');
        $fullApiUrl = $baseApiUrl . '/' . $apiEndpoint;


        $this->info("Llamando a la API Python para Tarea ID {$task->id}: POST {$fullApiUrl}");
        Log::info("Llamando a API Python para Tarea ID {$task->id}: POST {$fullApiUrl}", $payload);

        try {
            // Realizar la petición POST a la API Python
            $response = Http::timeout(15) // Timeout corto para la petición inicial
                          ->post($fullApiUrl, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['status']) && $responseData['status'] === 'accepted' && isset($responseData['task_id'])) {
                    $task->api_task_id = $responseData['task_id'];
                    $task->status = 'processing'; // Cambiar estado a procesando
                    $task->save();
                    $this->info("Tarea ID {$task->id} enviada. API Task ID: {$responseData['task_id']}. Estado: 'processing'.");
                    Log::info("Tarea ID {$task->id} iniciada en API Python. API Task ID: {$responseData['task_id']}");
                } else {
                    $this->error("Respuesta inesperada de API Python para Tarea ID {$task->id}: " . $response->body());
                    Log::error("Respuesta inesperada de API Python para Tarea ID {$task->id}: ", ['body' => $response->body()]);
                    $task->status = 'failed'; // Marcar como fallida si la respuesta no es la esperada
                    $task->save();
                }
            } else {
                $this->error("Error HTTP de API Python para Tarea ID {$task->id}. Status: {$response->status()}. Body: " . $response->body());
                Log::error("Error HTTP de API Python para Tarea ID {$task->id}: ", ['status' => $response->status(), 'body' => $response->body()]);
                // No marcamos como failed para que se reintente si fue error temporal
            }
        // Capturar específicamente errores de conexión
        } catch (ConnectionException $e) {
             $this->error("Error de conexión llamando a API Python para Tarea ID {$task->id}: " . $e->getMessage());
             Log::error("Error de conexión llamando a API Python para Tarea ID {$task->id}: " . $e->getMessage());
             // No marcamos como failed, se reintentará en el siguiente ciclo
        } catch (Throwable $e) {
            // Capturar otras excepciones durante la llamada o procesamiento de respuesta
            $this->error("Excepción al procesar Tarea ID {$task->id}: " . $e->getMessage());
            Log::error("Excepción procesando Tarea ID {$task->id}: " . $e->getMessage(), ['exception' => $e]);
            $task->status = 'failed'; // Marcar como fallida ante excepciones inesperadas
            $task->save();
        }
    }
}
