<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class FixAdminSgcPermission extends Command
{
    protected $signature = 'fix:admin-sgc-permission';
    protected $description = 'Asigna el permiso sgc.access a todos los usuarios Administradores que no lo tengan';

    public function handle()
    {
        $this->info('🔧 Verificando y corrigiendo permisos SGC para Administradores...');

        // Asegurar que el permiso existe
        $permission = Permission::firstOrCreate(
            ['name' => 'sgc.access'],
            ['guard_name' => 'web']
        );

        // Asegurar que el rol Administrador tiene el permiso
        $adminRole = Role::where('name', 'Administrador')->first();
        
        if (!$adminRole) {
            $this->error('❌ El rol "Administrador" no existe.');
            return 1;
        }

        // Asignar el permiso al rol si no lo tiene
        if (!$adminRole->hasPermissionTo($permission)) {
            $adminRole->givePermissionTo($permission);
            $this->info('✅ Permiso sgc.access asignado al rol Administrador.');
        } else {
            $this->info('ℹ️  El rol Administrador ya tiene el permiso sgc.access.');
        }

        // Buscar todos los usuarios con rol Administrador
        $adminUsers = $adminRole->users;
        
        if ($adminUsers->isEmpty()) {
            $this->warn('⚠️  No se encontraron usuarios con rol Administrador.');
            return 0;
        }

        $this->info("\n📋 Usuarios Administradores encontrados: {$adminUsers->count()}");

        $fixed = 0;
        $alreadyHas = 0;

        foreach ($adminUsers as $user) {
            // Asignar el permiso directamente al usuario (no solo verificar)
            // Esto asegura que el permiso esté asignado incluso si hay problemas de caché
            if (!$user->hasDirectPermission('sgc.access')) {
                $user->givePermissionTo('sgc.access');
                $this->line("  ✅ Permiso asignado directamente a: {$user->name} ({$user->email})");
                $fixed++;
            } else {
                $this->line("  ℹ️  Ya tiene permiso directo: {$user->name} ({$user->email})");
                $alreadyHas++;
            }
        }

        // Limpiar caché de permisos para asegurar que los cambios se reflejen
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        $this->info("\n🔄 Caché de permisos limpiado.");

        $this->info("\n✨ Proceso completado:");
        $this->info("   - Usuarios corregidos: {$fixed}");
        $this->info("   - Usuarios que ya tenían el permiso: {$alreadyHas}");

        return 0;
    }
}

