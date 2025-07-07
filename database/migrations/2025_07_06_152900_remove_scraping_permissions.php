<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RemoveScrapingPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Eliminar permisos relacionados con scraping
        DB::table('permissions')
            ->where('name', 'like', 'scrapingtasks %')
            ->delete();
            
        // Eliminar relaciones de permisos en la tabla pivot
        DB::table('role_has_permissions')
            ->whereIn('permission_id', function($query) {
                $query->select('id')
                    ->from('permissions')
                    ->where('name', 'like', 'scrapingtasks %');
            })
            ->delete();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No recreamos los permisos en caso de rollback
    }
}
