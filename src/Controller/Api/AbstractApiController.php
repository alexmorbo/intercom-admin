<?php

namespace App\Controller\Api;

use App\Provider\ProviderRegistry;
use App\Traits\RequestTrait;
use Psr\Http\Message\ServerRequestInterface;

class AbstractApiController
{
    use RequestTrait;

    public function __construct(
        protected ProviderRegistry $providerRegistry,
        protected ServerRequestInterface $request,
    )
    {
        $this->parseJsonRequest($this->request);
    }
}