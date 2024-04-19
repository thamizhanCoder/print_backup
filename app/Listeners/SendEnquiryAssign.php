<?php

namespace App\Listeners;

use App\Events\EnquiryAssign;
use Illuminate\Support\Facades\Mail;


class SendEnquiryAssign
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(EnquiryAssign $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.enquiryassign', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Enquiry Assigned');
            $message->to($user['email']);
        });
    }
}
