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

$firstFinal = operation(40, 40, 'auto');
$firstFinal->isFinal = 'yes';
$storedFinal = operation(50, 50, 'auto');
$storedFinal->isFinal = $storedFinal->canStore = 'yes';
$regular = operation(60, 60, 'auto');
$rows = added(array(), $storedFinal, array());
$rows = added($rows, $firstFinal, array());
$rows = added($rows, $regular, array());
checkOrder(array_keys($rows), array(60, 40, 50), 'Първо финална без предходни, после друга финална и междинна');
checkOrder(array_keys(added(array(60 => $regular), $firstFinal, array())), array(60, 40), 'Финална, въведена след междинната');
$needsStored = array(40 => array(50 => 50), 60 => array(40 => 40));
checkOrder(planning_TaskOrder::order($rows, $needsStored), array(60, 50, 40), 'Междинните са преди финалните, а вътре в групата Предходни е преди Складируем');
$otherStored = clone $storedFinal;
$otherStored->id = $otherStored->productId = 51;
$rows = array(51 => $otherStored) + $rows;
checkOrder(planning_TaskOrder::order($rows, $needsStored), array(60, 50, 40, 51), 'Независимият складируем остава последен');
$cycle = array(40 => array(50 => 50), 50 => array(40 => 40), 51 => array(50 => 50));
$order = planning_TaskOrder::order($rows, $cycle);
checkOrder($order, array(60, 40, 50, 51), 'Цикъл между финални запазва изпълнимата зависимост към трети етап');
$sorted = array();
foreach ($order as $position => $id) {
    $sorted[$id] = clone $rows[$id];
    $sorted[$id]->saoOrder = $position + 1;
}
checkOrder(planning_TaskOrder::order($sorted, $cycle), $order, 'Следващото подреждане дава същия резултат');
$regular->jobOrderMode = 'fixed';
$regular->previousTask = 40;
checkOrder(array_keys(added(array(40 => $firstFinal), $regular, array())), array(60, 40), 'Заварена несъвместима ръчна връзка не изтегля финалната напред');
foreach (array(array('clonedFromId', 70), array('systemId', 0), array('jobOrderMode', 'recipe'), array('jobOrderMode', 'fixed')) as list($field, $value)) {
    $protected = clone $firstFinal;
    $protected->{$field} = $value;
    $rows = array(40 => $protected, 60 => $regular);
    checkOrder(planning_TaskOrder::order($rows, array()), array(40, 60), 'Предишният ред се пази при ' . $field);
    checkOrder(array_keys(added(array(40 => $protected), $regular, array())), array(40, 60), 'Единично добавяне към задание с ' . $field);
}
$stockClone = clone $storedFinal;
$stockClone->clonedFromId = 80;
$regular = operation(60, 60, 'auto');
$regular->clonedFromId = 81;
$clonedJob = array(50 => $stockClone, 60 => $regular);
checkOrder(array_keys(added($clonedJob, $firstFinal, array())), array(60, 40, 50), 'Ръчна финална към клонирано задание подрежда и складируемата финална');
$clonedJob = array(60 => $regular, 61 => operation(61, 61, 'auto'));
checkOrder(array_keys(added($clonedJob, $firstFinal, array(), array(40 => array(60 => 60), 60 => array(61 => 61)))), array(60, 61, 40), 'Ръчна финална пази реда на заварените междинни');
$freeFinal = clone $firstFinal;
$recipeFinal = operation(10, 10, 'recipe', 0);
$recipeFinal->isFinal = 'yes';
$recipeMiddle = operation(20, 20, 'recipe', 1);
$recipe = array((object)array('_dId' => 110, 'productId' => 10), (object)array('_dId' => 120, 'productId' => 20));
$mixedJob = added(array(10 => $recipeFinal, 20 => $recipeMiddle), $freeFinal, $recipe, array(40 => array(10 => 10)));
checkOrder(array_keys($mixedJob), array(10, 20, 40), 'Наличието на рецепта запазва само определените от нея места');
$mixedJob = added($mixedJob, operation(70, 70, 'auto'), $recipe);
checkOrder(array_keys($mixedJob), array(10, 20, 70, 40), 'Междинна след финалната запазва реда от рецептата и финалната група');
$lateClone = operation(71, 71, 'auto');
$lateClone->clonedFromId = 91;
$mixedJob = added($mixedJob, $lateClone, $recipe);
checkOrder(array_keys($mixedJob), array(10, 20, 70, 71, 40), 'По-късно клонирана междинна също влиза пред ръчната финална');
$recipe[] = (object)array('_dId' => 130, 'productId' => 30);
$mixedJob = added($mixedJob, operation(72, 30, 'recipe', 2), $recipe);
checkOrder(array_keys($mixedJob), array(10, 20, 72, 70, 71, 40), 'Нов рецептурен етап заема мястото си пред ръчната финална');
$older = clone $firstFinal;
$older->id = 35;
$newer = clone $firstFinal;
$newer->id = 41;
$sameStage = array(41 => $newer, 40 => $firstFinal, 35 => $older, 60 => $regular);
checkOrder(planning_TaskOrder::order($sameStage, array()), array(60, 35, 40, 41), 'Еднакви финални се връщат по ред на добавяне след разместване');
$previewFinal = clone $firstFinal;
$previewFinal->id = -1;
checkOrder(array_keys(added($sameStage, $previewFinal, array())), array(60, 35, 40, 41, -1), 'В прегледа новата финална е най-новата при равни условия');
$sameStage[40]->isFinal = 'no';
checkOrder(planning_TaskOrder::order($sameStage, array()), array(40, 60, 35, 41), 'Бивш финален етап не участва в крайния ред');
$firstFinal->isFinal = 'yes';
$firstFinal->jobOrderMode = 'fixedFinal';
$firstFinal->state = 'closed';
checkOrder(array_keys(added(array(40 => $firstFinal), operation(90, 90, 'auto'), array())), array(90, 40), 'Приключена ръчна финална запазва мястото си в края при следващо добавяне');
$firstFinal->state = 'wakeup';
checkOrder(planning_TaskOrder::isFixed($firstFinal), true, 'Приключената финална остава фиксирана и след събуждане');
$firstFinal->jobOrderMode = 'recipe';
$recipe = array((object)array('_dId' => 110, 'productId' => 40), (object)array('_dId' => 120, 'productId' => 60));
$rows = added(array(40 => $firstFinal), operation(60, 60, 'recipe', 1), $recipe);
checkOrder(array_keys($rows), array(40, 60), 'Рецепта, която изрично започва с финален етап');
echo "OK: recipe stages, mixed cloning, manual final stages and changing stage dependencies\n";
