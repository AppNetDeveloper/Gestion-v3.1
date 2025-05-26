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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('verifactu_hash')->nullable()->after('verifactu_id');
            $table->text('verifactu_signature')->nullable()->after('verifactu_hash');
            $table->timestamp('verifactu_timestamp')->nullable()->after('verifactu_signature');
            
            // Índice para búsquedas por hash
            $table->index('verifactu_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['verifactu_hash']);
            
            $table->dropColumn([
                'verifactu_hash',
                'verifactu_signature',
                'verifactu_timestamp'
            ]);
        });
    }
};
