<?php

namespace App\Http\Controllers;

use App\Models\WordDocument;
use App\Jobs\ProcesarDocumentoWordJob;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;

class WordDocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $documentos = WordDocument::orderBy('created_at', 'desc')->paginate(15);
        
        // Estadísticas
        $totalDocumentos = WordDocument::count();
        $documentosProcesados = WordDocument::procesados()->count();
        $documentosConError = WordDocument::conError()->count();
        $documentosPendientes = WordDocument::pendientes()->count();
        
        return view('word-documents.index', compact(
            'documentos',
            'totalDocumentos',
            'documentosProcesados',
            'documentosConError',
            'documentosPendientes'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('word-documents.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'archivo' => 'required|file|mimes:doc,docx|max:' . config('word-documents.max_file_size_kb'),
            'tipo_documento' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:100',
            'autor' => 'nullable|string|max:255',
            'contenido_markdown' => 'nullable|string',
        ], [
            'archivo.required' => 'Debe seleccionar un archivo para subir.',
            'archivo.file' => 'El archivo seleccionado no es válido.',
            'archivo.mimes' => 'Solo se permiten archivos .doc y .docx.',
            'archivo.max' => 'El archivo no puede ser mayor a ' . config('word-documents.max_file_size_kb') . ' KB.',
        ]);

        try {
            $archivo = $request->file('archivo');
            $nombreOriginal = $archivo->getClientOriginalName();
            $extension = strtolower($archivo->getClientOriginalExtension());
            $tamañoArchivo = $archivo->getSize();
            
            // Verificación adicional del tipo de archivo
            if (!in_array($extension, ['doc', 'docx'])) {
                return redirect()->back()
                    ->with('error', 'Tipo de archivo no permitido. Solo se aceptan archivos .doc y .docx.')
                    ->withInput();
            }
            
            // Verificación del tamaño del archivo
            $tamañoMaximoKB = config('word-documents.max_file_size_kb') * 1024;
            if ($tamañoArchivo > $tamañoMaximoKB) {
                return redirect()->back()
                    ->with('error', 'El archivo es demasiado grande. Tamaño máximo: ' . config('word-documents.max_file_size_kb') . ' KB.')
                    ->withInput();
            }
            
            $nombreArchivo = time() . '_' . uniqid() . '.' . $extension;
            
            // Guardar archivo
            $rutaArchivo = $archivo->storeAs('word-documents', $nombreArchivo, 'public');
            
            // Crear registro en BD
            $documento = WordDocument::create([
                'nombre_archivo' => $nombreArchivo,
                'nombre_original' => $nombreOriginal,
                'ruta_archivo' => $rutaArchivo,
                'tipo_documento' => $request->tipo_documento,
                'version' => $request->version,
                'autor' => $request->autor,
                'contenido_markdown' => $request->contenido_markdown ?: null,
                'estado' => 'pendiente'
            ]);

            // Procesar archivo de forma asíncrona
            ProcesarDocumentoWordJob::dispatch($documento);

            $mensaje = 'Documento Word subido exitosamente. Se está procesando en segundo plano.';
            if ($extension === 'doc') {
                $mensaje .= ' Nota: Los archivos .doc pueden tener limitaciones en la extracción de contenido.';
            }

            return redirect()->route('word-documents.index')
                ->with('success', $mensaje);

        } catch (\Exception $e) {
            Log::error('Error al subir documento Word: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Error al subir el documento: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(WordDocument $wordDocument): View
    {
        return view('word-documents.show', compact('wordDocument'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WordDocument $wordDocument): View
    {
        return view('word-documents.edit', compact('wordDocument'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WordDocument $wordDocument): RedirectResponse
    {
        $request->validate([
            'tipo_documento' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:100',
            'autor' => 'nullable|string|max:255',
            'contenido_markdown' => 'nullable|string',
        ]);

        try {
            $wordDocument->update([
                'tipo_documento' => $request->tipo_documento,
                'version' => $request->version,
                'autor' => $request->autor,
                'contenido_markdown' => $request->contenido_markdown,
            ]);

            return redirect()->route('word-documents.index')
                ->with('success', 'Documento actualizado exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error al actualizar documento Word: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Error al actualizar el documento: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WordDocument $wordDocument): RedirectResponse
    {
        try {
            $wordDocument->delete();
            
            return redirect()->route('word-documents.index')
                ->with('success', 'Documento eliminado exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error al eliminar documento Word: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Error al eliminar el documento: ' . $e->getMessage());
        }
    }

    /**
     * Procesar documento Word para extraer contenido
     */
    
    /**
     * Reprocesar documento
     */
    public function reprocesar(WordDocument $wordDocument): RedirectResponse
    {
        try {
            $wordDocument->update([
                'estado' => 'pendiente',
                'error_mensaje' => null
            ]);
            
            // Procesar de forma asíncrona
            ProcesarDocumentoWordJob::dispatch($wordDocument);
            
            return redirect()->back()
                ->with('success', 'Documento enviado para reprocesamiento.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al reprocesar: ' . $e->getMessage());
        }
    }

    /**
     * Descargar archivo original
     */
    public function descargar(WordDocument $wordDocument): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!Storage::disk('public')->exists($wordDocument->ruta_archivo)) {
            abort(404, 'Archivo no encontrado');
        }
        
        return Storage::disk('public')->download(
            $wordDocument->ruta_archivo,
            $wordDocument->nombre_original
        );
    }

    /**
     * Filtrar documentos por estado
     */
    public function filtrar(Request $request): View
    {
        $query = WordDocument::query();
        
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        
        if ($request->filled('tipo_documento')) {
            $query->where('tipo_documento', 'like', '%' . $request->tipo_documento . '%');
        }
        
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }
        
        $documentos = $query->orderBy('created_at', 'desc')->paginate(15);
        
        return view('word-documents.index', compact('documentos'));
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
     * Limpiar contenido de caracteres corruptos
     */
    private function limpiarContenido(string $contenido): string
    {
        // Filtrar caracteres no imprimibles
        $contenido = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $contenido);
        
        // Filtrar caracteres UTF-8 malformados
        $contenido = preg_replace('/[\x80-\xFF]/', '', $contenido);
        
        // Solo mantener caracteres ASCII imprimibles
        $contenido = preg_replace('/[^\x20-\x7E\n\r\t]/', '', $contenido);
        
        // Verificar codificación UTF-8
        if (!mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'ISO-8859-1');
        }
        
        return $contenido;
    }

    /**
     * Extraer lista de un elemento ListItem de PHPWord
     */
    private function extraerLista($elementoLista): string
    {
        $textoLista = '';
        
        // Obtener el texto del elemento de lista
        foreach ($elementoLista->getElements() as $elemento) {
            if (method_exists($elemento, 'getText')) {
                $textoLista .= $elemento->getText();
            }
        }
        
        // Determinar el tipo de lista y formatear
        $nivel = $elementoLista->getDepth();
        $indentacion = str_repeat("  ", $nivel);
        
        // Detectar si es lista numerada o con viñetas
        if ($elementoLista->getStyle() && $elementoLista->getStyle()->getNumStyle()) {
            // Lista numerada
            return $indentacion . "1. " . trim($textoLista);
        } else {
            // Lista con viñetas
            return $indentacion . "- " . trim($textoLista);
        }
    }

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
                $textoCelda = '';
                foreach ($celda->getElements() as $elemento) {
                    if (method_exists($elemento, 'getText')) {
                        $textoCelda .= $elemento->getText();
                    }
                }
                $celdas[] = trim($textoCelda);
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
     * Verificar si una línea es parte de una tabla
     */
    private function esLineaDeTabla(string $linea): bool
    {
        // Detectar líneas que contienen separadores de tabla (|)
        if (strpos($linea, '|') !== false && substr_count($linea, '|') >= 2) {
            return true;
        }
        
        // Detectar líneas que contienen múltiples columnas separadas por espacios o tabs
        // Solo si la línea es lo suficientemente larga y tiene al menos 3 columnas
        if (strlen($linea) > 60) {
            $palabras = preg_split('/\s{2,}/', $linea);
            if (count($palabras) >= 3) {
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
            return array_map('trim', array_filter($columnas, function($col) {
                return !empty(trim($col));
            }));
        }
        
        // Dividir por espacios múltiples
        $columnas = preg_split('/\s{2,}/', $linea);
        
        // Limpiar columnas vacías
        return array_filter($columnas, function($col) {
            return !empty(trim($col));
        });
    }


    /**
     * Limpiar contenido estructurado para evitar errores de JSON
     */
    private function limpiarContenidoEstructurado(array $contenido): array
    {
        $limpiado = [];
        
        foreach ($contenido as $clave => $valor) {
            if (is_string($valor)) {
                $limpiado[$clave] = $this->limpiarContenido($valor);
            } elseif (is_array($valor)) {
                $limpiado[$clave] = $this->limpiarContenidoEstructurado($valor);
            } else {
                $limpiado[$clave] = $valor;
            }
        }
        
        return $limpiado;
    }
}
                    