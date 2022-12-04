<?php

namespace App\Dto\Provider;

use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use Symfony\Component\Serializer\Annotation\Groups;

class AccountDto
{
    #[Groups(['publicApi'])]
    public mixed $id = null;
    #[Groups(['publicApi'])]
    public AuthDtoInterface $authDto;
    #[Groups(['publicApi'])]
    public ?BalanceDto $balance = null;
    #[Groups(['publicApi'])]
    /** @var array<AddressDto>  */
    public array $addresses;
}