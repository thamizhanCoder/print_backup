<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;


class QuoteReraiseRejectEmployee
{
    use SerializesModels;

    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
}
