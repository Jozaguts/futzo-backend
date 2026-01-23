<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportMessageSent extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public string $message, public mixed $ticketId) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('soporte@futzo.io', 'Soporte Futzo'),
            subject: 'Futzo Confirmación de contacto — #' . $this->ticketId,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.contact.support-message',
            with: [
                'isUserCopy' => true,
                'userName' => 'User name',
                'contactMethod' => 'email',
                'ticketId' => '2122',
                'createdAt' => now()->translatedFormat('l d M Y'),
                'userId' => '1111',
                'leagueName' => 'League name',
                'tournamentName' => 'Tournament name',
                'contactValue' => 'test@test.com',
                'pageUrl' => 'page url',
                'message' => $this->message,
                'adminUrl' => 'adminUrl'
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
