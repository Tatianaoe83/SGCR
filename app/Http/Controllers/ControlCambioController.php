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
        $cambios = ControlCambio::with('elemento')
            ->findOrFail($controID);

        return view('control-cambios.show', compact('cambios'));
    }

    public function edit(string $id)
    {
        $cambios = ControlCambio::with(['elemento'])->findOrFail($id);

        return view('control-cambios.edit', compact('cambios'));
    }
}
