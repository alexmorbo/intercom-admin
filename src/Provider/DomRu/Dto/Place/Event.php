<?php

namespace App\Provider\DomRu\Dto\Place;

class Event
{
    public int $id;
    public int $placeId;
    public string $eventTypeName;
    public int $timestamp;
    public string $message;
    public EventSource $source;
    public mixed $value;
    public mixed $eventStatusValue;
    public mixed $actions;
}