<?php

namespace App\Service;

use App\Dto\HomeAssistant\Supervisor\Network\NetworkDto;
use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use App\Provider\ProviderInterface;
use App\Provider\ProviderRegistry;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HomeAssistant
{
    public const SUPERVISOR_BASE_URL = 'http://supervisor';

    public const SUPERVISOR_NETWORK_INFO = '/network/info';

    private string $path;

    private ?string $supervisorToken;

    private ?string $hassioToken;

    private HttpClientInterface $client;

    public function __construct(
        private ProviderRegistry $providerRegistry,
        private SerializerInterface $serializer,
        RequestStack $requestStack,
        string $projectDir,
    ) {
        $request = $requestStack->getCurrentRequest();
        $this->supervisorToken = $request?->server->get('SUPERVISOR_TOKEN');
        $this->hassioToken = $request?->server->get('HASSIO_TOKEN');
        $this->path = sprintf('%s/data/accounts.json', $projectDir);
        $this->client = (new HttpSyncClient())->getClient();
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
            $accounts[$provider->getName()][$accountId] = $provider->getSerializer()->denormalize(
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

    public function getSupervisorNetworkInfo(): ?NetworkDto
    {
        try {
            $response = $this->client->request(
                'GET',
                self::SUPERVISOR_BASE_URL . self::SUPERVISOR_NETWORK_INFO,
                [
                    'headers' => [
                        'Authorization' => sprintf('Bearer %s', $this->supervisorToken),
                    ],
                ]
            );

            $data = $response->toArray();
            if ($data['result'] !== 'ok') {
                throw new \RuntimeException('Can\'t get supervisor network info');
            }

            return $this->serializer->denormalize(
                $data['data'],
                NetworkDto::class
            );
        } catch (TransportException $e) {
            return null;
        }
    }
}