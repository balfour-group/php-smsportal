# php-smsportal

An API client for sending SMS via the [SmsPortal](https://smsportal.com/) API

## Installation

```bash
composer require balfour/php-smsportal
```

## PHPUnit

```bash
phpunit Tests
```

## Usage

```php
$client = new \Balfour\SmsPortal\SmsPortalClient(
            'api_client_id',
            'api_client_secret'
        );

$resp = $client->sendMessage(
    '+27000000000',
    'This is a test message.'
);
```

