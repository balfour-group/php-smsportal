<?php

namespace Tests;

use Balfour\SmsPortal\SmsPortalClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class SmsPortalClientTest extends TestCase
{
    public function testGetClient()
    {
        $guzzle = Mockery::mock(Client::class);
        $cache = Mockery::mock(CacheItemPoolInterface::class);
        $client = new SmsPortalClient($guzzle, $cache, '123', 'secret');
        $this->assertSame($guzzle, $client->getClient());
    }

    public function testGetCache()
    {
        $guzzle = Mockery::mock(Client::class);

        $client = new SmsPortalClient($guzzle, null, '123', 'secret');
        $this->assertNull($client->getCache());

        $cache = Mockery::mock(CacheItemPoolInterface::class);
        $client = new SmsPortalClient($guzzle, $cache, '123', 'secret');
        $this->assertSame($cache, $client->getCache());

        $cache2 = Mockery::mock(CacheItemPoolInterface::class);
        $client->setCache($cache2);
        $this->assertSame($cache2, $client->getCache());
    }

    public function testGetUri()
    {
        $guzzle = Mockery::mock(Client::class);

        $client = new SmsPortalClient($guzzle, null, '123', 'secret');
        $this->assertEquals('https://rest.smsportal.com/v1/', $client->getUri());

        $client->setUri('https://foo.bar');
        $this->assertEquals('https://foo.bar', $client->getUri());
    }

    public function testGetClientID()
    {
        $guzzle = Mockery::mock(Client::class);

        $client = new SmsPortalClient($guzzle, null, '123', 'secret');
        $this->assertEquals('123', $client->getClientID());

        $client->setClientID('moo');
        $this->assertEquals('moo', $client->getClientID());
    }

    public function testGetSecret()
    {
        $guzzle = Mockery::mock(Client::class);

        $client = new SmsPortalClient($guzzle, null, '123', 'secret');
        $this->assertEquals('secret', $client->getSecret());

        $client->setSecret('lalala');
        $this->assertEquals('lalala', $client->getSecret());
    }

    public function testGet()
    {
        $response = ['foo' => 'bar'];

        $request = new Request(
            'GET',
            'https://rest.smsportal.com/v1/messages'
        );

        $guzzle = Mockery::mock(Client::class);
        $guzzle->shouldReceive('send');

        $client = Mockery::mock(
            '\Balfour\SmsPortal\SmsPortalClient[createRequest,sendRequest,authorize]',
            [
                $guzzle,
                null,
                '123',
                'secret'
            ]
        );
        $client->shouldAllowMockingProtectedMethods();

        $client->shouldReceive('createRequest')
            ->withArgs([
                'GET',
                'messages',
                [],
            ])
            ->once()
            ->andReturn($request);
        $client->shouldReceive('sendRequest')
            ->with($request)
            ->once()
            ->andReturn($response);
        $client->shouldReceive('authorize')
            ->once();

        $data = $client->get('messages');
        $this->assertSame($response, $data);
    }

    public function testGetWithQueryString()
    {
        $response = ['foo' => 'bar'];

        $request = new Request(
            'GET',
            'https://rest.smsportal.com/v1/messages?status=draft'
        );

        $guzzle = Mockery::mock(Client::class);
        $guzzle->shouldReceive('send');

        $client = Mockery::mock(
            '\Balfour\SmsPortal\SmsPortalClient[createRequest,sendRequest,authorize]',
            [
                $guzzle,
                null,
                '123',
                'secret'
            ]
        );
        $client->shouldAllowMockingProtectedMethods();

        $client->shouldReceive('createRequest')
            ->withArgs([
                'GET',
                'messages',
                ['status' => 'draft'],
            ])
            ->once()
            ->andReturn($request);
        $client->shouldReceive('sendRequest')
            ->with($request)
            ->once()
            ->andReturn($response);
        $client->shouldReceive('authorize')
            ->once();

        $data = $client->get('messages', ['status' => 'draft']);
        $this->assertSame($response, $data);
    }

    public function testPost()
    {
        $response = ['foo' => 'bar'];

        $request = new Request(
            'POST',
            'https://rest.smsportal.com/v1/messages'
        );

        $guzzle = Mockery::mock(Client::class);
        $guzzle->shouldReceive('send');

        $client = Mockery::mock(
            '\Balfour\SmsPortal\SmsPortalClient[createRequest,sendRequest,authorize]',
            [
                $guzzle,
                null,
                '123',
                'secret'
            ]
        );
        $client->shouldAllowMockingProtectedMethods();

        $client->shouldReceive('createRequest')
            ->withArgs([
                'POST',
                'messages',
                [],
                ['hello' => 'world'],
            ])
            ->once()
            ->andReturn($request);
        $client->shouldReceive('sendRequest')
            ->with($request)
            ->once()
            ->andReturn($response);
        $client->shouldReceive('authorize')
            ->once();

        $data = $client->post('messages', ['hello' => 'world']);
        $this->assertSame($response, $data);
    }

    public function testSendRequest()
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')
            ->andReturn('{"hello":"world"}');

        $guzzle = Mockery::mock(Client::class);
        $guzzle->shouldReceive('send')
            ->withArgs([
                Mockery::on(function ($argument) {
                    return $argument instanceof RequestInterface
                        && $argument->getMethod() === 'GET'
                        && (string) $argument->getUri() === 'https://rest.smsportal.com/v1/messages'
                        && $argument->getHeaderLine('Accept') === 'application/json'
                        && $argument->getHeaderLine('Content-Type') === 'application/json'
                        && $argument->getHeaderLine('Authorization') === 'Bearer my_api_token';
                }),
                [
                    'connect_timeout' => 2000,
                    'timeout' => 6000,
                ]
            ])
            ->once()
            ->andReturn($response);

        $client = Mockery::mock(
            '\Balfour\SmsPortal\SmsPortalClient[authorize]',
            [
                $guzzle,
                null,
                '123',
                'secret'
            ]
        );
        $client->shouldAllowMockingProtectedMethods();

        $client->shouldReceive('authorize')
            ->once();

        $client->setAPIToken([
            'token' => 'my_api_token',
        ]);

        $data = $client->get('messages');
        $this->assertEquals(['hello' => 'world'], $data);
    }

    public function testGetAuthorizationToken()
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')
            ->andReturn('{"token":"my_api_token","schema":"JWT","expiresInMinutes":1440}');

        $guzzle = Mockery::mock(Client::class);
        $guzzle->shouldReceive('send')
            ->withArgs([
                Mockery::on(function ($argument) {
                    return $argument instanceof RequestInterface
                        && $argument->getMethod() === 'GET'
                        && (string) $argument->getUri() === 'https://rest.smsportal.com/v1/Authentication'
                        && $argument->getHeaderLine('Accept') === 'application/json'
                        && $argument->getHeaderLine('Content-Type') === 'application/json';
                }),
                [
                    'auth' => [
                        '123',
                        'secret',
                    ],
                    'connect_timeout' => 2000,
                    'timeout' => 6000,
                ]
            ])
            ->andReturn($response);

        $client = new SmsPortalClient($guzzle, null, '123', 'secret');
        $apiToken = $client->getAuthorizationToken();
        $this->assertArrayHasKey('token', $apiToken);
        $this->assertArrayHasKey('schema', $apiToken);
        $this->assertArrayHasKey('expiresInMinutes', $apiToken);
        $this->assertArrayHasKey('expiresAt', $apiToken);
        $this->assertEquals('my_api_token', $apiToken['token']);
        $this->assertEquals('JWT', $apiToken['schema']);
        $this->assertEquals(1440, $apiToken['expiresInMinutes']);
    }

    public function testSendMessage()
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')
            ->andReturn('{"hello":"world"}');

        $guzzle = Mockery::mock(Client::class);
        $guzzle->shouldReceive('send')
            ->withArgs([
                Mockery::on(function ($argument) {
                    return $argument instanceof RequestInterface
                        && $argument->getMethod() === 'POST'
                        && (string) $argument->getUri() === 'https://rest.smsportal.com/v1/BulkMessages'
                        && $argument->getHeaderLine('Accept') === 'application/json'
                        && $argument->getHeaderLine('Content-Type') === 'application/json'
                        && $argument->getHeaderLine('Authorization') === 'Bearer my_api_token'
                        && (string) $argument->getBody() === '{"messages":[{"destination":"+27000000000","content":"This is a test message."}]}';
                }),
                [
                    'connect_timeout' => 2000,
                    'timeout' => 6000,
                ]
            ])
            ->andReturn($response);

        $client = Mockery::mock(
            '\Balfour\SmsPortal\SmsPortalClient[authorize]',
            [
                $guzzle,
                null,
                '123',
                'secret'
            ]
        );
        $client->shouldAllowMockingProtectedMethods();

        $client->shouldReceive('authorize')
            ->once();

        $client->setAPIToken([
            'token' => 'my_api_token',
        ]);

        $data = $client->sendMessage('+27000000000', 'This is a test message.');
        $this->assertEquals(['hello' => 'world'], $data);
    }

    public function testSendMessageWithFromNumber()
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')
            ->andReturn('{"hello":"world"}');

        $guzzle = Mockery::mock(Client::class);
        $guzzle->shouldReceive('send')
            ->withArgs([
                Mockery::on(function ($argument) {
                    return $argument instanceof RequestInterface
                        && $argument->getMethod() === 'POST'
                        && (string) $argument->getUri() === 'https://rest.smsportal.com/v1/BulkMessages'
                        && $argument->getHeaderLine('Accept') === 'application/json'
                        && $argument->getHeaderLine('Content-Type') === 'application/json'
                        && $argument->getHeaderLine('Authorization') === 'Bearer my_api_token'
                        && (string) $argument->getBody() === '{"messages":[{"destination":"+27000000000","content":"This is a test message."}],"SendOptions":{"senderId":"+27111111111"}}';
                }),
                [
                    'connect_timeout' => 2000,
                    'timeout' => 6000,
                ]
            ])
            ->andReturn($response);

        $client = Mockery::mock(
            '\Balfour\SmsPortal\SmsPortalClient[authorize]',
            [
                $guzzle,
                null,
                '123',
                'secret'
            ]
        );
        $client->shouldAllowMockingProtectedMethods();

        $client->shouldReceive('authorize')
            ->once();

        $client->setAPIToken([
            'token' => 'my_api_token',
        ]);

        $data = $client->sendMessage('+27000000000', 'This is a test message.', '+27111111111');
        $this->assertEquals(['hello' => 'world'], $data);
    }
}
