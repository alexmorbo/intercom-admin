<?php

namespace App\Provider;

use App\Hydrator\HydratorInterface;
use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use RuntimeException;

class ProviderRegistry
{
    const DATABASE_FILE = __DIR__ . '/../../data/accounts.sf';

    private array $providers;

    private array $accounts = [];

    public function __construct(
        iterable $providers = [],
        private readonly HydratorInterface $hydrator,
        private readonly ProviderCache $cache,
    ) {
        $providers = iterator_to_array($providers);
        /** @var ProviderInterface $provider */
        foreach ($providers as $provider) {
            $this->providers[$provider->getName()] = $provider;
        }
    }

    public function getHydrator(): HydratorInterface
    {
        return $this->hydrator;
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

    public function getCache(): ProviderCache
    {
        return $this->cache;
    }

    public function getProviderFromClass(mixed $providerClass): string
    {
        /** Get provider name from provider class */
        $providerClassData = explode('\\', get_class($providerClass));
        return $providerClassData[2];
    }

    public function loadAccounts(): void
    {
        if (!file_exists(self::DATABASE_FILE)) {
            return;
        }

        $db = file_get_contents(self::DATABASE_FILE);

        foreach (explode("\n", $db) as $row) {
            if (empty($row)) {
                continue;
            }
            [$providerClass, $json] = explode('|', $row);
            /** @var AuthDtoInterface $authDto */
            $authDto = new $providerClass();

            $authDto->populate(json_decode($json, true));
            $this->accounts[$this->getProviderFromClass($authDto)][$authDto->getAccountId()] = $authDto;
        }
    }

    /**
     * @return array
     */
    public function getAccounts(): array
    {
        return $this->accounts;
    }

    /**
     * @param string $providerClass
     * @return array|AuthDtoInterface[]
     */
    public function getAccountsByProvider(string $providerClass): array
    {
        return $this->accounts[$providerClass] ?? [];
    }

    public function getProviderByAuth(AuthDtoInterface $account)
    {
        return $this->providers[$this->getProviderFromClass($account)] ?? null;
    }

    public function getAccount(string $provider, string $accountId): ?AuthDtoInterface
    {
        return $this->accounts[$provider][$accountId] ?? null;
    }

    public function pushAccount(AuthDtoInterface $authDto): void
    {
        $storedBytes = file_put_contents(
            ProviderRegistry::DATABASE_FILE,
            get_class($authDto).'|'.json_encode((array)$authDto) . "\n",
            FILE_APPEND
        );

        if ($storedBytes === false) {
            throw new RuntimeException('Failed to store account');
        }

        $this->accounts[$this->getProviderFromClass($authDto)][$authDto->getAccountId()] = $authDto;
    }

    public function removeAccount(AuthDtoInterface $authDto): void
    {
        $db = file_get_contents(self::DATABASE_FILE);
        $db = str_replace(
            get_class($authDto).'|'.json_encode((array)$authDto) . "\n",
            '',
            $db
        );
        $bytes = file_put_contents(self::DATABASE_FILE, $db);
        if ($bytes === false) {
            throw new RuntimeException('Failed to delete account');
        }

        unset($this->accounts[$this->getProviderFromClass($authDto)][$authDto->getAccountId()]);

        if (count($this->accounts[$this->getProviderFromClass($authDto)]) === 0) {
            unset($this->accounts[$this->getProviderFromClass($authDto)]);
        }
    }
}