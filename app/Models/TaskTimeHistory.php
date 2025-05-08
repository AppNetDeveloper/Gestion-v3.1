<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskTimeHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'task_time_history'; // Nombre explícito de la tabla

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_id',
        'user_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'log_date',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime', // Se maneja como objeto Carbon/DateTime
        'end_time' => 'datetime',   // Se maneja como objeto Carbon/DateTime
        'duration_minutes' => 'integer',
        'log_date' => 'date',       // Se maneja como objeto Carbon/Date
    ];

    /**
     * Get the task that this time history entry belongs to.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user who logged this time history entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
