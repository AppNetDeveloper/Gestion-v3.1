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
                            'name' => $contact['name'],
                            'whatsapp_name' => $contact['name'],
                            'imported_from' => 'whatsapp',
                            'last_activity' => now(),
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
                // La API Node ya devuelve la estructura correcta con messageData y publicMediaUrl
                $messages = $responseMsgs->json()['messages'] ?? [];
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
