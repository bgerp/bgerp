<?php
/**
 * Изолирана регресия за датите на ЧМР, без база данни.
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
class trans_abstract_ShipmentDocument {}
class dt
{
    public static function mysql2verbal($value, $format) { return (new DateTimeImmutable($value))->format($format); }
}
require dirname(__DIR__) . '/Cmrs.class.php';
class CmrValuesTest extends trans_Cmrs
{
    public function getLink($id, $short) { return 'TEST CMR'; }
    public function verbal($rec)
    {
        $row = new stdClass();
        parent::on_AfterRecToVerbal($this, $row, $rec, array('-single' => true));
        return $row;
    }
}
$test = new CmrValuesTest();
foreach (array(null, '2026-11-02', '2026-11-03') as $date) {
    $row = $test->verbal((object) array('id' => 1, 'loadingDate' => '2026-11-03', 'establishedDate' => $date));
    if ($row->loadingDate !== '03.11.26') throw new RuntimeException('Loading date changed');
    $expected = $date === null ? null : (new DateTimeImmutable($date))->format('d.m.Y');
    if (($row->establishedDate ?? null) !== $expected) throw new RuntimeException('Wrong established date');
}
$row = $test->verbal((object) array('id' => 1));
if (isset($row->establishedDate) || isset($row->loadingDate)) throw new RuntimeException('Empty dates were populated');
echo "CMR dates: empty, different, equal, missing — OK\n";
