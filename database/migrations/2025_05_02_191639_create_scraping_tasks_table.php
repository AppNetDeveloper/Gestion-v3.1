<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scraping_tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // *** NUEVO CAMPO: Fuente/Tipo de la tarea ***
            // Guardará 'google_ddg', 'empresite', 'paginas_amarillas', etc.
            $table->string('source');

            $table->string('keyword');
            $table->string('region')->nullable();
            $table->string('status')->default('pending');
            $table->uuid('api_task_id')->nullable()->unique();
            $table->foreignId('ollama_task_id')->nullable()->constrained('ollama_taskers')->onDelete('set null');
            $table->timestamps();

            // Índices
            $table->index('user_id');
            $table->index('status');
            $table->index('api_task_id');
            $table->index('ollama_task_id');
            $table->index('source'); // Añadir índice para el nuevo campo
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scraping_tasks');
    }
};
