<?php

/**
 * Regression checks for BLOB defaults returned by schema inspection.
 *
 * @category  bgerp
 * @package   core
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */

if (PHP_SAPI !== 'cli') exit("Run from CLI.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
require dirname(__DIR__) . '/Db.class.php';

class BlobDefaultTestDb extends core_Db
{
    public $column;
    public $serverVersion = '10.11.14-MariaDB';
    public function __construct() {}
    public function query($sql, $silent = false, $replication = false, $resultMode = MYSQLI_STORE_RESULT) {
        if ($sql !== "SHOW FULL COLUMNS FROM `test_items` LIKE 'payload'") throw new RuntimeException('Unexpected SQL');
        return true;
    }
    public function fetchArray($result, $resultType = MYSQLI_ASSOC) { return $this->column; }
    public function freeResult($result = null) {}
    public function connect($forceDb = false) { return (object) array('server_info' => $this->serverVersion); }
}

$db = new BlobDefaultTestDb();
$checks = 0;
foreach (array('tinyblob', 'blob', 'mediumblob', 'longblob', 'text', 'varchar') as $type) {
    foreach (array('10.11.14-MariaDB', '8.0.40') as $serverVersion) {
        foreach (array(null, '', "''", "'value'", "''''", "('')") as $default) {
            $db->serverVersion = $serverVersion;
            $db->column = array('Field' => 'payload', 'Type' => $type, 'Null' => $default === null ? 'YES' : 'NO', 'Default' => $default);
            $attr = $db->getFieldAttr('test_items', 'payload');
            $expected = $serverVersion === '10.11.14-MariaDB' && substr($type, -4) === 'blob' && $default === "''" ? '' : $default;
            if (!property_exists($attr, 'default') || ($attr->default ?? null) !== $expected || ($attr->type ?? null) !== strtoupper($type) || ($attr->notNull ?? null) !== ($default !== null)) {
                throw new RuntimeException('Unexpected metadata: ' . json_encode(array($type, $serverVersion, $default, $attr)));
            }
            $checks++;
        }
    }
}
echo "OK: {$checks} BLOB default checks\n";
