<?php

namespace App\Listeners;

use App\Events\RegisteredTeamPresident;
use App\Notifications\SendRegisteredTeamPresident;

class SendRegisteredTeamPresidentNotification
{
    /**
     * Create the event listener.
     */
    public function __construct(){}

    /**
     * Handle the event.
     */
    public function handle(RegisteredTeamPresident $event): void
    {
       $event->user->notify(new SendRegisteredTeamPresident($event->temporaryPassword));
    }


}
