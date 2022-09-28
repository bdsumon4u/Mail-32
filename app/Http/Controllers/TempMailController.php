<?php

namespace App\Http\Controllers;

use App\Models\EmailAccountMessage;
use Illuminate\Http\Request;

class TempMailController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $email = 'alexharisont20@gmail.com';

        return view('mail.temp-mail', [
            'messages' => EmailAccountMessage::query()
                ->with(['from', 'to', 'cc', 'bcc', 'replyTo', 'sender'])
                // ->whereHas('to', fn ($query) => $query->where('address', 'alexharisont20+krow@gmail.com'))
                // ->whereHas('to', fn ($query) => $query->where('address', 'bradlriordan+work@gmail.com'))
                // ->where('email_account_id', 1)
                ->inRandomOrder()
                // ->take(15)
                // ->take(5)
                ->get()
                ->dd(),
        ]);
    }
}
