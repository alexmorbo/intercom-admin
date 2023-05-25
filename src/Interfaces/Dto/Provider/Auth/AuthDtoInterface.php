<?php

namespace App\Interfaces\Dto\Provider\Auth;

interface AuthDtoInterface
{
    public function populate(array $data): self;
    public function getAccountId(): string;
    public function __toArray(): array;
    public function getProviderClass(): string;
}