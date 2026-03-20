<?php

namespace App\Http\Controllers;

use App\Exports\ControlCambiosExport;
use App\Models\ControlCambio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ControlCambioController extends Controller
{
    public function __construct()
    {
        // Control de cambios pertenece a la sección SGC; se bloquea por permiso.
        $this->middleware('permission:sgc.access')->only([
            'index',
            'show',
            'edit',
            'update',
            'destroy',
        ]);
    }

    public function index()
    {
        $cambios = ControlCambio::query()
            ->select([
                'id',
                'id_elemento',
                'FolioCambio',
                'Naturaleza',
                'Afectacion',
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

    public function update(Request $request, string $id): RedirectResponse
    {
        $cambios = ControlCambio::findOrFail($id);

        $request->validate([
            'Naturaleza'      => 'nullable|string|max:255',
            'Afectacion'      => 'nullable|string|max:255',
            'Prioridad'       => 'nullable|integer|min:1|max:4',
            'Descripcion'     => 'nullable|string',
            'DetalleCambio'   => 'nullable|string',
            'RedaccionCambio' => 'nullable|string',
            'Seguimiento'     => 'nullable|string',
            'HistorialStatus' => 'nullable|string',
        ]);

        $cambios->update([
            'Naturaleza'      => $request->input('Naturaleza'),
            'Afectacion'      => $request->input('Afectacion'),
            'Prioridad'       => $request->input('Prioridad'),
            'Descripcion'     => $request->input('Descripcion'),
            'DetalleStatus'   => $request->input('DetalleCambio'),
            'RedaccionCambio' => $request->input('RedaccionCambio'),
            'Seguimiento'     => $request->input('Seguimiento'),
            'HistorialStatus' => $request->input('HistorialStatus'),
        ]);

        return redirect()
            ->route('control-cambios.index')
            ->with('success', 'Control de cambio actualizado correctamente.');
    }

    public function export()
    {
        return Excel::download(
            new ControlCambiosExport,
            'control-cambios.xlsx'
        );
    }
}
