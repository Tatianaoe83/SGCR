<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://ia-proser.dynalias.org/'),
        'username' => env('OLLAMA_USERNAME'),
        'password' => env('OLLAMA_PASSWORD'),
        'model' => env('OLLAMA_MODEL', 'llama3.2:1b'),
        'timeout' => env('OLLAMA_TIMEOUT', 120),
    ],

    'ilovepdf' => [
        'public' => env('ILOVEPDF_PUBLIC_KEY'),
        'secret' => env('ILOVEPDF_SECRET_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Servicios de IA de Pago
    |--------------------------------------------------------------------------
    |
    | Configuración para modelos de IA de pago (OpenAI, Anthropic, Google)
    | 
    | Proveedores disponibles:
    | - openai: GPT-4 Turbo (recomendado: gpt-4.1-mini o gpt-4o)
    | - anthropic: Claude 3 Sonnet (recomendado: claude-3-sonnet-20240229)
    | - google: Gemini Pro (recomendado: gemini-pro o gemini-1.5-pro)
    |
    */
    'ai' => [
        'provider' => env('AI_PROVIDER', 'openai'), // openai, anthropic, google
        'api_key' => env('AI_API_KEY'),
        // Mini: reescribir búsqueda (barato). Chat: respuestas al usuario.
        'model' => env('AI_MODEL'),
        'chat_model' => env('AI_CHAT_MODEL') ?: env('AI_MODEL'),
        'timeout' => env('AI_TIMEOUT', 30),
        'chat_timeout' => env('AI_CHAT_TIMEOUT', 90),
        'embed_model' => env('AI_EMBED_MODEL', 'text-embedding-3-small'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OCR de documentos (PDF -> imagen -> texto)
    |--------------------------------------------------------------------------
    |
    | dpi: 200 es el punto óptimo para texto de oficina. 
    | concurrency: páginas OCR en vuelo al mismo tiempo.
    | model: gpt-4.1-mini lee tablas y diagramas mejor que nano.
    |
    */
    'ocr' => [
        'dpi' => (int) (env('AI_OCR_DPI') ?: 200),
        'model' => env('AI_OCR_MODEL') ?: 'gpt-4.1-mini',
        'detail' => env('AI_OCR_DETAIL') ?: 'high',
        'concurrency' => (int) (env('AI_OCR_CONCURRENCY') ?: 5),
        'max_output_tokens' => (int) (env('AI_OCR_MAX_OUTPUT_TOKENS') ?: 8000),
        'timeout' => (int) (env('AI_OCR_TIMEOUT') ?: 180),
        'retries' => (int) (env('AI_OCR_RETRIES') ?: 2),
    ],
];
