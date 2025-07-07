<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Contact; // Asegúrate que el modelo Contact existe y está importado
use App\Models\AutoProcess; // Asegúrate que el modelo AutoProcess existe y está importado
// Session no se usa si devuelves JSON

class WhatsappController extends Controller
{
    /**
     * Muestra el listado de contactos y, si se selecciona, la conversación para el usuario logueado.
     */
    public function index(Request $request)
    {
        $userId    = Auth::id();
        $sessionId = $userId; // Usamos el id del usuario como sessionId
        $nodeUrl   = env('WHATSAPP_URL'); // Ej: http://localhost:3005

        // Definir variables por defecto
        $sortedContacts = collect();
        $autoResponseConfig = null;
        $messages      = collect(); // Colección para los mensajes formateados
        $selectedPhone = $request->query('phone');

        // Intentar obtener la configuración de auto respuesta siempre
        $autoResponseConfig = AutoProcess::where('user_id', $userId)->first();

        try {
            // Verificamos si la sesión está activa
            $responseSessions = Http::timeout(5)->get("$nodeUrl/sessions");
            $activeSessions = $responseSessions->successful() ? $responseSessions->json()['activeSessions'] ?? [] : [];

            if (!in_array((string)$sessionId, $activeSessions)) {
                return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                    ->with('error', __('Session is not active. Please connect WhatsApp.'));
            }

            // Si la sesión está activa, obtenemos los chats (contactos)
            $responseContacts = Http::timeout(15)->get("$nodeUrl/get-chats/$sessionId");
            if (!$responseContacts->successful()) {
                 return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                    ->with('error', __('Could not get contacts from API.'));
            }

            // Procesar y ordenar contactos
            $contacts = collect($responseContacts->json()['chats'] ?? [])
                ->filter(fn ($contact) => isset($contact['jid']) && $contact['jid'] !== 'status@broadcast')
                ->map(function ($contact) {
                    $phoneNum = preg_replace('/@s\.whatsapp\.net$/', '', $contact['jid']);
                    // Usar messageTimestamp directamente para ordenar (ya está en ms)
                    $timestamp = $contact['messageTimestamp'] ?? 0;
                    return [
                        'jid'         => $contact['jid'],
                        'phone'       => $phoneNum,
                        'name'        => $contact['name'] ?? $phoneNum,
                        'lastMessageText' => $contact['lastMessageText'] ?? '', // Usar la clave correcta
                        'unreadCount' => $contact['unreadCount'] ?? 0,
                        'last_message_timestamp' => $timestamp, // Usar el timestamp directamente
                    ];
                });
            $sortedContacts = $contacts->sortByDesc('last_message_timestamp')->values();

            // Si se selecciona un teléfono, obtener y procesar mensajes
            if ($selectedPhone) {
                // Asegurarse que no tenga el @s.whatsapp.net si viene del query param
                $cleanPhone = preg_replace('/@s\.whatsapp\.net$/', '', $selectedPhone);
                $jid = $cleanPhone . '@s.whatsapp.net';

                $responseMsgs = Http::timeout(20)->get("$nodeUrl/get-messages/$sessionId/$jid");

                if ($responseMsgs->successful()) {
                    // Iterar sobre la nueva estructura devuelta por la API Node
                    $messages = collect($responseMsgs->json()['messages'] ?? [])->map(function ($messageItem) {
                        // La API ya devuelve la estructura correcta, solo la adaptamos al formato esperado por la vista
                        return [
                            'id'        => $messageItem['id'] ?? '',
                            'fromMe'    => $messageItem['fromMe'] ?? false,
                            'timestamp' => $messageItem['timestamp'] ?? 0,
                            'text'      => $messageItem['body'] ?? '',
                            'type'      => $messageItem['type'] ?? 'chat',
                            'mediaUrl'  => $messageItem['mediaUrl'] ?? null,
                            'filename'  => $messageItem['filename'] ?? null,
                            'mimetype'  => $messageItem['mimetype'] ?? null,
                        ];
                    });
                } else {
                    Log::error("Failed to get messages for $jid. Status: " . $responseMsgs->status());
                    return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                        ->with('error', __('Could not get messages from API.'));
                }
            }

            return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'));

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("WhatsApp API connection error for user $userId: " . $e->getMessage());
            return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                ->with('error', __('Could not connect to the WhatsApp service.'));
        } catch (\Exception $e) {
            Log::error("General error in WhatsApp for user $userId: " . $e->getMessage());
            return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                ->with('error', __('An error occurred.'));
        }
    }

    /**
     * Obtiene la conversación para un teléfono específico.
     * (Considera si este método es necesario o si index() ya cubre la funcionalidad)
     */
    public function conversation($phone)
    {
        // --- DEFINICIONES AÑADIDAS ---
        $userId    = Auth::id();
        $sessionId = $userId;
        $nodeUrl   = env('WHATSAPP_URL');
        $sortedContacts = collect(); // Inicializar
        $messages = collect(); // Inicializar
        $autoResponseConfig = AutoProcess::where('user_id', $userId)->first();
        $selectedPhone = $phone; // Guardar el teléfono seleccionado
        // --- FIN DEFINICIONES AÑADIDAS ---

        try {
            // Verificamos si la sesión está activa
            $responseSessions = Http::timeout(5)->get("$nodeUrl/sessions");
            $activeSessions = $responseSessions->successful() ? $responseSessions->json()['activeSessions'] ?? [] : [];

            if (!in_array((string)$sessionId, $activeSessions)) {
                return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                    ->with('error', __('Session is not active. Please connect WhatsApp.'));
            }

            // Obtenemos los chats (contactos)
            $responseContacts = Http::timeout(15)->get("$nodeUrl/get-chats/$sessionId");
            if (!$responseContacts->successful()) {
                return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                    ->with('error', __('Could not get contacts from API.'));
            }

            // Procesar y ordenar contactos
            $contacts = collect($responseContacts->json()['chats'] ?? [])
                ->filter(fn ($contact) => isset($contact['jid']) && $contact['jid'] !== 'status@broadcast')
                ->map(function ($contact) {
                    $phoneNum = preg_replace('/@s\.whatsapp\.net$/', '', $contact['jid']);
                    $timestamp = $contact['messageTimestamp'] ?? 0;
                    return [
                        'jid'         => $contact['jid'],
                        'phone'       => $phoneNum,
                        'name'        => $contact['name'] ?? $phoneNum,
                        'lastMessageText' => $contact['lastMessageText'] ?? '',
                        'unreadCount' => $contact['unreadCount'] ?? 0,
                        'last_message_timestamp' => $timestamp,
                    ];
                });
            $sortedContacts = $contacts->sortByDesc('last_message_timestamp')->values();

            // Obtener y procesar mensajes para el teléfono seleccionado
            $cleanPhone = preg_replace('/@s\.whatsapp\.net$/', '', $phone);
            $jid = $cleanPhone . '@s.whatsapp.net';

            $responseMsgs = Http::timeout(20)->get("$nodeUrl/get-messages/$sessionId/$jid");

            if ($responseMsgs->successful()) {
                $messages = collect($responseMsgs->json()['messages'] ?? [])->map(function ($messageItem) {
                    return [
                        'id'        => $messageItem['id'] ?? '',
                        'fromMe'    => $messageItem['fromMe'] ?? false,
                        'timestamp' => $messageItem['timestamp'] ?? 0,
                        'text'      => $messageItem['body'] ?? '',
                        'type'      => $messageItem['type'] ?? 'chat',
                        'mediaUrl'  => $messageItem['mediaUrl'] ?? null,
                        'filename'  => $messageItem['filename'] ?? null,
                        'mimetype'  => $messageItem['mimetype'] ?? null,
                    ];
                });
            } else {
                Log::error("Failed to get messages for $jid. Status: " . $responseMsgs->status());
                return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                    ->with('error', __('Could not get messages from API.'));
            }

            return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'));

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("WhatsApp API connection error for user $userId: " . $e->getMessage());
            return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                ->with('error', __('Could not connect to the WhatsApp service.'));
        } catch (\Exception $e) {
            Log::error("General error in WhatsApp for user $userId: " . $e->getMessage());
            return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                ->with('error', __('An error occurred.'));
        }
    }

    /**
     * Elimina un mensaje individual (en la API de Node).
     * Espera que el cuerpo de la petición sea JSON con { "remoteJid": ..., "fromMe": ..., "id": ... }
     */
    public function destroyMessage(Request $request)
    {
        $userId = Auth::id();
        $sessionId = $userId;
        $nodeUrl = env('WHATSAPP_URL');

        try {
            // Validar datos de entrada
            $validated = $request->validate([
                'remoteJid' => 'required|string',
                'fromMe' => 'required|boolean',
                'id' => 'required|string',
            ]);

            // Llamar a la API para eliminar el mensaje
            $response = Http::timeout(10)->delete("$nodeUrl/delete-message/$sessionId", [
                'remoteJid' => $validated['remoteJid'],
                'fromMe' => $validated['fromMe'],
                'id' => $validated['id'],
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Message deleted successfully.'),
                ]);
            } else {
                Log::error("Failed to delete message. Status: " . $response->status() . ", Response: " . $response->body());
                return response()->json([
                    'success' => false,
                    'message' => __('Could not delete message.'),
                ], $response->status());
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("WhatsApp API connection error during message deletion for user $userId: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Could not connect to the WhatsApp service.'),
            ], 500);
        } catch (\Exception $e) {
            Log::error("General error during message deletion for user $userId: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('An error occurred.'),
            ], 500);
        }
    }

    /**
     * Elimina todos los mensajes para un contacto (teléfono).
     */
    public function destroyChat($phone)
    {
        $userId = Auth::id();
        $sessionId = $userId;
        $nodeUrl = env('WHATSAPP_URL');

        try {
            // Asegurarse que el teléfono tenga el formato correcto
            $cleanPhone = preg_replace('/@s\.whatsapp\.net$/', '', $phone);
            $jid = $cleanPhone . '@s.whatsapp.net';

            // Llamar a la API para eliminar el chat
            $response = Http::timeout(10)->delete("$nodeUrl/clear-chat/$sessionId/$jid");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Chat cleared successfully.'),
                ]);
            } else {
                Log::error("Failed to clear chat. Status: " . $response->status() . ", Response: " . $response->body());
                return response()->json([
                    'success' => false,
                    'message' => __('Could not clear chat.'),
                ], $response->status());
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("WhatsApp API connection error during chat clearing for user $userId: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Could not connect to the WhatsApp service.'),
            ], 500);
        } catch (\Exception $e) {
            Log::error("General error during chat clearing for user $userId: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('An error occurred.'),
            ], 500);
        }
    }

    /**
     * Importa contactos desde la API de Node.js y devuelve respuesta JSON.
     */
    public function importContacts(Request $request)
    {
        $userId = Auth::id();
        $sessionId = $userId;
        $nodeUrl = env('WHATSAPP_URL');

        try {
            // Verificar si la sesión está activa
            $responseSessions = Http::timeout(5)->get("$nodeUrl/sessions");
            $activeSessions = $responseSessions->successful() ? $responseSessions->json()['activeSessions'] ?? [] : [];

            if (!in_array((string)$sessionId, $activeSessions)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Session is not active. Please connect WhatsApp.'),
                ], 400);
            }

            // Obtener contactos desde la API
            $responseContacts = Http::timeout(30)->get("$nodeUrl/get-contacts/$sessionId");

            if (!$responseContacts->successful()) {
                Log::error("Failed to get contacts from API for user $userId. Status: " . $responseContacts->status());
                return response()->json([
                    'success' => false,
                    'message' => __('Could not get contacts from API.'),
                ], $responseContacts->status());
            }

            $contacts = $responseContacts->json()['contacts'] ?? [];
            $importedCount = 0;

            // Procesar y guardar contactos
            foreach ($contacts as $contact) {
                if (isset($contact['id']) && $contact['id'] !== 'status@broadcast' && !empty($contact['name'])) {
                    $phoneNum = preg_replace('/@s\.whatsapp\.net$/', '', $contact['id']);
                    
                    // Crear o actualizar el contacto
                    Contact::updateOrCreate(
                        ['phone' => $phoneNum, 'user_id' => $userId],
                        [
                            'name' => $contact['name']
                        ]
                    );
                    
                    $importedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => __(':count contacts imported successfully.', ['count' => $importedCount]),
                'count' => $importedCount,
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
             Log::error("WhatsApp API connection error during import for user $userId: " . $e->getMessage());
             return response()->json(['success' => false, 'message' => __('Could not connect to the WhatsApp service.')], 500);
        } catch (\Exception $e) {
            Log::error("General error during contact import for user $userId: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('An error occurred during import.')], 500);
        }
    }

    // --- NUEVAS RUTAS JSON ---

    /**
     * Devuelve los mensajes de una conversación en formato JSON.
     */
    public function getMessagesJson(Request $request, $phone)
    {
        $userId    = Auth::id();
        $sessionId = $userId;
        $nodeUrl   = env('WHATSAPP_URL');
        $messages  = [];

        try {
            // Limpiar el teléfono por si acaso
            $cleanPhone = preg_replace('/@s\.whatsapp\.net$/', '', $phone);
            $jid = $cleanPhone . '@s.whatsapp.net';

            $responseMsgs = Http::timeout(20)->get("$nodeUrl/get-messages/$sessionId/$jid");

            if ($responseMsgs->successful()) {
                // Obtener los mensajes de la respuesta
                $rawMessages = $responseMsgs->json()['messages'] ?? [];
                $messages = [];
                
                // Procesar cada mensaje para asegurar que las URLs de los medios estén correctamente configuradas
                foreach ($rawMessages as $message) {
                    $messageData = $message['messageData'] ?? null;
                    $publicMediaUrl = $message['publicMediaUrl'] ?? null;
                    
                    // Si hay datos del mensaje pero no hay URL pública para los medios, intentamos generarla
                    if ($messageData && !$publicMediaUrl) {
                        // Verificar si es un mensaje con imagen, video o audio
                        $hasImage = isset($messageData['message']['imageMessage']);
                        $hasVideo = isset($messageData['message']['videoMessage']);
                        $hasAudio = isset($messageData['message']['audioMessage']);
                        $hasSticker = isset($messageData['message']['stickerMessage']);
                        $hasDocument = isset($messageData['message']['documentMessage']);
                        
                        // Si es un mensaje multimedia, construir la URL para obtener el medio
                        if ($hasImage || $hasVideo || $hasAudio || $hasSticker || $hasDocument) {
                            $mediaType = $hasImage ? 'image' : ($hasVideo ? 'video' : ($hasAudio ? 'audio' : ($hasSticker ? 'sticker' : 'document')));
                            $messageId = $messageData['key']['id'] ?? null;
                            
                            if ($messageId) {
                                // Construir la URL para obtener el medio a través del proxy de Laravel
                                // Esto asegura que el cliente pueda acceder a los medios sin problemas de CORS o localhost
                                // Asegurarnos de que el JID esté en el formato correcto antes de codificarlo
                                // Si no tiene el sufijo @s.whatsapp.net, añadirlo
                                if (!str_contains($jid, '@')) {
                                    $jid = $jid . '@s.whatsapp.net';
                                }
                                
                                // Codificar el JID para evitar problemas con caracteres especiales en la URL
                                $encodedJid = urlencode($jid);
                                
                                // Registrar la información del mensaje multimedia para depuración
                                Log::channel('daily')->info("Mensaje multimedia detectado - Tipo: $mediaType, MessageID: $messageId, JID: $jid");
                                
                                // Generar la URL absoluta para el proxy de medios
                                // Esta URL será procesada por nuestro método getMedia que probará diferentes rutas
                                $publicMediaUrl = route('whatsapp.media', [
                                    'sessionId' => $sessionId,
                                    'jid' => $encodedJid,
                                    'messageId' => $messageId
                                ]);
                                $message['publicMediaUrl'] = $publicMediaUrl;
                            }
                        }
                    }
                    
                    $messages[] = $message;
                }
            } else {
                 Log::error("Failed to get JSON messages for $jid, user $userId. Status: " . $responseMsgs->status());
                 return response()->json(['success' => false, 'messages' => [], 'message' => 'Could not retrieve messages.'], $responseMsgs->status());
            }

            // Devolvemos directamente la estructura recibida
            return response()->json([
                'success' => true,
                'messages' => $messages,
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
             Log::error("WhatsApp API connection error getting JSON messages for $phone, user $userId: " . $e->getMessage());
             return response()->json(['success' => false, 'message' => 'Connection error.'], 500);
        } catch (\Exception $e) {
            Log::error("General error getting JSON messages for $phone, user $userId: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error retrieving messages.'], 500);
        }
    }

     /**
     * Sirve como proxy para obtener archivos multimedia del servidor de WhatsApp.
     * Este método permite que el cliente acceda a los archivos multimedia a través de Laravel.
     */
    public function getMedia(Request $request, $sessionId, $jid, $messageId)
    {
        $nodeUrl = env('WHATSAPP_URL');
        $userId = auth()->id();
        
        // Log detallado al inicio para depuración
        Log::channel('daily')->info("=== WHATSAPP MEDIA REQUEST ===\nURL: {$request->fullUrl()}\nNode URL: {$nodeUrl}\nSession ID: {$sessionId}\nJID: {$jid}\nMessage ID: {$messageId}\nUser ID: {$userId}");
        
        try {
            // Decodificar el JID si está codificado en la URL
            $decodedJid = urldecode($jid);
            
            // Registrar los parámetros recibidos para depuración
            Log::info("Media request params - SessionId: $sessionId, JID: $decodedJid, MessageId: $messageId, UserId: $userId");
            
            // Verificar si el sessionId coincide con el usuario autenticado
            if ((string)$userId !== (string)$sessionId) {
                Log::warning("User ID ($userId) does not match session ID ($sessionId) in media request");
                // Usar el ID del usuario autenticado como sessionId para mayor seguridad
                $sessionId = $userId;
            }
            
            // Asegurarnos de que el JID tenga el formato correcto (con @s.whatsapp.net si no lo tiene)
            if (!str_contains($decodedJid, '@')) {
                $decodedJid = $decodedJid . '@s.whatsapp.net';
            }
            
            // Basado en los logs, la ruta correcta es: /media/{sessionId}/{filename}
            // El archivo se guarda con un formato: {sessionId}-{messageId}-{números}.jpg
            
            // Construir la URL directa basada en el formato observado en los logs
            $mediaUrl = "$nodeUrl/media/$sessionId/$sessionId-$messageId-*.jpg";
            
            // Intentar primero con la URL directa que vimos en los logs
            $directUrl = "$nodeUrl/media/$sessionId";
            
            Log::channel('daily')->info("Intentando obtener lista de archivos desde: $directUrl");
            
            try {
                $response = Http::timeout(5)->get($directUrl);
                
                // Si la respuesta es exitosa y es un directorio HTML, buscar el archivo que contiene el messageId
                if ($response->successful() && strpos($response->body(), 'Directory listing') !== false) {
                    $body = $response->body();
                    
                    // Buscar archivos que contengan el messageId en el HTML de la respuesta
                    if (preg_match_all('/<a href="([^"]*' . $messageId . '[^"]*)">/i', $body, $matches)) {
                        $matchingFile = $matches[1][0]; // Tomar el primer archivo que coincida
                        $mediaUrl = "$nodeUrl/media/$sessionId/$matchingFile";
                        
                        Log::channel('daily')->info("Archivo encontrado: $matchingFile - URL: $mediaUrl");
                        
                        $mediaResponse = Http::timeout(10)->get($mediaUrl);
                        
                        if ($mediaResponse->successful()) {
                            $contentType = $mediaResponse->header('Content-Type') ?? 'application/octet-stream';
                            $body = $mediaResponse->body();
                            
                            if (!empty($body)) {
                                return response($body, 200)
                                    ->header('Content-Type', $contentType)
                                    ->header('Cache-Control', 'public, max-age=86400'); // Cachear por 24 horas
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::channel('daily')->error("Error al buscar archivos de medios: " . $e->getMessage());
            }
            
            // Si no encontramos el archivo por el método anterior, probamos diferentes rutas
            $routesToTry = [
                "/media/$sessionId/$sessionId-$messageId", // Formato observado en los logs sin extensión
                "/media/$sessionId/$messageId", // Solo con messageId
                "/media/$sessionId/$decodedJid/$messageId", // Ruta con JID
                "/download/$sessionId/$messageId",
                "/download-media/$sessionId/$messageId",
                "/media-download/$sessionId/$messageId"
            ];
            
            // Extensiones comunes para probar
            $extensions = ["", ".jpg", ".jpeg", ".png", ".mp4", ".webp", ".pdf", ".ogg"];
            
            foreach ($routesToTry as $route) {
                foreach ($extensions as $ext) {
                    $url = $nodeUrl . $route . $ext;
                    
                    Log::channel('daily')->info("Intentando obtener medio desde: $url");
                    
                    try {
                        $response = Http::timeout(10)->get($url);
                        
                        if ($response->successful()) {
                            $contentType = $response->header('Content-Type') ?? 'application/octet-stream';
                            $body = $response->body();
                            
                            Log::channel('daily')->info("Éxito al obtener medio desde: $url - Content-Type: $contentType");
                            
                            // Verificar que el cuerpo de la respuesta no esté vacío
                            if (empty($body)) {
                                Log::error("Empty response body from WhatsApp media server for URL: $url");
                                continue;
                            }
                            
                            // Devolver el contenido con el tipo de contenido correcto
                            return response($body, 200)
                                ->header('Content-Type', $contentType)
                                ->header('Cache-Control', 'public, max-age=86400'); // Cachear por 24 horas
                        } else {
                            $statusCode = $response->status();
                            Log::channel('daily')->warning("Error al obtener medio desde: $url - Status: $statusCode");
                        }
                    } catch (\Exception $e) {
                        Log::channel('daily')->error("Excepción al obtener medio desde: $url - " . $e->getMessage());
                    }
                }
            }
            
            // Si llegamos aquí, no se encontró el archivo
            Log::error("No se pudo encontrar el archivo multimedia para messageId: $messageId");
            return response()->json(['error' => 'Could not retrieve media', 'messageId' => $messageId], 404);
            
        } catch (\Exception $e) {
            Log::error("Error getting media from WhatsApp server: " . $e->getMessage());
            return response()->json(['error' => 'Error retrieving media: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Devuelve la lista de contactos en formato JSON.
     */
    public function getContactsJson(Request $request)
    {
         $userId    = Auth::id();
         $sessionId = $userId;
         $nodeUrl   = env('WHATSAPP_URL');
         $contacts = [];

         try {
            // Llama a get-chats para obtener la lista formateada y ordenada
            $responseContacts = Http::timeout(15)->get("$nodeUrl/get-chats/$sessionId");

            if (!$responseContacts->successful()) {
                 Log::error("Failed to get JSON contacts for user $userId. Status: " . $responseContacts->status());
                 return response()->json(['success' => false, 'contacts' => [], 'message' => 'Could not retrieve contacts.'], $responseContacts->status());
            }

            // Procesar los contactos para asegurar que tengan todos los campos necesarios
            $rawContacts = $responseContacts->json()['chats'] ?? [];
            $contacts = collect($rawContacts)
                ->filter(fn ($contact) => isset($contact['jid']) && $contact['jid'] !== 'status@broadcast')
                ->map(function ($contact) {
                    $phoneNum = preg_replace('/@s\.whatsapp\.net$/', '', $contact['jid']);
                    return [
                        'jid' => $contact['jid'],
                        'phone' => $phoneNum,
                        'name' => $contact['name'] ?? $phoneNum,
                        'lastMessageText' => $contact['lastMessageText'] ?? '',
                        'unreadCount' => $contact['unreadCount'] ?? 0,
                        'last_message_timestamp' => $contact['messageTimestamp'] ?? 0,
                    ];
                })->values()->all();

            return response()->json([
                'success' => true,
                'contacts' => $contacts,
            ]);

         } catch (\Illuminate\Http\Client\ConnectionException $e) {
             Log::error("WhatsApp API connection error getting JSON contacts for user $userId: " . $e->getMessage());
             return response()->json(['success' => false, 'message' => 'Connection error.'], 500);
         } catch (\Exception $e) {
            Log::error("General error getting JSON contacts for user $userId: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error retrieving contacts.'], 500);
         }
    }

}
