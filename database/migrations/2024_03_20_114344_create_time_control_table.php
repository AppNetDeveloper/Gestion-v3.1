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
    Schema::create('time_controls', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->unsignedBigInteger('time_control_status_id');
        $table->foreign('time_control_status_id')->references('id')->on('time_control_status')->onDelete('cascade');
        $table->decimal('lat', 10, 8)->nullable();
        $table->decimal('long', 11, 8)->nullable();
        $table->integer('time_break')->nullable(); // Duration of a single break (in minutes)
        $table->integer('total_break_time')->nullable();  // Total accumulated break time (in minutes)
        $table->integer('time_working')->nullable(); // Active work time without breaks (in minutes)
        $table->integer('time_worked')->nullable();   // Total worked time (including breaks, in minutes)
        $table->integer('overtime')->nullable(); // Overtime hours
        $table->integer('missing_time')->nullable();  // Missing hours to complete the workday
        //$table->timestamps(); // Crea automáticamente created_at y updated_at 
        $table->timestamp('created_at')->useCurrent();
        $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_control');
    }
};
