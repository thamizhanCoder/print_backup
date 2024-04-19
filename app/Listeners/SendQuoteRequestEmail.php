<?php

namespace App\Listeners;

use App\Events\QuoteRequestEmployee;
use Illuminate\Support\Facades\Mail;

class SendQuoteRequestEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(QuoteRequestEmployee $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendquoterequest', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Request for reraising quote');
            $message->to($user['email']);
        });
    }
}
