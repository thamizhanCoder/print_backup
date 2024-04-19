<?php

namespace App\Listeners;

use App\Events\DeliverySuccess;
use Illuminate\Support\Facades\Mail;


class SendDeliverySuccessEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(DeliverySuccess $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.deliverysuccess', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Delivery Success');
            $message->to($user['email']);
        });
    }
}
