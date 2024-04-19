<?php

namespace App\Listeners;

use App\Events\SendRevoke;
use Illuminate\Support\Facades\Mail;


class SendRevokeEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(SendRevoke $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendrevoke', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Order Revoked');
            $message->to('kamesh@technogenesis.in');
        });
    }
}
