<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use Illuminate\Support\Facades\Mail;


class SendPlacedOrderEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(OrderPlaced $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.orderplace', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Order Placed');
            $message->to($user['email']);
        });
    }
}
