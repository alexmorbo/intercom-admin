<?php

namespace App\Provider\DomRu;

use App\Enum\Provider\AuthScheme;
use App\Exceptions\ProviderClientException;
use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use App\Interfaces\ProviderSyncClientInterface;
use App\Provider\AbstractProvider;
use App\Provider\DomRu\Dto\Auth\AuthDto;
use App\Provider\ProviderInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\SerializerInterface;

class DomRu extends AbstractProvider implements ProviderInterface
{
    protected string $providerName = 'domru';

    public const BASE_API_DOMAIN = 'https://api-mh.ertelecom.ru';

    public const API_LOGIN_REQUEST = '/auth/v2/login/%s';

    public const API_SMS_REQUEST = '/auth/v2/confirmation/%s';

    public const API_SMS_CONFIRMATION = '/auth/v2/auth/%s/confirmation';

    public const API_FINANCES = '/rest/v1/subscribers/profiles/finances?placeId=';

    public const API_SUBSCRIBER_PLACES = '/rest/v2/subscriberplaces';

    public const API_SUBSCRIBERS_PROFILES = '/rest/v1/subscribers/profiles';

    public const API_PLACE_ACCESS_CONTROLS = '/rest/v1/places/%d/accesscontrols';

    public const API_PLACE_CAMERAS = '/rest/v1/places/%d/cameras';

    public const API_PLACE_PUBLIC_CAMERAS = '/rest/v2/places/%d/public/cameras';

    public const API_EVENTS = '/rest/v1/events/search?page=0&sort=occurredAt,DESC';

    public function __construct(protected SerializerInterface $serializer)
    {
        $this->authScheme = AuthScheme::AddressFirst;
    }

    public function getDeviceId(bool $forceNewId = false): string
    {
        static $uuid = null;

        if ($uuid === null || $forceNewId) {
            $uuid = mb_strtoupper(Uuid::uuid4()->toString());
        }

        return $uuid;
    }

    public function generateHeaders(string $deviceId = null, int $operatorId = null): array
    {
        $userAgentParts = [
            'HA-Intercom',
            'PHP ' . phpversion(),
            'ntk',
            '0.0.1 (build 0.1)',
            '_',
            $operatorId,
            $deviceId
        ];

        return [
            'Host' => parse_url(self::BASE_API_DOMAIN, PHP_URL_HOST),
            'Content-Type' => 'application/json; charset=UTF-8',
            'Accept' => '*/*',
            'User-Agent' => implode(' | ', $userAgentParts),
            'Accept-Language' => 'ru',
        ];
    }

    public function formatPhone(string $phone): string
    {
        if (str_starts_with($phone, '7') && strlen($phone) === 11) {
            return $phone;
        } elseif (str_starts_with($phone, '8') && strlen($phone) === 11) {
            return '7' . substr($phone, 1);
        } elseif (str_starts_with($phone, '9') && strlen($phone) === 10) {
            return '7' . $phone;
        } else {
            throw new ProviderClientException('Invalid phone number');
        }
    }

    public function getAuthDtoClass(): string
    {
        return AuthDto::class;
    }

    public function getSyncClient(AuthDtoInterface $authDto = null): ProviderSyncClientInterface
    {
        if ($this->syncClient === null) {
            $this->syncClient = new Sync($this);
            $this->syncClient->setSerializer($this->getSerializer());
        }

        if ($authDto !== null && !isset($this->syncClientsForAuth[$authDto->getAccountId()])) {
            $this->syncClientsForAuth[$authDto->getAccountId()] = $this->syncClient->withAuth($authDto);

            return $this->syncClientsForAuth[$authDto->getAccountId()];
        }

        return $this->syncClient;
    }
}