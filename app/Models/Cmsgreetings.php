<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Cmsgreetings extends Model
{
    use HasFactory;
    protected $table='cms_greeting';
    protected $primaryKey = 'cms_greeting_id';
    protected $fillable=[];
    public $timestamps = false;
}

