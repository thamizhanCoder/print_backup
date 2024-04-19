<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CmsVideo extends Model
{
    use HasFactory;
    protected $table='cms_video';
    protected $primaryKey = 'cms_video_id';
    protected $fillable=['video_url','video_description'];
    public $timestamps = false;
}

