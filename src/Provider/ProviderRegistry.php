<?php

namespace App\Provider;

class ProviderRegistry
{
    private array $providers;

    public function __construct(iterable $providers = [])
    {
        $this->providers = iterator_to_array($providers);
    }

    public function getProviders(): array
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