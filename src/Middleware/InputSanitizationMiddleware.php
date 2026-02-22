<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

/**
 * Middleware para sanitizar input
 */
class InputSanitizationMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            try {
                $parsedBody = $request->getParsedBody();

                if (is_array($parsedBody)) {
                    $sanitized = $this->sanitize($parsedBody);
                    $request = $request->withParsedBody($sanitized);
                }
            } catch (\Exception $e) {
                // Si hay error en sanitización, continuar con la solicitud original
            }
        }

        return $handler->handle($request);
    }

    /**
     * Sanitizar datos recursivamente
     *
     * @param mixed $data
     * @return mixed
     */
    private function sanitize(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }

        if (is_string($data)) {
            return $this->sanitizeString($data);
        }

        return $data;
    }

    /**
     * Sanitizar string
     *
     * @param string $data
     * @return string
     */
    private function sanitizeString(string $data): string
    {
        // Remover espacios innecesarios
        $data = trim($data);

        // Escapar caracteres HTML especiales
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

        // Remover secuencias de null bytes
        $data = str_replace("\0", '', $data);

        // Remover caracteres de control
        $data = preg_replace('/[\x00-\x1F\x7F]/u', '', $data);

        return $data;
    }
}
