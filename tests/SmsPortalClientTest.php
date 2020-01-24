<?php

namespace Tests;

use Balfour\SmsPortal\SmsPortalClient;
use GuzzleHttp\Client;
use Mockery;
use PHPUnit\Framework\TestCase;

class SmsPortalClientTest extends TestCase
{
    public function testSetBaseRestUri()
    {
        $client = new SmsPortalClient(
            new Client,
            'api_client_id',
            'api_client_secret'
        );
        $this->assertEquals('https://rest.smsportal.com/v1/', $client->getUri());
        $client->setUri('new_base_rest_uri');
        $this->assertEquals('new_base_rest_uri', $client->getUri());
    }

    public function testSetApiClientId()
    {
        $client = new SmsPortalClient(
            new Client,
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
            new Client,
            'api_client_id',
            'api_client_secret'
        );
        $this->assertEquals('api_client_secret', $client->getApiClientSecret());
        $client->setApiClientSecret('new_api_client_secret');
        $this->assertEquals('new_api_client_secret', $client->getApiClientSecret());
    }

    public function testSendMessage()
    {
        $guzzleClient = Mockery::mock(Client::class);
        $client = Mockery::mock(SmsPortalClient::class . '[post]', [$guzzleClient]);

        $client->shouldReceive('post')
            ->withArgs([
                'BulkMessages',
                [
                    'messages' => [
                        [
                            'destination' => '+27000000000',
                            'content' => 'This is my message',
                        ]
                    ]
                ]
            ])
            ->andReturn(['cost' => 1])
            ->once();

        $resp = $client->sendMessage('+27000000000', 'This is my message');

        $this->assertEquals(1, $resp['cost']);
    }
}
