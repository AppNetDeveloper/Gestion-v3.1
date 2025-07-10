<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// Importamos la clase si existe en el sistema
// Si no existe, el código en booted() lo manejará
use App\Models\KnowledgeChunk;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OllamaTasker extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'model',
        'prompt',
        'response',
        'error',
        'callback_url',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        // Add any hidden attributes here
    ];
    
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        // Add any accessors here
    ];

    /**
     * (Opcional) Si deseas especificar la tabla de forma explícita.
     * Por defecto, Laravel asume el nombre plural del modelo.
     *
     * @var string
     */
    // protected $table = 'ollama_taskers';

    /**
     * (Opcional) Si deseas definir el nombre de la clave primaria.
     * Por defecto es 'id'.
     *
     * @var string
     */
    // protected $primaryKey = 'id';

    /**
     * (Opcional) Si los campos 'created_at' y 'updated_at' no se usan.
     *
     * @var bool
     */
    // public $timestamps = true;

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function (OllamaTasker $task) {
            Log::info('--- RAG PROCESS START ---');

            if (str_contains($task->model, 'nomic-embed-text')) {
                Log::info('RAG: Embedding model detected. Skipping process.');
                return;
            }

            $originalPrompt = $task->prompt;
            Log::info('RAG: Original prompt received.', ['prompt' => $originalPrompt]);

            $searchTerms = array_filter(explode(' ', $originalPrompt), fn($term) => strlen($term) > 3);
            $searchTerms = array_slice($searchTerms, 0, 10);

            if (empty($searchTerms)) {
                Log::info('RAG: No valid search terms found. Skipping.');
                return;
            }
            Log::info('RAG: Search terms extracted.', ['terms' => $searchTerms]);

            $user = Auth::user();

            if (!$user) {
                Log::warning('RAG: No authenticated user. Skipping knowledge base search.');
                return;
            }
            // Log user info - solo necesitamos user_id
            Log::info('RAG: Authenticated user found.', ['user_id' => $user->id]);

            // Verificar si la clase KnowledgeChunk existe antes de usarla
            if (!class_exists('App\\Models\\KnowledgeChunk')) {
                Log::warning('RAG: KnowledgeChunk class not found. Skipping knowledge base search.');
                return; // Salir del evento si la clase no existe
            }
            
            try {
                // Buscar cualquier contenido en la base de conocimiento
                Log::info('RAG: Realizando búsqueda amplia en la base de conocimiento');
                
                // Obtener un término relevante del prompt original
                $relevantTerm = null;
                $businessTerms = ['empresa', 'negocio', 'servicio', 'cliente', 'producto', 'desarrollo', 'software', 'automatización'];
                
                foreach ($businessTerms as $term) {
                    if (stripos($task->prompt, $term) !== false) {
                        $relevantTerm = $term;
                        break;
                    }
                }
                
                // Si no se encuentra un término relevante, usar uno genérico
                if (!$relevantTerm) {
                    $relevantTerm = 'empresa';
                }
                
                Log::info('RAG: Término relevante seleccionado', ['term' => $relevantTerm]);
                
                // Construir consulta muy simple
                $knowledgeChunks = KnowledgeChunk::query()
                    ->where('content', 'LIKE', '%' . $relevantTerm . '%')
                    ->when($user, function ($query, $user) {
                        $query->whereHas('knowledgeBaseFile', function ($fileQuery) use ($user) {
                            // Documentos del usuario o documentos públicos (user_id = null)
                            $fileQuery->where('user_id', $user->id)
                                     ->orWhereNull('user_id');
                        });
                    })
                    // Ordenar por relevancia (número de coincidencias)
                    ->orderByRaw('LENGTH(content) DESC') // Priorizar fragmentos más completos
                    ->limit(5)
                    ->get();
            } catch (\Exception $e) {
                Log::error('RAG: Error during knowledge base search: ' . $e->getMessage());
                return; // Salir del evento si hay un error
            }

            Log::info('RAG: Knowledge base search completed.', ['chunks_found' => $knowledgeChunks->count()]);

            if ($knowledgeChunks->isNotEmpty()) {
                $additionalInfo = "\n\n---\nInformación adicional extraída de la base de conocimiento (utilízala para mejorar tu respuesta):\n";
                foreach ($knowledgeChunks as $chunk) {
                    $additionalInfo .= "- " . trim($chunk->content) . "\n";
                }
                $additionalInfo .= "---\n";
                
                $task->prompt .= $additionalInfo;
                Log::info('RAG: Prompt augmented successfully.');
            } else {
                Log::info('RAG: No relevant chunks found to augment prompt.');
            }
            Log::info('--- RAG PROCESS END ---');
        });
    }
}
