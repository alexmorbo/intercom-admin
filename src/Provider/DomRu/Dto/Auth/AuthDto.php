<?php

namespace App\Provider\DomRu\Dto\Auth;

use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use App\Provider\DomRu\DomRu;

class AuthDto implements AuthDtoInterface
{
    public const PROVIDER_CLASS = DomRu::class;

    public ?string $deviceId = null;
    public ?string $phone = null;
    public ?string $accountId = null;
    public int $operatorId;
    public string $operatorName;
    public string $tokenType;
    public string $accessToken;
    public mixed $expiresIn;
    public string $refreshToken;
    public mixed $refreshExpiresIn;

    public function getProviderClass(): string
    {
        return self::PROVIDER_CLASS;
    }

    public function populate(array $data): self
    {
        $this->deviceId = $data['deviceId'];
        $this->phone = $data['phone'];
        $this->accountId = $data['accountId'];
        $this->operatorId = $data['operatorId'];
        $this->operatorName = $data['operatorName'];
        $this->tokenType = $data['tokenType'];
        $this->accessToken = $data['accessToken'];
        $this->expiresIn = $data['expiresIn'];
        $this->refreshToken = $data['refreshToken'];
        $this->refreshExpiresIn = $data['refreshExpiresIn'];

        return $this;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function __toArray(): array
    {
        return [
            'deviceId' => $this->deviceId,
            'phone' => $this->phone,
            'accountId' => $this->accountId,
            'operatorId' => $this->operatorId,
            'operatorName' => $this->operatorName,
            'tokenType' => $this->tokenType,
            'accessToken' => $this->accessToken,
            'expiresIn' => $this->expiresIn,
            'refreshToken' => $this->refreshToken,
            'refreshExpiresIn' => $this->refreshExpiresIn,
        ];
    }
}