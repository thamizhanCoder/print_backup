<?php

namespace App\Listeners;

use App\Events\SendDisapproved;
use Illuminate\Support\Facades\Mail;


class SendDisapprovedEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(SendDisapproved $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.senddisapproved', ['user' => $user], function ($message) use ($user) {
            $message->subject('Order Disapproved');
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->to($user['email']);
        });
    }
}
