<?php

namespace App\Listeners;

use App\Events\EmployeeCreate;
use Illuminate\Support\Facades\Mail;


class SendEmployeeCreate
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(EmployeeCreate $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendemployeecreate', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Welcome to printapp!');
            $message->to($user['email']);
        });
    }
}
