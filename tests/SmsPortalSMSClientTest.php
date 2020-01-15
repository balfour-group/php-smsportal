<?php

namespace Tests;

use Balfour\SmsPortal\SmsPortalClient;
use PHPUnit\Framework\TestCase;

function arr_to_string($arr) {
    if (true) { //is_object($arr)
        ob_start();
        //var_export($arr);
        var_dump($arr);
        $str = ob_get_contents();
        ob_end_clean();

        return $str;
    } else { //prolly a string
        return $arr;
    }
}

class SmsPortalSMSClientTest extends TestCase
{
    public function testSendMessage()
    {
        $client = new SmsPortalClient(
            'https://rest.smsportal.com/v1/',
            '8f272f09-7cd6-4c0a-b352-1513f7491764',
            '47CHNduGxoDKslW1opwFDwqGBFBPrCqL'
        );

        // No valid messages: 0861448687
        $resp = $client->sendMessage('0610624165', 'Hello SmsPortal world 2');

        echo arr_to_string($resp);
    }
}
