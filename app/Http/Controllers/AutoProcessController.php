<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AutoProcess;
use Illuminate\Support\Facades\Auth;

class AutoProcessController extends Controller
{
        /**
     * Get WhatsApp auto-response settings for the authenticated user.
     * (NEW METHOD)
     */
    public function getWhatsapp(Request $request)
    {
        $user = Auth::user();
         // Find the record or return a default structure if not found
        $autoProcess = AutoProcess::where('user_id', $user->id)->firstOr(function () use ($user) {
             // Return a default object/array so the JS doesn't break if no record exists
             return [
                'user_id' => $user->id,
                'whatsapp' => 0, // Default to disabled
                'whatsapp_prompt' => null
             ];
             // Or if AutoProcess model has fillable defaults:
             // return new AutoProcess(['user_id' => $user->id]);
        });

        // If using firstOr(), convert potential model instance to array if needed by JS
        if ($autoProcess instanceof AutoProcess) {
             $autoProcess = $autoProcess->toArray();
        }


        return response()->json([
            'success' => true,
            'data'    => $autoProcess // Send data (even if it's default)
        ]);
    }
    public function updateWhatsapp(Request $request)
    {
        // Validación: se requiere que 'whatsapp' tenga un valor de 0,1,2 o 3 y si es mayor que 0, 'whatsapp_prompt' es obligatorio.
        $data = $request->validate([
            'whatsapp'        => 'required|in:0,1,2,3',
            'whatsapp_prompt' => 'required_if:whatsapp,1,2,3'
        ]);

        $user = Auth::user();

        // Actualiza o crea el registro de configuración para el usuario
        AutoProcess::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuración actualizada correctamente.'
        ]);
    }
    public function updateTelegram(Request $request)
    {
        // Validación: se requiere que 'whatsapp' tenga un valor de 0,1,2 o 3 y si es mayor que 0, 'whatsapp_prompt' es obligatorio.
        $data = $request->validate([
            'telegram'        => 'required|in:0,1,2,3',
            'telegram_prompt' => 'required_if:telegram,1,2,3'
        ]);

        $user = Auth::user();

        // Actualiza o crea el registro de configuración para el usuario
        AutoProcess::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuración actualizada correctamente.'
        ]);
    }

    public function getTelegram(Request $request)
    {
        $user = Auth::user();
        $autoProcess = AutoProcess::where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'data'    => $autoProcess
        ]);
    }

}
