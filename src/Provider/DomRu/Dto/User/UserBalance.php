<?php

namespace App\Provider\DomRu\Dto\User;

use App\Interfaces\Dto\Provider\User\UserBalanceInterface;

class UserBalance implements UserBalanceInterface
{
    public ?float $balance;
    public string $blockType;
    public ?float $amountSum;
    public ?string $targetDate;
    public ?string $paymentLink;
    public bool $blocked;
}