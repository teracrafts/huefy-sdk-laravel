<?php

namespace TeraCrafts\HuefyLaravel\Exceptions;

class ValidationException extends HuefyException
{
    private array $errors;

    public function __construct(string $message, int $code = 422, array $errors = [])
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}