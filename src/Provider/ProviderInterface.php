<?php

namespace App\Provider;

use App\Enum\Provider\AuthScheme;
use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use App\Interfaces\ProviderSyncClientInterface;
use Symfony\Component\Serializer\SerializerInterface;

interface ProviderInterface
{
    public function getName(): string;
    public function getAuthScheme(): AuthScheme;
    public function formatPhone(string $phone): string;
    public function getAuthDtoClass(): string;
    public function setSerializer(SerializerInterface $serializer): void;
    public function getSyncClient(?AuthDtoInterface $authDto = null): ProviderSyncClientInterface;
}