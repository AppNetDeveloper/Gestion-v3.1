<?php

namespace App\Mail;

use App\Models\User; // Importar el modelo User
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewUserWelcomeMail extends Mailable implements ShouldQueue // Implementar ShouldQueue para enviar en segundo plano
{
    use Queueable, SerializesModels;

    public User $user; // Usuario recién creado
    public string $plainPassword; // Contraseña en texto plano (solo para este email)

    /**
     * Create a new message instance.
     *
     * @param \App\Models\User $user
     * @param string $plainPassword
     * @return void
     */
    public function __construct(User $user, string $plainPassword)
    {
        $this->user = $user;
        $this->plainPassword = $plainPassword;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope(): Envelope
    {
        $fromEmail = config('mail.from.address', 'noreply@example.com');
        $fromName = config('mail.from.name', config('app.name'));

        return new Envelope(
            from: new Address($fromEmail, $fromName),
            subject: __('Welcome to :app_name!', ['app_name' => config('app.name')]), // Asunto del correo
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.new_user_welcome', // Usará resources/views/emails/new_user_welcome.blade.php
            with: [ // Datos que se pasan a la vista Blade del email
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'password' => $this->plainPassword,
                'loginUrl' => route('login'), // Asume que tienes una ruta llamada 'login'
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return []; // No adjuntamos nada en este email de bienvenida
    }
}
