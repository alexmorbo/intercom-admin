<?php

namespace App\Interfaces;

use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use App\Interfaces\Dto\Provider\Auth\RequestConfirmSmsInterface;
use App\Interfaces\Dto\Provider\Auth\RequestSmsInterface;
use App\Interfaces\Dto\Provider\User\UserBalanceInterface;
use App\Interfaces\Dto\Provider\User\UserInfoInterface;
use App\Provider\ProviderInterface;
use Generator;
use Symfony\Component\Serializer\SerializerInterface;

interface ProviderSyncClientInterface
{
    public function __construct(ProviderInterface $provider);
    public function setSerializer(SerializerInterface $serializer): void;
    public function withAuth(AuthDtoInterface $authDto): self;
    public function setAuthData(AuthDtoInterface $authDto): void;

    public function requestSmsAuth(RequestSmsInterface $requestSmsDto);
    public function confirmSmsAuth(RequestConfirmSmsInterface $confirmSmsDto);

    public function getUserBalance(): UserBalanceInterface;
    public function getUserInfo(): UserInfoInterface;
    public function getRelays(): Generator;

}