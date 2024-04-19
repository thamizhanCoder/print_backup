<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TermsAndConditions extends Model
{
    protected $table='terms_and_conditions';
    protected $primaryKey = 'terms_and_conditions_id';
    protected $fillable=[''];
    public $timestamps = false;

}