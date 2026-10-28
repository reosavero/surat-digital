<?php

/**
 * Validator Helper - Surat Digital
 */

class Validator
{
    private array $data = [];
    private array $rules = [];
    private array $errors = [];
    private array $validated = [];

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function setRules(array $rules): self
    {
        $this->rules = $rules;
        return $this;
    }

    public function validate(): bool
    {
        $this->errors = [];
        $this->validated = [];

        foreach ($this->rules as $field => $ruleset) {
            $value = $this->data[$field] ?? null;
            $ruleList = is_string($ruleset) ? explode('|', $ruleset) : $ruleset;

            foreach ($ruleList as $rule) {
                if ($rule === 'required' && (empty($value) && $value !== '0')) {
                    $this->errors[$field] = ucfirst($field) . ' wajib diisi.';
                    break;
                }
                if ($rule === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = 'Format email tidak valid.';
                    break;
                }
                if (str_starts_with($rule, 'min:') && !empty($value)) {
                    $min = (int) substr($rule, 4);
                    if (strlen($value) < $min) {
                        $this->errors[$field] = ucfirst($field) . " minimal {$min} karakter.";
                        break;
                    }
                }
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }

        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getValidatedData(): array
    {
        return $this->validated;
    }
}
