<?php

namespace App\Traits;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait RequestTrait
{
    protected mixed $requestBody = null;
    protected function parseJsonRequest(ServerRequestInterface $request)
    {
        if ($request->hasHeader('Content-Type')) {
            if (($contentType = $request->getHeader('Content-Type')[0]) && $contentType === 'application/json') {
                $this->requestBody = json_decode($request->getBody()->getContents(), true);
            }
        }
    }

    protected function hydrateRequest(string $mapTo, array $data = null): mixed
    {
        if ($data === null) {
            $data = $this->requestBody;
        }

        if ($data === null) {
            throw new BadRequestHttpException('Request body is empty');
        }

        return $this->providerRegistry->getHydrator()->hydrate($data, $mapTo);
    }
}