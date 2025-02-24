<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // Permisos de Time control status
            ['name' => 'timecontrolstatus create', 'module_name' => 'timecontrolstatus'],
            ['name' => 'timecontrolstatus update', 'module_name' => 'timecontrolstatus'],
            ['name' => 'timecontrolstatus delete', 'module_name' => 'timecontrolstatus'],
            ['name' => 'timecontrolstatus show',   'module_name' => 'timecontrolstatus'],
            ['name' => 'timecontrolstatus index',  'module_name' => 'timecontrolstatus'],

            // Company date
            ['name' => 'company create', 'module_name' => 'company'],
            ['name' => 'company update', 'module_name' => 'company'],
            ['name' => 'company delete', 'module_name' => 'company'],
            ['name' => 'company show',   'module_name' => 'company'],
            ['name' => 'company index',  'module_name' => 'company'],

            ['name' => 'user create', 'module_name' => 'user'],
            ['name' => 'user update', 'module_name' => 'user'],
            ['name' => 'user delete', 'module_name' => 'user'],
            ['name' => 'user show',   'module_name' => 'user'],
            ['name' => 'user index',  'module_name' => 'user'],

            ['name' => 'permission index',  'module_name' => 'permission'],
            ['name' => 'permission create', 'module_name' => 'permission'],
            ['name' => 'permission update', 'module_name' => 'permission'],
            ['name' => 'permission delete', 'module_name' => 'permission'],
            ['name' => 'permission show',   'module_name' => 'permission'],

            ['name' => 'role index',   'module_name' => 'role'],
            ['name' => 'role create',  'module_name' => 'role'],
            ['name' => 'role update',  'module_name' => 'role'],
            ['name' => 'role delete',  'module_name' => 'role'],
            ['name' => 'role show',    'module_name' => 'role'],

            ['name' => 'database_backup viewAny', 'module_name' => 'database_backup'],
            ['name' => 'database_backup create',    'module_name' => 'database_backup'],
            ['name' => 'database_backup delete',    'module_name' => 'database_backup'],
            ['name' => 'database_backup download',  'module_name' => 'database_backup'],

            ['name' => 'menu users_list', 'module_name' => 'menu'],
            ['name' => 'menu role_permission', 'module_name' => 'menu'],
            ['name' => 'menu role_permission_permissions', 'module_name' => 'menu'],
            ['name' => 'menu role_permission_roles', 'module_name' => 'menu'],
            ['name' => 'menu database_backup', 'module_name' => 'menu'],

            // Monitor de servidores empresa (globales)
            ['name' => 'servermonitorbusynes create', 'module_name' => 'servermonitorbusynes'],
            ['name' => 'servermonitorbusynes update', 'module_name' => 'servermonitorbusynes'],
            ['name' => 'servermonitorbusynes delete', 'module_name' => 'servermonitorbusynes'],
            ['name' => 'servermonitorbusynes show',   'module_name' => 'servermonitorbusynes'],
            ['name' => 'servermonitorbusynes index',  'module_name' => 'servermonitorbusynes'],

            // Monitor de servidores propios
            ['name' => 'servermonitor create', 'module_name' => 'servermonitor'],
            ['name' => 'servermonitor update', 'module_name' => 'servermonitor'],
            ['name' => 'servermonitor delete', 'module_name' => 'servermonitor'],
            ['name' => 'servermonitor show',   'module_name' => 'servermonitor'],
            ['name' => 'servermonitor index',  'module_name' => 'servermonitor'],

            //permisos para calendario laboral lab calendar
            ['name' => 'labcalendar create', 'module_name' => 'labcalendar'],
            ['name' => 'labcalendar update', 'module_name' => 'labcalendar'],
            ['name' => 'labcalendar delete', 'module_name' => 'labcalendar'],
            ['name' => 'labcalendar show',   'module_name' => 'labcalendar'],
            ['name' => 'labcalendar index',  'module_name' => 'labcalendar'],

            //permisos para calendario individuales calendar individual
            ['name' => 'calendarindividual create', 'module_name' => 'calendarindividual'],
            ['name' => 'calendarindividual update', 'module_name' => 'calendarindividual'],
            ['name' => 'calendarindividual delete', 'module_name' => 'calendarindividual'],
            ['name' => 'calendarindividual show',   'module_name' => 'calendarindividual'],
            ['name' => 'calendarindividual index', 'module_name' => 'calendarindividual'],

        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                [
                    'name'       => $permissionData['name'],
                    'guard_name' => 'web',
                ],
                [
                    'module_name' => $permissionData['module_name'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
        }
    }
}
