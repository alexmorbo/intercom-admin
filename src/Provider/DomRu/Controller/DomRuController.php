<?php

namespace App\Provider\DomRu\Controller;

use App\Interfaces\ProviderASyncClientInterface;
use App\Provider\DomRu\DomRu;
use App\Provider\ProviderRegistry;
use App\Traits\RequestTrait;
use Psr\Http\Message\ServerRequestInterface;

abstract class DomRuController
{
    use RequestTrait;

    protected DomRu $provider;
    protected ProviderASyncClientInterface $client;

    public function __construct(
        protected ProviderRegistry $providerRegistry,
        protected ServerRequestInterface $request,
    )
    {
        $providerName = $this->providerRegistry->getProviderFromClass($this);
        $this->provider = $this->providerRegistry->getProvider($providerName);
        $this->client = $this->provider->getASyncClient();
        $this->parseJsonRequest($this->request);
    }
}