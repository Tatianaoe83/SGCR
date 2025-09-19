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
            'explicacion' => ['explicar', 'como', 'que es', 'definir', 'significado']
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
        ];
        
        foreach ($rules as $pattern => $replacement) {
            if (preg_match($pattern, $word)) {
                return preg_replace($pattern, $replacement, $word);
            }
        }
        
        return $word;
    }
}
