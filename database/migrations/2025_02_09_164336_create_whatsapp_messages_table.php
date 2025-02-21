<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('phone');
            $table->text('message'); // Campo para el mensaje en inglés
            // Agregamos el campo status con valores 'send' o 'recived'
            $table->enum('status', ['send', 'recived'])->default('send');
            $table->text('image')->nullable(); // Campo para imagen en base64 (opcional)
            $table->timestamps();

            // Definir la llave foránea con la tabla users
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_messages');
    }
}
