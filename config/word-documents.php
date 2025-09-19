<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración de Indexación de Documentos Word
    |--------------------------------------------------------------------------
    |
    | Configuración específica para la indexación y búsqueda de documentos Word
    | en el sistema de chatbot.
    |
    */

    'indexing' => [
        /*
         * Solo indexar documentos con este estado
         */
        'required_status' => 'procesado',

        /*
         * Tamaño máximo de chunks en caracteres
         */
        'max_chunk_size' => 500,

        /*
         * Número máximo de chunks por documento
         */
        'max_chunks_per_document' => 20,

        /*
         * Número máximo de keywords por documento
         */
        'max_keywords_per_document' => 20,

        /*
         * Palabras vacías en español para filtrar
         */
        'stop_words' => [
            'el', 'la', 'de', 'que', 'y', 'a', 'en', 'un', 'es', 'se', 'no', 'te', 'lo', 'le', 'da', 'su', 'por', 'son',
            'con', 'para', 'como', 'las', 'del', 'los', 'una', 'al', 'pero', 'sus', 'le', 'ya', 'todo', 'esta', 'fue',
            'han', 'ser', 'su', 'hacer', 'otros', 'puede', 'tiene', 'más', 'muy', 'hasta', 'desde', 'cuando', 'entre',
            'sin', 'sobre', 'también', 'me', 'si', 'había', 'vez', 'donde', 'quien', 'antes', 'después', 'tanto',
            'poco', 'mucho', 'bien', 'aquí', 'allí', 'ahora', 'entonces', 'siempre', 'nunca', 'cada', 'algunos'
        ],

        /*
         * Longitud mínima de palabra para considerar como keyword
         */
        'min_keyword_length' => 3,
    ],

    'search' => [
        /*
         * Número máximo de resultados por defecto
         */
        'default_limit' => 10,

        /*
         * Score mínimo para considerar un resultado relevante
         */
        'min_relevance_score' => 1,

        /*
         * Tiempo de caché para resultados de búsqueda (en segundos)
         */
        'cache_timeout' => 300, // 5 minutos

        /*
         * Número máximo de chunks coincidentes a mostrar por documento
         */
        'max_matched_chunks' => 3,

        /*
         * Pesos para el cálculo de relevancia
         */
        'relevance_weights' => [
            'title_match' => 10,        // Coincidencia en título
            'content_match' => 2,       // Coincidencia en contenido
            'keyword_match' => 5,       // Coincidencia en keywords
            'recency_bonus' => 10,      // Bonus por documento reciente (máximo)
        ],

        /*
         * Configuración para boost por recencia
         */
        'recency' => [
            'enabled' => true,
            'max_days' => 30,           // Días para aplicar boost
            'max_boost_percentage' => 0.2, // 20% de boost máximo
        ],
    ],

    'maintenance' => [
        /*
         * Configuración de comandos programados
         */
        'scheduled_indexing' => [
            'enabled' => true,
            'hourly_check' => true,     // Verificar nuevos documentos cada hora
            'weekly_full_reindex' => true, // Re-indexación completa semanal
            'daily_cleanup' => true,    // Limpieza diaria del índice
        ],

        /*
         * Configuración de logs
         */
        'logging' => [
            'enabled' => true,
            'level' => 'info',          // debug, info, warning, error
            'include_document_content' => false, // Para debugging
        ],

        /*
         * Configuración de batch processing
         */
        'batch_processing' => [
            'default_batch_size' => 50,
            'max_batch_size' => 200,
            'processing_timeout' => 300, // 5 minutos
        ],
    ],

    'scout' => [
        /*
         * Configuración específica de Scout para WordDocument
         */
        'chunk_sizes' => [
            'small' => 250,     // Para documentos cortos
            'medium' => 500,    // Por defecto
            'large' => 1000,    // Para documentos muy largos
        ],

        /*
         * Configuración de fallback cuando Scout no está disponible
         */
        'fallback' => [
            'enabled' => true,
            'use_database_search' => true,
            'search_fields' => ['contenido_texto', 'elemento.nombre'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Analytics
    |--------------------------------------------------------------------------
    |
    | Configuración para el seguimiento de búsquedas y uso del sistema
    |
    */
    'analytics' => [
        'track_searches' => true,
        'track_results' => true,
        'track_performance' => true,
        
        /*
         * Limpieza automática de analytics antiguos
         */
        'cleanup' => [
            'enabled' => true,
            'keep_months' => 6,         // Mantener analytics por 6 meses
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Performance
    |--------------------------------------------------------------------------
    |
    | Configuraciones para optimizar el rendimiento del sistema
    |
    */
    'performance' => [
        /*
         * Configuración de caché
         */
        'cache' => [
            'search_results' => true,
            'document_metadata' => true,
            'keywords_extraction' => true,
        ],

        /*
         * Configuración de límites
         */
        'limits' => [
            'max_search_terms' => 10,
            'max_content_length' => 50000, // 50KB por documento
            'max_concurrent_indexing' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Archivos
    |--------------------------------------------------------------------------
    |
    | Configuraciones para el manejo de archivos de documentos
    |
    */
    'file_settings' => [
        /*
         * Tamaño máximo de archivo en KB (por defecto 5MB = 5120 KB)
         */
        'max_file_size_kb' => 5120,
        
        /*
         * Tipos de archivo permitidos
         */
        'allowed_extensions' => ['doc', 'docx', 'pdf', 'xls', 'xlsx'],
        
        /*
         * Directorio de almacenamiento
         */
        'storage_path' => 'elementos/formato',
    ],

];