<?php

namespace App\Interfaces;

use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use App\Provider\ProviderInterface;

interface ProviderASyncClientInterface
{
    public function __construct(ProviderInterface $provider);
    public function withAuth(AuthDtoInterface $authDto): self;
    public function setAuthData(AuthDtoInterface $authDto): void;
}