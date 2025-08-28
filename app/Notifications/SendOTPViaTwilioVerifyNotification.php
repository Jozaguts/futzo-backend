<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class SendOTPViaTwilioVerifyNotification extends Notification
{

    public function __construct(public string $phone, public string | int $code)
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
         $response =  $twilio->verify->v2->services(config('services.twilio.verify_sid'))
                ->verifications
                ->create(
                    $this->phone,
                    'sms',
                    ['customCode' => $this->code]
                );
         logger('response', [
             'data' => $response,
         ]);
        }catch(TwilioException $exception){
            logger('error',['message' => $exception->getMessage(), 'code' => $exception->getCode()]);
        }
    }
}
