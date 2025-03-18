<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramController extends Controller
{
    /**
     * Muestra la vista principal de Telegram.
     */
    public function index(Request $request)
    {
        return view('telegram.index');
    }

    /*===========================
      Authentication Endpoints
    ===========================*/

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
        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::get("$telegramUrl/download-media/{$userId}/{$peer}/{$messageId}");

        return response()->json($response->json());
    }

    /*===========================
      Chats Endpoints
    ===========================*/

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
     * GET /get-messages/{userId}/{peer}
     * Obtiene mensajes de un chat individual y agrega información de contacto.
     */
    public function getMessages($userId, $peer)
    {
        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::get("$telegramUrl/get-messages/{$userId}/{$peer}");

        return response()->json($response->json());
    }

    /**
     * DELETE /delete-message/{userId}/{peer}/{messageId}
     * Borra un mensaje específico en un chat individual.
     */
    public function deleteMessage($userId, $peer, $messageId)
    {
        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::delete("$telegramUrl/delete-message/{$userId}/{$peer}/{$messageId}");

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

        $response = Http::get("$telegramUrl/search-contact/{$userId}", [
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
}
