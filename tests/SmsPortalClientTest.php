<?php

namespace Tests;

use Balfour\SmsPortal\SmsPortalClient;
use Mockery;
use PHPUnit\Framework\TestCase;

class SmsPortalClientTest extends TestCase
{
    public function testSetBaseRestUri()
    {
        $client = new SmsPortalClient(
            'api_client_id',
            'api_client_secret'
        );
        $this->assertEquals('https://rest.smsportal.com/v1/', $client->getBaseRestUri());
        $client->setBaseRestUri('new_base_rest_uri');
        $this->assertEquals('new_base_rest_uri', $client->getBaseRestUri());
    }

    public function testSetApiClientId()
    {
        $client = new SmsPortalClient(
            'api_client_id',
            'api_client_secret'
        );
        $this->assertEquals('api_client_id', $client->getApiClientId());
        $client->setApiClientId('new_api_client_id');
        $this->assertEquals('new_api_client_id', $client->getApiClientId());
    }

    public function testSetApiClientSecret()
    {
        $client = new SmsPortalClient(
            'api_client_id',
            'api_client_secret'
        );
        $this->assertEquals('api_client_secret', $client->getApiClientSecret());
        $client->setApiClientSecret('new_api_client_secret');
        $this->assertEquals('new_api_client_secret', $client->getApiClientSecret());
    }

    public function testSendMessage()
    {
        $client = new SmsPortalClient(
            '8f272f09-7cd6-4c0a-b352-1513f7491764',
            '47CHNduGxoDKslW1opwFDwqGBFBPrCqL'
        );

        $resp = $client->sendMessage(
            '+27000000000',
            'Hello this is my message'
        );

        $this->assertEquals(1, $resp['cost']);

        //echo print_r($resp, true);
    }
}
