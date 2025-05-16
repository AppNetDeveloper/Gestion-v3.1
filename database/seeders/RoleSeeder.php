<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Definición de roles
        $roles = [
            'super-admin',
            'admin',
            'manager',
            'employee',
            'user',
            'customer',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 1. Super Admin: TODOS los permisos (incluidos WhatsApp/Telegram general y menú)
        $allWebPermissions = Permission::where('guard_name', 'web')->get();
        $superAdminWeb = Role::where(['name' => 'super-admin', 'guard_name' => 'web'])->firstOrFail();
        $superAdminWeb->syncPermissions($allWebPermissions);

        // 2. Admin: Permisos de gestión completos (puedes ajustar según necesidades)
        $adminWeb = Role::where(['name' => 'admin', 'guard_name' => 'web'])->firstOrFail();
        $adminPermissions = [
            // Gestión de usuarios, roles y permisos
            'user index', 'user create', 'user update', 'user delete', 'user show',
            'role index', 'role create', 'role update', 'role delete', 'role show',
            'permission index', 'permission create', 'permission update', 'permission delete', 'permission show',

            // Menús administrativos
            'menu users_list', 'menu role_permission', 'menu role_permission_permissions', 'menu role_permission_roles', 'menu database_backup',
            'menu services', 'menu clients', 'menu quotes', 'menu projects', 'menu tasks', 'menu invoices',
            'menu whatsapp-general', 'menu telegram-general',

            // Módulos empresa
            'company index', 'company create', 'company update', 'company delete', 'company show',

            // Control horario, servidores, calendario
            'timecontrolstatus index', 'timecontrolstatus create', 'timecontrolstatus update', 'timecontrolstatus delete', 'timecontrolstatus show',
            'scrapingtasks index', 'scrapingtasks create', 'scrapingtasks store', 'scrapingtasks update', 'scrapingtasks delete', 'scrapingtasks show_contacts', 'menu scrapingtasks',
            'servermonitorbusynes index', 'servermonitorbusynes create', 'servermonitorbusynes update', 'servermonitorbusynes delete', 'servermonitorbusynes show',
            'servermonitor index', 'servermonitor create', 'servermonitor update', 'servermonitor delete', 'servermonitor show', 'menu servermonitor', 'menu servermonitorbusynes',
            'database_backup viewAny', 'database_backup create', 'database_backup delete', 'database_backup download',

            // Calendarios
            'labcalendar index', 'labcalendar create', 'labcalendar update', 'labcalendar delete', 'labcalendar show', 'menu labcalendar',
            'calendarindividual index', 'calendarindividual create', 'calendarindividual update', 'calendarindividual delete', 'calendarindividual show', 'menu calendarindividual',

            // Módulos de gestión
            'services index', 'services create', 'services update', 'services delete', 'services show',
            'clients index', 'clients create', 'clients update', 'clients delete', 'clients show',
            'quotes index', 'quotes create', 'quotes update', 'quotes delete', 'quotes show', 'quotes send_email', 'quotes export_pdf', 'quotes convert_to_invoice', 'quotes accept', 'quotes reject',
            'projects index', 'projects create', 'projects update', 'projects delete', 'projects show', 'projects assign_users',
            'tasks index', 'tasks create', 'tasks update', 'tasks delete', 'tasks show', 'tasks assign_users', 'tasks log_time',
            'invoices index', 'invoices create', 'invoices update', 'invoices delete', 'invoices show', 'invoices send_email', 'invoices export_pdf',

            // Time History
            'time_entries edit all', 'time_entries delete all', 'time_entries view all',

            // WhatsApp General Empresa (canal general)
            'whatsapp-general index', 'whatsapp-general show', 'whatsapp-general create', 'whatsapp-general update', 'whatsapp-general delete',

            // Telegram General Empresa (canal general)
            'telegram-general index', 'telegram-general show', 'telegram-general create', 'telegram-general update', 'telegram-general delete',
        ];
        $adminWeb->syncPermissions($adminPermissions);

        // 3. Manager: Más que employee, menos que admin (puedes ampliar si quieres)
        $managerWeb = Role::where(['name' => 'manager', 'guard_name' => 'web'])->firstOrFail();
        $managerPermissions = array_merge([
            // Acceso WhatsApp/Telegram general SI LO DESEAS
            'menu whatsapp-general', 'whatsapp-general index', 'whatsapp-general show',
            'menu telegram-general', 'telegram-general index', 'telegram-general show',

            // Proyectos/Tareas/Gestión
            'projects create', 'projects update', 'projects delete',
            'tasks create', 'tasks update', 'tasks delete', 'tasks assign_users',
            'quotes create', 'quotes update', 'quotes delete', 'quotes send_email', 'quotes convert_to_invoice',
            'clients create', 'clients update', 'clients delete',
            'time_entries view all', 'time_entries edit all', 'time_entries delete all',
        ], [
            // Hereda de employee
            'timecontrolstatus index',
            'clients index', 'clients show',
            'quotes index', 'quotes show', 'quotes view_own', 'quotes export_pdf',
            'projects index', 'projects show', 'projects view_own',
            'tasks index', 'tasks show', 'tasks view_own', 'tasks view_assigned', 'tasks log_time',
            'invoices index', 'invoices show', 'invoices view_own', 'invoices export_pdf',
            'time_entries edit own', 'time_entries delete own',
            'menu quotes', 'menu projects', 'menu tasks', 'menu invoices',
        ]);
        $managerWeb->syncPermissions(array_unique($managerPermissions));

        // 4. Employee: Acceso solo a sus propios datos/tareas
        $employeeWeb = Role::where(['name' => 'employee', 'guard_name' => 'web'])->firstOrFail();
        $employeePermissions = [
            'timecontrolstatus index',
            'clients index', 'clients show',
            'quotes index', 'quotes show', 'quotes view_own', 'quotes export_pdf',
            'projects index', 'projects show', 'projects view_own',
            'tasks index', 'tasks show', 'tasks view_own', 'tasks view_assigned', 'tasks log_time',
            'invoices index', 'invoices show', 'invoices view_own', 'invoices export_pdf',
            'time_entries edit own', 'time_entries delete own',
            'menu quotes', 'menu projects', 'menu tasks', 'menu invoices',
        ];
        $employeeWeb->syncPermissions($employeePermissions);

        // 5. User: Básico o vacío (lo puedes personalizar)
        $userWeb = Role::where(['name' => 'user', 'guard_name' => 'web'])->firstOrFail();
        // $userWeb->syncPermissions([]);

        // 6. Customer: Acceso a lo suyo
        $customerWeb = Role::where(['name' => 'customer', 'guard_name' => 'web'])->firstOrFail();
        $customerPermissions = [
            'servermonitor create', 'servermonitor update', 'servermonitor delete', 'servermonitor show', 'servermonitor index',
            'calendarindividual create', 'calendarindividual update', 'calendarindividual delete', 'calendarindividual show', 'calendarindividual index',
            'quotes index', 'quotes show', 'quotes view_own', 'quotes export_pdf', 'quotes accept', 'quotes reject',
            'projects index', 'projects show', 'projects view_own',
            'tasks index', 'tasks show', 'tasks view_own',
            'invoices index', 'invoices show', 'invoices view_own', 'invoices export_pdf',
            'menu quotes', 'menu projects', 'menu invoices',
        ];
        $customerWeb->syncPermissions($customerPermissions);

        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Roles and permissions synced successfully.');
    }
}
