<?php

namespace App\Imports;

use App\Models\PuestoTrabajo;
use App\Models\Division;
use App\Models\UnidadNegocio;
use App\Models\Area;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PuestosTrabajoImport implements ToCollection, WithHeadingRow, WithValidation
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        // Verificar si el archivo está vacío
        if ($collection->isEmpty()) {
            throw new \Exception('El archivo está vacío. Por favor, verifica que el archivo contenga datos.');
        }

        $processedRows = 0;
        $skippedRows = 0;

        foreach ($collection as $row) {
            // Verificar si la fila tiene todos los datos requeridos
            if (empty(trim($row['nombre_del_puesto'] ?? '')) || 
                empty(trim($row['division'] ?? '')) || 
                empty(trim($row['unidad_de_negocio'] ?? '')) || 
                empty(trim($row['area'] ?? ''))) {
                $skippedRows++;
                continue; // Saltar filas vacías o incompletas
            }

            // Buscar las entidades relacionadas por nombre
            $division = Division::where('nombre', trim($row['division']))->first();
            $unidadNegocio = UnidadNegocio::where('nombre', trim($row['unidad_de_negocio']))->first();
            $area = Area::where('nombre', trim($row['area']))->first();

            // Solo crear si todas las entidades relacionadas existen
            if ($division && $unidadNegocio && $area) {
                PuestoTrabajo::updateOrCreate(
                    [
                        'nombre' => trim($row['nombre_del_puesto']),
                        'division_id' => $division->id_division,
                        'unidad_negocio_id' => $unidadNegocio->id_unidad_negocio,
                        'area_id' => $area->id_area,
                    ],
                    [
                        'nombre' => trim($row['nombre_del_puesto']),
                        'division_id' => $division->id_division,
                        'unidad_negocio_id' => $unidadNegocio->id_unidad_negocio,
                        'area_id' => $area->id_area,
                    ]
                );
                $processedRows++;
            } else {
                $skippedRows++;
            }
        }

        // Si no se procesó ninguna fila, mostrar mensaje
        if ($processedRows == 0) {
            throw new \Exception("No se pudo procesar ninguna fila. Filas omitidas: $skippedRows. Verifica que las divisiones, unidades de negocio y áreas existan en la base de datos.");
        }
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'nombre_del_puesto' => 'required|string|max:255',
            'division' => 'required|string|exists:divisions,nombre',
            'unidad_de_negocio' => 'required|string|exists:unidad_negocios,nombre',
            'area' => 'required|string|exists:area,nombre',
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'nombre_del_puesto.required' => 'El nombre del puesto es obligatorio.',
            'division.required' => 'La división es obligatoria.',
            'division.exists' => 'La división especificada no existe.',
            'unidad_de_negocio.required' => 'La unidad de negocio es obligatoria.',
            'unidad_de_negocio.exists' => 'La unidad de negocio especificada no existe.',
            'area.required' => 'El área es obligatoria.',
            'area.exists' => 'El área especificada no existe.',
        ];
    }
}
