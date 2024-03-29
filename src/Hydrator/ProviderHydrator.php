<?php

namespace App\Hydrator;

use ReflectionException;
use SergiX44\Hydrator\Hydrator;

class ProviderHydrator implements HydratorInterface
{
    private Hydrator $mapper;

    public function __construct()
    {
        $this->mapper = new Hydrator();
    }

    /**
     * @inheritDoc
     */
    public function hydrate(object|array $data, object|string $instance): object|string
    {
        return $this->mapper->hydrate($instance, $data);
    }

    /**
     * @inheritDoc
     */
    public function hydrateArray(array $data, object|string $instance): array
    {
        return array_map(
            fn ($obj) => $this->mapper->hydrate(is_string($instance) ? $instance : clone $instance, $obj),
            $data
        );
    }

    /**
     * @throws ReflectionException
     */
    public function getConcreteFor(string $class): ?object
    {
        return $this->mapper->getConcreteResolverFor($class);
    }
}
