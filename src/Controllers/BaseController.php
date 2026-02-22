<?php

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;

/**
 * Controlador Base con manejo de errores
 */
abstract class BaseController
{
    /**
     * Respuesta JSON exitosa
     *
     * @param Response $response
     * @param mixed $data
     * @param int $status
     * @return Response
     */
    protected function successResponse(Response $response, mixed $data = null, int $status = 200): Response
    {
        $payload = [
            'success' => true,
            'status' => $status,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        $response->getBody()->write(json_encode($payload));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }

    /**
     * Respuesta JSON con error
     *
     * @param Response $response
     * @param string $message
     * @param int $status
     * @param array $errors
     * @return Response
     */
    protected function errorResponse(Response $response, string $message, int $status = 400, array $errors = []): Response
    {
        $payload = [
            'success' => false,
            'status' => $status,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        $response->getBody()->write(json_encode($payload));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }

    /**
     * Obtener el usuario autenticado
     *
     * @param Request $request
     * @return object|null
     */
    protected function getAuthUser(Request $request): ?object
    {
        return $request->getAttribute('user');
    }

    /**
     * Obtener datos del body
     *
     * @param Request $request
     * @return array
     */
    protected function getBodyData(Request $request): array
    {
        $body = $request->getParsedBody();
        return is_array($body) ? $body : [];
    }

    /**
     * Validar datos requeridos
     *
     * @param array $data
     * @param array $required
     * @return array
     */
    protected function validateRequired(array $data, array $required): array
    {
        $errors = [];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[$field] = "El campo '{$field}' es requerido";
            }
        }

        return $errors;
    }
}
