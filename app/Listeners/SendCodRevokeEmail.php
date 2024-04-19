<?php

namespace App\Listeners;

use App\Events\SendCodRevoke;
use Illuminate\Support\Facades\Mail;


class SendCodRevokeEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(SendCodRevoke $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendcodrevoke', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Cod Revoke');
            $message->to('kamesh@technogenesis.in');
        });
    }
}
