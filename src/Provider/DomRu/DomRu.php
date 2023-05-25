<?php

namespace App\Provider\DomRu;

use App\Enum\Provider\AuthScheme;
use App\Exceptions\ProviderClientException;
use App\Hydrator\HydratorInterface;
use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use App\Interfaces\ProviderASyncClientInterface;
use App\Interfaces\ProviderSyncClientInterface;
use App\Provider\AbstractProvider;
use App\Provider\DomRu\Controller\AuthController;
use App\Provider\DomRu\Dto\Auth\AuthDto;
use App\Provider\ProviderCache;
use App\Provider\ProviderInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Serializer\SerializerInterface;

class DomRu extends AbstractProvider implements ProviderInterface
{
    protected string $providerName = 'DomRu';

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

    public const API_CAMERA_SNAPSHOT = '/rest/v1/forpost/cameras/%d/snapshots';

    public const API_CAMERA_VIDEO_STREAM = '/rest/v1/forpost/cameras/%d/video';

    public const API_RELAY_OPEN = '/rest/v1/places/%d/accesscontrols/%d/actions';

    public function __construct(
        protected SerializerInterface $serializer,
        protected HydratorInterface $hydrator,
        protected ProviderCache $cache,
        protected MessageBusInterface $bus,
    ) {
        $this->authScheme = AuthScheme::AddressFirst;
    }

    /**
     * @return array<Route>
     */
    public function getRoutes(): array
    {
        return [
            'domru_login' => new Route('/api/domru/login', [
                '_controller' => AuthController::class,
                '_method' => 'login',
            ]),
            'domru_address' => new Route('/api/domru/address', [
                '_controller' => AuthController::class,
                '_method' => 'address',
            ]),
            'domru_sms' => new Route('/api/domru/sms', [
                '_controller' => AuthController::class,
                '_method' => 'sms',
            ]),
        ];
    }

    public function getDeviceId(bool $forceNewId = false): string
    {
        static $uuid = null;

        if ($uuid === null || $forceNewId) {
            $uuid = mb_strtoupper(Uuid::uuid4()->toString());
        }

        return $uuid;
    }

    public function generateHeaders(
        string $deviceId = null,
        int $operatorId = null,
        int $accountId = null,
        int $placeId = null
    ): array {
        $userAgentParts = [
            'HA-Intercom',
            'PHP ' . phpversion(),
            'ntk',
            '0.0.1 (build 0.1)',
            $accountId ?? '_',
            $operatorId,
            $deviceId,
            $placeId
        ];

        return [
            'Host' => parse_url(self::BASE_API_DOMAIN, PHP_URL_HOST),
            'Content-Type' => 'application/json; charset=UTF-8',
            'Accept' => '*/*',
            'User-Agent' => implode(' | ', $userAgentParts),
            'Accept-Language' => 'ru',
        ];
    }

    /**
     * Get phone number in any format and return it in XXXXXXXXXX, strip +7, 8, 7, (, ), -, +, space
     * @param string $phone
     * @return string
     * @throws ProviderClientException
     */
    public function formatPhone(mixed $phone): string
    {
        if (is_string($phone)) {
            $phone = preg_replace('/[^0-9]/', '', $phone);
        }

        if (is_int($phone)) {
            $phone = (string) $phone;
        }

        if (strlen($phone) === 11 && str_starts_with($phone, '89')) {
            $phone = substr($phone, 1);
        }

        if (strlen($phone) === 11 && str_starts_with($phone, '79')) {
            $phone = substr($phone, 1);
        }

        if (strlen($phone) === 10 && str_starts_with($phone, '9')) {
            return (int) $phone;
        }

        throw new ProviderClientException('Invalid phone number format');
    }

    public function getAuthDtoClass(): string
    {
        return AuthDto::class;
    }

    public function getASyncClient(AuthDtoInterface $authDto = null): ProviderASyncClientInterface
    {
        if ($this->asyncClient === null) {
            $this->asyncClient = new ASync($this);
            $this->asyncClient->setSerializer($this->getSerializer());
        }

        if ($authDto !== null && !isset($this->asyncClientsForAuth[$authDto->getAccountId()])) {
            $this->asyncClientsForAuth[$authDto->getAccountId()] = $this->asyncClient->withAuth($authDto);

            return $this->asyncClientsForAuth[$authDto->getAccountId()];
        }

        return $this->asyncClient;
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