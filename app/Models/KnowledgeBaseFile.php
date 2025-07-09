<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\KnowledgeBase; // Importamos la clase KnowledgeBase

class KnowledgeBaseFile extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'knowledge_base_files';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'file_path',
        'original_name',
        'user_id',
    ];

    /**
     * Obtiene el usuario propietario del archivo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtiene todos los trozos (chunks) de conocimiento asociados a este archivo.
     */
    public function knowledgeChunks(): HasMany
    {
        // Asume que la columna 'source_id' en la tabla 'knowledge_base'
        // guarda el 'id' de este archivo (KnowledgeBaseFile).
        return $this->hasMany(KnowledgeBase::class, 'source_id');
    }
}
