<?php

namespace App\Listeners;

use App\Events\SendCodDisapproved;
use Illuminate\Support\Facades\Mail;


class SendCodDisapprovedEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(SendCodDisapproved $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendcoddisapproved', ['user' => $user], function ($message) use ($user) {
            $message->subject('Order Dis-Approved');
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->to($user['email']);
        });
    }
}
