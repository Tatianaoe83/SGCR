<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permisos = [
        'comites.view',
        'comites.create',
        'comites.edit',
        'comites.delete',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permisos as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        foreach (['Super Administrador', 'Administrador'] as $rol) {
            Role::where('name', $rol)->first()?->givePermissionTo($this->permisos);
        }
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::whereIn('name', $this->permisos)->delete();
    }
};
