<?php

namespace App\Broadcasting;

use GuzzleHttp\Client;
use Illuminate\Notifications\Notification;

class WhatsAppChannel
{
    protected Client $httpClient;
    protected string $accessToken;
    protected string $url;

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->accessToken = config('services.whatsapp.token');
        $this->url = config('services.whatsapp.url') . '/' . config('services.whatsapp.phone_id') . "/messages";
    }

    public function send($notifiable, Notification $notification)
    {
        $phoneNumber = $notifiable->routeNotificationFor('whatsapp');
        $message = $notification->toWhatsApp($notifiable);
        $data = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $phoneNumber,
            "type" => "template",
            'template' => [
                'name' => 'verify_code_es',
                'language' => ['code' => 'es'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $message['otp']]
                        ]
                    ],
                    [
                        "type" => "button",
                        "sub_type" => "url",
                        "index" => "0",
                        "parameters" => [
                            [
                                "type" => "text",
                                "text" => $message['text_button']
                            ]
                        ]
                    ]
                ]
            ],
        ];
        $this->httpClient->post($this->url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

    }
}
