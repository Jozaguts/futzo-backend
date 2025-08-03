<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class SendOTPViaTwilioVerifyNotification extends Notification
{

    public function __construct(public string $phone)
    {
    }

    public function via($notifiable): array
    {
        return ['twilio_verify'];
    }


    public function toTwilioVerify($notifiable): void
    {
        $twilio = new Client(config('services.twilio.sid'),config('services.twilio.token'));
        try {
            $twilio->verify->v2->services(config('services.twilio.verify_sid'))
                ->verifications
                ->create($this->phone,'sms');
        }catch(TwilioException $exception){
            logger('error',['message' => $exception->getMessage(), 'code' => $exception->getCode()]);
        }
    }
}
