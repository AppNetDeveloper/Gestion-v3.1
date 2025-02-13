<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Auth;

class WhatsappController extends Controller
{
    /**
     * Muestra el listado de teléfonos para el usuario logueado.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Obtener los números de teléfono únicos asociados al usuario logueado
        $phones = WhatsappMessage::where('user_id', $userId)
            ->distinct()
            ->pluck('phone');

        // Si se recibe un query parameter "phone", se pueden cargar los mensajes de ese teléfono.
        $selectedPhone = $request->query('phone');
        $messages = [];
        if ($selectedPhone) {
            $messages = WhatsappMessage::where('user_id', $userId)
                ->where('phone', $selectedPhone)
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return view('whatsapp.index', compact('phones', 'selectedPhone', 'messages'));
    }

    /**
     * Muestra la conversación para el teléfono seleccionado.
     */
    public function conversation($phone)
    {
        $userId = Auth::id();

        // Obtener los números de teléfono únicos asociados al usuario logueado
        $phones = WhatsappMessage::where('user_id', $userId)
            ->distinct()
            ->pluck('phone');

        // Obtener los mensajes correspondientes al teléfono seleccionado, ordenados por fecha ascendente
        $messages = WhatsappMessage::where('user_id', $userId)
            ->where('phone', $phone)
            ->orderBy('created_at', 'asc')
            ->get();

        return view('whatsapp.index', [
            'phones'        => $phones,
            'selectedPhone' => $phone,
            'messages'      => $messages,
        ]);
    }
     /**
     * Elimina un mensaje individual.
     */
    public function destroyMessage($id)
    {
        $message = WhatsappMessage::findOrFail($id);

        // Verificar que el mensaje pertenezca al usuario autenticado
        if ($message->user_id != Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $message->delete();

        return redirect()->back()->with('success', 'Message deleted successfully.');
    }

    /**
     * Elimina todos los mensajes para un contacto (teléfono).
     */
    public function destroyChat($phone)
    {
        $userId = Auth::id();

        $deletedCount = WhatsappMessage::where('user_id', $userId)
                        ->where('phone', $phone)
                        ->delete();

        return redirect()->back()->with('success', "Deleted $deletedCount messages for contact $phone.");
    }
}
