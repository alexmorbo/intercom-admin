<?php

namespace App\Provider\DomRu\Dto\Place;

class Camera
{
    public int $id;
    public string $name;
    public bool $previewAvailable;
    public bool $videoDownloadAvailable;
    public int $quota;
    public int $timeZone;
}