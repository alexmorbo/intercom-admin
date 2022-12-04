<?php

namespace App\Dto\Provider;

use Symfony\Component\Serializer\Annotation\Groups;

class AddressDto
{
    #[Groups(['publicApi'])]
    public mixed $id = null;

    #[Groups(['publicApi'])]
    public ?string $address = null;

    #[Groups(['publicApi'])]
    public ?LocationDto $location = null;

    /** @var array<CameraDto> */
    #[Groups(['publicApi'])]
    public array $cameras = [];

    /** @var array<RelayDto> */
    #[Groups(['publicApi'])]
    public array $relays = [];
}