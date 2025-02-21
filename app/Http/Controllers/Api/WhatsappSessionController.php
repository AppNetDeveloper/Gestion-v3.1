<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class WhatsappSessionController extends Controller
{
    /**
     * Verifica si el usuario (id recibido) está conectado al servidor de WhatsApp.
     *
     * Se espera que la petición incluya el parámetro "user_id".
     */
    public function checkSession(Request $request)
    {
        // Validamos que se haya enviado el id del usuario.
        $userId = $request->input('user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User id is required.'
            ], 400);
        }

        // Construimos la URL de la API externa usando la variable del .env.
        $url = env('WATSHAPP_URL') . '/sessions';

        try {
            $client = new Client();
            $response = $client->get($url);
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            // Obtenemos el array de sesiones activas (si existe)
            $activeSessions = $data['activeSessions'] ?? [];

            // Comprobamos si el user_id (convertido a string) está en el array
            $connected = in_array((string)$userId, $activeSessions);

            return response()->json([
                'success'        => true,
                'connected'      => $connected,
                'activeSessions' => $activeSessions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error connecting to WhatsApp server',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Realiza el logout del usuario en el servidor de WhatsApp.
     * Se llama al endpoint: {WATSHAPP_URL}/logout/{user_id}
     */
    public function logout(Request $request)
    {
        // Validar que se reciba el user_id (por query o parámetro)
        $userId = $request->input('user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User id is required.'
            ], 400);
        }
    
        // Construir la URL usando la variable de entorno y el id del usuario
        $url = env('WATSHAPP_URL') . '/logout/' . $userId;
    
        try {
            $client = new \GuzzleHttp\Client();
            // Usamos POST para llamar al endpoint externo
            $response = $client->post($url);
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
    
            // Se asume que el endpoint externo devuelve un JSON indicando éxito, por ejemplo:
            // { "message": "Sesión {id} cerrada y eliminada" }
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully.',
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            \Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error connecting to WhatsApp logout endpoint.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }    
    public function startSession(Request $request)
    {
        // Validar que se reciba el user_id
        $userId = $request->input('user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User id is required.'
            ], 400);
        }
    
        // Construir las URLs usando la variable de entorno.
        // Se asume que la sesión se inicia en este endpoint.
        $urlStart = env('WATSHAPP_URL') . '/start-session/' . $userId;
        // La URL para obtener el QR ya retorna la imagen en base64.
        $urlQr    = env('WATSHAPP_URL') . '/get-qr/' . $userId;
    
        try {
            $client = new \GuzzleHttp\Client();
    
            // Iniciar la sesión (se usa POST para este endpoint)
            $client->post($urlStart);
    
            //ponemos un sleep de de 1 segundo
            sleep(1);
            // Obtener el QR. Se asume que el endpoint devuelve un JSON, por ejemplo:
            // {"success": true, "qr": "data:image/png;base64,...."}
            $responseQr = $client->get($urlQr);
            $body = $responseQr->getBody()->getContents();
            $data = json_decode($body, true);
    
            // Verificamos que la respuesta sea exitosa y que contenga la propiedad 'qr'
            if (isset($data['success']) && $data['success'] && isset($data['qr'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Session started successfully.',
                    'qr'      => $data['qr']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error retrieving QR from WhatsApp server.',
                    'data'    => $data
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Error starting WhatsApp session: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error connecting to WhatsApp start-session endpoint.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    
}
