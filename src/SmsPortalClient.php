<?php

namespace Balfour\SmsPortal;

use Exception;
use GuzzleHttp\Client;

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
    protected $baseRestUri;

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
     * @param string $apiClientId
     * @param string $apiClientSecret
     */
    public function __construct(string $apiClientId = null, string $apiClientSecret = null)
    {
        $this->client = new Client;
        $this->baseRestUri = 'https://rest.smsportal.com/v1/';
        $this->apiClientId = $apiClientId;
        $this->apiClientSecret = $apiClientSecret;
    }

    /**
     * Set the base REST Uri
     *
     * @param string $baseRestUri
     */
    public function setBaseRestUri(string $baseRestUri)
    {
        $this->baseRestUri = $baseRestUri;
    }

    /**
     * Return base REST Uri
     *
     * @return string
     */
    public function getBaseRestUri()
    {
        return $this->baseRestUri;
    }

    /**
     * Set the API client id
     *
     * @param string $apiClientId
     */
    public function setApiClientId(string $apiClientId)
    {
        $this->apiClientId = $apiClientId;
    }

    /**
     * Get the API client id
     *
     * @return string
     */
    public function getApiClientId()
    {
        return $this->apiClientId;
    }

    /**
     * Set the API client secret
     *
     * @param string $apiClientSecret
     */
    public function setApiClientSecret(string $apiClientSecret)
    {
        $this->apiClientSecret = $apiClientSecret;
    }

    /**
     * Get the API client secret
     *
     * @return string
     */
    public function getApiClientSecret()
    {
        return $this->apiClientSecret;
    }

    /**
     * Sets the API token
     * https://docs.smsportal.com/reference#authentication
     *
     * @return SmsPortalClient
     * @throws Exception
     */
    public function authorize()
    {
        $response = $this->client->request(static::HTTP_GET, $this->baseRestUri . 'Authentication', [
            'http_errors' => false,
            'headers' => ['Authorization' => 'Basic ' . base64_encode($this->apiClientId . ':' . $this->apiClientSecret)]
        ]);

        $responseData = $this->getResponse((string) $response->getBody());

        $this->apiToken = $responseData['token'];

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
        $request = [
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
            $request['SendOptions'] = $sendOptions;
        }

        return $this->sendRequest($request);
    }

    /**
     * Submit API request to send SMS
     *
     * @link https://docs.smsportal.com/reference#bulkmessages
     * @param array $request
     * @return array
     * @throws \Exception
     */
    protected function sendRequest(array $request)
    {
        $response = $this->authorize()->client->request(static::HTTP_POST, $this->baseRestUri . 'BulkMessages', [
            'json' => $request,
            'http_errors' => false,
            'headers' => ['Authorization' => 'Bearer ' . $this->apiToken]
        ]);

        $response = $this->getResponse((string) $response->getBody());

        if (isset($response['statusCode']) && $response['statusCode'] !== 200) {
            $errorMessage = 'Error sending SMS message';
            if (isset($response['errors'])) {
                $errorMessage = json_encode($response['errors']);
            } elseif (isset($response['ErrorReport']['Faults'])) {
                $errorMessage = json_encode($response['ErrorReport']['Faults']);
            }

            throw new \Exception($errorMessage);
        }

        return $response;
    }

    /**
     * Transform response string to responseData
     *
     * @param string $responseBody
     * @return array
     */
    private function getResponse(string $responseBody): array
    {
        return json_decode($responseBody, true);
    }
}
