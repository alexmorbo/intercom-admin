<?php

namespace App\Provider\DomRu\Dto\Auth;

use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;

class AuthDto implements AuthDtoInterface
{
    public string $deviceId;
    public int $operatorId;
    public string $accountId;
    public string $operatorName;
    public string $tokenType;
    public string $accessToken;
    public mixed $expiresIn;
    public string $refreshToken;
    public mixed $refreshExpiresIn;

    public function populate(array $data): self
    {
        $this->deviceId = $data['deviceId'];
        $this->operatorId = $data['operatorId'];
        $this->accountId = $data['accountId'];
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

    public function toArray(): array
    {
        return [
            'deviceId' => $this->deviceId,
            'operatorId' => $this->operatorId,
            'accountId' => $this->accountId,
            'operatorName' => $this->operatorName,
            'tokenType' => $this->tokenType,
            'accessToken' => $this->accessToken,
            'expiresIn' => $this->expiresIn,
            'refreshToken' => $this->refreshToken,
            'refreshExpiresIn' => $this->refreshExpiresIn,
        ];
    }
}