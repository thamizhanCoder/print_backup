<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table='ticket';
    protected $primaryKey = 'ticket_id';
    protected $fillable=['department_id','related_services','orderno','subject','priority','status'];
    public $timestamps = false;
}
