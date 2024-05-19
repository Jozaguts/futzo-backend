<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailWithToken extends Notification
{
    use Queueable;
    protected string $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = env('FRONTEND_URL') . '/verify-email?token=' . $this->token;
        return (new MailMessage)
                    ->line('Por favor pulsa el siguiente botón para confirmar tu correo electrónico.')
                    ->action('Confirmar correo electrónico', $url)
                    ->line('Si no has creado ninguna cuenta, puedes ignorar o eliminar este e-mail.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
