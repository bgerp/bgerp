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

    public static function collectNewConflicts($scheduled, $baseline, $changedIds = array(), $scope = null)
    {
        $conflicts = array();
        $changedIds = array_fill_keys($changedIds, true);
        foreach ((array)($scheduled->tasks ?? array()) as $plannedTasks) {
            foreach ($plannedTasks as $planned) {
                if (empty($planned->targetStartConflict)) continue;
                $id = $planned->id ?? null;
                if (is_array($scope) && !isset($scope[$id])) continue;
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
            $reason = $conflict['reason'] ?? 'конфликт в плана';
            $message = $label . ': ' . $reason;
            if (!empty($conflict['earliest'])) {
                // Причината е от отказания опит, а часът е от следващ план с освободени резервации.
                $message = $label . ': При първоначалния опит за запазване на фиксираните начала: ' . $reason;
                $message .= "\nВ отделен пробен план, след освобождаване на неспазимите фиксирани начала, {$label} е изчислена за "
                    . dt::mysql2verbal($conflict['earliest'], 'd.m.Y H:i')
                    . '. Този час е ориентир за търсенето, а не потвърдено предложение за запис.';
            }
            $messages[] = $message;
        }
        return implode('<br>', $messages);
    }

    /**
     * Обяснява причинната верига, без да смесва пакетната и технологичната връзка.
     */
    public static function describeDependencyConflict($targetId, $blockedId, $limits, $ready, $tasks, $assets, $chain, $requiredStart = null)
    {
        $label = function ($id) { return $id < 0 ? 'новата операция' : "Opr{$id}"; };
        $machine = function ($id) use ($tasks, $assets) {
            $assetId = $tasks[$id]->assetId ?? null;
            $asset = $assets[$assetId] ?? (object)array();
            $name = trim(($asset->code ?? '') . ' ' . ($asset->name ?? ''));
            return $name !== '' ? 'машина „' . $name . '“' : ($assetId ? "машина №{$assetId}" : 'неуказана машина');
        };
        $blocked = $label($blockedId);
        $parts = array('Желаното начало не може да се спази поради технологична зависимост.');
        if ($targetId != $blockedId) {
            $targetPosition = array_search($targetId, $chain);
            $blockedPosition = array_search($blockedId, $chain);
            $relation = $targetPosition !== false && $blockedPosition !== false
                ? 'е ' . ($blockedPosition < $targetPosition ? 'преди' : 'след') . ' ' . $label($targetId) . ' в същия пакет'
                : 'е в същия пакет като ' . $label($targetId);
            $jobNote = '';
            if (!empty($tasks[$targetId]->originId) && !empty($tasks[$blockedId]->originId)) $jobNote = ($tasks[$targetId]->originId ?? null) == ($tasks[$blockedId]->originId ?? null)
                ? ' Двете операции са от едно задание.' : ' Двете операции са от различни задания, но са обвързани в пакет.';
            $parts[] = "{$blocked} {$relation} на " . $machine($blockedId) . '.' . $jobNote . ' Операциите в пакета се планират заедно.';
        }
        if ($requiredStart && $requiredStart < planning_TaskConstraints::NOT_FOUND_DATE) $parts[] = 'За да се спази въведеният час за ' . $label($targetId)
            . ", {$blocked} трябва да започне на " . dt::mysql2verbal($requiredStart, 'd.m.Y H:i') . '.';
        foreach ($limits as $previousId => $limit) {
            $previous = $label($previousId);
            $sameJob = !empty($tasks[$blockedId]->originId) && !empty($tasks[$previousId]->originId)
                && ($tasks[$blockedId]->originId ?? null) == ($tasks[$previousId]->originId ?? null);
            $differentJob = !empty($tasks[$blockedId]->originId) && !empty($tasks[$previousId]->originId) && !$sameJob;
            $knownMachines = !empty($tasks[$blockedId]->assetId) && !empty($tasks[$previousId]->assetId);
            $sameMachine = $knownMachines && ($tasks[$blockedId]->assetId ?? null) == ($tasks[$previousId]->assetId ?? null);
            $parts[] = "{$blocked} зависи от {$previous} — предходна технологична операция"
                . ($sameJob ? ' в същото задание' : ($differentJob ? ' от друго задание' : ' по зададената технологична връзка'))
                . ', изпълнявана на ' . ($knownMachines ? ($sameMachine ? 'същата ' : 'друга ') : '') . $machine($previousId) . '.';
            $end = $limit['end'] ?? null;
            $parts[] = $end && $end < planning_TaskConstraints::NOT_FOUND_DATE
                ? "Очакван край на {$previous}: " . dt::mysql2verbal($end, 'd.m.Y H:i') . '.'
                : "За {$previous} все още няма изчислен край.";
            $waiting = max(0, (int)($limit['waitingTime'] ?? 0));
            $duration = array();
            $hours = (int)floor($waiting / 3600);
            $minutes = (int)floor(($waiting % 3600) / 60);
            if ($hours) $duration[] = $hours . ($hours == 1 ? ' час' : ' часа');
            if ($minutes) $duration[] = $minutes . ' мин.';
            if ($waiting % 60) $duration[] = ($waiting % 60) . ' сек.';
            $noOverlap = ($limit['intersect'] ?? 'yes') == 'no';
            $limitReady = $limit['ready'] ?? $ready;
            if ($noOverlap && $end && $limitReady < planning_TaskConstraints::NOT_FOUND_DATE) {
                $release = dt::addSecs(-$waiting, $limitReady);
                if (substr($release, 0, 16) != substr($end, 0, 16)) $parts[] = 'За следващата операция заетият интервал се освобождава в '
                    . dt::mysql2verbal($release, 'd.m.Y H:i') . '; изчакването се брои от този момент.';
            }
            $parts[] = $noOverlap
                ? "{$blocked} може да започне едва след приключването на {$previous}" . ($waiting ? ' и още ' . implode(' и ', $duration) . ' календарно изчакване' : '') . '.'
                : 'Зададената връзка допуска застъпване на двете операции' . ($waiting ? ', но изисква изчакване ' . implode(' и ', $duration) : '') . '.';
        }
        $parts[] = $ready < planning_TaskConstraints::NOT_FOUND_DATE
            ? "Само по това технологично условие {$blocked} не може да започне преди " . dt::mysql2verbal($ready, 'd.m.Y H:i') . '. Това все още не означава свободен час на машината.'
            : "Докато предходните операции нямат изчислено изпълнение, не може да се определи допустимо начало за {$blocked}.";
        return implode("\n", $parts);
    }

    /**
     * Изчислява предложението изцяло в паметта, включително при създаване.
     */
    public static function simulate($rec, $policy = 'keep', $resolution = 'keep', $context = null, $now = null)
    {
        if ($context === null) {
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
            $previous = static::getPredecessors($tasks);
            $manualRecords = planning_TaskManualOrderPerAssets::getQuery()->fetchAll();
            $now = $now ?? dt::now();
            // Двата плана имат общ часовник: изтичането на време не е последица от редакцията.
            $comparison = $baseline
                ? planning_TaskConstraints::calcScheduledTimes($baseline, static::getPredecessors($baseline), $now)
                : (object)array('tasks' => array());
            $prepared = planning_TaskConstraints::calcScheduledTimes($tasks, $previous, $now);
            $previous = $prepared->previousTasks ?? $previous;
            $context = (object)array('tasks' => $tasks, 'previous' => $previous, 'baseline' => $baseline,
                'manualRecords' => $manualRecords, 'prepared' => $prepared, 'comparison' => $comparison, 'now' => $now);
        }
        $tasks = array();
        foreach ($context->tasks ?? array() as $taskId => $stored) $tasks[$taskId] = clone $stored;
        $baseline = $context->baseline ?? array();
        $previous = $context->previous ?? array();
        $manualRecords = $context->manualRecords ?? array();
        $now = $context->now ?? dt::now();
        $id = $rec->id ?? -1;
        $task = $tasks[$id];
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
        $prepared = clone ($context->prepared ?? (object)array());
        $prepared->targetTimeBaseline = $context->comparison ?? null;
        $proposal = planning_TargetTimePlan::propose($tasks, $previous, $baseline, $prepared, $options, $id, $resolution, $now);
        if (!empty($proposal->error)) {
            $proposal->context = $context;
            return $proposal;
        }
        $options = $proposal->options ?? $options;
        foreach ((array)($options['manualOrderOverrides'] ?? array()) as $assetId => $order) {
            $asset = $context->prepared->assets[$assetId] ?? (object)array();
            $links = $options['packageLinkOverrides'][$assetId] ?? ($asset->packageLinks ?? array());
            $anchors = $options['anchorLinkOverrides'][$assetId] ?? ($asset->anchorLinks ?? array());
            $excluded = $options['excludedAutoGroupTaskOverrides'][$assetId] ?? ($asset->excludedAutoGroupTasks ?? array());
            if ($order != array_values((array)($asset->manualOrder ?? array())) || isset($changes[$assetId])) {
                $changes[$assetId] = array('order' => $order, 'links' => $links, 'anchors' => $anchors, 'excluded' => $excluded);
            }
        }
        $proposal->baseline = $baseline;
        $proposal->changes = $changes;
        $proposal->now = $now;
        $proposal->context = $context;
        $proposal->approval = planning_TargetTimeApproval::snapshot($proposal, $rec, $policy, $resolution);
        $proposal->hash = sha1(serialize($proposal->approval));
        return $proposal;
    }

    private static function getPredecessors($tasks)
    {
        if (planning_Setup::get('MANUAL_ORDER_IN_ASSET') == 'yes') return array();

        $previous = array();
        foreach (planning_TaskConstraints::calculateTaskConstraints($tasks) as $constraint) {
            if (($constraint->type ?? null) == 'prevId' && isset($constraint->taskId, $constraint->previousTaskId)) {
                $previous[$constraint->taskId][$constraint->previousTaskId] = $constraint;
            }
        }
        return $previous;
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
        if (!in_array($policy, array('keep', 'detach', 'split', 'release'))) {
            $form->setError('targetPackagePolicy', 'Непознат вариант за пакетните връзки');
            return;
        }
        $canChangePackages = $mvc->haveRightFor('savereordertasks', (object)array('assetId' => $rec->assetId));
        if ($policy != 'keep' && !$canChangePackages) {
            $form->setError('targetPackagePolicy', 'Промяната на пакетните връзки изисква право за подреждане на операции');
            return;
        }
        $resolution = $rec->targetResolution ?? 'keep';
        if (!in_array($resolution, array('keep', 'move', 'nearest')) || ($resolution == 'move' && !$canChangePackages)) {
            $form->setError('timeStart', 'Нямате право за избрания вариант на подреждане');
            return;
        }
        $input = clone $rec;
        unset($input->targetPreviewHash, $input->targetPreviewAccepted, $input->targetPackagePolicy, $input->targetResolution, $input->_fromForm);
        $acceptedAttempt = ($rec->targetPreviewAccepted ?? null) == 'yes';
        $cached = $acceptedAttempt && !empty($rec->targetPreviewHash)
            ? core_Cache::get('TargetTimePreview', core_Users::getCurrent() . ':' . $rec->targetPreviewHash) : false;
        $previewNow = is_array($cached) ? ($cached['now'] ?? false) : false;
        $approvalChanges = array();
        $currentNow = dt::now();
        if ($previewNow && ($previewNow > $currentNow || strtotime($currentNow) - strtotime($previewNow) > 900)) $previewNow = false;
        $simulation = static::simulate($input, $policy, $resolution, null, $previewNow ?: null);
        // Потвърждението не може да резервира вече отминал час.
        if ($previewNow && !empty($simulation->proposedStart) && $simulation->proposedStart < $currentNow) {
            $previewNow = false;
            $simulation = static::simulate($input, $policy, $resolution);
            $approvalChanges[] = 'Предложеното начало вече е в миналото. Изберете актуално допустимо начало.';
        }
        if ($acceptedAttempt && !$previewNow && !$approvalChanges) $approvalChanges[] = 'Предишният преглед е изтекъл или вече не е наличен (валидност 15 минути). Прегледайте актуалния резултат.';
        if ($previewNow && ($rec->targetPreviewHash ?? null) != ($simulation->hash ?? null)) {
            $approvalChanges = isset($simulation->approval)
                ? planning_TargetTimeApproval::describeChanges($cached['snapshot'] ?? array(), $simulation->approval)
                : array('Предишният вариант вече не е допустим. Вижте конкретния конфликт по-долу.');
        }
        $rightsError = static::proposalRightsError($simulation, $canChangePackages);
        $conflicts = $simulation->conflicts ?? array();
        if (!empty($simulation->error) || $conflicts || $rightsError) {
            $messages = array();
            if (!empty($simulation->error)) $messages[] = $simulation->error;
            if ($conflicts) $messages[] = static::formatConflicts($conflicts);
            if ($rightsError) $messages[] = $rightsError;
            if ($canChangePackages) $form->setField('targetPackagePolicy', 'input');
            $form->setError('timeStart,timeDuration', htmlspecialchars(str_replace('<br>', '; ', implode('; ', $messages)), ENT_QUOTES, 'UTF-8'));
            if (!empty($rec->timeStart)) {
                $report = array('alternatives' => array(), 'conflictText' => str_replace('<br>', "\n", implode("\n", $messages)),
                    'command' => $form->cmd ?? 'save', 'ignoreWarnings' => !empty($form->ignore), 'approvalChanges' => $approvalChanges,
                    'requestedStart' => static::verbalTime($rec->timeStart ?? null),
                    'editedOperation' => ($rec->id ?? -1) < 0 ? 'Новата операция' : 'Opr' . ($rec->id ?? -1));
                $choices = array(
                    array('mode' => 'move', 'policy' => $policy, 'title' => 'Запази желания час и промени конфликтните фиксирани начала'),
                    array('mode' => 'nearest', 'policy' => $policy, 'title' => 'Запази чуждите фиксирани начала и предложи близко допустимо начало'),
                );
                $asset = $simulation->context->prepared->assets[$rec->assetId ?? null] ?? (object)array();
                $links = (array)($asset->packageLinks ?? array()) + (array)($asset->anchorLinks ?? array());
                $recId = $rec->id ?? -1;
                if ($canChangePackages && $policy == 'keep' && (isset($links[$recId]) || in_array($recId, $links))) {
                    $choices[] = array('mode' => 'keep', 'policy' => 'detach', 'title' => 'Извади операцията от пакета и запази желания час');
                }
                foreach ($choices as $choice) {
                    $candidate = static::simulate($input, $choice['policy'], $choice['mode'], $simulation->context ?? null);
                    $error = static::proposalRightsError($candidate, $canChangePackages);
                    $candidateConflicts = $candidate->conflicts ?? array();
                    if (!empty($candidate->error)) $error = $candidate->error;
                    if ($candidateConflicts) $error = str_replace('<br>', "\n", static::formatConflicts($candidateConflicts));
                    $choice['available'] = !$error;
                    $choice['reason'] = $error;
                    if (!$error) $choice['report'] = static::proposalReport($candidate, $rec, $form);
                    $report['alternatives'][] = $choice;
                }
                $form->_targetTimesReport = $report;
            }
            $rec->targetPreviewAccepted = 'no';
            return;
        }
        if (!$changed && empty($simulation->changes) && empty($simulation->pinChanges)
            && ($simulation->proposedStart ?? ($rec->timeStart ?? null)) == ($rec->timeStart ?? null)) return;
        if ($previewNow && ($rec->targetPreviewAccepted ?? null) == 'yes' && ($rec->targetPreviewHash ?? null) == ($simulation->hash ?? null)) {
            $rec->_targetTimesChanged = true;
            $rec->_targetPackageChanges = $simulation->changes ?? array();
            $rec->_targetStartChanges = $simulation->pinChanges ?? array();
            $rec->timeStart = $simulation->proposedStart ?? ($rec->timeStart ?? null);
            return;
        }
        $rec->targetPreviewHash = $simulation->hash ?? null;
        $rec->targetPreviewAccepted = 'no';
        $form->_targetTimesReport = static::proposalReport($simulation, $rec, $form);
        $form->_targetTimesReport['approvalChanges'] = $approvalChanges;
        $form->setError('timeStart,timeDuration', $approvalChanges
            ? 'Записът не е извършен. Необходимо е ново потвърждение: ' . implode(' ', $approvalChanges)
            : 'Въведени/променени са целевите начало и/или продължителност на операцията! Промяната им е с най-висок приоритет и ще промени подредбата както на текущата, така и на операциите около нея. Прегледайте резултата (новата подредба) и при необходимост нанесете необходимите корекции!');
    }

    private static function proposalRightsError($simulation, $canReorder)
    {
        if (!$canReorder && (!empty($simulation->changes) || !empty($simulation->pinChanges))) {
            return 'Промяната на подредбата изисква роля planning или ceo';
        }
        foreach ((array)($simulation->pinChanges ?? array()) as $taskId => $date) {
            $old = $simulation->baseline[$taskId] ?? null;
            if (!is_object($old) || !in_array($old->state ?? null, array('draft', 'waiting', 'pending', 'stopped'))
                || (!empty($old->actualStart) && ($old->state ?? null) != 'stopped')
                || !planning_Tasks::haveRightFor('edit', $old)) {
                return "Фиксираното начало на Opr{$taskId} не може да бъде променено с Вашите права или в текущото състояние";
            }
        }
        return '';
    }

    /**
     * Показва всяка промяна на час, фиксация, място или връзка преди потвърждение.
     */
    private static function proposalReport($simulation, $rec, $form)
    {
        $tasks = $simulation->tasks ?? array();
        $comparison = $simulation->context->comparison ?? null;
        $baseline = planning_TargetTimeApproval::baseline($simulation->baseline ?? array(), $comparison);
        $scheduled = $simulation->scheduled ?? (object)array();
        $report = planning_Tasks::getTargetTimesPreviewReport($tasks, $scheduled, $simulation->now ?? null, $rec->assetId ?? null, $baseline, $comparison);
        $report['command'] = $form->cmd ?? 'save';
        $report['ignoreWarnings'] = !empty($form->ignore);
        $report['hash'] = $simulation->hash ?? '';
        if (!empty($report['hash']) && !empty($simulation->now)) {
            core_Cache::set('TargetTimePreview', core_Users::getCurrent() . ':' . $report['hash'],
                array('now' => $simulation->now, 'snapshot' => $simulation->approval ?? array()), 15);
        }
        $report['requestedStart'] = static::verbalTime($rec->timeStart ?? null);
        $report['proposedStart'] = static::verbalTime($simulation->proposedStart ?? ($rec->timeStart ?? null));
        $report['editedOperation'] = ($rec->id ?? -1) < 0 ? 'Новата операция' : 'Opr' . ($rec->id ?? -1);
        $report['currentStart'] = static::verbalTime($baseline[$rec->id ?? -1]->expectedTimeStart ?? null);
        $report['operationChanges'] = array();
        $report['operationOrder'] = array();
        $oldRequired = planning_TaskConstraints::getSameResourceJobPackageLinks($baseline);
        $newRequired = planning_TaskConstraints::getSameResourceJobPackageLinks($tasks);
        $storedManuals = array();
        foreach ((array)($simulation->context->manualRecords ?? array()) as $manual) $storedManuals[$manual->assetId ?? null] = $manual;
        $beforeByAsset = array();
        foreach ($baseline as $taskId => $old) $beforeByAsset[$old->assetId ?? 0][$taskId] = $old;
        foreach ((array)($scheduled->tasks ?? array()) + $beforeByAsset as $assetId => $unused) {
            $rows = $scheduled->tasks[$assetId] ?? array();
            $asset = $simulation->context->prepared->assets[$assetId] ?? (object)array();
            $storedManual = $storedManuals[$assetId] ?? (object)array();
            // Сравняваме ефективните връзки от двата плана, включително автоматичните пакети.
            $oldAsset = $comparison->assets[$assetId] ?? $storedManual;
            $newAsset = $scheduled->assets[$assetId] ?? (object)array();
            $oldOrder = array_values((array)($oldAsset->manualOrder ?? ($storedManual->data ?? array())));
            $newOrder = array_values((array)($newAsset->manualOrder ?? ($simulation->options['manualOrderOverrides'][$assetId] ?? $oldOrder)));
            $oldPositions = array_flip($oldOrder);
            $newPositions = array_flip($newOrder);
            $oldPackages = $oldRequired + (array)($oldAsset->packageLinks ?? array());
            $newPackages = $newRequired + (array)($newAsset->packageLinks ?? ($simulation->options['packageLinkOverrides'][$assetId] ?? ($asset->packageLinks ?? array())));
            $oldAnchors = (array)($oldAsset->anchorLinks ?? array());
            $newAnchors = (array)($newAsset->anchorLinks ?? ($simulation->options['anchorLinkOverrides'][$assetId] ?? ($asset->anchorLinks ?? array())));
            $oldLinks = $oldRequired + $oldAnchors + $oldPackages;
            $newLinks = $newRequired + $newAnchors + $newPackages;
            $orderRows = array();
            $focus = array();
            $changedCount = $laterCount = $pinCount = $packageCount = $anchorCount = 0;
            $assetChanged = $assetId == ($rec->assetId ?? null);
            foreach (($beforeByAsset[$assetId] ?? array()) + $rows as $taskId => $unusedRow) {
                $row = $rows[$taskId] ?? (object)array();
                $old = $baseline[$taskId] ?? (object)array();
                $presentBefore = isset($beforeByAsset[$assetId][$taskId]);
                $presentAfter = isset($rows[$taskId]);
                $pinBefore = $old->timeStart ?? null;
                $pinAfter = $tasks[$taskId]->timeStart ?? null;
                $before = array(static::verbalTime($old->expectedTimeStart ?? null), static::verbalTime($old->expectedTimeEnd ?? null));
                $after = array(static::verbalTime($row->expectedTimeStart ?? null), static::verbalTime($row->expectedTimeEnd ?? null));
                $oldPosition = isset($oldPositions[$taskId]) ? $oldPositions[$taskId] + 1 : null;
                $newPosition = isset($newPositions[$taskId]) ? $newPositions[$taskId] + 1 : null;
                $entry = array('operation' => $taskId < 0 ? 'Новата операция' : "Opr{$taskId}",
                    'asset' => $asset->code ?? (string)$assetId, 'before' => $before, 'after' => $after,
                    'pinBefore' => static::verbalTime($pinBefore), 'pinAfter' => static::verbalTime($pinAfter),
                    'pinChanged' => $pinBefore != $pinAfter, 'positionBefore' => $oldPosition, 'positionAfter' => $newPosition,
                    'packageChanged' => ($oldPackages[$taskId] ?? null) != ($newPackages[$taskId] ?? null),
                    'anchorChanged' => ($oldAnchors[$taskId] ?? null) != ($newAnchors[$taskId] ?? null),
                    'linkBefore' => isset($oldLinks[$taskId]) ? 'Opr' . $oldLinks[$taskId] : '—',
                    'linkAfter' => isset($newLinks[$taskId]) ? 'Opr' . $newLinks[$taskId] : '—');
                $changed = $presentBefore != $presentAfter || $before != $after || $pinBefore != $pinAfter
                    || $oldPosition != $newPosition || $entry['packageChanged'] || $entry['anchorChanged'];
                $entry['changed'] = $changed;
                if ($changed || $taskId == ($rec->id ?? -1)) {
                    $focus[$taskId] = true;
                    $report['operationChanges'][] = $entry;
                    $assetChanged = true;
                }
                if ($changed) $changedCount++;
                if (static::timeDirection($old->expectedTimeEnd ?? null, $row->expectedTimeEnd ?? null) > 0) $laterCount++;
                if ($entry['pinChanged'] && $taskId != ($rec->id ?? -1)) $pinCount++;
                if ($entry['packageChanged']) $packageCount++;
                if ($entry['anchorChanged']) $anchorCount++;
                foreach (array('before', 'after') as $side) {
                    if ($side == 'before' ? !$presentBefore : !$presentAfter) continue;
                    $source = $side == 'before' ? $old : $row;
                    $links = $side == 'before' ? $oldLinks : $newLinks;
                    $pin = $side == 'before' ? $pinBefore : $pinAfter;
                    $orderRows[$side][] = array('id' => (int)$taskId, 'operation' => $entry['operation'],
                        'state' => ($side == 'before' ? ($old->state ?? null) : ($tasks[$taskId]->state ?? null)),
                        'start' => $entry[$side][0], 'end' => $entry[$side][1],
                        'sortStart' => $source->expectedTimeStart ?? planning_TaskConstraints::NOT_FOUND_DATE,
                        'position' => ($side == 'before' ? $oldPosition : $newPosition) ?? PHP_INT_MAX,
                        'pin' => static::verbalTime($pin), 'pinBefore' => $entry['pinBefore'], 'pinChanged' => $entry['pinChanged'],
                        'previous' => isset($links[$taskId]) ? (int)$links[$taskId] : null,
                        'startChange' => static::timeDirection($old->expectedTimeStart ?? null, $row->expectedTimeStart ?? null),
                        'endChange' => static::timeDirection($old->expectedTimeEnd ?? null, $row->expectedTimeEnd ?? null));
                }
            }
            if (!$assetChanged) continue;
            foreach (array('before', 'after') as $side) {
                $orderRows[$side] = $orderRows[$side] ?? array();
                usort($orderRows[$side], function ($a, $b) {
                    return strcmp($a['sortStart'], $b['sortStart']) ?: ($a['position'] <=> $b['position']) ?: ($a['id'] <=> $b['id']);
                });
            }
            // Един съсед от всяка страна дава контекст; останалите редове са разгъваеми.
            $visible = $focus;
            foreach ($orderRows as $sideRows) foreach ($sideRows as $index => $item) {
                if (empty($focus[$item['id']])) continue;
                foreach (array($index - 1, $index + 1) as $neighbor) {
                    if (isset($sideRows[$neighbor])) $visible[$sideRows[$neighbor]['id']] = true;
                }
            }
            foreach ($orderRows as &$sideRows) foreach ($sideRows as &$item) $item['focus'] = !empty($visible[$item['id']]);
            unset($sideRows, $item);
            $report['operationOrder'][] = array('asset' => trim(($asset->code ?? (string)$assetId) . ' ' . ($asset->name ?? '')),
                'assetId' => $assetId, 'edited' => $assetId == ($rec->assetId ?? null),
                'changedCount' => $changedCount, 'laterCount' => $laterCount, 'foreignPinCount' => $pinCount,
                'packageCount' => $packageCount, 'anchorCount' => $anchorCount,
                'before' => $orderRows['before'], 'after' => $orderRows['after']);
        }
        usort($report['operationOrder'], function ($a, $b) { return $b['edited'] <=> $a['edited']; });
        // Кратките номера започват от видимите редове на редактираната машина.
        $numbers = array();
        foreach ($report['operationOrder'] as $group) foreach (array('before', 'after') as $side) foreach ($group[$side] as $item) {
            if ($item['focus'] && !isset($numbers[$item['id']])) $numbers[$item['id']] = count($numbers) + 1;
        }
        foreach (array('before', 'after') as $side) foreach ($report['operationOrder'] as &$group) {
            foreach ($group[$side] as &$item) {
                if (!isset($numbers[$item['id']])) $numbers[$item['id']] = count($numbers) + 1;
                $item['number'] = $numbers[$item['id']];
                unset($item['sortStart'], $item['position']);
            }
            unset($item);
        }
        unset($group);
        return $report;
    }

    private static function timeDirection($before, $after)
    {
        if (!$before || !$after || max($before, $after) >= planning_TaskConstraints::NOT_FOUND_DATE) return null;
        return strcmp(substr($after, 0, 16), substr($before, 0, 16)) <=> 0;
    }

    private static function verbalTime($date)
    {
        return $date && $date < planning_TaskConstraints::NOT_FOUND_DATE ? dt::mysql2verbal($date, 'd.m.Y H:i') : '—';
    }

    public static function applyPackageChanges($rec)
    {
        foreach ((array)($rec->_targetStartChanges ?? array()) as $taskId => $date) {
            $saveRec = (object)array('id' => $taskId, 'timeStart' => $date, 'targetStartBy' => core_Users::getCurrent());
            cls::get('planning_Tasks')->save_($saveRec, 'timeStart,targetStartBy');
            planning_Tasks::logWrite('Промяна на целево начало след потвърден преглед', $taskId);
        }
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
