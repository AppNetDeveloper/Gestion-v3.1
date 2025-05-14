<?php

namespace App\Mail;

use App\Models\Quote; // Importar el modelo Quote
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address; // Para definir remitente/destinatario
use Illuminate\Mail\Mailables\Attachment; // Para adjuntar el PDF
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteSentMail extends Mailable
{
    use Queueable, SerializesModels;

    // Propiedades públicas para pasar datos a la vista
    public Quote $quote;
    protected $pdfData; // Datos binarios del PDF

    /**
     * Create a new message instance.
     *
     * @param \App\Models\Quote $quote El objeto Quote a enviar.
     * @param string|null $pdfData Los datos binarios del PDF a adjuntar (opcional).
     * @return void
     */
    public function __construct(Quote $quote, $pdfData = null)
    {
        $this->quote = $quote;
        $this->pdfData = $pdfData;
    }

    /**
     * Get the message envelope.
     * Define el remitente y el asunto.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope(): Envelope
    {
        // Puedes obtener el email "from" y el nombre desde config o .env
        $fromEmail = config('mail.from.address', 'noreply@example.com');
        $fromName = config('mail.from.name', config('app.name'));

        return new Envelope(
            from: new Address($fromEmail, $fromName),
            subject: __('Quote') . ' #' . $this->quote->quote_number . ' - ' . config('app.name'), // Asunto del correo
        );
    }

    /**
     * Get the message content definition.
     * Define la vista Markdown a usar.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.quote_sent', // Usará resources/views/emails/quote_sent.blade.php
            // with: ['quote' => $this->quote], // Los datos públicos ya están disponibles en la vista
        );
    }

    /**
     * Get the attachments for the message.
     * Adjunta el PDF si se proporcionó.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        if ($this->pdfData) {
            $attachments[] = Attachment::fromData(fn () => $this->pdfData, 'Quote-'.$this->quote->quote_number.'.pdf')
                ->withMime('application/pdf');
        }
        return $attachments;
    }
}
