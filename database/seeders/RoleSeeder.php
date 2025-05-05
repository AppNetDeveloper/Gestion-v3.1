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
        // Definición de roles, incluyendo 'employee' (empleado en inglés) y 'manager'
        $roles = [
            'super-admin',
            'admin',
            'user',
            'employee',
            'manager',
            'customer',
        ];

        // Se crean los roles solo si no existen
        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role, 'guard_name' => 'web']
            );
        }

        // Asignación de permisos al rol 'super-admin'
        $superAdminWeb = Role::where(['name' => 'super-admin', 'guard_name' => 'web'])->firstOrFail();
        $superAdminWeb->syncPermissions(Permission::where('guard_name', 'web')->get());

        // Asignación de permisos al rol 'admin'
        $adminWeb = Role::where(['name' => 'admin', 'guard_name' => 'web'])->firstOrFail();
        $adminWeb->syncPermissions([
            // Permisos de usuario
            'user index',
            'user create',
            'user update',
            'user delete',
            'user show',
            // Permisos de rol
            'role index',
            'role update',
            'role show',
            // Permisos de permiso
            'permission index',
            'permission update',
            'permission show',
            // Permisos de menú
            'menu users_list',
            'menu role_permission',
            'menu role_permission_permissions',
            'menu role_permission_roles',
            // Permisos de Time Control Status
            'timecontrolstatus index',
            'timecontrolstatus create',
            'timecontrolstatus update',
            'timecontrolstatus delete',
            'timecontrolstatus show',
            // Permisos de Company Data
            'company index',
            'company create',
            'company update',
            'company delete',
            'company show',
            'scrapingtasks index',
            'scrapingtasks create',
            'scrapingtasks store',
            'scrapingtasks update',
            'scrapingtasks delete',
            'scrapingtasks show_contacts',
            'menu scrapingtasks',
        ]);

        // Asignación de permisos al rol 'employee'
        $employeeWeb = Role::where(['name' => 'employee', 'guard_name' => 'web'])->firstOrFail();
        $employeeWeb->syncPermissions([
            'user index',
            'timecontrolstatus index',
        ]);

        // Asignación de permisos al rol 'customer'
        $customerWeb = Role::where(['name' => 'customer', 'guard_name' => 'web'])->firstOrFail();
        $customerWeb->syncPermissions([
            'user index',
            'servermonitor create',
            'servermonitor update',
            'servermonitor delete',
            'servermonitor show',
            'servermonitor index',
            'calendarindividual create',
            'calendarindividual update',
            'calendarindividual delete',
            'calendarindividual show',
            'calendarindividual index',
            'scrapingtasks index',
            'scrapingtasks create',
            'scrapingtasks store',
            'scrapingtasks update',
            'scrapingtasks delete',
            'scrapingtasks show_contacts',
            'menu scrapingtasks',
        ]);


        // En este ejemplo, el rol 'user' no se le asignan permisos específicos,
        // pero se podría modificar de manera similar si fuera necesario.
        $userWeb = Role::where(['name' => 'user', 'guard_name' => 'web'])->firstOrFail();
        // $userWeb->syncPermissions([]); // Se puede descomentar y personalizar si se requiere
    }
}
