<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CompanyInfo extends Model
{
    use HasFactory;
    protected $table='company_info';
    protected $primaryKey = 'id';
    protected $fillable=[];
    public $timestamps = false;
}

