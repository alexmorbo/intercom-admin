<?php

namespace App\Dto\Provider;
use Symfony\Component\Serializer\Annotation\Groups;

class BalanceDto
{
    #[Groups(['publicApi'])]
    public ?float $balance = null;

    #[Groups(['publicApi'])]
    public ?bool $isBlocked = null;

    #[Groups(['publicApi'])]
    public ?string $paymentUrl = null;
}