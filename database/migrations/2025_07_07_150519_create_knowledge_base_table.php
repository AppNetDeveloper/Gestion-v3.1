<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Asegura que la extensión vector esté creada (idempotente)
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');

        Schema::create('knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->longText('content');
            $table->vector('embedding', 768); // Ajusta la dimensión si tu modelo lo requiere
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('source_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_base');
    }
};
