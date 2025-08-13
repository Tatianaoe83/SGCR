<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\PuestoTrabajo;
use App\Models\Empleados;

class EmpleadosImport implements ToCollection, WithHeadingRow, WithValidation
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

        // Log temporal para debugging - mostrar todas las filas recibidas
        \Log::info('Datos recibidos en la importación:', [
            'total_rows' => $collection->count(),
            'all_data' => $collection->toArray()
        ]);

        // Log temporal para debugging - mostrar todos los puestos de trabajo existentes
        $puestosExistentes = PuestoTrabajo::all(['id_puesto_trabajo', 'nombre']);
        \Log::info('Puestos de trabajo existentes en la base de datos:', $puestosExistentes->toArray());

        // Filtrar filas vacías antes de procesar
        $filteredCollection = $collection->filter(function ($row) {
            // Validación menos estricta - solo verificar que no sean completamente vacías
            $hasData = !empty(trim((string) ($row['nombres_del_empleado'] ?? ''))) || 
                       !empty(trim((string) ($row['apellido_paterno'] ?? ''))) || 
                       !empty(trim((string) ($row['apellido_materno'] ?? ''))) || 
                       !empty(trim((string) ($row['puesto_de_trabajo'] ?? ''))) ||
                       !empty(trim((string) ($row['correo'] ?? ''))) ||
                       !empty(trim((string) ($row['telefono'] ?? '')));
            
            // Log temporal para debugging
            if (!$hasData) {
                \Log::info('Fila completamente vacía filtrada');
            }
            
            return $hasData;
        });

        if ($filteredCollection->isEmpty()) {
            throw new \Exception('No se encontraron filas válidas para procesar. Verifica que todas las filas tengan los datos completos.');
        }

        $processedRows = 0;
        $skippedRows = 0;

        foreach ($filteredCollection as $row) {
            try {
                // Verificar que la fila tenga todos los datos requeridos para crear un empleado
                if (empty(trim((string) ($row['nombres_del_empleado'] ?? ''))) || 
                    empty(trim((string) ($row['apellido_paterno'] ?? ''))) || 
                    empty(trim((string) ($row['apellido_materno'] ?? ''))) || 
                    empty(trim((string) ($row['puesto_de_trabajo'] ?? ''))) ||
                    empty(trim((string) ($row['correo'] ?? ''))) ||
                    empty(trim((string) ($row['telefono'] ?? '')))) {
                    
                    \Log::info('Fila omitida por datos incompletos:', [
                        'nombres' => $row['nombres_del_empleado'] ?? 'NULL',
                        'apellido_paterno' => $row['apellido_paterno'] ?? 'NULL',
                        'apellido_materno' => $row['apellido_materno'] ?? 'NULL',
                        'puesto_trabajo' => $row['puesto_de_trabajo'] ?? 'NULL',
                        'correo' => $row['correo'] ?? 'NULL',
                        'telefono' => $row['telefono'] ?? 'NULL',
                    ]);
                    $skippedRows++;
                    continue;
                }

                // Buscar el puesto de trabajo por nombre
                $puestoTrabajo = PuestoTrabajo::where('nombre', trim($row['puesto_de_trabajo']))->first();

                // Solo crear si el puesto de trabajo existe
                if ($puestoTrabajo) {
                    // Verificar que el correo no esté duplicado
                    $empleadoExistente = Empleados::where('correo', trim($row['correo']))->first();
                    if ($empleadoExistente) {
                        // Empleado existe, verificar si hay cambio de puesto
                        if ($empleadoExistente->puesto_trabajo_id !== $puestoTrabajo->id_puesto_trabajo) {
                            // Actualizar el puesto de trabajo del empleado existente
                            $empleadoExistente->update([
                                'puesto_trabajo_id' => $puestoTrabajo->id_puesto_trabajo
                            ]);
                            \Log::info('Empleado actualizado con nuevo puesto:', [
                                'correo' => trim($row['correo']),
                                'puesto_anterior' => $empleadoExistente->puestoTrabajo->nombre ?? 'N/A',
                                'puesto_nuevo' => $puestoTrabajo->nombre
                            ]);
                        } else {
                            \Log::info('Empleado con correo ya existe y mismo puesto, omitiendo:', ['correo' => trim($row['correo'])]);
                        }
                        $skippedRows++;
                        continue; // Saltar si el correo ya existe
                    }

                    Empleados::create([
                        'nombres' => (string) trim($row['nombres_del_empleado']),
                        'apellido_paterno' => (string) trim($row['apellido_paterno']),
                        'apellido_materno' => (string) trim($row['apellido_materno']),
                        'puesto_trabajo_id' => $puestoTrabajo->id_puesto_trabajo,
                        'correo' => (string) trim($row['correo']),
                        'telefono' => (string) trim($row['telefono']),
                    ]);
                    $processedRows++;
                    \Log::info('Empleado creado exitosamente:', ['correo' => trim($row['correo'])]);
                } else {
                    \Log::info('Puesto de trabajo no encontrado:', ['puesto' => trim($row['puesto_de_trabajo'])]);
                    $skippedRows++;
                }
            } catch (\Exception $e) {
                $skippedRows++;
                // Log del error para debugging
                \Log::error('Error procesando fila de empleado: ' . $e->getMessage(), [
                    'row_data' => $row->toArray()
                ]);
            }
        }

        // Si no se procesó ninguna fila, mostrar mensaje
        if ($processedRows == 0) {
            throw new \Exception("No se pudo procesar ninguna fila. Filas omitidas: $skippedRows. Verifica que los puestos de trabajo existan en la base de datos.");
        }

        // Mostrar resumen del procesamiento
        $totalRows = $collection->count();
        $emptyRows = $totalRows - $filteredCollection->count();
        
        if ($emptyRows > 0) {
            \Log::info("Importación completada: $processedRows filas procesadas, $skippedRows filas omitidas, $emptyRows filas vacías ignoradas");
        }
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'nombres_del_empleado' => 'nullable|string|max:255',
            'apellido_paterno' => 'nullable|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'puesto_de_trabajo' => 'nullable|string|max:255',
            'correo' => 'nullable|string|email|max:255',
            'telefono' => 'nullable|max:255',
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'nombres_del_empleado.max' => 'El nombre del empleado no puede exceder 255 caracteres.',
            'apellido_paterno.max' => 'El apellido paterno no puede exceder 255 caracteres.',
            'apellido_materno.max' => 'El apellido materno no puede exceder 255 caracteres.',
            'puesto_de_trabajo.max' => 'El puesto de trabajo no puede exceder 255 caracteres.',
            'correo.email' => 'El formato del correo no es válido.',
            'correo.max' => 'El correo no puede exceder 255 caracteres.',
            'telefono.max' => 'El teléfono no puede exceder 255 caracteres.',
        ];
    }
}
