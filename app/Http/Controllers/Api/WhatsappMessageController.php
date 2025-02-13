<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappMessage;
use App\Models\Contact;
use App\Models\AutoProcess;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

/**
 * @OA\Info(
 *     title="API de WhatsApp",
 *     version="1.0.0",
 *     description="Documentación de la API para gestionar mensajes de WhatsApp."
 * )
 *
 * @OA\Tag(
 *     name="Whatsapp Message",
 *     description="Endpoints para el manejo de mensajes de WhatsApp"
 * )
 */
class WhatsappMessageController extends Controller
{
    /**
     * Almacena un mensaje de WhatsApp y crea el contacto si no existe.
     *
     * @OA\Post(
     *      path="/api/whatsapp/messages",
     *      summary="Crear mensaje de WhatsApp",
     *      description="Valida el token, almacena el mensaje en la base de datos y crea el contacto asociado si aún no existe. Además, si el mensaje es recibido, se evalúa si se debe enviar una respuesta automática.",
     *      operationId="storeWhatsappMessage",
     *      tags={"Whatsapp Message"},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Datos necesarios para crear el mensaje",
     *          @OA\JsonContent(
     *              required={"token", "user_id", "phone", "message", "status"},
     *              @OA\Property(property="token", type="string", example="mi_token_secreto"),
     *              @OA\Property(property="user_id", type="integer", example=1),
     *              @OA\Property(property="phone", type="string", example="123456789"),
     *              @OA\Property(property="message", type="string", example="Hola, ¿cómo estás?"),
     *              @OA\Property(property="status", type="string", enum={"send", "received"}, example="received"),
     *              @OA\Property(property="image", type="string", nullable=true, example="https://example.com/image.jpg")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Mensaje creado exitosamente y contacto creado si no existía.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Mensaje de WhatsApp creado exitosamente y contacto creado si no existía."),
     *              @OA\Property(property="data", type="object")
     *          )
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Token inválido.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Token inválido.")
     *          )
     *      )
     * )
     */
    public function store(Request $request)
    {
        // Verificar que el token enviado coincida con el definido en el archivo .env
        if ($request->input('token') !== env('WHATSAPP_API_TOKEN')) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido.'
            ], 403);
        }

        // Validar los datos entrantes
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'phone'   => 'required|string',
            'message' => 'required|string',
            'status'  => 'required|in:send,received',
            'image'   => 'nullable|string',
        ]);

        // Crear el registro del mensaje en la base de datos
        $whatsappMessage = WhatsappMessage::create($data);

        // Guardar el contacto en la tabla 'contacts' si no existe.
        Contact::firstOrCreate(
            ['user_id' => $data['user_id'], 'phone' => $data['phone']],
            ['name' => $data['phone']]
        );

        /*
         * Lógica de respuesta automática:
         * Se ejecuta solo si el mensaje tiene status "received".
         */
        if ($data['status'] === 'received') {
            // Log: verificar si hay que contestar de forma automática
            Log::info("Buscando si se debe enviar una respuesta automática");
            // Buscar la configuración de auto respuesta para el usuario
            $autoConfig = AutoProcess::where('user_id', $data['user_id'])->first();

            // Si la configuración existe y se ha configurado WhatsApp en modo "texto automático" (valor 1)
            if ($autoConfig && $autoConfig->whatsapp == 1) {
                // Contar los mensajes para este teléfono en los últimos 30 minutos.
                $recentMessagesCount = WhatsappMessage::where('phone', $data['phone'])
                    ->where('created_at', '>=', Carbon::now()->subMinutes(30))
                    ->count();

                if ($recentMessagesCount <= 1) { // Solo se encontró el mensaje recién guardado
                    $prompt = $autoConfig->whatsapp_prompt;

                    // Definir el sessionId. En este ejemplo se usa el user_id como sessionId.
                    $sessionId = (string)$data['user_id'];

                    // Formatear el JID: si no contiene '@', se le agrega el dominio de WhatsApp.
                    $jid = $data['phone'];
                    if (strpos($jid, '@') === false) {
                        $jid .= '@s.whatsapp.net';
                    }

                    try {
                        $client = new Client();
                        // Construir la URL usando la variable de entorno WATSHAPP_URL
                        $url = env('WATSHAPP_URL') . '/send-message/' . $sessionId;
                        $response = $client->post($url, [
                            'json' => [
                                'jid'     => $jid,
                                'message' => $prompt,
                            ],
                        ]);
                        $body = $response->getBody()->getContents();
                        $nodeData = json_decode($body, true);
                        // Opcional: registrar la respuesta del endpoint o realizar otra acción.
                    } catch (\Exception $e) {
                        // Registrar el error para análisis.
                        Log::error("Error enviando respuesta automática: " . $e->getMessage());
                    }
                }
            }
        }

        // Retornar la respuesta JSON
        return response()->json([
            'success' => true,
            'message' => 'Mensaje de WhatsApp creado exitosamente y contacto creado si no existía.',
            'data'    => $whatsappMessage
        ], 201);
    }

    /**
     * Envía un mensaje sin almacenarlo, llamando a la API de Node (Baileys).
     *
     * @OA\Post(
     *      path="/api/whatsapp/send-message-now",
     *      summary="Enviar mensaje sin almacenar",
     *      description="Valida el token y envía un mensaje a través de la API de Node sin guardarlo en la base de datos.",
     *      operationId="sendMessageNow",
     *      tags={"Whatsapp Message"},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Datos necesarios para enviar el mensaje",
     *          @OA\JsonContent(
     *              required={"token", "sessionId", "jid", "message"},
     *              @OA\Property(property="token", type="string", example="mi_token_secreto"),
     *              @OA\Property(property="sessionId", type="string", example="1"),
     *              @OA\Property(property="jid", type="string", example="123456789"),
     *              @OA\Property(property="message", type="string", example="Hola, ¿cómo estás?")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Mensaje enviado correctamente",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Mensaje enviado correctamente"),
     *              @OA\Property(property="data", type="object")
     *          )
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Token inválido.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Token inválido.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error al enviar el mensaje vía Node",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Error sending message via Node: ...")
     *          )
     *      )
     * )
     */
    public function sendMessageNow(Request $request)
    {
        // Validar el token para seguridad
        if ($request->input('token') !== env('WHATSAPP_API_TOKEN')) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido.'
            ], 403);
        }

        // Validar los datos entrantes
        $data = $request->validate([
            'sessionId' => 'required|string',
            'jid'       => 'required|string',
            'message'   => 'required|string'
        ]);

        // Obtener los datos y, si el jid no contiene '@', agregarle el dominio
        $sessionId = $data['sessionId'];
        $jid       = $data['jid'];
        if (strpos($jid, '@') === false) {
            $jid .= '@s.whatsapp.net';
        }
        $message   = $data['message'];

        try {
            $client = new Client();
            // Construir la URL usando la variable de entorno WATSHAPP_URL
            $url = env('WATSHAPP_URL') . '/send-message/' . $sessionId;
            $response = $client->post($url, [
                'json' => [
                    'jid'     => $jid,
                    'message' => $message,
                ],
            ]);
            $body = $response->getBody()->getContents();
            $nodeData = json_decode($body, true);

            // Si el endpoint de Node devuelve un error, lo retornamos
            if (isset($nodeData['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error sending message via Node: ' . $nodeData['error']
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending message via Node: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mensaje enviado correctamente',
            'data'    => $nodeData
        ]);
    }
}
