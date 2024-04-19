<?php

namespace App\Listeners;

use App\Events\SendContactUs;
use Illuminate\Support\Facades\Mail;


class SendContactEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(SendContactUs $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendcontactus', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Send Contact us');
            $message->to('kamesh@technogenesis.in');
        });
    }
}
