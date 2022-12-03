<?php

namespace App\Provider;

use App\Enum\Provider\AuthScheme;
use App\Interfaces\ProviderSyncClientInterface;

class AbstractProvider
{
    protected string $providerName;
    protected AuthScheme $authScheme;
    protected ?ProviderSyncClientInterface $syncClient = null;
    protected array $syncClientsForAuth = [];

    public function getName(): string
    {
        return $this->providerName;
    }

    public function getAuthScheme(): AuthScheme
    {
        return $this->authScheme;
    }

    public function formatPhone(string $phone): string
    {
        return $phone;
    }

    public function getAuthDtoClass(): string
    {
        return '';
    }
}