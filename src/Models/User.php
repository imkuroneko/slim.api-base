<?php

namespace App\Models;

use PDO;

/**
 * Modelo de Usuario
 */
class User
{
    private PDO $db;
    private string $table = 'users';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtener todos los usuarios
     *
     * @return array
     */
    public function getAll(): array
    {
        try {
            $query = "SELECT id, name, email, created_at FROM {$this->table} ORDER BY created_at DESC";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll() ?: [];
        } catch (\PDOException $e) {
            throw new \Exception('Error en getAll: ' . $e->getMessage());
        }
    }

    /**
     * Obtener usuario por ID
     *
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        try {
            $query = "SELECT id, name, email, created_at FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        } catch (\PDOException $e) {
            throw new \Exception('Error en getById: ' . $e->getMessage());
        }
    }

    /**
     * Encontrar usuario por email
     *
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        try {
            $query = "SELECT id, name, email, password, created_at FROM {$this->table} WHERE email = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$email]);
            return $stmt->fetch() ?: null;
        } catch (\PDOException $e) {
            throw new \Exception('Error en findByEmail: ' . $e->getMessage());
        }
    }

    /**
     * Crear usuario
     *
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $query = "INSERT INTO {$this->table} (name, email, password, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$data['name'], $data['email'], $data['password']]);

            $id = $this->db->lastInsertId();
            return $this->getById((int) $id) ?? [];
        } catch (\PDOException $e) {
            throw new \Exception('Error en create: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar usuario
     *
     * @param int $id
     * @param array $data
     * @return array
     */
    public function update(int $id, array $data): array
    {
        try {
            $fields = [];
            $values = [];

            foreach ($data as $key => $value) {
                $fields[] = "{$key} = ?";
                $values[] = $value;
            }

            $values[] = $id;
            $query = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";

            $stmt = $this->db->prepare($query);
            $stmt->execute($values);

            return $this->getById($id) ?? [];
        } catch (\PDOException $e) {
            throw new \Exception('Error en update: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar usuario
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $query = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            throw new \Exception('Error en delete: ' . $e->getMessage());
        }
    }
}
