<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $table='rating_review';
    protected $primaryKey = 'rating_review_id';
    protected $fillable=['brand_name'];
    public $timestamps = false;

}