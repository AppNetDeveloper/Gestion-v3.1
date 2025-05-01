<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Contact; // Asegúrate que el modelo Contact existe y está importado
use App\Models\AutoProcess; // Asegúrate que el modelo AutoProcess existe y está importado
use Session; // Session no se usa si devuelves JSON

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

        // Definir $sortedContacts y $autoResponseConfig como variables vacías por defecto
        $sortedContacts = collect();
        $autoResponseConfig = null;
        $messages      = collect();
        $selectedPhone = $request->query('phone');

        // Intentar obtener la configuración de auto respuesta siempre
        $autoResponseConfig = AutoProcess::where('user_id', $userId)->first();

        try {
            // Verificamos si la sesión está activa
            $responseSessions = Http::timeout(5)->get("$nodeUrl/sessions"); // Timeout corto para check rápido
            $activeSessions = $responseSessions->successful() ? $responseSessions->json()['activeSessions'] ?? [] : [];

            if (!in_array((string)$sessionId, $activeSessions)) { // Convertir sessionId a string para comparación segura
                // Si la sesión no está activa, mostrar vista con error (sin intentar cargar contactos/mensajes de API)
                return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                    ->with('error', __('Session is not active. Please connect WhatsApp.'));
            }

            // Si la sesión está activa, obtenemos los chats (contactos) de la API de Node
            $responseContacts = Http::timeout(15)->get("$nodeUrl/get-chats/$sessionId"); // Timeout más largo para chats
            if (!$responseContacts->successful()) {
                 return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                    ->with('error', __('Could not get contacts from API.'));
            }

            // Asegurar que 'jid' está presente y filtrar contactos válidos
            $contacts = collect($responseContacts->json()['chats'] ?? [])
                ->filter(function ($contact) {
                    return isset($contact['jid']) && $contact['jid'] !== 'status@broadcast';
                })
                ->map(function ($contact) {
                    // Limpiar el número de teléfono de su formato completo
                    $phoneNum = preg_replace('/@s\.whatsapp\.net$/', '', $contact['jid']);
                    return [
                        'jid'         => $contact['jid'], // 'jid' es necesario
                        'phone'       => $phoneNum,
                        'name'        => $contact['name'] ?? $phoneNum, // Usar phone si no hay nombre
                        'lastMessage' => $contact['lastMessage'] ?? null,
                        'unreadCount' => $contact['unreadCount'] ?? 0,
                         // Añadir timestamp para ordenar
                        'last_message_timestamp' => $contact['lastMessage']['messageTimestamp'] ?? 0,
                    ];
                });

            // Ordenar los contactos por fecha del último mensaje (si existe)
            $sortedContacts = $contacts->sortByDesc('last_message_timestamp')->values();

            // Si se recibe un query parameter "phone", obtenemos los mensajes para ese contacto
            if ($selectedPhone) {
                $jid = $selectedPhone . '@s.whatsapp.net';
                $responseMsgs = Http::timeout(20)->get("$nodeUrl/get-messages/$sessionId/$jid"); // Timeout para mensajes
                if ($responseMsgs->successful()) {
                    $messages = collect($responseMsgs->json()['messages'] ?? []);
                } else {
                     // No redirigir, solo mostrar error y la vista con lo que se tenga
                     return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                        ->with('error', __('Could not get messages for the selected contact.'));
                }
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
             Log::error("WhatsApp API connection error for user $userId: " . $e->getMessage());
             // Mostrar vista con error de conexión
             return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                    ->with('error', __('Could not connect to the WhatsApp service. Please check if it is running.'));
        } catch (\Exception $e) {
            Log::error("General error in WhatsApp index for user $userId: " . $e->getMessage());
             return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'))
                    ->with('error', __('An unexpected error occurred.'));
        }

        return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'));
    }


    public function conversation($phone)
    {
        $userId    = Auth::id();
        $sessionId = $userId;
        $nodeUrl   = env('WATSHAPP_URL');

         // Definir variables por defecto
        $sortedContacts = collect();
        $messages = collect();
        $autoResponseConfig = AutoProcess::where('user_id', $userId)->first();

        try {
             // Obtener contactos desde la API
            $responseContacts = Http::timeout(15)->get("$nodeUrl/get-chats/$sessionId");
            if (!$responseContacts->successful()) {
                return redirect()->route('whatsapp.index')->with('error', 'No se pudieron obtener los contactos.'); // Redirigir a index si falla
            }

            // Filtrar los contactos válidos
            $contacts = collect($responseContacts->json()['chats'] ?? [])
                ->filter(function ($contact) {
                    return isset($contact['jid']) && $contact['jid'] !== 'status@broadcast';
                })
                ->map(function ($contact) {
                    $phoneNum = preg_replace('/@s\.whatsapp\.net$/', '', $contact['jid']);
                    return [
                        'jid'         => $contact['jid'],
                        'phone'       => $phoneNum,
                        'name'        => $contact['name'] ?? $phoneNum,
                        'lastMessage' => $contact['lastMessage'] ?? null,
                        'unreadCount' => $contact['unreadCount'] ?? 0,
                         // Añadir timestamp para ordenar
                        'last_message_timestamp' => $contact['lastMessage']['messageTimestamp'] ?? 0,
                    ];
                });

            // Ordenar los contactos por fecha del último mensaje (si existe)
            $sortedContacts = $contacts->sortByDesc('last_message_timestamp')->values();

            // Obtener los mensajes para el contacto seleccionado
            if (strpos($phone, '@') === false) {
                $jid = $phone . '@s.whatsapp.net';
            } else {
                $jid = $phone; // Asumir que ya es JID completo si tiene @
            }
            $responseMsgs = Http::timeout(20)->get("$nodeUrl/get-messages/$sessionId/$jid");
            if ($responseMsgs->successful()) {
                $messages = collect($responseMsgs->json()['messages'] ?? []);
            } else {
                 return redirect()->route('whatsapp.index')->with('error', 'No se pudieron obtener los mensajes para ' . $phone); // Redirigir a index si falla
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
            'selectedPhone'  => $phone, // Pasar el phone limpio
            'messages'       => $messages,
            'autoResponseConfig' => $autoResponseConfig,
        ]);
    }

    /**
     * Elimina un mensaje individual (en la API de Node).
     * Espera un {id} que identificará el mensaje en tu base de datos
     * o la forma en que obtengas "remoteJid", "fromMe", e "id".
     */
    public function destroyMessage($id, Request $request)
    {
        $userId  = Auth::id(); // Usamos el id del usuario como sessionId
        $nodeUrl = env('WATSHAPP_URL');

        // Extraemos los datos enviados desde el formulario
        $remoteJid = $request->input('remoteJid');
        $fromMe    = $request->input('fromMe');

        if (!$remoteJid || $fromMe === null || !$id) {
            // Devolver error JSON para AJAX
            return response()->json(['success' => false, 'message' => 'Datos del mensaje incompletos.'], 400);
        }

        try {
            $response = Http::delete("$nodeUrl/delete-message/$userId", [
                'remoteJid' => $remoteJid,
                'fromMe'    => filter_var($fromMe, FILTER_VALIDATE_BOOLEAN),
                'id'        => $id,
            ]);

            if ($response->successful()) {
                 // Devolver éxito JSON
                return response()->json(['success' => true, 'message' => 'Mensaje eliminado correctamente.']);
            } else {
                 // Devolver error JSON
                 Log::error("Error deleting message $id for user $userId: " . $response->body());
                 return response()->json(['success' => false, 'message' => "Error eliminando el mensaje: " . $response->json()['error'] ?? $response->body()], $response->status());
            }
        } catch (\Exception $e) {
             Log::error("Exception deleting message $id for user $userId: " . $e->getMessage());
             // Devolver error JSON
             return response()->json(['success' => false, 'message' => "Error en la solicitud: " . $e->getMessage()], 500);
        }
    }

    /**
     * Elimina todos los mensajes para un contacto (teléfono).
     * Llama a la API Node para /delete-chat/:sessionId con { jid }
     */
    public function destroyChat($phone)
    {
        $userId  = Auth::id();
        $nodeUrl = env('WATSHAPP_URL');

        // Convertimos el $phone en el JID esperado por la API Node
        $jid = $phone . '@s.whatsapp.net';

        try {
            $response = Http::delete("$nodeUrl/delete-chat/$userId", [
                'jid' => $jid
            ]);

            if ($response->successful()) {
                // Opcional: borrar en tu DB los mensajes de ese phone
                // WhatsappMessage::where('phone', $phone)->delete();

                // Redirigir a index porque la conversación ya no existe
                return redirect()->route('whatsapp.index')->with('success', "Chat con $phone eliminado correctamente.");
            } else {
                 Log::error("Error deleting chat $jid for user $userId: " . $response->body());
                 return back()->with('error', "Error al eliminar chat: " . $response->json()['error'] ?? $response->body());
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
            // Llamada a la API de Node.js para obtener los contactos
            $response = Http::timeout(30)->get("$nodeUrl/get-contacts/$userId"); // Aumentar timeout si es necesario

            // Verificamos si la solicitud fue exitosa
            if (!$response->successful()) {
                Log::error("Failed to fetch contacts from API for user $userId. Status: " . $response->status() . " Body: " . $response->body());
                // Devolver error JSON
                return response()->json(['success' => false, 'message' => __('Failed to fetch contacts from API.')], $response->status());
            }

            // Obtener los contactos del JSON que se devuelve
            $contactsData = $response->json()['contacts'] ?? [];

            // Array para almacenar los contactos a insertar
            $contactsToInsert = [];
            $importedCount = 0;

            // Iterar sobre los contactos y procesarlos
            foreach ($contactsData as $contact) {
                // Limpiar el número de teléfono (si tiene @...)
                $phone = preg_replace('/@.*$/', '', $contact['jid']);

                // Validar que el teléfono no esté vacío y sea numérico (básico)
                if (empty($phone) || !is_numeric($phone)) {
                    Log::warning("Skipping invalid contact JID during import for user $userId: " . $contact['jid']);
                    continue;
                }

                // Verificar si el teléfono ya existe en la base de datos PARA ESTE USUARIO
                if (!Contact::where('phone', $phone)->where('user_id', $userId)->exists()) {
                    $contactsToInsert[] = [
                        'user_id'   => $userId,
                        'name' => $contact['name'] ?? $phone, // Usar phone si no hay nombre
                        'phone' => $phone,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Si hay contactos para insertar, los agregamos a la base de datos
            if (!empty($contactsToInsert)) {
                Contact::insert($contactsToInsert);
                $importedCount = count($contactsToInsert);
                Log::info("$importedCount contacts imported for user $userId.");
                // Devolver éxito JSON
                return response()->json(['success' => true, 'message' => __(':count contacts imported successfully.', ['count' => $importedCount])]);
            } else {
                Log::info("No new contacts to import for user $userId.");
                 // Devolver éxito JSON (pero indicando que no hubo nuevos)
                 return response()->json(['success' => true, 'message' => __('No new contacts were imported.')]);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
             Log::error("WhatsApp API connection error during import for user $userId: " . $e->getMessage());
             return response()->json(['success' => false, 'message' => __('Could not connect to the WhatsApp service.')], 500);
        } catch (\Exception $e) {
            Log::error("General error during contact import for user $userId: " . $e->getMessage());
             // Devolver error JSON
            return response()->json(['success' => false, 'message' => __('An error occurred during import.')], 500);
        }
    }

    // --- NUEVAS RUTAS JSON ---

    /**
     * Devuelve los mensajes de una conversación en formato JSON.
     * (Necesita ruta: Route::get('/whatsapp/messages/json/{phone}', [WhatsAppController::class, 'getMessagesJson'])->name('whatsapp.messages.json');)
     */
    public function getMessagesJson(Request $request, $phone)
    {
        $userId    = Auth::id();
        $sessionId = $userId;
        $nodeUrl   = env('WATSHAPP_URL');
        $messages  = []; // Valor por defecto

        try {
            if (strpos($phone, '@') === false) {
                $jid = $phone . '@s.whatsapp.net';
            } else {
                $jid = $phone;
            }

            $responseMsgs = Http::timeout(20)->get("$nodeUrl/get-messages/$sessionId/$jid");

            if ($responseMsgs->successful()) {
                $messages = $responseMsgs->json()['messages'] ?? [];
                // Opcional: añadir timestamp PHP si no viene de la API consistentemente
                // foreach ($messages as &$msg) {
                //     $msg['created_at_ts'] = isset($msg['created_at']) ? \Carbon\Carbon::parse($msg['created_at'])->timestamp : ($msg['messageTimestamp'] ?? 0);
                // }
            } else {
                 Log::error("Failed to get JSON messages for $jid, user $userId. Status: " . $responseMsgs->status());
                 // Devolver éxito false pero con array vacío para no romper el JS
                 return response()->json(['success' => false, 'messages' => [], 'message' => 'Could not retrieve messages.'], $responseMsgs->status());
            }

            return response()->json([
                'success' => true,
                'messages' => $messages,
                // Opcional: puedes devolver info del chat también
                // 'chat_info' => ['name' => 'Nombre Contacto', 'status' => 'online']
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
     * (Necesita ruta: Route::get('/whatsapp/contacts/json', [WhatsAppController::class, 'getContactsJson'])->name('whatsapp.contacts.json');)
     */
    public function getContactsJson(Request $request)
    {
         $userId    = Auth::id();
         $sessionId = $userId;
         $nodeUrl   = env('WATSHAPP_URL');
         $contacts = []; // Valor por defecto

         try {
            $responseContacts = Http::timeout(15)->get("$nodeUrl/get-chats/$sessionId");

            if (!$responseContacts->successful()) {
                 Log::error("Failed to get JSON contacts for user $userId. Status: " . $responseContacts->status());
                 return response()->json(['success' => false, 'contacts' => [], 'message' => 'Could not retrieve contacts.'], $responseContacts->status());
            }

            $rawContacts = $responseContacts->json()['chats'] ?? [];

            $contacts = collect($rawContacts)
                ->filter(function ($contact) {
                    return isset($contact['jid']) && $contact['jid'] !== 'status@broadcast';
                })
                ->map(function ($contact) {
                    $phoneNum = preg_replace('/@s\.whatsapp\.net$/', '', $contact['jid']);
                    return [
                        'jid'         => $contact['jid'],
                        'phone'       => $phoneNum,
                        'name'        => $contact['name'] ?? $phoneNum,
                        // Añadir timestamp para ordenar en JS si es necesario
                        'last_message_timestamp' => $contact['lastMessage']['messageTimestamp'] ?? 0,
                    ];
                })->values()->all(); // Convertir a array simple

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
