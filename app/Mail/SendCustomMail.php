<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendCustomMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectText;
    public $content;
    public $data;

    /**
     * Create a new message instance.
     *
     * @param string $subjectText
     * @param string $content
     * @param array $data
     */
    public function __construct(string $subjectText, string $content, array $data = [])
    {
        $this->subjectText = $subjectText;
        $this->content = $content;
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subjectText)
                   //->view('emails.custom') usar este en lugar de markdown si quieres eliminar el formateo automatico del blade
                    ->markdown('emails.custom')
                    ->with([
                        'subjectText' => $this->subjectText,
                        'content'     => $this->content,
                        'data'        => $this->data,
                    ]);
    }
}
