<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskerLinkedinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasker_linkedins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // User relationship
            $table->text('prompt');                // The prompt/message sent to the API
            $table->text('response')->nullable();  // The processed response (nullable until completed)
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending');           // Task status
            $table->text('error')->nullable();     // Any error message if processing fails
            $table->dateTime('publish_date')->nullable(); // Publication date (in English)
            $table->timestamps();

            // Foreign key relationship with users table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasker_linkedins');
    }
}
