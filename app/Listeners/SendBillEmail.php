<?php

namespace App\Listeners;

use App\Events\SendBillNumber;
use Illuminate\Support\Facades\Mail;


class SendBillEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(SendBillNumber $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.billno', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Waiting for dispatch');
            $message->to($user['email']);
        });
    }
}
