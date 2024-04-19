<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContestParticipant extends Model
{
    use HasFactory;
    protected $table='contest_participant';
    protected $primaryKey = 'contest_participant_id';
    protected $fillable=[''];
    public $timestamps = false;
}
