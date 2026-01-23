<?php

namespace App\Http\Controllers;

use App\Mail\SupportMessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SupportController extends Controller
{
    public function message(Request $request)
    {
        $message = $request->input('message');
        Mail::to($request->user())
            ->cc($moreUsers)
            ->bcc($evenMoreUsers)
            ->queue(new SupportMessageSent($message));
    }
}
