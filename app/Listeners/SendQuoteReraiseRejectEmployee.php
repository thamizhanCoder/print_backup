<?php

namespace App\Listeners;

use App\Events\QuoteReraiseRejectEmployee;
use Illuminate\Support\Facades\Mail;

class SendQuoteReraiseRejectEmployee
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(QuoteReraiseRejectEmployee $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendquotereraiserejectemployee', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Your quote reraised rejected');
            $message->to($user['email']);
        });
    }
}
