<?php

namespace App\Listeners;

use App\Events\ContestApply;
use Illuminate\Support\Facades\Mail;


class SendContestApplyEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(ContestApply $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendcontestapply', ['user' => $user], function ($message) use ($user) {
            $message->subject('Thanks for participating the contest');
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->to($user['email']);
        });
    }
}
