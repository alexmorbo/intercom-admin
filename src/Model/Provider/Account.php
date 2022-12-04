<?php

namespace App\Model\Provider;

use App\Dto\Provider\AccountDto;
use App\Exceptions\ProviderException;
use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use App\Interfaces\ProviderSyncClientInterface;
use App\Provider\DomRu\DomRu;
use App\Provider\DomRu\Dto\Subscriber\SubscriberPlace;
use App\Provider\DomRu\Dto\User\UserBalance;
use App\Provider\DomRu\Dto\User\UserInfo;
use App\Provider\ProviderInterface;
use Symfony\Component\HttpClient\Exception\ServerException;

class Account
{
    private ProviderSyncClientInterface $client;

    public function __construct(
        private AuthDtoInterface $authDto,
        private ProviderInterface $provider,
    )
    {
        $this->client = $this->provider->getSyncClient($this->authDto);
    }

    public function getMainData()
    {
        $balanceInterfaceData = $this->client->getUserBalance();
        $userInfoInterfaceData = $this->client->getUserInfo();

        if ($this->provider instanceof DomRu) {
            $accountData = [
                'id' => $this->authDto->getAccountId(),
                'authDto' => $this->authDto,
                'balance' => null,
                'addresses' => [],
            ];
        } else {
            throw new ProviderException('Wrong provider', 400);
        }

        if ($balanceInterfaceData instanceof UserBalance) {
            $accountData['balance'] = [
                'balance' => $balanceInterfaceData->balance,
                'isBlocked' => $balanceInterfaceData->blocked,
                'paymentUrl' => $balanceInterfaceData->paymentLink,
            ];
        }

        if ($userInfoInterfaceData instanceof UserInfo) {
            /** @var SubscriberPlace $subscriberPlace */
            foreach ($userInfoInterfaceData->subscriberPlaces as $subscriberPlace) {
                $addressData = [
                    'id' => $subscriberPlace->place->id,
                    'address' => $subscriberPlace->place->address->visibleAddress,
                    'location' => [
                        'lat' => $subscriberPlace->place->location->latitude,
                        'lon' => $subscriberPlace->place->location->longitude,
                    ],
                ];
                foreach ($subscriberPlace->cameras as $camera) {
                    $addressData['cameras'][] = [
                        'id' => $camera->id,
                        'name' => $camera->name,
                    ];
                }
                foreach ($subscriberPlace->accessControls as $accessControl) {
                    $addressData['relays'][] = [
                        'id' => $accessControl->id,
                        'cameraId' => $accessControl->externalCameraId,
                    ];
                }

                $accountData['addresses'][] = $addressData;
            }
        }

        return $this->provider->getSerializer()->denormalize($accountData, AccountDto::class);
    }

    public function getCameraSnapshot(int $cameraId): string
    {
        return $this->client->getCameraSnapshot($cameraId);
    }

    public function getCameraVideoStream(int $cameraId): string
    {
        $cameraVideoStream = $this->client->getCameraVideoStream($cameraId);
        if ($cameraVideoStream->errorCode === null) {
            return $cameraVideoStream->url;
        } else {
            throw new ProviderException($cameraVideoStream->errorMessage, $cameraVideoStream->errorCode);
        }
    }

    public function openRelay(int $placeId, int $relayId)
    {
        try {
            $this->client->openRelay($placeId, $relayId);

            return true;
        } catch (ServerException $e) {
            throw new ProviderException($e->getMessage(), $e->getCode());
        }
    }
}