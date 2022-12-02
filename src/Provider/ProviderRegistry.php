<?php

namespace App\Provider;

use Symfony\Component\Serializer\SerializerInterface;

class ProviderRegistry
{
    public function __construct(private iterable $providers, private SerializerInterface $serializer)
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
                $provider->setSerializer($this->serializer);
                return $provider;
            }
        }

        return null;
    }
}