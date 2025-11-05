<?php

namespace App\Events;

use App\DTO\TournamentDTO;
use App\Models\Tournament;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TournamentCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Tournament $tournament
     * @param array $basicFields
     * */
    public function __construct(public Tournament $tournament, public array $basicFields)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name')
        ];
    }
}
