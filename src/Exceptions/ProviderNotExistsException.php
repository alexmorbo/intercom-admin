<?php

namespace App\Exceptions;

class ProviderNotExistsException extends \Exception
{
    public function __construct(string $providerName)
    {
        parent::__construct(sprintf('Provider %s not exists', $providerName));
    }
}