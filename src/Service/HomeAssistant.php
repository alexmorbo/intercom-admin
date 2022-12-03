<?php

namespace App\Service;

use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use App\Kernel;
use App\Provider\ProviderInterface;
use App\Provider\ProviderRegistry;
use Symfony\Component\Serializer\SerializerInterface;

class HomeAssistant
{
    public const API_NETWORK_INFO = 'http://supervisor/network/info';

    private string $path;

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ProviderRegistry $providerRegistry,
        Kernel $kernel
    ) {
        $this->path = $kernel->getProjectDir() . '/data/accounts.json';
    }

    public function saveProviderAuthData(ProviderInterface $provider, AuthDtoInterface $authDto): bool
    {
        $accounts = $this->getAccounts(true);
        $accounts[$provider->getName() . '|' . $authDto->getAccountId()] = $authDto->toArray();

        return $this->saveAccounts($accounts) ?? true;
    }

    public function getAccount(ProviderInterface $provider, $accountId): ?AuthDtoInterface
    {
        $accounts = $this->getAccounts();

        return $accounts[$provider->getName()][$accountId] ?? null;
    }

    public function getAccounts($raw = false): array
    {
        $accounts = [];
        if (!file_exists($this->path)) {
            return $accounts;
        }

        if ($raw) {
            return json_decode(file_get_contents($this->path), true);
        }

        $accountsData = json_decode(file_get_contents($this->path), true);
        foreach ($accountsData as $accountKey => $accountData) {
            list($providerName, $accountId) = explode('|', $accountKey);
            $provider = $this->providerRegistry->getProvider($providerName);
            $accounts[$provider->getName()][$accountId] = $this->serializer->denormalize(
                $accountData,
                $provider->getAuthDtoClass()
            );
        }

        return $accounts;
    }

    private function saveAccounts(array $accounts): false|int
    {
        return file_put_contents($this->path, json_encode($accounts, JSON_UNESCAPED_UNICODE));
    }
}