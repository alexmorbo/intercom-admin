<?php

namespace App\Provider\DomRu\Traits;

use App\Exceptions\ProviderClientException;
use App\Provider\DomRu\Dto\Auth\AuthDto;
use App\Provider\DomRu\Dto\Auth\LoginAddress;
use App\Provider\ProviderHelper;
use Psr\Http\Message\ResponseInterface;
use React\Http\Message\ResponseException;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Response;

trait AuthTrait
{
    public function requestAddressForSms(string $phone, mixed $acceptableResponseCode): PromiseInterface
    {
        $deviceId = $this->provider->getDeviceId();
        $headers = $this->provider->generateHeaders($deviceId);

        return $this->client
            ->request(
                'GET',
                sprintf($this->provider::API_LOGIN_REQUEST, $phone),
                [
                    'headers' => $headers
                ]
            )
            ->then(
                function (ResponseInterface $response) use ($acceptableResponseCode) {
                    if (
                        is_array($acceptableResponseCode) &&
                        !in_array($response->getStatusCode(), $acceptableResponseCode)
                    ) {
                        throw new ProviderClientException(
                            "No addresses by this phone number (" . $response->getStatusCode() . ")",
                            Response::HTTP_BAD_REQUEST
                        );
                    } elseif (
                        !is_array($acceptableResponseCode) &&
                        $response->getStatusCode() !== $acceptableResponseCode
                    ) {
                        throw new ProviderClientException(
                            "No addresses by this phone number (" . $response->getStatusCode() . ")",
                            Response::HTTP_BAD_REQUEST
                        );
                    }

                    $addresses = $this->mapResponse($response, mapTo: LoginAddress::class, options: ['array' => true]);
                    foreach ($addresses as $id => &$address) {
                        $address->id = ++$id;
                    }

                    return $addresses;
                },
                function ($error) {
                    throw new ProviderClientException($error->getMessage(), $error->getCode());
                }
            );
    }

    public function requestSms(int $addressId, ProviderHelper $helper): PromiseInterface
    {
        $cachedAddresses = $helper->get('addresses');
        $addressToUse = null;
        foreach ($cachedAddresses as $address) {
            if ($address->id === $addressId) {
                $addressToUse = $address;
            }
        }

        if (!$addressToUse) {
            throw new ProviderClientException("Address not found for this id", Response::HTTP_BAD_REQUEST);
        }

        $headers = $this->provider->generateHeaders(
            deviceId: $helper->getDeviceId(),
            operatorId: $addressToUse->operatorId,
            accountId: $addressToUse->accountId,
            placeId: $addressToUse->placeId,
        );

        return $this->client
            ->request(
                'POST',
                sprintf($this->provider::API_SMS_REQUEST, $helper->getPhone()),
                $headers,
                json_encode(
                    [
                        'accountId' => $addressToUse->accountId,
                        'placeId' => $addressToUse->placeId,
                        'address' => $addressToUse->address,
                        'subscriberId' => $addressToUse->subscriberId,
                        'operatorId' => $addressToUse->operatorId,
                    ],
                    JSON_UNESCAPED_UNICODE
                )
            )
            ->then(
                function (ResponseInterface $response) use ($helper, $addressId) {
                    switch ($response->getStatusCode()) {
                        case Response::HTTP_OK:
                            $helper->store('smsAddressId', $addressId);
                            return true;
                        default:
                            throw new ProviderClientException(
                                sprintf("[%s] %s", $response->getStatusCode(), $response->getBody()->getContents()),
                                Response::HTTP_BAD_REQUEST
                            );
                    }
                },
                function (ResponseException $error) {
                    $response = $error->getResponse();

                    throw new ProviderClientException(
                        trim($response->getBody()->getContents(), '"'), $error->getCode()
                    );
                }
            );
    }

    public function smsConfirmation(int $code, ProviderHelper $helper): PromiseInterface
    {
        $addressId = $helper->get('smsAddressId');
        if (!$addressId) {
            throw new ProviderClientException(
                "You need to call method with address selection first",
                Response::HTTP_BAD_REQUEST
            );
        }

        $cachedAddresses = $helper->get('addresses');
        $addressToUse = null;
        foreach ($cachedAddresses as $address) {
            if ($address->id === $addressId) {
                $addressToUse = $address;
            }
        }

        if (!$addressToUse) {
            throw new ProviderClientException("Address not found for this id", Response::HTTP_BAD_REQUEST);
        }

        $headers = $this->provider->generateHeaders(
            deviceId: $helper->getDeviceId(),
            operatorId: $addressToUse->operatorId,
            accountId: $addressToUse->accountId,
            placeId: $addressToUse->placeId,
        );

        return $this->client
            ->request(
                'POST',
                sprintf($this->provider::API_SMS_CONFIRMATION, $helper->getPhone()),
                $headers,
                json_encode(
                    [
                        'subscriberId' => $addressToUse->subscriberId,
                        'operatorId' => $addressToUse->operatorId,
                        'accountId' => $addressToUse->accountId,
                        'confirm1' => $code,
                        'login' => $helper->getPhone(),
                    ],
                    JSON_UNESCAPED_UNICODE
                )
            )
            ->then(
                function (ResponseInterface $response) use ($helper, $addressToUse) {
                    $authDto = $this->mapResponse($response, mapTo: AuthDto::class);
                    $authDto->deviceId = $helper->getDeviceId();
                    $authDto->phone = $helper->getPhone();
                    $authDto->accountId = $addressToUse->accountId;

                    return $authDto;
                },
                function (ResponseException $error) {
                    $response = $error->getResponse();

                    throw new ProviderClientException(
                        trim($response->getBody()->getContents(), '"'), $error->getCode()
                    );
                }
            );
    }
}