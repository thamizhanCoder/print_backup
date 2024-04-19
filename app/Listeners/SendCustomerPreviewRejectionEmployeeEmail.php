<?php

namespace App\Listeners;

use App\Events\CustomerPreviewRejectionEmployee;
use Illuminate\Support\Facades\Mail;


class SendCustomerPreviewRejectionEmployeeEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(CustomerPreviewRejectionEmployee $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendcustomerpreviewrejectionemployee', ['user' => $user], function ($message) use ($user) {
            $message->subject('Order image is rejected');
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->to($user['email']);
        });
    }
}
