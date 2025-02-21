<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TelegramMessage;

class TelegramController extends Controller
{
// En tu método del controlador (por ejemplo, store)
    public function store(Request $request)
    {
        $token = null;

        // 1. Intentar obtener el token del header "token"
        if ($request->hasHeader('token')) {
            $token = $request->header('token');
        }
        // 2. Si no, revisar el header "Authorization"
        elseif ($request->hasHeader('Authorization')) {
            $authHeader = $request->header('Authorization');
            // Extraer token si está en formato "Bearer <token>"
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];
            } else {
                // Si no tiene el formato "Bearer", tomar todo el valor
                $token = $authHeader;
            }
        }
        // 3. Como opción adicional, revisar el header "x-api-key"
        elseif ($request->hasHeader('x-api-key')) {
            $token = $request->header('x-api-key');
        }

        // Agrega logs para ver qué valores se están recibiendo y comparando
        \Log::info('Token recibido: ' . $token);
        \Log::info('Token esperado: ' . env('TELEGRAM_API_TOKEN'));

        if (!$token || $token !== env('TELEGRAM_API_TOKEN')) {
            return response()->json(['error' => 'Token inválido'], 401);
        }

        // Resto del código...
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string',
            'date'    => 'required|integer',
            'peer'    => 'required|string', // o 'nullable|string' si decides permitir null
            'status'  => 'required|string|in:send,received',  // Actualiza a 'received'
            'image'   => 'nullable|string'
        ]);
        

        $telegramMessage = TelegramMessage::create($data);

        return response()->json([
            'success' => true,
            'data'    => $telegramMessage
        ], 201);
    }

}
