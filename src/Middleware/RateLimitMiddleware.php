<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

/**
 * Middleware para Rate Limiting
 */
class RateLimitMiddleware
{
    private int $maxRequests;
    private int $window; // en segundos
    private string $storePath;

    public function __construct(int $maxRequests, int $window, string $storePath = '/tmp')
    {
        $this->maxRequests = $maxRequests;
        $this->window = $window;
        $this->storePath = $storePath;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        try {
            $clientIp = $this->getClientIp($request);
            $key = "ratelimit_{$clientIp}";
            $filePath = $this->storePath . '/' . hash('sha256', $key) . '.json';

            // Obtener datos actuales
            $data = [];
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $data = json_decode($content, true) ?? [];
            }

            $now = time();
            $startWindow = $data['start'] ?? $now;

            // Limpiar si la ventana expiró
            if (($now - $startWindow) > $this->window) {
                $data = ['start' => $now, 'count' => 0];
            }

            // Incrementar contador
            $data['count'] = ($data['count'] ?? 0) + 1;

            // Guardar datos
            file_put_contents($filePath, json_encode($data), LOCK_EX);

            // Validar límite
            if ($data['count'] > $this->maxRequests) {
                $response = new Response();
                $response->getBody()->write(json_encode([
                    'error' => 'Demasiadas solicitudes. Límite excedido.',
                    'retry_after' => $this->window - ($now - $startWindow),
                ]));
                return $response->withStatus(429)
                    ->withHeader('Content-Type', 'application/json')
                    ->withHeader('Retry-After', (string) ($this->window - ($now - $startWindow)));
            }

            return $handler->handle($request);
        } catch (\Exception $e) {
            // En caso de error, permitir la solicitud
            return $handler->handle($request);
        }
    }

    private function getClientIp(Request $request): string
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? '';

        // Considerar proxies
        if (!empty($request->getServerParams()['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $request->getServerParams()['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($request->getServerParams()['HTTP_CLIENT_IP'])) {
            $ip = $request->getServerParams()['HTTP_CLIENT_IP'];
        }

        return $ip ?: '127.0.0.1';
    }
}
