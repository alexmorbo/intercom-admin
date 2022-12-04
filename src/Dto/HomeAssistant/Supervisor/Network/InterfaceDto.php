<?php

namespace App\Dto\HomeAssistant\Supervisor\Network;

class InterfaceDto
{
    public string $interface;
    public string $type;
    public bool $enabled;
    public bool $connected;
    public bool $primary;
    public ?IpDto $ipv4 = null;
    public ?IpDto $ipv6 = null;
    public mixed $wifi;
    public mixed $vlan;
}