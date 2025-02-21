<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignsTable extends Migration
{
    public function up()
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('prompt');
            $table->enum('status', ['pending', 'in_process', 'finished'])->default('pending');
            $table->dateTime('campaign_start')->nullable();
            $table->enum('model', ['whatsapp', 'email', 'sms', 'telegram']);
            $table->timestamps();

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('campaigns');
    }
}

