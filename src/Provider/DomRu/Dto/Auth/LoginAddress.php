<?php

namespace App\Provider\DomRu\Dto\Auth;

class LoginAddress
{
    public ?int $id = null;
    public ?string $deviceId = null;
    public int $operatorId;
    public int $subscriberId;
    public ?string $accountId = null;
    public int $placeId;
    public string $address;
    public ?int $profileId = null;
}