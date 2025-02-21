<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskerLinkedinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasker_linkedins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');           // Relación con la tabla users
            $table->text('prompt');                           // El mensaje enviado a la API
            $table->text('response')->nullable();             // La respuesta procesada (nullable hasta completarse)
            
            // Campo status con sus posibilidades
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending');                       // Estado de la tarea
            
            $table->text('error')->nullable();                // Mensaje de error en caso de fallo

            // Columna para la relación con la tabla ollama_taskers
            $table->unsignedBigInteger('ollama_tasker_id')->nullable();

            $table->dateTime('publish_date')->nullable();     // Fecha de publicación
            $table->timestamps();

            // Llave foránea con la tabla users
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            
            // Llave foránea con la tabla ollama_taskers
            $table->foreign('ollama_tasker_id')
                  ->references('id')
                  ->on('ollama_taskers')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Primero eliminamos las restricciones de llave foránea
        Schema::table('tasker_linkedins', function (Blueprint $table) {
            $table->dropForeign(['ollama_tasker_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::dropIfExists('tasker_linkedins');
    }
}
