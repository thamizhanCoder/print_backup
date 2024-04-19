<?php

namespace App\Listeners;

use App\Events\SuccessfullyDispatched;
use Illuminate\Support\Facades\Mail;


class SendDispatchedEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(SuccessfullyDispatched $event)
    {
        //send the welcome email to the user
        $user = $event->user;
        Mail::send('mail.dispatchorder', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Order Dispatched');
            $message->to($user['billing_email']);
        });
    }
}
