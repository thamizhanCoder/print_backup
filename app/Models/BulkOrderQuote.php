<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkOrderQuote extends Model
{
    use HasFactory;
    protected $table='bulk_order_quote';
    protected $primaryKey = 'bulk_order_quote_id';
    protected $fillable=[''];
    public $timestamps = false;

}