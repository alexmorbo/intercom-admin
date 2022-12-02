<?php

namespace App\Provider\DomRu\Dto\Auth;

use App\Interfaces\Dto\Provider\Auth\RequestConfirmSmsInterface;

class RequestConfirmSms implements RequestConfirmSmsInterface
{
    public string $phone;
    public string $deviceId;
    public string $login;
    public int $confirm1;
    public int $operatorId;
    public string $accountId;
    public int $subscriberId;

    public function populate(array $data): self
    {
        $this->phone = $data['phone'];
        $this->deviceId = $data['deviceId'];
        $this->login = $data['login'];
        $this->operatorId = $data['operatorId'];
        $this->subscriberId = $data['subscriberId'];
        $this->accountId = $data['accountId'];
        $this->confirm1 = $data['confirm1'];

        return $this;
    }

    public function toArray(): array
    {
        return [
            'login' => $this->login,
            'operatorId' => $this->operatorId,
            'subscriberId' => $this->subscriberId,
            'accountId' => $this->accountId,
            'confirm1' => $this->confirm1,
        ];
    }
}