<?php

namespace App\Dto\Provider;

use Symfony\Component\Serializer\Annotation\Groups;

class RelayDto
{
    #[Groups(['publicApi'])]
    public mixed $id = null;

    #[Groups(['publicApi'])]
    public mixed $cameraId = null;
}