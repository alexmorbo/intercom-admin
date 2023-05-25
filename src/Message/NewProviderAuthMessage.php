<?php

namespace App\Message;

use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use App\Message\Interfaces\SyncMessageInterface;

final readonly class NewProviderAuthMessage implements SyncMessageInterface
{
    public function __construct(private AuthDtoInterface $authDto)
    {
    }

    public function getAuthDto(): AuthDtoInterface
    {
        return $this->authDto;
    }
}
