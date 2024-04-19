<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancelReason extends Model
{
    use HasFactory;
    protected $table = 'cancel_reason';
    protected $primaryKey = 'cancel_reason_id';
    protected $fillable = [''];
    public $timestamps = false;
}
