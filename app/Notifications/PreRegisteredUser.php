<?php

namespace App\Notifications;

use App\Models\PreRegister;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class PreRegisteredUser extends Notification
{
    use Queueable;
    public $locale = 'es';
     private PreRegister $user;

    /**
     * Create a new notification instance.
     */
    public function __construct(PreRegister $preRegister)
    {
        $this->user = $preRegister;
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
        $couponHtml = "<div>
                            <p style='font-size: 24px'><strong>{$this->user->coupon->string}</strong>
                            <br>
                             <small style='font-size: 12px;'>*Descuento valido hasta {$this->user->coupon->end_date->format('d-m-Y')}</small>
                             </p>
                       </div>";
        $salutation = "<p>Saludos, <br> El equipo de Futzo</p>";
        $message = new  MailMessage();
        return $message
            ->subject('¡Gracias por Pre-Registrarte en Futzo!')
            ->greeting('¡Nos encanta tenerte con nosotros!')
            ->line('Por ser parte de los primeros 100 en unirte, queremos agradecerte con un código de descuento exclusivo que podrás utilizar en el lanzamiento:')
            ->line(new HtmlString($couponHtml))
            ->line('Gracias por confiar en Futzo.')
            ->salutation(new HtmlString($salutation));
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
