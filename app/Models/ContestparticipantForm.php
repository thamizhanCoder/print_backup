<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContestparticipantForm extends Model
{
    use HasFactory;
    protected $table = 'contest_participant_form';
    protected $primaryKey = 'contest_participant_form_id';
    protected $fillable = [''];
    public $timestamps = false;
}
