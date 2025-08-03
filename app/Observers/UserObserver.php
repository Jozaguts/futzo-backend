<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\SendOTPNotification;
use App\Notifications\SendOTPViaTwilioVerifyNotification;
use Illuminate\Support\Facades\Notification;
use Random\RandomException;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * @throws RandomException
     */
    public function created(User $user): void
    {
        if (!is_null($user->phone) && is_null($user->email)) {
            $phoneNumber = $user->phone;
            $user->notify(new SendOTPViaTwilioVerifyNotification($phoneNumber));
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
