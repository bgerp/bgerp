<?php

/**
 * Регресии за подредбата при частично създаване, клониране и редакция на предходни етапи.
 *
 * @category  bgerp
 * @package   planning
 * @author    Stefan Arsov <stefan.arsof@gmail.com> with Codex
 * @license   GPL 3
 */
class core_Mvc {}
class planning_Setup { public static function get($key) { return 1; } }
class cls { public static function getInterface($interface, $id) { return new planning_interface_TopologicalOrderTasksInJob(); } }
require dirname(__DIR__) . '/GraphSort.class.php';
require dirname(__DIR__) . '/interface/TopologicalOrderTasksInJob.class.php';
require dirname(__DIR__) . '/TaskOrder.class.php';

function checkOrder($actual, $expected, $message)
{
    if ($actual !== $expected) throw new RuntimeException($message . ' ' . json_encode($actual));
}

function operation($id, $product, $mode = 'recipe', $key = null, $subtitle = '')
{
    return (object)array('id' => $id, 'productId' => $product, 'jobOrderMode' => $mode, 'systemId' => $key, 'subTitle' => $subtitle, 'saoOrder' => $id);
}

function added($job, $row, $recipe, $conditions = array())
{
    $order = planning_TaskOrder::insert($job, $row, $recipe, $conditions);
    $job[$row->id] = $row;
    $res = array();
    foreach ($order as $index => $id) {
        $res[$id] = clone $job[$id];
        $res[$id]->saoOrder = $index + 1;
    }
    return $res;
}

$recipe = array();
for ($i = 1; $i <= 20; $i++) $recipe[$i - 1] = (object)array('_dId' => 100 + $i, 'productId' => $i);
foreach (array(true, false) as $cloneFirst) {
    $job = array();
    $missing = array(2, 5, 8, 11, 15);
    foreach (array_reverse(array_diff(range(1, 20), $missing)) as $step) {
        $job = added($job, operation($step, $step, 'recipe', $cloneFirst ? null : $step - 1), $recipe);
    }
    foreach (array(11, 2) as $step) {
        $job = added($job, operation($step, $step, 'recipe', $cloneFirst ? $step - 1 : null), $recipe);
    }
    checkOrder(array_keys($job), array_values(array_diff(range(1, 20), array(5, 8, 15))), 'Клонове и редове от рецептата в двете посоки');
}

$job = array(6 => operation(6, 6), 1 => operation(1, 1), 3 => operation(3, 3));
checkOrder(array_keys(added($job, operation(2, 2), $recipe)), array(6, 1, 2, 3), 'Ръчно разместена рецепта');
checkOrder(array_keys(added(array(4 => operation(4, 4)), operation(1, 1), $recipe)), array(1, 4), 'Липсват предходните и непосредственият следващ');
$outside = operation(30, 30, 'auto');
checkOrder(array_keys(added(array(30 => $outside), operation(1, 1), $recipe)), array(30, 1), 'Само външни операции');
$job = array(1 => operation(1, 1), 30 => $outside, 5 => operation(5, 5));
checkOrder(array_keys(added($job, operation(3, 3), $recipe, array(3 => array(30 => 30)))), array(1, 30, 3, 5), 'Уточняване между двата рецептурни съседа');
checkOrder(array_keys(added($job, operation(3, 3), $recipe, array(3 => array(5 => 5)))), array(1, 3, 30, 5), 'Рецептата има предимство');
$freeJob = added(array(1 => operation(1, 1), 5 => operation(5, 5)), $outside, $recipe, array(30 => array(1 => 1)));
checkOrder(array_keys($freeJob), array(1, 30, 5), 'Нов свободен ред след технологичния предходник');
$debug = null;
checkOrder(planning_TaskOrder::order($freeJob, array(30 => array(5 => 5)), $debug, array(30)), array(1, 5, 30), 'Променен предходник');
$freeJob[30]->jobOrderMode = 'fixed';
checkOrder(planning_TaskOrder::order($freeJob, array(30 => array(5 => 5)), $debug, array(30)), array(1, 30, 5), 'Ръчно фиксирана или събудена операция');

$mixed = array(1 => operation(1, 1), 30 => operation(30, 30, 'auto'), 31 => operation(31, 31, 'auto'));
checkOrder(array_keys(added($mixed, operation(2, 2), $recipe, array(30 => array(31 => 31)))), array(1, 2, 30, 31), 'Вмъкването запазва относителния ред и на свободните съседи');
$cycle = array(30 => array(31 => 31), 31 => array(30 => 30));
checkOrder(planning_TaskOrder::improvesOrder($mixed, array(1, 31, 30), $cycle), false, 'Циклични предходни не предизвикват редуване при всеки крон');
$crossJob = operation(41, 41, 'auto');
$crossJob->previousTask = 900;
checkOrder(planning_TaskOrder::order(array(41 => $crossJob, 40 => operation(40, 40, 'auto')), array(41 => array(40 => 40))), array(40, 41), 'Предходна от друго задание отпада и се прилага стандартната стратегия');
$mixed = array(1 => operation(1, 1), 2 => operation(2, 2), 41 => $crossJob);
checkOrder(planning_TaskOrder::order($mixed, array(41 => array(1 => 1)), $debug, array(41)), array(1, 41, 2), 'Променен предходник при стар клон с чужда връзка');
checkOrder(planning_TaskOrder::matchRecipe($recipe, array($crossJob)), array(), 'Клон от външен етап остава свободен в целевото задание');

$twice = array((object)array('_dId' => 10, 'productId' => 1, 'subTitle' => '1'), (object)array('_dId' => 20, 'productId' => 1, 'subTitle' => '2'));
$clones = array(9 => operation(9, 1, 'recipe', null, '2'), 5 => operation(5, 1));
$matches = planning_TaskOrder::matchRecipe($twice, $clones);
checkOrder(array($matches[9], $matches[5]), array(1, 0), 'Клонирани повтарящи се етапи');
$clones[5]->systemId = 0;
$clones[9]->subTitle = '';
checkOrder(planning_TaskOrder::existingByTemplate($twice, $clones), array(0 => array(5 => 5), 1 => array(9 => 9)), 'Плюсът използва идентично съответствие');
checkOrder(planning_TaskOrder::matchRecipe(array((object)array('productId' => 1)), $clones), array(), 'Генериран шаблон без рецепта');
echo "OK: recipe stages, mixed cloning, manual order and changing stage dependencies\n";
