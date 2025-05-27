<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Telegram",
 *     description="Endpoints para interactuar con la API de Telegram"
 * )
 * 
 * @OA\SecurityRequirement({"telegramToken": {}})
 */
class TelegramProxyController extends Controller
{
    protected $telegramUrl;
    protected $apiToken;
    protected $defaultServerId;

    public function __construct()
    {
        $config = config('services.telegram');
        $this->telegramUrl = rtrim($config['url'], '/');
        $this->apiToken = $config['api_token'];
        $this->defaultServerId = $config['server_id'];
    }

    /**
     * @OA\Post(
     *     path="/api/telegram/send-message",
     *     summary="Enviar un mensaje de texto",
     *     tags={"Telegram"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"chat_id", "text"},
     *             @OA\Property(property="chat_id", type="string", example="123456789"),
     *             @OA\Property(property="text", type="string", example="Hola, esto es un mensaje de prueba")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensaje enviado correctamente"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required|string',
            'text' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post("$this->telegramUrl/send-message", [
                'chat_id' => $request->chat_id,
                'text' => $request->text,
                'server_id' => $request->server_id ?? $this->defaultServerId
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el mensaje: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/telegram/updates",
     *     summary="Obtener actualizaciones de mensajes",
     *     tags={"Telegram"},
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Identificador de la última actualización recibida",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Número máximo de actualizaciones a devolver",
     *         required=false,
     *         @OA\Schema(type="integer", default=100, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de actualizaciones"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */
    public function getUpdates(Request $request)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->get("$this->telegramUrl/updates", [
                'offset' => $request->query('offset'),
                'limit' => $request->query('limit', 100),
                'server_id' => $request->query('server_id', $this->defaultServerId)
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las actualizaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/telegram/send-photo",
     *     summary="Enviar una foto",
     *     tags={"Telegram"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"chat_id", "photo"},
     *             @OA\Property(property="chat_id", type="string", example="123456789"),
     *             @OA\Property(property="photo", type="string", format="url", example="https://example.com/photo.jpg"),
     *             @OA\Property(property="caption", type="string", example="Descripción de la foto")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Foto enviada correctamente"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */
    public function sendPhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required|string',
            'photo' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post("$this->telegramUrl/send-photo", [
                'chat_id' => $request->chat_id,
                'photo' => $request->photo,
                'caption' => $request->caption ?? '',
                'server_id' => $request->server_id ?? $this->defaultServerId
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la foto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/telegram/me",
     *     summary="Obtener información del bot",
     *     tags={"Telegram"},
     *     @OA\Response(
     *         response=200,
     *         description="Información del bot"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */
    public function getMe(Request $request)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->get("$this->telegramUrl/me", [
                'server_id' => $request->query('server_id', $this->defaultServerId)
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la información del bot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/telegram/chats",
     *     summary="Obtener la lista de chats",
     *     tags={"Telegram"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Número máximo de chats a devolver",
     *         required=false,
     *         @OA\Schema(type="integer", default=100, maximum=200)
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Desplazamiento para la paginación",
     *         required=false,
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de chats obtenida correctamente"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */
    public function getChats(Request $request)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->get("$this->telegramUrl/chats", [
                'limit' => $request->query('limit', 100),
                'offset' => $request->query('offset', 0),
                'server_id' => $request->query('server_id', $this->defaultServerId)
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de chats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/telegram/chat-messages",
     *     summary="Obtener mensajes de un chat específico",
     *     tags={"Telegram"},
     *     @OA\Parameter(
     *         name="chat_id",
     *         in="query",
     *         description="ID del chat",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Número máximo de mensajes a devolver",
     *         required=false,
     *         @OA\Schema(type="integer", default=50, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="ID del mensaje a partir del cual obtener los mensajes",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensajes del chat obtenidos correctamente"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */
    public function getChatMessages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->get("$this->telegramUrl/chat-messages", [
                'chat_id' => $request->chat_id,
                'limit' => $request->query('limit', 50),
                'offset' => $request->query('offset'),
                'server_id' => $request->query('server_id', $this->defaultServerId)
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los mensajes del chat: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/telegram/contacts",
     *     summary="Obtener la lista de contactos",
     *     tags={"Telegram"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de contactos obtenida correctamente"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */
    public function getContacts()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->get("$this->telegramUrl/contacts");

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de contactos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/telegram/chat-info",
     *     summary="Obtener información de un chat específico",
     *     tags={"Telegram"},
     *     @OA\Parameter(
     *         name="chat_id",
     *         in="query",
     *         description="ID del chat",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información del chat obtenida correctamente"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */
    public function getChatInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->get("$this->telegramUrl/chat-info", [
                'chat_id' => $request->chat_id,
                'server_id' => $request->query('server_id', $this->defaultServerId)
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la información del chat: ' . $e->getMessage()
            ], 500);
        }
    }
}
