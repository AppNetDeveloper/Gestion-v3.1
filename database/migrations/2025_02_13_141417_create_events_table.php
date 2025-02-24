<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('category');
            $table->string('video_conferencia')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('contact_id')
                  ->references('id')->on('contacts')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('events');
    }
}
