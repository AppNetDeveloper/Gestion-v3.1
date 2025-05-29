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
        Schema::table('scraping_tasks', function (Blueprint $table) {
            $table->unsignedSmallInteger('retry_attempts')->default(0)->after('status');
            $table->timestamp('last_attempt_at')->nullable()->after('retry_attempts');
            $table->timestamp('failed_at')->nullable()->after('last_attempt_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scraping_tasks', function (Blueprint $table) {
            $table->dropColumn(['retry_attempts', 'last_attempt_at', 'failed_at']);
        });
    }
};
