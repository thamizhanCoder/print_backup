<?php

namespace App\Listeners;

use App\Events\Register;
use Illuminate\Support\Facades\Mail;


class SendRegisterEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(Register $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.newregister', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Welcome New User For Printapp');
            $message->to($user['email']);
        });
    }
}
