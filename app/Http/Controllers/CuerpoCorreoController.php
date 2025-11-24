<?php

namespace App\Http\Controllers;

use App\Models\CuerpoCorreo;
use App\Mail\AccesoMail;
use App\Mail\AgradecimientoMail;
use App\Mail\ImplementacionMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CuerpoCorreoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:cuerpo-correo.view')->only(['index', 'show',]);
        $this->middleware('permission:cuerpo-correo.edit')->only(['edit', 'update']);
        $this->middleware('permission:cuerpo-correo.create')->only(['create', 'store']);
        $this->middleware('permission:cuerpo-correo.export')->only(['export']);
    }

    /**
     * Definición de variables por tipo de correo
     */
    private function getVariableDefinitions()
    {
        return [
            'acceso' => [
                '{{nombre}}'     => 'Nombre completo del usuario',
                '{{correo}}'     => 'Correo electrónico asignado',
                '{{contraseña}}' => 'Contraseña temporal generada',
                '{{link}}'       => 'Enlace para acceder al sistema',
                '{{puesto}}'     => 'Puesto de trabajo del usuario',
                '{{fecha_ingreso}}' => 'Fecha de ingreso del usuario'
            ],
            'implementacion' => [
                '{{elemento}}'   => 'Nombre del elemento implementado',
                '{{folio}}'      => 'Folio de la implementación',
                '{{link}}'       => 'Enlace al detalle del elemento',
                '{{responsable}}' => 'Nombre del responsable',
                '{{fecha_implementacion}}' => 'Fecha de implementación',
                '{{area}}'       => 'Área responsable'
            ],
            'agradecimiento' => [
                '{{elemento}}'   => 'Elemento por el cual se agradece',
                '{{link}}'       => 'Enlace al detalle del elemento',
                '{{responsable}}' => 'Nombre del responsable',
                '{{fecha}}'      => 'Fecha del agradecimiento'
            ],
            'fecha_vencimiento' => [
                '{{fecha}}'      => 'Fecha límite que tiene el procedimiento a verificar',
                '{{elemento}}'   => 'Nombre del elemento implementado',
                '{{folio}}'      => 'Folio de la implementación',
                '{{link}}'       => 'Enlace al detalle del elemento',
                '{{responsable}}' => 'Nombre del responsable',
                '{{dias_restantes}}' => 'Días restantes para la revisión'
            ]
        ];
    }

    /**
     * Mostrar listado de cuerpos de correo con filtros
     */
    public function index(Request $request)
    {
        $query = CuerpoCorreo::query();

        // Filtros
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('estado')) {
            $query->where('activo', $request->estado === '1');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('tipo', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'tipo');
        $sortDirection = $request->get('sort_direction', 'asc');

        if (in_array($sortBy, ['nombre', 'tipo', 'activo', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $cuerpos = $query->paginate(15)->withQueryString();

        return view('cuerpos-correo.index', compact('cuerpos'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(int $id)
    {
        $tpl = CuerpoCorreo::findOrFail($id);
        $variableDefinitions = $this->getVariableDefinitions();
        $tpl->vars = $variableDefinitions[$tpl->tipo] ?? [];

        return view('cuerpos-correo.edit', compact('tpl'));
    }

    /**
     * Crear nuevo cuerpo de correo
     */
    public function create()
    {
        $tipos = CuerpoCorreo::getTipos();
        return view('cuerpos-correo.create', compact('tipos'));
    }

    /**
     * Guardar nuevo cuerpo de correo
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|string|in:acceso,implementacion,agradecimiento,fecha_vencimiento',
            'subject' => 'nullable|string|max:255',
            'cuerpo_html' => 'required|string',
            'activo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        CuerpoCorreo::create([
            'nombre' => $request->nombre,
            'tipo' => $request->tipo,
            'subject' => $request->subject,
            'cuerpo_html' => $request->cuerpo_html,
            'cuerpo_texto' => strip_tags($request->cuerpo_html),
            'activo' => $request->boolean('activo', true)
        ]);

        return redirect()->route('cuerpos-correo.index')
            ->with('success', 'Plantilla de correo creada exitosamente.');
    }

    /**
     * Mostrar vista detallada de una plantilla
     */
    public function show(int $id)
    {
        $tpl = CuerpoCorreo::findOrFail($id);
        $variableDefinitions = $this->getVariableDefinitions();
        $tpl->vars = $variableDefinitions[$tpl->tipo] ?? [];

        return view('cuerpos-correo.show', compact('tpl'));
    }

    /**
     * Vista previa de plantilla (método original mantenido para compatibilidad)
     */
    public function previewTemplate(int $id)
    {
        $tpl = CuerpoCorreo::findOrFail($id);
        $variableDefinitions = $this->getVariableDefinitions();
        $tpl->vars = $variableDefinitions[$tpl->tipo] ?? [];

        // Generar HTML procesado con datos de ejemplo
        $processedHtml = $this->processTemplateWithSampleData($tpl);

        return view('cuerpos-correo.preview', [
            'html' => $processedHtml,
            'tpl' => $tpl
        ]);
    }

    /**
     * Procesar template con datos de ejemplo para vista previa
     */
    private function processTemplateWithSampleData(CuerpoCorreo $tpl): string
    {
        $htmlContent = $tpl->cuerpo_html;

        // Datos de ejemplo según el tipo de correo
        $sampleData = $this->getSampleDataForType($tpl->tipo);

        // Reemplazar variables con datos de ejemplo
        foreach ($sampleData as $variable => $value) {
            $htmlContent = str_replace($variable, $value, $htmlContent);
        }

        return $htmlContent;
    }

    /**
     * Obtener datos de ejemplo según el tipo de correo
     */
    private function getSampleDataForType(string $tipo): array
    {
        $baseUrl = config('app.url', 'http://localhost');

        switch ($tipo) {
            case 'acceso':
                return [
                    '{{nombre}}' => 'Juan Pérez García',
                    '{{correo}}' => 'juan.perez@empresa.com',
                    '{{contraseña}}' => 'TempPass123',
                    '{{link}}' => rtrim($baseUrl, '/') . '/login',
                    '{{puesto}}' => 'Analista de Calidad',
                    '{{fecha_ingreso}}' => '15/01/2024'
                ];
            case 'implementacion':
                return [
                    '{{elemento}}' => 'Procedimiento de Control de Calidad',
                    '{{folio}}' => 'IMP-2024-001',
                    '{{link}}' => rtrim($baseUrl, '/') . '/elementos/1',
                    '{{responsable}}' => 'María González López',
                    '{{fecha_implementacion}}' => '20/01/2024',
                    '{{area}}' => 'Control de Calidad'
                ];
            case 'agradecimiento':
                return [
                    '{{elemento}}' => 'Procedimiento de Seguridad',
                    '{{link}}' => rtrim($baseUrl, '/') . '/elementos/2',
                    '{{responsable}}' => 'Carlos Rodríguez Martínez',
                    '{{fecha}}' => '25/01/2024'
                ];
            case 'fecha_vencimiento':
                return [
                    '{{fecha}}' => '15/02/2024',
                    '{{elemento}}' => 'Manual de Procedimientos',
                    '{{folio}}' => 'MAN-2024-003',
                    '{{link}}' => rtrim($baseUrl, '/') . '/elementos/3',
                    '{{responsable}}' => 'Ana López Sánchez',
                    '{{dias_restantes}}' => '15'
                ];
            default:
                return [];
        }
    }

    protected array $templates = [
        'acceso'       => \App\Mail\AccesoMail::class,
        'implementacion' => ImplementacionMail::class,
        'agradecimiento' => AgradecimientoMail::class
    ];

    public function preview(string $tipo)
    {
        if (!array_key_exists($tipo, $this->templates)) {
            abort(404, 'Template no encontrado');
        }

        // Obtener el template de la base de datos
        $tpl = CuerpoCorreo::where('tipo', $tipo)->first();

        if (!$tpl) {
            abort(404, 'Template no encontrado en la base de datos');
        }

        // Procesar el template con datos de ejemplo
        $processedHtml = $this->processTemplateWithSampleData($tpl);

        return response($processedHtml)->header('Content-Type', 'text/html');
    }

    /**
     * Actualizar plantilla desde el editor
     */
    public function updateEditor(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'html' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'HTML inválido'], 422);
        }

        $tpl = CuerpoCorreo::findOrFail($id);
        $tpl->update([
            'cuerpo_html' => $request->input('html'),
            'cuerpo_texto' => strip_tags($request->input('html'))
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Cambiar estado de una plantilla
     */
    public function toggleStatus(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'activo' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Datos inválidos'], 422);
        }

        $tpl = CuerpoCorreo::findOrFail($id);
        $tpl->update(['activo' => $request->boolean('activo')]);

        $status = $request->boolean('activo') ? 'activada' : 'desactivada';
        return response()->json([
            'ok' => true,
            'message' => "Plantilla {$status} exitosamente"
        ]);
    }

    /**
     * Obtener estadísticas de uso de variables
     */
    public function getVariableStats(int $id)
    {
        $tpl = CuerpoCorreo::findOrFail($id);
        $variableDefinitions = $this->getVariableDefinitions();
        $availableVariables = $variableDefinitions[$tpl->tipo] ?? [];

        $stats = [];
        foreach ($availableVariables as $variable => $description) {
            $count = substr_count($tpl->cuerpo_html, $variable);
            $stats[] = [
                'variable' => $variable,
                'description' => $description,
                'count' => $count,
                'used' => $count > 0
            ];
        }

        return response()->json($stats);
    }

    /**
     * Duplicar una plantilla
     */
    public function duplicate(int $id)
    {
        $originalTpl = CuerpoCorreo::findOrFail($id);

        $newTpl = $originalTpl->replicate();
        $newTpl->nombre = $originalTpl->nombre . ' (Copia)';
        $newTpl->save();

        return redirect()->route('cuerpos-correo.edit', $newTpl->id_cuerpo)
            ->with('success', 'Plantilla duplicada exitosamente. Puedes editarla ahora.');
    }

    /**
     * Exportar plantilla como HTML
     */
    public function export(int $id)
    {
        $tpl = CuerpoCorreo::findOrFail($id);

        $filename = Str::slug($tpl->nombre) . '.html';

        return response($tpl->cuerpo_html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Validar plantilla
     */
    public function validateTemplate(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'html' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['valid' => false, 'errors' => $validator->errors()], 422);
        }

        $tpl = CuerpoCorreo::findOrFail($id);
        $variableDefinitions = $this->getVariableDefinitions();
        $availableVariables = array_keys($variableDefinitions[$tpl->tipo] ?? []);

        $html = $request->input('html');
        $issues = [];

        // Verificar variables no utilizadas
        foreach ($availableVariables as $variable) {
            if (!str_contains($html, $variable)) {
                $issues[] = [
                    'type' => 'unused_variable',
                    'message' => "Variable no utilizada: {$variable}",
                    'severity' => 'warning'
                ];
            }
        }

        // Verificar variables mal formateadas
        preg_match_all('/\{\{[^}]+\}\}/', $html, $matches);
        foreach ($matches[0] as $match) {
            if (!in_array($match, $availableVariables)) {
                $issues[] = [
                    'type' => 'unknown_variable',
                    'message' => "Variable desconocida: {$match}",
                    'severity' => 'error'
                ];
            }
        }

        // Verificar estructura HTML básica
        if (!str_contains($html, '<html') && !str_contains($html, '<body')) {
            $issues[] = [
                'type' => 'missing_structure',
                'message' => 'Falta estructura HTML básica (html, body)',
                'severity' => 'warning'
            ];
        }

        return response()->json([
            'valid' => empty(array_filter($issues, fn($issue) => $issue['severity'] === 'error')),
            'issues' => $issues
        ]);
    }
}
