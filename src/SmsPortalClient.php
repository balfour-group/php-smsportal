<?php

namespace Balfour\SmsPortal;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class SmsPortalClient
{
    /**
     * @var string
     */
    const HTTP_GET = 'GET';

    /**
     * @var string
     */
    const HTTP_POST = 'POST';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $apiClientId;

    /**
     * @var string
     */
    protected $apiClientSecret;

    /**
     * @var string
     */
    protected $apiToken;

    /**
     * @param Client $client
     * @param string|null $apiClientId
     * @param string|null $apiClientSecret
     */
    public function __construct(Client $client, ?string $apiClientId = null, ?string $apiClientSecret = null)
    {
        $this->client = $client;
        $this->uri = 'https://rest.smsportal.com/v1/';
        $this->apiClientId = $apiClientId;
        $this->apiClientSecret = $apiClientSecret;
    }

    /**
     * Set the base REST Uri
     *
     * @param string $uri
     */
    public function setUri(string $uri)
    {
        $this->uri = $uri;
    }

    /**
     * Return base REST Uri
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Set the api client id
     *
     * @param string $apiClientId
     */
    public function setApiClientId(string $apiClientId)
    {
        $this->apiClientId = $apiClientId;
    }

    /**
     * Return the api client id
     *
     * @return string
     */
    public function getApiClientId()
    {
        return $this->apiClientId;
    }

    /**
     * Set the api client secret
     *
     * @param string $apiClientSecret
     */
    public function setApiClientSecret(string $apiClientSecret)
    {
        $this->apiClientSecret = $apiClientSecret;
    }

    /**
     * Return the api client secret
     *
     * @return string
     */
    public function getApiClientSecret()
    {
        return $this->apiClientSecret;
    }

    /**
     * @param string $endpoint
     * @param array $params
     * @return string
     */
    protected function getBaseUri($endpoint, array $params = [])
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
     * @param array $params
     * @param array $headers
     * @return array
     */
    public function get($endpoint, array $params = [], $headers = [])
    {
        $request = new Request(
            'GET',
            $this->getBaseUri($endpoint, $params),
            $headers
        );

        return $this->sendRequest($request);
    }

    /**
     * @param string $endpoint
     * @param array $payload
     * @param array $headers
     * @return array
     * @throws Exception
     */
    public function post($endpoint, array $payload = [])
    {
        if ($this->apiToken === null) {
            echo 'doing authorize()';
            $this->authorize();
        }

        $request = new Request(
            'POST',
            $this->getBaseUri($endpoint),
            ['Authorization' => 'Bearer ' . $this->apiToken]
        );

        return $this->sendRequest($request, ['json' => $payload]);
    }

    protected function sendRequest(Request $request, $options = [])
    {
        $response = $this->client->send($request, $options);
        $body = (string) $response->getBody();

        return json_decode($body, true);
    }

    public function authorize()
    {
        $headers = ['Authorization' => 'Basic ' . base64_encode($this->apiClientId . ':' . $this->apiClientSecret)];
        $response = $this->get('Authentication', [], $headers);

        if (!isset($response['token'])) {
            throw new Exception(
                sprintf(
                    'Unable to authenticate using apiClientId=%s, ApiClientSecret=%',
                    $this->apiClientId,
                    $this->apiClientSecret
                )
            );
        }

        $this->apiToken = $response['token'];

        return $this;
    }

    /**
     * Send an SMS
     *
     * @param string $to
     * @param string $message
     * @param string|null $from
     * @return mixed
     * @throws Exception
     */
    public function sendMessage(
        $to,
        $message,
        $from = null
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

        return $this->post(
            'BulkMessages',
            $payload
        );
    }
}
