<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Relación con el usuario que posee el correo
            $table->unsignedBigInteger('user_id');
            // Relación con el contacto (puede referirse a una tabla "contacts")
            $table->unsignedBigInteger('contact_id');

            // Asunto (subject) del correo
            $table->string('subject');
            // Fecha y hora de recepción
            $table->timestamp('date');
            // Remitente (sender)
            $table->string('sender');

            $table->string('folder')->nullable();

            $table->timestamps();

            // Claves foráneas
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // Ajusta la siguiente línea según la tabla de contactos que tengas
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('emails');
    }
};

