<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar knowledge_base_file_id a knowledge_base
        Schema::table('knowledge_base', function (Blueprint $table) {
            $table->foreignId('knowledge_base_file_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('knowledge_base_files')
                  ->onDelete('cascade');
            
            // Cambiar source_id a knowledge_base_file_id (opcional, dependiendo del uso)
            $table->dropColumn('source_id');
            
            // Cambiar company_id a user_id si existe
            if (Schema::hasColumn('knowledge_base', 'company_id')) {
                $table->renameColumn('company_id', 'user_id');
            }
        });
        
        // 2. Asegurarnos de que knowledge_base_files tiene user_id nullable
        Schema::table('knowledge_base_files', function (Blueprint $table) {
            $table->foreignId('user_id')
                  ->nullable()
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir los cambios
        Schema::table('knowledge_base', function (Blueprint $table) {
            // Eliminar la relación
            $table->dropForeign(['knowledge_base_file_id']);
            
            // Volver a agregar source_id si era necesario
            if (!Schema::hasColumn('knowledge_base', 'source_id')) {
                $table->string('source_id')->nullable()->after('id');
            }
            
            // Revertir el cambio de company_id a user_id si existía
            if (Schema::hasColumn('knowledge_base', 'user_id') && 
                !Schema::hasColumn('knowledge_base', 'company_id')) {
                $table->renameColumn('user_id', 'company_id');
            }
        });
    }
};
