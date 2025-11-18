<?php

namespace App\Services;

class NLPProcessor
{
    private $stopwords;
    private $entityPatterns;
    
    public function __construct()
    {
        $this->stopwords = [
            'el', 'la', 'de', 'que', 'y', 'a', 'en', 'un', 'es', 'se', 'no', 'te', 'lo', 'le', 'da', 'su', 'por', 'son', 'con', 'para', 'como', 'las', 'del', 'los', 'una', 'qué', 'cómo', 'cuál', 'donde', 'cuando', 'porque', 'pero', 'muy', 'sin', 'sobre', 'también', 'me', 'ya', 'si', 'o'
        ];
        
        $this->entityPatterns = [
            'precios' => ['precio', 'costo', 'valor', 'cuanto', 'dinero', 'pagar', 'cobrar'],
            'tiempo' => ['horario', 'hora', 'cuando', 'tiempo', 'fecha', 'día'],
            'ubicacion' => ['donde', 'ubicación', 'dirección', 'lugar', 'sitio'],
            'productos' => ['producto', 'servicio', 'artículo', 'item'],
            'comparacion' => ['comparar', 'diferencia', 'mejor', 'peor', 'versus', 'vs'],
            'explicacion' => ['explicar', 'como', 'que es', 'definir', 'significado'],
            // Nuevos patrones para razonamiento semántico
            'procedimientos' => ['procedimiento', 'proceso', 'metodología', 'protocolo', 'guía', 'manual'],
            'lineamientos' => ['lineamiento', 'lineamientos', 'directriz', 'directrices', 'norma', 'normas', 'política', 'políticas', 'regla', 'reglas'],
            'establecimiento' => ['establecer', 'crear', 'definir', 'implementar', 'desarrollar', 'formular'],
            'gestion' => ['gestión', 'administración', 'manejo', 'control', 'supervisión'],
            'documentos' => ['documento', 'formato', 'plantilla', 'archivo', 'registro'],
            'responsabilidades' => ['responsable', 'encargado', 'ejecutor', 'responsabilidad', 'cargo', 'puesto']
        ];
    }

    public function normalize($text)
    {
        // Convertir a minúsculas
        $text = mb_strtolower($text, 'UTF-8');
        
        // Remover puntuación excepto interrogación
        $text = preg_replace('/[^\w\s¿?áéíóúñü]/u', ' ', $text);
        
        // Remover espacios múltiples
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }

    public function extractKeywords($text)
    {
        $words = explode(' ', $text);
        
        // Filtrar stopwords y palabras muy cortas
        $keywords = array_filter($words, function($word) {
            return !in_array($word, $this->stopwords) && 
                   mb_strlen($word, 'UTF-8') > 2 &&
                   !is_numeric($word);
        });
        
        // Aplicar stemming básico
        $keywords = array_map([$this, 'stem'], $keywords);
        
        return array_unique(array_values($keywords));
    }

    public function extractEntities($text)
    {
        $entities = [];
        
        foreach ($this->entityPatterns as $entityType => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($text, $pattern) !== false) {
                    $entities[$entityType][] = $pattern;
                }
            }
        }
        
        // Extraer números
        if (preg_match_all('/\d+/', $text, $matches)) {
            $entities['numeros'] = $matches[0];
        }
        
        // Extraer fechas básicas
        if (preg_match_all('/\b(lunes|martes|miércoles|jueves|viernes|sábado|domingo|hoy|mañana|ayer)\b/', $text, $matches)) {
            $entities['fechas'] = $matches[0];
        }
        
        return $entities;
    }
    
    /**
     * Analizar la intención semántica de la consulta
     */
    public function analyzeIntent($text)
    {
        $normalizedText = $this->normalize($text);
        $entities = $this->extractEntities($normalizedText);
        $keywords = $this->extractKeywords($normalizedText);
        
        $intent = [
            'primary_intent' => 'unknown',
            'secondary_intents' => [],
            'semantic_keywords' => [],
            'confidence' => 0.0
        ];
        
        // Detectar intención principal basada en entidades
        if (isset($entities['procedimientos']) && isset($entities['lineamientos'])) {
            $intent['primary_intent'] = 'buscar_procedimientos_lineamientos';
            $intent['semantic_keywords'] = array_merge(
                ['procedimiento', 'proceso', 'metodología', 'protocolo'],
                ['lineamiento', 'directriz', 'norma', 'política', 'regla']
            );
            $intent['confidence'] = 0.9;
        } elseif (isset($entities['procedimientos'])) {
            $intent['primary_intent'] = 'buscar_procedimientos';
            $intent['semantic_keywords'] = ['procedimiento', 'proceso', 'metodología', 'protocolo', 'guía', 'manual'];
            $intent['confidence'] = 0.8;
        } elseif (isset($entities['lineamientos'])) {
            $intent['primary_intent'] = 'buscar_lineamientos';
            $intent['semantic_keywords'] = ['lineamiento', 'directriz', 'norma', 'política', 'regla', 'directrices'];
            $intent['confidence'] = 0.8;
        } elseif (isset($entities['establecimiento'])) {
            $intent['primary_intent'] = 'buscar_establecimiento_procesos';
            $intent['semantic_keywords'] = ['establecer', 'crear', 'definir', 'implementar', 'desarrollar'];
            $intent['confidence'] = 0.7;
        }
        
        // Detectar intenciones secundarias
        if (isset($entities['responsabilidades'])) {
            $intent['secondary_intents'][] = 'incluir_responsables';
        }
        if (isset($entities['documentos'])) {
            $intent['secondary_intents'][] = 'incluir_documentos';
        }
        if (isset($entities['gestion'])) {
            $intent['secondary_intents'][] = 'incluir_gestion';
        }
        
        return $intent;
    }

    public function stem($word)
    {
        $rules = [
            '/mente$/' => '', // rápidamente -> rápid
            '/ación$/' => '', // información -> inform
            '/ando$/' => 'ar', // cantando -> cant
            '/iendo$/' => 'er', // corriendo -> corr
            '/ado$/' => 'ar', // terminado -> termin
            '/ido$/' => 'er', // partido -> part
            '/ar$/' => 'ar', // cantar -> cant
            '/er$/' => 'er', // comer -> com
            '/ir$/' => 'ir', // vivir -> viv
            '/mientos?$/' => '', // lineamientos -> lineam
            '/ción$/' => '', // gestión -> gest
        ];
        
        foreach ($rules as $pattern => $replacement) {
            if (preg_match($pattern, $word)) {
                return preg_replace($pattern, $replacement, $word);
            }
        }
        
        return $word;
    }
    
    /**
     * Expandir términos semánticamente relacionados
     */
    public function expandSemanticTerms($keywords)
    {
        $expansions = [
            'lineamiento' => ['lineamiento', 'lineamientos', 'directriz', 'directrices', 'norma', 'normas', 'política', 'políticas'],
            'procedimiento' => ['procedimiento', 'procedimientos', 'proceso', 'procesos', 'metodología', 'protocolo', 'guía'],
            'establecer' => ['establecer', 'crear', 'definir', 'implementar', 'desarrollar', 'formular'],
            'responsable' => ['responsable', 'encargado', 'ejecutor', 'responsabilidad', 'cargo'],
            'documento' => ['documento', 'formato', 'plantilla', 'archivo', 'registro'],
            'gestión' => ['gestión', 'administración', 'manejo', 'control', 'supervisión']
        ];
        
        $expandedKeywords = [];
        
        foreach ($keywords as $keyword) {
            if (!is_string($keyword) && !is_numeric($keyword)) {
                continue;
            }

            $keyword = strtolower(trim((string) $keyword));

            if ($keyword === '') {
                continue;
            }

            $expandedKeywords[] = $keyword;
            
            foreach ($expansions as $baseWord => $synonyms) {
                if (strpos($keyword, $baseWord) !== false || in_array($keyword, $synonyms, true)) {
                    $expandedKeywords = array_merge($expandedKeywords, $synonyms);
                }
            }
        }

        $expandedKeywords = array_map(function ($value) {
            if (is_string($value) || is_numeric($value)) {
                return strtolower(trim((string) $value));
            }
            return null;
        }, $expandedKeywords);

        $expandedKeywords = array_filter($expandedKeywords, fn($value) => is_string($value) && $value !== '');
        
        return array_values(array_unique($expandedKeywords));
    }
}
