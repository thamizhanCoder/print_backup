<?php

namespace App\Listeners;

use App\Events\EnquiryRevokeEmployee;
use Illuminate\Support\Facades\Mail;


class SendEnquiryRevokeEmployee
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(EnquiryRevokeEmployee $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendenquiryrevokeemployee', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Enquiry Revoked');
            $message->to($user['email']);
        });
    }
}
