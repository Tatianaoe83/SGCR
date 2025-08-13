<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos básicos
        $permissions = [
            // Usuarios
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            
            // Roles
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            
            // Permisos
            'permissions.view',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',
            
            // Divisiones
            'divisions.view',
            'divisions.create',
            'divisions.edit',
            'divisions.delete',
            
            // Unidades de Negocio
            'unidades-negocios.view',
            'unidades-negocios.create',
            'unidades-negocios.edit',
            'unidades-negocios.delete',
            
            // Áreas
            'areas.view',
            'areas.create',
            'areas.edit',
            'areas.delete',
            
            // Puestos de Trabajo
            'puestos-trabajo.view',
            'puestos-trabajo.create',
            'puestos-trabajo.edit',
            'puestos-trabajo.delete',
            'puestos-trabajo.import',
            'puestos-trabajo.export',
            
            // Empleados
            'empleados.view',
            'empleados.create',
            'empleados.edit',
            'empleados.delete',
            'empleados.import',
            'empleados.export',
            
            // Dashboard
            'dashboard.view',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Crear roles básicos
        $superAdmin = Role::create(['name' => 'Super Administrador']);
        $admin = Role::create(['name' => 'Administrador']);
        $manager = Role::create(['name' => 'Gerente']);
        $user = Role::create(['name' => 'Usuario']);

        // Asignar todos los permisos al Super Administrador
        $superAdmin->givePermissionTo(Permission::all());

        // Asignar permisos al Administrador
        $admin->givePermissionTo([
            'users.view', 'users.create', 'users.edit',
            'roles.view', 'roles.create', 'roles.edit',
            'permissions.view',
            'divisions.view', 'divisions.create', 'divisions.edit', 'divisions.delete',
            'unidades-negocios.view', 'unidades-negocios.create', 'unidades-negocios.edit', 'unidades-negocios.delete',
            'areas.view', 'areas.create', 'areas.edit', 'areas.delete',
            'puestos-trabajo.view', 'puestos-trabajo.create', 'puestos-trabajo.edit', 'puestos-trabajo.delete', 'puestos-trabajo.import', 'puestos-trabajo.export',
            'empleados.view', 'empleados.create', 'empleados.edit', 'empleados.delete', 'empleados.import', 'empleados.export',
            'dashboard.view',
        ]);

        // Asignar permisos al Gerente
        $manager->givePermissionTo([
            'users.view',
            'divisions.view', 'divisions.create', 'divisions.edit',
            'unidades-negocios.view', 'unidades-negocios.create', 'unidades-negocios.edit',
            'areas.view', 'areas.create', 'areas.edit',
            'puestos-trabajo.view', 'puestos-trabajo.create', 'puestos-trabajo.edit', 'puestos-trabajo.import', 'puestos-trabajo.export',
            'empleados.view', 'empleados.create', 'empleados.edit', 'empleados.import', 'empleados.export',
            'dashboard.view',
        ]);

        // Asignar permisos básicos al Usuario
        $user->givePermissionTo([
            'divisions.view',
            'unidades-negocios.view',
            'areas.view',
            'puestos-trabajo.view',
            'empleados.view',
            'dashboard.view',
        ]);

        // Crear usuario super administrador por defecto
        $superAdminUser = User::create([
            'name' => 'Tatiana Ordoñez',
            'email' => 'tordonez@proser.com.mx',
            'password' => bcrypt('12345678'),
        ]);

        $superAdminUser->assignRole($superAdmin);

        $this->command->info('Roles y permisos creados exitosamente!');
        $this->command->info('Usuario Super Administrador creado: tordonez@proser.com.mx / 12345678');
    }
}
