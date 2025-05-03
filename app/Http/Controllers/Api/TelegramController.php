<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TelegramMessage;
use App\Models\Contact; // Asegúrate de importar el modelo de contactos
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\AutoProcess; // Asegúrate de tener este modelo si no existe, créalo

class TelegramController extends Controller
{
    /**
     * Almacena un mensaje y, además, si el campo "peer" (o "chatPeer") no existe en la tabla de contactos,
     * lo crea usando el valor recibido. (Se normaliza el teléfono quitando el signo +).
     */
    public function store(Request $request)
    {
        // Si no viene 'peer' y sí viene 'chatPeer', se asigna a 'peer'
        if (!$request->has('peer') && $request->has('chatPeer')) {
            $request->merge(['peer' => $request->input('chatPeer')]);
        }

        // Obtener el token del header (como ya tienes implementado)
        $token = null;
        if ($request->hasHeader('token')) {
            $token = $request->header('token');
        } elseif ($request->hasHeader('Authorization')) {
            $authHeader = $request->header('Authorization');
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];
            } else {
                $token = $authHeader;
            }
        } elseif ($request->hasHeader('x-api-key')) {
            $token = $request->header('x-api-key');
        }

        if (!$token || $token !== env('TELEGRAM_API_TOKEN')) {
            //log de token invalido
            Log::info('Token inválido en la api de telegram');
            return response()->json(['error' => 'Token inválido'], 401);
        }



        // Validar los datos recibidos
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string',
            'date'    => 'required|integer',
            'peer'    => 'required|string', // Aquí se usa "peer"
            'status'  => 'required|string|in:sent,received',
            'image'   => 'nullable|string'
        ]);

        // Buscar en la tabla de contactos si ya existe un registro para este peer.
        // Se normaliza el teléfono quitando el signo "+"
        $peer = $data['peer'];
        // Si no se encuentra en la base, se realiza la llamada a la API externa para buscar el contacto.
        $externalUrl = env('TELEGRAM_URL') . '/search-contact/' . $data['user_id'] . '?peer=' . urlencode($peer);

        $externalResponse = Http::timeout(90)           // hasta 60 segundos para recibir datos
                                ->connectTimeout(100)    // hasta 10 segundos para conectar
                                ->get($externalUrl);


         $externalData = $externalResponse->json();

        if (isset($externalData['success']) && $externalData['success'] === true && !empty($externalData['contacts'])) {
            $externalContact = $externalData['contacts'][0];
            $phoneNormalized = ltrim($externalContact['phone'] ?? '', '+');
        } else {
            $phoneNormalized = ltrim($data['peer'], '+');
        }



        $contact = Contact::where('user_id', $data['user_id'])
            ->where(function ($query) use ($peer, $phoneNormalized) {
                $query->where('telegram', $peer)
                      ->orWhere('phone', $phoneNormalized);
            })->first();

            if (!$contact) {

                if (isset($externalData['success']) && $externalData['success'] === true && !empty($externalData['contacts'])) {
                    $externalContact = $externalData['contacts'][0];
                    $firstName = $externalContact['first_name'] ?? 'Unknown';
                    $lastName  = $externalContact['last_name'] ?? '';
                    $phone     = ltrim($externalContact['phone'] ?? '', '+');
                } else {
                    // Si la API externa no retorna datos, usar valores por defecto.
                    $firstName = 'Unknown';
                    $lastName = '';
                    $phone = $phoneNormalized;
                }

                // Crear el contacto con los datos obtenidos (o por defecto)
                try {
                    // Crear el contacto con los datos obtenidos (o por defecto)
                    $contact = Contact::create([
                        'user_id'  => $data['user_id'],
                        'name'     => trim($firstName . ' ' . $lastName),
                        'phone'    => $phone,
                        'telegram' => $peer
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error al crear contacto: ' . $e->getMessage());
                }

            }

        // Crear el mensaje de Telegram
        $telegramMessage = TelegramMessage::create($data);


        // Verificar en auto_processes si hay opción de respuesta automática
        $autoProcess = AutoProcess::where('user_id', $data['user_id'])->first();
        if ($autoProcess && $autoProcess->telegram != 0) {
            // Opciones: 1 = texto automático, 2 = con IA, 3 = con ticket
            switch ($autoProcess->telegram) {
                case 1:
                    // Opción 1: Texto automático
                    // Se verifica si el último mensaje anterior del mismo chat es mayor a 30 minutos.
                    $previousMessage = TelegramMessage::where('user_id', $data['user_id'])
                        ->where('peer', $data['peer'])
                        ->where('id', '<>', $telegramMessage->id)
                        ->orderBy('date', 'desc')
                        ->first();

                    $shouldSendPrompt = false;
                    if ($previousMessage) {
                        $timeDiff = $telegramMessage->date - $previousMessage->date;
                        if ($timeDiff > 1800) { // 1800 segundos = 30 minutos
                            $shouldSendPrompt = true;
                        }
                    } else {
                        // Si no hay mensaje anterior, se envía el prompt
                        $shouldSendPrompt = true;
                    }

                    if ($shouldSendPrompt) {
                        Log::info('Sending auto text response');
                        Log::info('Auto text response: ' . $autoProcess->telegram_prompt);
                        $this->sendAutoTextResponse($autoProcess->telegram_prompt, $data['user_id'], $data['peer']);
                    }
                    break;
                case 2:
                    // Opción 2: Respuesta con IA (pendiente de implementación)
                    break;
                case 3:
                    // Opción 3: Respuesta con ticket (pendiente de implementación)
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $telegramMessage
        ], 201);
    }

    /**
     * Función para enviar la respuesta automática de texto.
     *
     * @param string $prompt Texto del mensaje automático
     * @param int $userId ID del usuario
     * @param string $peer Identificador del chat
     * @return void
     */
    private function sendAutoTextResponse($prompt, $userId, $peer)
    {

        if (!$prompt) {
            return;
        }

        $telegramUrl = env('TELEGRAM_URL');
        $response = Http::post("$telegramUrl/send-message/{$userId}/{$peer}", [
            'message' => $prompt,
        ]);
        Log::info('Response from Telegram API:', ['response' => $response->json()]);
    }
}
