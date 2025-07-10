<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
            Log::info('RAG: Authenticated user found.', ['user_id' => $user->id, 'company_id' => $user->company_id]);

            $knowledgeChunks = KnowledgeChunk::query()
                ->where(function ($query) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $query->orWhere('content', 'LIKE', '%' . $term . '%');
                    }
                })
                ->when($user, function ($query, $user) {
                    $query->whereHas('knowledgeBaseFile', function ($fileQuery) use ($user) {
                        $fileQuery->where('user_id', $user->id)
                                  ->orWhere(function ($companyQuery) use ($user) {
                                      if ($user->company_id) {
                                          $companyQuery->where('company_id', $user->company_id);
                                      }
                                  });
                    });
                })
                ->limit(5)
                ->get();

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
