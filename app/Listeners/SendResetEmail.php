<?php

namespace App\Listeners;

use App\Events\ForgetPassword;
use Illuminate\Support\Facades\Mail;


class SendResetEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(ForgetPassword $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.forget', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Reset Password');
            $message->to($user['email']);
        });
    }
}
