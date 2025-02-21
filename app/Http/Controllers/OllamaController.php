<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // para los logs
use GuzzleHttp\Client; // Usaremos Guzzle directamente

class OllamaController extends Controller
{
    public function processPrompt(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string'
        ]);

        //Ponemos si desde request recivimos model valido no null o vacio se lo asignamos a la variable model si no dejamos el valor default de env
        $model = $request->input('model', env('OLLAMA_MODEL_DEFAULT'));
        $prompt    = $request->input('prompt');
        $ollamaUrl = env('OLLAMA_URL');
        

        $payload = [
            'model'    => $model,
            'messages' => [
                [
                    'role'    => 'user',
                    'content' => $prompt
                ]
            ]
        ];

        // Construir la URL completa con el endpoint correcto (/api/chat)
        $fullUrl = rtrim($ollamaUrl, '/') . '/api/chat';
        Log::info("Llamando a la API de Ollama en: {$fullUrl}", $payload);

        // Configuramos Guzzle con un timeout largo (por ejemplo, 300 segundos)
        $client = new Client([
            'timeout' => 300,
        ]);

        try {
            $response = $client->request('POST', $fullUrl, [
                'json'   => $payload,
                'stream' => true,
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Error al conectar con la API de Ollama en {$fullUrl}: " . $e->getMessage());
            return response()->json(['error' => 'Error al comunicarse con la API de Ollama'], 500);
        }

        $body = $response->getBody();
        $combinedContent = '';
        $buffer = ''; // para acumular fragmentos parciales

        Log::info("Iniciando lectura del stream de respuesta");

        // Leer el stream hasta que se encuentre un fragmento con "done": true
        while (!$body->eof()) {
            // Leer un bloque (por ejemplo, 1024 bytes)
            $chunk = $body->read(1024);
            Log::info("Chunk leído, longitud: " . strlen($chunk));
            $buffer .= $chunk;
            // Separar por líneas (cada fragmento JSON está separado por "\n")
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                // Logueamos los primeros 100 caracteres para evitar exceso de información
                Log::info("Procesando línea: " . substr($line, 0, 100));
                $decoded = json_decode($line, true);
                if (!$decoded) {
                    Log::error("Error al decodificar línea: " . $line);
                    continue;
                }
                // Si se encuentra contenido, lo concatenamos
                if (isset($decoded['message']['content'])) {
                    Log::info("Fragmento contenido: " . $decoded['message']['content']);
                    $combinedContent .= $decoded['message']['content'];
                }
                // Si se indica que se terminó la respuesta, salimos de ambos bucles
                if (isset($decoded['done']) && $decoded['done'] === true) {
                    Log::info("Flag 'done' encontrado; finalizando lectura del stream.");
                    break 2;
                }
            }
        }

        if (empty($combinedContent)) {
            Log::error("No se encontró contenido en la respuesta de Ollama");
            return response()->json(['error' => 'Respuesta inválida de la API de Ollama'], 500);
        }

        Log::info("Contenido combinado final, longitud: " . strlen($combinedContent));
        // Limpiar el contenido, por ejemplo, eliminando secciones entre <think> y </think>
        $cleanContent = $this->cleanContent($combinedContent);
        Log::info("Contenido limpio final, longitud: " . strlen($cleanContent));

        return response()->json(['text' => $cleanContent]);
    }

    /**
     * Elimina todo el contenido que esté entre <think> y </think>.
     *
     * @param string $text
     * @return string
     */
    private function cleanContent(string $text): string
    {
        return trim(preg_replace('/<think>.*?<\/think>/s', '', $text));
    }
}
