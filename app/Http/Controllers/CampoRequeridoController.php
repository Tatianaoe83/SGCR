<?php

namespace App\Http\Controllers;

use App\Models\CampoRequeridoTipoElemento;
use App\Models\TipoElemento;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CampoRequeridoController extends Controller
{
    /**
     * Obtener campos requeridos de un tipo de elemento
     */
    public function index(string $tipoElementoId): JsonResponse
    {
        $campos = CampoRequeridoTipoElemento::where('tipo_elemento_id', $tipoElementoId)
            ->orderBy('orden')
            ->get();
            
        return response()->json($campos);
    }

    /**
     * Guardar campos requeridos para un tipo de elemento
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'tipo_elemento_id' => 'required|exists:tipo_elementos,id_tipo_elemento',
            'campos' => 'required|array',
            'campos.*.campo_nombre' => 'required|string',
            'campos.*.campo_label' => 'required|string',
            'campos.*.es_requerido' => 'boolean',
            'campos.*.es_obligatorio' => 'boolean',
            'campos.*.orden' => 'integer'
        ]);

        $tipoElementoId = $request->tipo_elemento_id;
        
        // Eliminar campos existentes
        CampoRequeridoTipoElemento::where('tipo_elemento_id', $tipoElementoId)->delete();
        
        // Insertar nuevos campos
        $campos = collect($request->campos)->map(function ($campo, $index) use ($tipoElementoId) {
            return [
                'tipo_elemento_id' => $tipoElementoId,
                'campo_nombre' => $campo['campo_nombre'],
                'campo_label' => $campo['campo_label'],
                'es_requerido' => $campo['es_requerido'] ?? false,
                'es_obligatorio' => $campo['es_obligatorio'] ?? false,
                'orden' => $campo['orden'] ?? $index,
                'created_at' => now(),
                'updated_at' => now()
            ];
        })->toArray();
        
        CampoRequeridoTipoElemento::insert($campos);
        
        return response()->json([
            'message' => 'Campos requeridos guardados exitosamente',
            'campos' => CampoRequeridoTipoElemento::where('tipo_elemento_id', $tipoElementoId)->get()
        ]);
    }

    /**
     * Mostrar campos requeridos de un tipo de elemento específico
     */
    public function show(string $tipoElementoId): JsonResponse
    {
        $campos = CampoRequeridoTipoElemento::where('tipo_elemento_id', $tipoElementoId)
            ->orderBy('orden')
            ->get();
            
        return response()->json($campos);
    }

    /**
     * Actualizar campos requeridos de un tipo de elemento
     */
    public function update(Request $request, string $tipoElementoId): JsonResponse
    {
        return $this->store($request);
    }

    /**
     * Eliminar campos requeridos de un tipo de elemento
     */
    public function destroy(string $tipoElementoId): JsonResponse
    {
        CampoRequeridoTipoElemento::where('tipo_elemento_id', $tipoElementoId)->delete();
        
        return response()->json([
            'message' => 'Campos requeridos eliminados exitosamente'
        ]);
    }
}
