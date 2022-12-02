<?php

namespace App\Provider\DomRu\Dto\Subscriber;

class Subscriber
{
    public int $id;
    public string $name;
    public string $accountId;
    public ?string $nickname = null;
}