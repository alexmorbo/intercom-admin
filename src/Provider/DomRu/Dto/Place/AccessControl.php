<?php

namespace App\Provider\DomRu\Dto\Place;

class AccessControl
{
    public int $id;
    public int $operatorId;
    public string $name;
    public ?int $forpostGroupId;
    public ?int $forpostAccountId;
    public string $type;
    public bool $allowOpen;
    public string $openMethod;
    public bool $allowVideo;
    public bool $allowCallMobile;
    public bool $allowSlideshow;
    public bool $previewAvailable;
    public bool $videoDownloadAvailable;
    public int $timeZone;
    public int $quota;
    public ?int $externalCameraId;
    public ?int $externalDeviceId;
    public mixed $entrances;
}