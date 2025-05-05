<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Contact; // Asegúrate que el modelo Contact existe y está importado
use App\Models\AutoProcess; // Asegúrate que el modelo AutoProcess existe y está importado
// Session no se usa si devuelves JSON

class WhatsAppController extends Controller
{
    /**
     * Muestra el listado de contactos y, si se selecciona, la conversación para el usuario logueado.
     */
    public function index(Request $request)
    {
        $userId    = Auth::id();
        $sessionId = $userId; // Usamos el id del usuario como sessionId
        $nodeUrl   = env('WATSHAPP_URL'); // Ej: http://localhost:3005

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
                        // Devolvemos un array que contiene tanto los datos originales como la URL pública
                        return [
                            'messageData' => $messageItem['messageData'] ?? null, // Mensaje original
                            'publicMediaUrl' => $messageItem['publicMediaUrl'] ?? null // URL pública del medio
                        ];
                    });
                } else {
                     Log::error("Failed to get messages for $jid, user $userId. Status: " . $responseMsgs->status());
                     return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                        ->with('error', __('Could not get messages for the selected contact.'));
                }
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
             Log::error("WhatsApp API connection error for user $userId: " . $e->getMessage());
             return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                    ->with('error', __('Could not connect to the WhatsApp service. Please check if it is running.'));
        } catch (\Exception $e) {
            Log::error("General error in WhatsApp index for user $userId: " . $e->getMessage());
             return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                    ->with('error', __('An unexpected error occurred.'));
        }

        // Pasamos la colección de mensajes formateados a la vista
        return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'));
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
        $nodeUrl   = env('WATSHAPP_URL');
        $sortedContacts = collect(); // Inicializar
        $messages = collect(); // Inicializar
        $autoResponseConfig = AutoProcess::where('user_id', $userId)->first();
        $selectedPhone = $phone; // Guardar el teléfono seleccionado
        // --- FIN DEFINICIONES AÑADIDAS ---

        try {
             // Obtener contactos desde la API (igual que en index)
            $responseContacts = Http::timeout(15)->get("$nodeUrl/get-chats/$sessionId");
            if (!$responseContacts->successful()) {
                return redirect()->route('whatsapp.index')->with('error', 'No se pudieron obtener los contactos.');
            }

             // Procesar y ordenar contactos (igual que en index)
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


            // Obtener mensajes (con corrección)
            // Limpiar el teléfono por si acaso
            $cleanPhone = preg_replace('/@s\.whatsapp\.net$/', '', $phone);
            $jid = $cleanPhone . '@s.whatsapp.net';

            $responseMsgs = Http::timeout(20)->get("$nodeUrl/get-messages/$sessionId/$jid");
            if ($responseMsgs->successful()) {
                 // La API Node ya devuelve la estructura correcta
                 $messages = collect($responseMsgs->json()['messages'] ?? [])->map(function ($messageItem) {
                     return [
                         'messageData' => $messageItem['messageData'] ?? null,
                         'publicMediaUrl' => $messageItem['publicMediaUrl'] ?? null
                     ];
                 });
            } else {
                 Log::error("Failed to get messages for conversation $jid, user $userId. Status: " . $responseMsgs->status());
                 return redirect()->route('whatsapp.index')->with('error', 'No se pudieron obtener los mensajes para ' . $phone);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
             Log::error("WhatsApp API connection error in conversation for user $userId, phone $phone: " . $e->getMessage());
             return redirect()->route('whatsapp.index')->with('error', __('Could not connect to the WhatsApp service. Please check if it is running.'));
        } catch (\Exception $e) {
            Log::error("General error in WhatsApp conversation for user $userId, phone $phone: " . $e->getMessage());
             return redirect()->route('whatsapp.index')->with('error', __('An unexpected error occurred.'));
        }


         return view('whatsapp.index', [
            'sortedContacts' => $sortedContacts,
            'selectedPhone'  => $selectedPhone, // Usar la variable definida
            'messages'       => $messages, // Pasar mensajes formateados
            'autoResponseConfig' => $autoResponseConfig,
        ]);
    }

    /**
     * Elimina un mensaje individual (en la API de Node).
     * Espera que el cuerpo de la petición sea JSON con { "remoteJid": ..., "fromMe": ..., "id": ... }
     */
    public function destroyMessage(Request $request)
    {
        $userId  = Auth::id();
        $nodeUrl = env('WATSHAPP_URL');

        // Obtener datos del cuerpo JSON (asumiendo que el JS envía messageKey)
        $messageKeyData = $request->input('messageKey');

        // Validar que messageKey y sus componentes existan en la *petición de Laravel*
        if (!$messageKeyData || !isset($messageKeyData['remoteJid']) || !isset($messageKeyData['fromMe']) || !isset($messageKeyData['id'])) {
            // Este error ahora es menos probable si el JS envía messageKey, pero lo mantenemos por si acaso
            return response()->json(['success' => false, 'message' => 'Datos del messageKey incompletos recibidos por Laravel.'], 400);
        }

        // --- Preparar datos como campos sueltos para Node.js ---
        $remoteJid = $messageKeyData['remoteJid'];
        $fromMe = filter_var($messageKeyData['fromMe'], FILTER_VALIDATE_BOOLEAN); // Asegurar booleano
        $messageId = $messageKeyData['id'];
        $participant = $messageKeyData['participant'] ?? null; // Obtener participant si existe
        // --- FIN Preparación ---

        $messageIdForLog = $messageId;

        try {
            // --- Enviar campos sueltos en el cuerpo JSON a Node.js ---
            $payload = [ // Crear el payload con campos sueltos
                'remoteJid' => $remoteJid,
                'fromMe'    => $fromMe,
                'id'        => $messageId,
            ];
            // Añadir participant al payload solo si existe y fromMe es false
            if ($participant && !$fromMe) {
                $payload['participant'] = $participant;
            }

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                          ->withBody(json_encode($payload), 'application/json') // Enviar el payload con campos sueltos
                          ->delete("$nodeUrl/delete-message/$userId");
            // --- FIN Envío ---


            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'Mensaje eliminado correctamente.']);
            } else {
                 Log::error("Error deleting message $messageIdForLog for user $userId: " . $response->body());
                 $errorBody = $response->json();
                 // El error de Node.js ahora debería ser más específico si algo falla allí
                 $errorMessage = $errorBody['error'] ?? ($errorBody['message'] ?? $response->body());
                 return response()->json(['success' => false, 'message' => "Error eliminando el mensaje: " . $errorMessage], $response->status());
            }
        } catch (\Exception $e) {
             Log::error("Exception deleting message $messageIdForLog for user $userId: " . $e->getMessage());
             return response()->json(['success' => false, 'message' => "Error en la solicitud: " . $e->getMessage()], 500);
        }
    }

    /**
     * Elimina todos los mensajes para un contacto (teléfono).
     */
    public function destroyChat($phone)
    {
        $userId  = Auth::id();
        $nodeUrl = env('WATSHAPP_URL');

        // Convertimos el $phone en el JID esperado por la API Node
        $cleanPhone = preg_replace('/@s\.whatsapp\.net$/', '', $phone);
        $jid = $cleanPhone . '@s.whatsapp.net';

        try {
            // La API /delete-chat espera el JID en el cuerpo JSON
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                          ->withBody(json_encode(['jid' => $jid]), 'application/json')
                          ->delete("$nodeUrl/delete-chat/$userId");

            if ($response->successful()) {
                return redirect()->route('whatsapp.index')->with('success', "Chat con $cleanPhone eliminado correctamente.");
            } else {
                 Log::error("Error deleting chat $jid for user $userId: " . $response->body());
                 $errorBody = $response->json();
                 $errorMessage = $errorBody['error'] ?? ($errorBody['message'] ?? $response->body());
                 return back()->with('error', "Error al eliminar chat: " . $errorMessage);
            }
        } catch (\Exception $e) {
             Log::error("Exception deleting chat $jid for user $userId: " . $e->getMessage());
             return back()->with('error', "Error en la solicitud: " . $e->getMessage());
        }
    }

    /**
     * Importa contactos desde la API de Node.js y devuelve respuesta JSON.
     */
    public function importContacts(Request $request)
    {
        $userId = Auth::id();
        $nodeUrl = env('WATSHAPP_URL');

        try {
            // Llama a /get-contacts de la API Node
            $response = Http::timeout(30)->get("$nodeUrl/get-contacts/$userId");

            if (!$response->successful()) {
                Log::error("Failed to fetch contacts from API for user $userId. Status: " . $response->status() . " Body: " . $response->body());
                return response()->json(['success' => false, 'message' => __('Failed to fetch contacts from API.')], $response->status());
            }

            $contactsData = $response->json()['contacts'] ?? [];
            $contactsToInsert = [];
            $importedCount = 0;

            foreach ($contactsData as $contact) {
                // Limpiar el número de teléfono
                $phone = preg_replace('/@.*$/', '', $contact['jid']);
                if (empty($phone) || !is_numeric($phone)) {
                    Log::warning("Skipping invalid contact JID during import for user $userId: " . $contact['jid']);
                    continue;
                }
                // Verificar si ya existe para este usuario
                if (!Contact::where('phone', $phone)->where('user_id', $userId)->exists()) {
                    $contactsToInsert[] = [
                        'user_id'   => $userId,
                        'name' => $contact['name'] ?? $phone, // Usar 'name' que viene de la API Node
                        'phone' => $phone,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($contactsToInsert)) {
                Contact::insert($contactsToInsert);
                $importedCount = count($contactsToInsert);
                Log::info("$importedCount contacts imported for user $userId.");
                return response()->json(['success' => true, 'message' => __(':count contacts imported successfully.', ['count' => $importedCount])]);
            } else {
                Log::info("No new contacts to import for user $userId.");
                 return response()->json(['success' => true, 'message' => __('No new contacts were imported.')]);
            }

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
        $nodeUrl   = env('WATSHAPP_URL');
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
         $nodeUrl   = env('WATSHAPP_URL');
         $contacts = [];

         try {
            // Llama a get-chats para obtener la lista formateada y ordenada
            $responseContacts = Http::timeout(15)->get("$nodeUrl/get-chats/$sessionId");

            if (!$responseContacts->successful()) {
                 Log::error("Failed to get JSON contacts for user $userId. Status: " . $responseContacts->status());
                 return response()->json(['success' => false, 'contacts' => [], 'message' => 'Could not retrieve contacts.'], $responseContacts->status());
            }

            // La API /get-chats ya devuelve el formato deseado
            $contacts = $responseContacts->json()['chats'] ?? [];

             return response()->json([
                'success' => true,
                'contacts' => $contacts, // Devolver directamente
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
