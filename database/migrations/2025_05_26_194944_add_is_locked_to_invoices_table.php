<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/YYYY_MM_DD_HHMMSS_add_is_locked_to_invoices_table.php
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('status');
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });
    }
};
