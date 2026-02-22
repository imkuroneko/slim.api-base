<?php

namespace App\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Controlador de Autenticación
 */
class AuthController extends BaseController
{
    private User $userModel;
    private string $jwtSecret;
    private string $jwtAlgorithm;
    private int $jwtExpiration;

    public function __construct(User $userModel, string $jwtSecret, string $jwtAlgorithm, int $jwtExpiration)
    {
        $this->userModel = $userModel;
        $this->jwtSecret = $jwtSecret;
        $this->jwtAlgorithm = $jwtAlgorithm;
        $this->jwtExpiration = $jwtExpiration;
    }

    /**
     * POST /auth/login - Autenticar usuario y obtener token JWT
     */
    public function login(Request $request, Response $response): Response
    {
        try {
            $data = $this->getBodyData($request);

            // Validar campos requeridos
            $errors = $this->validateRequired($data, ['email', 'password']);

            if (!empty($errors)) {
                return $this->errorResponse($response, 'Credenciales incompletas', 422, $errors);
            }

            // Buscar usuario por email
            $user = $this->userModel->findByEmail($data['email']);

            if (!$user) {
                return $this->errorResponse($response, 'Credenciales inválidas', 401);
            }

            // Verificar contraseña
            if (!password_verify($data['password'], $user['password'])) {
                return $this->errorResponse($response, 'Credenciales inválidas', 401);
            }

            // Generar JWT
            $token = $this->generateJWT($user);

            return $this->successResponse(
                $response,
                [
                    'token' => $token,
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                    ],
                ],
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $response,
                'Error en autenticación: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * POST /auth/register - Registrar nuevo usuario
     */
    public function register(Request $request, Response $response): Response
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

            // Verificar si el usuario ya existe
            $userExists = $this->userModel->findByEmail($data['email']);
            if ($userExists) {
                return $this->errorResponse(
                    $response,
                    'Email ya registrado',
                    409,
                    ['email' => 'Este email ya existe']
                );
            }

            // Validar longitud de contraseña
            if (strlen($data['password']) < 8) {
                return $this->errorResponse(
                    $response,
                    'Contraseña insegura',
                    422,
                    ['password' => 'La contraseña debe tener mínimo 8 caracteres']
                );
            }

            // Crear usuario
            $user = $this->userModel->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            ]);

            // Generar JWT
            $token = $this->generateJWT($user);

            return $this->successResponse(
                $response,
                [
                    'token' => $token,
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                    ],
                ],
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $response,
                'Error en registro: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Generar JWT token
     *
     * @param array $user
     * @return string
     */
    private function generateJWT(array $user): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->jwtExpiration;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'userId' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
        ];

        return JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);
    }
}
