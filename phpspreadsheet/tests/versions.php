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
class core_ProtoSetup {}

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
