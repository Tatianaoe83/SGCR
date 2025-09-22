<?php

namespace App\Http\Controllers;

use App\Models\CuerpoCorreo;
use App\Mail\AccesoMail;
use App\Mail\AgradecimientoMail;
use App\Mail\ImplementacionMail;
use Illuminate\Http\Request;

class CuerpoCorreoController extends Controller
{
    public function index()
    {
        $cuerpos = CuerpoCorreo::orderBy('tipo')->orderBy('nombre')->paginate(10);
        return view('cuerpos-correo.index', compact('cuerpos'));
    }
    
    public function edit(int $id)
    {
        $tpl = \App\Models\CuerpoCorreo::findOrFail($id);

        $descripciones = [
            'acceso' => [
                '{{nombre}}'     => 'Nombre completo del usuario',
                '{{correo}}'     => 'Correo electrónico asignado',
                '{{contraseña}}' => 'Contraseña temporal generada',
                '{{link}}'       => 'Enlace para acceder al sistema'
            ],
            'implementacion' => [
                '{{elemento}}'   => 'Nombre del elemento implementado',
                '{{folio}}'      => 'Folio de la implementación',
                '{{link}}'       => 'Enlace al detalle del elemento'
            ],
            'agradecimiento' => [
                '{{elemento}}'   => 'Elemento por el cual se agradece',
                '{{link}}'       => 'Enlace al detalle del elemento'
            ],
            'fecha_vencimiento' => [
                '{{fecha}}'      => 'Fecha límite que tiene el procedimiento a verificar',
                '{{elemento}}'   => 'Nombre del elemento implementado',
                '{{folio}}'      => 'Folio de la implementación',
                '{{link}}'       => 'Enlace al detalle del elemento'
            ]
        ];

        $tpl->vars = $descripciones[$tpl->tipo] ?? [];

        return view('cuerpos-correo.edit', compact('tpl'));
    }

    public function show(int $id)
    {
        $tpl = CuerpoCorreo::findOrFail($id);

        $descripciones = [
            'acceso' => [
                '{{nombre}}'     => 'Nombre completo del usuario',
                '{{correo}}'     => 'Correo electrónico asignado',
                '{{contraseña}}' => 'Contraseña temporal generada',
                '{{link}}'       => 'Enlace para acceder al sistema'
            ],
            'implementacion' => [
                '{{elemento}}'   => 'Nombre del elemento implementado',
                '{{folio}}'      => 'Folio de la implementación',
                '{{link}}'       => 'Enlace al detalle del elemento'
            ],
            'agradecimiento' => [
                '{{elemento}}'   => 'Elemento por el cual se agradece',
                '{{link}}'       => 'Enlace al detalle del elemento'
            ],
            'fecha_vencimiento' => [
                '{{fecha}}'      => 'Fecha límite que tiene el procedimiento a verificar',
                '{{elemento}}'   => 'Nombre del elemento implementado',
                '{{folio}}'      => 'Folio de la implementación',
                '{{link}}'       => 'Enlace al detalle del elemento'
            ]
        ];

        $tpl->vars = $descripciones[$tpl->tipo] ?? [];

        return view('cuerpos-correo.preview', [
            'html' => $tpl->cuerpo_html,
            'tpl' => $tpl
        ]);
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

        $mailableClass = $this->templates[$tipo];

        return match ($tipo) {
            'acceso'       => (new $mailableClass())->render(),
            'implementacion' => (new $mailableClass())->render(),
            'agradecimiento' => (new $mailableClass())->render(),
            default        => (new $mailableClass())->render(),
        };
    }

    public function updateEditor(Request $request, int $id)
    {
        $tpl = CuerpoCorreo::findOrFail($id);
        $tpl->update([
            'cuerpo_html' => $request->input('html'),
            'cuerpo_texto' => strip_tags($request->input('html'))
        ]);
        return response()->json(['ok' => true]);
    }
}
