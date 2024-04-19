<?php

namespace App\Listeners;

use App\Events\CancelOrderItems;
use Illuminate\Support\Facades\Mail;


class SendCancelOrderItemsEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(CancelOrderItems $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendorderitemcancel', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Cancel order item');
            $message->to($user['email']);
        });
    }
}
