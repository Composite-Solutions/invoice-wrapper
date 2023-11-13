<?php

namespace Composite\InvoiceWrapper\Services\Billingo\BillingoApiService;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\StreamInterface;

class GuzzleClientBase
{
    protected Client $client;
    private string $apiKey;

    /**
     * @param string $apiKey
     * @param string $baseUrl
     */
    protected function __construct(string $apiKey, string $baseUrl)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'verify' => false,
            'debug' => false,
        ]);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $payload
     * @param bool $stream
     * @return mixed|StreamInterface
     * @throws GuzzleException
     */
    private function request(string $method, string $uri, array $payload = [], bool $stream = false): mixed
    {
        $defaultOptions = [
            'headers' => [
                'Accept' => 'application/json',
                'X-API-KEY' => $this->apiKey,
            ],
        ];

        $response = $this->client->request($method, $uri, array_merge($defaultOptions, $payload));

        if ($stream) {
            return $response->getBody();
        } else {
            return json_decode($response->getBody(), true);
        }
    }

    /**
     * @param string $uri
     * @param array $payload
     * @param bool $stream
     * @return mixed|StreamInterface
     * @throws GuzzleException
     */
    protected function get(string $uri, array $payload = [], bool $stream = false): mixed
    {
        return $this->request('GET', $uri, [
            'query' => $payload,
        ], $stream);
    }

    /**
     * @param string $uri
     * @param array $payload
     * @return mixed|StreamInterface
     * @throws GuzzleException
     */
    protected function post(string $uri, array $payload = []): mixed
    {
        return $this->request('POST', $uri, [
            'json' => $payload,
        ]);
    }

    /**
     * @param string $uri
     * @param array $payload
     * @return mixed|StreamInterface
     * @throws GuzzleException
     */
    protected function put(string $uri, array $payload = []): mixed
    {
        return $this->request('PUT', $uri, [
            'json' => $payload,
        ]);
    }
}
