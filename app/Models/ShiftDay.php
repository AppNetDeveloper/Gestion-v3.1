<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftDay extends Model
{
    use HasFactory;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'shift_days';

    /**
     * Campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shift_id',
        'day_of_week',
        'start_time',
        'end_time',
        'effective_hours',
        'split_shift',
        'split_start_time',
        'split_end_time',
        'created_at',
        'updated_at'
    ];

    /**
     * Definición de los tipos de dato para cada columna
     * (opcional, dependiendo de tu lógica).
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time'        => 'datetime:H:i',
        'end_time'          => 'datetime:H:i',
        'split_start_time'  => 'datetime:H:i',
        'split_end_time'    => 'datetime:H:i',
    ];

    /**
     * Relación con el modelo Shift.
     * Indica que cada ShiftDay pertenece a un Shift.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
        /**
     * Comprueba si el turno es partido.
     */
    public function isSplitShift()
    {
        return $this->split_shift == 1;
    }
// app/Models/ShiftDay.php

    public function users()
    {
        return $this->belongsToMany(User::class, 'shift_day_user', 'shift_day_id', 'user_id')
                    ->using(ShiftDayUser::class)
                    ->withTimestamps();
    }

}
