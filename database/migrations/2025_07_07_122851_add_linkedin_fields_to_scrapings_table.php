<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('scrapings', function (Blueprint $table) {
            $table->string('linkedin_username')->nullable();
            $table->string('linkedin_password')->nullable();
            $table->string('tasker_id')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scrapings', function (Blueprint $table) {
            $table->dropColumn(['linkedin_username', 'linkedin_password', 'tasker_id']);
        });
    }
};
