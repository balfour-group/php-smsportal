<?php

namespace Balfour\SmsPortal;

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
    protected $apiSecret;

    /**
     * @var string
     */
    protected $apiToken;

    public function __construct(string $baseRestUri, string $apiClientId, string $apiSecret)
    {
        $this->client = new Client;
        $this->baseRestUri = $baseRestUri;
        $this->apiClientId = $apiClientId;
        $this->apiSecret = $apiSecret;
    }

    /**
     * get apiToken
     *
     * https://docs.smsportal.com/reference#authentication
     * @return SmsPortalClient
     * @throws \Exception
     */
    public function authorize()
    {
        $response = $this->client->request(static::HTTP_GET, $this->baseRestUri . 'Authentication', [
            'http_errors' => false,
            'headers' => ['Authorization' => 'Basic ' . base64_encode($this->apiClientId . ':' . $this->apiSecret)]
        ]);
        $responseData = $this->getResponse((string) $response->getBody());
        $this->apiToken = $responseData['token'];
        return $this;
    }

    /**
     * @param $to
     * @param $message
     * @param string|null $from
     * todo: reportUrl, etc
     *
     * @return mixed
     * @throws \Exception
     */
    public function sendMessage(
        $to,
        $message,
        $from = null
    ) {
        return $this->sendRequest([
            'messages' => [
                [
                    'destination' => $to,
                    'content' => $message,
                ]
            ]
        ]);
    }

    /**
     * Submit API request to send SMS
     *
     * @link https://docs.smsportal.com/reference#bulkmessages
     * @param array $options
     * @return array
     * @throws \Exception
     */
    protected function sendRequest(array $options)
    {
        $response = $this->authorize()->client->request(static::HTTP_POST, $this->baseRestUri . 'BulkMessages', [
            'json' => $options,
            'http_errors' => false,
            'headers' => ['Authorization' => 'Bearer ' . $this->apiToken]
        ]);

        $response = $this->getResponse((string) $response->getBody());

        if (isset($response['errors'])) {
            echo 'errors is set...';
            $errorMessage = $response['errors'][0]['errorMessage'] ?? 'Error sending SMS';
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
