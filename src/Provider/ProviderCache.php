<?php

namespace App\Provider;

class ProviderCache
{
    private array $cache = [];

    public function __construct()
    {
    }

    public function get(string $provider, string $key): mixed
    {
        return $this->cache[$provider][$key] ?? null;
    }

    public function set(string $provider, string $key, mixed $value): void
    {
        $this->cache[$provider][$key] = $value;
    }

    public function cleanProviderCache(string $provider): void
    {
        unset($this->cache[$provider]);
    }
}