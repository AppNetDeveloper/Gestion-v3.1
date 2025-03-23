<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TelegramMessage;
use App\Models\Contact; // Asegúrate de importar el modelo de contactos
use Illuminate\Support\Facades\Http;
//anadimos use log
use Illuminate\Support\Facades\Log;

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
         $externalResponse = Http::get($externalUrl);

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

        return response()->json([
            'success' => true,
            'data'    => $telegramMessage
        ], 201);
    }
}
