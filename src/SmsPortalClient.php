<?php

namespace Balfour\SmsPortal;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class SmsPortalClient
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var CacheItemPoolInterface|null
     */
    protected $cache;

    /**
     * @var string
     */
    protected $uri = 'https://rest.smsportal.com/v1/';

    /**
     * @var string
     */
    protected $clientID;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var mixed[]
     */
    protected $apiToken;

    /**
     * @param Client $client
     * @param CacheItemPoolInterface|null $cache
     * @param string|null $clientID
     * @param string|null $secret
     */
    public function __construct(
        Client $client,
        ?CacheItemPoolInterface $cache,
        ?string $clientID = null,
        ?string $secret = null
    ) {
        $this->client = $client;
        $this->cache = $cache;
        $this->clientID = $clientID;
        $this->secret = $secret;
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @param CacheItemPoolInterface $cache
     * @return $this
     */
    public function setCache(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @return CacheItemPoolInterface|null
     */
    public function getCache(): ?CacheItemPoolInterface
    {
        return $this->cache;
    }

    /**
     * @param string $uri
     * @return $this
     */
    public function setUri(string $uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @param string $clientID
     * @return $this
     */
    public function setClientID(string $clientID)
    {
        $this->clientID = $clientID;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientID(): string
    {
        return $this->clientID;
    }

    /**
     * @param string $secret
     * @return $this
     */
    public function setSecret(string $secret)
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @param mixed[] $apiToken
     * @return $this
     */
    public function setAPIToken(array $apiToken)
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    /**
     * @return mixed[]|null
     */
    public function getAPIToken(): ?array
    {
        return $this->apiToken;
    }

    /**
     * @param string $endpoint
     * @param mixed[] $params
     * @return string
     */
    protected function getBaseUri($endpoint, array $params = []): string
    {
        $uri = $this->uri;
        $uri = rtrim($uri, '/');
        $uri .=  '/' . ltrim($endpoint, '/');

        if (!empty($params)) {
            $uri .= '?' . http_build_query($params);
        }

        return $uri;
    }

    /**
     * @param string $endpoint
     * @param mixed[] $params
     * @return mixed[]
     * @throws Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function get(string $endpoint, array $params = []): array
    {
        $this->authorize();

        $request = $this->createRequest('GET', $endpoint, $params);

        return $this->sendRequest($request);
    }

    /**
     * @param string $endpoint
     * @param mixed[] $payload
     * @return mixed[]
     * @throws Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function post(string $endpoint, array $payload = []): array
    {
        $this->authorize();

        $request = $this->createRequest('POST', $endpoint, [], $payload);

        return $this->sendRequest($request);
    }

    /**
     * @return array
     */
    protected function getDefaultRequestOptions()
    {
        return [
            'connect_timeout' => 2000,
            'timeout' => 6000,
        ];
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @param array|null $payload
     * @return Request
     */
    protected function createRequest(
        string $method,
        string $endpoint,
        array $params = [],
        ?array $payload = null
    ): Request {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($this->apiToken) {
            $headers['Authorization'] = sprintf('Bearer %s', $this->apiToken['token']);
        }

        $body = $payload ? json_encode($payload) : null;

        return new Request(
            $method,
            $this->getBaseUri($endpoint, $params),
            $headers,
            $body
        );
    }

    /**
     * @param Request $request
     * @return mixed[]
     */
    protected function sendRequest(Request $request): array
    {
        $response = $this->client->send($request, $this->getDefaultRequestOptions());
        $body = (string) $response->getBody();
        return json_decode($body, true);
    }

    /**
     * @return mixed[]
     */
    public function getAuthorizationToken(): array
    {
        $request = new Request(
            'GET',
            $this->getBaseUri('Authentication'),
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        );

        $options = array_merge(
            [
                'auth' => [
                    $this->clientID,
                    $this->secret,
                ],
            ],
            $this->getDefaultRequestOptions()
        );

        $response = $this->client->send($request, $options);

        $body = (string) $response->getBody();
        $token = json_decode($body, true);
        $token['expiresAt'] = time() + $token['expiresInMinutes'];

        return $token;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function authorize(): void
    {
        // we first try retrieve a token from the class instance
        // if nothing, we look at our cache
        // finally, we generate one using our client id and secret by doing an authentication call to the api

        $this->apiToken = $this->getAPITokenFromClassInstance(function (): array {
            return $this->getAPITokenFromCache(function (?CacheItemPoolInterface $cache, ?CacheItemInterface $item): array {
                $apiToken = $this->getAuthorizationToken();

                if ($cache && $item) {
                    $item->expiresAfter($apiToken['expiresInMinutes']);
                    $item->set($apiToken);

                    $cache->save($item);
                }

                return $apiToken;
            });
        });
    }

    /**
     * @param callable|null $default
     * @return mixed[]|null
     */
    protected function getAPITokenFromClassInstance(?callable $default = null): ?array
    {
        if ($this->apiToken) {
            if ($this->apiToken['expiresAt'] > time()) {
                return $this->apiToken;
            }
        }

        return $default ? call_user_func($default) : null;
    }

    /**
     * @param callable|null $default
     * @return mixed[]|null
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function getAPITokenFromCache(?callable $default = null): ?array
    {
        $item = null;

        if ($this->cache) {
            $item = $this->cache->getItem('smsportal_auth_token');

            if ($item->isHit()) {
                $apiToken = $item->get();

                if ($apiToken['expiresAt'] > time()) {
                    return $apiToken;
                }
            }
        }

        return $default ? call_user_func($default, $this->cache, $item) : null;
    }

    /**
     * @param string $to
     * @param string $message
     * @param string|null $from
     * @return mixed[]
     * @throws Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function sendMessage(
        string $to,
        string $message,
        ?string $from = null
    ) {
        $payload = [
            'messages' => [
                [
                    'destination' => $to,
                    'content' => $message,
                ]
            ]
        ];

        $sendOptions = [];

        if ($from) {
            $sendOptions['senderId'] = $from;
        }

        if (count($sendOptions) > 0) {
            $payload['SendOptions'] = $sendOptions;
        }

        return $this->post('BulkMessages', $payload);
    }
}
