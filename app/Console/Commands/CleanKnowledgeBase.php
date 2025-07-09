<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanKnowledgeBase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-knowledge-base';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia completamente la base de conocimiento, incluyendo archivos y registros de la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('¿Estás seguro de que deseas limpiar completamente la base de conocimiento? Esto eliminará todos los archivos y registros relacionados.')) {
            $this->info('Operación cancelada.');
            return 0;
        }

        $this->info('Iniciando limpieza de la base de conocimiento...');

        // 1. Eliminar todos los registros de knowledge_base
        $deletedChunks = \App\Models\KnowledgeBase::query()->delete();
        $this->info("Se eliminaron {$deletedChunks} fragmentos de conocimiento.");

        // 2. Obtener todos los archivos antes de eliminarlos
        $files = \App\Models\KnowledgeBaseFile::all();
        $fileCount = $files->count();
        
        // 3. Eliminar los archivos físicos
        $deletedFiles = 0;
        foreach ($files as $file) {
            if (\Illuminate\Support\Facades\Storage::exists($file->file_path)) {
                \Illuminate\Support\Facades\Storage::delete($file->file_path);
                $deletedFiles++;
            }
        }
        
        // 4. Eliminar los registros de la base de datos
        $deletedFileRecords = \App\Models\KnowledgeBaseFile::query()->delete();
        
        $this->info("Se eliminaron {$deletedFiles} archivos físicos de un total de {$fileCount} registros.");
        $this->info("Se eliminaron {$deletedFileRecords} registros de archivos de la base de datos.");
        
        // 5. Opcional: Limpiar tareas de Ollama relacionadas
        $deletedTasks = \App\Models\OllamaTasker::where('model', 'like', '%nomic-embed-text%')->delete();
        $this->info("Se eliminaron {$deletedTasks} tareas de Ollama relacionadas con embeddings.");
        
        $this->info('¡Limpieza completada con éxito!');
        return 0;
    }
}
