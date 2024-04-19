<?php

namespace App\Listeners;

use App\Events\SendCodApproved;
use Illuminate\Support\Facades\Mail;


class SendCodApprovedEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(SendCodApproved $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendcodapproved', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Order Approved');
            $message->to($user['email']);
        });
    }
}
