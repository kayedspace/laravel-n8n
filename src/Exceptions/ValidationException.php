<?php

namespace KayedSpace\N8n\Exceptions;

class ValidationException extends N8nException
{
    protected array $errors = [];

    public function setErrors(array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
