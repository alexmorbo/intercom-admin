<?php

namespace App\Provider\DomRu\Dto\Subscriber;

class Place
{
    public int $id;
    public Address $address;
    public Location $location;
    public bool $autoArmingState;
    public ?int $autoArmingRadius;
}