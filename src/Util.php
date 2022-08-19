<?php
declare(strict_types=1);

namespace Lemenio\SmsApi;

class Util
{

    public static function getRandomHex(int $numBytes = 4): string
    {
        return bin2hex(random_bytes($numBytes));
    }

    public static function decodeHeader(string $header): array
    {
        $joinedArgs = \explode(', ', $header);

        $args = [];

        foreach ($joinedArgs as $joinedArg) {
            list($key, $value) = \explode('=', $joinedArg);

            if (empty($value)) {
                continue;
            }

            if ($value[0] === '"' && $value[\strlen($value) - 1] == '"') {
                $value = \mb_substr($value, 1, \strlen($value) - 2);
            }

            $args[$key] = $value;
        }

        return $args;
    }

    public static function encodeHeader(array $header): string
    {
        $headerArray = [];

        foreach ($header as $key => $value) {
            $headerArray[] = $key . '=' . $value . '';
        }

        return \implode(', ', $headerArray);
    }

    public static function getValidationHash(
        $username = 'test',
        $password = 'test',
        $realm = "Access to the '/' path",
        $method = 'POST',
        $uri = '/login',
        $nonce = '4773e3acc434452c'
    ): string
    {

        $a1 = \md5(\implode(':', [
            $username,
            $realm,
            $password
        ]));
        $a2 = \md5(implode(':', [
            $method,
            $uri
        ]));

        return \md5(\implode(':', [
            $a1,
            $nonce,
            $a2
        ]));
    }
}