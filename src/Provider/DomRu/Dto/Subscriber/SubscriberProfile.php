<?php

namespace App\Provider\DomRu\Dto\Subscriber;

class SubscriberProfile
{
    public Subscriber $subscriber;
    public ?string $pushUserId;
    public bool $callSelectedPlaceOnly;
    /**
     * @var array <SubscriberPhone>
     */
    public array $subscriberPhones;
    public bool $checkPhoneForSvcActivation;
    public bool $allowAddPhone;
}