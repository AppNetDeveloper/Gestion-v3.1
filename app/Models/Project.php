<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // <-- Importar BelongsToMany

class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'quote_id',
        'project_title',
        'description',
        'start_date',
        'due_date',
        'completion_date',
        'status',
        'budgeted_hours',
        'actual_hours',
        'internal_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completion_date' => 'date',
        'budgeted_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
    ];

    /**
     * Get the client that owns the project.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the quote that originated the project (if any).
     */
    public function quote(): BelongsTo
    {
        // Esto asume que la tabla 'projects' tiene una columna 'quote_id'
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the tasks for the project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the invoices associated with the project.
     * Un proyecto puede tener varias facturas (ej. facturación por hitos).
     */
    public function invoices(): HasMany
    {
        // Esto asume que la tabla 'invoices' tiene una columna 'project_id'
        return $this->hasMany(Invoice::class);
    }

        /**
     * The users that are assigned to the project.
     */
    public function assignedUsers(): BelongsToMany // <-- NUEVA RELACIÓN
    {
        return $this->belongsToMany(User::class, 'project_user');
        // 'project_user' es el nombre de la tabla pivote.
        // Laravel inferirá las claves foráneas 'project_id' y 'user_id'.
        // Si usaste otros nombres en la tabla pivote, especifícalos aquí.
    }
}
