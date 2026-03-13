<?php

namespace App\Jobs;

use App\Models\Firmas;
use App\Services\DocumentChunkingService;
use App\Models\WordDocument;
use App\Services\DocumentoGeneradorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\OpenAiOcrService;
use Ilovepdf\Ilovepdf;
use Exception;

class ProcesarDocumentoWordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200;
    public $tries = 1;

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
        //EVITAR QUE XAMPP/PHP CORTE EL PROCESO A LOS 60 SEGUNDOS
        set_time_limit(0);
        ini_set('max_execution_time', 0);

        try {
            Log::info("🚀 [MODO OCR PURO] Iniciando Doc ID: {$this->documento->id}");

            $elemento = \App\Models\Elemento::find($this->documento->elemento_id);
            if (!$elemento) {
                throw new \Exception("Elemento no encontrado");
            }

            $rutaArchivoRel = ltrim($this->rutaWordOriginal, '/');
            $rutaArchivoAbs = Storage::disk('public')->path($rutaArchivoRel);

            if (!file_exists($rutaArchivoAbs)) {
                throw new \Exception("Archivo físico no encontrado: {$rutaArchivoAbs}");
            }

            $extension = strtolower(pathinfo($rutaArchivoAbs, PATHINFO_EXTENSION));

            if ($extension === 'pdf') {
                Log::info("📄 El archivo ya es PDF. Se omite conversión previa.");
                $rutaPdfRel = $rutaArchivoRel;
                $rutaPdfAbs = $rutaArchivoAbs;
            } elseif (in_array($extension, ['doc', 'docx', 'rtf'], true)) {
                Log::info("📄 Convirtiendo archivo a PDF con iLovePDF...");
                $rutaPdfRel = $this->convertirWordAPdfHelper($rutaArchivoAbs);
                $rutaPdfAbs = Storage::disk('public')->path($rutaPdfRel);

                if (!file_exists($rutaPdfAbs)) {
                    throw new \Exception("Fallo crítico: No se generó el PDF intermedio.");
                }
            } else {
                throw new \Exception("Formato no soportado para procesamiento: {$extension}");
            }

            Log::info("Enviando PDF a la IA para lectura visual...");

            $contenidoTexto = app(OpenAiOcrService::class)->extractTextFromPdf($rutaPdfRel);

            Log::info("IA terminó. Texto recuperado: " . mb_strlen($contenidoTexto) . " caracteres.");

            if (mb_strlen(trim($contenidoTexto)) < 20) {
                throw new \Exception("La IA vió el documento pero no encontró texto legible.");
            }

            $contenidoTexto = $this->sanitizarUTF8($contenidoTexto);

            $this->documento->contenido_texto = $contenidoTexto;
            $this->documento->estado = 'procesado';
            $this->documento->save();

            Log::info("Texto guardado en Base de Datos.");

            $elemento->update(['archivo_es_formato' => $rutaPdfRel]);
            $elemento->touch();

            $requiereFirmas = $this->requiereFirmas((int) $elemento->id_elemento);

            if ($requiereFirmas) {
                try {
                    $elementoFresh = $elemento->fresh();
                    $rutaMarcaAgua = app(DocumentoGeneradorService::class)->generarDocumentoConMarcaAgua($elementoFresh);
                    $elementoFresh->update(['archivo_markdown' => $rutaMarcaAgua]);
                    Log::info("Marca de agua generada y guardada en el elemento.");
                } catch (\Throwable $e) {
                    Log::error("Error generando marca de agua en Job: " . $e->getMessage());
                }
            }

            if (
                $extension !== 'pdf' &&
                file_exists($rutaArchivoAbs) &&
                realpath($rutaArchivoAbs) !== realpath($rutaPdfAbs)
            ) {
                @unlink($rutaArchivoAbs);
                Log::info("🗑️ Archivo original eliminado tras generar el PDF.");
            }

            Log::info("Iniciando Chunking Inteligente...");
            app(DocumentChunkingService::class)->chunkWordDocument($this->documento);

            Log::info("PROCESO 100% COMPLETADO.");
        } catch (\Throwable $e) {
            Log::error("Error Fatal Job: " . $e->getMessage());

            try {
                $this->documento->estado = 'error';
                $this->documento->save();
            } catch (\Throwable $x) {
            }
        }
    }


    // =========================================================================
    // MÉTODOS DE LIMPIEZA Y SEGURIDAD (LOS NUEVOS Y BLINDADOS)
    // =========================================================================

    /**
     * "SEGURO DE VIDA" para MySQL.
     * Elimina bytes corruptos y caracteres de 4 bytes (emojis) que rompen BD antiguas.
     */
    private function sanitizarUTF8(string $texto): string
    {
        if (empty($texto)) return '';
        $texto = str_replace(chr(0), '', $texto);
        $texto = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $texto);
        return trim($texto);
    }

    /**
     * Rescate de emergencia para .DOCX: Abre el ZIP y saca el XML a la fuerza.
     * Sirve cuando PhpWord falla por imágenes o tablas complejas.
     */
    private function extraerTextoDeDocxManual(string $rutaArchivo): string
    {
        $texto = '';
        try {
            Log::info("Intentando extracción manual ZIP para: " . basename($rutaArchivo));

            $zip = new \ZipArchive;
            if ($zip->open($rutaArchivo) === TRUE) {
                // El texto en Word siempre vive en 'word/document.xml'
                if (($index = $zip->locateName('word/document.xml')) !== false) {
                    $xmlData = $zip->getFromIndex($index);

                    // Log para ver si encontramos el XML
                    Log::info("XML encontrado. Tamaño: " . strlen($xmlData) . " bytes.");

                    // Limpiamos etiquetas XML para dejar solo el texto puro
                    // Agregamos un espacio entre etiquetas para evitar que palabras se peguen
                    $texto = strip_tags(str_replace('<', ' <', $xmlData));

                    Log::info("Texto extraído (primeros 100 chars): " . substr($texto, 0, 100));
                } else {
                    Log::error("No se encontró 'word/document.xml' dentro del ZIP.");
                }
                $zip->close();
            } else {
                Log::error("No se pudo abrir el archivo como ZIP.");
            }
        } catch (\Exception $e) {
            Log::error("Error en extracción manual: " . $e->getMessage());
        }

        return trim($texto);
    }

    /**
     * Método "Minería de Texto" (Estilo Strings de Unix)
     * Extrae solo secuencias de caracteres legibles del binario.
     * Ignora imágenes, formatos y basura binaria.
     */
    private function extraerTextoAlternativo(string $rutaArchivo): string
    {
        if (!file_exists($rutaArchivo)) return '';

        // 1. Leemos el archivo crudo
        $binario = file_get_contents($rutaArchivo);
        if ($binario === false) return '';

        // 2. FILTRO BYTE POR BYTE (EL COLADOR)
        // Convertimos a espacios cualquier cosa que no sea una letra, número o símbolo común.
        // Esto elimina los símbolos raros como "ÐÏ à¡" que rompen tu base de datos.
        $limpio = '';
        $len = strlen($binario);
        for ($i = 0; $i < $len; $i++) {
            $ord = ord($binario[$i]);

            // Permitimos: Tab(9), Enter(10,13), y caracteres imprimibles (32 al 255)
            // El resto (0-8, 11-12, 14-31) lo convertimos en espacio.
            if (($ord >= 32 && $ord <= 255) || $ord == 9 || $ord == 10 || $ord == 13) {
                $limpio .= $binario[$i];
            } else {
                $limpio .= ' ';
            }
        }

        // 3. Convertimos a UTF-8 para que Laravel lo entienda
        // Usamos Windows-1252 porque es el estándar de los .doc viejos en español
        $textoUtf8 = mb_convert_encoding($limpio, 'UTF-8', 'Windows-1252');

        // 4. Limpieza final de espacios extra
        $textoUtf8 = preg_replace('/\s+/', ' ', $textoUtf8);

        return trim($textoUtf8);
    }

    // =========================================================================
    // MÉTODOS DE SOPORTE DE PHPWORD (STANDARD)
    // =========================================================================

    private function extraerContenidoEstructuradoDesdeXml(string $rutaCompleta): array
    {
        $estructura = [
            'parrafos' => [],
            'tablas' => [],
            'listas' => [],
            'cuadros_texto' => [],
        ];

        $zip = new \ZipArchive();
        if ($zip->open($rutaCompleta) !== TRUE) {
            throw new Exception("No se pudo abrir el DOCX");
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        foreach ($xpath->query('//w:p') as $parrafo) {
            $texto = '';
            foreach ($xpath->query('.//w:t', $parrafo) as $text) {
                $texto .= $text->nodeValue . ' ';
            }
            $texto = trim($texto);
            if ($texto !== '') {
                $estructura['parrafos'][] = $texto;
            }
        }
        // TABLAS
        foreach ($xpath->query('//w:tbl') as $tablaNode) {
            $tabla = [];
            foreach ($xpath->query('.//w:tr', $tablaNode) as $filaNode) {
                $fila = [];
                foreach ($xpath->query('.//w:tc', $filaNode) as $celdaNode) {
                    $celda = '';
                    foreach ($xpath->query('.//w:t', $celdaNode) as $textoNode) {
                        $celda .= $textoNode->nodeValue . ' ';
                    }
                    $fila[] = trim($celda);
                }
                $tabla[] = $fila;
            }
            $estructura['tablas'][] = $tabla;
        }

        // LISTAS
        $ultimoIndex = null;
        $anteriorFueLista = false;
        $parrafos = $xpath->query('//w:p');

        foreach ($parrafos as $p) {
            $tieneNumPr = $xpath->query('.//w:numPr', $p)->length > 0;
            $texto = '';
            foreach ($xpath->query('.//w:t', $p) as $t) {
                $texto .= $t->nodeValue . ' ';
            }
            $texto = trim($texto);
            if ($texto === '') continue;

            if ($tieneNumPr) {
                $estructura['listas'][] = $texto;
                $ultimoIndex = count($estructura['listas']) - 1;
                $anteriorFueLista = true;
            } elseif ($anteriorFueLista && $ultimoIndex !== null) {
                $estructura['listas'][$ultimoIndex] .= ' ' . $texto;
                $anteriorFueLista = false;
            } else {
                $anteriorFueLista = false;
            }
        }

        // CUADROS DE TEXTO
        $cuadros = [];

        foreach ($xpath->query('//w:txbxContent') as $cuadro) {
            $texto = '';
            foreach ($xpath->query('.//w:t', $cuadro) as $t) {
                $texto .= $t->nodeValue . ' ';
            }
            $texto = preg_replace('/\s+/', ' ', trim($texto));
            if ($texto !== '') {
                $cuadros[] = $texto;
            }
        }

        $cuadrosLimpios = [];
        foreach ($cuadros as $c) {
            $hash = md5(strtolower(preg_replace('/\s+/', '', $c)));
            if (!isset($cuadrosLimpios[$hash])) {
                $cuadrosLimpios[$hash] = $c;
            }
        }

        $estructura['cuadros_texto'] = array_values($cuadrosLimpios);

        return $estructura;
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
     * Limpiar contenido final antes de guardar (VERSIÓN BLINDADA)
     */
    private function limpiarContenidoFinal(string $texto): string
    {
        $original = $texto; // 1. Guardamos copia de seguridad por si acaso

        // 2. Eliminar caracteres de control (Null bytes, vertical tabs, etc.)
        // Mantenemos saltos de línea (\n) y retorno de carro (\r)
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $texto);

        // 3. Eliminar duplicados de palabras pegadas (ej: "OBJETIVOOBJETIVO")
        $texto = preg_replace('/(\b[A-Z]{3,})\1+/', '$1', $texto);

        // 4. Eliminar diagramas de flujo (Versión corregida y segura)
        $texto = $this->eliminarDiagramasDeFlujo($texto);

        // 5. Normalizar espacios y saltos de línea
        // Convierte tabs y espacios múltiples en un solo espacio
        $texto = preg_replace('/\s+/', ' ', $texto);
        $texto = trim($texto);

        // === SEGURO DE VIDA ===
        // Si la limpieza fue un desastre (borró casi todo) y el original tenía datos...
        // ... ¡RESTAURAMOS EL ORIGINAL!
        if (mb_strlen($texto) < 20 && mb_strlen($original) > 100) {
            \Illuminate\Support\Facades\Log::warning("⚠️ [SAFETY] La limpieza agresiva borró el texto. Restaurando original.");
            // Devolvemos el original con una limpieza mínima (solo quitar nulos y espacios extra)
            return trim(preg_replace('/\s+/', ' ', str_replace(chr(0), '', $original)));
        }

        return $texto;
    }

    /**
     * Eliminar diagramas de flujo del contenido (VERSIÓN QUIRÚRGICA)
     * Ya no usa selectores que se comen todo el archivo.
     */
    private function eliminarDiagramasDeFlujo(string $texto): string
    {
        // En lugar de borrar bloques gigantes, borramos patrones de líneas específicas
        // que sobran de los diagramas (flechas, decisiones, conectores).

        $patronesSeguros = [
            // Líneas que son solo "SI", "NO", "FIN", "INICIO" aisladas (comunes en las flechas de decisión)
            '/^\s*(SI|NO|FIN|INICIO)\s*$/m',

            // Líneas que dicen explícitamente "Viene del procedimiento..." o "Fin de procedimiento"
            '/^\s*Viene del procedimiento.*$/mi',
            '/^\s*Fin de procedimiento.*$/mi',

            // Pasos numéricos sueltos que quedaron vacíos (ej: "1. " sin texto)
            '/^\s*\d+\.\s*$/m',

            // Encabezados de diagramas si aparecen solos
            '/^\s*DIAGRAMA DE FLUJO\s*$/mi',

            // Preguntas de decisión sueltas (ej: "¿Autoriza?")
            // Solo si están en una línea corta (menos de 50 chars) para no borrar preguntas reales del texto
            '/^\s*¿.{1,50}\?\s*$/m',
        ];

        return preg_replace($patronesSeguros, ' ', $texto);
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
     * Helper para OCR: Convierte Word a PDF y DEVUELVE la ruta relativa.
     */
    private function convertirWordAPdfHelper(string $rutaWordAbs): string
    {
        $ilovepdf = new Ilovepdf(
            config('services.ilovepdf.public'),
            config('services.ilovepdf.secret')
        );

        $rutaOriginalRel = ltrim($this->rutaWordOriginal, '/');
        $directorioRel = pathinfo($rutaOriginalRel, PATHINFO_DIRNAME);
        $nombreBaseOriginal = pathinfo($rutaOriginalRel, PATHINFO_FILENAME);
        $nombrePdf = $nombreBaseOriginal . '.pdf';

        $rutaRel = ($directorioRel && $directorioRel !== '.')
            ? $directorioRel . '/' . $nombrePdf
            : $nombrePdf;

        $rutaPdfAbs = Storage::disk('public')->path($rutaRel);
        $dirDestino = dirname($rutaPdfAbs);

        if (!is_dir($dirDestino)) {
            mkdir($dirDestino, 0755, true);
        }

        if (realpath($rutaWordAbs) === realpath($rutaPdfAbs)) {
            throw new \RuntimeException('La ruta origen y destino del PDF son la misma. No se puede convertir sobre el mismo archivo.');
        }

        if (file_exists($rutaPdfAbs)) {
            @unlink($rutaPdfAbs);
        }

        $task = $ilovepdf->newTask('officepdf');
        $task->addFile($rutaWordAbs);
        $task->setOutputFilename($nombrePdf);
        $task->execute();
        $task->download($dirDestino);

        if (!file_exists($rutaPdfAbs)) {
            $pdfs = glob($dirDestino . DIRECTORY_SEPARATOR . '*.pdf') ?: [];

            if (empty($pdfs)) {
                throw new \RuntimeException('No se encontró el PDF generado después de la conversión.');
            }

            usort($pdfs, static fn(string $a, string $b) => filemtime($b) <=> filemtime($a));

            $pdfGenerado = $pdfs[0];

            if ($pdfGenerado !== $rutaPdfAbs) {
                if (file_exists($rutaPdfAbs)) {
                    @unlink($rutaPdfAbs);
                }

                if (!@rename($pdfGenerado, $rutaPdfAbs)) {
                    throw new \RuntimeException('No se pudo renombrar el PDF generado al nombre esperado.');
                }
            }
        }

        return $rutaRel;
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

    private function requiereFirmas(int $elementoID): bool
    {
        return Firmas::where('elemento_id', $elementoID)
            ->where('is_active', true)
            ->whereIn('tipo', ['Participante', 'Responsable', 'Autorizo', 'Reviso'])
            ->exists();
    }
}
