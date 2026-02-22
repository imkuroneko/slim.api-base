<?php

/**
 * Funciones de ayuda (helpers)
 */

/**
 * Cargar variables de entorno
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function env(string $key, mixed $default = null): mixed
{
    return getenv($key) ?: $default;
}

/**
 * Hashing de contraseña
 *
 * @param string $password
 * @return string
 */
function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verificar contraseña
 *
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/**
 * JSON response helper
 *
 * @param array $data
 * @param int $status
 * @return string
 */
function jsonResponse(array $data, int $status = 200): string
{
    http_response_code($status);
    header('Content-Type: application/json');
    return json_encode($data);
}

/**
 * Sanitizar string
 *
 * @param string $data
 * @return string
 */
function sanitize(string $data): string
{
    $data = trim($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    $data = str_replace("\0", '', $data);
    return preg_replace('/[\x00-\x1F\x7F]/u', '', $data);
}

/**
 * Obtener IP del cliente
 *
 * @return string
 */
function getClientIp(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }

    return $ip ?: '127.0.0.1';
}

/**
 * Loguear mensaje
 *
 * @param string $message
 * @param string $level
 * @return void
 */
function log_message(string $message, string $level = 'info'): void
{
    $timestamp = date('Y-m-d H:i:s');
    $logPath = __DIR__ . '/../logs';

    if (!is_dir($logPath)) {
        mkdir($logPath, 0755, true);
    }

    $logFile = $logPath . '/' . date('Y-m-d') . '.log';
    $logEntry = "[{$timestamp}] [{$level}] {$message}\n";

    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
