<?php

/**
 * Проверки на данните за библиотечни графики без база данни.
 *
 * @category  bgerp
 * @package   chartjs
 * @author    Mustafov1 <mmustafov3@icloud.com>
 * @copyright 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit;

require dirname(__DIR__, 2) . '/core/BaseClass.class.php';
require dirname(__DIR__) . '/ChartDriver.class.php';

$data = array('labels' => array('А < Б', '[#период#]'), 'values' => array('Серия' => array(-2.5, 0)));
foreach (array('line', 'bar') as $type) {
    $fields = chartjs_ChartDriver::prepareFields($type, 'Сравнение', $data);
    if ($fields['chartType'] !== $type || json_decode($fields['chartData'], true) !== $data) {
        throw new RuntimeException('Променени етикети или числови данни.');
    }
}

$pie = json_decode('{"info":{"0":0,"1":3.25,"length":2}}');
$fields = chartjs_ChartDriver::prepareFields('pie', 'Дялове', $pie);
if ($fields['chartData'] !== json_encode($pie)) {
    throw new RuntimeException('Променени дялове.');
}

$invalid = array(
    array('scatter', 'Тест', $data),
    array('bar', " \t", $data),
    array('line', 'Тест', $data + array('options' => array())),
    array('line', 'Тест', json_encode($data)),
    array('line', 'Тест', array('labels' => array(' '), 'values' => $data['values'])),
    array('line', 'Тест', array('labels' => array_fill(0, 201, 'А'), 'values' => $data['values'])),
    array('line', 'Тест', array('labels' => $data['labels'], 'values' => array())),
    array('line', 'Тест', array('labels' => $data['labels'], 'values' => array('А' => array(1)))),
    array('bar', 'Тест', array('labels' => $data['labels'], 'values' => array('А' => array(0, '2')))),
    array('bar', 'Тест', array('labels' => $data['labels'], 'values' => array('А' => array(0, INF)))),
    array('bar', 'Тест', array('labels' => $data['labels'], 'values' => array(array(1, 2)))),
    array('pie', 'Тест', array('info' => array('' => 1))),
    array('pie', 'Тест', array('info' => array('А' => 0))),
    array('pie', 'Тест', array('info' => array('А' => -1, 'Б' => 4))),
    array('pie', 'Тест', array('info' => array('А' => NAN))),
    array('pie', 'Тест', array('info' => array('А' => 1e308, 'Б' => 1e308))),
    array('pie', 'Тест', array('info' => array(true))),
    array('line', 'Тест', array('labels' => array_fill(0, 200, str_repeat('й', 160)),
        'values' => array('А' => array_fill(0, 200, 1234567)))),
);

foreach ($invalid as $i => $fixture) {
    try {
        chartjs_ChartDriver::prepareFields(...$fixture);
    } catch (InvalidArgumentException $e) {
        continue;
    }
    throw new RuntimeException("Приети са невалидни данни във вариант {$i}.");
}
echo "OK\n";
