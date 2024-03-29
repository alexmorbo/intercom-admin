<?php

namespace App\Provider;

use App\Enum\Provider\AuthScheme;
use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use App\Interfaces\ProviderASyncClientInterface;
use App\Interfaces\ProviderSyncClientInterface;
use Symfony\Component\Serializer\SerializerInterface;

interface ProviderInterface
{
    public function getName(): string;
    public function getAuthScheme(): AuthScheme;
    public function formatPhone(string $phone): string;
    public function getAuthDtoClass(): string;
    public function getSerializer(): SerializerInterface;
    public function getSyncClient(?AuthDtoInterface $authDto = null): ProviderSyncClientInterface;
    public function getASyncClient(?AuthDtoInterface $authDto = null): ProviderASyncClientInterface;
}