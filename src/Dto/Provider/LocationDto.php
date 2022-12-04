<?php

namespace App\Dto\Provider;

use Symfony\Component\Serializer\Annotation\Groups;

class LocationDto
{
    #[Groups(['publicApi'])]
    public ?float $lat = null;

    #[Groups(['publicApi'])]
    public ?float $lon = null;
}