<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Firmas Electrónicas
    |--------------------------------------------------------------------------
    */

    // Días de validez del link de firma
    'link_expiration_days' => env('FIRMAS_LINK_EXPIRATION_DAYS', 7),

    // Máximo de días para firmar desde que se envió el correo
    'max_days_to_sign' => env('FIRMAS_MAX_DAYS_TO_SIGN', 7),

    // Recordatorios
    'reminder_frequency' => [
        'diario' => 1,
        'cada_3_dias' => 3,
        'semanal' => 7,
    ],
];
