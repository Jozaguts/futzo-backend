<?php

namespace App\Notifications\Channels;

class TwilioVerifyChannel
{
    public function send($notifiable, $notification)
    {
        if(!method_exists($notification, 'toTwilioVerify')){
           return false;
        }

        return $notification->toTwilioVerify($notifiable);
    }
}