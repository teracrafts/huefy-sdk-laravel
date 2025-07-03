<?php

namespace TeraCrafts\HuefyLaravel\Exceptions;

class TemplateNotFoundException extends HuefyException
{
    private string $templateKey;

    public function __construct(string $message, int $code = 404, string $templateKey = '')
    {
        parent::__construct($message, $code);
        $this->templateKey = $templateKey;
    }

    public function getTemplateKey(): string
    {
        return $this->templateKey;
    }
}