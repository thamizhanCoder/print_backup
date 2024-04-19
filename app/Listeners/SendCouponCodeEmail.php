<?php

namespace App\Listeners;

use App\Events\CouponCodeMail;
use Illuminate\Support\Facades\Mail;


class SendCouponCodeEmail
{
    /**
     * Handle the event.
     *
     * @param  NewUserRegistered  $event
     */
    public function handle(CouponCodeMail $event)
    {
        //send the welcome email to the user
        $user = $event->user;

        Mail::send('mail.couponemail', ['user' => $user], function ($message) use ($user) {
            $message->subject('Special Coupon Just for You!');
            $message->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'));
            $message->to($user['email']);
        });
    }
}
