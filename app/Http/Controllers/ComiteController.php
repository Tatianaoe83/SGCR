<?php

namespace App\Http\Controllers;

use App\Models\Elemento;
use App\Models\PuestoTrabajo;
use App\Models\Relaciones;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComiteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:comites.view')->only(['index', 'data', 'show']);
        $this->middleware('permission:comites.create')->only(['create', 'store']);
        $this->middleware('permission:comites.edit')->only(['edit', 'update']);
        $this->middleware('permission:comites.delete')->only(['destroy']);
    }

    public function index(): View
    {
        return view('comites.index');
    }

    public function data()
    {
        $comites = Relaciones::with('elemento:id_elemento,nombre_elemento,folio_elemento')
            ->select(['relacionID', 'nombreRelacion', 'elementoID', 'puestos_trabajo', 'created_at']);

        $puestos = PuestoTrabajo::pluck('nombre', 'id_puesto_trabajo');

        return datatables()->of($comites)
            ->addColumn('elemento', function ($comite) {
                if (!$comite->elemento) {
                    return '<span class="text-slate-400 italic">Elemento no disponible</span>';
                }

                $folio = $comite->elemento->folio_elemento
                    ? '<span class="text-xs text-slate-500 block">' . e($comite->elemento->folio_elemento) . '</span>'
                    : '';

                return '<span class="font-medium text-slate-800 dark:text-slate-100">' . e($comite->elemento->nombre_elemento) . '</span>' . $folio;
            })
            ->filterColumn('elemento', function ($query, $keyword) {
                $query->whereHas('elemento', function ($q) use ($keyword) {
                    $q->where('nombre_elemento', 'like', "%{$keyword}%")
                        ->orWhere('folio_elemento', 'like', "%{$keyword}%");
                });
            })
            ->addColumn('puestos', function ($comite) use ($puestos) {
                $nombres = collect($this->puestosIds($comite->puestos_trabajo))
                    ->map(fn($id) => $puestos[$id] ?? null)
                    ->filter();

                if ($nombres->isEmpty()) {
                    return '<span class="text-slate-400 italic">Sin puestos</span>';
                }

                return $nombres->map(fn($nombre) => '<span class="inline-block px-2 py-1 mr-1 mb-1 text-xs font-semibold bg-[#E8EEF5] text-[#021D49] rounded">'
                    . e($nombre) .
                    '</span>')->implode('');
            })
            ->editColumn(
                'created_at',
                fn($comite) => $comite->created_at ? Carbon::parse($comite->created_at)->format('d/m/Y g:i a') : 'N/A'
            )
            ->addColumn(
                'acciones',
                fn($comite) => view('comites.partials-actions', compact('comite'))->render()
            )
            ->rawColumns(['elemento', 'puestos', 'acciones'])
            ->make(true);
    }

    public function create(): View
    {
        return view('comites.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        Relaciones::create($this->validated($request));

        return redirect()->route('comites.index')
            ->with('success', 'Comité creado exitosamente.');
    }

    public function show(string $id): View
    {
        $comite = Relaciones::with('elemento.tipoElemento')->findOrFail($id);
        $puestos = PuestoTrabajo::whereIn('id_puesto_trabajo', $this->puestosIds($comite->puestos_trabajo))
            ->orderBy('nombre')
            ->get(['id_puesto_trabajo', 'nombre']);

        return view('comites.show', compact('comite', 'puestos'));
    }

    public function edit(string $id): View
    {
        $comite = Relaciones::findOrFail($id);

        return view('comites.edit', ['comite' => $comite] + $this->formData());
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $comite = Relaciones::findOrFail($id);
        $comite->update($this->validated($request, $comite));

        return redirect()->route('comites.index')
            ->with('success', 'Comité actualizado exitosamente.');
    }

    public function destroy(string $id): RedirectResponse
    {
        Relaciones::findOrFail($id)->delete();

        return redirect()->route('comites.index')
            ->with('success', 'Comité eliminado exitosamente.');
    }

    private function formData(): array
    {
        return [
            'elementos' => Elemento::orderBy('nombre_elemento')
                ->get(['id_elemento', 'nombre_elemento', 'folio_elemento']),
            'puestosTrabajo' => PuestoTrabajo::orderBy('nombre')
                ->get(['id_puesto_trabajo', 'nombre']),
        ];
    }

    private function validated(Request $request, ?Relaciones $comite = null): array
    {
        $datos = $request->validate([
            'nombreRelacion' => [
                'required',
                'string',
                'max:200',
                // El formulario de elementos identifica cada comité por elemento + nombre.
                Rule::unique('puestos_relacion', 'nombreRelacion')
                    ->where('elementoID', $request->input('elementoID'))
                    ->whereNull('deleted_at')
                    ->ignore($comite?->relacionID, 'relacionID'),
            ],
            'elementoID' => ['required', 'integer', Rule::exists(Elemento::class, 'id_elemento')->whereNull('deleted_at')],
            'puestos_trabajo' => ['required', 'array', 'min:1'],
            'puestos_trabajo.*' => ['integer', Rule::exists(PuestoTrabajo::class, 'id_puesto_trabajo')],
        ], [
            'nombreRelacion.required' => 'El nombre del comité es obligatorio.',
            'nombreRelacion.unique' => 'Ya existe un comité con ese nombre para el elemento seleccionado.',
            'elementoID.required' => 'Selecciona el elemento al que pertenece el comité.',
            'puestos_trabajo.required' => 'Selecciona al menos un puesto de trabajo.',
        ]);

        $datos['puestos_trabajo'] = array_values(array_unique(array_map('intval', $datos['puestos_trabajo'])));

        return $datos;
    }

    private function puestosIds($valor): array
    {
        $ids = is_array($valor) ? $valor : (json_decode((string) $valor, true) ?? []);

        return array_map('intval', $ids);
    }
}
