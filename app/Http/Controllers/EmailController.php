<?php

namespace App\Http\Controllers;

use App\Services\ImapService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\EmailSenderService;
use App\Models\User;

class EmailController extends Controller
{
    protected ImapService $imapService;
    protected EmailSenderService $emailSender;

    public function __construct(ImapService $imapService, EmailSenderService $emailSender)
    {
        $this->imapService = $imapService;
        $this->emailSender = $emailSender;

        // Se utiliza un middleware anónimo para conectar al IMAP una vez autenticado el usuario.
        $this->middleware(function ($request, $next) {
            $this->imapService->connect();
            return $next($request);
        });
    }

    /**
     * Muestra el listado de correos junto con el mensaje de error, en caso de existir.
     *
     * @return View
     */

     public function index(Request $request): View
     {
         $folder = $request->input('folder', 'INBOX');
         $perPage = 20; // Este es el número de mensajes por página
         $page = $request->input('page', 1);

         // Obtener todos los mensajes (sin límite)
         $allMessages = $this->imapService->getOnlyMessages($folder);

         // Ordenar los mensajes por fecha (del más reciente al más antiguo)
         $allMessages = collect($allMessages)->sortByDesc(function($mail) {
             return strtotime($mail->getDate()); // Ordena por fecha de recepción
         });

         // Aplicar la paginación
         $currentPageItems = $allMessages->forPage($page, $perPage);

         // Crear el paginador
         $messages = new LengthAwarePaginator(
             $currentPageItems,
             $allMessages->count(),
             $perPage,
             $page,
             [
                 'path' => $request->url(),
                 'query' => $request->query(),
             ]
         );

         // Obtener carpetas y errores
         $folders = $this->imapService->getFolders();
         $error   = $this->imapService->getError();

         // Si es una solicitud AJAX, solo devolver la lista de correos
         if ($request->ajax()) {
             return view('email.partials.email-list', compact('messages', 'folder'));
         }

         // Para el caso no AJAX, devolver la vista completa
         return view('email.index', compact('messages', 'error', 'folder', 'folders'));
     }



    public function indexAll(Request $request): View
    {
        $folder = $request->input('folder', 'INBOX');
        $perPage = 20;
        $page = $request->input('page', 1);

        // Obtén un conjunto de mensajes, pero solo los encabezados (limita la carga)
        $allMessages = $this->imapService->getMessages(1000, $folder); // Obtener hasta 1000 mensajes (solo encabezados)
        $allMessages = $allMessages->sortByDesc(function($mail) {
            return strtotime((string)$mail->getDate());
        });

        $currentPageItems = $allMessages->forPage($page, $perPage);

        $messages = new LengthAwarePaginator(
            $currentPageItems,
            $allMessages->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $folders = $this->imapService->getFolders();
        $error   = $this->imapService->getError();

        // Si la solicitud es AJAX, solo devolver la lista de correos (sin cuerpo completo)
        if ($request->ajax()) {
            return view('email.partials.email-list', compact('messages', 'folder'));
        }

        // Para el caso no AJAX, devolver la vista completa
        return view('email.index', compact('messages', 'error', 'folder', 'folders'));
    }


    /**
     * Muestra el detalle de un correo específico.
     *
     * @param int $uid UID del mensaje
     * @return View
     */
    public function show(Request $request, int $uid)
    {
        $folder  = $request->input('folder', 'INBOX');
        $message = $this->imapService->getMessageByUid($uid, $folder);

        // Marcar el mensaje como leído
        if ($message) {
            try {
                $message->setFlag('Seen');
            } catch (\Throwable $e) {
                \Log::error("IMAP: Error al marcar el mensaje como leído: " . $e->getMessage());
            }
        }

        // Comprobar si la solicitud es AJAX
        if ($request->ajax()) {
            // Cargar solo el detalle del mensaje (asunto, remitente, cuerpo)
            $html = view('email.partials.message-detail', compact('message'))->render();
            return response()->json(['html' => $html]);
        }

        // Para el caso no AJAX (si se accede directamente al detalle)
        $folders = $this->imapService->getFolders();
        $error   = $this->imapService->getError();

        // Retornar la vista completa, pero no recargar todos los correos.
        return view('email.index', compact('message', 'folders', 'error', 'folder'));
    }


    /**
     * Actualiza la configuración IMAP del usuario autenticado.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'imap_host'       => 'required|string',
            'imap_port'       => 'required|integer',
            'imap_encryption' => 'nullable|string',
            'imap_username'   => 'required|string',
            'imap_password'   => 'required|string',
        ]);

        $user = Auth::user();
        if ($user) {
            $user->update($data);
        }

        return redirect()->back()->with('success', 'Configuración IMAP actualizada correctamente.');
    }

    public function downloadAttachment($messageUid, $attachmentIndex)
    {
        // Recupera el mensaje usando el UID
        $message = $this->imapService->getMessageByUid((int)$messageUid);
        if (!$message) {
            abort(404, 'Mensaje no encontrado');
        }

        // Obtén todos los adjuntos del mensaje
        $attachments = $message->getAttachments();
        if (!isset($attachments[$attachmentIndex])) {
            abort(404, 'Adjunto no encontrado');
        }

        $attachment = $attachments[$attachmentIndex];

        // Obtén el contenido y el nombre del archivo
        $content  = $attachment->getContent();
        $filename = $attachment->getName();

        // Devuelve el archivo para descarga
        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename);
    }

    public function updateSmtpSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'smtp_host'       => 'required|string',
            'smtp_port'       => 'required|integer',
            'smtp_encryption' => 'nullable|string',
            'smtp_username'   => 'required|string',
            'smtp_password'   => 'required|string',
        ]);

        $user = Auth::user();
        if ($user) {
            $user->update($data);
        }

        return redirect()->back()->with('success', 'Configuración SMTP actualizada correctamente.');
    }

    /**
     * Muestra el formulario para componer un correo.
     */
    public function compose()
    {
        return view('emails.compose');
    }

    /**
     * Procesa el envío del correo desde la vista.
     */
    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'to'      => 'required|email',
            'subject' => 'required|string',
            'content' => 'required|string',
        ]);

        // Datos adicionales opcionales, por ejemplo para un botón de acción.
        $extra = $request->only('action_url');

        // Usamos el usuario autenticado como remitente si existe.
        /**
         * @return \Illuminate\Contracts\Auth\Authenticatable|null
         */
        $sender = auth()->user();

        $this->emailSender->send($sender, $data['to'], $data['subject'], $data['content'], $extra);

        return redirect()->back()->with('success', 'Correo enviado correctamente.');
    }

    public function reply(Request $request, int $uid): RedirectResponse
    {
        $data = $request->validate([
            'to'      => 'required|email',
            'subject' => 'required|string',
            'content' => 'required|string',
        ]);

        // En este caso, usamos el usuario autenticado como remitente,
        // aunque si se desea se podría permitir otro comportamiento.
        /**
         * @return \Illuminate\Contracts\Auth\Authenticatable|null
         */
        $sender = auth()->user();

        $this->emailSender->send($sender, $data['to'], $data['subject'], $data['content']);

        return redirect()->back()->with('success', 'Respuesta enviada correctamente.');
    }

    public function delete(Request $request, int $uid): RedirectResponse
    {
        $folder = $request->input('folder', 'INBOX');

        $result = $this->imapService->deleteMessageByUid($uid, $folder);

        if ($result) {
            return redirect()->back()->with('success', 'Correo borrado correctamente.');
        } else {
            return redirect()->back()->with('error', 'No se pudo borrar el correo.');
        }
    }

}
