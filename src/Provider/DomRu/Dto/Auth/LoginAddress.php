<?php

namespace App\Provider\DomRu\Dto\Auth;

class LoginAddress
{
    public int $id;
    public string $deviceId;
    public int $operatorId;
    public int $subscriberId;
    public ?string $accountId;
    public int $placeId;
    public string $address;
    public mixed $profileId;
}