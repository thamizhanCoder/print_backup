<?php

namespace App\Listeners;

use App\Events\QuoteReraiseApprovalEmployee;
use Illuminate\Support\Facades\Mail;

class SendQuoteReraiseApprovalEmployee
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(QuoteReraiseApprovalEmployee $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendquotereraiseapprovalemployee', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Your quote reraised approved');
            $message->to($user['email']);
        });
    }
}
