<?php

namespace App\Http\Controllers;

use App\Jobs\EnviarPropuestaMejoraMailJob;
use App\Models\ControlCambio;
use App\Models\Elemento;
use App\Models\Empleados;
use App\Models\PropuestaMejoras;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropuestaMejoraController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:propuestas_mejora.view')->only(['index', 'revision']);
        $this->middleware('permission:propuestas_mejora.create')->only(['store']);
    }

    public function index()
    {
        return view('propuesta_mejora.index');
    }

    public function getElementos(Request $request)
    {
        $elementos = Elemento::where('tipo_elemento_id', $request->tipo_id)
            ->where('active', true)
            ->where('status', 'Publicado')
            ->select('id_elemento as id', 'nombre_elemento as nombre')
            ->orderBy('nombre_elemento')
            ->get();

        return response()->json($elementos);
    }

    public function store(Request $request)
    {

        $user = auth()->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Usuario no autenticado.'], 401);
        }

        $empleado = Empleados::where('correo', $user->email)->first();

        if (!$empleado) {
            return response()->json(['success' => false, 'message' => 'Empleado no encontrado para el usuario autenticado.'], 404);
        }

        $request->validate([
            'titulo'        => 'required|string|max:255',
            'elemento_id'   => 'required|integer|exists:elementos,id_elemento',
            'justificacion' => 'required|string',
        ]);

        $propuesta = PropuestaMejoras::create([
            'titulo' => $request->titulo,
            'justificacion' => $request->justificacion,
            'estatus' => 'Pendiente',
            'id_elemento' => $request->elemento_id,
            'id_usuario_solicita' => $empleado->id_empleado,
        ]);

        EnviarPropuestaMejoraMailJob::dispatch($propuesta)->afterCommit();

        return response()->json(['success' => true, 'message' => 'Propuesta creada correctamente.']);
    }

    public function revision(PropuestaMejoras $propuesta)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $propuesta->load([
            'empleado:id_empleado,nombres,apellido_paterno,apellido_materno,correo',
            'elemento:id_elemento,nombre_elemento',
        ]);

        return view('propuesta_mejora.revision', compact('propuesta'));
    }

    public function aprobar(Request $request, PropuestaMejoras $propuesta)
    {
        $propuesta->update([
            'estatus' => 'Aprobado',
            'comentario' => $request->comentario,
        ]);

        $año = (int) now()->format('y');
        $baseAño = $año * 1000;

        $ultimoFolio = ControlCambio::where('FolioCambio', 'like', 'GC' . $año . '%')
            ->select(DB::raw('MAX(CAST(SUBSTRING(FolioCambio, 3) AS UNSIGNED)) as max_folio'))
            ->value('max_folio');

        $consecutivo = $ultimoFolio ? ($ultimoFolio - $baseAño) + 1 : 1;
        $folioNumerico = $baseAño + $consecutivo;

        ControlCambio::create([
            'id_elemento'     => $propuesta->id_elemento,
            'FolioCambio'     => 'GC' . $folioNumerico,
        ]);

        return redirect()->back()->with('success', 'Propuesta aprobada correctamente.');
    }

    public function rechazar(Request $request, PropuestaMejoras $propuesta)
    {
        $propuesta->update([
            'estatus' => 'Rechazado',
            'comentario' => $request->comentario,
        ]);

        return redirect()->back()->with('success', 'Propuesta rechazada.');
    }
}
