<?php

namespace App\Notifications;

use App\Broadcasting\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SendOTPNotification extends Notification implements ShouldQueue
{
	use Queueable;

	protected int $otp;
	protected string $buttonText;

	public function __construct($otp, $buttonText)
	{
		$this->otp = $otp;
		$this->buttonText = $buttonText;
	}

	public function via($notifiable): array
	{
		return [WhatsAppChannel::class];
	}

	public function toWhatsApp($notifiable): array
	{
		return [
			'otp' => $this->otp,
			'button_text' => $this->buttonText,
		];
	}
}
