<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkOrderQuoteDetails extends Model
{
    use HasFactory;
    protected $table='bulk_order_quote_details';
    protected $primaryKey = 'bulk_order_quote_details_id';
    protected $fillable=[''];
    public $timestamps = false;

}