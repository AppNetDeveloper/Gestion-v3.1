<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeControlStatus extends Model
{
    use HasFactory;

    protected $table = 'time_control_status';

    protected $fillable = [
        'table_name',
    ];

    // **Relaciones**

    // Un estado de control de tiempo puede tener muchos registros de control de tiempo
    public function timeControlRecords()
    {
        //return $this->hasMany(TimeControlRecord::class);
    }

    // **Métodos adicionales**

    // Obtener el nombre del estado en formato legible
    public function getFormattedStatusNameAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->table_name));
    }

    // **Alcance**

    // Filtrar por nombre de estado
    public function scopeFilterByName($query, $name)
    {
        return $query->where('table_name', 'like', "%$name%");
    }
}
