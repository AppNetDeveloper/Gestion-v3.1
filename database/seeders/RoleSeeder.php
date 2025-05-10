<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Definición de roles
        $roles = [
            'super-admin',
            'admin',
            'user', // Rol genérico, podrías definir permisos base
            'employee', // Empleado interno
            'manager', // Gerente/Supervisor
            'customer', // Cliente final
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Obtener todos los permisos para el super-admin
        $allWebPermissions = Permission::where('guard_name', 'web')->get();
        $superAdminWeb = Role::where(['name' => 'super-admin', 'guard_name' => 'web'])->firstOrFail();
        $superAdminWeb->syncPermissions($allWebPermissions);

        // Asignación de permisos al rol 'admin'
        // El admin tendrá la mayoría de los permisos, excepto quizás los de super-admin muy específicos
        $adminWeb = Role::where(['name' => 'admin', 'guard_name' => 'web'])->firstOrFail();
        $adminPermissions = [
            // User Management
            'user index', 'user create', 'user update', 'user delete', 'user show',
            // Role & Permission Management (quizás no todos los admins deberían tener esto)
            'role index', 'role update', 'role show',
            'permission index', 'permission update', 'permission show',
            // Menu Items
            'menu users_list', 'menu role_permission', 'menu role_permission_permissions', 'menu role_permission_roles', 'menu database_backup',
            // Company Data
            'company index', 'company create', 'company update', 'company delete', 'company show',
            // Time Control
            'timecontrolstatus index', 'timecontrolstatus create', 'timecontrolstatus update', 'timecontrolstatus delete', 'timecontrolstatus show',
            // Scraping
            'scrapingtasks index', 'scrapingtasks create', 'scrapingtasks store', 'scrapingtasks update', 'scrapingtasks delete', 'scrapingtasks show_contacts', 'menu scrapingtasks',
            // Server Monitor (Global)
            'servermonitorbusynes index', 'servermonitorbusynes create', 'servermonitorbusynes update', 'servermonitorbusynes delete', 'servermonitorbusynes show',
            // Calendars
            'labcalendar index', 'labcalendar create', 'labcalendar update', 'labcalendar delete', 'labcalendar show',
            'calendarindividual index', 'calendarindividual create', 'calendarindividual update', 'calendarindividual delete', 'calendarindividual show',

            // *** NUEVOS MÓDULOS DE GESTIÓN PARA ADMIN ***
            'services index', 'services create', 'services update', 'services delete', 'services show', 'menu services',
            'clients index', 'clients create', 'clients update', 'clients delete', 'clients show', 'menu clients',
            'quotes index', 'quotes create', 'quotes update', 'quotes delete', 'quotes show', 'quotes send_email', 'quotes export_pdf', 'quotes convert_to_invoice', 'menu quotes',
            'projects index', 'projects create', 'projects update', 'projects delete', 'projects show', 'menu projects',
            'tasks index', 'tasks create', 'tasks update', 'tasks delete', 'tasks show', 'tasks assign_users', 'tasks log_time', 'menu tasks',
            'invoices index', 'invoices create', 'invoices update', 'invoices delete', 'invoices show', 'invoices send_email', 'invoices export_pdf', 'menu invoices',
        ];
        $adminWeb->syncPermissions($adminPermissions);


        // Asignación de permisos al rol 'employee' (Empleado Interno)
        $employeeWeb = Role::where(['name' => 'employee', 'guard_name' => 'web'])->firstOrFail();
        $employeePermissions = [
            'user index', // Ver lista de usuarios (quizás solo ciertos tipos)
            'timecontrolstatus index', // Fichar
            // Permisos para gestionar el trabajo
            'clients index', 'clients create', 'clients update', 'clients show', 'menu clients',
            'quotes index', 'quotes create', 'quotes update', 'quotes show', 'quotes send_email', 'quotes export_pdf', 'quotes convert_to_invoice', 'menu quotes',
            'projects index', 'projects create', 'projects update', 'projects show', 'menu projects',
            'tasks index', 'tasks create', 'tasks update', 'tasks show', 'tasks assign_users', 'tasks log_time', 'menu tasks',
            'invoices index', 'invoices create', 'invoices update', 'invoices show', 'invoices send_email', 'invoices export_pdf', 'menu invoices',
            // Podrían tener permisos para ver sus propios servicios/tareas asignadas
            'services index', 'services show', // Ver servicios que pueden ofrecer
        ];
        $employeeWeb->syncPermissions($employeePermissions);


        // Asignación de permisos al rol 'customer' (Cliente)
        $customerWeb = Role::where(['name' => 'customer', 'guard_name' => 'web'])->firstOrFail();
        $customerPermissions = [
            // Permisos existentes que ya tenías
            'user index', // Para ver su propio perfil
            'servermonitor create', 'servermonitor update', 'servermonitor delete', 'servermonitor show', 'servermonitor index',
            'calendarindividual create', 'calendarindividual update', 'calendarindividual delete', 'calendarindividual show', 'calendarindividual index',
            // 'scrapingtasks index', 'scrapingtasks create', 'scrapingtasks store', 'scrapingtasks update', 'scrapingtasks delete', 'scrapingtasks show_contacts', 'menu scrapingtasks',

            // *** NUEVOS PERMISOS PARA CLIENTES ***
            'quotes index',         // Ver su lista de presupuestos
            'quotes show',          // Ver detalle de un presupuesto
            'quotes view_own',      // (Crucial para lógica de "solo los míos")
            'quotes export_pdf',    // Descargar sus PDF
            // 'quotes update',     // Si permites que acepten/rechacen online

            'projects index',       // Ver su lista de proyectos
            'projects show',        // Ver detalle de un proyecto
            'projects view_own',    // (Crucial)

            'tasks index',          // Ver tareas de sus proyectos
            'tasks show',           // Ver detalle de una tarea
            'tasks view_own',       // (Crucial, o tasks view_assigned)

            'invoices index',       // Ver su lista de facturas
            'invoices show',        // Ver detalle de una factura
            'invoices view_own',    // (Crucial)
            'invoices export_pdf',  // Descargar sus PDF

            // Menús relevantes para clientes
            'menu quotes',
            'menu projects',
            // 'menu tasks', // Quizás las tareas se ven dentro de proyectos
            'menu invoices',
        ];
        $customerWeb->syncPermissions($customerPermissions);


        // Rol 'user' (genérico, puedes dejarlo sin permisos específicos o darle unos básicos)
        $userWeb = Role::where(['name' => 'user', 'guard_name' => 'web'])->firstOrFail();
        // $userWeb->syncPermissions([]);

        // Rol 'manager' (puedes definirlo con más permisos que employee, menos que admin)
        $managerWeb = Role::where(['name' => 'manager', 'guard_name' => 'web'])->firstOrFail();
        // $managerWeb->syncPermissions([...]); // Similar a admin pero quizás sin gestión de usuarios/roles

        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Roles and permissions (including new modules) synced successfully.');
    }
}
