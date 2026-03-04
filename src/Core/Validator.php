<?php

declare(strict_types=1);

namespace Core;

/**
 * Input Validation
 */
class Validator
{
    private array $errors = [];
    private array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $message = null): self
    {
        $value = $this->getValue($field);

        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, $message ?? "The {$field} field is required.");
        }

        return $this;
    }

    public function email(string $field, string $message = null): self
    {
        $value = $this->getValue($field);

        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $message ?? "The {$field} must be a valid email address.");
        }

        return $this;
    }

    public function minLength(string $field, int $min, string $message = null): self
    {
        $value = $this->getValue($field);

        if ($value !== null && strlen($value) < $min) {
            $this->addError($field, $message ?? "The {$field} must be at least {$min} characters.");
        }

        return $this;
    }

    public function maxLength(string $field, int $max, string $message = null): self
    {
        $value = $this->getValue($field);

        if ($value !== null && strlen($value) > $max) {
            $this->addError($field, $message ?? "The {$field} must not exceed {$max} characters.");
        }

        return $this;
    }

    public function alphanumeric(string $field, string $message = null): self
    {
        $value = $this->getValue($field);

        if ($value !== null && $value !== '' && !ctype_alnum($value)) {
            $this->addError($field, $message ?? "The {$field} must contain only letters and numbers.");
        }

        return $this;
    }

    public function username(string $field, string $message = null): self
    {
        $value = $this->getValue($field);

        if ($value !== null && $value !== '' && !preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
            $this->addError($field, $message ?? "The {$field} must contain only letters, numbers, and underscores.");
        }

        return $this;
    }

    public function password(string $field, string $message = null): self
    {
        $value = $this->getValue($field);

        if ($value !== null && $value !== '') {
            // At least one uppercase, one lowercase, one digit
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $value)) {
                $this->addError($field, $message ?? "The {$field} must contain at least one uppercase letter, one lowercase letter, and one digit.");
            }
        }

        return $this;
    }

    public function confirmed(string $field, string $confirmField, string $message = null): self
    {
        $value = $this->getValue($field);
        $confirmValue = $this->getValue($confirmField);

        if ($value !== $confirmValue) {
            $this->addError($field, $message ?? "The {$field} confirmation does not match.");
        }

        return $this;
    }

    public function unique(string $field, string $table, string $column, ?int $exceptId = null, string $message = null): self
    {
        $value = $this->getValue($field);

        if ($value === null || $value === '') {
            return $this;
        }

        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
        $params = [$value];

        if ($exceptId !== null) {
            $sql .= " AND id != ?";
            $params[] = $exceptId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->fetchColumn() > 0) {
            $this->addError($field, $message ?? "The {$field} has already been taken.");
        }

        return $this;
    }

    public function in(string $field, array $allowed, string $message = null): self
    {
        $value = $this->getValue($field);

        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $this->addError($field, $message ?? "The {$field} must be one of: " . implode(', ', $allowed));
        }

        return $this;
    }

    public function numeric(string $field, string $message = null): self
    {
        $value = $this->getValue($field);

        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, $message ?? "The {$field} must be a number.");
        }

        return $this;
    }

    public function integer(string $field, string $message = null): self
    {
        $value = $this->getValue($field);

        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, $message ?? "The {$field} must be an integer.");
        }

        return $this;
    }

    public function min(string $field, int|float $min, string $message = null): self
    {
        $value = $this->getValue($field);

        if ($value !== null && is_numeric($value) && $value < $min) {
            $this->addError($field, $message ?? "The {$field} must be at least {$min}.");
        }

        return $this;
    }

    public function max(string $field, int|float $max, string $message = null): self
    {
        $value = $this->getValue($field);

        if ($value !== null && is_numeric($value) && $value > $max) {
            $this->addError($field, $message ?? "The {$field} must not be greater than {$max}.");
        }

        return $this;
    }

    private function getValue(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(string $field = null): ?string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }

        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }

        return null;
    }

    public function allErrors(): array
    {
        $all = [];
        foreach ($this->errors as $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $all[] = $error;
            }
        }
        return $all;
    }

    public static function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeArray(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = self::sanitize($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
}
