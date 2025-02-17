<?php

namespace App\Http\Controllers;

use App\Services\ImapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class EmailController extends Controller
{
    protected ImapService $imapService;

    public function __construct(ImapService $imapService)
    {
        $this->imapService = $imapService;

        // Se usa un middleware closure para ejecutar la conexión IMAP
        // una vez que el usuario ya esté autenticado.
        $this->middleware(function ($request, $next) {
            $this->imapService->connect();
            return $next($request);
        });
    }

    /**
     * Muestra el listado de correos junto con el mensaje de error (si lo hubiera).
     */
    public function index()
    {
        $messages = $this->imapService->getMessages(50);
        $error    = $this->imapService->getError(); // Método que retorna el error, si existe.
        return view('email.index', compact('messages', 'error'));
    }

    /**
     * Muestra el detalle de un correo seleccionado, junto con el listado y mensaje de error.
     *
     * @param int $uid UID del mensaje.
     */
    public function show($uid)
    {
        $message  = $this->imapService->getMessageByUid($uid);
        $messages = $this->imapService->getMessages(50);
        $error    = $this->imapService->getError();
        return view('email.index', compact('messages', 'message', 'error'));
    }

    /**
     * Actualiza la configuración IMAP del usuario.
     */
    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'imap_host'       => 'required|string',
            'imap_port'       => 'required|integer',
            'imap_encryption' => 'nullable|string',
            'imap_username'   => 'required|string',
            'imap_password'   => 'required|string',
        ]);

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user) {
            $user->update($data);
        }

        return redirect()->back()->with('success', 'Configuración IMAP actualizada correctamente.');
    }
}
