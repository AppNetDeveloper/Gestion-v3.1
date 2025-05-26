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
        Schema::table('ollama_taskers', function (Blueprint $table) {
            $table->text('callback_url')->nullable()->after('error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ollama_taskers', function (Blueprint $table) {
            $table->dropColumn('callback_url');
        });
    }
};
