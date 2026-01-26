<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketCollection;
use App\Models\SupportMessage;
use App\Models\Ticket;
use App\Notifications\NewSupportTicketNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\CreateTicketRequest;
use Illuminate\Support\Facades\Notification;

class SupportController extends Controller
{


    public function ticket(CreateTicketRequest  $request): JsonResponse
    {
        $input = $request->validated();
        $user = $request->user();
        $contactMethod = $user->contact_method;
        $contactValue = $user->contact_method ==='email' ? $user->email : $user->phone;

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'subject' => $input['subject'],
            'tournament_id' => $input['tournament_id'] ?? null,
            'league_id' => $user->league->id,
            'category' => $input['subject'],
            'contact_method' => $contactMethod,
            'contact_value' => $contactValue,
        ]);

        $message = $input['message'];

        SupportMessage::create([
           'ticket_id' => $ticket->id,
            'author_type' => 'user',
            'author_user_id' => $user->id,
            'body' => $message,
        ]);


        Notification::route('mail', 'soporte@futzo.io')
            ->notify(new NewSupportTicketNotification($ticket, $message));

        return response()->json([
            'message' => 'Ticket created successfully',
            'ticket_id' => $ticket->id,
        ], 201);

    }
    public function list(Request $request): TicketCollection
    {
        return TicketCollection::make(
            Ticket::where('user_id', $request->user()->id)
                ->where('status', 'open')
                ->with('publicMessages')
                ->get()
        );


    }
    public function message(Request $request)
    {

    }
}
