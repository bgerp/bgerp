<?php

/**
 * Изолирани проверки на заключването при обновяване, без Git, bootstrap и база.
 *
 * @category tests
 * @package core
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("Run this regression test from CLI.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$source = file_get_contents(dirname(__DIR__) . '/Setup.inc.php');
$start = strpos($source, '// Стъпка 2: Обновяване');
$end = strpos($source, '// Ако се намираме на стъпка 3: Проверки');
if ($start === false || $end === false || $end <= $start) throw new RuntimeException('Missing update section');
$updateSource = substr($source, $start, $end - $start);

// Извличаме само безопасните HTML помощници, без изпълнение на setup скрипта.
$helpers = array('linksToHtml', 'logToHtml', 'addParams');
$tokens = token_get_all($source);
for ($i = 0, $count = count($tokens); $i < $count; $i++) {
    if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
    $first = $i;
    do { $i++; } while (is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE);
    if (!is_array($tokens[$i]) || !in_array($tokens[$i][1], $helpers, true)) continue;
    $name = $tokens[$i][1];
    $body = '';
    $depth = 0;
    $opened = false;
    for ($i = $first; $i < $count; $i++) {
        $token = $tokens[$i];
        $body .= is_array($token) ? $token[1] : $token;
        if ($token === '{' || (is_array($token) && in_array($token[0], array(T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES), true))) {
            $depth++;
            $opened = true;
        }
        if ($token === '}' && --$depth === 0 && $opened) break;
    }
    eval($body);
    $helpers = array_values(array_diff($helpers, array($name)));
}
if ($helpers) throw new RuntimeException('Missing HTML helpers');

class core_App
{
    public static $repos = array('/fixtures/private_a' => 'dev', '/fixtures/private_b' => 'dev', '/fixtures/bgerp' => 'dev');
    public static function getRepos() { return self::$repos; }
}
class core_Updates { public static function getNewVersionTag() { return false; } }
class core_SystemLock
{
    public static $locked = false;
    public static function block($message, $seconds) { self::$locked = true; recordUpdateCall('lock', $seconds); }
    public static function remove() { self::$locked = false; recordUpdateCall('unlock', null); }
}
function recordUpdateCall($operation, $value) { $GLOBALS['updateCalls'][] = array($operation, $value); }
function gitCurrentBranch($path, &$log) { return 'old-branch'; }
function gitLastCommitDate($path, &$log) { return '2026-01-01'; }
function gitSetBranch($path, &$log, $branch) { recordUpdateCall('switch', $path); return true; }
function gitPullRepo($path, &$log, $branch) { recordUpdateCall('pull', $path); return true; }
function gitRevertRepo($path, &$log) { recordUpdateCall('revert', $path); return true; }
function gitHasChanges($path, &$log) { return false; }
function gitHasNewVersion($path, &$log, $branch) { return true; }
function checkoutMaxVersion(&$log) { recordUpdateCall('release', null); }

function runUpdateCase($request, $alreadyLocked = false)
{
    global $updateSource, $updateCalls;
    $_GET = $request;
    $updateCalls = array();
    core_SystemLock::$locked = $alreadyLocked;
    $step = 2;
    $links = $texts = array();
    $selfUrl = 'https://example.invalid/setup?step=2&' . http_build_query($request);
    $nextUrl = 'https://example.invalid/setup?step=3';
    eval($updateSource);

    return array('calls' => $updateCalls, 'html' => $texts['body'] ?? '', 'locked' => core_SystemLock::$locked);
}
function updateCallsOf($result, $operation)
{
    $values = array();
    foreach ($result['calls'] as $call) if ($call[0] === $operation) $values[] = $call[1];
    return $values;
}
$checks = 0;
function checkUpdate($condition, $message)
{
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}

// Прегледът съдържа само синтетични адреси и реално генерираните HTML и JavaScript.
if (in_array('--html', $argv, true)) {
    $request = array('update' => '');
    if (in_array('--unchecked', $argv, true)) $request['lockUpdate'] = '0';
    $result = runUpdateCase($request);
    $styleStart = strpos($source, "\$texts['styles'] =");
    $styleEnd = strpos($source, "\$texts['scripts'] =");
    if ($styleStart === false || $styleEnd === false) throw new RuntimeException('Missing setup styles');
    $step = 2;
    $texts = array();
    eval(substr($source, $styleStart, $styleEnd - $styleStart));
    echo "<!doctype html><html lang='bg'><head><meta charset='UTF-8'><title>Update lock fixture</title>";
    echo ($texts['styles'] ?? '') . "</head><body class='setup-body'><div class='holder'>";
    echo $result['html'] . '</div></body></html>';
    exit;
}

$defaults = array(array(), array('lockUpdate' => '1'), array('lockUpdate' => ''), array('lockUpdate' => 'false'), array('lockUpdate' => array('0')), array('lockUpdate' => 0));
foreach ($defaults as $request) {
    $result = runUpdateCase($request + array('update' => 'private_a'));
    checkUpdate(updateCallsOf($result, 'lock') === array(7), 'Missing or malformed opt-out must lock');
    checkUpdate(updateCallsOf($result, 'pull') === array('/fixtures/private_a'), 'Only the requested repository is pulled');
    checkUpdate(strpos($result['html'], "checked='checked'") !== false, 'Checkbox defaults to checked');
}
foreach (array('private_a', 'bgerp') as $repository) {
    $result = runUpdateCase(array('update' => $repository, 'lockUpdate' => '0'));
    checkUpdate(updateCallsOf($result, 'lock') === array(), 'Explicit single repository opt-out must skip locking');
    checkUpdate(updateCallsOf($result, 'switch') === array('/fixtures/' . $repository), 'Other repository branches must remain untouched');
    checkUpdate(updateCallsOf($result, 'pull') === array('/fixtures/' . $repository), 'Opt-out pulls only its selected repository');
    checkUpdate(strpos($result['html'], "checked='checked'") === false, 'Checkbox reflects explicit opt-out');
}
$result = runUpdateCase(array('update' => 'all', 'lockUpdate' => '0'));
checkUpdate(count(updateCallsOf($result, 'lock')) === 3, 'Updating all repositories must lock');
checkUpdate(updateCallsOf($result, 'pull') === array_keys(core_App::$repos), 'All repositories are still updated');
foreach (array('revert', 'checkout') as $operation) {
    foreach (array('', 'private_b') as $value) {
        $result = runUpdateCase(array('update' => 'private_a', 'lockUpdate' => '0', $operation => $value));
        checkUpdate(updateCallsOf($result, 'lock') === array(7), 'Combined operation must ignore opt-out');
    }
}
$result = runUpdateCase(array('update' => 'private_a', 'lockUpdate' => '0', 'checkoutMaxVersion' => ''));
checkUpdate(updateCallsOf($result, 'release') === array(null), 'Release switching keeps its existing route');
checkUpdate(updateCallsOf($result, 'pull') === array(), 'Release switching cannot become an unlocked single pull');
foreach (array('', 'unknown', array('private_a')) as $repository) {
    $result = runUpdateCase(array('update' => $repository, 'lockUpdate' => '0'));
    checkUpdate(updateCallsOf($result, 'pull') === array(), 'Invalid targets must not pull repositories');
    checkUpdate(count(updateCallsOf($result, 'switch')) === 3, 'Invalid target must not enable single-repository bypass');
}
core_App::$repos['/other/private_a'] = 'dev';
$result = runUpdateCase(array('update' => 'private_a', 'lockUpdate' => '0'));
checkUpdate(count(updateCallsOf($result, 'lock')) === 2, 'Ambiguous repository names must retain locking');
unset(core_App::$repos['/other/private_a']);
core_App::$repos['/fixtures/01'] = 'dev';
core_App::$repos['/fixtures/1'] = 'dev';
$result = runUpdateCase(array('update' => '01', 'lockUpdate' => '0'));
checkUpdate(updateCallsOf($result, 'pull') === array('/fixtures/01'), 'Numeric repository names must match exactly');
checkUpdate(updateCallsOf($result, 'switch') === array('/fixtures/01'), 'Numeric target must not switch similarly named repositories');
checkUpdate(updateCallsOf($result, 'lock') === array(), 'Unambiguous numeric repository may opt out');
unset(core_App::$repos['/fixtures/01'], core_App::$repos['/fixtures/1']);
$result = runUpdateCase(array('update' => 'private_a', 'lockUpdate' => '0'), true);
checkUpdate($result['locked'] && updateCallsOf($result, 'unlock') === array(), 'Opt-out must preserve an existing maintenance lock');
$result = runUpdateCase(array());
checkUpdate(updateCallsOf($result, 'lock') === array() && updateCallsOf($result, 'pull') === array(), 'Displaying the update page must not update or lock');
checkUpdate(strpos($result['html'], "id='lockUpdate'") !== false && strpos($result['html'], "id='setupUpdateLinks'") !== false, 'Render checkbox and action container');

// Това е проверка на границата в кода, а не изпълнение на пълна инициализация.
$remainingSource = substr($source, $end);
checkUpdate(strpos($remainingSource, 'lockUpdate') === false, 'Later setup steps must not consume the update checkbox');
checkUpdate(strpos($remainingSource, "\$Packs->setupPack('bgerp')") !== false, 'Full initialization keeps the bgerp setup entry');
echo "OK: {$checks} isolated update-lock checks; no Git, database or live setup executed.\n";
