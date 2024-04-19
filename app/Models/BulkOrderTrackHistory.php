<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkOrderTrackHistory extends Model
{
    use HasFactory;
    protected $table = 'bulk_order_track_history';
    protected $primaryKey = 'bulk_order_track_history_id';
    protected $fillable = [''];
    public $timestamps = false;
}
