<?php

namespace App\Utils;

/**
 * Clase de validación para datos de entrada
 */
class Validator
{
    private array $errors = [];
    private array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Validar que un campo sea requerido
     *
     * @param string $field
     * @param string|null $message
     * @return self
     */
    public function required(string $field, ?string $message = null): self
    {
        if (!isset($this->data[$field]) || empty($this->data[$field])) {
            $this->errors[$field] = $message ?? "El campo '{$field}' es requerido";
        }
        return $this;
    }

    /**
     * Validar email
     *
     * @param string $field
     * @param string|null $message
     * @return self
     */
    public function email(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = $message ?? "El email no es válido";
            }
        }
        return $this;
    }

    /**
     * Validar longitud mínima
     *
     * @param string $field
     * @param int $min
     * @param string|null $message
     * @return self
     */
    public function min(string $field, int $min, ?string $message = null): self
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field] = $message ?? "El campo debe tener mínimo {$min} caracteres";
        }
        return $this;
    }

    /**
     * Validar longitud máxima
     *
     * @param string $field
     * @param int $max
     * @param string|null $message
     * @return self
     */
    public function max(string $field, int $max, ?string $message = null): self
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field] = $message ?? "El campo no debe exceder {$max} caracteres";
        }
        return $this;
    }

    /**
     * Validar que sea numérico
     *
     * @param string $field
     * @param string|null $message
     * @return self
     */
    public function numeric(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? "El campo debe ser numérico";
        }
        return $this;
    }

    /**
     * Validar que sea una URL
     *
     * @param string $field
     * @param string|null $message
     * @return self
     */
    public function url(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
            $this->errors[$field] = $message ?? "La URL no es válida";
        }
        return $this;
    }

    /**
     * Validar expresión regular
     *
     * @param string $field
     * @param string $pattern
     * @param string|null $message
     * @return self
     */
    public function regex(string $field, string $pattern, ?string $message = null): self
    {
        if (isset($this->data[$field]) && !preg_match($pattern, $this->data[$field])) {
            $this->errors[$field] = $message ?? "El campo no cumple el formato requerido";
        }
        return $this;
    }

    /**
     * Verificar si hay errores
     *
     * @return bool
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Verificar si pasó validación
     *
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Obtener errores
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Obtener primer error
     *
     * @param string|null $field
     * @return string|null
     */
    public function first(?string $field = null): ?string
    {
        if ($field) {
            return $this->errors[$field] ?? null;
        }
        return reset($this->errors) ?: null;
    }
}
