<?php

namespace App\Listeners;

use App\Events\SendApproved;
use Illuminate\Support\Facades\Mail;


class SendApprovedEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(SendApproved $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendapproved', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Order Approved');
            $message->to($user['email']);
        });
    }
}
