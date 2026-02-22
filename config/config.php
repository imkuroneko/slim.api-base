<?php

/**
 * Configuración General de la Aplicación
 */

return [
    'app' => [
        'name' => getenv('APP_NAME') ?: 'API-Base',
        'env' => getenv('APP_ENV') ?: 'production',
        'debug' => getenv('APP_DEBUG') ?: false,
        'url' => getenv('APP_URL') ?: 'http://localhost:8000',
    ],
    'jwt' => [
        'secret' => getenv('JWT_SECRET') ?: 'change-this-secret-key',
        'algorithm' => getenv('JWT_ALGORITHM') ?: 'HS256',
        'expiration' => getenv('JWT_EXPIRATION') ?: 3600,
    ],
    'rateLimit' => [
        'requests' => getenv('RATE_LIMIT_REQUESTS') ?: 100,
        'window' => getenv('RATE_LIMIT_WINDOW') ?: 3600,
    ],
    'cors' => [
        'origin' => getenv('CORS_ORIGIN') ?: 'http://localhost:3000',
        'methods' => explode(',', getenv('CORS_METHODS') ?: 'GET,POST,PUT,PATCH,DELETE,OPTIONS'),
        'headers' => explode(',', getenv('CORS_HEADERS') ?: 'Content-Type,Authorization'),
    ],
];
