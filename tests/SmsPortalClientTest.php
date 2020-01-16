<?php

namespace Tests;

use Balfour\SmsPortal\SmsPortalClient;
use PHPUnit\Framework\TestCase;

class SmsPortalClientTest extends TestCase
{
    public function testSendMessage()
    {
        $client = new SmsPortalClient(
            '8f272f09-7cd6-4c0a-b352-1513f7491764',
            '47CHNduGxoDKslW1opwFDwqGBFBPrCqL'
        );

        $resp = $client->sendMessage(
            '0610624165',
            'This is my message http://masterstart.com',
            null,
            //todo: input ngrok url here
        );

        echo json_encode($resp);
    }
}
