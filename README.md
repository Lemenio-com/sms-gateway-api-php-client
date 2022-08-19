## Installation

Insert following repository into composer.json

```
"repositories": [
    {
        "type": "vcs",
        "url": "https://vcs.lemenio.com/Lemenio/sms-gateway-api-php-client.git"
    }
]
```

Run command

```shell
$ composer require lemenio/sms-api-client
```

## Usage

```php
//client config
$config = [
    'username' => 'test',
    'password' => 'test',
    'uri' => 'localhost',
    'port' => 8080
];

//client initialization
$client = new \Lemenio\SmsApi\SmsApiClient(
    $config['username'],
    $config['password'],
    $config['uri'],
    $config['port']
);

//sending messages
$client->sendMessage(
    '123456789', //phone number
    'Test message' //message
);
```