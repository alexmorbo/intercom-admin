<?php

namespace App\Provider\DomRu\Dto\Subscriber;

use App\Provider\DomRu\Dto\Place\AccessControl;
use App\Provider\DomRu\Dto\Place\Event;
use App\Provider\DomRu\Dto\Place\Camera;

class SubscriberPlace
{
    public int $id;
    public string $subscriberType;
    public string $subscriberState;
    public Place $place;
    public Subscriber $subscriber;
    public mixed $guardCallOut;
    public mixed $payment;
    public bool $blocked;
    public string $provider;
    /**
     * @var array <AccessControl>
     */
    public array $accessControls;
    /**
     * @var array <Event>
     */
    public array $events;
    /**
     * @var array <Camera>
     */
    public array $cameras = [];
}