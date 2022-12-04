<?php

namespace App\Dto\HomeAssistant\Supervisor\Network;

use Symfony\Component\Serializer\Annotation\SerializedName;

class NetworkDto
{
    /**
     * @var array <InterfaceDto>
     */
    public array $interfaces;

    public DockerDto $docker;

    /** @SerializedName("host_internet") */
    public bool $hostInternet;

    /** @SerializedName("supervisor_internet") */
    public bool $supervisorInternet;
}