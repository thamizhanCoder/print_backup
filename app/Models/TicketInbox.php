<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketInbox extends Model
{
    protected $table='ticket_inbox';
    protected $primaryKey = 'ticket_inbox_id';
    protected $fillable=[''];
    public $timestamps = false;
}