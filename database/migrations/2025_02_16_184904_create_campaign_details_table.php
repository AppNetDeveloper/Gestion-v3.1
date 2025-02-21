<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignDetailsTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     */
    public function up()
    {
        Schema::create('campaign_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('ollama_tasker_id')->nullable();
            $table->text('text')->nullable();
            $table->timestamps();

            // Llaves foráneas
            $table->foreign('campaign_id')
                  ->references('id')->on('campaigns')
                  ->onDelete('cascade');

            $table->foreign('contact_id')
                  ->references('id')->on('contacts')
                  ->onDelete('cascade');

            $table->foreign('ollama_tasker_id')
                  ->references('id')->on('ollama_taskers')
                  ->onDelete('cascade');
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down()
    {
        Schema::dropIfExists('campaign_details');
    }
}
