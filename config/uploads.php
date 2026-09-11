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

    // Tamano fijo de cada parte en MB. Vacio = se calcula con los limites de
    // PHP del servidor (upload_max_filesize / post_max_size), topado por
    // chunk_max_mb. Partes mas grandes = menos requests = subida mas rapida.
    'chunk_size_mb' => env('UPLOAD_CHUNK_MB'),

    // Tope del calculo automatico. Algunos proxies (nginx, LiteSpeed,
    // ModSecurity) cortan requests grandes aunque PHP los acepte.
    'chunk_max_mb' => (int) env('UPLOAD_CHUNK_MAX_MB', 8),

    // Partes que el navegador envia al mismo tiempo. Tapa la latencia de
    // cada request; mas de 4-6 suele saturar los procesos del hosting.
    'chunk_concurrency' => (int) env('UPLOAD_CHUNK_CONCURRENCY', 4),

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
    ],

];
