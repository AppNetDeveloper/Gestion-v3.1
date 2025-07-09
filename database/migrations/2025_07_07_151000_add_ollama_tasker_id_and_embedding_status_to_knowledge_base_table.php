<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('knowledge_base', function (Blueprint $table) {
            $table->unsignedBigInteger('ollama_tasker_id')->nullable()->after('embedding');
            $table->string('embedding_status')->default('pending')->after('ollama_tasker_id');
            // Opcional: índice para búsquedas rápidas
            $table->index('ollama_tasker_id');
            $table->index('embedding_status');
        });
    }

    public function down()
    {
        Schema::table('knowledge_base', function (Blueprint $table) {
            $table->dropIndex(['ollama_tasker_id']);
            $table->dropIndex(['embedding_status']);
            $table->dropColumn(['ollama_tasker_id', 'embedding_status']);
        });
    }
};
