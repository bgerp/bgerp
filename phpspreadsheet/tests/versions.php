<?php

/**
 * Проверки на точния избор на Composer версия без изпълнение на Composer или SQL.
 *
 * @category  bgerp
 * @package   phpspreadsheet
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') {
    exit;
}

define('EF_ROOT_PATH', dirname(__DIR__, 3));
class core_Mvc {}
class core_ProtoSetup
{
    /** Тестов избор без запис в базата. */
    public static function get($key) { return '5.10.0'; }
}

/** Дефинира стойност само ако още липсва. */
function defIfNot($name, $value)
{
    if (!defined($name)) define($name, $value);
}

/** Брой на синтетичните инсталирани пакети. */
function countR($value)
{
    return count($value ?? array());
}

require dirname(__DIR__, 2) . '/core/Composer.class.php';
require dirname(__DIR__) . '/Setup.class.php';

/** Проверява условие и прекратява с ненулев код при неуспех. */
function checkVersion($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
    echo $message . ": OK\n";
}

$package = 'phpoffice/phpspreadsheet';
core_Composer::$packs = array($package => '1.30.5');
checkVersion(PHPSPREADSHEET_VERSION === '1.30.5', 'Default stays 1.30.5');
checkVersion(core_Composer::isInstalled($package, '1.30.5', true), 'Installed old version matches');
checkVersion(!core_Composer::isInstalled($package, '1.30.7', true), 'Upgrade requires installation');

core_Composer::$packs[$package] = '1.30.7';
checkVersion(core_Composer::isInstalled($package, '1.30.7', true), 'Installed new version matches');
checkVersion(!core_Composer::isInstalled($package, '1.30.5', true), 'Downgrade requires installation');
checkVersion(core_Composer::isInstalled($package, '1.30.5'), 'Existing minimum-version callers are unchanged');
checkVersion(core_Composer::isInstalled($package, 'v1.30.7', true), 'Version prefix is accepted');
checkVersion(core_Composer::isInstalled($package), 'Versionless callers are unchanged');
checkVersion(!core_Composer::isInstalled('missing/package', '1.30.7', true), 'Missing package is rejected');

$form = new class {
    public $rec;
    public $errors = array();
    /** Формата е подадена за запис. */ public function isSubmitted() { return true; }
    /** Запазва стандартните грешки по полета. */ public function setError($field, $message) { $this->errors[$field] = $message; }
};
$form->rec = (object) array('PHPSPREADSHEET_VERSION' => '5.10.0');
$setup = new phpspreadsheet_Setup();
$setup->inputConfigDescriptionForm($form);
checkVersion(isset($form->errors['PHPSPREADSHEET_VERSION']) === (PHP_VERSION_ID < 80200), 'PHP form guard');
checkVersion(phpspreadsheet_Setup::getPhpVersionError('1.30.7') === null, 'Old branch stays available');
if (PHP_VERSION_ID < 80200) {
    checkVersion(strpos($setup->checkConfig(), 'PHP 8.2') !== false, 'Setup PHP diagnostic');
    checkVersion(strpos($setup->install(), "class='red'") !== false, 'No Composer invocation on PHP 7.4');
} else {
    checkVersion(strpos($setup->checkConfig(), 'Липсва') !== false, 'Missing new version diagnostic');
    core_Composer::$packs[$package] = '5.10.0';
    checkVersion($setup->checkConfig() === null, 'Installed new branch accepted');
}

if (PHP_VERSION_ID < 80200) {
    class core_BaseClass {}
    class core_Packs {
        /** Симулира инсталиран bgERP пакет. */ public static function isInstalled($name) { return true; }
    }
    /** Проверява отказа преди зареждане на PHP 8 класове. */
    function expect($value, $message) { if (!$value) throw new RuntimeException($message); }
    require dirname(__DIR__) . '/Adapter.class.php';
    checkVersion(!phpspreadsheet_Adapter::isAvailable(), 'PHP 7.4 uses the existing XLS fallback');
    $failed = false;
    try { phpspreadsheet_Adapter::writeXls('', 'unused.xls'); } catch (RuntimeException $e) { $failed = strpos($e->getMessage(), 'PHP 8.2') !== false; }
    checkVersion($failed, 'Direct XLS call rejects before loading PHP 8 code');
}
