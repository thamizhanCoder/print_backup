<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContestFieldType extends Model
{
    use HasFactory;
    protected $table='contest_field_type';
    protected $primaryKey = 'contest_field_type_id';
    protected $fillable=[''];
    public $timestamps = false;
}
