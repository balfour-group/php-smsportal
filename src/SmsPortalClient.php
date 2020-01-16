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
    protected $apiClientSecret;

    /**
     * @var string
     */
    protected $apiToken;

    public function __construct(string $apiClientId = nul, string $apiClientSecret = null)
    {
        $this->client = new Client;
        $this->baseRestUri = 'https://rest.smsportal.com/v1/';
        $this->apiClientId = $apiClientId;
        $this->apiClientSecret = $apiClientSecret;
    }

    public function setApiClientId($apiClientId)
    {
        $this->apiClientId = $apiClientId;
    }

    public function setApiClientSecret($apiClientSecret)
    {
        $this->apiClientSecret = $apiClientSecret;
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
            'headers' => ['Authorization' => 'Basic ' . base64_encode($this->apiClientId . ':' . $this->apiClientSecret)]
        ]);
        $responseData = $this->getResponse((string) $response->getBody());
        $this->apiToken = $responseData['token'];
        return $this;
    }

    /**
     * @param $to
     * @param $message
     * @param string|null $from
     * @param bool $shortenUrls - https://docs.smsportal.com/docs/url-shortening
     * @return mixed
     * @throws \Exception
     */
    public function sendMessage(
        $to,
        $message,
        $from = null,
        $reportUrl = null,
        $shortenUrls = false
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

        if ($shortenUrls) {
            $sendOptions['shortenUrls'] = true;
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

        echo "token={$this->apiToken}";

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
