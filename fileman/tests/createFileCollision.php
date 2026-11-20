<?php

/**
 * A new file record survives another process taking its name between the lookup and the insert.
 *
 * @category  bgerp
 * @package   fileman
 * @author    Mustafa Mustafov <mmustafov084@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

// php fileman/tests/createFileCollision.php. In-memory records; no database or filesystem.
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
$checks = 0;
function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
class str { public static function utf2ascii($text) { return $text; } }
class fileman_Data
{
    public static $links = 0;
    public static function increaseLinks($dataId) { self::$links++; }
}
class core_Master
{
    // Name => record id; the unique index on name and bucket
    public static $names = array();
    // Names another process inserts just before this process does
    public static $rivals = array();
    public static $errors = 0;

    public static function fetchField($cond, $field)
    {
        return self::$names[$cond[1]] ?? null;
    }

    public static function save(&$rec, $fields = null, $mode = null)
    {
        if (self::$rivals) {
            self::$names[array_shift(self::$rivals)] = count(self::$names) + 1;
        }
        // INSERT IGNORE: a duplicate is skipped and leaves no insert id
        $rec->fileHnd = 'h' . mt_rand();
        $rec->id = 0;
        if (!isset(self::$names[$rec->name])) {
            $rec->id = self::$names[$rec->name] = count(self::$names) + 1;
        }

        return $rec->id;
    }

    public static function logErr($message, $id = null) { self::$errors++; }
}
require dirname(__DIR__) . '/Files.class.php';

$create = new ReflectionMethod('fileman_Files', 'createFile');
$create->setAccessible(true);

$fh = $create->invoke(null, 'page.jpg', 7, 1);
check($fh && core_Master::$names === array('page.jpg' => 1) && fileman_Data::$links === 1, 'A free name is taken on the first attempt');

// Two rivals take the looked-up name twice in a row
core_Master::$rivals = array('page_1.jpg', 'page_2.jpg');
$fh = $create->invoke(null, 'page.jpg', 7, 2);
check($fh && isset(core_Master::$names['page_3.jpg']) && count(core_Master::$names) === 4, 'The next free name is used after a lost insert');
check(fileman_Data::$links === 2 && core_Master::$errors === 0, 'Only the stored record counts as a link');

// A rival wins every attempt: no handle without a record
core_Master::$rivals = array();
for ($i = 4; $i < 4 + fileman_Files::CREATE_FILE_TRIES; $i++) {
    core_Master::$rivals[] = "page_{$i}.jpg";
}
$fh = $create->invoke(null, 'page.jpg', 7, 3);
check($fh === null && fileman_Data::$links === 2 && core_Master::$errors === 1, 'A record that was never stored returns no handle and is logged');

echo "File record collisions: {$checks} checks passed.\n";
