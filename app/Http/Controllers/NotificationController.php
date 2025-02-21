<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Muestra la vista completa de notificaciones.
     */
    public function index()
    {
        return view('notifications.index');
    }

    /**
     * Retorna los datos de las notificaciones para DataTables en formato JSON.
     */
    public function data(Request $request)
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $notifications]);
    }

    /**
     * Marca todas las notificaciones sin ver (seen = 0) del usuario autenticado como vistas (seen = 1).
     */
    public function markAsSeen(Request $request)
    {
        Notification::where('user_id', Auth::id())
            ->where('seen', 0)
            ->update(['seen' => 1]);

        return response()->json(['success' => true]);
    }
}
