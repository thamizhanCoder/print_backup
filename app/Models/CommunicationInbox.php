<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationInbox extends Model
{
    use HasFactory;
    protected $table='communication_inbox';
    protected $primaryKey = 'communication_inbox_id';
    protected $fillable=[''];
    public $timestamps = false;

}