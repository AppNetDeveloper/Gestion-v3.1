<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftDayUserTable extends Migration
{
    public function up()
    {
        Schema::create('shift_day_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shift_day_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('shift_day_id')->references('id')->on('shift_days')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_day_user');
    }
}
