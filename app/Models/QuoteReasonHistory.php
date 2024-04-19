<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteReasonHistory extends Model
{
    use HasFactory;
    protected $table='quote_reason_history';
    protected $primaryKey = 'quote_reason_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}