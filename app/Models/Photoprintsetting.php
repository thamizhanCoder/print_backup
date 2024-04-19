<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photoprintsetting extends Model
{
    protected $table='photo_print_setting';
    protected $primaryKey = 'photo_print_settings_id';
    protected $fillable=['width','height','min_resolution_width','min_resolution_height','max_resolution_width','max_resolution_height'];
    public $timestamps = false;
}
