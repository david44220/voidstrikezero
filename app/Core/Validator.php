<?php

declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self
    {
        $v = new self($data, $rules);
        $v->validate();
        return $v;
    }

    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $rulesList = is_array($ruleString) ? $ruleString : explode('|', (string) $ruleString);
            $val = $this->data[$field] ?? null;

            foreach ($rulesList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$ruleName, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                } else {
                    $ruleName = $rule;
                }

                $this->applyRule($field, $val, $ruleName, $params);
            }
        }

        return empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }

        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }

    private function applyRule(string $field, mixed $val, string $rule, array $params): void
    {
        $label = ucfirst(str_replace('_', ' ', $field));

        switch ($rule) {
            case 'required':
                if ($val === null || (is_string($val) && trim($val) === '') || (is_array($val) && empty($val))) {
                    $this->addError($field, __("validation.required", ['field' => $label]));
                }
                break;

            case 'email':
                if (!empty($val) && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, __("validation.email", ['field' => $label]));
                }
                break;

            case 'min':
                $min = (int) ($params[0] ?? 0);
                if (is_numeric($val) && (float) $val < $min) {
                    $this->addError($field, __("validation.min_numeric", ['field' => $label, 'min' => (string) $min]));
                } elseif (is_string($val) && mb_strlen($val) < $min) {
                    $this->addError($field, __("validation.min_string", ['field' => $label, 'min' => (string) $min]));
                }
                break;

            case 'max':
                $max = (int) ($params[0] ?? 0);
                if (is_numeric($val) && (float) $val > $max) {
                    $this->addError($field, __("validation.max_numeric", ['field' => $label, 'max' => (string) $max]));
                } elseif (is_string($val) && mb_strlen($val) > $max) {
                    $this->addError($field, __("validation.max_string", ['field' => $label, 'max' => (string) $max]));
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                $confirmVal = $this->data[$confirmField] ?? null;
                if ($val !== $confirmVal) {
                    $this->addError($field, __("validation.confirmed", ['field' => $label]));
                }
                break;

            case 'in':
                if (!empty($val) && !in_array((string) $val, $params, true)) {
                    $this->addError($field, __("validation.in", ['field' => $label]));
                }
                break;

            case 'alpha_dash':
                if (!empty($val) && !preg_match('/^[a-zA-Z0-9_-]+$/', (string) $val)) {
                    $this->addError($field, __("validation.alpha_dash", ['field' => $label]));
                }
                break;

            case 'numeric':
                if (!empty($val) && !is_numeric($val)) {
                    $this->addError($field, __("validation.numeric", ['field' => $label]));
                }
                break;

            case 'unique':
                $table = $params[0] ?? '';
                $column = $params[1] ?? $field;
                $ignoreId = $params[2] ?? null;
                if (!empty($val) && !empty($table)) {
                    $sql = "SELECT 1 FROM {$table} WHERE {$column} = :val";
                    $bindings = [':val' => $val];
                    if ($ignoreId !== null) {
                        $sql .= " AND id != :ignore_id";
                        $bindings[':ignore_id'] = $ignoreId;
                    }
                    $exists = Database::selectOne($sql, $bindings);
                    if ($exists) {
                        $this->addError($field, __("validation.unique", ['field' => $label]));
                    }
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
