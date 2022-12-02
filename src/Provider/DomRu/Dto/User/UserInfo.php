<?php

namespace App\Provider\DomRu\Dto\User;

use App\Interfaces\Dto\Provider\User\UserInfoInterface;

class UserInfo implements UserInfoInterface
{
    /**
     * @var array <SubscriberPlaces>
     */
    public array $subscriberPlaces;
}