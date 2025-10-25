<?php

declare(strict_types=1);

namespace Velolia\Validation;

class Validator
{
    protected array $data = [];
    protected array $rules = [];
    protected array $messages = [];
    protected array $attributes = [];
    protected array $errors = [];

    public function make(array $data, array $rules, array $messages = [], array $attributes = []): static
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
        $this->attributes = $attributes;
        $this->errors = [];

        $this->validate();

        return $this;
    }

    protected function validate(): void
    {
        foreach ($this->rules as $field => $rules) {
            $value = $this->data[$field] ?? null;

            foreach ($this->normalizeRules($rules) as [$ruleName, $parameters, $ruleObject]) {
                if ($ruleObject instanceof Rule) {
                    if (! $ruleObject->validate($field, $value, $parameters)) {
                        $message = $this->messages[$field.'.'.$ruleName]
                            ?? $ruleObject->message($field, $parameters);

                        $this->addError($field, $message);
                    }
                }
            }
        }
    }

    protected function normalizeRules(string|array $rules): array
    {
        $ruleList = is_string($rules) ? explode('|', $rules) : $rules;
        $result = [];

        foreach ($ruleList as $rule) {
            $parameters = [];
            $ruleName = $rule;

            if (is_string($rule) && str_contains($rule, ':')) {
                [$ruleName, $paramStr] = explode(':', $rule, 2);
                $parameters = explode(',', $paramStr);
            }

            $class = __NAMESPACE__ . '\\Rules\\' . ucfirst($ruleName) . 'Rule';
            $ruleObject = class_exists($class) ? new $class : null;

            if ($ruleObject) {
                $result[] = [$ruleName, $parameters, $ruleObject];
            }
        }

        return $result;
    }

    protected function addError(string $field, string $message): void
    {
        $attribute = $this->attributes[$field] ?? $field;
        $this->errors[$field][] = str_replace(':attribute', $attribute, $message);
    }

    public function fails(): bool
    {
        return ! empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}