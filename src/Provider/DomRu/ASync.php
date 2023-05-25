<?php

namespace App\Provider\DomRu;

use App\Exceptions\ProviderClientException;
use App\Exceptions\ProviderException;
use App\Interfaces\Dto\Provider\Auth\AuthDtoInterface;
use App\Interfaces\ProviderASyncClientInterface;
use App\Provider\DomRu\Traits\AuthTrait;
use App\Provider\ProviderInterface;
use Clue\React\HttpProxy\ProxyConnector;
use Exception;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Socket\Connector;
use Symfony\Component\Serializer\SerializerInterface;

class ASync implements ProviderASyncClientInterface
{
    use AuthTrait;

    private ProviderInterface $provider;
    private SerializerInterface $serializer;
    private ?AuthDtoInterface $authData = null;
    private Browser $client;

    /**
     * @param DomRu $provider
     */
    public function __construct(ProviderInterface $provider)
    {
        $this->provider = $provider;

        $proxy = new ProxyConnector('127.0.0.1:9090');
        $connector = new Connector(array(
            'tcp' => $proxy,
            'dns' => false,
        ));

        $this->client = (new Browser($connector))->withBase($this->provider::BASE_API_DOMAIN);
    }

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    public function setAuthData(?AuthDtoInterface $authDto = null): void
    {
        $this->authData = $authDto;
    }

    public function withAuth(AuthDtoInterface $authDto): ProviderASyncClientInterface
    {
        $client = clone $this;
        $client->setAuthData($authDto);

        return $client;
    }

    private function generateHeaders(): array
    {
        if ($this->authData === null) {
            throw new ProviderClientException("Auth data is not set", 401);
        }

        $headers = $this->provider->generateHeaders(
            $this->authData->deviceId,
            $this->authData->operatorId,
        );

        $headers['Operator'] = $this->authData->operatorId;
        $headers['Authorization'] = sprintf('%s %s', $this->authData->tokenType, $this->authData->accessToken);

        return $headers;
    }

    protected function mapResponse(
        ResponseInterface $response,
        string $mapTo,
        Exception $clientException = null,
        array $options = []
    ): mixed {
        $body = $response->getBody()->getContents();
        try {
            $json = json_decode(
                json: $body,
                associative: true,
                depth: 512,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            dump($body);
            throw new ProviderException(
                message: 'Invalid JSON response',
                code: 0,
                previous: $e,
            );
        }

        if ($json) {
            return match (true) {
                is_scalar($json) => $json,
                isset($options['array']) && $options['array'] => $this->provider->getHydrator()->hydrateArray($json, $mapTo),
                default => $this->provider->getHydrator()->hydrate($json, $mapTo)
            };
        }

        throw new ProviderException(
            message: $json?->description ?? 'Client exception',
            code: $json?->error_code ?? 0,
            previous: $clientException,
            parameters: (array)($json?->parameters ?? []),
        );
    }
}