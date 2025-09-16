<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly User $user)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $fullName = trim(implode(' ', array_filter([
            $this->user->name,
            $this->user->last_name,
        ])));
        $registeredAt = $this->user->created_at?->timezone(config('app.timezone'))?->format('Y-m-d H:i:s') ?? 'No disponible';

        return new MailMessage()
            ->subject('Nuevo usuario registrado')
            ->greeting('Hola,')
            ->line('Se ha registrado un nuevo usuario en la plataforma.')
            ->line('Nombre: ' . ($fullName !== '' ? $fullName : 'Sin nombre'))
            ->line('Correo electrónico: ' . ($this->user->email ?? 'No proporcionado'))
            ->line('Teléfono: ' . ($this->user->phone ?? 'No proporcionado'))
            ->line('ID de usuario: ' . $this->user->id)
            ->line('Fecha de registro: ' . $registeredAt);
    }
}
