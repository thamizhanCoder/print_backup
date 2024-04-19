<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContestForm extends Model
{
    use HasFactory;
    protected $table = 'contest_form';
    protected $primaryKey = 'contest_form_id';
    protected $fillable = [''];
    public $timestamps = false;
}
