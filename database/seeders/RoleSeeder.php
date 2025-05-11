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
            'user',
            'employee',
            'manager',
            'customer',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Obtener todos los permisos para el super-admin
        $allWebPermissions = Permission::where('guard_name', 'web')->get();
        $superAdminWeb = Role::where(['name' => 'super-admin', 'guard_name' => 'web'])->firstOrFail();
        $superAdminWeb->syncPermissions($allWebPermissions);

        // Asignación de permisos al rol 'admin'
        $adminWeb = Role::where(['name' => 'admin', 'guard_name' => 'web'])->firstOrFail();
        $adminPermissions = [
            // User Management
            'user index', 'user create', 'user update', 'user delete', 'user show',
            // Role & Permission Management
            'role index', 'role create', 'role update', 'role delete', 'role show', // Admins pueden necesitar crear roles
            'permission index', 'permission create', 'permission update', 'permission delete', 'permission show', // Admins pueden necesitar crear permisos
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

            // MÓDULOS DE GESTIÓN PARA ADMIN
            'services index', 'services create', 'services update', 'services delete', 'services show', 'menu services',
            'clients index', 'clients create', 'clients update', 'clients delete', 'clients show', 'menu clients',
            'quotes index', 'quotes create', 'quotes update', 'quotes delete', 'quotes show', 'quotes send_email', 'quotes export_pdf', 'quotes convert_to_invoice', 'quotes accept', 'quotes reject', 'menu quotes', // Añadido accept y reject
            'projects index', 'projects create', 'projects update', 'projects delete', 'projects show', 'projects assign_users', 'menu projects',
            'tasks index', 'tasks create', 'tasks update', 'tasks delete', 'tasks show', 'tasks assign_users', 'tasks log_time', 'menu tasks',
            'invoices index', 'invoices create', 'invoices update', 'invoices delete', 'invoices show', 'invoices send_email', 'invoices export_pdf', 'menu invoices',

            // *** PERMISOS DE TIME HISTORY PARA ADMIN ***
            'time_entries edit all',
            'time_entries delete all',
            'time_entries view all',
        ];
        $adminWeb->syncPermissions($adminPermissions);


        // Asignación de permisos al rol 'employee' (Empleado Interno)
        $employeeWeb = Role::where(['name' => 'employee', 'guard_name' => 'web'])->firstOrFail();
        $employeePermissions = [
            // 'user index', // Quizás solo ver su propio perfil o ciertos usuarios
            'timecontrolstatus index', // Fichar

            // Permisos para gestionar el trabajo asignado
            'clients index', 'clients show', // Ver clientes
            'quotes index', 'quotes show', 'quotes view_own', 'quotes export_pdf', // Ver sus presupuestos
            'projects index', 'projects show', 'projects view_own', // Ver sus proyectos
            'tasks index', 'tasks show', 'tasks view_own', 'tasks view_assigned', // Ver sus tareas
            'tasks log_time', // Registrar tiempo en sus tareas
            'invoices index', 'invoices show', 'invoices view_own', 'invoices export_pdf', // Ver sus facturas

            // *** PERMISOS DE TIME HISTORY PARA EMPLOYEE ***
            'time_entries edit own',
            'time_entries delete own',

            // Menús relevantes
            'menu quotes', 'menu projects', 'menu tasks', 'menu invoices',
        ];
        $employeeWeb->syncPermissions($employeePermissions);


        // Asignación de permisos al rol 'customer' (Cliente)
        $customerWeb = Role::where(['name' => 'customer', 'guard_name' => 'web'])->firstOrFail();
        $customerPermissions = [
            // 'user index', // Para ver su propio perfil (si UserPolicy lo permite)
            // Permisos existentes que ya tenías (revisar si siguen siendo necesarios)
            'servermonitor create', 'servermonitor update', 'servermonitor delete', 'servermonitor show', 'servermonitor index',
            'calendarindividual create', 'calendarindividual update', 'calendarindividual delete', 'calendarindividual show', 'calendarindividual index',

            // PERMISOS PARA CLIENTES
            'quotes index', 'quotes show', 'quotes view_own', 'quotes export_pdf', 'quotes accept', 'quotes reject',
            'projects index', 'projects show', 'projects view_own',
            'tasks index', 'tasks show', 'tasks view_own', // Clientes ven tareas de sus proyectos
            'invoices index', 'invoices show', 'invoices view_own', 'invoices export_pdf',

            // Menús relevantes para clientes
            'menu quotes', 'menu projects', 'menu invoices',
            // 'menu tasks', // Quizás las tareas se ven dentro de proyectos
        ];
        $customerWeb->syncPermissions($customerPermissions);


        // Rol 'user' (genérico, puedes dejarlo sin permisos específicos o darle unos básicos)
        $userWeb = Role::where(['name' => 'user', 'guard_name' => 'web'])->firstOrFail();
        // $userWeb->syncPermissions([]);

        // Rol 'manager' (puedes definirlo con más permisos que employee, menos que admin)
        $managerWeb = Role::where(['name' => 'manager', 'guard_name' => 'web'])->firstOrFail();
        $managerPermissions = array_merge($employeePermissions, [ // Hereda de empleado y añade más
            'projects create', 'projects update', 'projects delete', // Gestionar todos los proyectos
            'tasks create', 'tasks update', 'tasks delete', 'tasks assign_users', // Gestionar todas las tareas
            'quotes create', 'quotes update', 'quotes delete', 'quotes send_email', 'quotes convert_to_invoice', // Gestionar todos los presupuestos
            'clients create', 'clients update', 'clients delete', // Gestionar todos los clientes
            'time_entries view all', // Ver todo el historial de tiempos
            'time_entries edit all', // Editar cualquier entrada
            'time_entries delete all', // Eliminar cualquier entrada
        ]);
        $managerWeb->syncPermissions(array_unique($managerPermissions));


        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Roles and permissions (including TaskTimeHistory) synced successfully.');
    }
}
