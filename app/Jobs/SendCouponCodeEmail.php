<?php

namespace App\Jobs;

use App\Events\CouponCodeMail;
use App\Mail\TestHelloEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendCouponCodeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $email;
    public $coupon_code;

    public $timeout = 7200;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($email, $coupon_code)
    {
        $this->email = $email;
        $this->coupon_code = $coupon_code;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // \DB::table('jobs')->where('id',$this->sync_id)->update(['tot_count'=>\DB::raw('tot_count + 1')]);
        if (!empty($this->email)) {
            $email = $this->email;
            $coupon_code = $this->coupon_code;
                    Mail::send('mail.couponemail', ['coupon_code' => $coupon_code], function($message) use ($email) {
                        $message->to($email)->subject("Special Coupon Just for You!");
                    });
        }
    }
}
