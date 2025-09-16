<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\NewUserRegisteredNotification;
use App\Notifications\SendOTPViaTwilioVerifyNotification;
use Illuminate\Support\Facades\DB;
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
            $token = random_int(1000, 9999);
            $user->verification_token = $token;
            $user->save();
            DB::table('phone_password_resets')->updateOrInsert(
                ['phone' => $phoneNumber],
                ['token' => $token, 'created_at' => now()]
            );
            $user->notify(new SendOTPViaTwilioVerifyNotification($phoneNumber, $token));
        }

        Notification::route('mail', 'sagit@futzo.io')
            ->notify(new NewUserRegisteredNotification($user));
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
