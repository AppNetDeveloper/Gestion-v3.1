<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTelegramMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->text('message');
            $table->unsignedBigInteger('date');
            $table->string('peer')->nullable();
            $table->string('status');
            $table->string('image')->nullable();
            $table->timestamps();

            // Relación con la tabla users (clave foránea)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('telegram_messages');
    }
}

