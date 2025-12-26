<?php

namespace App\Services;

use App\Models\Empleados;
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
}
