<?php

namespace App\Provider\DomRu;

use App\Exceptions\ProviderClientException;
use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use App\Interfaces\Dto\Provider\Auth\RequestConfirmSmsInterface;
use App\Interfaces\Dto\Provider\Auth\RequestSmsInterface;
use App\Interfaces\Dto\Provider\User\UserBalanceInterface;
use App\Interfaces\Dto\Provider\User\UserInfoInterface;
use App\Interfaces\ProviderSyncClientInterface;
use App\Provider\DomRu\Dto\Auth\AuthDto;
use App\Provider\DomRu\Dto\Auth\LoginAddress;
use App\Provider\DomRu\Dto\Place\AccessControl;
use App\Provider\DomRu\Dto\Place\Camera;
use App\Provider\DomRu\Dto\Place\Event;
use App\Provider\DomRu\Dto\Subscriber\SubscriberPlace;
use App\Provider\DomRu\Dto\Subscriber\SubscriberProfile;
use App\Provider\DomRu\Dto\User\UserBalance;
use App\Provider\DomRu\Dto\User\UserInfo;
use App\Provider\ProviderInterface;
use App\Service\HttpSyncClient;
use Generator;
use JsonException;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Sync implements ProviderSyncClientInterface
{
    private ProviderInterface $provider;
    private HttpClientInterface $client;
    private SerializerInterface $serializer;
    private ?AuthDtoInterface $authData = null;

    /**
     * @param DomRu $provider
     */
    public function __construct(ProviderInterface $provider)
    {
        $this->provider = $provider;
        $this->client = (new HttpSyncClient())->getClient();
    }

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    public function setAuthData(?AuthDtoInterface $authDto = null): void
    {
        $this->authData = $authDto;
    }

    public function withAuth(AuthDtoInterface $authDto): ProviderSyncClientInterface
    {
        $client = clone $this;
        $client->setAuthData($authDto);

        return $client;
    }

    private function generateHeaders(): array
    {
        if ($this->authData === null) {
            throw new ProviderClientException("Auth data is not set", 401);
        }

        $headers = $this->provider->generateHeaders(
            $this->authData->deviceId,
            $this->authData->operatorId,
        );

        $headers['Operator'] = $this->authData->operatorId;
        $headers['Authorization'] = sprintf('%s %s', $this->authData->tokenType, $this->authData->accessToken);

        return $headers;
    }

    private function fetchApiWithAuth(string $method, string $url, ?array $data = [], string $fromKey = null): array
    {
        $headers = $this->generateHeaders();

        try {
            $response = $this->client->request($method, $url, [
                'headers' => $headers,
                'json' => $data,
            ]);
        } catch (ClientException $e) {
            throw new ProviderClientException($e->getMessage(), $e->getCode(), $e);
        } catch (TransportExceptionInterface $e) {
            throw new ProviderClientException($e->getMessage(), $e->getCode(), $e);
        }

        try {
            $content = $response->getContent();
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ProviderClientException($e->getMessage(), $e->getCode(), $e);
        }

        return $fromKey ? $data[$fromKey] : $data;
    }

    public function requestAddressForSms(string $phone): array
    {
        $deviceId = $this->provider->getDeviceId();
        $headers = $this->provider->generateHeaders($deviceId);

        $response = $this->client->request(
            'GET',
            $this->provider::BASE_API_DOMAIN . sprintf($this->provider::API_LOGIN_REQUEST, $phone),
            [
                'headers' => $headers
            ]
        );

        $addresses = [];
        try {
            foreach ($response->toArray(false) as $id => $addressData) {
                $addressData['id'] = $id;
                $addressData['deviceId'] = $deviceId;
                $addresses[$id] = $this->serializer->denormalize(
                    $addressData,
                    LoginAddress::class,
                    null,
                    [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
                );
            }
        } catch (ClientException|JsonException $e) {
            throw new ProviderClientException("No addresses by this phone number", 400);
        }

        return $addresses;
    }

    public function requestSmsAuth(RequestSmsInterface $requestSmsDto): bool
    {
        $headers = $this->provider->generateHeaders($requestSmsDto->deviceId, $requestSmsDto->operatorId);

        try {
            $smsRequestResponse = $this->client->request(
                'POST',
                $this->provider::BASE_API_DOMAIN . sprintf($this->provider::API_SMS_REQUEST, $requestSmsDto->phone),
                [
                    'headers' => $headers,
                    'json' => $requestSmsDto->toArray()
                ]
            );

            return $smsRequestResponse->getStatusCode() === 200;
        } catch (ClientException|TransportExceptionInterface $e) {
            throw new ProviderClientException(
                "Bad response from provider for sms request: " . $e->getMessage(), 400
            );
        }
    }

    public function confirmSmsAuth(RequestConfirmSmsInterface $confirmSmsDto): AuthDtoInterface
    {
        $headers = $this->provider->generateHeaders($confirmSmsDto->deviceId, $confirmSmsDto->operatorId);

        try {
            $smsConfirmResponse = $this->client->request(
                'POST',
                $this->provider::BASE_API_DOMAIN . sprintf(
                    $this->provider::API_SMS_CONFIRMATION,
                    $confirmSmsDto->phone
                ),
                [
                    'headers' => $headers,
                    'json' => $confirmSmsDto->toArray()
                ]
            );

            $authDto = $this->serializer->denormalize(
                $smsConfirmResponse->toArray(),
                AuthDto::class,
                null,
                [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
            );
            $authDto->deviceId = $confirmSmsDto->deviceId;
            $authDto->accountId = $confirmSmsDto->accountId;

            return $authDto;
        } catch (ClientException|TransportExceptionInterface $e) {
            throw new ProviderClientException(
                "Bad response from provider for sms confirm: " . $e->getMessage(), $e->getCode()
            );
        }
    }

    public function getUserBalance(): UserBalanceInterface
    {
        return $this->serializer->denormalize(
            $this->fetchApiWithAuth('GET', $this->provider::BASE_API_DOMAIN . $this->provider::API_FINANCES),
            UserBalance::class,
            null,
            [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
        );
    }

    public function getUserInfo(): UserInfoInterface
    {
        $user = new UserInfo();
        $subscriberPlaces = $this->fetchSubscriberPlaces();
        foreach ($subscriberPlaces as $subscriberPlace) {
            $subscriberPlace->accessControls = iterator_to_array(
                $this->fetchAccessControls($subscriberPlace->place->id)
            );
            $subscriberPlace->events = iterator_to_array(
                $this->fetchEvents($subscriberPlace->place->id)
            );
            $subscriberPlace->cameras = iterator_to_array(
                $this->fetchPublicCameras($subscriberPlace->place->id)
            );

            $user->subscriberPlaces[] = $subscriberPlace;
        }

        return $user;
    }

    private function fetchSubscriberPlaces(): Generator
    {
        $data = $this->fetchApiWithAuth(
            'GET',
            $this->provider::BASE_API_DOMAIN . $this->provider::API_SUBSCRIBER_PLACES,
            null,
            'data'
        );

        foreach ($data as $subscriberData) {
            $subscriber = $this->serializer->denormalize(
                $subscriberData,
                SubscriberPlace::class,
                null,
                [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
            );

            yield $subscriber;
        }
    }

    private function fetchSubscribersProfiles()
    {
        return $this->serializer->denormalize(
            $this->fetchApiWithAuth(
                'GET',
                $this->provider::BASE_API_DOMAIN . $this->provider::API_SUBSCRIBERS_PROFILES,
                null,
                'data'
            ),
            SubscriberProfile::class,
            null,
            [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
        );
    }

    private function fetchAccessControls(int $placeId): Generator
    {
        $data = $this->fetchApiWithAuth(
            'GET',
            $this->provider::BASE_API_DOMAIN . sprintf($this->provider::API_PLACE_ACCESS_CONTROLS, $placeId),
            null,
            'data'
        );

        foreach($data as $accessControlData) {
            $accessControl = $this->serializer->denormalize(
                $accessControlData,
                AccessControl::class,
                null,
                [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
            );

            yield $accessControl;
        }
    }

    private function fetchEvents(int $placeId): Generator
    {
        $data = $this->fetchApiWithAuth(
            'POST',
            $this->provider::BASE_API_DOMAIN . $this->provider::API_EVENTS,
            [
                'placeIds' => [$placeId],
                'eventTypes' => [
                    'placeArmingOn',
                    'placeArmingOff',
                    'cameraTariffChanged',
                    'techNotification',
                    'advertisementNotification',
                    'infoNotification',
                    'billingNotification',
                    'cameraChangeRecordMode',
                    'accessControlCallAccepted',
                    'accessControlCallMissed',
                    'subscriberEntered',
                    'subscriberWentOut',
                    'cameraStatusOnline',
                    'cameraStatusOffline',
                    'cameraMoving',
                    'advertisementAccessKeyOrder',
                    'accessKeyActivated',
                    'accessKeyRemoved',
                    'cameraRemoved',
                    'cameraAdded',
                    'cameraActivationError',
                    'guestAdded',
                    'guestDeleted'
                ]
            ],
            'content'
        );

        foreach ($data as $eventData) {
            $event = $this->serializer->denormalize(
                $eventData,
                Event::class,
                null,
                [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
            );

            yield $event;
        }
    }

    public function fetchCameras(): Generator
    {

    }

    public function fetchPublicCameras(int $placeId): Generator
    {
        $data = $this->fetchApiWithAuth(
            'GET',
            $this->provider::BASE_API_DOMAIN . sprintf($this->provider::API_PLACE_PUBLIC_CAMERAS, $placeId),
            null,
            'data'
        );

        foreach ($data as $cameraData) {
            $camera = $this->serializer->denormalize(
                $cameraData,
                Camera::class,
                null,
                [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
            );

            yield $camera;
        }
    }

    private function fetchSipDevices()
    {
        return $this->serializer->denormalize(
            $this->fetchApiWithAuth(
                'GET',
                $this->provider::BASE_API_DOMAIN . $this->provider::API_SUBSCRIBERS_PROFILES,
                null,
                'data'
            ),
            SubscriberProfile::class,
            null,
            [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
        );
    }

    public function getRelays(): Generator
    {
        foreach ($this->fet as $relayData) {
            $relay = $this->serializer->denormalize(
                $relayData,
                Relay::class,
                null,
                [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
            );

            yield $relay;
        }
    }
}