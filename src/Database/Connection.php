<?php

namespace App\Database;

use PDO;
use PDOException;

/**
 * Clase para manejar conexiones ODBC con MySQL y PostgreSQL
 */
class Connection
{
    private ?PDO $connection = null;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Conectar a la base de datos
     *
     * @return PDO
     * @throws PDOException
     */
    public function connect(): PDO
    {
        try {
            if ($this->connection === null) {
                $dsn = $this->buildDSN();
                
                $this->connection = new PDO(
                    $dsn,
                    $this->config['user'],
                    $this->config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            }

            return $this->connection;
        } catch (PDOException $e) {
            throw new PDOException('Error de conexión a BD: ' . $e->getMessage());
        }
    }

    /**
     * Construir DSN según el driver
     *
     * @return string
     */
    private function buildDSN(): string
    {
        $driver = strtolower($this->config['driver']);

        if ($driver === 'mysql') {
            return sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $this->config['host'],
                $this->config['port'],
                $this->config['database']
            );
        } elseif ($driver === 'postgresql') {
            return sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database']
            );
        }

        throw new \InvalidArgumentException("Driver no soportado: {$driver}");
    }

    /**
     * Obtener conexión
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connect();
    }

    /**
     * Cerrar conexión
     *
     * @return void
     */
    public function disconnect(): void
    {
        $this->connection = null;
    }
}
