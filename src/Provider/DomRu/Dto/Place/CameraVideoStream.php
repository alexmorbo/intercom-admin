<?php

namespace App\Provider\DomRu\Dto\Place;

use Symfony\Component\Serializer\Annotation\SerializedName;

class CameraVideoStream
{
    /** @SerializedName("URL") */
    public ?string $url;
    public ?int $errorCode = null;
    public ?string $errorMessage;
    /** @SerializedName("Status") */
    public mixed $status;
}