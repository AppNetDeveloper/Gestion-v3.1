<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutoProcessesTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auto_processes', function (Blueprint $table) {
            $table->id();
            // Asumiendo que cada usuario tendrá una configuración, se establece user_id como único.
            $table->unsignedBigInteger('user_id')->unique();
            // 0: desactivado, 1: texto automático, 2: con IA, 3: con ticket.
            $table->unsignedTinyInteger('whatsapp')->default(0)->comment('0: desactivado, 1: texto automático, 2: con IA, 3: con ticket');
            $table->text('whatsapp_prompt')->nullable();
            $table->unsignedTinyInteger('telegram')->default(0)->comment('0: desactivado, 1: texto automático, 2: con IA, 3: con ticket');
            $table->text('telegram_prompt')->nullable();
            $table->unsignedTinyInteger('email')->default(0)->comment('0: desactivado, 1: texto automático, 2: con IA, 3: con ticket');
            $table->text('email_prompt')->nullable();
            $table->timestamps();

            // Se agrega la llave foránea para el usuario (asumiendo que la tabla de usuarios se llama "users")
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auto_processes');
    }
}

