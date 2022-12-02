<?php

namespace App\Interfaces\Dto\Provider\Auth;

interface RequestSmsInterface
{
    public function populate(array $data): self;
    public function toArray(): array;
}