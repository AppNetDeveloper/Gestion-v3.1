<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Pgvector\Laravel\Vector; // Importamos la clase Vector

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
        'company_id', // Añadido para multi-inquilino
        'user_id',
        'source_id',
        'ollama_tasker_id',
        'embedding_status',
    ];

    /**
     * Obtiene el archivo original al que pertenece este trozo de conocimiento.
     */
    public function sourceFile()
    {
        return $this->belongsTo(KnowledgeBaseFile::class, 'source_id');
    }
}
