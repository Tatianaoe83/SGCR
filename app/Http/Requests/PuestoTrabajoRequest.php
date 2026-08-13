<?php

namespace App\Http\Requests;

use App\Models\Area;
use App\Models\PuestoTrabajo;
use App\Models\UnidadNegocio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PuestoTrabajoRequest extends FormRequest
{
    /** @var PuestoTrabajo|null|false false = aun no resuelto */
    private $puestoResuelto = false;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $unidades = $this->input('unidades_negocio_ids');

        // Compatibilidad con formularios/integraciones que aun envian una sola unidad.
        if (empty($unidades) && $this->filled('unidad_negocio_id')) {
            $unidades = [$this->input('unidad_negocio_id')];
        }

        $this->merge([
            'unidades_negocio_ids' => $this->soloEnteros($unidades),
            'areas_ids' => $this->soloEnteros($this->input('areas_ids')),
            'puesto_trabajo_id' => $this->filled('puesto_trabajo_id')
                ? (int) $this->input('puesto_trabajo_id')
                : null,
        ]);
    }

    public function rules(): array
    {
        $reglas = [
            'nombre' => ['required', 'string', 'max:255'],
            'puesto_trabajo_id' => [
                'nullable',
                'integer',
                Rule::exists('puesto_trabajos', 'id_puesto_trabajo')->whereNull('deleted_at'),
            ],
        ];

        if ($this->puestoEsGlobal()) {
            return $reglas;
        }

        return $reglas + [
            'division_id' => ['required', 'integer', 'exists:divisions,id_division'],
            'unidades_negocio_ids' => array_filter([
                'required',
                'array',
                'min:1',
                // El nivel sale de la primera palabra del nombre del puesto.
                PuestoTrabajo::nombrePermiteMultiplesUnidades($this->input('nombre')) ? null : 'max:1',
            ]),
            'unidades_negocio_ids.*' => ['integer', 'exists:unidad_negocios,id_unidad_negocio'],
            'areas_ids' => ['required', 'array', 'min:1'],
            'areas_ids.*' => ['integer', 'exists:area,id_area'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validarJefeDirecto($validator);

            if ($this->puestoEsGlobal() || $validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validarUnidadesDeLaDivision($validator);
            $this->validarAreasDeLasUnidades($validator);
        });
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del puesto es obligatorio.',
            'division_id.required' => 'La división es obligatoria.',
            'division_id.exists' => 'La división seleccionada no existe.',
            'unidades_negocio_ids.required' => 'Debes seleccionar al menos una unidad de negocio.',
            'unidades_negocio_ids.min' => 'Debes seleccionar al menos una unidad de negocio.',
            'unidades_negocio_ids.max' => 'Solo los puestos de Director, Subdirector o Gerente pueden tener más de una unidad de negocio.',
            'unidades_negocio_ids.*.exists' => 'Una de las unidades de negocio seleccionadas no existe.',
            'areas_ids.required' => 'Debes seleccionar al menos un área.',
            'areas_ids.min' => 'Debes seleccionar al menos un área.',
            'areas_ids.*.exists' => 'Una de las áreas seleccionadas no existe.',
            'puesto_trabajo_id.exists' => 'El jefe directo seleccionado no existe.',
        ];
    }

    /**
     * Los puestos globales aplican a toda la organización, su estructura no se edita.
     */
    public function puestoEsGlobal(): bool
    {
        $puesto = $this->puestoEnEdicion();

        return $puesto !== null && $puesto->isGlobal();
    }

    public function puestoEnEdicion(): ?PuestoTrabajo
    {
        if ($this->puestoResuelto !== false) {
            return $this->puestoResuelto;
        }

        $id = collect($this->route()?->parameters() ?? [])->first();

        $this->puestoResuelto = $id instanceof PuestoTrabajo
            ? $id
            : ($id ? PuestoTrabajo::find($id) : null);

        return $this->puestoResuelto;
    }

    private function validarJefeDirecto(Validator $validator): void
    {
        $jefeId = $this->input('puesto_trabajo_id');
        $puesto = $this->puestoEnEdicion();

        if ($jefeId && $puesto && (int) $jefeId === (int) $puesto->id_puesto_trabajo) {
            $validator->errors()->add(
                'puesto_trabajo_id',
                'Un puesto no puede ser su propio jefe directo.'
            );
        }
    }

    private function validarUnidadesDeLaDivision(Validator $validator): void
    {
        $divisionId = (int) $this->input('division_id');
        $unidadesIds = $this->input('unidades_negocio_ids', []);

        $ajenas = UnidadNegocio::whereIn('id_unidad_negocio', $unidadesIds)
            ->where('division_id', '!=', $divisionId)
            ->pluck('nombre');

        if ($ajenas->isNotEmpty()) {
            $validator->errors()->add(
                'unidades_negocio_ids',
                'Estas unidades de negocio no pertenecen a la división seleccionada: ' . $ajenas->implode(', ') . '.'
            );
        }
    }

    private function validarAreasDeLasUnidades(Validator $validator): void
    {
        $unidadesIds = $this->input('unidades_negocio_ids', []);
        $areasIds = $this->input('areas_ids', []);

        $ajenas = Area::whereIn('id_area', $areasIds)
            ->whereNotIn('unidad_negocio_id', $unidadesIds)
            ->pluck('nombre');

        if ($ajenas->isNotEmpty()) {
            $validator->errors()->add(
                'areas_ids',
                'Estas áreas no pertenecen a las unidades de negocio seleccionadas: ' . $ajenas->implode(', ') . '.'
            );
        }
    }

    /**
     * @return array<int, int>
     */
    private function soloEnteros($valores): array
    {
        if (!is_array($valores)) {
            return [];
        }

        $enteros = array_filter($valores, static fn($valor) => is_numeric($valor));

        return array_values(array_unique(array_map('intval', $enteros)));
    }
}
