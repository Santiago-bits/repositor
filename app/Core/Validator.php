<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Validación del lado del servidor.
 * Reglas: required, nullable, boolean, email, integer, numeric, min:N, max:N, between:A,B, in:a,b,c
 * (min/max miden caracteres en texto y valor en campos integer/numeric).
 */
final class Validator
{
    private array $errors = [];
    private array $clean = [];

    public function __construct(array $data, array $rules, array $labels = [])
    {
        foreach ($rules as $field => $ruleString) {
            $this->check($field, $data[$field] ?? null, explode('|', $ruleString), $labels[$field] ?? $field);
        }
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->clean;
    }

    private function check(string $field, mixed $value, array $rules, string $label): void
    {
        if (in_array('boolean', $rules, true)) {
            $this->clean[$field] = in_array($value, [1, '1', true, 'on', 'true'], true);
            return;
        }

        if (is_string($value)) {
            $value = trim($value);
        }
        if ($value === null || $value === '') {
            if (in_array('required', $rules, true)) {
                $this->errors[$field] = "Completá el campo {$label}.";
            }
            $this->clean[$field] = null;
            return;
        }
        if (!is_scalar($value)) {
            $this->errors[$field] = "El campo {$label} no es válido.";
            return;
        }
        if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
            $this->errors[$field] = "El campo {$label} tiene caracteres no válidos.";
            return;
        }

        $isNumber = in_array('integer', $rules, true) || in_array('numeric', $rules, true);

        foreach ($rules as $rule) {
            [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
            $error = match ($name) {
                'email'   => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Ingresá un email válido.',
                'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : "{$label} tiene que ser un número entero.",
                'numeric' => is_numeric($value) ? null : "{$label} tiene que ser un número.",
                'min'     => $isNumber
                    ? ($value >= (float) $arg ? null : "{$label} tiene que ser como mínimo {$arg}.")
                    : (mb_strlen((string) $value) >= (int) $arg ? null : "{$label} tiene que tener al menos {$arg} caracteres."),
                'max'     => $isNumber
                    ? ($value <= (float) $arg ? null : "{$label} no puede superar {$arg}.")
                    : (mb_strlen((string) $value) <= (int) $arg ? null : "{$label} no puede superar los {$arg} caracteres."),
                'between' => $this->between($value, (string) $arg, $label),
                'in'      => in_array((string) $value, explode(',', (string) $arg), true) ? null : "Elegí una opción válida para {$label}.",
                default   => null,
            };
            if ($error !== null) {
                $this->errors[$field] = $error;
                return;
            }
        }

        $this->clean[$field] = match (true) {
            in_array('integer', $rules, true) => (int) $value,
            in_array('numeric', $rules, true) => (float) $value,
            default                           => $value,
        };
    }

    private function between(mixed $value, string $arg, string $label): ?string
    {
        [$min, $max] = array_map('floatval', explode(',', $arg));
        return is_numeric($value) && $value >= $min && $value <= $max
            ? null
            : "{$label} tiene que estar entre {$min} y {$max}.";
    }
}
