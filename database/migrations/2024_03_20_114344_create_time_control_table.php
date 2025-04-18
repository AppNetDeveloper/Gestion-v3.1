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
        //agregamos el tiempo de medico y total tiempo medico
        $table->integer('time_doctor')->nullable(); // Time spent by the doctor (in minutes)
        $table->integer('total_time_doctor')->nullable();  // Total time spent by the doctor (in minutes)
        // agragamos tiempo smoking y total tiempo smoking
        $table->integer('time_smoking')->nullable(); // Time spent smoking (in minutes)
        $table->integer('total_time_smoking')->nullable();  // Total time spent smoking (in minutes)
        //anadimos 2 campos nuevos tiempo en moviemiento con el coche y total tiempo en moviemiento con el coche
        $table->integer('time_in_vehicle')->nullable(); // Time spent in a vehicle (in minutes)
        $table->integer('total_time_in_vehicle')->nullable();  // Total time spent in a vehicle (in minutes)
        //anadimos tambien 2campos kilometros recorridos y total kilometros recorridos
        $table->integer('distance_traveled')->nullable(); // Distance traveled (in kilometers)
        $table->integer('total_distance_traveled')->nullable();  // Total distance traveled (in kilometers)
        //anadimos 2 campos precio comida y total precio comida
        $table->integer('food_cost')->nullable(); // Cost of food (in dollars)
        $table->integer('total_food_cost')->nullable();  // Total cost of food (in dollars)
        
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
