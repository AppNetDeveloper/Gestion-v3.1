<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="WhatsApp",
 *     description="Endpoints para interactuar con la API de WhatsApp"
 * )
 */
class WhatsAppProxyController extends Controller
{
    protected $whatsappUrl;
    protected $apiToken;
    protected $defaultServerId;

    public function __construct()
    {
        $config = config('services.whatsapp');
        $this->whatsappUrl = rtrim($config['url'], '/');
        $this->apiToken = $config['api_token'];
        $this->defaultServerId = $config['server_id'];
    }

    /**
     * @OA\Post(
     *     path="/api/whatsapp/send-message",
     *     summary="Enviar mensaje de WhatsApp",
     *     tags={"WhatsApp"},
     *     @OA\Parameter(
     *         name="server_id",
     *         in="query",
     *         required=false,
     *         description="ID del servidor de WhatsApp",
     *         @OA\Schema(type="integer", default=2)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"to", "message"},
     *             @OA\Property(property="to", type="string", example="34612345678"),
     *             @OA\Property(property="message", type="string", example="Hola, esto es un mensaje de prueba"),
     *             @OA\Property(property="options", type="object", example={"delay": 1000})
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
            'to' => 'required|string',
            'message' => 'required|string',
            'options' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        $serverId = $request->query('server_id', $this->defaultServerId);
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post("$this->whatsappUrl/api/send-message", [
                'to' => $request->to,
                'message' => $request->message,
                'options' => $request->options ?? [],
                'server_id' => $serverId
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
     *     path="/api/whatsapp/sessions",
     *     summary="Obtener sesiones de WhatsApp",
     *     tags={"WhatsApp"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de sesiones"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */
    public function getSessions()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->get("$this->whatsappUrl/api/sessions");

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sesiones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/whatsapp/start-session",
     *     summary="Iniciar sesión de WhatsApp",
     *     tags={"WhatsApp"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_id"},
     *             @OA\Property(property="session_id", type="string", example="my-session-1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sesión iniciada correctamente"
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
    public function startSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
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
            ])->post("$this->whatsappUrl/api/start-session", [
                'session_id' => $request->session_id
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la sesión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/whatsapp/chats",
     *     summary="Obtener lista de chats",
     *     tags={"WhatsApp"},
     *     @OA\Parameter(
     *         name="server_id",
     *         in="query",
     *         required=false,
     *         description="ID del servidor de WhatsApp",
     *         @OA\Schema(type="integer", default=2)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Límite de chats a devolver",
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de chats"
     *     )
     * )
     */
    public function getChats(Request $request)
    {
        $serverId = $request->query('server_id', $this->defaultServerId);
        $limit = $request->query('limit', 50);
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
        ])->get("$this->whatsappUrl/api/chats", [
            'server_id' => $serverId,
            'limit' => $limit
        ]);

        return response()->json($response->json(), $response->status());
    }

    /**
     * @OA\Get(
     *     path="/api/whatsapp/messages",
     *     summary="Obtener mensajes de un chat",
     *     tags={"WhatsApp"},
     *     @OA\Parameter(
     *         name="chat_id",
     *         in="query",
     *         required=true,
     *         description="ID del chat",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Límite de mensajes a devolver",
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de mensajes del chat"
     *     )
     * )
     */
    public function getMessages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $serverId = $request->query('server_id', $this->defaultServerId);
        $limit = $request->query('limit', 50);
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
        ])->get("$this->whatsappUrl/api/messages", [
            'server_id' => $serverId,
            'chat_id' => $request->chat_id,
            'limit' => $limit
        ]);

        return response()->json($response->json(), $response->status());
    }

    /**
     * @OA\Get(
     *     path="/api/whatsapp/download-media",
     *     summary="Descargar un archivo multimedia",
     *     tags={"WhatsApp"},
     *     @OA\Parameter(
     *         name="message_id",
     *         in="query",
     *         required=true,
     *         description="ID del mensaje que contiene el medio",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Archivo multimedia descargado"
     *     )
     * )
     */
    public function downloadMedia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $serverId = $request->query('server_id', $this->defaultServerId);
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
        ])->get("$this->whatsappUrl/api/download-media", [
            'server_id' => $serverId,
            'message_id' => $request->message_id
        ]);

        if ($response->successful()) {
            $contentType = $response->header('Content-Type');
            $contentDisposition = $response->header('Content-Disposition');
            
            return response($response->body(), 200)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', $contentDisposition);
        }

        return response()->json($response->json(), $response->status());
    }

    /**
     * @OA\Post(
     *     path="/api/whatsapp/send-media",
     *     summary="Enviar un archivo multimedia",
     *     tags={"WhatsApp"},
     *     @OA\Parameter(
     *         name="server_id",
     *         in="query",
     *         required=false,
     *         description="ID del servidor de WhatsApp",
     *         @OA\Schema(type="integer", default=2)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"to", "media_url", "caption"},
     *             @OA\Property(property="to", type="string", example="34612345678"),
     *             @OA\Property(property="media_url", type="string", example="https://example.com/image.jpg"),
     *             @OA\Property(property="caption", type="string", example="Mira esta imagen"),
     *             @OA\Property(property="filename", type="string", example="imagen.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Archivo multimedia enviado correctamente"
     *     )
     * )
     */
    public function sendMedia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|string',
            'media_url' => 'required|url',
            'caption' => 'required|string',
            'filename' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $serverId = $request->query('server_id', $this->defaultServerId);
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type' => 'application/json',
        ])->post("$this->whatsappUrl/api/send-media", [
            'server_id' => $serverId,
            'to' => $request->to,
            'media_url' => $request->media_url,
            'caption' => $request->caption,
            'filename' => $request->filename ?? basename($request->media_url)
        ]);

        return response()->json($response->json(), $response->status());
    }

    /**
     * @OA\Get(
     *     path="/api/whatsapp/contact-info",
     *     summary="Obtener información de un contacto",
     *     tags={"WhatsApp"},
     *     @OA\Parameter(
     *         name="contact_id",
     *         in="query",
     *         required=true,
     *         description="ID o número de teléfono del contacto",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información del contacto"
     *     )
     * )
     */
    public function getContactInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $serverId = $request->query('server_id', $this->defaultServerId);
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
        ])->get("$this->whatsappUrl/api/contact-info", [
            'server_id' => $serverId,
            'contact_id' => $request->contact_id
        ]);

        return response()->json($response->json(), $response->status());
    }
}
