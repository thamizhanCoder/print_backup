<?php

namespace App\Listeners;

use App\Events\OrderCreateFromAdmin;
use Illuminate\Support\Facades\Mail;

class SendOrderCreateAdminEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(OrderCreateFromAdmin $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendordercreateadmin', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Order Created');
            $message->to($user['email']);
        });
    }
}
