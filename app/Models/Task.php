<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth; // Necesario para getActiveTimeLogForCurrentUser

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'status',
        'priority',
        'start_date',
        'due_date',
        'completed_date', // Cambiado de completion_date para coincidir con tu modelo
        'estimated_hours',
        'logged_hours',
        // 'parent_task_id', // Mantener comentado si no se usa aún
        'sort_order',
        'created_by_user_id', // <-- AÑADIDO: Quién creó la tarea
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
        return $this->belongsToMany(User::class, 'task_user');
    }

    /**
     * Get the user who created the task.
     */
    public function creator(): BelongsTo // <-- NUEVA RELACIÓN (Opcional)
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get all of the time history entries for the task.
     */
    public function timeHistories(): HasMany
    {
        return $this->hasMany(TaskTimeHistory::class)->orderBy('start_time', 'desc');
    }

    /**
     * Get the parent task (if this task is a subtask).
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * Get the child tasks (if this task is a parent task).
     */
    public function childTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * Get the active time log for the currently authenticated user for this task.
     *
     * @return TaskTimeHistory|null
     */
    public function getActiveTimeLogForCurrentUser(): ?TaskTimeHistory // <-- NUEVO MÉTODO
    {
        if (!Auth::check()) {
            return null;
        }
        return $this->timeHistories()
                    ->where('user_id', Auth::id())
                    ->whereNull('end_time')
                    ->first();
    }

    /**
     * Get the active time log for a specific user for this task.
     *
     * @param User $user
     * @return TaskTimeHistory|null
     */
    public function getActiveTimeLogForUser(User $user): ?TaskTimeHistory // <-- NUEVO MÉTODO
    {
        return $this->timeHistories()
                    ->where('user_id', $user->id)
                    ->whereNull('end_time')
                    ->first();
    }
}
