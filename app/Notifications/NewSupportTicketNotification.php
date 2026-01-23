<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSupportTicketNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(
        private readonly Ticket $ticket,
        private readonly string $firstMessageBody,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {

        $user = $this->ticket->requester;

        $subject = sprintf(
            'Futzo %s • %s • Ticket %s',
            strtoupper($this->ticket->category),
            strtoupper($this->ticket->status),
            $this->ticket->id->toString()->truncate(8) . '...',
        );
        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Nuevo ticket de soporte')
            ->line("Ticket: {$this->ticket->id}")
            ->line("Categoría: {$this->ticket->category}")
            ->line("Liga ID: {$this->ticket->league_id}")
            ->line("Torneo ID: " . ($this->ticket->tournament_id ?? 'N/A'))
            ->line("Usuario: {$user->name} ({$user->email})")
            ->line("Preferencia de contacto: {$user->contact_method}")
            ->line("Dato de contacto: " . ($user->contact_method === 'email' ? $user->email : $user->phone))
            ->line('Mensaje:')
            ->line($this->firstMessageBody);

            if ($user->contact_method === 'email') {
                $mail->cc($user->email);
            }

            return $mail;
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
