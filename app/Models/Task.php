<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id', // Clave foránea para la tabla projects
        'title',
        'description',
        'status',
        'priority',
        'start_date',
        'due_date',
        'completed_date',
        'estimated_hours',
        'logged_hours',
        // 'parent_task_id', // Descomentar si se implementan dependencias de tareas
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_date' => 'date',
        'estimated_hours' => 'decimal:2',
        'logged_hours' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * The users that are assigned to the task.
     */
    public function users(): BelongsToMany
    {
        // Especificamos la tabla pivote 'task_user'
        // y las claves foráneas si no siguen las convenciones de Laravel exactamente.
        // En este caso, Laravel debería inferirlas correctamente como task_id y user_id.
        return $this->belongsToMany(User::class, 'task_user');
    }

    /**
     * Get the time history records for the task.
     * Renombrado a 'timeHistories' para consistencia.
     */
    public function timeHistories(): HasMany
    {
        return $this->hasMany(TaskTimeHistory::class);
    }

    /**
     * Get the parent task (if this task is a subtask).
     * Descomentar si se implementa la funcionalidad de tareas padre/hija.
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * Get the child tasks (if this task is a parent task).
     * Descomentar si se implementa la funcionalidad de tareas padre/hija.
     */
    public function childTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }
}
