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
        Schema::create('time_control_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('time_control_status_id');  // Key to 'time_control_status'
            $table->unsignedBigInteger('permission_id'); // Key to your 'permissions' table (assuming you have one)
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('time_control_status_id')
                  ->references('id')
                  ->on('time_control_status')
                  ->onDelete('cascade'); // Adjust cascade behavior as needed 

            // Assuming you have a separate `permissions` table
            $table->foreign('permission_id')
                  ->references('id')
                  ->on('time_control_status') 
                  ->onDelete('cascade');  // Adjust cascade behavior as needed 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_control_rules');
    }
};
