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

            // Users
            ['name' => 'user create', 'module_name' => 'user'],
            ['name' => 'user update', 'module_name' => 'user'],
            ['name' => 'user delete', 'module_name' => 'user'],
            ['name' => 'user show',   'module_name' => 'user'],
            ['name' => 'user index',  'module_name' => 'user'],

            // Permissions
            ['name' => 'permission index',  'module_name' => 'permission'],
            ['name' => 'permission create', 'module_name' => 'permission'],
            ['name' => 'permission update', 'module_name' => 'permission'],
            ['name' => 'permission delete', 'module_name' => 'permission'],
            ['name' => 'permission show',   'module_name' => 'permission'],

            // Roles
            ['name' => 'role index',   'module_name' => 'role'],
            ['name' => 'role create',  'module_name' => 'role'],
            ['name' => 'role update',  'module_name' => 'role'],
            ['name' => 'role delete',  'module_name' => 'role'],
            ['name' => 'role show',    'module_name' => 'role'],

            // Database Backup
            ['name' => 'database_backup viewAny', 'module_name' => 'database_backup'],
            ['name' => 'database_backup create',    'module_name' => 'database_backup'],
            ['name' => 'database_backup delete',    'module_name' => 'database_backup'],
            ['name' => 'database_backup download',  'module_name' => 'database_backup'],

            // Menu Items
            ['name' => 'menu users_list', 'module_name' => 'menu'],
            ['name' => 'menu role_permission', 'module_name' => 'menu'],
            ['name' => 'menu role_permission_permissions', 'module_name' => 'menu'],
            ['name' => 'menu role_permission_roles', 'module_name' => 'menu'],
            ['name' => 'menu database_backup', 'module_name' => 'menu'],

            // Server Monitor Busynes (Global)
            ['name' => 'servermonitorbusynes create', 'module_name' => 'servermonitorbusynes'],
            ['name' => 'servermonitorbusynes update', 'module_name' => 'servermonitorbusynes'],
            ['name' => 'servermonitorbusynes delete', 'module_name' => 'servermonitorbusynes'],
            ['name' => 'servermonitorbusynes show',   'module_name' => 'servermonitorbusynes'],
            ['name' => 'servermonitorbusynes index',  'module_name' => 'servermonitorbusynes'],

            // Server Monitor (Own)
            ['name' => 'servermonitor create', 'module_name' => 'servermonitor'],
            ['name' => 'servermonitor update', 'module_name' => 'servermonitor'],
            ['name' => 'servermonitor delete', 'module_name' => 'servermonitor'],
            ['name' => 'servermonitor show',   'module_name' => 'servermonitor'],
            ['name' => 'servermonitor index',  'module_name' => 'servermonitor'],

            // Lab Calendar
            ['name' => 'labcalendar create', 'module_name' => 'labcalendar'],
            ['name' => 'labcalendar update', 'module_name' => 'labcalendar'],
            ['name' => 'labcalendar delete', 'module_name' => 'labcalendar'],
            ['name' => 'labcalendar show',   'module_name' => 'labcalendar'],
            ['name' => 'labcalendar index',  'module_name' => 'labcalendar'],

            // Individual Calendar
            ['name' => 'calendarindividual create', 'module_name' => 'calendarindividual'],
            ['name' => 'calendarindividual update', 'module_name' => 'calendarindividual'],
            ['name' => 'calendarindividual delete', 'module_name' => 'calendarindividual'],
            ['name' => 'calendarindividual show',   'module_name' => 'calendarindividual'],
            ['name' => 'calendarindividual index', 'module_name' => 'calendarindividual'],

            // *** NUEVOS PERMISOS PARA SCRAPING TASKS ***
            ['name' => 'scrapingtasks index',          'module_name' => 'scrapingtasks'], // Ver la lista de tareas
            ['name' => 'scrapingtasks create',         'module_name' => 'scrapingtasks'], // Poder crear nuevas tareas (implícito en store)
            ['name' => 'scrapingtasks store',          'module_name' => 'scrapingtasks'], // Guardar nuevas tareas
            ['name' => 'scrapingtasks update',         'module_name' => 'scrapingtasks'], // Actualizar tareas pendientes
            ['name' => 'scrapingtasks delete',         'module_name' => 'scrapingtasks'], // Eliminar tareas pendientes
            ['name' => 'scrapingtasks show_contacts',  'module_name' => 'scrapingtasks'], // Ver los contactos de una tarea completada

            // *** NUEVO PERMISO PARA MENÚ (si lo necesitas) ***
            ['name' => 'menu scrapingtasks', 'module_name' => 'menu'],

        ];

        foreach ($permissions as $permissionData) {
            // Usar firstOrCreate para evitar duplicados si el seeder se ejecuta más de una vez
            Permission::firstOrCreate(
                [
                    'name'       => $permissionData['name'],      // Condición para buscar
                    'guard_name' => 'web',                      // Condición para buscar
                ],
                [
                    'module_name' => $permissionData['module_name'], // Dato a insertar/actualizar si no existe
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
        }

         // Opcional: Limpiar caché de permisos después de añadir nuevos
         app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

         // Mensaje de éxito (opcional)
         $this->command->info('Permisos (incluyendo scrapingtasks) creados o verificados exitosamente.');
    }
}
