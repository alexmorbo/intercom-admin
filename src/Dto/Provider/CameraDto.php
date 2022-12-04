<?php

namespace App\Dto\Provider;

use Symfony\Component\Serializer\Annotation\Groups;

class CameraDto
{
    #[Groups(['publicApi'])]
    public mixed $id = null;

    #[Groups(['publicApi'])]
    public ?string $name = null;
}