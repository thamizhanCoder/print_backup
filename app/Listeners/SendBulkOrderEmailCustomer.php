<?php

namespace App\Listeners;

use App\Events\BulkOrderEnquiryCustomer;
use Illuminate\Support\Facades\Mail;


class SendBulkOrderEmailCustomer
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(BulkOrderEnquiryCustomer $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendbulkorderenquirycustomer', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Bulk Order Mail');
            $message->to($user['email']);
        });
    }
}
