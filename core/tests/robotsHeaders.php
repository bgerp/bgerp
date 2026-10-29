<?php

/**
 * Isolated HTTP checks for private responses and public CMS pages.
 *
 * @category  bgerp
 * @package   core
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */

// php core/tests/robotsHeaders.php; no installation, database or user session needed.
if (PHP_SAPI !== 'cli') exit("CLI only\n");

$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
if (!$socket) throw new RuntimeException($error);
$address = stream_socket_get_name($socket, false);
fclose($socket);
$log = tempnam(sys_get_temp_dir(), 'robots_http_');
$server = proc_open(array(PHP_BINARY, '-d', 'display_errors=0', '-S', $address, __DIR__ . '/robotsHeadersFixture.php'),
    array(0 => array('pipe', 'r'), 1 => array('file', $log, 'a'), 2 => array('file', $log, 'a')), $pipes);
if (!is_resource($server)) throw new RuntimeException('Cannot start test server');

try {
    for ($i = 0; $i < 100; $i++) {
        $connection = @stream_socket_client('tcp://' . $address, $errno, $error, 0.05);
        if ($connection) {
            fclose($connection);
            break;
        }
        usleep(20000);
    }

    $private = 'noindex, nofollow';
    $cases = array(
        'internal' => array(200, $private, 'page'),
        'setup' => array(200, $private, 'setup'),
        'external' => array(200, $private, 'page'),
        'public' => array(200, null, 'page'),
        'protected-public' => array(200, $private, 'page'),
        'internal-cms' => array(200, $private, 'page'),
        'partner-tab' => array(200, $private, 'page'),
        'printing' => array(200, $private, 'page'),
        'pdf' => array(200, $private, 'page'),
        'explicit-policy' => array(200, 'noindex', 'page'),
        'redirect' => array(302, $private, 'redirect'),
        'json' => array(200, $private, '{"ok":true}'),
        'download' => array(200, $private, 'file'),
        'pixel' => array(200, $private, 'GIF89a'),
        'error' => array(500, $private, 'Internal Server Error'),
        'public-error' => array(500, $private, 'Internal Server Error'),
        'robots.txt' => array(200, null, 'static'),
        'asset' => array(200, null, 'static'),
    );
    foreach ($cases as $case => $expected) {
        $context = stream_context_create(array('http' => array('follow_location' => 0, 'ignore_errors' => true, 'timeout' => 5)));
        $body = file_get_contents('http://' . $address . '/' . $case, false, $context);
        $responseHeaders = $http_response_header ?? array();
        $robots = null;
        foreach ($responseHeaders as $header) {
            if (stripos($header, 'X-Robots-Tag:') === 0) $robots = trim(substr($header, 13));
        }
        if ($body === false || strpos($responseHeaders[0] ?? '', (string) $expected[0]) === false ||
            $robots !== $expected[1] || strpos($body, $expected[2]) === false) {
            throw new RuntimeException($case . ': ' . implode('; ', $responseHeaders) . "\n" . $body . "\n" . file_get_contents($log));
        }
    }
    echo 'OK: ' . count($cases) . " HTTP robots scenarios.\n";
} finally {
    proc_terminate($server);
    fclose($pipes[0]);
    proc_close($server);
    unlink($log);
}
