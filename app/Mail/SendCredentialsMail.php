<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;
    public function __construct($user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Credenciales de acceso',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.SendCredentials',
            with: ['user' => $this->user]

        );
    }

    public function attachments(): array
    {
        return [];
    }
}
