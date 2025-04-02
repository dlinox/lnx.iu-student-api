<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CreateAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $url;
    public function __construct($url)
    {
        $this->url = $url;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verificación de correo electrónico',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.CreateAccount',
            with: ['url' => $this->url],

        );
    }

    public function attachments(): array
    {
        return [];
    }
}
