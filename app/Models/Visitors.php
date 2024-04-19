<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Visitors extends Model
{
    use HasFactory;
    protected $table='visit_history';
    protected $primaryKey = 'visit_history_id';
    protected $fillable=[''];
    public $timestamps = false;
}