<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Contact;
use Session;

class WhatsappController extends Controller
{
    /**
     * Muestra el listado de contactos y, si se selecciona, la conversación para el usuario logueado.
     */
    public function index(Request $request)
    {
        $userId    = Auth::id();
        $sessionId = $userId; // Usamos el id del usuario como sessionId
        $nodeUrl   = env('WATSHAPP_URL'); // Ej: http://localhost:3005

        // Verificamos si la sesión está activa
        $responseSessions = Http::get("$nodeUrl/sessions");
        $activeSessions = $responseSessions->successful() ? $responseSessions->json()['activeSessions'] : [];

        // Definir $sortedContacts y $autoResponseConfig como variables vacías por defecto
        $sortedContacts = collect();
        $autoResponseConfig = null;

        if (!in_array($sessionId, $activeSessions)) {
            // Si la sesión no está activa, redirigir con un mensaje de error y no obtener los contactos
            return view('whatsapp.index')
                ->with('error', 'Sesión no activa. Cargamos datos sin conexión a la API.')
                ->with('sortedContacts', $sortedContacts)
                ->with('autoResponseConfig', $autoResponseConfig);
        }

        // Si la sesión está activa, obtenemos los chats (contactos) de la API de Node
        $response = Http::get("$nodeUrl/get-chats/$sessionId");
        if (!$response->successful()) {
            return redirect()->back()->with('error', 'No se pudieron obtener los contactos.');
        }

        // Asegurar que 'jid' está presente y filtrar contactos válidos
        $contacts = collect($response->json()['chats'] ?? [])
            ->filter(function ($contact) {
                return isset($contact['jid']) && $contact['jid'] !== 'status@broadcast';
            })
            ->map(function ($contact) {
                // Limpiar el número de teléfono de su formato completo
                $phone = $contact['jid'];
                return [
                    'jid'         => $contact['jid'], // 'jid' es necesario
                    'phone'       => $phone,
                    'name'        => $contact['name'] ?? $phone,
                    'lastMessage' => $contact['lastMessage'] ?? null,
                    'unreadCount' => $contact['unreadCount'] ?? 0,
                ];
            });

        // Ordenar los contactos por fecha del último mensaje (si existe)
        $sortedContacts = $contacts->sortByDesc(function ($contact) {
            return $contact['lastMessage']['messageTimestamp'] ?? 0;
        })->values();

        // Si se recibe un query parameter "phone", obtenemos los mensajes para ese contacto
        $selectedPhone = $request->query('phone');
        $messages      = collect();
        if ($selectedPhone) {
            $jid = $selectedPhone . '@s.whatsapp.net';
            $responseMsgs = Http::get("$nodeUrl/get-messages/$sessionId/$jid");
            if ($responseMsgs->successful()) {
                $messages = collect($responseMsgs->json()['messages'] ?? []);
            } else {
                return redirect()->back()->with('error', 'No se pudieron obtener los mensajes.');
            }
        }

        // Obtener la configuración de auto respuesta
        $autoResponseConfig = \App\Models\AutoProcess::where('user_id', auth()->id())->first();

        return view('whatsapp.index', compact('sortedContacts', 'selectedPhone', 'messages', 'autoResponseConfig'));
    }


    public function conversation($phone)
    {
        $userId    = Auth::id();
        $sessionId = $userId;
        $nodeUrl   = env('WATSHAPP_URL');

        // Obtener contactos desde la API
        $response = Http::get("$nodeUrl/get-chats/$sessionId");
        if (!$response->successful()) {
            return redirect()->back()->with('error', 'No se pudieron obtener los contactos.');
        }

        // Filtrar los contactos válidos
        $contacts = collect($response->json()['chats'] ?? [])
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
                ];
            });

        // Ordenar los contactos por fecha del último mensaje (si existe)
        $sortedContacts = $contacts->sortByDesc(function ($contact) {
            return $contact['lastMessage']['messageTimestamp'] ?? 0;
        })->values();

        // Obtener los mensajes para el contacto seleccionado
        if (strpos($phone, '@') === false) {
            $jid = $phone . '@s.whatsapp.net';
        } else {
            $jid = $phone;
        }
        $responseMsgs = Http::get("$nodeUrl/get-messages/$sessionId/$jid");
        if ($responseMsgs->successful()) {
            $messages = collect($responseMsgs->json()['messages'] ?? []);
        } else {
            return redirect()->back()->with('error', 'No se pudieron obtener los mensajes.');
        }

        // Obtener la configuración de auto respuesta
        $autoResponseConfig = \App\Models\AutoProcess::where('user_id', auth()->id())->first();

        return view('whatsapp.index', [
            'sortedContacts' => $sortedContacts,
            'selectedPhone'  => $phone,
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

        if (!$remoteJid || $fromMe === null) {
            return back()->with('error', 'Datos del mensaje incompletos.');
        }

        try {
            $response = Http::delete("$nodeUrl/delete-message/$userId", [
                'remoteJid' => $remoteJid,
                'fromMe'    => filter_var($fromMe, FILTER_VALIDATE_BOOLEAN),
                'id'        => $id,
            ]);

            if ($response->successful()) {
                return back()->with('success', "Mensaje eliminado correctamente.");
            } else {
                return back()->with('error', "Error eliminando el mensaje: " . $response->body());
            }
        } catch (\Exception $e) {
            return back()->with('error', "Error en la solicitud: " . $e->getMessage());
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

                return back()->with('success', "Chat con $phone eliminado correctamente.");
            } else {
                return back()->with('error', "Error al eliminar chat: " . $response->body());
            }
        } catch (\Exception $e) {
            return back()->with('error', "Error en la solicitud: " . $e->getMessage());
        }
    }
    // Función para importar contactos desde la API de Node.js
    public function importContacts(Request $request)
    {
        // Obtener el userId desde la sesión autenticada
        $userId = Auth::id();
        $nodeUrl = env('WATSHAPP_URL');  // URL de la API de Node.js (asegúrate de que esté configurada en .env)

        // Llamada a la API de Node.js para obtener los contactos
        $response = Http::get("$nodeUrl/get-contacts/$userId");

        // Verificamos si la solicitud fue exitosa
        if ($response->successful()) {
            // Obtener los contactos del JSON que se devuelve
            $contactsData = $response->json()['contacts'];

            // Array para almacenar los contactos a insertar
            $contactsToInsert = [];

            // Iterar sobre los contactos y procesarlos
            foreach ($contactsData as $contact) {
                // Limpiar el número de teléfono (si tiene @...)
                $phone = preg_replace('/@.*$/', '', $contact['jid']);  // Extraemos solo el teléfono limpio

                // Verificar si el teléfono ya existe en la base de datos
                if (!Contact::where('phone', $phone)->exists()) {
                    $contactsToInsert[] = [
                        'user_id'   => $userId, // ID del usuario al que pertenece el contacto
                        'name' => $contact['name'],  // Nombre del contacto
                        'phone' => $phone,           // Teléfono limpio
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Si hay contactos para insertar, los agregamos a la base de datos
            if (count($contactsToInsert) > 0) {
                Contact::insert($contactsToInsert);
                // Log para seguimiento
                Log::info("Contactos importados: " . json_encode($contactsToInsert));
                return redirect()->route('whatsapp.index')->with('success', __('Contacts imported successfully.'));
            } else {
                Log::info("No contactos nuevos para importar.");
                return redirect()->route('whatsapp.index')->with('success', __('No new contacts were imported.'));
            }
        } else {
            // En caso de que la llamada a la API falle
            return redirect()->route('whatsapp.index')->with('error', __('Failed to fetch contacts from API.'));
        }
    }
}
