<?php

// database/migrations/xxxx_xx_xx_create_host_lists_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHostListsTable extends Migration
{
    public function up()
    {
        Schema::create('host_lists', function (Blueprint $table) {
            $table->id();
            $table->string('host')->unique(); // Dirección IP o nombre de host, único
            $table->string('token')->unique(); // Token único para cada host
            $table->string('name'); // Nombre descriptivo del host (opcional)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('host_lists');
    }
}

