<?php

namespace App\Provider\DomRu\Controller;

use App\Exceptions\ProviderClientException;
use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use App\Provider\ProviderHelper;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends DomRuController
{
    public function login(): PromiseInterface
    {
        $phone = $this->provider->formatPhone($this->requestBody['phone'] ?? null);
        $helper = ProviderHelper::getInstance($this->provider, $phone);
        $this->provider->getCache()->set(
            $this->provider->getName(),
            'lastPhone',
            $phone
        );

        return $this->client
            ->requestAddressForSms(
                $phone,
                [Response::HTTP_OK, Response::HTTP_MULTIPLE_CHOICES]
            )
            ->then(function (array $addresses) use ($helper) {
                $helper->store('addresses', $addresses);

                return $addresses;
            })
            ->then(fn(array $addresses) => ['data' => $addresses]);
    }

    public function address(): PromiseInterface
    {
        $lastPhone = $this->provider->getCache()->get($this->provider->getName(), 'lastPhone');
        if (!$lastPhone) {
            throw new ProviderClientException("You need to call request address first", Response::HTTP_BAD_REQUEST);
        }

        $helper = ProviderHelper::getInstance($this->provider, $lastPhone);
        if (!$helper) {
            throw new ProviderClientException("No addresses by this phone number", Response::HTTP_BAD_REQUEST);
        }

        $addressId = $this->requestBody['address'] ?? null;
        if (!$addressId) {
            throw new ProviderClientException("Address id is required", Response::HTTP_BAD_REQUEST);
        }

        return $this->client
            ->requestSms($addressId, $helper)
            ->then(fn() => ['data' => ['sms' => 'ok']]);
    }

    public function sms(): PromiseInterface
    {
        $lastPhone = $this->provider->getCache()->get($this->provider->getName(), 'lastPhone');
        if (!$lastPhone) {
            throw new ProviderClientException("You need to call request address method first", Response::HTTP_BAD_REQUEST);
        }

        $helper = ProviderHelper::getInstance($this->provider, $lastPhone);
        if (!$helper) {
            throw new ProviderClientException("No addresses by this phone number", Response::HTTP_BAD_REQUEST);
        }

        $code = $this->requestBody['code'] ?? null;
        if (!$code) {
            throw new ProviderClientException("Code is required", Response::HTTP_BAD_REQUEST);
        }

        return $this->client
            ->smsConfirmation($code, $helper)
            ->then(fn(AuthDtoInterface $authDto) => $helper->storeAuth($authDto))
            ->then(fn() => $helper->clean())
            ->then(fn() => ['data' => ['auth' => 'ok']]);
    }
}