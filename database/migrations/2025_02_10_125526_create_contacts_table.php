<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactsTable extends Migration
{
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Nuevo campo para separar los contactos por usuario
            $table->string('name');      // Nombre del contacto
            $table->string('phone');     // Teléfono
            $table->string('address')->nullable(); // Dirección (opcional)
            $table->string('email')->nullable();   // Email (opcional)
            $table->string('web')->nullable();     // Sitio web (opcional)
            $table->string('telegram')->nullable(); // ID o peer de Telegram (opcional)
            $table->timestamps();

            // Llave foránea: se asume que el id del usuario se encuentra en la tabla users.
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contacts');
    }
}
