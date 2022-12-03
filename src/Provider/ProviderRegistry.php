<?php

namespace App\Provider;

class ProviderRegistry
{
    public function __construct(private iterable $providers)
    {

    }

    public function getProviders(): iterable
    {
        return $this->providers;
    }

    public function getProvider(string $providerName): ?ProviderInterface
    {
        /** @var ProviderInterface $provider */
        foreach ($this->providers as $provider) {
            if ($provider->getName() === $providerName) {
                return $provider;
            }
        }

        return null;
    }
}