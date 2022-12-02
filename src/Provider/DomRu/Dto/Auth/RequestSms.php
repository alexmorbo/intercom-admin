<?php

namespace App\Provider\DomRu\Dto\Auth;

use App\Interfaces\Dto\Provider\Auth\RequestSmsInterface;

class RequestSms implements RequestSmsInterface
{
    public string $phone;
    public string $deviceId;
    public int $operatorId;
    public int $placeId;
    public string $address;
    public int $subscriberId;
    public string $accountId;

    public function populate(array $data): self
    {
        $this->phone = $data['phone'];
        $this->deviceId = $data['deviceId'];
        $this->operatorId = $data['operatorId'];
        $this->placeId = $data['placeId'];
        $this->address = $data['address'];
        $this->subscriberId = $data['subscriberId'];
        $this->accountId = $data['accountId'];

        return $this;
    }

    public function toArray(): array
    {
        return [
            'operatorId' => $this->operatorId,
            'placeId' => $this->placeId,
            'address' => $this->address,
            'subscriberId' => $this->subscriberId,
            'accountId' => $this->accountId,
        ];
    }
}