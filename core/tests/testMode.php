<?php

/**
 * Изолирани CLI и HTTP проверки на разрешението за тестове.
 *
 * @category tests
 * @package core
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

// Зареждаме само реалната функция, без bootstrap, конфигурация и база.
$tokens = token_get_all(file_get_contents(dirname(__DIR__) . '/boot.inc.php'));
$source = '';
for ($i = 0, $count = count($tokens); $i < $count; $i++) {
    if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
    $start = $i;
    do { $i++; } while (is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE);
    if (!is_array($tokens[$i]) || $tokens[$i][1] !== 'requireTestMode') continue;
    $depth = 0;
    $opened = false;
    for ($i = $start; $i < $count; $i++) {
        $token = $tokens[$i];
        $source .= is_array($token) ? $token[1] : $token;
        if ($token === '{') { $depth++; $opened = true; }
        if ($token === '}' && --$depth === 0 && $opened) break;
    }
    break;
}
if (!$source) throw new RuntimeException('Missing requireTestMode');

$directory = sys_get_temp_dir() . '/bgerp-test-mode-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
$fixture = $directory . '/router.php';
$log = $directory . '/http.log';
$server = null;
$checks = 0;
$fixtureCode = <<<'PHP'
<?php
$input = PHP_SAPI === 'cli' ? json_decode($argv[1], true) : $_GET;
if (isset($input['branch'])) define('BGERP_GIT_BRANCH', $input['branch']);
if (isset($input['flag'])) define('EF_ALLOW_TESTS', json_decode($input['flag'], true));
$roleChecks = 0;
function requireRole($roles) {
    global $input, $roleChecks;
    $roleChecks++;
    if ($roles !== 'debug' || ($input['user'] ?? '') !== 'debug') error('403 Role required');
}
function error($message) { throw new RuntimeException($message); }
PHP;
$fixtureCode .= "\n" . $source . "\n" . <<<'PHP'
try {
    requireTestMode();
    echo json_encode(array('allowed' => true, 'roleChecks' => $roleChecks));
} catch (RuntimeException $e) {
    // redirect-catch: terminal - isolated role/error stubs do not issue redirects.
    http_response_code(403);
    echo json_encode(array('allowed' => false, 'roleChecks' => $roleChecks));
}
PHP;

function verifyTestMode($body, $allowed, $roleChecks, $label)
{
    global $checks;
    $result = json_decode($body, true);
    if ($result !== array('allowed' => $allowed, 'roleChecks' => $roleChecks)) {
        throw new RuntimeException($label . ': unexpected result ' . $body);
    }
    $checks++;
}

try {
    file_put_contents($fixture, $fixtureCode);
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if (!$socket) throw new RuntimeException($error);
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $server = proc_open(array(PHP_BINARY, '-S', $address, $fixture), array(
        0 => array('file', '/dev/null', 'r'), 1 => array('file', $log, 'a'), 2 => array('file', $log, 'a'),
    ), $pipes);
    if (!is_resource($server)) throw new RuntimeException('Cannot start HTTP fixture');
    for ($i = 0; $i < 100; $i++) {
        $connection = @stream_socket_client('tcp://' . $address, $errno, $error, 0.05);
        if ($connection) { fclose($connection); break; }
        usleep(20000);
    }

    $cases = array(
        array(array(), false),
        array(array('branch' => 'dev'), true),
        array(array('branch' => 'master'), false),
        array(array('branch' => 'DC1'), false),
        array(array('branch' => 'dev', 'flag' => 'false'), false),
        array(array('branch' => 'master', 'flag' => 'true'), true),
        array(array('flag' => 'true'), true),
        array(array('branch' => 'dev', 'flag' => '1'), false),
        array(array('branch' => 'dev', 'flag' => '"true"'), false),
        array(array('branch' => 'dev', 'flag' => 'null'), false),
    );
    foreach ($cases as $index => $case) {
        list($input, $enabled) = $case;
        $process = proc_open(array(PHP_BINARY, $fixture, json_encode($input)), array(
            0 => array('file', '/dev/null', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w'),
        ), $pipes);
        if (!is_resource($process)) throw new RuntimeException('Cannot start CLI fixture');
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        if (proc_close($process) !== 0 || $errors !== '') throw new RuntimeException($errors);
        verifyTestMode($output, $enabled, 0, 'CLI case ' . $index);

        foreach (array('anonymous', 'ordinary', 'debug') as $user) {
            $context = stream_context_create(array('http' => array('ignore_errors' => true, 'timeout' => 5)));
            $output = file_get_contents('http://' . $address . '/?' . http_build_query($input + array('user' => $user)), false, $context);
            $allowed = $enabled && $user === 'debug';
            verifyTestMode($output, $allowed, 1, 'HTTP case ' . $index . ' ' . $user);
            $status = $allowed ? '200' : '403';
            if (strpos($http_response_header[0], $status) === false) throw new RuntimeException('Wrong HTTP status');
            $checks++;
        }
    }
    echo "OK: {$checks} test mode checks, no database or application configuration.\n";
} finally {
    if (is_resource($server)) { proc_terminate($server); proc_close($server); }
    foreach (array($fixture, $log) as $file) { if (is_file($file)) unlink($file); }
    rmdir($directory);
}
