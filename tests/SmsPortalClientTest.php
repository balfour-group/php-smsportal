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
            '+27610624166',
            'Hello this is my message'
        );

        echo json_encode($resp);
    }
}
