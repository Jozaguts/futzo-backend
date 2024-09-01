<?php

namespace App\Listeners;

use App\Events\RegisteredTeamCoach;
use App\Notifications\SendRegisteredTeamCoach;

class SendRegisteredTeamCoachNotification
{
    /**
     * Create the event listener.
     */
    public function __construct(){}

    /**
     * Handle the event.
     */
    public function handle(RegisteredTeamCoach $event): void
    {
        $event->user->notify(new SendRegisteredTeamCoach($event->temporaryPassword));
    }
}
