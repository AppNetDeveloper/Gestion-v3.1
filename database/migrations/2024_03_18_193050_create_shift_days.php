<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shift_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shift_id');
            $table->foreign('shift_id')->references('id')->on('shift')->onDelete('cascade');
            $table->string('day_of_week'); // LUNES, MARTES, ...
            $table->time('start_time');
            $table->time('end_time');
            $table->string('effective_hours');
            $table->boolean('split_shift')->default(false);
            $table->time('split_start_time')->nullable();
            $table->time('split_end_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shift_days');
    }
};
