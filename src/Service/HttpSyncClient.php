<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpSyncClient
{
    private HttpClientInterface $client;

    public function __construct()
    {
        $this->client = HttpClient::create([
            'proxy' => 'http://10.30.29.1:9090',
        ]);
    }

    public function getClient(): HttpClientInterface
    {
        return $this->client;
    }
}