<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Documentos Word
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar las opciones para el módulo de documentos Word
    |
    */

    // Tamaño máximo de archivo en KB (600 KB = 600)
    'max_file_size_kb' => env('WORD_DOCUMENTS_MAX_SIZE_KB', 600),

    // Tipos de archivo permitidos
    'allowed_types' => ['doc', 'docx'],

    // Directorio de almacenamiento
    'storage_path' => env('WORD_DOCUMENTS_STORAGE_PATH', 'word-documents'),

    // Configuración de procesamiento
    'processing' => [
        'extract_text' => true,
        'extract_metadata' => true,
        'structure_content' => true,
        'save_markdown' => true,
    ],

    // Configuración de metadatos
    'metadata' => [
        'extract_creator' => true,
        'extract_created_date' => true,
        'extract_modified_date' => true,
        'extract_title' => true,
        'extract_subject' => true,
        'extract_keywords' => true,
        'extract_category' => true,
        'extract_company' => true,
        'extract_manager' => true,
    ],
];
