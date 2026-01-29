<?php

namespace App\Http\Controllers;

use App\Models\ControlCambio;

class ControlCambioController extends Controller
{
    public function index()
    {
        $cambios = ControlCambio::query()
            ->select([
                'id',
                'id_elemento',
                'FolioCambio',
                'Naturaleza',
                'DetalleStatus',
                'Prioridad',
            ])
            ->with([
                'elemento'
            ])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('control-cambios.index', compact('cambios'));
    }

    public function show(string $controID)
    {
        $cambios = ControlCambio::findOrFail($controID);
    }
}
