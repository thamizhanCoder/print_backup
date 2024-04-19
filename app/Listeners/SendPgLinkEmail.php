<?php

namespace App\Listeners;

use App\Events\SendPgLink;
use Illuminate\Support\Facades\Mail;


class SendPgLinkEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(SendPgLink $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendpglink', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('PG Link for Order Payment');
            $message->to($user['email']);
        });
    }
}
