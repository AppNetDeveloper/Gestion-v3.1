<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pgvector\Laravel\Vector;

/**
 * KnowledgeChunk es un alias de KnowledgeBase para mantener compatibilidad
 * con el código existente que hace referencia a KnowledgeChunk.
 * 
 * Este modelo se utiliza para buscar texto en la base de conocimiento
 * y filtrar por usuario y permisos.
 */
class KnowledgeChunk extends KnowledgeBase
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
        'content',        // Contenido del fragmento de conocimiento
        'embedding',      // Vector de embedding para búsquedas semánticas
        'user_id',        // ID del usuario propietario (puede ser nulo para documentos públicos)
        'knowledge_base_file_id', // ID del archivo al que pertenece este fragmento
        'ollama_tasker_id',      // ID de la tarea de Ollama relacionada (para embeddings)
        'embedding_status',      // Estado del proceso de embedding
    ];

    /**
     * Obtiene el archivo al que pertenece este fragmento de conocimiento.
     * Esta relación es fundamental para el filtrado por usuario en las consultas RAG.
     */
    public function knowledgeBaseFile(): BelongsTo
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
}
