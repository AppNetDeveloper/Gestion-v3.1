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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('address')->nullable();
            $table->string('document_number')->nullable();

            $table->unsignedBigInteger('job_posicion_id')->nullable();
            $table->foreign('job_posicion_id')->references('id')->on('job_posicion')->onDelete('cascade');

            $table->unsignedBigInteger('type_of_contract_id')->nullable();
            $table->foreign('type_of_contract_id')->references('id')->on('type_of_contract')->onDelete('cascade');

            $table->unsignedBigInteger('shift_id')->nullable();
            $table->foreign('shift_id')->references('id')->on('shift')->onDelete('cascade');
            $table->string('point_control_enable', 1)->default('1')->nullable();

            $table->string('time_control_enable', 1)->default('1')->nullable();

            $table->date('birthdate')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('pin')->unique()->nullable();

            $table->rememberToken();

            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
