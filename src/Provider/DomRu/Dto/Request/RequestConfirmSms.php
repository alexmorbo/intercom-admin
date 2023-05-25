<?php

namespace App\Provider\DomRu\Dto\Request;

class RequestConfirmSms
{
    public string $accountId;
    public int $placeId;
    public string $address;
    public int $subscriberId;
    public int $operatorId;


}