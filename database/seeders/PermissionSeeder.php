<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // Permisos de Time control status (Existentes)
            ['name' => 'timecontrolstatus create', 'module_name' => 'timecontrolstatus'],
            ['name' => 'timecontrolstatus update', 'module_name' => 'timecontrolstatus'],
            ['name' => 'timecontrolstatus delete', 'module_name' => 'timecontrolstatus'],
            ['name' => 'timecontrolstatus show',   'module_name' => 'timecontrolstatus'],
            ['name' => 'timecontrolstatus index',  'module_name' => 'timecontrolstatus'],

            // Company date (Existentes)
            ['name' => 'company create', 'module_name' => 'company'],
            ['name' => 'company update', 'module_name' => 'company'],
            ['name' => 'company delete', 'module_name' => 'company'],
            ['name' => 'company show',   'module_name' => 'company'],
            ['name' => 'company index',  'module_name' => 'company'],

            // Users (Existentes)
            ['name' => 'user create', 'module_name' => 'user'],
            ['name' => 'user update', 'module_name' => 'user'],
            ['name' => 'user delete', 'module_name' => 'user'],
            ['name' => 'user show',   'module_name' => 'user'],
            ['name' => 'user index',  'module_name' => 'user'],

            // Permissions (Existentes)
            ['name' => 'permission index',  'module_name' => 'permission'],
            ['name' => 'permission create', 'module_name' => 'permission'],
            ['name' => 'permission update', 'module_name' => 'permission'],
            ['name' => 'permission delete', 'module_name' => 'permission'],
            ['name' => 'permission show',   'module_name' => 'permission'],

            // Roles (Existentes)
            ['name' => 'role index',   'module_name' => 'role'],
            ['name' => 'role create',  'module_name' => 'role'],
            ['name' => 'role update',  'module_name' => 'role'],
            ['name' => 'role delete',  'module_name' => 'role'],
            ['name' => 'role show',    'module_name' => 'role'],

            // Database Backup (Existentes)
            ['name' => 'database_backup viewAny', 'module_name' => 'database_backup'],
            ['name' => 'database_backup create',    'module_name' => 'database_backup'],
            ['name' => 'database_backup delete',    'module_name' => 'database_backup'],
            ['name' => 'database_backup download',  'module_name' => 'database_backup'],

            // Menu Items (Existentes)
            ['name' => 'menu users_list', 'module_name' => 'menu'],
            ['name' => 'menu role_permission', 'module_name' => 'menu'],
            ['name' => 'menu role_permission_permissions', 'module_name' => 'menu'],
            ['name' => 'menu role_permission_roles', 'module_name' => 'menu'],
            ['name' => 'menu database_backup', 'module_name' => 'menu'],

            // Server Monitor Busynes (Global) (Existentes)
            ['name' => 'servermonitorbusynes create', 'module_name' => 'servermonitorbusynes'],
            ['name' => 'servermonitorbusynes update', 'module_name' => 'servermonitorbusynes'],
            ['name' => 'servermonitorbusynes delete', 'module_name' => 'servermonitorbusynes'],
            ['name' => 'servermonitorbusynes show',   'module_name' => 'servermonitorbusynes'],
            ['name' => 'servermonitorbusynes index',  'module_name' => 'servermonitorbusynes'],

            // Server Monitor (Own) (Existentes)
            ['name' => 'servermonitor create', 'module_name' => 'servermonitor'],
            ['name' => 'servermonitor update', 'module_name' => 'servermonitor'],
            ['name' => 'servermonitor delete', 'module_name' => 'servermonitor'],
            ['name' => 'servermonitor show',   'module_name' => 'servermonitor'],
            ['name' => 'servermonitor index',  'module_name' => 'servermonitor'],

            // Lab Calendar (Existentes)
            ['name' => 'labcalendar create', 'module_name' => 'labcalendar'],
            ['name' => 'labcalendar update', 'module_name' => 'labcalendar'],
            ['name' => 'labcalendar delete', 'module_name' => 'labcalendar'],
            ['name' => 'labcalendar show',   'module_name' => 'labcalendar'],
            ['name' => 'labcalendar index',  'module_name' => 'labcalendar'],

            // Individual Calendar (Existentes)
            ['name' => 'calendarindividual create', 'module_name' => 'calendarindividual'],
            ['name' => 'calendarindividual update', 'module_name' => 'calendarindividual'],
            ['name' => 'calendarindividual delete', 'module_name' => 'calendarindividual'],
            ['name' => 'calendarindividual show',   'module_name' => 'calendarindividual'],
            ['name' => 'calendarindividual index', 'module_name' => 'calendarindividual'],

            // Scraping Tasks (Existentes)
            ['name' => 'scrapingtasks index',          'module_name' => 'scrapingtasks'],
            ['name' => 'scrapingtasks create',         'module_name' => 'scrapingtasks'],
            ['name' => 'scrapingtasks store',          'module_name' => 'scrapingtasks'],
            ['name' => 'scrapingtasks update',         'module_name' => 'scrapingtasks'],
            ['name' => 'scrapingtasks delete',         'module_name' => 'scrapingtasks'],
            ['name' => 'scrapingtasks show_contacts',  'module_name' => 'scrapingtasks'],
            ['name' => 'menu scrapingtasks', 'module_name' => 'menu'],

            // Services (Servicios)
            ['name' => 'services index',  'module_name' => 'services'],
            ['name' => 'services create', 'module_name' => 'services'],
            ['name' => 'services update', 'module_name' => 'services'],
            ['name' => 'services delete', 'module_name' => 'services'],
            ['name' => 'services show',   'module_name' => 'services'],

            // Clients (Clientes)
            ['name' => 'clients index',  'module_name' => 'clients'],
            ['name' => 'clients create', 'module_name' => 'clients'],
            ['name' => 'clients update', 'module_name' => 'clients'],
            ['name' => 'clients delete', 'module_name' => 'clients'],
            ['name' => 'clients show',   'module_name' => 'clients'],

            // Quotes (Presupuestos)
            ['name' => 'quotes index',  'module_name' => 'quotes'],
            ['name' => 'quotes create', 'module_name' => 'quotes'],
            ['name' => 'quotes update', 'module_name' => 'quotes'],
            ['name' => 'quotes delete', 'module_name' => 'quotes'],
            ['name' => 'quotes show',   'module_name' => 'quotes'],
            ['name' => 'quotes send_email', 'module_name' => 'quotes'],
            ['name' => 'quotes export_pdf', 'module_name' => 'quotes'],
            ['name' => 'quotes convert_to_invoice', 'module_name' => 'quotes'],
            ['name' => 'quotes view_own', 'module_name' => 'quotes'],
            ['name' => 'quotes accept', 'module_name' => 'quotes'], // Permiso para aceptar
            ['name' => 'quotes reject', 'module_name' => 'quotes'], // Permiso para rechazar

            // Projects (Proyectos)
            ['name' => 'projects index',  'module_name' => 'projects'],
            ['name' => 'projects create', 'module_name' => 'projects'],
            ['name' => 'projects update', 'module_name' => 'projects'],
            ['name' => 'projects delete', 'module_name' => 'projects'],
            ['name' => 'projects show',   'module_name' => 'projects'],
            ['name' => 'projects view_own', 'module_name' => 'projects'],
            ['name' => 'projects assign_users', 'module_name' => 'projects'], // Para asignar usuarios a proyectos

            // Tasks (Tareas)
            ['name' => 'tasks index',  'module_name' => 'tasks'], // Listar tareas (general o de un proyecto)
            ['name' => 'tasks create', 'module_name' => 'tasks'],
            ['name' => 'tasks update', 'module_name' => 'tasks'],
            ['name' => 'tasks delete', 'module_name' => 'tasks'],
            ['name' => 'tasks show',   'module_name' => 'tasks'],
            ['name' => 'tasks assign_users', 'module_name' => 'tasks'], // Asignar usuarios a tareas
            ['name' => 'tasks log_time', 'module_name' => 'tasks'],     // Iniciar/detener temporizador
            ['name' => 'tasks view_own', 'module_name' => 'tasks'],     // Ver sus propias tareas asignadas
            ['name' => 'tasks view_assigned', 'module_name' => 'tasks'],// Ver tareas asignadas (similar a view_own)

            // *** NUEVOS PERMISOS PARA TASK TIME HISTORY ***
            ['name' => 'time_entries edit own', 'module_name' => 'time_history'],
            ['name' => 'time_entries delete own', 'module_name' => 'time_history'],
            ['name' => 'time_entries edit all', 'module_name' => 'time_history'], // Para managers/admins
            ['name' => 'time_entries delete all', 'module_name' => 'time_history'], // Para managers/admins
            ['name' => 'time_entries view all', 'module_name' => 'time_history'], // Para ver historial de todos

            // Invoices (Facturas)
            ['name' => 'invoices index',  'module_name' => 'invoices'],
            ['name' => 'invoices create', 'module_name' => 'invoices'],
            ['name' => 'invoices update', 'module_name' => 'invoices'],
            ['name' => 'invoices delete', 'module_name' => 'invoices'],
            ['name' => 'invoices show',   'module_name' => 'invoices'],
            ['name' => 'invoices send_email', 'module_name' => 'invoices'],
            ['name' => 'invoices export_pdf', 'module_name' => 'invoices'],
            ['name' => 'invoices view_own', 'module_name' => 'invoices'],

            // Menus
            ['name' => 'menu services', 'module_name' => 'menu'],
            ['name' => 'menu clients', 'module_name' => 'menu'],
            ['name' => 'menu quotes', 'module_name' => 'menu'],
            ['name' => 'menu projects', 'module_name' => 'menu'],
            ['name' => 'menu tasks', 'module_name' => 'menu'], // Para "Mis Tareas"
            ['name' => 'menu invoices', 'module_name' => 'menu'],
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

         app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
         $this->command->info('Permissions (including TaskTimeHistory) created or verified successfully.');
    }
}
