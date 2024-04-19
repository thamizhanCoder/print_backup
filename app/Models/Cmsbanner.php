<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Cmsbanner extends Model
{
    use HasFactory;
    protected $table='cms_banner';
    protected $primaryKey = 'cms_banner_id';
    protected $fillable=[];
    public $timestamps = false;
}

