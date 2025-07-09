<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixNullFilePathsInKnowledgeBaseFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Registrar cuántos registros tienen file_path nulo
        $nullPathCount = DB::table('knowledge_base_files')
            ->whereNull('file_path')
            ->count();
        
        Log::info("Encontrados {$nullPathCount} registros con file_path nulo en knowledge_base_files");
        
        // 2. Eliminar los registros de knowledge_base relacionados con archivos con file_path nulo
        $nullPathIds = DB::table('knowledge_base_files')
            ->whereNull('file_path')
            ->pluck('id')
            ->toArray();
        
        $deletedChunks = DB::table('knowledge_base')
            ->whereIn('source_id', $nullPathIds)
            ->delete();
        
        Log::info("Eliminados {$deletedChunks} chunks de knowledge_base relacionados con archivos nulos");
        
        // 3. Eliminar los registros con file_path nulo
        $deletedFiles = DB::table('knowledge_base_files')
            ->whereNull('file_path')
            ->delete();
        
        Log::info("Eliminados {$deletedFiles} registros con file_path nulo de knowledge_base_files");
        
        // 4. Añadir restricción NOT NULL a la columna file_path
        Schema::table('knowledge_base_files', function (Blueprint $table) {
            $table->string('file_path')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revertir la restricción NOT NULL
        Schema::table('knowledge_base_files', function (Blueprint $table) {
            $table->string('file_path')->nullable()->change();
        });
    }
}
