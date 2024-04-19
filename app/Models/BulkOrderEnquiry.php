<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkOrderEnquiry extends Model
{
    use HasFactory;
    protected $table = 'bulk_order_enquiry';
    protected $primaryKey = 'bulk_order_enquiry_id';
    protected $fillable = [''];
    public $timestamps = false;
}
