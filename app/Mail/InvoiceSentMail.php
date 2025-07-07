<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment; // Para adjuntar el PDF
use Illuminate\Queue\SerializesModels;

class InvoiceSentMail extends Mailable implements ShouldQueue // Implementar ShouldQueue para enviar en segundo plano
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public array $companyData;
    public string $pdfPath; // Ruta al archivo PDF generado

    /**
     * Create a new message instance.
     *
     * @param \App\Models\Invoice $invoice
     * @param array $companyData
     * @param string $pdfPath
     * @return void
     */
    public function __construct(Invoice $invoice, array $companyData, string $pdfPath)
    {
        $this->invoice = $invoice;
        $this->companyData = $companyData;
        $this->pdfPath = $pdfPath;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Invoice from :companyName - #:invoiceNumber', [
                'companyName' => $this->companyData['name'] ?? config('app.name'),
                'invoiceNumber' => $this->invoice->invoice_number
            ]),
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
            markdown: 'emails.invoices.sent', // Usará la vista Markdown
            with: [
                'invoiceUrl' => route('invoices.show', $this->invoice->id), // O un enlace público si lo tienes
                'companyName' => $this->companyData['name'] ?? config('app.name'),
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
        return [
            Attachment::fromPath($this->pdfPath)
                ->as('invoice-' . $this->invoice->invoice_number . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
