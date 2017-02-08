<?php

namespace app\helpers;

use Yii;

class Token
{

    /**
     * When checking nbf, iat or expiration times,
     * we want to provide some extra leeway time to
     * account for clock skew.
     */
    public static $leeway = 0;

    public static $supported_algs = array(
        'HS256' => array('hash_hmac', 'SHA256'),
        'HS512' => array('hash_hmac', 'SHA512'),
        'HS384' => array('hash_hmac', 'SHA384'),
        'RS256' => array('openssl', 'SHA256'),
    );

    /**
     * Decodes a JWT string into a PHP object.
     *
     * @param string $jwt The JWT
     * @param string|array|null $key The key, or map of keys.
     *                                          If the algorithm used is asymmetric, this is the public key
     * @param array $allowed_algs List of supported verification algorithms
     *                                          Supported algorithms are 'HS256', 'HS384', 'HS512' and 'RS256'
     *
     * @return object The JWT's payload as a PHP object
     *
     * @throws DomainException              Algorithm was not provided
     * @throws UnexpectedValueException     Provided JWT was invalid
     * @throws SignatureInvalidException    Provided JWT was invalid because the signature verification failed
     * @throws BeforeValidException         Provided JWT is trying to be used before it's eligible as defined by 'nbf'
     * @throws BeforeValidException         Provided JWT is trying to be used before it's been created as defined by 'iat'
     * @throws ExpiredException             Provided JWT has since expired, as defined by the 'exp' claim
     *
     * @uses jsonDecode
     * @uses urlsafeB64Decode
     */
    public static function decode($jwt)
    {
        $key = Yii::$app->params['TOKEN_SECRET'];
        $allowed_algs = [Yii::$app->params['TOKEN_ALG']];

        if (empty($key)) {
            return false;
        }
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            return false;
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        if (null === ($header = self::jsonDecode(self::urlsafeB64Decode($headb64)))) {
            return false;
        }
        if (null === $payload = self::jsonDecode(self::urlsafeB64Decode($bodyb64))) {
            return false;
        }
        $sig = self::urlsafeB64Decode($cryptob64);

        if (empty($header->alg)) {
            return false;
        }
        if (empty(self::$supported_algs[$header->alg])) {
            return false;
        }
        if (!is_array($allowed_algs) || !in_array($header->alg, $allowed_algs)) {
            return false;
        }
        if (is_array($key) || $key instanceof \ArrayAccess) {
            if (isset($header->kid)) {
                $key = $key[$header->kid];
            } else {
                return false;
            }
        }

        // Check the signature
        if (!self::verify("$headb64.$bodyb64", $sig, $key, $header->alg)) {
            return false;
        }

        // Check if the nbf if it is defined. This is the time that the
        // token can actually be used. If it's not yet that time, abort.
        if (isset($payload->nbf) && $payload->nbf > (time() + self::$leeway)) {
            return false;
        }

        // Check that this token has been created before 'now'. This prevents
        // using tokens that have been created for later use (and haven't
        // correctly used the nbf claim).
        if (isset($payload->iat) && $payload->iat > (time() + self::$leeway)) {
            return false;
        }

        // Check if this token has expired.
        if (isset($payload->exp) && (time() - self::$leeway) >= $payload->exp) {
            return 10002;
        }

        if (isset($payload->uid)) {
            if (!self::verifyPlatform($payload->uid)) {
                return false;
            }
        }

        return $payload;
    }

    public static function authorization()
    {
        $headers = Yii::$app->request->headers;
        $token = $headers->get('X-' . Yii::$app->params['name'] . '-Authorization');
        // Log::debug('Authorization', ['token' => $token]);
        if ($payload = self::decode($token)) {
            // Log::debug('payload', ['payload' => $payload]);
            if (is_object($payload) && property_exists($payload, 'uid')) {
                return $payload->uid;
            }
        }

        if ($payload == 10002) {
            return 'token-expired';
        }

        return false;
    }

    public static function refresh()
    {
        $headers = Yii::$app->request->headers;
        $token = $headers->get('X-' . Yii::$app->params['name'] . '-Authorization');

        if ($token) {
            if ($payload = self::decode($token)) {

                if (is_object($payload)) {

                    // 超过1天
                    if (property_exists($payload, 'exp')) {
                        if ((time() + Yii::$app->params['TOKEN_TTL'] * 60 - $payload->exp) > Yii::$app->params['TOKEN_REFRESH_TTL'] * 60) {
                            return self::new_token($payload);
                        }
                    }

                    // 版本号不匹配
                    if (property_exists($payload, 'ver')) {
                        if (version_compare(Yii::$app->params['TOKEN_VER'], $payload->ver) != 0) {
                            return self::new_token($payload);
                        }
                    }

                    // 没有版本号
                    if (!property_exists($payload, 'ver')) {
                        return self::new_token($payload);
                    }
                }
            }
        }

        return false;

    }

    private static function new_token($payload)
    {
        return self::encode([
            'uid' => $payload->uid,
            'ver' => Yii::$app->params['TOKEN_VER']
        ]);
    }

    private static function str_mix($domain, $uuid)
    {
        $uuid = explode('-', $uuid);
        $domain = explode('.', $domain);
        $mixed = array_merge($uuid, $domain);
        arsort($mixed);
        return implode('-', $mixed);
    }

    private static function parse_domain($url)
    {
        $data = parse_url($url);
        $host = $data['host'];

        if (preg_match('/^www.*$/', $host)) {
            return str_replace('www.', '', $host);
        }

        return $host;
    }

    /**
     * Converts and signs a PHP object or array into a JWT string.
     *
     * @param object|array $payload PHP object or array
     * @param string $key The secret key.
     *                                  If the algorithm used is asymmetric, this is the private key
     * @param string $alg The signing algorithm.
     *                                  Supported algorithms are 'HS256', 'HS384', 'HS512' and 'RS256'
     * @param array $head An array with header elements to attach
     *
     * @return string A signed JWT
     *
     * @uses jsonEncode
     * @uses urlsafeB64Encode
     */
    public static function encode($payload, $keyId = null, $head = null)
    {
        $key = Yii::$app->params['TOKEN_SECRET'];
        $alg = Yii::$app->params['TOKEN_ALG'];

        if (!isset($payload['exp'])) {
            $payload['exp'] = time() + Yii::$app->params['TOKEN_TTL'] * 60;
        }

        if (isset($payload['uid'])) {
            $payload['platform'] = self::setPlatform($payload['uid']);
        }

        $header = array('typ' => 'JWT', 'alg' => $alg);
        if ($keyId !== null) {
            $header['kid'] = $keyId;
        }
        if (isset($head) && is_array($head)) {
            $header = array_merge($head, $header);
        }
        $segments = array();
        $segments[] = self::urlsafeB64Encode(self::jsonEncode($header));
        $segments[] = self::urlsafeB64Encode(self::jsonEncode($payload));
        $signing_input = implode('.', $segments);

        $signature = self::sign($signing_input, $key, $alg);
        $segments[] = self::urlsafeB64Encode($signature);

        return implode('.', $segments);
    }

    /**
     * Sign a string with a given key and algorithm.
     *
     * @param string $msg The message to sign
     * @param string|resource $key The secret key
     * @param string $alg The signing algorithm.
     *                                  Supported algorithms are 'HS256', 'HS384', 'HS512' and 'RS256'
     *
     * @return string An encrypted message
     *
     * @throws DomainException Unsupported algorithm was specified
     */
    public static function sign($msg, $key, $alg = 'HS256')
    {
        if (empty(self::$supported_algs[$alg])) {
            return false;
        }
        list($function, $algorithm) = self::$supported_algs[$alg];
        switch ($function) {
            case 'hash_hmac':
                return hash_hmac($algorithm, $msg, $key, true);
            case 'openssl':
                $signature = '';
                $success = openssl_sign($msg, $signature, $key, $algorithm);
                if (!$success) {
                    return false;
                } else {
                    return $signature;
                }
        }
    }

    /**
     * Verify a signature with the message, key and method. Not all methods
     * are symmetric, so we must have a separate verify and sign method.
     *
     * @param string $msg The original message (header and body)
     * @param string $signature The original signature
     * @param string|resource $key For HS*, a string key works. for RS*, must be a resource of an openssl public key
     * @param string $alg The algorithm
     *
     * @return bool
     *
     * @throws DomainException Invalid Algorithm or OpenSSL failure
     */
    private static function verify($msg, $signature, $key, $alg)
    {
        if (empty(self::$supported_algs[$alg])) {
            return false;
        }

        list($function, $algorithm) = self::$supported_algs[$alg];
        switch ($function) {
            case 'openssl':
                $success = openssl_verify($msg, $signature, $key, $algorithm);
                if (!$success) {
                    return false;
                } else {
                    return $signature;
                }
            case 'hash_hmac':
            default:
                $hash = hash_hmac($algorithm, $msg, $key, true);
                if (function_exists('hash_equals')) {
                    return hash_equals($signature, $hash);
                }
                $len = min(self::safeStrlen($signature), self::safeStrlen($hash));

                $status = 0;
                for ($i = 0; $i < $len; $i++) {
                    $status |= (ord($signature[$i]) ^ ord($hash[$i]));
                }
                $status |= (self::safeStrlen($signature) ^ self::safeStrlen($hash));

                return ($status === 0);
        }
    }

    /**
     * Decode a JSON string into a PHP object.
     *
     * @param string $input JSON string
     *
     * @return object Object representation of JSON string
     *
     * @throws DomainException Provided string was invalid JSON
     */
    public static function jsonDecode($input)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=') && !(defined('JSON_C_VERSION') && PHP_INT_SIZE > 4)) {
            /** In PHP >=5.4.0, json_decode() accepts an options parameter, that allows you
             * to specify that large ints (like Steam Transaction IDs) should be treated as
             * strings, rather than the PHP default behaviour of converting them to floats.
             */
            $obj = json_decode($input, false, 512, JSON_BIGINT_AS_STRING);
        } else {
            /** Not all servers will support that, however, so for older versions we must
             * manually detect large ints in the JSON string and quote them (thus converting
             *them to strings) before decoding, hence the preg_replace() call.
             */
            $max_int_length = strlen((string)PHP_INT_MAX) - 1;
            $json_without_bigints = preg_replace('/:\s*(-?\d{' . $max_int_length . ',})/', ': "$1"', $input);
            $obj = json_decode($json_without_bigints);
        }

        if (function_exists('json_last_error') && $errno = json_last_error()) {
            self::handleJsonError($errno);
        } elseif ($obj === null && $input !== 'null') {
            return false;
        }
        return $obj;
    }

    /**
     * Encode a PHP object into a JSON string.
     *
     * @param object|array $input A PHP object or array
     *
     * @return string JSON representation of the PHP object or array
     *
     * @throws DomainException Provided object could not be encoded to valid JSON
     */
    public static function jsonEncode($input)
    {
        $json = json_encode($input);
        if (function_exists('json_last_error') && $errno = json_last_error()) {
            self::handleJsonError($errno);
        } elseif ($json === 'null' && $input !== null) {
            return false;
        }
        return $json;
    }

    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     */
    public static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     */
    public static function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * Helper method to create a JSON error.
     *
     * @param int $errno An error number from json_last_error()
     *
     * @return void
     */
    private static function handleJsonError($errno)
    {
        $messages = array(
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON'
        );
        return false;
    }

    /**
     * Get the number of bytes in cryptographic strings.
     *
     * @param string
     *
     * @return int
     */
    private static function safeStrlen($str)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, '8bit');
        }
        return strlen($str);
    }

    private static function setPlatform($uid)
    {
        $platform = Header::getUserAgent('Platform');
        $key = "platform:{$uid}";
        // cache
        Yii::$app->cache->set($key, $platform, 0);
        return $platform;
    }

    private static function verifyPlatform($uid)
    {
        return true;
        $platform = Header::getUserAgent('Platform');

        $key = "platform:{$uid}";

        if ($platform == Yii::$app->cache->get($key)) {
            return true;
        }

        return false;
    }
}
