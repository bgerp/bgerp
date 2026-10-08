<?php

/**
 * Точни целеви времена и проверки, общи за формите и планировчика.
 */
class planning_TargetTimes extends core_BaseClass
{
    /**
     * Резервира целия пакет атомарно; при неуспех календарът остава непроменен.
     */
    public static function reserveRun($chain, $tasks, &$interval, &$reservations, $now)
    {
        $target = $firstPin = null;
        $durationBefore = $totalDuration = 0;
        foreach ($chain as $taskId) {
            $task = $tasks[$taskId] ?? null;
            if (!is_object($task)) return false;
            if ($firstPin === null && !empty($task->timeStart)) {
                $firstPin = $taskId;
                $target = strtotime($task->timeStart);
                $durationBefore = $totalDuration;
            }
            $totalDuration += max(1, (int)($task->calcedCurrentDuration ?? 0));
        }
        if ($target === null || $target === false || $target < strtotime($now)) return false;

        $begin = $target;
        $left = $durationBefore;
        $frames = $left && $target > strtotime($now) ? $interval->getFrame(strtotime($now), $target - 1) : array();
        foreach (array_reverse($frames) as $frame) {
            if (!$left) break;
            $take = min($left, $frame[1] - $frame[0] + 1);
            $begin = $frame[1] - $take + 1;
            $left -= $take;
        }
        if ($left) return false;

        $probe = clone $interval;
        $capacity = 0;
        foreach ($probe->getFrame($begin, PHP_INT_MAX) as $frame) $capacity += $frame[1] - $frame[0] + 1;
        if ($capacity < $totalDuration) return false;
        $range = $probe->consume($totalDuration, $begin);
        if (!is_array($range) || $range[0] != $begin) return false;
        foreach ($reservations as $reservation) {
            if ($reservation[0] <= $range[1] && $reservation[1] >= $range[0]) return false;
        }

        $probe = clone $interval;
        $result = array();
        $cursor = $begin;
        foreach ($chain as $taskId) {
            $task = $tasks[$taskId];
            $duration = max(1, (int)($task->calcedCurrentDuration ?? 0));
            $member = $probe->consume($duration, $cursor, $range[1]);
            if (!is_array($member)) return false;
            $start = date('Y-m-d H:i:00', $member[0]);
            if (!empty($task->timeStart) && $member[0] != strtotime($task->timeStart)) return false;
            $result[$taskId] = (object)array(
                'id' => $taskId, 'assetId' => $task->assetId ?? null,
                'calcedCurrentDuration' => $duration,
                '_startTimestamp' => $member[0], '_endTimestamp' => $member[1] + 1,
                'expectedTimeStart' => $start,
                'expectedTimeEnd' => date('Y-m-d H:i:00', $member[1]),
            );
            $cursor = $member[1] + 1;
        }
        $interval = $probe;
        $reservations[] = $range;
        usort($reservations, function($a, $b) { return $a[0] <=> $b[0]; });

        return $result;
    }

    /**
     * За точните начала се проверява и пълният предходен ред в заданието.
     */
    public static function addJobPredecessors(&$tasks, &$previousTasks)
    {
        $origins = array();
        foreach ($tasks as $task) {
            if (!empty($task->timeStart) && !empty($task->originId)) $origins[$task->originId] = $task->originId;
        }
        if (!$origins) return;
        $query = planning_Tasks::getQuery();
        $query->in('originId', $origins);
        $query->where("#state != 'rejected'");
        $query->show('id,originId,saoOrder,state,timeClosed,offsetAfter');
        $jobTasks = $query->fetchAll();
        foreach ($tasks as $task) {
            if (empty($task->timeStart)) continue;
            foreach ($jobTasks as $previous) {
                if (($previous->originId ?? null) != ($task->originId ?? null)
                    || ($previous->saoOrder ?? PHP_INT_MAX) >= ($task->saoOrder ?? PHP_INT_MAX)
                    || ($previous->id ?? null) == ($task->id ?? null)) continue;
                if (($previous->state ?? null) == 'closed') {
                    if (!empty($previous->timeClosed)) {
                        $waiting = $previousTasks[$task->id][$previous->id]->waitingTime ?? ($previous->offsetAfter ?? 0);
                        $ready = dt::addSecs(max(0, (int)$waiting), $previous->timeClosed);
                        $task->_targetReadyAfter = max($task->_targetReadyAfter ?? '', $ready);
                    }
                } elseif (isset($tasks[$previous->id])) {
                    $constraint = clone ($previousTasks[$task->id][$previous->id] ?? (object)array('waitingTime' => 0));
                    $constraint->intersect = 'no';
                    $previousTasks[$task->id][$previous->id] = $constraint;
                } else {
                    $task->_targetReadyAfter = planning_TaskConstraints::NOT_FOUND_DATE;
                }
            }
        }
    }

    /**
     * Голямата корекция се измерва спрямо цялата предишна продължителност.
     */
    public static function needsDurationWarning($old, $new, $state)
    {
        if ((string)$old === (string)$new) return false;
        if (empty($old) || empty($new)) return true;

        return $state == 'stopped' && abs($new - $old) / $old > 0.5;
    }

    /**
     * Полето datetime-local изпраща ISO дата, а планировчикът работи с MySQL дати.
     */
    public static function normalizeManualTimes($times)
    {
        $result = array('expectedTimeStart' => array(), 'expectedTimeEnd' => array());
        foreach ($result as $field => $values) {
            foreach ((array)($times[$field] ?? array()) as $id => $date) {
                $timestamp = $date ? strtotime($date) : false;
                $result[$field][$id] = $timestamp !== false ? date('Y-m-d H:i:00', $timestamp) : $date;
            }
        }

        return $result;
    }

    public static function collectNewConflicts($scheduled, $baseline, $changedIds = array())
    {
        $conflicts = array();
        $changedIds = array_fill_keys($changedIds, true);
        foreach ((array)($scheduled->tasks ?? array()) as $plannedTasks) {
            foreach ($plannedTasks as $planned) {
                if (empty($planned->targetStartConflict)) continue;
                $id = $planned->id ?? null;
                $old = $baseline[$id] ?? null;
                $wasConflict = !empty($old->timeStart) && ($old->expectedTimeStart ?? null) != $old->timeStart;
                if (isset($changedIds[$id]) || !$wasConflict) $conflicts[$id] = $planned->targetStartConflict;
            }
        }
        return $conflicts;
    }

    public static function formatConflicts($conflicts)
    {
        $messages = array();
        foreach ($conflicts as $id => $conflict) {
            $label = $id < 0 ? 'Новата операция' : "Opr{$id}";
            $message = $label . ': ' . ($conflict['reason'] ?? 'конфликт в плана');
            if (!empty($conflict['earliest'])) $message .= '. Най-ранно намерено безопасно начало: ' . dt::mysql2verbal($conflict['earliest'], 'd.m.Y H:i');
            $messages[] = $message;
        }
        return implode('<br>', $messages);
    }

    /**
     * Изчислява предложението изцяло в паметта, включително при създаване.
     */
    public static function simulate($rec, $policy = 'keep')
    {
        $baseline = planning_TaskConstraints::getDefaultArr();
        $tasks = array();
        foreach ($baseline as $id => $task) $tasks[$id] = clone $task;
        $id = $rec->id ?? -1;
        $task = isset($tasks[$id]) ? clone $tasks[$id] : clone $rec;
        foreach (get_object_vars($rec) as $name => $value) $task->{$name} = $value;
        $task->id = $id;
        $job = planning_Jobs::fetch(array("#containerId = '[#1#]'", $task->originId ?? 0));
        $task->dueDate = $job->dueDate ?? null;
        $task->jobProductId = $job->productId ?? null;
        if (empty($task->saoOrder)) {
            $query = planning_Tasks::getQuery();
            $query->where(array("#originId = '[#1#]'", $task->originId ?? 0));
            $query->XPR('lastOrder', 'int', 'MAX(#saoOrder)');
            $query->show('lastOrder');
            $order = $query->fetch()->lastOrder ?? 0;
            $task->saoOrder = $order + 1;
        }
        $task->progress = $task->progress ?? 0;
        $old = !empty($rec->id) ? planning_Tasks::fetch($rec->id) : null;
        if (!empty($task->plannedQuantity) && is_object($old) && ($task->plannedQuantity ?? null) != ($old->plannedQuantity ?? null)) {
            $task->progress = round((($old->totalQuantity ?? 0) - ($old->scrappedQuantity ?? 0)) / $task->plannedQuantity, 2);
        }
        $task->state = in_array($task->state ?? null, array('active', 'wakeup', 'stopped')) ? $task->state : 'pending';
        $task->simultaneity = $task->simultaneity ?? null;
        $task->indTime = $task->indTime ?? null;
        $task->indPackagingId = $task->indPackagingId ?? ($task->measureId ?? null);
        $task->labelPackagingId = $task->labelPackagingId ?? null;
        $task->labelQuantityInPack = $task->labelQuantityInPack ?? null;
        $task->isFinal = $task->isFinal ?? 'no';
        $task->actualStart = $task->actualStart ?? null;
        $task->previousTask = $task->previousTask ?? null;
        $task->offsetAfter = $task->offsetAfter ?? 0;
        $task->_newPlanningActions = static::getNewPlanningActions($task, $old);
        $durationTasks = array($id => $task);
        planning_TaskConstraints::calculateTaskDurations($durationTasks);
        $tasks[$id] = $task;
        $previous = array();
        foreach (planning_TaskConstraints::calculateTaskConstraints($tasks) as $constraint) {
            if (($constraint->type ?? null) == 'prevId') $previous[$constraint->taskId][$constraint->previousTaskId] = $constraint;
        }

        $manualRecords = planning_TaskManualOrderPerAssets::getQuery()->fetchAll();
        $options = $changes = array();
        $required = planning_TaskConstraints::getSameResourceJobPackageLinks($tasks);
        foreach ($manualRecords as $manual) {
            $assetId = $manual->assetId ?? null;
            if ($assetId != ($task->assetId ?? null)) continue;
            $order = array_values((array)($manual->data ?? array()));
            $links = (array)($manual->packageLinks ?? array());
            $anchors = (array)($manual->anchorLinks ?? array());
            $excluded = (array)($manual->excludedAutoGroupTasks ?? array());
            if (in_array($policy, array('detach', 'release'))) {
                if (isset($required[$id]) || in_array($id, $required)) {
                    return (object)array('error' => 'Операцията е част от задължителен технологичен пакет и не може да бъде извадена от него');
                }
                $previousId = $links[$id] ?? null;
                unset($links[$id], $anchors[$id]);
                foreach ($links as $nextId => $prevId) {
                    if ($prevId != $id) continue;
                    if ($previousId) $links[$nextId] = $previousId;
                    else unset($links[$nextId]);
                }
                foreach ($anchors as $nextId => $prevId) if ($prevId == $id) unset($anchors[$nextId]);
                $order = array_values(array_diff($order, array($id)));
                $order[] = $id;
                $excluded[$id] = $id;
            }
            if (in_array($policy, array('split', 'release')) && !empty($task->timeStart)) {
                foreach ($links as $nextId => $prevId) {
                    $prevEnd = $baseline[$prevId]->expectedTimeEnd ?? null;
                    $nextEnd = $baseline[$nextId]->expectedTimeEnd ?? null;
                    if ($prevEnd && $nextEnd && $prevEnd <= $task->timeStart && $nextEnd > $task->timeStart
                        && $prevId != $id && $nextId != $id && !isset($required[$nextId])) {
                        unset($links[$nextId]);
                        $excluded[$nextId] = $nextId;
                    }
                }
            }
            if ($links != (array)($manual->packageLinks ?? array()) || $anchors != (array)($manual->anchorLinks ?? array()) || $order != array_values((array)($manual->data ?? array()))) {
                $changes[$assetId] = array('order' => $order, 'links' => $links, 'anchors' => $anchors, 'excluded' => $excluded);
                $options['manualOrderOverrides'][$assetId] = $order;
                $options['packageLinkOverrides'][$assetId] = $links;
                $options['anchorLinkOverrides'][$assetId] = $anchors;
                $options['excludedAutoGroupTaskOverrides'][$assetId] = $excluded;
            }
        }
        $now = dt::now();
        $source = array();
        foreach ($baseline as $taskId => $stored) {
            $source[$taskId] = get_object_vars($stored);
            foreach (array('expectedTimeStart', 'expectedTimeEnd', 'gapData', 'planningError', 'targetStartConflict', 'orderByAssetId') as $field) unset($source[$taskId][$field]);
        }
        $input = get_object_vars($rec);
        foreach (array('expectedTimeStart', 'expectedTimeEnd', 'gapData', 'planningError', 'targetStartConflict', 'orderByAssetId') as $field) unset($input[$field]);
        $snapshot = sha1(serialize(array($source, $input, $manualRecords, $policy)));
        $scheduled = planning_TaskConstraints::calcScheduledTimes($tasks, $previous, $now, $options);
        $conflicts = static::collectNewConflicts($scheduled, $baseline, array($id));

        return (object)array(
            'scheduled' => $scheduled, 'baseline' => $baseline, 'tasks' => $tasks,
            'changes' => $changes, 'conflicts' => $conflicts, 'now' => $now,
            'hash' => $snapshot,
        );
    }

    /**
     * Новите планиращи действия се включват още преди създаването на записа.
     */
    private static function getNewPlanningActions($rec, $old)
    {
        if (is_object($old) && !in_array($old->state ?? null, array('draft', 'waiting'))) return array();
        $driver = cat_Products::getDriver($rec->productId ?? null);
        if (!$driver || empty($rec->assetId)) return array();
        $data = $driver->getProductionData($rec->productId);
        $allowed = planning_AssetResourcesNorms::getNormOptions($rec->assetId, array(), true);
        $result = array();
        foreach ((array)($data['actions'] ?? array()) as $productId) {
            if (!in_array($productId, $allowed)) continue;
            if (!empty($rec->id) && $rec->id > 0 && planning_ProductionTaskProducts::fetchField(array("#taskId = '[#1#]' AND #type = 'input' AND #productId = '[#2#]'", $rec->id, $productId))) continue;
            $norm = planning_AssetResources::getNormRec($rec->assetId, $productId);
            if ($norm) $result[$productId] = planning_type_ProductionRate::getInSecsByQuantity($norm->indTime ?? null, 1);
        }

        return $result;
    }

    /**
     * Приемането потвърждава свеж преглед на същите данни, без междинен запис.
     */
    public static function validateForm($mvc, $form)
    {
        $rec = $form->rec;
        $old = !empty($rec->id) && empty($form->_cloneForm) ? $mvc->fetch($rec->id) : null;
        if (is_object($old) && in_array($old->state ?? null, array('active', 'wakeup', 'closed'))) {
            foreach (array('timeStart', 'timeDuration') as $field) {
                if (($rec->{$field} ?? null) != ($old->{$field} ?? null)) $form->setError($field, 'Целевите времена не могат да се променят при започнала или приключена операция. Спрете операцията, за да ги коригирате.');
            }
            return;
        }
        $changed = ($old->timeStart ?? null) != ($rec->timeStart ?? null)
            || ($old->timeDuration ?? null) != ($rec->timeDuration ?? null);
        if (!$changed && empty($rec->timeStart)) return;
        if (static::needsDurationWarning($old->timeDuration ?? null, $rec->timeDuration ?? null, $old->state ?? null)) {
            $form->setWarning('timeDuration', 'Целевата продължителност е за цялата операция при 0% прогрес, без планиращите действия, а не само за оставащата работа. Проверете въведената стойност.');
        }
        if ($form->gotErrors()) return;
        if (empty($rec->assetId)) {
            if (!empty($rec->timeStart)) $form->setError('assetId,timeStart', 'За точно целево начало трябва да бъде избрана машина с работен график');
            if ($changed && empty($rec->timeStart)) $rec->_targetTimesChanged = true;
            return;
        }
        $policy = $rec->targetPackagePolicy ?? 'keep';
        $canChangePackages = $mvc->haveRightFor('savereordertasks', (object)array('assetId' => $rec->assetId));
        if ($policy != 'keep' && !$canChangePackages) {
            $form->setError('targetPackagePolicy', 'Промяната на пакетните връзки изисква право за подреждане на операции');
            return;
        }
        $input = clone $rec;
        unset($input->targetPreviewHash, $input->targetPreviewAccepted, $input->targetPackagePolicy, $input->_fromForm);
        $simulation = static::simulate($input, $policy);
        $conflicts = $simulation->conflicts ?? array();
        if (!empty($simulation->error) || $conflicts) {
            $messages = array();
            if (!empty($simulation->error)) $messages[] = $simulation->error;
            if ($conflicts) $messages[] = static::formatConflicts($conflicts);
            if ($canChangePackages) $form->setField('targetPackagePolicy', 'input');
            $form->setError('timeStart,timeDuration', implode('<br>', $messages));
            $rec->targetPreviewAccepted = 'no';
            return;
        }
        if (!$changed && !$simulation->changes) return;
        if (($rec->targetPreviewAccepted ?? null) == 'yes' && ($rec->targetPreviewHash ?? null) == $simulation->hash) {
            $rec->_targetTimesChanged = true;
            $rec->_targetPackageChanges = $simulation->changes;
            return;
        }
        $rec->targetPreviewHash = $simulation->hash;
        $rec->targetPreviewAccepted = 'no';
        $report = planning_Tasks::getTargetTimesPreviewReport($simulation->tasks, $simulation->scheduled, $simulation->now, $rec->assetId, $simulation->baseline);
        $report['command'] = $form->cmd ?? 'save';
        $report['cancelUrl'] = toUrl(getRetUrl());
        $report['ignoreWarnings'] = !empty($form->ignore);
        foreach ((array)($simulation->scheduled->tasks[$rec->assetId] ?? array()) as $planned) {
            if (($planned->id ?? null) != ($rec->id ?? -1)) continue;
            $report['operationText'] = 'Предложение за операцията: начало ' . dt::mysql2verbal($planned->expectedTimeStart, 'd.m.Y H:i')
                . ', край ' . dt::mysql2verbal($planned->expectedTimeEnd, 'd.m.Y H:i');
            break;
        }
        $form->_targetTimesReport = $report;
        $form->setError('timeStart,timeDuration', 'Въведени/променени са целевите начало и/или продължителност на операцията! Промяната им е с най-висок приоритет и ще промени подредбата както на текущата, така и на операциите около нея. Прегледайте резултата (новата подредба) и при необходимост нанесете необходимите корекции!');
    }

    public static function applyPackageChanges($rec)
    {
        foreach ((array)($rec->_targetPackageChanges ?? array()) as $assetId => $change) {
            foreach ($change['order'] as &$taskId) if ($taskId == -1) $taskId = $rec->id;
            unset($taskId);
            if (isset($change['excluded'][-1])) {
                unset($change['excluded'][-1]);
                $change['excluded'][$rec->id] = $rec->id;
            }
            planning_TaskManualOrderPerAssets::force($assetId, $change['order'], $change['links'], $change['anchors']);
            $manual = planning_TaskManualOrderPerAssets::fetch(array("#assetId = '[#1#]'", $assetId));
            if ($manual) {
                $manual->excludedAutoGroupTasks = $change['excluded'];
                planning_TaskManualOrderPerAssets::save($manual, 'excludedAutoGroupTasks');
            }
        }
    }

    /**
     * Показва фактическите времена само в списъка към заданието.
     */
    public static function getJobTime($rec)
    {
        if (($rec->state ?? null) == 'closed') {
            return array($rec->timeClosed ?? null, '#444', 'Реален край');
        }
        if (in_array($rec->state ?? null, array('active', 'wakeup', 'stopped'))) {
            $starts = array_filter(array($rec->activatedOn ?? null, $rec->firstProgress ?? null));
            return array($starts ? min($starts) : ($rec->actualStart ?? null), '#176b2c', 'Реално начало');
        }

        return array($rec->expectedTimeStart ?? null, '#68a541', 'Планирано начало');
    }

    /**
     * Известието се изпраща веднъж за епизод, включително след прочитането му.
     */
    public static function updateNotification($taskId)
    {
        if (Request::get('ajax_mode')) {
            core_CallOnTime::setOnce('planning_TargetTimes', 'NotifyConflict', $taskId, dt::addSecs(1));
            return;
        }
        $rec = planning_Tasks::fetch($taskId);
        if (!is_object($rec)) return;
        $url = array('planning_Tasks', 'single', $taskId, 'targetStartConflict' => 1);
        $conflict = $rec->targetStartConflict ?? null;
        if (empty($conflict) || in_array($rec->state ?? null, array('closed', 'rejected'))) {
            bgerp_Notifications::clear($url, '*');
            return;
        }
        if (!empty($conflict['notified'])) return;

        $users = array();
        foreach (array($rec->targetStartBy ?? null, $rec->modifiedBy ?? null, $rec->createdBy ?? null) as $userId) {
            if ($userId > 0 && core_Users::isActiveUserId($userId) && planning_Tasks::haveRightFor('single', $rec, $userId)) {
                $users[$userId] = $userId;
                break;
            }
        }
        if (!$users) {
            $users = core_Users::getByRole('planning') + core_Users::getByRole('ceo');
            foreach ($users as $userId => $value) {
                if (!core_Users::isActiveUserId($userId) || !planning_Tasks::haveRightFor('single', $rec, $userId)) unset($users[$userId]);
            }
        }
        $date = dt::mysql2verbal($conflict['requested'] ?? '', 'd.m.Y H:i');
        $message = "Opr{$taskId} - желаното начало {$date} не може да бъде спазено.";
        core_Users::forceSystemUser();
        try {
            foreach ($users as $userId => $value) {
                bgerp_Notifications::add($message, $url, $userId, 'warning', array('planning_Tasks', 'single', $taskId), true);
            }
        } finally {
            core_Users::cancelSystemUser();
        }
        if ($users) {
            $conflict['notified'] = true;
            $taskRec = (object) array('id' => $taskId, 'targetStartConflict' => $conflict);
            cls::get('planning_Tasks')->save_($taskRec, 'targetStartConflict');
        }
    }

    public static function callback_NotifyConflict($taskId)
    {
        static::updateNotification($taskId);
    }

    /**
     * Подробният хинт пази желаното начало видимо и при безопасно изместване.
     */
    public static function getStartHint($rec)
    {
        $requested = dt::mysql2verbal($rec->timeStart ?? '', 'd.m.Y H:i');
        $hint = "Зададено е желано начало: {$requested}";
        $conflict = $rec->targetStartConflict ?? null;
        if (!empty($conflict)) {
            $hint .= '. Не може да бъде спазено: ' . ($conflict['reason'] ?? 'конфликт в плана');
            $earliest = $conflict['earliest'] ?? null;
            $hint .= $earliest ? '. Най-ранно намерено безопасно начало: ' . dt::mysql2verbal($earliest, 'd.m.Y H:i') : '. Не е намерено начало в работния график.';
        }

        return $hint;
    }
}
