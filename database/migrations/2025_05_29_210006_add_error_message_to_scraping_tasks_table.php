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
        if (!Schema::hasColumn('scraping_tasks', 'error_message')) {
            Schema::table('scraping_tasks', function (Blueprint $table) {
                $table->text('error_message')->nullable()->after('api_task_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('scraping_tasks', 'error_message')) {
            Schema::table('scraping_tasks', function (Blueprint $table) {
                $table->dropColumn('error_message');
            });
        }
    }
};
