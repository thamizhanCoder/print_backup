<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkOrderQuoteStatus extends Model
{
    protected $table='bulk_order_quote_status';
    protected $primaryKey = 'bulk_order_quote_status_id';
    protected $fillable=[''];
    public $timestamps = false;
}
