<?php

namespace App\Provider;

use App\Enum\Provider\AuthScheme;
use App\Hydrator\HydratorInterface;
use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use App\Interfaces\ProviderASyncClientInterface;
use App\Interfaces\ProviderSyncClientInterface;
use App\Message\NewProviderAuthMessage;
use App\Message\RemoveAccountMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;

class AbstractProvider
{
    protected string $providerName;
    protected SerializerInterface $serializer;
    protected HydratorInterface $hydrator;
    protected MessageBusInterface $bus;
    protected ProviderCache $cache;
    protected AuthScheme $authScheme;
    protected ?ProviderSyncClientInterface $syncClient = null;
    protected ?ProviderASyncClientInterface $asyncClient = null;
    protected array $asyncClientsForAuth = [];
    protected array $syncClientsForAuth = [];

    public function getName(): string
    {
        return $this->providerName;
    }

    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    public function getHydrator(): HydratorInterface
    {
        return $this->hydrator;
    }

    public function getMessageBus(): MessageBusInterface
    {
        return $this->bus;
    }

    public function getAuthScheme(): AuthScheme
    {
        return $this->authScheme;
    }

    public function formatPhone(string $phone): string
    {
        return $phone;
    }

    public function getCache(): ProviderCache
    {
        return $this->cache;
    }

    public function getAuthDtoClass(): string
    {
        return '';
    }

    public function storeAuth(string $accountId, AuthDtoInterface $authDto): void
    {
        $this->bus->dispatch(new NewProviderAuthMessage($authDto));
    }

    public function removeAuth(AuthDtoInterface $authDto): void
    {
        $this->bus->dispatch(new RemoveAccountMessage($authDto));
    }
}