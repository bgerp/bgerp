<?php

/**
 * Проверки на Web Push криптиране и HTTP отчети без външно изпращане.
 *
 * @category bgerp
 * @package pwa
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
class pwa_tests_WebPushFixture
{
    /** Base64 URL декодиране на синтетични ключове и HTTP полета. */
    private static function decode($value)
    {
        return base64_decode(strtr($value, '-_', '+/'));
    }


    /** HKDF expand за кратките ключове от RFC 8291/8188. */
    private static function expand($key, $info, $length)
    {
        return substr(hash_hmac('sha256', $info . "\x01", $key, true), 0, $length);
    }


    /** Превръща P-256 публичната точка в PEM за независимо OpenSSL декриптиране. */
    private static function publicPem($point)
    {
        $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $point;

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }


    /** Декриптира реалната HTTP заявка независимо от WebPush Encryption. */
    private static function decrypt($request, $privateKey, $publicKey, $authToken, $encoding)
    {
        $body = (string) $request->getBody();
        if ($encoding === 'aes128gcm') {
            $salt = substr($body, 0, 16);
            $keyLength = ord($body[20]);
            $serverKey = substr($body, 21, $keyLength);
            $encrypted = substr($body, 21 + $keyLength);
        } else {
            preg_match('/salt=([^;, ]+)/', $request->getHeaderLine('Encryption'), $saltMatch);
            preg_match('/dh=([^;, ]+)/', $request->getHeaderLine('Crypto-Key'), $keyMatch);
            $salt = self::decode($saltMatch[1]);
            $serverKey = self::decode($keyMatch[1]);
            $encrypted = $body;
        }
        $shared = openssl_pkey_derive(self::publicPem($serverKey), $privateKey, 32);
        $authKey = hash_hmac('sha256', $shared, $authToken, true);
        $info = $encoding === 'aes128gcm' ? "WebPush: info\0" . $publicKey . $serverKey : "Content-Encoding: auth\0";
        $ikm = self::expand($authKey, $info, 32);
        $prk = hash_hmac('sha256', $ikm, $salt, true);
        $context = $encoding === 'aes128gcm' ? '' : "P-256\0" . pack('n', strlen($publicKey)) . $publicKey . pack('n', strlen($serverKey)) . $serverKey;
        $key = self::expand($prk, 'Content-Encoding: ' . $encoding . "\0" . $context, 16);
        $nonce = self::expand($prk, "Content-Encoding: nonce\0" . $context, 12);
        $plain = openssl_decrypt(substr($encrypted, 0, -16), 'aes-128-gcm', $key, OPENSSL_RAW_DATA, $nonce, substr($encrypted, -16));
        if ($plain === false) throw new RuntimeException('AES-GCM authentication failed');
        if ($encoding === 'aes128gcm') {
            $plain = rtrim($plain, "\0");
            if (substr($plain, -1) !== "\x02") throw new RuntimeException('Invalid record delimiter');

            return substr($plain, 0, -1);
        }
        $padding = unpack('nlength', substr($plain, 0, 2));

        return substr($plain, 2 + $padding['length']);
    }


    /** Проверява signature, claims и публичния ключ от VAPID заглавката. */
    private static function verifyVapid($request, $publicKey, $check)
    {
        $header = $request->getHeaderLine('Authorization');
        if (strpos($header, 'vapid ') === 0) {
            preg_match('/t=([^, ]+)/', $header, $match);
            $jwt = $match[1];
            $check(strpos($header, 'k=' . $publicKey) !== false, 'VAPID public key preserved');
        } else {
            $jwt = substr($header, strlen('WebPush '));
            $check(strpos($request->getHeaderLine('Crypto-Key'), 'p256ecdsa=' . $publicKey) !== false, 'Legacy VAPID public key preserved');
        }
        $parts = explode('.', $jwt);
        $claims = json_decode(self::decode($parts[1]), true);
        $check($claims['aud'] === 'https://push.example.invalid' && $claims['sub'] === 'mailto:qa@example.invalid'
            && $claims['exp'] > time() && $claims['exp'] <= time() + 86400, 'VAPID audience, subject and expiration');
        $raw = self::decode($parts[2]);
        $der = '';
        foreach (array(substr($raw, 0, 32), substr($raw, 32)) as $part) {
            $part = ltrim($part, "\0");
            if ($part === '' || (ord($part[0]) & 128)) $part = "\0" . $part;
            $der .= "\x02" . chr(strlen($part)) . $part;
        }
        $der = "\x30" . chr(strlen($der)) . $der;
        $check(openssl_verify($parts[0] . '.' . $parts[1], $der, self::publicPem(self::decode($publicKey)), OPENSSL_ALGO_SHA256) === 1, 'VAPID ES256 signature');
    }


    /** Изпълнява криптиране, декриптиране и отчетите с Guzzle MockHandler. */
    public static function run($check)
    {
        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
        $check(strlen(self::decode($keys['publicKey'])) === 65 && strlen(self::decode($keys['privateKey'])) === 32, 'Existing VAPID key format');
        $auth = array('VAPID' => array('subject' => 'mailto:qa@example.invalid') + $keys);
        $clientKey = openssl_pkey_new(array('private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1'));
        $details = openssl_pkey_get_details($clientKey);
        $clientPublic = "\x04" . str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT) . str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);
        $clientAuth = random_bytes(16);
        $encode = function ($value) { return rtrim(strtr(base64_encode($value), '+/', '-_'), '='); };
        $payloadMethod = new ReflectionMethod('pwa_PushSubscriptions', 'getPayload');
        $payloadMethod->setAccessible(true);
        $data = (object) array('title' => 'Тест Аида', 'text' => str_repeat('Кирилица 🙂 ', 1000), 'url' => '/test?fpn=1', 'sound' => true, 'vibration' => array(100, 50, 100));
        $payload = $payloadMethod->invoke(null, $data);
        $decoded = json_decode($payload, true);
        $check(strlen($payload) <= \Minishlink\WebPush\Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH && json_last_error() === JSON_ERROR_NONE, 'Long UTF-8 payload fits existing limit');
        $check($decoded['title'] === 'Тест Аида' && $decoded['url'] === '/test?fpn=1' && $decoded['sound'] === true && $decoded['vibration'] === array(100, 50, 100), 'Notification fields preserved');
        foreach (array('aes128gcm', 'aesgcm') as $encoding) {
            $history = array();
            $statuses = array(201, 400, 403, 404, 410, 429, 500);
            $responses = array();
            foreach ($statuses as $status) $responses[] = new \GuzzleHttp\Psr7\Response($status);
            $stack = \GuzzleHttp\HandlerStack::create(new \GuzzleHttp\Handler\MockHandler($responses));
            $stack->push(\GuzzleHttp\Middleware::history($history));
            $push = pwa_PushSubscriptions::createWebPush($auth);
            $property = new ReflectionProperty($push, 'client');
            $property->setAccessible(true);
            $property->setValue($push, new \GuzzleHttp\Client(array('handler' => $stack)));
            $subscription = \Minishlink\WebPush\Subscription::create(array('endpoint' => 'https://push.example.invalid/subscription',
                'publicKey' => $encode($clientPublic), 'authToken' => $encode($clientAuth), 'contentEncoding' => $encoding));
            $check($subscription->getContentEncoding() === $encoding, 'Existing subscription encoding ' . $encoding);
            foreach ($statuses as $status) {
                $report = $push->sendOneNotification($subscription, $payload, array('TTL' => 3600));
                $check($report->isSuccess() === ($status === 201)
                    && $report->isSubscriptionExpired() === in_array($status, array(404, 410), true), 'Report ' . $encoding . '/' . $status);
            }
            $request = $history[0]['request'];
            $check($request->getHeaderLine('TTL') === '3600' && $request->getHeaderLine('Content-Encoding') === $encoding, 'TTL and content encoding headers');
            $check(self::decrypt($request, $clientKey, $clientPublic, $clientAuth, $encoding) === $payload, 'Independent OpenSSL decryption ' . $encoding);
            self::verifyVapid($request, $keys['publicKey'], $check);
            $check(count($history) === count($statuses), 'One mock HTTP request per report');
        }
    }
}
