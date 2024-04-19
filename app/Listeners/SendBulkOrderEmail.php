<?php

namespace App\Listeners;

use App\Events\BulkOrderEnquiryEvent;
use Illuminate\Support\Facades\Mail;


class SendBulkOrderEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(BulkOrderEnquiryEvent $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.bulkorderenquiry', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->subject('Bulk Order Mail');
            $message->to($user['admin_email']);
        });
    }
}
