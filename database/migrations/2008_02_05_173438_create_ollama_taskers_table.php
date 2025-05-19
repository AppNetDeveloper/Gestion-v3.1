<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOllamaTaskersTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ollama_taskers', function (Blueprint $table) {
            $table->id();
            $table->string('model');                           // model
            $table->text('prompt');                             // Mensaje o prompt enviado a la API
            $table->text('response')->nullable();               // Respuesta procesada (nullable hasta completarse)
            $table->text('error')->nullable();                  // Mensaje de error en caso de fallo
            $table->timestamps();
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ollama_taskers');
    }
}
