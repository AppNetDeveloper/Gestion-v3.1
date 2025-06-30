<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Importar BelongsToMany

class ScrapingTask extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'keyword',
        'region',
        'status',
        'type',
        'api_task_id',
        'ollama_task_id',
        'source',
        'data',
        'retry_attempts',
        'last_attempt_at',
        'failed_at',
        'error_message',
        'updated_at',
    ];

    /**
     * Obtiene el usuario que creó la tarea de scraping.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtiene la tarea de Ollama asociada (si existe).
     * Opcional: Solo si implementas la tabla y modelo OllamaTasker.
     */
    public function ollamaTasker(): BelongsTo
    {
        // Asume que el modelo de Ollama se llama OllamaTasker
        return $this->belongsTo(OllamaTasker::class, 'ollama_task_id');
    }

    /**
     * Obtiene los contactos asociados a esta tarea de scraping.
     * Define la relación muchos-a-muchos a través de la tabla pivot 'contact_scraping_task'.
     */
    public function contacts(): BelongsToMany
    {
        // Laravel infiere los nombres de las claves foráneas y la tabla pivot
        // si seguimos las convenciones.
        // Si no, se pueden especificar como argumentos adicionales:
        // return $this->belongsToMany(Contact::class, 'contact_scraping_task', 'scraping_task_id', 'contact_id');
        return $this->belongsToMany(Contact::class);
    }


    // --- Scopes (Ejemplos) ---
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
