<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkOrderEnquiryStatus extends Model
{
    use HasFactory;
    protected $table = 'bulk_order_enquiry_status';
    protected $primaryKey = 'bulk_order_enquiry_status_id';
    protected $fillable = [''];
    public $timestamps = false;
}
