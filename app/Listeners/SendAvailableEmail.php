<?php

namespace App\Listeners;

use App\Events\SendAvailable;
use Illuminate\Support\Facades\Mail;


class SendAvailableEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(SendAvailable $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.available', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Notification Mail');
            $message->to($user['email']);
        });
    }
}
