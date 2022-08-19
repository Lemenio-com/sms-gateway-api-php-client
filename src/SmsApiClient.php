<?php
declare(strict_types=1);

namespace Lemenio\SmsApi;

class SmsApiClient
{

    const AUTHORIZATION_HEADER = 'Authorization';
    const AUTHENTICATE_HEADER = 'WWW-Authenticate';

    const LOGIN_URI = '/login';

    private $url;

    private $username;
    private $password;
    private $port;

    private $requestCounter = 1;
    private $clientNonce;

    private $authToken = null;

    /** @var \GuzzleHttp\Client */
    private $client;

    public function __construct(
        string $url,
        string $username,
        string $password,
        int    $port
    )
    {
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;

        $this->clientNonce = Util::getRandomHex(8);

        $this->client = new \GuzzleHttp\Client([
            'base_uri' => $this->url . ':' . $this->port
        ]);
    }

    private function refreshToken()
    {
        $authenticateHeader = null;

        try {
            $request = new \GuzzleHttp\Psr7\Request("POST", self::LOGIN_URI);
            $this->client->send($request);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();

            if ($response->getStatusCode() !== 401) {
                throw $e;
            }

            $authenticateHeader = Util::decodeHeader($response->getHeader(self::AUTHENTICATE_HEADER)[0]);
        }

        if ($authenticateHeader === null) {
            throw \GuzzleHttp\Exception\BadResponseException::create($request, $response);
        }

        $nonceCount = $this->requestCounter++;

        unset($authenticateHeader['algorithm']);

        $authenticateHeader['cnonce'] = $this->clientNonce;
        $authenticateHeader['uri'] = self::LOGIN_URI;
        $authenticateHeader['username'] = $this->username;
        $authenticateHeader['nc'] = $nonceCount;

        $token = Util::getValidationHash(
            $this->username,
            $this->password,
            $authenticateHeader['Digest realm'],
            $request->getMethod(),
            self::LOGIN_URI,
            $authenticateHeader['nonce']
        );

        $authenticateHeader['response'] = $token;

        $authenticateHeader['Digest realm'] = '"' . $authenticateHeader['Digest realm'] . '"';
        $authenticateHeader['uri'] = '"' . $authenticateHeader['uri'] . '"';

        $authRequest = new \GuzzleHttp\Psr7\Request('POST', self::LOGIN_URI, [
            self::AUTHORIZATION_HEADER => Util::encodeHeader($authenticateHeader),
        ]);

        try {
            $response = $this->client->send($authRequest);

            $this->authToken = $response->getBody()->getContents();
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();

            throw $e;
        }
    }

    public function sendMessage(
        string $phoneNumber,
        string $message
    )
    {
        if ($this->authToken === null) {
            $this->refreshToken();
        }

        $makeRequest = function (bool $retry = false) use ($phoneNumber, $message): bool {
            $request = new \GuzzleHttp\Psr7\Request('POST', '/send', [
                self::AUTHORIZATION_HEADER => 'Bearer ' . $this->authToken,
                'Content-Type' => 'application/json'
            ],
                \json_encode([
                    'number' => $phoneNumber,
                    'message' => $message,
                ])
            );

            try {
                $this->client->send($request);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $response = $e->getResponse();

                if ($response->getStatusCode() !== 401 && !$retry) {
                    throw $e;
                }

                return false;
            }

            return true;
        };

        if (!$makeRequest()) {
            $this->refreshToken();
            $makeRequest(true);
        }
    }
}