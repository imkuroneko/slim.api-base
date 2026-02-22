<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Firebase\JWT\ExpiredException;

/**
 * Middleware para validar JWT
 */
class JWTMiddleware
{
    private string $secret;
    private string $algorithm;
    private array $excludedRoutes = [
        '/auth/login',
        '/auth/register',
        '/health',
    ];

    public function __construct(string $secret, string $algorithm = 'HS256')
    {
        $this->secret = $secret;
        $this->algorithm = $algorithm;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Rutas sin autenticación requerida
        if (in_array($request->getUri()->getPath(), $this->excludedRoutes)) {
            return $handler->handle($request);
        }

        try {
            $authHeader = $request->getHeaderLine('Authorization');

            if (!$authHeader) {
                return $this->errorResponse('Token no proporcionado', 401);
            }

            // Extraer Bearer token
            if (!preg_match('/Bearer\s+(.+?)(?:\s|$)/', $authHeader, $matches)) {
                return $this->errorResponse('Formato de token inválido', 401);
            }

            $token = $matches[1];

            // Decodificar y validar token
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));

            // Guardar decoded token en el request
            $request = $request->withAttribute('user', $decoded);

            return $handler->handle($request);
        } catch (ExpiredException $e) {
            return $this->errorResponse('Token expirado', 401);
        } catch (\Exception $e) {
            return $this->errorResponse('Token inválido: ' . $e->getMessage(), 401);
        }
    }

    private function errorResponse(string $message, int $code): Response
    {
        $response = new Response();
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }
}
