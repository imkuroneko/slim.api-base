<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

/**
 * Middleware para manejar CORS
 */
class CORSMiddleware
{
    private string $origin;
    private array $methods;
    private array $headers;

    public function __construct(string $origin, array $methods, array $headers)
    {
        $this->origin = $origin;
        $this->methods = $methods;
        $this->headers = $headers;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);

        // Agregar headers de CORS
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->origin)
            ->withHeader('Access-Control-Allow-Methods', implode(', ', $this->methods))
            ->withHeader('Access-Control-Allow-Headers', implode(', ', $this->headers))
            ->withHeader('Access-Control-Max-Age', '86400')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }
}
