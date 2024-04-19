<?php

namespace App\Listeners;

use App\Events\CustomerPreviewApprovalEmployee;
use Illuminate\Support\Facades\Mail;


class SendCustomerPreviewApprovalEmployeeEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(CustomerPreviewApprovalEmployee $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.sendcustomerpreviewapprovalemployee', ['user' => $user], function ($message) use ($user) {
            $message->subject('Order image is approved');
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->to($user['email']);
        });
    }
}
