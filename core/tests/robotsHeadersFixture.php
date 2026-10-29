<?php

/**
 * HTTP fixture using the real response, CMS, rights and HTML header code.
 *
 * @category  bgerp
 * @package   core
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */

if (PHP_SAPI !== 'cli-server') exit("HTTP test fixture only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function expect($ok) { if (!$ok) throw new RuntimeException('Expectation failed'); }
function getBoot() { return ''; }
function getEF_SALT() { return 'synthetic-salt'; }
function haveRole($roles) { return false; }
function logHitState($code, $state) { return null; }
define('EF_SALT', 'synthetic-salt');
define('EF_HTTPS', 'NO');
define('EF_SBF', 'sbf');
define('EF_APP_NAME', 'robots-test');
define('EF_ROOT_PATH', dirname(__DIR__, 2));
class core_BaseClass {}
class core_Manager extends core_BaseClass {}
class core_Master extends core_Manager {}
class core_ET {}
class core_Session {
    public static function getDecoratePrefix() { return 'robotsTest'; }
    public static function get($key) { return array(); }
}
class core_Lg {
    public static function set($lang, $force) {}
    public static function getLangs() { return array(); }
}
class cms_Domains { public static function getPublicDomain($part = null, $lang = null) { return 'en'; } }
class arr { public static function make($value) { return (array) $value; } }
class core_SystemLock { public static function stopIfBlocked() {} }
class core_Webroot { public static function serve($filename) { echo 'static'; } }
class core_Sbf { public static function serveStaticFile($filename) { echo 'static'; } }

require dirname(__DIR__) . '/App.class.php';
require dirname(__DIR__) . '/Mode.class.php';
class_alias('core_Mode', 'Mode');
require dirname(__DIR__) . '/Users.class.php';
class Users extends core_Users {
    public static function refreshSession() {}
    public static function haveRole($roles, $userId = null) { return true; }
}
require dirname(__DIR__, 2) . '/cms/Content.class.php';
require dirname(__DIR__, 2) . '/page/Html.class.php';
require dirname(__DIR__) . '/page/Active.class.php';
require dirname(__DIR__, 2) . '/cms/page/External.class.php';
require dirname(__DIR__, 2) . '/log/Browsers.class.php';
require dirname(__DIR__) . '/Debug.class.php';

trait RobotsTestTemplate {
    public function __construct() {}
    public function getArray($name) {
        return $name === 'HTTP_HEADER' && ($_GET['case'] ?? '') === 'explicit-policy' ? array('X-Robots-Tag: noindex') : array();
    }
    public function appendFiles($files) {}
}
class RobotsPublicPage extends cms_page_External { use RobotsTestTemplate; }
class RobotsInternalPage extends page_Html { use RobotsTestTemplate; }
class RobotsWrapper {
    public function render($content) {
        page_Html::on_Output($content);
        echo 'page';
    }
}
class core_Cls {
    public static function get($name) { return $name === 'core_Plugins' ? new stdClass() : new RobotsWrapper(); }
}
class core_Request {
    public static function forward() {
        $case = $_GET['case'];
        if ($case === 'redirect') { header('Location: /destination'); echo 'redirect'; exit; }
        if ($case === 'json') { header('Content-Type: application/json'); echo '{"ok":true}'; exit; }
        if ($case === 'download') { header('Content-Disposition: attachment; filename=test.txt'); echo 'file'; exit; }
        if ($case === 'pixel') { (new log_Browsers())->render1x1gif(); exit; }
        if ($case !== 'internal' && $case !== 'external' && $case !== 'error') cms_Content::setCurrent();
        if ($case === 'protected-public') {
            core_Users::requireRole('partner');
            core_Users::requireRole('every_one');
        }
        if ($case === 'partner-tab') Mode::set('currentExternalTab', 'cms_Profiles');
        if ($case === 'printing' || $case === 'pdf') Mode::set($case, true);
        $page = in_array($case, array('internal', 'internal-cms'), true) ? new RobotsInternalPage() : new RobotsPublicPage();
        if ($case === 'error' || $case === 'public-error') {
            if ($case === 'public-error') page_Html::on_Output($page);
            core_Debug::renderErrorState(array('httpStatusCode' => 500, 'httpStatusMsg' => 'Internal Server Error'));
            exit;
        }
        return $page;
    }
}

$_GET['case'] = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$_GET['virtual_url'] = $_GET['case'];
if ($_GET['case'] === 'asset') $_GET[EF_SBF] = 'test.css';
core_App::initSystem();
if ($_GET['case'] === 'setup') { echo 'setup'; exit; }
Mode::set('screenMode', 'wide');
core_App::run();
