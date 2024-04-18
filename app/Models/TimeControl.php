<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeControl extends Model
{
    protected $fillable = [
        'user_id', 
        'time_control_status_id', 
        'lat', 
        'long', 
        'time_break', 
        'total_break_time', 
        'time_working', 
        'time_worked', 
        'overtime',
        'missing_time',
        'created_ad',
        'updated_ad',
        

        
        // Agregar campos adicionales como latitud, longitud, etc.
    ];


    // Relación con el modelo User (pertenece a)
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    // Relación con el modelo TimeControlStatus (pertenece a)
    public function status()
    {
        return $this->belongsTo('App\Models\TimeControlStatus');
    }
}
