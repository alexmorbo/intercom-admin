<?php

namespace App\Interfaces\Dto\Provider\Auth;

interface RequestConfirmSmsInterface
{
    public function populate(array $data): self;
    public function toArray(): array;
}