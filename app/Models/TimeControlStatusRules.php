<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeControlStatusRules extends Model
{
    use HasFactory;

    protected $table = 'time_control_rules';

    protected $fillable = [
        'table_name',
    ];

    // **Relaciones**

    // Un estado de control de tiempo puede tener muchos registros de control de tiempo
    public function timeControlRecordsRules()
    {
        //return $this->hasMany(TimeControlRecord::class);
    }

    // **Métodos adicionales**
    // En el modelo TimeControlStatusRules.php

    //Obtengo el nombre real de la tabla padre time_control_status
    public function timeControlStatus()
    {
        return $this->belongsTo(TimeControlStatus::class, 'time_control_status_id');
    }
        public function permission()
    {
        return $this->belongsTo(TimeControlStatus::class, 'permission_id');
    }
    // Obtener el nombre del estado en formato legible
    public function getFormattedStatusNameAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->time_control_status_id));
    }

    // **Alcance**

    // Filtrar por nombre de estado
    public function scopeFilterByName($query, $name)
    {
        return $query->where('time_control_status_id', 'like', "%$name%");
    }

}
