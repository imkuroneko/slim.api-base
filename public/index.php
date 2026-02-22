<?php

/**
 * Slim Framework API Base
 * Punto de entrada de la aplicación
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Symfony\Component\Dotenv\Dotenv;
use App\Database\Connection;
use App\Controllers\{UserController, AuthController};
use App\Models\User;
use App\Middleware\{JWTMiddleware, CORSMiddleware, RateLimitMiddleware, InputSanitizationMiddleware};

// Cargar variables de entorno
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

// Cargar configuración
$config = include __DIR__ . '/../config/config.php';
$dbConfig = include __DIR__ . '/../config/database.php';

// Crear aplicación Slim
$app = AppFactory::create();

// Agregar middleware de errores y excepciones
$errorMiddleware = $app->addErrorMiddleware(
    $config['app']['debug'],
    true,
    true
);

// Configurar manejador de errores
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->forceContentType('application/json');

// Setup DI Container
$container = $app->getContainer();

// Registrar conexión a BD
$container->set('db', function () use ($dbConfig) {
    $connection = new Connection($dbConfig);
    return $connection->connect();
});

// Registrar modelos
$container->set(User::class, function ($c) {
    return new User($c->get('db'));
});

// Registrar controladores
$container->set(UserController::class, function ($c) use ($config) {
    return new UserController(
        $c->get(User::class),
        $config['jwt']['secret'],
        $config['jwt']['expiration']
    );
});

$container->set(AuthController::class, function ($c) use ($config) {
    return new AuthController(
        $c->get(User::class),
        $config['jwt']['secret'],
        $config['jwt']['algorithm'],
        $config['jwt']['expiration']
    );
});

// ===== MIDDLEWARE GLOBAL =====

// 1. CORS Middleware
$app->add(new CORSMiddleware(
    $config['cors']['origin'],
    $config['cors']['methods'],
    $config['cors']['headers']
));

// 2. Rate Limiting Middleware
$app->add(new RateLimitMiddleware(
    $config['rateLimit']['requests'],
    $config['rateLimit']['window'],
    sys_get_temp_dir()
));

// 3. Input Sanitization Middleware
$app->add(new InputSanitizationMiddleware());

// 4. JWT Middleware
$app->add(new JWTMiddleware(
    $config['jwt']['secret'],
    $config['jwt']['algorithm']
));

// ===== RUTAS =====

// Health check (sin autenticación)
$app->get('/health', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Rutas de autenticación
$app->post('/auth/login', [AuthController::class, 'login']);
$app->post('/auth/register', [AuthController::class, 'register']);

// Rutas de usuarios (requieren autenticación)
$app->get('/users', [UserController::class, 'getAll']);
$app->get('/users/{id}', [UserController::class, 'getById']);
$app->post('/users', [UserController::class, 'create']);
$app->put('/users/{id}', [UserController::class, 'update']);
$app->patch('/users/{id}', [UserController::class, 'patch']);
$app->delete('/users/{id}', [UserController::class, 'delete']);

// Ruta 404
$app->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], '/{routes:.+}', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'error' => 'Ruta no encontrada',
        'status' => 404,
    ]));
    return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
});

// Ejecutar aplicación
$app->run();
