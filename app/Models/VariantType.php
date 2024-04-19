<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantType extends Model
{
    use HasFactory;
    protected $table='variant_type';
    protected $primaryKey = 'variant_type_id';
    protected $fillable=['variant_type'];
    public $timestamps = false;
}
