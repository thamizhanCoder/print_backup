<?php

namespace App\Listeners;

use App\Events\CancelOrder;
use Illuminate\Support\Facades\Mail;


class SendCancelOrderEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(CancelOrder $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.cancelorder', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Cancel order');
            $message->to($user['email']);
        });
    }
}
