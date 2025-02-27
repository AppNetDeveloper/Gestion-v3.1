<?php

namespace App\Services;

use App\Mail\SendCustomMail;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class EmailSenderService
{
    /**
     * Envía un correo.
     *
     * @param User|null $sender     Usuario que envía el correo (opcional).
     * @param string    $to         Dirección destino.
     * @param string    $subject    Asunto del correo.
     * @param string    $content    Contenido del correo.
     * @param array     $data       Datos adicionales (por ejemplo, para un botón de acción).
     *
     * @return void
     */
    public function send(?User $sender, string $to, string $subject, string $content, array $data = []): void
    {
        if ($sender) {
            config([
                'mail.mailers.smtp.host'       => $sender->smtp_host,
                'mail.mailers.smtp.port'       => $sender->smtp_port,
                'mail.mailers.smtp.encryption' => $sender->smtp_encryption,
                'mail.mailers.smtp.username'   => $sender->smtp_username,
                'mail.mailers.smtp.password'   => $sender->smtp_password,
            ]);
        }

        $mailable = new SendCustomMail($subject, $content, $data);

        if ($sender) {
            $mailable->from($sender->email, $sender->name);
        }

        Mail::to($to)->send($mailable);
    }
}
