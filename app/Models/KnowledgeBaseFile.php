<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\KnowledgeBase; // Importamos la clase KnowledgeBase
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class KnowledgeBaseFile extends Model
{
    use HasFactory;
    
    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        // Verificar que file_path no sea nulo antes de guardar
        static::saving(function ($model) {
            if (empty($model->file_path)) {
                Log::error('Intento de guardar KnowledgeBaseFile con file_path nulo', [
                    'user_id' => $model->user_id,
                    'original_name' => $model->original_name
                ]);
                throw new \InvalidArgumentException('El campo file_path no puede ser nulo');
            }
        });
    }

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
        'user_id', // Puede ser nulo si es un PDF de empresa
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function booted()
    {
        // Eliminar el archivo físico cuando se elimina el registro
        static::deleting(function ($file) {
            if (Storage::exists($file->file_path)) {
                Storage::delete($file->file_path);
            }
        });
    }

    /**
     * Obtiene el usuario propietario del archivo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    /**
     * Obtiene todos los trozos de conocimiento asociados a este archivo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function knowledgeChunks(): HasMany
    {
        // Relación con los chunks de conocimiento usando la nueva columna knowledge_base_file_id
        return $this->hasMany(KnowledgeBase::class, 'knowledge_base_file_id');
    }
}
