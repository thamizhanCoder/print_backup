<?php

namespace App\Listeners;

use App\Events\ForgetPasswordCustomer;
use Illuminate\Support\Facades\Mail;


class SendResetEmailCustomer
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(ForgetPasswordCustomer $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.customerforget', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Reset Password');
            $message->to($user['email']);
        });
    }
}
