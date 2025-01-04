<?php

namespace App\Broadcasting;

use GuzzleHttp\Client;
use Illuminate\Notifications\Notification;

class WhatsAppChannel
{
	protected Client $httpClient;

	public function __construct()
	{
		$this->httpClient = new Client();
	}

	public function send($notifiable, Notification $notification)
	{
		$phoneNumber = $notifiable->routeNotificationFor('whatsapp');
		$message = $notification->toWhatsApp($notifiable);
		$url = "https://graph.facebook.com/v21.0/522368584287373/messages";
		$accessToken = env('WHATSAPP_TOKEN');
		$data = [
			"messaging_product" => "whatsapp",
			"recipient_type" => "individual",
			"to" => $phoneNumber,
			"type" => "template",
			"template" => [
				"name" => "verify_code_1", // Nombre de tu plantilla
				"language" => [
					"code" => "es"
				],
				"components" => [
					[
						"type" => "body",
						"parameters" => [
							[
								"type" => "text",
								"text" => $message['otp'] // El código OTP que deseas enviar
							]
						]
					],
					[
						"type" => "button",
						"sub_type" => "url",
						"index" => "0",
						"parameters" => [
							[
								"type" => "text",
								"text" => $message['button_text']
							]
						]
					]
				]
			]
		];
		$this->httpClient->post($url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $accessToken,
				'Content-Type' => 'application/json',
			],
			'json' => $data,
		]);

	}
}
