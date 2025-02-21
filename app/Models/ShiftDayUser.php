<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ShiftDayUser extends Pivot
{
    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'shift_day_user';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shift_day_id',
        'user_id',
    ];
}
