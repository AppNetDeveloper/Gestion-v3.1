<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class WhatsappApiExplorerController extends Controller
{
    /**
     * Explorador de API para descubrir endpoints disponibles en el servidor Node.js de WhatsApp
     */
    public function exploreApi(Request $request)
    {
        // Solo permitir a usuarios autenticados
        if (!Auth::check()) {
            return response()->json(['error' => 'No autorizado'], 401);
        }
        
        $userId = Auth::id();
        $nodeUrl = env('WHATSAPP_URL');
        
        if (!$nodeUrl) {
            return response()->json(['error' => 'WHATSAPP_URL no está configurada en .env'], 500);
        }
        
        // Obtener información sobre la sesión actual
        $sessionInfo = $this->getSessionInfo($nodeUrl, $userId);
        
        // Probar diferentes rutas para obtener medios
        $mediaRoutes = $this->testMediaRoutes($nodeUrl, $userId);
        
        // Explorar otros endpoints disponibles
        $otherEndpoints = $this->exploreOtherEndpoints($nodeUrl, $userId);
        
        return response()->json([
            'success' => true,
            'nodeUrl' => $nodeUrl,
            'sessionInfo' => $sessionInfo,
            'mediaRoutes' => $mediaRoutes,
            'otherEndpoints' => $otherEndpoints
        ]);
    }
    
    /**
     * Obtener información sobre la sesión actual
     */
    private function getSessionInfo($nodeUrl, $userId)
    {
        try {
            $response = Http::timeout(10)->get("$nodeUrl/session-info/$userId");
            
            if ($response->successful()) {
                return $response->json();
            } else {
                // Intentar con otra ruta alternativa
                $response2 = Http::timeout(10)->get("$nodeUrl/info/$userId");
                if ($response2->successful()) {
                    return $response2->json();
                }
            }
            
            return ['error' => 'No se pudo obtener información de la sesión', 'status' => $response->status()];
        } catch (\Exception $e) {
            return ['error' => 'Error al conectar con el servidor: ' . $e->getMessage()];
        }
    }
    
    /**
     * Probar diferentes rutas para obtener medios
     */
    private function testMediaRoutes($nodeUrl, $userId)
    {
        $results = [];
        
        // Lista de posibles rutas para probar
        $testRoutes = [
            '/media/{userId}/{jid}/{messageId}',
            '/download/{userId}/{messageId}',
            '/download-media/{userId}/{messageId}',
            '/media-download/{userId}/{messageId}',
            '/media/{userId}/{messageId}',
            '/file/{userId}/{messageId}',
            '/attachment/{userId}/{messageId}',
            '/get-media/{userId}/{messageId}',
            '/get-file/{userId}/{messageId}'
        ];
        
        // Obtener un mensaje reciente para probar
        $recentMessage = $this->getRecentMediaMessage($nodeUrl, $userId);
        
        if (!$recentMessage) {
            return ['error' => 'No se encontró ningún mensaje multimedia reciente para probar'];
        }
        
        $messageId = $recentMessage['messageId'];
        $jid = $recentMessage['jid'];
        
        foreach ($testRoutes as $routeTemplate) {
            $route = str_replace('{userId}', $userId, $routeTemplate);
            $route = str_replace('{messageId}', $messageId, $route);
            $route = str_replace('{jid}', $jid, $route);
            
            $url = $nodeUrl . $route;
            
            try {
                $response = Http::timeout(5)->get($url);
                $status = $response->status();
                $contentType = $response->header('Content-Type') ?? 'none';
                $contentLength = $response->header('Content-Length') ?? 'none';
                
                $results[$routeTemplate] = [
                    'url' => $url,
                    'status' => $status,
                    'contentType' => $contentType,
                    'contentLength' => $contentLength,
                    'isSuccess' => $response->successful(),
                    'isMedia' => $this->isMediaContentType($contentType)
                ];
                
                // Registrar en los logs
                Log::channel('daily')->info("API Explorer - Probando ruta: $url - Status: $status - Content-Type: $contentType");
            } catch (\Exception $e) {
                $results[$routeTemplate] = [
                    'url' => $url,
                    'error' => $e->getMessage(),
                    'isSuccess' => false
                ];
                
                Log::channel('daily')->error("API Explorer - Error probando ruta: $url - Error: " . $e->getMessage());
            }
        }
        
        return $results;
    }
    
    /**
     * Obtener un mensaje multimedia reciente para probar
     */
    private function getRecentMediaMessage($nodeUrl, $userId)
    {
        try {
            // Obtener contactos
            $contactsResponse = Http::timeout(10)->get("$nodeUrl/contacts/$userId");
            
            if (!$contactsResponse->successful()) {
                return null;
            }
            
            $contacts = $contactsResponse->json()['contacts'] ?? [];
            
            // Buscar en los primeros contactos
            foreach (array_slice($contacts, 0, 5) as $contact) {
                if (isset($contact['id']) && $contact['id'] !== 'status@broadcast') {
                    $jid = $contact['id'];
                    
                    // Obtener mensajes de este contacto
                    $messagesResponse = Http::timeout(10)->get("$nodeUrl/get-messages/$userId/$jid");
                    
                    if ($messagesResponse->successful()) {
                        $messages = $messagesResponse->json()['messages'] ?? [];
                        
                        // Buscar un mensaje con media
                        foreach ($messages as $message) {
                            $messageData = $message['messageData'] ?? null;
                            
                            if ($messageData) {
                                $hasImage = isset($messageData['message']['imageMessage']);
                                $hasVideo = isset($messageData['message']['videoMessage']);
                                $hasAudio = isset($messageData['message']['audioMessage']);
                                $hasSticker = isset($messageData['message']['stickerMessage']);
                                $hasDocument = isset($messageData['message']['documentMessage']);
                                
                                if ($hasImage || $hasVideo || $hasAudio || $hasSticker || $hasDocument) {
                                    $messageId = $messageData['key']['id'] ?? null;
                                    
                                    if ($messageId) {
                                        return [
                                            'messageId' => $messageId,
                                            'jid' => $jid,
                                            'type' => $hasImage ? 'image' : ($hasVideo ? 'video' : ($hasAudio ? 'audio' : ($hasSticker ? 'sticker' : 'document')))
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::channel('daily')->error("Error buscando mensaje multimedia: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Explorar otros endpoints disponibles
     */
    private function exploreOtherEndpoints($nodeUrl, $userId)
    {
        $endpoints = [
            '/status' => 'Estado del servidor',
            '/info' => 'Información general',
            "/session-info/$userId" => 'Información de sesión',
            "/contacts/$userId" => 'Contactos',
            "/chats/$userId" => 'Chats',
            "/qr/$userId" => 'Código QR',
            "/disconnect/$userId" => 'Desconectar (NO PROBAR)',
            "/connect/$userId" => 'Conectar (NO PROBAR)'
        ];
        
        $results = [];
        
        foreach ($endpoints as $endpoint => $description) {
            if (strpos($endpoint, 'disconnect') !== false || strpos($endpoint, 'connect') !== false) {
                $results[$endpoint] = [
                    'url' => $nodeUrl . $endpoint,
                    'description' => $description,
                    'status' => 'No probado - Acción potencialmente destructiva'
                ];
                continue;
            }
            
            try {
                $response = Http::timeout(5)->get($nodeUrl . $endpoint);
                $results[$endpoint] = [
                    'url' => $nodeUrl . $endpoint,
                    'description' => $description,
                    'status' => $response->status(),
                    'isSuccess' => $response->successful()
                ];
            } catch (\Exception $e) {
                $results[$endpoint] = [
                    'url' => $nodeUrl . $endpoint,
                    'description' => $description,
                    'error' => $e->getMessage(),
                    'isSuccess' => false
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Determinar si un tipo de contenido es un medio
     */
    private function isMediaContentType($contentType)
    {
        $mediaTypes = [
            'image/',
            'video/',
            'audio/',
            'application/pdf',
            'application/octet-stream'
        ];
        
        foreach ($mediaTypes as $mediaType) {
            if (strpos($contentType, $mediaType) === 0) {
                return true;
            }
        }
        
        return false;
    }
}
