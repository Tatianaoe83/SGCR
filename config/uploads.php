<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subida por partes (chunked upload)
    |--------------------------------------------------------------------------
    |
    | El hosting compartido no permite modificar upload_max_filesize ni
    | post_max_size. Enviando el archivo en partes pequenas ningun request
    | se acerca a esos limites, sin importar como este configurado el .ini.
    |
    */

    'chunk_size_bytes' => 2 * 1024 * 1024,

    // Carpeta temporal donde se van pegando las partes (disco local, no publico).
    'chunk_temp_dir' => 'chunks',

    // Horas antes de considerar huerfana una subida incompleta.
    'chunk_ttl_hours' => 24,

    /*
    |--------------------------------------------------------------------------
    | Multimedia
    |--------------------------------------------------------------------------
    */

    'video' => [
        // Tipos de elemento que aceptan multimedia en "Archivo del Elemento".
        'tipos_permitidos' => ['Manual'],

        'max_size_bytes' => 450 * 1024 * 1024,

        'extensiones' => ['mp4', 'webm', 'mp3'],

        // Se valida contra el contenido real del archivo, no contra la extension.
        'mimetypes' => [
            'video/mp4',
            'video/webm',
            'audio/mpeg',
            'audio/mp3',
        ],

        'directorio' => 'Archivos/Multimedia',

        // Horas que sobrevive un archivo ya subido al que ningun elemento
        // apunta todavia. Cubre el hueco entre terminar la subida y guardar
        // el formulario; pasado ese plazo se asume que el alta se abandono.
        'huerfano_ttl_horas' => 24,

        // Horas que sobrevive un archivo ya subido al que ningun elemento
        // apunta todavia. Cubre el hueco entre terminar la subida y guardar
        // el formulario; pasado ese plazo se asume que el alta se abandono.
        'huerfano_ttl_horas' => 24,
    ],

];
