<?php

/**
 * S3 регресия с реалния SDK и синтетичен HTTP транспорт, без външни заявки.
 *
 * @category bgerp
 * @package backup
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
class backup_tests_S3Fixture
{
    /** Проверява serialize/sign/parse и реалните методи на backup_Amazon. */
    public static function run($client, $check)
    {
        $objects = array();
        $requests = array();
        $forbidden = false;
        $transport = function ($request, $options) use (&$objects, &$requests, &$forbidden) {
            $key = rawurldecode(ltrim($request->getUri()->getPath(), '/'));
            $method = $request->getMethod();
            $requests[] = array($method, $key, $request->getHeaderLine('Authorization'));
            if ($forbidden || ($method === 'GET' && !array_key_exists($key, $objects))) {
                $status = $forbidden ? 403 : 404;
                $code = $forbidden ? 'AccessDenied' : 'NoSuchKey';
                return new \GuzzleHttp\Promise\RejectedPromise(array(
                    'exception' => new RuntimeException($code),
                    'response' => new \GuzzleHttp\Psr7\Response($status, array('Content-Type' => 'application/xml'),
                        '<Error><Code>' . $code . '</Code><Message>QA</Message></Error>'),
                ));
            }
            $body = '';
            if ($method === 'PUT') {
                $objects[$key] = (string) $request->getBody();
            } elseif ($method === 'GET') {
                $body = $objects[$key];
                if (isset($options['sink'])) file_put_contents($options['sink'], $body);
            } elseif ($method === 'DELETE') {
                unset($objects[$key]);
            } else {
                throw new LogicException('Unexpected HTTP method');
            }
            return new \GuzzleHttp\Promise\FulfilledPromise(new \GuzzleHttp\Psr7\Response(
                $method === 'DELETE' ? 204 : 200,
                array('Content-Length' => (string) strlen($body), 'ETag' => '"qa"'), $body
            ));
        };
        $client->getHandlerList()->setHandler(new \Aws\WrappedHttpHandler($transport,
            \Aws\Api\Service::createParser($client->getApi()),
            \Aws\Api\Service::createErrorParser($client->getApi()->getProtocol(), $client->getApi())
        ));
        $clientProperty = new ReflectionProperty('backup_Amazon', 's3Client');
        $bucketProperty = new ReflectionProperty('backup_Amazon', 'bucket');
        $clientProperty->setAccessible(true);
        $bucketProperty->setAccessible(true);
        $previousClient = $clientProperty->getValue();
        $previousBucket = $bucketProperty->getValue();
        $source = tempnam(sys_get_temp_dir(), 's3-source-');
        $dest = tempnam(sys_get_temp_dir(), 's3-dest-');
        $clientProperty->setValue(null, $client);
        $bucketProperty->setValue(null, 'qa-bgerp-bucket');
        try {
            $payload = "Кирилица\n00123\x00\xff\r\n";
            file_put_contents($source, $payload);
            $key = 'архиви/тест & space/' . basename($source);
            $check(backup_Amazon::putFile($source, 'архиви/тест & space'), 'Upload Cyrillic key');
            $check(($objects[$key] ?? null) === $payload, 'Uploaded bytes preserved');
            $check(backup_Amazon::getFile($key, $dest), 'Download to SaveAs');
            $check(file_get_contents($dest) === $payload, 'Downloaded bytes preserved');
            $check(strpos($requests[0][2], 'AWS4-HMAC-SHA256') === 0, 'Signature V4 generated');
            $check(backup_Amazon::removeFile($key) && !isset($objects[$key]), 'Delete synthetic object');
            $check(!backup_Amazon::getFile($key, $dest), 'Missing object returns false');
            $forbidden = true;
            $check(!backup_Amazon::putFile($source), 'Denied upload returns false');
            $check(!backup_Amazon::getFile($key, $dest), 'Denied download returns false');
            $check(!backup_Amazon::removeFile($key), 'Denied delete returns false');
            $forbidden = false;
            file_put_contents($source, '');
            $check(backup_Amazon::putFile($source), 'Empty file upload');
            $check(backup_Amazon::getFile(basename($source), $dest) && filesize($dest) === 0, 'Empty file download');
            $large = str_repeat("QA\x00Кирилица\n", 400000);
            file_put_contents($source, $large);
            $check(backup_Amazon::putFile($source), 'Large binary upload');
            $check(backup_Amazon::getFile(basename($source), $dest) && hash_file('sha256', $dest) === hash('sha256', $large), 'Large binary download checksum');
            $check(!backup_Amazon::putFile($source . '.missing'), 'Missing source returns false');
            $check(!backup_Amazon::putFile(null) && !backup_Amazon::putFile('') && !backup_Amazon::putFile(array()), 'Invalid source returns false');
            chmod($source, 0444);
            $check(backup_Amazon::putFile($source), 'Read-only source upload');
        } finally {
            $clientProperty->setValue(null, $previousClient);
            $bucketProperty->setValue(null, $previousBucket);
            chmod($source, 0600);
            unlink($source);
            unlink($dest);
        }
    }
}
