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
        'model' => env('AI_MODEL'), // Dejar null para usar modelo por defecto del proveedor
        'timeout' => env('AI_TIMEOUT', 30),
    ],
];
