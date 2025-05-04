<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TelegramController extends Controller
{
    /**
     * Muestra la vista principal de Telegram.
     */
    public function index(Request $request)
    {
        $autoResponseConfig = null; // O el valor que consideres por defecto
        return view('telegram.index', compact('autoResponseConfig'));
    }


    /**
     * POST /request-code/{userId}
     * Solicita el código de verificación para autenticación en Telegram.
     */
    public function requestCode(Request $request, $userId)
    {
        $phone = $request->input('phone');
        $telegramUrl = env('TELEGRAM_URL'); // Ej: http://localhost:3006

        $response = Http::post("$telegramUrl/request-code/{$userId}", [
            'phone' => $phone,
        ]);

        return response()->json($response->json());
    }

    /**
     * POST /verify-code/{userId}
     * Verifica el código y completa el login en Telegram.
     */
    public function verifyCode(Request $request, $userId)
    {
        $code = $request->input('code');
        $telegramUrl = env('TELEGRAM_URL');

        $response = Http::post("$telegramUrl/verify-code/{$userId}", [
            'code' => $code,
        ]);

        return response()->json($response->json());
    }

    /**
     * POST /logout/{userId}
     * Cierra la sesión de un usuario.
     */
    public function logout($userId)
    {
        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::post("$telegramUrl/logout/{$userId}");

        return response()->json($response->json());
    }

    /**
     * GET /session-status/{userId}
     * Obtiene el estado de la sesión (validada o no) y el userId.
     */
    public function sessionStatus($userId)
    {
        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::get("$telegramUrl/session-status/{$userId}");

        return response()->json($response->json());
    }

    /**
     * GET /all-session-status
     * Obtiene el estado de todas las sesiones (si han sido validadas o no).
     */
    public function allSessionStatus()
    {
        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::get("$telegramUrl/all-session-status");

        return response()->json($response->json());
    }

    /*===========================
      Media Endpoints
    ===========================*/

    /**
     * GET /download-media/{userId}/{peer}/{messageId}
     * Descarga el archivo multimedia de un mensaje.
     */
    public function downloadMedia($userId, $peer, $messageId)
    {
        $cacheDirectory = storage_path("app/media/{$userId}/{$peer}");
        $cachePath = "{$cacheDirectory}/{$messageId}";

        // 30 días expresados en segundos
        $thirtyDaysInSeconds = 30 * 24 * 60 * 60;

        // Verifica si el archivo existe y si no tiene más de 30 días
        if (file_exists($cachePath) && (time() - filemtime($cachePath)) < $thirtyDaysInSeconds) {
            $content = file_get_contents($cachePath);
            $mimeType = mime_content_type($cachePath);

            return response($content, 200)
                    ->header('Content-Type', $mimeType)
                    ->header('Content-Disposition', 'inline');
        }

        // Si el archivo no existe o ya expiró la caché, se descarga de nuevo
        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::get("$telegramUrl/download-media/{$userId}/{$peer}/{$messageId}");

        if ($response->failed()) {
            return response()->json(['error' => 'Error descargando la media'], $response->status());
        }

        // Crea el directorio de caché si no existe
        if (!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0755, true);
        }

        // Guarda el contenido en el caché
        file_put_contents($cachePath, $response->body());

        // Recupera los headers de respuesta originales
        $contentType = $response->header('Content-Type', 'application/octet-stream');
        $disposition = $response->header('Content-Disposition', 'attachment; filename="downloaded_media"');

        return response($response->body(), 200)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', $disposition);
    }



    /**
     * GET /get-chat/{userId}
     * Obtiene la lista de chats individuales.
     * (Nota: Si tu API usa /get-chats, puedes adaptar este método)
     */
    public function getChat($userId)
    {
        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::get("$telegramUrl/get-chat/{$userId}");

        return response()->json($response->json());
    }

    /**
     * DELETE /delete-chat/{userId}/{peer}
     * Borra el historial completo de un chat.
     */
    public function deleteChat($userId, $peer)
    {
        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::delete("$telegramUrl/delete-chat/{$userId}/{$peer}");

        return response()->json($response->json());
    }

    /*===========================
      Messages Endpoints
    ===========================*/

    /**
     * Obtiene mensajes de un chat individual y utiliza caché para optimizar llamadas.
     * Si no hay mensajes en caché, se obtienen todos.
     * Si ya hay mensajes en caché, se traen solo 10 mensajes nuevos después del último mensaje cacheado.
     *
     * @param Request $request
     * @param string $userId
     * @param string $peer
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessages(Request $request, $userId, $peer)
    {
        $cacheKey = "messages_{$userId}_{$peer}";
        $telegramUrl = env('TELEGRAM_URL');

        // Intentamos obtener los mensajes cacheados
        $cachedMessages = Cache::get($cacheKey);

        if (!$cachedMessages) {
            // No hay mensajes en caché: obtenemos todos los mensajes de la API
            $response = Http::get("$telegramUrl/get-messages/{$userId}/{$peer}");
            $messages = $response->json()['messages'] ?? [];

            // Almacenamos en caché (por ejemplo, por 30 días, ajusta según tus necesidades)
            Cache::put($cacheKey, $messages, now()->addDays(30));
        } else {
            // Ya hay mensajes en caché: obtenemos el último mensaje
            $lastMessage = end($cachedMessages);
            // Suponemos que el identificador está en 'id' o en 'messageId'
            $lastId = $lastMessage['id'] ?? $lastMessage['messageId'] ?? null;

            if ($lastId) {
                // Llamada a la API para obtener solo los nuevos mensajes: limitamos a 10 y enviamos 'after'
                $response = Http::get("$telegramUrl/get-messages/{$userId}/{$peer}", [
                    'limit' => 10,
                    'after' => $lastId,
                ]);
                $newMessages = $response->json()['messages'] ?? [];

                // Combina los mensajes nuevos con los ya cacheados y elimina duplicados
                $messages = collect(array_merge($cachedMessages, $newMessages))
                            ->unique(function ($item) {
                                return $item['id'] ?? $item['messageId'];
                            })
                            ->values()
                            ->all();

                // Actualiza la caché
                Cache::put($cacheKey, $messages, now()->addDays(30));
            } else {
                $messages = $cachedMessages;
            }
        }

        return response()->json([
            'success'  => true,
            'messages' => $messages,
        ]);
    }


    /**
     * DELETE /delete-message/{userId}/{peer}/{messageId}
     * Borra un mensaje específico en un chat individual.
     */
    public function deleteMessage($userId, $peer, $messageId)
    {
        $telegramUrl = env('TELEGRAM_URL');

        // Llamada a la API para borrar el mensaje
        $response = Http::delete("$telegramUrl/delete-message/{$userId}/{$peer}/{$messageId}");

        // Intentamos obtener los mensajes cacheados para este chat
        $cacheKey = "messages_{$userId}_{$peer}";
        $cachedMessages = Cache::get($cacheKey);

        if ($cachedMessages) {
            // Filtramos los mensajes, quitando aquel cuyo messageId coincide
            $updatedMessages = array_filter($cachedMessages, function ($msg) use ($messageId) {
                return $msg['messageId'] != $messageId;
            });

            // Actualizamos la caché (por ejemplo, manteniendo la expiración previa, aquí usamos 30 días)
            Cache::put($cacheKey, $updatedMessages, now()->addDays(30));
        }

        return response()->json($response->json());
    }


    /**
     * POST /forward-message/{userId}/{fromPeer}/{toPeer}/{messageId}
     * Reenvía un mensaje desde un chat a otro.
     */
    public function forwardMessage($userId, $fromPeer, $toPeer, $messageId)
    {
        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::post("$telegramUrl/forward-message/{$userId}/{$fromPeer}/{$toPeer}/{$messageId}");

        return response()->json($response->json());
    }

    /**
     * POST /send-media/{userId}/{peer}
     * Envía un archivo multimedia a un chat.
     */
    public function sendMedia(Request $request, $userId, $peer)
    {
        $telegramUrl = env('TELEGRAM_URL');
        // Suponemos que el request trae filePath y caption
        $data = $request->only(['filePath', 'caption']);
        $response = Http::post("$telegramUrl/send-media/{$userId}/{$peer}", $data);

        return response()->json($response->json());
    }

    /**
     * GET /search-messages/{userId}/{peer}
     * Busca mensajes en un chat a partir de un parámetro de búsqueda.
     */
    public function searchMessages(Request $request, $userId, $peer)
    {
        $telegramUrl = env('TELEGRAM_URL');
        // Se esperan parámetros de consulta: query y limit
        $query = $request->query('query');
        $limit = $request->query('limit', 20);

        $response = Http::get("$telegramUrl/search-messages/{$userId}/{$peer}", [
            'query' => $query,
            'limit' => $limit,
        ]);

        return response()->json($response->json());
    }

    /*===========================
      Groups Endpoints
    ===========================*/

    /**
     * POST /send-group-message/{userId}/{groupId}/{message}
     * Envía un mensaje a un grupo o canal.
     */
    public function sendGroupMessage($userId, $groupId, $message)
    {
        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::post("$telegramUrl/send-group-message/{$userId}/{$groupId}/{$message}");

        return response()->json($response->json());
    }

        /**
     * POST /send-message/{userId}/{groupId}/{message}
     * Envía un mensaje a un grupo o canal.
     */
    public function sendMessage($userId, $groupId, Request $request)
    {
        $message = $request->input('message');
        if (!$message) {
            return response()->json(['error' => 'El mensaje es obligatorio'], 400);
        }

        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::post("$telegramUrl/send-message/{$userId}/{$groupId}", [
            'message' => $message,
        ]);
        //ponemos un log para ver la respuesta de la llamada api
        Log::info('Response from Telegram API:', ['response' => $response->json()]);


        return response()->json($response->json());
    }


    /**
     * POST /leave-group/{userId}/{groupId}
     * Elimina al bot de un grupo (o canal) de Telegram.
     */
    public function leaveGroup($userId, $groupId)
    {
        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::post("$telegramUrl/leave-group/{$userId}/{$groupId}");

        return response()->json($response->json());
    }

    /**
     * POST /create-group/{userId}
     * Crea un nuevo grupo privado en Telegram.
     */
    public function createGroup(Request $request, $userId)
    {
        $telegramUrl = env('TELEGRAM_URL');
        // Se espera que el request contenga title y members
        $data = $request->only(['title', 'members']);
        $response = Http::post("$telegramUrl/create-group/{$userId}", $data);

        return response()->json($response->json());
    }

    /*===========================
      Contacts Endpoints
    ===========================*/

    /**
     * GET /get-contacts/{userId}
     * Obtiene el listado de contactos del usuario.
     */
    public function getContacts($userId)
    {
        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::get("$telegramUrl/get-contacts/{$userId}");

        return response()->json($response->json());
    }

    /**
     * GET /search-contact/{userId}
     * Busca un contacto por teléfono o nombre.
     */
    public function searchContact(Request $request, $userId)
    {
        $telegramUrl = env('TELEGRAM_URL');
        $phone = $request->query('phone');
        $name  = $request->query('name');

        $response = Http::timeout(90)           // hasta 90 segundos para recibir datos
        ->connectTimeout(100)    // hasta 100 segundos para conectar
        ->get("$telegramUrl/search-contact/{$userId}", [
            'phone' => $phone,
            'name'  => $name,
        ]);

        return response()->json($response->json());
    }

    /**
     * GET /export-contacts/{userId}
     * Exporta los contactos del usuario en formato CSV.
     */
    public function exportContacts($userId)
    {
        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::get("$telegramUrl/export-contacts/{$userId}");

        return response()->json($response->json());
    }

    /**
     * POST /import-contacts/{userId}
     * Importa contactos a la cuenta del usuario desde un JSON.
     */
    public function importContacts(Request $request, $userId)
    {
        $telegramUrl = env('TELEGRAM_URL');
        $data = $request->all();
        $response = Http::post("$telegramUrl/import-contacts/{$userId}", $data);

        return response()->json($response->json());
    }

    /*===========================
      Sessions Endpoints
    ===========================*/

    /**
     * GET /active-sessions
     * Obtiene las sesiones activas en el sistema.
     */
    public function activeSessions()
    {
        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::get("$telegramUrl/active-sessions");

        return response()->json($response->json());
    }


    /**
     * Sincroniza los contactos desde la API de Telegram.
     *
     * JSON esperado:
     * {
     *   "success": true,
     *   "contacts": [
     *     {
     *       "peer": "string",
     *       "first_name": "string",
     *       "last_name": "string",
     *       "phone": "string",
     *       "username": "string"
     *     }
     *   ]
     * }
     *
     * Se utiliza para buscar si el contacto ya existe (por su telegram id o por su teléfono, normalizado quitando el "+").
     * Si no existe, se crea un nuevo registro en la tabla de contactos.
     *
     * @openapi
     * /sync-contacts/{userId}:
     *   get:
     *     summary: Sincroniza los contactos desde Telegram
     *     tags: [Contacts]
     *     parameters:
     *       - in: path
     *         name: userId
     *         required: true
     *         schema:
     *           type: string
     *         description: ID del usuario.
     *     responses:
     *       200:
     *         description: Contactos sincronizados correctamente.
     *         content:
     *           application/json:
     *             schema:
     *               type: object
     *               properties:
     *                 success:
     *                   type: boolean
     *                 imported:
     *                   type: array
     *                   items:
     *                     type: object
     *                     properties:
     *                       id:
     *                         type: integer
     *                       user_id:
     *                         type: integer
     *                       name:
     *                         type: string
     *                       phone:
     *                         type: string
     *                       telegram:
     *                         type: string
     *       500:
     *         description: Error al obtener o sincronizar los contactos.
     */
    public function syncContacts($userId)
    {
        $telegramUrl = env('TELEGRAM_URL');

        // Llamada a la API para obtener los contactos
        $response = Http::get("$telegramUrl/get-contacts/{$userId}");
        $data = $response->json();

        if (!isset($data['success']) || $data['success'] !== true) {
            return response()->json(['error' => 'Error al obtener contactos desde Telegram'], 500);
        }

        $contacts = $data['contacts'] ?? [];
        $importedContacts = [];

        foreach ($contacts as $tc) {
            // Verificar que exista la clave 'id'
            if (!isset($tc['peer'])) {
                Log::warning('Contacto sin peer recibido', $tc);
                continue;
            }

            // Normalizar el teléfono: quitar el signo '+' si existe.
            $phoneNormalized = isset($tc['phone']) ? ltrim($tc['phone'], '+') : null;

            // Buscar un contacto existente para este usuario que tenga el mismo telegram (id) o el mismo teléfono.
            $contact = \App\Models\Contact::where('user_id', $userId)
                ->where(function ($query) use ($tc, $phoneNormalized) {
                    $query->where('telegram', $tc['peer'])
                        ->orWhere('phone', $phoneNormalized);
                })->first();

            if (!$contact) {
                // Si no existe, se crea el contacto.
                // Si first_name o last_name no están definidos, se puede asignar un valor por defecto.
                $firstName = $tc['first_name'] ?? 'Unknown';
                $lastName = $tc['last_name'] ?? '';
                $name = trim($firstName . ' ' . $lastName);

                $contact = \App\Models\Contact::create([
                    'user_id'  => $userId,
                    'name'     => $name,
                    'phone'    => $phoneNormalized,
                    'telegram' => $tc['peer']
                ]);
                $importedContacts[] = $contact;
                //Log::info('Nuevo contacto creado', ['contact' => $contact]);
            }
        }

        return response()->json([
            'success' => true,
            'imported' => $importedContacts
        ]);
    }


}
