<?php

namespace App\Jobs;

use App\Models\WordDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;
use Ilovepdf\Ilovepdf;

class ProcesarDocumentoWordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $documento;
    protected $rutaWordOriginal;

    /**
     * Create a new job instance.
     */
    public function __construct(WordDocument $documento, string $rutaWordOriginal)
    {
        $this->documento = $documento;
        $this->rutaWordOriginal = $rutaWordOriginal;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Iniciando procesamiento asíncrono del documento: ' . $this->documento->id);

            // Configurar PhpWord
            Settings::setOutputEscapingEnabled(true);

            // Obtener el elemento relacionado
            $elemento = \App\Models\Elemento::find($this->documento->elemento_id);
            if (!$elemento) {
                throw new \Exception('Elemento no encontrado para el documento ID: ' . $this->documento->id);
            }

            $rutaCompleta = storage_path('app/public/' . $this->rutaWordOriginal);
            $extension = pathinfo($elemento->archivo_formato, PATHINFO_EXTENSION);

            // Intentar cargar documento
            $phpWord = null;
            $contenidoTexto = '';


            try {
                // Configurar manejo de errores para imágenes problemáticas
                set_error_handler(function ($severity, $message, $file, $line) {
                    if (strpos($message, 'Invalid image:') !== false) {
                        Log::info('Imagen problemática ignorada: ' . $message);
                        return true; // Suprimir el error
                    }
                    return false; // Permitir otros errores
                });

                $phpWord = IOFactory::load($rutaCompleta);

                // Restaurar el manejador de errores
                restore_error_handler();

                // Extraer texto con mejor detección de tablas y listas
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        try {
                            if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                                // Procesar tabla específicamente
                                $contenidoTexto .= $this->extraerTabla($element) . "\n";
                            } else {
                                // Extraer contenido completo del elemento
                                $contenidoTexto .= $this->extraerContenidoDeElemento($element) . "\n";
                            }
                        } catch (\Exception $e) {
                            // Si hay error al procesar un elemento específico, verificar si es una imagen
                            if ($element instanceof \PhpOffice\PhpWord\Element\Image) {
                                // Ignorar errores de imágenes completamente
                                Log::info('Imagen ignorada: ' . $e->getMessage());
                            } else {
                                // Para otros elementos, registrar el error pero continuar
                                Log::warning('Error al procesar elemento del documento: ' . $e->getMessage());
                                $contenidoTexto .= "[Elemento no procesable: " . get_class($element) . "]\n";
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Restaurar el manejador de errores si no se restauró antes
                restore_error_handler();

                // Verificar si el error es relacionado con imágenes
                if (strpos($e->getMessage(), 'Invalid image:') !== false) {
                    Log::info('Documento con imágenes problemáticas, intentando procesamiento alternativo: ' . $e->getMessage());

                    // Intentar procesar el documento ignorando las imágenes problemáticas
                    try {
                        $contenidoTexto = $this->procesarDocumentoConImagenesProblematicas($rutaCompleta);
                    } catch (\Exception $e2) {
                        Log::warning('Método alternativo también falló: ' . $e2->getMessage());
                        throw $e; // Re-lanzar el error original
                    }
                } else {
                    // Si falla la lectura con PHPWord por otros motivos, intentar métodos alternativos
                    Log::warning('PHPWord falló al leer archivo ' . $this->documento->elemento->archivo_formato . ': ' . $e->getMessage());

                    // Para archivos .doc, intentar extraer texto usando métodos alternativos
                    if (strtolower($extension) === 'doc') {
                        $contenidoTexto = $this->extraerTextoAlternativo($rutaCompleta);
                    } else {
                        // Re-lanzar la excepción para archivos .docx
                        throw $e;
                    }
                }
            }

            //Log::info('Contenido texto generado: ' . $contenidoTexto);
            // Si no se pudo extraer contenido, crear un mensaje informativo
            if (empty($contenidoTexto)) {
                $contenidoTexto = "No se pudo extraer el contenido del documento.\n\n";
                $contenidoTexto .= "Archivo: " . $this->documento->elemento->archivo_formato . "\n";
                $contenidoTexto .= "Tipo: " . $extension . "\n";
                $contenidoTexto .= "Fecha de subida: " . now()->format('Y-m-d H:i:s') . "\n\n";
                $contenidoTexto .= "Este documento puede requerir procesamiento manual o puede estar en un formato no compatible.";
            }

            Log::info('Contenido texto generado: ' . $contenidoTexto);

            // Limpiar errores de imágenes y contenido problemático

            Log::info('Limpiando errores de imágenes y contenido problemático');

            $contenidoTexto = $this->limpiarErroresDeImagenes($contenidoTexto);

            // Limpiar contenido final
            $contenidoTexto = $this->limpiarContenidoFinal($contenidoTexto);

            Log::info('Contenido de texto procesado correctamente');


            // Actualizar documento
            $this->documento->update([
                'contenido_texto' => trim($contenidoTexto),
                'estado' => 'procesado'
            ]);

            Log::info('Documento procesado exitosamente: ' . $this->documento->id);

            // Convertir a PDF y eliminar archivo Word original
            try {
                $this->convertirAPdfYEliminarWord($elemento);
                Log::info('Archivo convertido a PDF y Word original eliminado: ' . $this->documento->id);
            } catch (\Exception $e) {
                Log::warning('Error al convertir a PDF o eliminar Word original: ' . $e->getMessage());
                // No fallar el proceso principal por este error
            }
        } catch (\Exception $e) {
            Log::error('Error al procesar documento Word ID ' . $this->documento->id . ': ' . $e->getMessage());

            $this->documento->update([
                'estado' => 'error',
                'error_mensaje' => $e->getMessage()
            ]);
        }
    }

    /**
     * Extraer lista de un elemento ListItem de PHPWord
     */

    /**
     * Extraer tabla de un elemento Table de PHPWord
     */
    private function extraerTabla($elementoTable): string
    {
        $tablaMarkdown = [];
        $filas = [];

        // Obtener todas las filas de la tabla
        foreach ($elementoTable->getRows() as $fila) {
            $celdas = [];
            foreach ($fila->getCells() as $celda) {
                $textoCelda = $this->extraerContenidoCompletoCelda($celda);
                $celdas[] = $textoCelda;
            }
            if (!empty($celdas)) {
                $filas[] = $celdas;
            }
        }

        if (empty($filas)) {
            return '';
        }

        // Crear tabla Markdown
        $numColumnas = count($filas[0]);

        // Encabezados (primera fila)
        $encabezados = array_pad($filas[0], $numColumnas, '');
        $headerRow = "| " . implode(" | ", $encabezados) . " |";
        $tablaMarkdown[] = $headerRow;

        // Separador
        $separator = "| " . str_repeat("--- | ", $numColumnas);
        $tablaMarkdown[] = $separator;

        // Filas de datos
        for ($i = 1; $i < count($filas); $i++) {
            $fila = array_pad($filas[$i], $numColumnas, '');
            $dataRow = "| " . implode(" | ", $fila) . " |";
            $tablaMarkdown[] = $dataRow;
        }

        return implode("\n", $tablaMarkdown);
    }

    /**
     * Extraer contenido completo de una celda, incluyendo elementos anidados
     */
    private function extraerContenidoCompletoCelda($celda): string
    {
        $contenido = '';

        foreach ($celda->getElements() as $elemento) {
            if (method_exists($elemento, 'getText')) {
                $contenido .= $elemento->getText();
            } elseif (method_exists($elemento, 'getElements')) {
                // Si el elemento tiene sub-elementos, procesarlos recursivamente
                $contenido .= $this->extraerContenidoDeElemento($elemento);
            } elseif (method_exists($elemento, 'getContent')) {
                // Para elementos que tienen contenido directo
                $contenido .= $elemento->getContent();
            } elseif (method_exists($elemento, 'getValue')) {
                // Para elementos que tienen valor
                $contenido .= $elemento->getValue();
            }
        }

        // Limpiar y normalizar el contenido
        $contenido = preg_replace('/\s+/', ' ', $contenido); // Normalizar espacios
        $contenido = str_replace(["\r", "\n"], ' ', $contenido); // Reemplazar saltos de línea
        $contenido = trim($contenido);

        return $contenido;
    }

    /**
     * Extraer contenido de un elemento recursivamente
     */
    private function extraerContenidoDeElemento($elemento): string
    {
        $contenido = '';

        try {
            // Ignorar completamente las imágenes
            if ($elemento instanceof \PhpOffice\PhpWord\Element\Image) {
                return '';
            }

            if (method_exists($elemento, 'getText')) {
                $contenido .= $elemento->getText();
            }

            if (method_exists($elemento, 'getElements')) {
                foreach ($elemento->getElements() as $subElemento) {
                    try {
                        $contenido .= $this->extraerContenidoDeElemento($subElemento);
                    } catch (\Exception $e) {
                        // Si hay error al procesar un sub-elemento, verificar si es una imagen
                        if ($subElemento instanceof \PhpOffice\PhpWord\Element\Image) {
                            // Ignorar errores de imágenes completamente
                            Log::info('Sub-elemento imagen ignorado: ' . $e->getMessage());
                        } else {
                            // Para otros elementos, registrar el error pero continuar
                            Log::warning('Error al procesar sub-elemento: ' . $e->getMessage());
                            $contenido .= "[Elemento no procesable]\n";
                        }
                    }
                }
            }

            if (method_exists($elemento, 'getContent')) {
                $contenido .= $elemento->getContent();
            }

            if (method_exists($elemento, 'getValue')) {
                $contenido .= $elemento->getValue();
            }
        } catch (\Exception $e) {
            // Si hay error al procesar el elemento, registrar y continuar
            Log::warning('Error al procesar elemento: ' . $e->getMessage());
            $contenido .= "[Elemento no procesable]\n";
        }

        return $contenido;
    }

    /**
     * Convertir texto plano a Markdown usando información estructurada
     */
    private function convertirTextoAMarkdownConEstructura(string $texto): string
    {
        $markdown = [];
        $lineas = explode("\n", $texto);
        $enTabla = false;
        $tablaActual = [];

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) {
                // Finalizar tabla si estamos en una
                if ($enTabla && !empty($tablaActual)) {
                    $markdown = array_merge($markdown, $this->procesarTablaCompleta($tablaActual));
                    $tablaActual = [];
                    $enTabla = false;
                }
                $markdown[] = "";
                continue;
            }

            // Detectar si es una línea de tabla
            if ($this->esLineaDeTabla($linea)) {
                $columnas = $this->extraerColumnasDeTabla($linea);
                if (count($columnas) >= 2) {
                    if (!$enTabla) {
                        $enTabla = true;
                    }
                    $tablaActual[] = $columnas;
                }
            }
            // Detectar fin de tabla (línea vacía después de tabla)
            elseif ($enTabla && empty($linea)) {
                // Finalizar tabla si estamos en una
                if (!empty($tablaActual)) {
                    $markdown = array_merge($markdown, $this->procesarTablaCompleta($tablaActual));
                    $tablaActual = [];
                    $enTabla = false;
                }
            }
            // Texto normal
            else {
                // Finalizar tabla si estamos en una
                if ($enTabla && !empty($tablaActual)) {
                    $markdown = array_merge($markdown, $this->procesarTablaCompleta($tablaActual));
                    $tablaActual = [];
                    $enTabla = false;
                }

                // Limpiar texto duplicado y caracteres especiales
                $lineaLimpia = $this->limpiarTexto($linea);
                if (!empty($lineaLimpia)) {
                    $markdown[] = $lineaLimpia;
                }
            }
        }

        // Finalizar tabla si quedó pendiente
        if ($enTabla && !empty($tablaActual)) {
            $markdown = array_merge($markdown, $this->procesarTablaCompleta($tablaActual));
        }

        return implode("\n", $markdown);
    }

    /**
     * Procesar tabla completa y convertirla a Markdown
     */
    private function procesarTablaCompleta(array $filas): array
    {
        if (empty($filas)) {
            return [];
        }

        $markdown = [];
        $numColumnas = max(array_map('count', $filas));

        // Procesar cada fila
        foreach ($filas as $fila) {
            $filaCompleta = array_pad($fila, $numColumnas, '');
            $filaLimpia = array_map([$this, 'limpiarTexto'], $filaCompleta);
            $markdown[] = "| " . implode(" | ", $filaLimpia) . " |";
        }

        // Agregar separador después de la primera fila (encabezados) solo si hay más de una fila
        if (count($markdown) > 1) {
            $separator = "| " . str_repeat("--- | ", $numColumnas - 1) . "--- |";
            array_splice($markdown, 1, 0, [$separator]);
        }

        $markdown[] = "";
        return $markdown;
    }

    /**
     * Limpiar contenido final antes de guardar
     */
    private function limpiarContenidoFinal(string $texto): string
    {
        // Eliminar caracteres de control y no imprimibles
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $texto);

        // Eliminar duplicados de palabras específicas (como "OBJETIVOOBJETIVO")
        $texto = preg_replace('/(\b[A-Z]{2,})\1+/', '$1', $texto);

        // Eliminar duplicados de frases completas
        $texto = preg_replace('/(\b[A-Z\s]{5,})\1+/', '$1', $texto);

        // Eliminar diagramas de flujo específicamente
        $texto = $this->eliminarDiagramasDeFlujo($texto);

        // Normalizar espacios y saltos de línea
        $texto = preg_replace('/\s+/', ' ', $texto);
        $texto = preg_replace('/\n\s*\n\s*\n/', "\n\n", $texto);

        // Eliminar espacios al inicio y final
        $texto = trim($texto);

        return $texto;
    }

    /**
     * Eliminar diagramas de flujo del contenido
     */
    private function eliminarDiagramasDeFlujo(string $texto): string
    {
        // Detectar y eliminar secciones de diagramas de flujo
        $patrones = [
            // Patrón para "DIAGRAMA DE FLUJO" seguido de contenido hasta el final o siguiente sección
            '/DIAGRAMA DE FLUJO.*?(?=\n[A-Z\s]{5,}|$)/s',

            // Patrón para secciones que contienen "swimlanes" o carriles
            '/COORD\. DE CONTROL.*?RESIDENTE DE CONTROL.*?RESIDENTE DE COMPRAS.*?AUXILIAR DE CONTROL.*?(?=\n[A-Z\s]{5,}|$)/s',

            // Patrón para contenido que contiene múltiples pasos numerados en formato de diagrama
            '/\d+\.\s+[A-Z][^.]*\.\s*\d+\.\s+[A-Z][^.]*\.\s*\d+\.\s+[A-Z][^.]*\./s',

            // Patrón para secciones que contienen "Viene del procedimiento" y "Fin de procedimiento"
            '/Viene del procedimiento.*?Fin de procedimiento/s',

            // Patrón para contenido con múltiples "¿" (preguntas de decisión en diagramas)
            '/.*¿[^?]*\?.*¿[^?]*\?.*/s',
        ];

        foreach ($patrones as $patron) {
            $texto = preg_replace($patron, '', $texto);
        }

        // Eliminar líneas que contengan solo números y puntos (pasos de diagrama)
        $texto = preg_replace('/^\d+\.\s*$/m', '', $texto);

        // Eliminar líneas que contengan solo texto en mayúsculas seguido de puntos
        $texto = preg_replace('/^[A-Z\s]+\.\s*$/m', '', $texto);

        return $texto;
    }

    /**
     * Limpiar errores de imágenes y contenido problemático
     */
    private function limpiarErroresDeImagenes(string $texto): string
    {
        // Eliminar completamente errores de imágenes específicos
        $texto = preg_replace('/Invalid image:.*?\.(emf|png|jpg|jpeg|gif|bmp|tiff|svg).*?\n?/i', '', $texto);

        // Eliminar completamente rutas de archivos de imágenes problemáticas
        $texto = preg_replace('/zip:\/\/.*?\.(emf|png|jpg|jpeg|gif|bmp|tiff|svg).*?\n?/i', '', $texto);

        // Eliminar completamente referencias a archivos de medios de Word
        $texto = preg_replace('/word\/media\/.*?\.(emf|png|jpg|jpeg|gif|bmp|tiff|svg).*?\n?/i', '', $texto);

        // Eliminar líneas que solo contengan referencias a imágenes
        $texto = preg_replace('/^\[Imagen.*?\]\s*$/m', '', $texto);

        // Limpiar múltiples espacios y saltos de línea
        $texto = preg_replace('/\s+/', ' ', $texto);
        $texto = preg_replace('/\n\s*\n\s*\n/', "\n\n", $texto);

        return trim($texto);
    }

    /**
     * Limpiar texto eliminando duplicados y caracteres especiales
     */
    private function limpiarTexto(string $texto): string
    {
        // Eliminar caracteres no imprimibles
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $texto);

        // Eliminar texto duplicado más específico (como "OBJETIVOOBJETIVO")
        $texto = preg_replace('/(\b[A-Z]{2,})\1+/', '$1', $texto);

        // Eliminar duplicados de palabras completas
        $texto = preg_replace('/(\b\w+\s+)\1+/', '$1', $texto);

        // Normalizar espacios múltiples
        $texto = preg_replace('/\s+/', ' ', $texto);

        // Eliminar espacios al inicio y final
        $texto = trim($texto);

        return $texto;
    }

    /**
     * Verificar si una línea es parte de una tabla
     */
    private function esLineaDeTabla(string $linea): bool
    {
        // Detectar líneas que contienen separadores de tabla (|)
        if (strpos($linea, '|') !== false && substr_count($linea, '|') >= 2) {
            return true;
        }

        // Detectar líneas que contienen múltiples columnas separadas por espacios o tabs
        // Solo si la línea es lo suficientemente larga y tiene al menos 2 columnas
        if (strlen($linea) > 40) {
            $palabras = preg_split('/\s{2,}/', $linea);
            if (count($palabras) >= 2) {
                // Verificar que no sea un título o encabezado
                if (!preg_match('/^[A-Z\s]+$/', $linea) && !preg_match('/^[A-Z][^:]*:$/', $linea)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extraer columnas de una línea de tabla
     */
    private function extraerColumnasDeTabla(string $linea): array
    {
        // Si la línea contiene separadores de tabla (|)
        if (strpos($linea, '|') !== false) {
            $columnas = explode('|', $linea);
            return array_map('trim', array_filter($columnas, function ($col) {
                return !empty(trim($col));
            }));
        }

        // Dividir por espacios múltiples
        $columnas = preg_split('/\s{2,}/', $linea);

        // Limpiar columnas vacías
        return array_filter($columnas, function ($col) {
            return !empty(trim($col));
        });
    }

    /**
     * Procesar documento con imágenes problemáticas usando método alternativo
     */
    private function procesarDocumentoConImagenesProblematicas(string $rutaArchivo): string
    {
        try {
            // Intentar extraer texto usando métodos alternativos que no dependan de PHPWord
            $contenido = file_get_contents($rutaArchivo);

            Log::info('Procesando documento con imágenes problemáticas');

            // Para archivos .docx, intentar extraer texto del XML interno
            if (pathinfo($rutaArchivo, PATHINFO_EXTENSION) === 'docx') {
                return $this->extraerTextoDeDocx($rutaArchivo);
            }

            // Para archivos .doc, usar el método alternativo existente
            return $this->extraerTextoAlternativo($rutaArchivo);
        } catch (\Exception $e) {
            Log::warning('Método alternativo para imágenes problemáticas falló: ' . $e->getMessage());
            return "No se pudo extraer contenido del documento debido a imágenes problemáticas.\n\n";
        }
    }

    /**
     * Extraer texto de archivo .docx usando el XML interno
     */
    private function extraerTextoDeDocx(string $rutaArchivo): string
    {
        try {
            // Crear un archivo temporal para extraer el contenido
            $tempDir = sys_get_temp_dir() . '/docx_extract_' . uniqid();
            mkdir($tempDir, 0755, true);

            // Copiar el archivo .docx como .zip y extraer
            $zipFile = $tempDir . '/document.zip';
            copy($rutaArchivo, $zipFile);

            $zip = new \ZipArchive();
            if ($zip->open($zipFile) === TRUE) {
                // Extraer el archivo document.xml que contiene el texto
                $documentXml = $zip->getFromName('word/document.xml');
                $zip->close();

                // Limpiar archivos temporales
                unlink($zipFile);
                rmdir($tempDir);

                if ($documentXml) {
                    // Extraer texto del XML
                    $texto = strip_tags($documentXml);
                    $texto = html_entity_decode($texto, ENT_QUOTES, 'UTF-8');
                    $texto = preg_replace('/\s+/', ' ', $texto);
                    return trim($texto);
                }
            }

            // Limpiar archivos temporales en caso de error
            if (file_exists($zipFile)) unlink($zipFile);
            if (is_dir($tempDir)) rmdir($tempDir);

            return "No se pudo extraer contenido del archivo .docx.\n\n";
        } catch (\Exception $e) {
            Log::warning('Error al extraer texto de .docx: ' . $e->getMessage());
            return "Error al procesar archivo .docx.\n\n";
        }
    }

    /**
     * Método alternativo para extraer texto de archivos .doc
     */
    private function extraerTextoAlternativo(string $rutaArchivo): string
    {
        try {
            // Intentar leer el archivo como texto plano (puede funcionar para algunos .doc)
            $contenido = file_get_contents($rutaArchivo);

            // Filtrar caracteres no imprimibles y mantener solo texto legible
            $contenido = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $contenido);

            // Filtrar caracteres UTF-8 malformados
            $contenido = preg_replace('/[\x80-\xFF]/', '', $contenido);

            // Solo mantener caracteres ASCII imprimibles
            $contenido = preg_replace('/[^\x20-\x7E\n\r\t]/', '', $contenido);

            // Buscar patrones de texto legible
            $lineas = explode("\n", $contenido);
            $lineasTexto = [];

            foreach ($lineas as $linea) {
                $linea = trim($linea);
                // Solo incluir líneas que parezcan texto legible
                if (strlen($linea) > 3 && preg_match('/[a-zA-Z]{3,}/', $linea)) {
                    // Verificar que la línea no contenga caracteres corruptos
                    if (mb_check_encoding($linea, 'UTF-8') && !preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $linea)) {
                        $lineasTexto[] = $linea;
                    }
                }
            }

            $textoFinal = implode("\n", $lineasTexto);

            // Si no se extrajo contenido útil, devolver un mensaje informativo
            if (empty($textoFinal) || strlen($textoFinal) < 10) {
                return "No se pudo extraer contenido legible del archivo .doc.\n\n";
            }

            return $textoFinal;
        } catch (\Exception $e) {
            Log::warning('Método alternativo falló para archivo: ' . $rutaArchivo);
            return "No se pudo extraer contenido del archivo .doc.\n\n";
        }
    }

    /**
     * Convertir documento Word a PDF y eliminar el archivo original
     */
    private function convertirAPdfYEliminarWord($elemento): void
    {
        try {
            // Ruta absoluta y relativa del Word
            $rutaWordAbs = Storage::disk('public')->path($elemento->archivo_formato);
            $rutaWordRel = $elemento->archivo_formato;

            if (!file_exists($rutaWordAbs)) {
                Log::warning('Archivo Word original no encontrado: ' . $rutaWordAbs);
                return;
            }

            // Generar nombre base limpio para el PDF
            $fechaNow = now()->format('d-m-Y-h-i-a');
            $nombreBase = Str::slug(pathinfo($rutaWordRel, PATHINFO_FILENAME), '-') . '-' . $fechaNow;
            $nombrePdf  = $nombreBase . '.pdf';
            $rutaPdfRel = 'elementos/formato/' . $nombrePdf;
            $rutaPdfAbs = Storage::disk('public')->path($rutaPdfRel);

            // Asegurar carpeta destino
            if (!Storage::disk('public')->exists('elementos/formato')) {
                Storage::disk('public')->makeDirectory('elementos/formato');
            }

            $ilovepdf = new Ilovepdf(
                config('services.ilovepdf.public'),
                config('services.ilovepdf.secret')
            );

            $task = $ilovepdf->newTask('officepdf');
            $task->addFile($rutaWordAbs);
            $task->setOutputFilename($nombreBase);
            $task->execute();
            $task->download(dirname($rutaPdfAbs));

            // Actualizar BD con la ruta del PDF final
            $elemento->update([
                'archivo_formato' => $rutaPdfRel
            ]);

            // Eliminar Word original
            Storage::disk('public')->delete($rutaWordRel);

            Log::info('Archivo convertido con iLovePDF y Word eliminado: ' . $rutaPdfRel);
        } catch (\Exception $e) {
            Log::error('Error al convertir a PDF con iLovePDF: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar HTML del contenido procesado para la conversión a PDF
     */
    private function generarHtmlDelContenido($elemento): string
    {
        $contenidoMarkdown = $this->documento->contenido_texto;

        // Convertir Markdown a HTML básico
        $html = $this->convertirMarkdownAHtml($contenidoMarkdown);

        // Crear documento HTML completo
        $documentoHtml = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . htmlspecialchars($elemento->nombre_elemento) . '</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; margin: 20px; }
                h1, h2, h3, h4, h5, h6 { color: #333; margin-top: 20px; margin-bottom: 10px; }
                h1 { font-size: 18px; }
                h2 { font-size: 16px; }
                h3 { font-size: 14px; }
                p { margin-bottom: 10px; }
                table { border-collapse: collapse; width: 100%; margin: 10px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                ul, ol { margin: 10px 0; padding-left: 20px; }
                li { margin-bottom: 5px; }
                .page-break { page-break-before: always; }
            </style>
        </head>
        <body>
            <h1>' . htmlspecialchars($elemento->nombre_elemento) . '</h1>
            <div class="contenido">
                ' . $html . '
            </div>
        </body>
        </html>';

        return $documentoHtml;
    }

    /**
     * Convertir Markdown básico a HTML
     */
    private function convertirMarkdownAHtml(string $markdown): string
    {
        // Convertir encabezados
        $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $markdown);
        $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);

        // Convertir tablas
        $html = preg_replace('/\|(.+)\|/', '<tr><td>' . str_replace('|', '</td><td>', '$1') . '</td></tr>', $html);
        $html = preg_replace('/<tr><td>(.+?)<\/td><\/tr>/s', '<table><tbody>$0</tbody></table>', $html);

        // Convertir listas
        $html = preg_replace('/^\* (.+)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/^\- (.+)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/<li>(.+?)<\/li>/s', '<ul>$0</ul>', $html);

        // Convertir negrita e itálica
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);

        // Convertir párrafos
        $html = preg_replace('/^(?!<[h|u|t|li])(.+)$/m', '<p>$1</p>', $html);

        // Limpiar HTML malformado
        $html = preg_replace('/<p><\/p>/', '', $html);
        $html = preg_replace('/<ul><\/ul>/', '', $html);

        return $html;
    }
}
