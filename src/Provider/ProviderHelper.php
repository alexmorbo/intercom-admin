<?php

namespace App\Provider;

use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;

class ProviderHelper
{
    private static array $instances = [];
    private string $deviceId;
    private string $phone;

    /**
     * Get instance for phone or create, save and return new instance
     */
    public static function getInstance(ProviderInterface $provider, string $phone): self
    {
        if (!isset(self::$instances[$phone])) {
            self::$instances[$phone] = new self($provider, $phone);
        }

        return self::$instances[$phone];
    }

    private function __construct(readonly private ProviderInterface $provider, string $phone)
    {
        $this->deviceId = $this->provider->getDeviceId();
        $this->phone = $this->provider->formatPhone($phone);
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getDeviceId(): string
    {
        return $this->deviceId;
    }

    public function store(string $key, mixed $value): void
    {
        $this->provider->getCache()->set(
            sprintf("%s|%s", $this->provider->getName(), $this->phone), $key, $value
        );
    }

    public function get(string $key)
    {
        return $this->provider->getCache()->get(
            sprintf("%s|%s", $this->provider->getName(), $this->phone), $key
        );
    }

    public function storeAuth(AuthDtoInterface $authDto): void
    {
        $this->store('authDto', $authDto);
        $this->provider->storeAuth($authDto->getAccountId(), $authDto);
    }

    /**
     * Clean cache and remove instance
     * @return void
     */
    public function clean(): void
    {
        $this->provider->getCache()->cleanProviderCache($this->provider->getName());

        unset(self::$instances[$this->phone]);
    }
}