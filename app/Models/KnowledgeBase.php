<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pgvector\Laravel\Vector; // Importamos la clase Vector
use Illuminate\Support\Facades\DB;

class KnowledgeBase extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'knowledge_base';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'embedding' => Vector::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'content',
        'embedding',
        'user_id',
        'knowledge_base_file_id',
        'ollama_tasker_id',
        'embedding_status',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function booted()
    {
        // Eliminar el embedding relacionado cuando se elimina el registro
        static::deleting(function ($knowledge) {
            if ($knowledge->ollama_tasker_id) {
                // Opcional: eliminar la tarea de Ollama relacionada si existe
                OllamaTasker::where('id', $knowledge->ollama_tasker_id)->delete();
            }
        });
    }

    /**
     * Obtiene el archivo al que pertenece este fragmento de conocimiento.
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseFile::class, 'knowledge_base_file_id');
    }

    /**
     * Obtiene el usuario propietario de este fragmento de conocimiento.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    /**
     * Obtiene la tarea de Ollama asociada a este fragmento.
     */
    public function ollamaTasker()
    {
        return $this->belongsTo(OllamaTasker::class, 'ollama_tasker_id');
    }
}
