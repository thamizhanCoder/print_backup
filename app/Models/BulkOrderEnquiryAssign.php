<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkOrderEnquiryAssign extends Model
{
    use HasFactory;
    protected $table = 'bulk_order_enquiry_assign';
    protected $primaryKey = 'bulk_order_enquiry_assign_id';
    protected $fillable = [''];
    public $timestamps = false;
}
