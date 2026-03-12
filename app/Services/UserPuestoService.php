<?php

namespace App\Services;

use App\Models\Empleados;
use App\Models\PuestoTrabajo;
use App\Models\User;

class UserPuestoService
{
    public function obtenerPuesto(?User $user): ?int
    {
        if (!$user) return null;

        return cache()->remember(
            "user_puesto_{$user->id}",
            3600,
            fn() => Empleados::whereRaw(
                "LOWER(TRIM(CONCAT(nombres,' ',apellido_paterno,' ',apellido_materno))) = ?",
                [mb_strtolower(trim($user->name))]
            )->value('puesto_trabajo_id')
        );
    }

    /**
     * 🆕 Verifica si el usuario tiene acceso total a todos los elementos
     * (Admin, Super Admin o Directora General)
     */
    public function tieneAccesoTotal(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // 1. Verificar roles admin
        if ($user->hasAnyRole('Super Administrador', 'Administrador')) {
            return true;
        }

        // 2. Verificar si es Directora General
        $puestoId = $this->obtenerPuesto($user);
        
        if (!$puestoId) {
            return false;
        }

        return cache()->remember(
            "user_es_directora_{$user->id}",
            3600,
            function () use ($puestoId) {
                $puesto = PuestoTrabajo::find($puestoId);
                return $puesto && 
                       mb_strtolower(trim($puesto->nombre)) === 'directora general';
            }
        );
    }
}