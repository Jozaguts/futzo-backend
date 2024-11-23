<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendRegisteredTeamCoach extends Notification implements ShouldQueue
{
	use Queueable;

	/**
	 * Create a new notification instance.
	 */
	public function __construct(public string $temporaryPassword)
	{
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
		return (new MailMessage)
			->subject('Bienvenido como Entrenador de Equipo en Futzo')
			->greeting('Hola ' . $notifiable->name . ',')
			->line('Has sido registrado como Entrenador de Equipo en nuestro sistema.')
			->line('Para iniciar sesión, utiliza tu correo electrónico y la siguiente contraseña temporal:')
			->line('Correo Electrónico: ' . $notifiable->email)
			->line('Contraseña: ' . $this->temporaryPassword)
			->action('Iniciar Sesión', url(env('FRONTEND_URL') . "/login?email={$notifiable->email}"))
			->line('Te recomendamos cambiar tu contraseña al iniciar sesión.');
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
