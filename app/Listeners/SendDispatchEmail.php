<?php

namespace App\Listeners;

use App\Events\SendEmail;
use Illuminate\Support\Facades\Mail;


class SendDispatchEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(SendEmail $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.dispatch', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Courier Mail');
            $message->to($user['email']);
        });
    }
}
