<?php

namespace App\Controllers;

use App\Models\User;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Controlador de Usuarios
 */
class UserController extends BaseController
{
    private User $userModel;
    private string $jwtSecret;
    private int $jwtExpiration;

    public function __construct(User $userModel, string $jwtSecret, int $jwtExpiration)
    {
        $this->userModel = $userModel;
        $this->jwtSecret = $jwtSecret;
        $this->jwtExpiration = $jwtExpiration;
    }

    /**
     * GET /users - Obtener todos los usuarios
     */
    public function getAll(Request $request, Response $response): Response
    {
        try {
            $users = $this->userModel->getAll();

            return $this->successResponse($response, $users, 200);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $response,
                'Error al obtener usuarios: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * GET /users/{id} - Obtener usuario por ID
     */
    public function getById(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = $args['id'] ?? null;

            if (!$userId || !is_numeric($userId)) {
                return $this->errorResponse($response, 'ID inválido', 400);
            }

            $user = $this->userModel->getById((int) $userId);

            if (!$user) {
                return $this->errorResponse($response, 'Usuario no encontrado', 404);
            }

            return $this->successResponse($response, $user, 200);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $response,
                'Error al obtener usuario: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * POST /users - Crear nuevo usuario
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $this->getBodyData($request);

            // Validar campos requeridos
            $errors = $this->validateRequired($data, ['name', 'email', 'password']);

            if (!empty($errors)) {
                return $this->errorResponse($response, 'Datos inválidos', 422, $errors);
            }

            // Validar email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->errorResponse(
                    $response,
                    'Email inválido',
                    422,
                    ['email' => 'El email no es válido']
                );
            }

            $userExists = $this->userModel->findByEmail($data['email']);
            if ($userExists) {
                return $this->errorResponse(
                    $response,
                    'Email ya registrado',
                    409,
                    ['email' => 'Este email ya existe']
                );
            }

            // Crear usuario
            $user = $this->userModel->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            ]);

            return $this->successResponse($response, $user, 201);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $response,
                'Error al crear usuario: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * PUT /users/{id} - Actualizar usuario completo
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = $args['id'] ?? null;

            if (!$userId || !is_numeric($userId)) {
                return $this->errorResponse($response, 'ID inválido', 400);
            }

            $user = $this->userModel->getById((int) $userId);
            if (!$user) {
                return $this->errorResponse($response, 'Usuario no encontrado', 404);
            }

            $data = $this->getBodyData($request);
            $errors = $this->validateRequired($data, ['name', 'email']);

            if (!empty($errors)) {
                return $this->errorResponse($response, 'Datos inválidos', 422, $errors);
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->errorResponse(
                    $response,
                    'Email inválido',
                    422,
                    ['email' => 'El email no es válido']
                );
            }

            // Verificar email único (excepto el usuario actual)
            if ($data['email'] !== $user['email']) {
                $emailExists = $this->userModel->findByEmail($data['email']);
                if ($emailExists) {
                    return $this->errorResponse(
                        $response,
                        'Email ya registrado',
                        409,
                        ['email' => 'Este email ya existe']
                    );
                }
            }

            $updatedUser = $this->userModel->update((int) $userId, [
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            return $this->successResponse($response, $updatedUser, 200);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $response,
                'Error al actualizar usuario: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * PATCH /users/{id} - Actualizar parcialmente usuario
     */
    public function patch(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = $args['id'] ?? null;

            if (!$userId || !is_numeric($userId)) {
                return $this->errorResponse($response, 'ID inválido', 400);
            }

            $user = $this->userModel->getById((int) $userId);
            if (!$user) {
                return $this->errorResponse($response, 'Usuario no encontrado', 404);
            }

            $data = $this->getBodyData($request);
            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }

            if (isset($data['email'])) {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    return $this->errorResponse(
                        $response,
                        'Email inválido',
                        422,
                        ['email' => 'El email no es válido']
                    );
                }

                if ($data['email'] !== $user['email']) {
                    $emailExists = $this->userModel->findByEmail($data['email']);
                    if ($emailExists) {
                        return $this->errorResponse(
                            $response,
                            'Email ya registrado',
                            409,
                            ['email' => 'Este email ya existe']
                        );
                    }
                }

                $updateData['email'] = $data['email'];
            }

            if (empty($updateData)) {
                return $this->errorResponse($response, 'No hay datos para actualizar', 400);
            }

            $updatedUser = $this->userModel->update((int) $userId, $updateData);

            return $this->successResponse($response, $updatedUser, 200);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $response,
                'Error al actualizar usuario: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * DELETE /users/{id} - Eliminar usuario
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = $args['id'] ?? null;

            if (!$userId || !is_numeric($userId)) {
                return $this->errorResponse($response, 'ID inválido', 400);
            }

            $user = $this->userModel->getById((int) $userId);
            if (!$user) {
                return $this->errorResponse($response, 'Usuario no encontrado', 404);
            }

            $this->userModel->delete((int) $userId);

            return $this->successResponse($response, null, 204);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $response,
                'Error al eliminar usuario: ' . $e->getMessage(),
                500
            );
        }
    }
}
