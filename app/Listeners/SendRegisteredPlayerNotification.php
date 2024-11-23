<?php

namespace App\Listeners;

use App\Events\RegisteredPlayer;
use App\Notifications\RegisteredPlayerNotification;

class SendRegisteredPlayerNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(RegisteredPlayer $event): void
    {
        $event->user->notify(new RegisteredPlayerNotification($event->temporaryPassword));
    }
}
