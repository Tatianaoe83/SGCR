<?php

namespace App\Imports;

use App\Models\PuestoTrabajo;
use App\Models\Area;
use App\Models\Elemento;
use App\Models\TipoElemento;
use App\Models\TipoProceso;
use App\Models\UnidadNegocio;
use Illuminate\Container\Attributes\Log;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ElementosImport implements ToCollection, WithHeadingRow
{

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        if ($collection->isEmpty()) {
            throw new \Exception('El archivo está vacío. Por favor, verifica que el archivo contenga datos.');
        }

        $processedRows = 0;
        $skippedRows = 0;

        foreach ($collection as $row) {

            if (
                empty(trim($row['NombreTipoElemento'] ?? '')) || empty(trim($row['NombreElemento'] ?? '')) ||
                empty(trim($row['NombreProceso'] ?? '')) || empty(trim($row['UnidadNegocio'] ?? '')) ||
                empty(trim($row['UbicacionX'] ?? '')) || empty(trim($row['Control'] ?? '')) ||
                empty(trim($row['FolioElemento'] ?? '')) || empty(trim($row['VersionElemento'] ?? '')) ||
                empty(trim($row['FechaElemento'] ?? '')) || empty(trim($row['PeriodoRevision'] ?? '')) ||
                empty(trim($row['PuestoResponsable'] ?? '')) || empty(trim($row['PuestosRelacionados'] ?? '')) ||
                empty(trim($row['EsFormato'] ?? '')) || empty(trim($row['ArchivoFormato'] ?? '')) ||
                empty(trim($row['PuestoEjecutor'] ?? '')) || empty(trim($row['PuestoResguardo'] ?? '')) ||
                empty(trim($row['MedioSoporte'] ?? '')) || empty(trim($row['UbicacionResguardo'] ?? '')) ||
                empty(trim($row['ElementosPadre'] ?? '')) || empty(trim($row['ElementoRelacionados'] ?? '')) ||
                empty(trim($row['CorreoImplementacion'] ?? '')) || empty(trim($row['CorreoAgradecimiento'] ?? ''))
            ) {
                $skippedRows++;
                continue;
            }

            $tipoElemento = TipoElemento::where('nombre', trim($row['NombreTipoElemento']))->first();
            $tipoProceso = TipoProceso::where('nombre', trim($row['TipoProceso']))->first();
            $unidadNegocio = UnidadNegocio::where('nombre', trim($row['UnidadNegocio']))->first();
            $responsable = PuestoTrabajo::where('nombre', trim($row['PuestoResponsable']))->first();
            $ejecutor = PuestoTrabajo::where('nombre', trim($row['PuestoEjecutor']))->first();
            $resguardo = PuestoTrabajo::where('nombre', trim($row['PuestoResguardo']))->first();

            if ($tipoElemento && $tipoProceso && $unidadNegocio && $responsable && $ejecutor && $resguardo) {
                Elemento::updateOrCreate(
                    [
                        'tipo_elemento_id' => $tipoElemento->id_tipo_elemento,
                        'nombre_elemento' => $row['NombreElemento'],
                        'tipo_proceso_id' => $tipoProceso->id_tipo_proceso,
                        'unidad_negocio_id' => $unidadNegocio->id_unidad_negocio,
                        'ubicacion_eje_x' => $row['UbicacionX'],
                        'control' => $row['Control'],
                        'folio_elemento' => $row['FolioElemento'],
                        'version_elemento' => $row['VersionElemento'],
                        'fecha_elemento' => $row['FechaElemento'],
                        'periodo_revision' => $row['PeriodoRevision'],
                        'puesto_responsable_id' => $responsable->id_puesto_trabajo,
                        'puestos_relacionados' => $row['PuestosRelacionados'],
                        'es_formato' => $row['EsFormato'],
                        'archivo_formato' => $row['ArchivoFormato'],
                        'puesto_ejecutor_id' => $ejecutor->id_puesto_trabajo,
                        'puesto_resguardo_id' => $resguardo->id_puesto_trabajo,
                        'medio_soporte' => $row['MedioSoporte'],
                        'ubicacion_resguardo' => $row['UbicacionResguardo'],
                        'periodo_resguardo' => $row['PeriodoResguardo'],
                        'elemento_padre_id' => $row['ElementoPadre'],
                        'elemento_relacionado_id' => $row['ElementoRelacionado'],
                        'correo_implementacion' => $row['CorreoImplementacion'],
                        'correo_agradecimiento' => $row['CorreoAgradecimiento'],
                    ],
                );
                $processedRows++;
            } else {
                $skippedRows++;
            }
        }

        if ($processedRows == 0) {
            throw new \Exception("No se pudo procesar ninguna fila. Filas omitidas: $skippedRows. Verifica que las divisiones, unidades de negocio y áreas existan en la base de datos.");
        }
    }
}
