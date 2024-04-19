<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PgLinkHistory extends Model
{
    protected $table='pg_link_history';
    protected $primaryKey = 'pg_link_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}