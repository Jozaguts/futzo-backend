<?php

namespace App\Http\Resources;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Ticket */ class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'league_id' => $this->league_id,
            'tournament_id' => $this->tournament_id,
            'contact_method' => $this->contact_method,
            'contact_value' => $this->contact_value,
            'subject' => $this->subject,
            'category' => $this->category,
            'status' => $this->status,
            'priority' => $this->priority,
            'last_message_at' => $this->last_message_at,
            'closed_at' => $this->closed_at,
            'meta' => $this->meta,
            'created_at' => $this->created_at->translatedFormat('j M Y') . ' a las ' .$this->created_at->format('H:i'),
            'user_id' => $this->user_id,
            'public_messages' => $this->whenLoaded('publicMessages')->map(function ($message) {
                return [
                    'id' => $message->id,
                    'author_type' => $message->author_type,
                    'author_user_id' => $message->author_user_id,
                    'body' => $message->body,
                    'created_at' => whatsappTimestamp($message->created_at),
                ];
            }),
            'messages_count' => $this->whenLoaded('publicMessages')->count()
        ];
    }
}
