<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\KnowledgeBaseFile;
use App\Models\KnowledgeBase;
use App\Models\OllamaTasker;
use Spatie\PdfToText\Pdf;

class ProcessPdfForRagJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $knowledgeBaseFileId;

    /**
     * Create a new job instance.
     * 
     * @param int $knowledgeBaseFileId ID del archivo en la base de datos
     */
    public function __construct(int $knowledgeBaseFileId)
    {
        $this->knowledgeBaseFileId = $knowledgeBaseFileId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $file = KnowledgeBaseFile::findOrFail($this->knowledgeBaseFileId);
            $fullPath = storage_path('app/' . $file->file_path);
            
            // 1. Extraer texto del PDF
            $text = Pdf::getText($fullPath);
            
            if (empty($text)) {
                Log::warning("No se pudo extraer texto del PDF: {$file->original_name}");
                return;
            }
            
            // 2. Dividir en chunks (método simple por párrafos)
            $chunks = $this->splitTextIntoParagraphs($text);
            
            foreach ($chunks as $chunk) {
                if (empty(trim($chunk))) continue;
                
                // 3.1. Crear tarea en ollama_taskers
                $tasker = OllamaTasker::create([
                    'model'   => 'nomic-embed-text', // Usamos nomic-embed-text directamente para embeddings
                    'prompt'  => "Genera el embedding para el siguiente texto:\n---\n" . $chunk,
                ]);
                
                // 3.2. Crear fila en knowledge_base con un vector vacío
                // Usamos pgvector para crear un vector vacío de dimensión 768 (dimensión configurada en la base de datos)
                $emptyVector = "[" . implode(',', array_fill(0, 768, 0)) . "]";
                
                KnowledgeBase::create([
                    'content'           => $chunk,
                    'embedding'         => $emptyVector,
                    'ollama_tasker_id'  => $tasker->id,
                    'embedding_status'  => 'pending',
                    'user_id'           => $file->user_id,
                    'source_id'         => $file->id,
                ]);
            }
            
            Log::info("PDF procesado correctamente: {$file->original_name}");
        } catch (\Exception $e) {
            Log::error('Error en ProcessPdfForRagJob: ' . $e->getMessage());
        }
    }
    
    /**
     * Divide el texto en párrafos (método simple sin LLPhant)
     */
    private function splitTextIntoParagraphs($text, $maxLength = 1024)
    {
        // Normalizar saltos de línea
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        
        // Dividir por párrafos (doble salto de línea)
        $paragraphs = preg_split('/\n\s*\n/', $text);
        
        $chunks = [];
        $currentChunk = '';
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) continue;
            
            // Si el párrafo es muy largo, dividirlo
            if (strlen($paragraph) > $maxLength) {
                // Dividir párrafo largo en oraciones
                $sentences = preg_split('/(\.|\?|\!)(\s|$)/', $paragraph, -1, PREG_SPLIT_DELIM_CAPTURE);
                $tempChunk = '';
                
                for ($i = 0; $i < count($sentences); $i += 3) {
                    if (!isset($sentences[$i]) || trim($sentences[$i]) === '') continue;
                    
                    $sentence = $sentences[$i];
                    $delimiter = isset($sentences[$i+1]) ? $sentences[$i+1] : '';
                    $space = isset($sentences[$i+2]) ? $sentences[$i+2] : '';
                    
                    $sentenceComplete = $sentence . $delimiter . $space;
                    
                    if (strlen($tempChunk . $sentenceComplete) > $maxLength) {
                        if (!empty($tempChunk)) {
                            $chunks[] = $tempChunk;
                            $tempChunk = '';
                        }
                        
                        // Si una sola oración es más larga que maxLength, dividirla
                        if (strlen($sentenceComplete) > $maxLength) {
                            $words = explode(' ', $sentenceComplete);
                            $wordChunk = '';
                            
                            foreach ($words as $word) {
                                if (strlen($wordChunk . ' ' . $word) > $maxLength) {
                                    $chunks[] = $wordChunk;
                                    $wordChunk = $word;
                                } else {
                                    $wordChunk .= (empty($wordChunk) ? '' : ' ') . $word;
                                }
                            }
                            
                            if (!empty($wordChunk)) {
                                $tempChunk = $wordChunk;
                            }
                        } else {
                            $tempChunk = $sentenceComplete;
                        }
                    } else {
                        $tempChunk .= $sentenceComplete;
                    }
                }
                
                if (!empty($tempChunk)) {
                    if (strlen($currentChunk . "\n\n" . $tempChunk) > $maxLength) {
                        if (!empty($currentChunk)) {
                            $chunks[] = $currentChunk;
                        }
                        $currentChunk = $tempChunk;
                    } else {
                        $currentChunk .= (empty($currentChunk) ? '' : "\n\n") . $tempChunk;
                    }
                }
            } else {
                // Párrafo normal
                if (strlen($currentChunk . "\n\n" . $paragraph) > $maxLength) {
                    $chunks[] = $currentChunk;
                    $currentChunk = $paragraph;
                } else {
                    $currentChunk .= (empty($currentChunk) ? '' : "\n\n") . $paragraph;
                }
            }
        }
        
        if (!empty($currentChunk)) {
            $chunks[] = $currentChunk;
        }
        
        return $chunks;
    }
}
