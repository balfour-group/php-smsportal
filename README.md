# php-smsportal

An API client for sending SMS via the [SmsPortal](https://smsportal.com/) API.

## Installation

```bash
composer require balfour/php-smsportal
```

## Usage

```php
use Balfour\SmsPortal\SmsPortalClient;
use GuzzleHttp\Client;

$guzzle = new Client();
$client = new SmsPortalClient(
    $guzzle,
    null, // PSR-6 CacheItemPoolInterface
    '[your client id]',
    '[your secret]'
);

$resp = $client->sendMessage(
    '+27000000000',
    'This is a test message.'
);
```

If you pass an implementation of a PSR-6 CacheItemPoolInterface, the authentication token will be cached and used
for subsequent requests.
