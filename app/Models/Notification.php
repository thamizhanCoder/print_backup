<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $table='notification';
    protected $primaryKey = 'notification_id';
    protected $fillable=[];
    public $timestamps = false;
}

