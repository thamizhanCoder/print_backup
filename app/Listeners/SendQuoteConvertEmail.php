<?php

namespace App\Listeners;

use App\Events\QuoteConvert;
use Illuminate\Support\Facades\Mail;

class SendQuoteConvertEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(QuoteConvert $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendquoteconvert', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Quote Created');
            $message->to($user['email']);
        });
    }
}
