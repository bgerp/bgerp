<?php

/**
 * Безопасни предложения за целево начало върху един прочетен график.
 */
class planning_TargetTimePlan extends core_BaseClass
{
    const MAX_NEAREST_TRIALS = 24;

    public static function propose($tasks, $previous, $baseline, $prepared, $options, $id, $mode, $now)
    {
        $assetId = $tasks[$id]->assetId ?? null;
        $calendar = $prepared->calendarIntervals[$assetId] ?? null;
        if (!$calendar || empty(($tasks[$id]->timeStart ?? ''))) {
            return static::trial($tasks, $previous, $baseline, $prepared, $options, $id, $now);
        }
        $calendar = clone $calendar;
        $asset = $prepared->assets[$assetId] ?? (object)array();
        $links = $options['packageLinkOverrides'][$assetId] ?? ($asset->packageLinks ?? array());
        $anchors = $options['anchorLinkOverrides'][$assetId] ?? ($asset->anchorLinks ?? array());
        $required = planning_TaskConstraints::getSameResourceJobPackageLinks($tasks, $assetId);
        $effective = planning_TaskConstraints::mergeRequiredPackageLinks($required, $anchors + $links);
        $assetTasks = array_filter($tasks, function($task) use ($assetId) { return ($task->assetId ?? null) == $assetId; });
        uasort($assetTasks, function($a, $b) use ($baseline) {
            return strcmp($baseline[$a->id ?? null]->expectedTimeStart ?? '', $baseline[$b->id ?? null]->expectedTimeStart ?? '')
                ?: (($a->id ?? 0) <=> ($b->id ?? 0));
        });
        $order = array_values(array_unique(array_merge((array)($options['manualOrderOverrides'][$assetId] ?? ($asset->manualOrder ?? array())), array_keys($assetTasks))));
        $order = array_values(array_intersect($order, array_keys($assetTasks)));
        $blocks = static::blocks($order, $effective);
        $ownKey = null;
        foreach ($blocks as $key => $chain) if (in_array($id, $chain)) $ownKey = $key;
        if ($ownKey === null) return (object)array('error' => 'Не е намерена операцията в подредбата на машината');
        $own = $blocks[$ownKey];
        $ownStarted = false;
        foreach ($own as $taskId) if (static::started($tasks[$taskId])) $ownStarted = true;
        $busy = array();
        foreach ($assetTasks as $taskId => $task) {
            if (static::started($task)) {
                $row = $prepared->tasks[$assetId][$taskId] ?? null;
                if (is_object($row)) {
                    $begin = $row->_startTimestamp ?? strtotime($row->expectedTimeStart ?? '');
                    $end = isset($row->_endTimestamp) ? $row->_endTimestamp - 1 : strtotime($row->expectedTimeEnd ?? '') + 59;
                    if ($begin !== false && $end !== false && $end >= $begin) {
                        $calendar->cut($begin, $end);
                        $busy[] = array($begin, $end);
                    }
                }
            }
        }
        $requested = ($tasks[$id]->timeStart ?? '');
        $layout = static::layout($own, $tasks, $calendar, $now, $id, $requested);
        $ranges = array();
        foreach ($blocks as $key => $chain) {
            if ($key == $ownKey) continue;
            foreach ($chain as $taskId) {
                if (empty(($tasks[$taskId]->timeStart ?? ''))) continue;
                $range = static::layout($chain, $tasks, $calendar, $now, $taskId, ($tasks[$taskId]->timeStart ?? ''));
                if ($range) $ranges[$key] = $range;
                break;
            }
        }
        if ($mode == 'nearest') {
            return static::nearest($tasks, $previous, $baseline, $prepared, $options, $id, $now, $calendar, $blocks, $ownKey, $ranges, $busy);
        }
        $moved = array($ownKey => $layout);
        if ($mode == 'move') {
            if (!$layout) return (object)array('error' => 'Желаното начало не се побира в работния график или застъпва започната операция');
            foreach ($own as $taskId) {
                if (static::started($tasks[$taskId])) return (object)array('error' => 'Пакет със започната операция не може да бъде преместен');
                if ($taskId != $id && !empty(($tasks[$taskId]->timeStart ?? ''))) $tasks[$taskId] = clone $tasks[$taskId];
                if ($taskId != $id && !empty(($tasks[$taskId]->timeStart ?? ''))) $tasks[$taskId]->timeStart = ($layout[$taskId]->timeStart ?? '');
            }
            uasort($ranges, function($a, $b) { return (reset($a)->begin ?? 0) <=> (reset($b)->begin ?? 0); });
            // Изместват се само пресечените пакети и породената от тях верига.
            foreach ($ranges as $key => $range) {
                $begin = (reset($range)->begin ?? 0);
                $end = (end($range)->end ?? 0);
                $after = $begin;
                foreach ($moved as $other) {
                    if (!$other) continue;
                    if ((reset($other)->begin ?? 0) <= $end && (end($other)->end ?? 0) >= $begin) $after = max($after, (end($other)->end ?? 0) + 1);
                }
                if ($after == $begin) continue;
                foreach ($blocks[$key] as $taskId) {
                    if (static::started($tasks[$taskId])) return (object)array('error' => "Конфликтният пакет съдържа започната Opr{$taskId}");
                }
                for ($attempt = 0; $attempt <= count($moved); $attempt++) {
                    $range = static::forward($blocks[$key], $tasks, $calendar, $after);
                    if (!$range) break;
                    $nextAfter = $after;
                    foreach ($moved as $other) {
                        if ($other && (reset($other)->begin ?? 0) <= (end($range)->end ?? 0) && (end($other)->end ?? 0) >= (reset($range)->begin ?? 0)) {
                            $nextAfter = max($nextAfter, (end($other)->end ?? 0) + 1);
                        }
                    }
                    if ($nextAfter == $after) break;
                    $after = $nextAfter;
                }
                if (!$range) return (object)array('error' => 'Няма достатъчно работно време за изместване на конфликтния пакет');
                $moved[$key] = $range;
                foreach ($blocks[$key] as $taskId) {
                    if (empty(($tasks[$taskId]->timeStart ?? ''))) continue;
                    $tasks[$taskId] = clone $tasks[$taskId];
                    $tasks[$taskId]->timeStart = ($range[$taskId]->timeStart ?? '');
                }
            }
        }
        $options['manualOrderOverrides'][$assetId] = $ownStarted ? $order : static::place($blocks, $moved, $baseline, $requested, $tasks);
        return static::trial($tasks, $previous, $baseline, $prepared, $options, $id, $now);
    }

    private static function started($task)
    {
        return in_array($task->state ?? null, array('active', 'wakeup', 'closed'))
            || (!empty($task->actualStart) && ($task->state ?? null) != 'stopped');
    }

    private static function blocks($order, $links)
    {
        $next = array_flip($links);
        $blocks = $seen = array();
        foreach ($order as $id) {
            if (isset($seen[$id])) continue;
            $head = $id;
            $walked = array();
            while (isset($links[$head]) && in_array($links[$head], $order) && !isset($walked[$head])) {
                $walked[$head] = true;
                $head = $links[$head];
            }
            $chain = array();
            while (!isset($seen[$head]) && in_array($head, $order)) {
                $chain[] = $head;
                $seen[$head] = true;
                if (!isset($next[$head])) break;
                $head = $next[$head];
            }
            if ($chain) $blocks[] = $chain;
        }
        return $blocks;
    }

    private static function layout($chain, $tasks, $calendar, $now, $anchor, $date)
    {
        $copy = array();
        foreach ($chain as $id) {
            $copy[$id] = clone $tasks[$id];
            $copy[$id]->timeStart = $id == $anchor ? $date : null;
        }
        $probe = clone $calendar;
        $reservations = array();
        $result = planning_TargetTimes::reserveRun($chain, $copy, $probe, $reservations, $now);
        if ($result === false) return null;
        $layout = array();
        foreach ($result as $id => $row) {
            $begin = $row->_startTimestamp ?? 0;
            $end = ($row->_endTimestamp ?? 0) - 1;
            $layout[$id] = (object)array('begin' => $begin, 'end' => $end, 'timeStart' => date('Y-m-d H:i:s', $begin));
        }
        return $layout;
    }

    private static function forward($chain, $tasks, $calendar, $after)
    {
        $capacity = $duration = 0;
        foreach ($calendar->getFrame($after, PHP_INT_MAX) as $frame) $capacity += $frame[1] - $frame[0] + 1;
        foreach ($chain as $id) $duration += max(1, (int)($tasks[$id]->calcedCurrentDuration ?? 0));
        if ($capacity < $duration) return null;
        $probe = clone $calendar;
        $result = array();
        foreach ($chain as $id) {
            $range = $probe->consume(max(1, (int)($tasks[$id]->calcedCurrentDuration ?? 0)), $after);
            if (!is_array($range)) return null;
            $result[$id] = (object)array('begin' => $range[0], 'end' => $range[1], 'timeStart' => date('Y-m-d H:i:s', $range[0]));
            $after = $range[1] + 1;
        }
        return $result;
    }

    private static function place($blocks, $moved, $baseline, $requested, $tasks)
    {
        $result = array_diff_key($blocks, $moved);
        uasort($moved, function($a, $b) use ($requested) {
            return ($a ? (reset($a)->begin ?? 0) : strtotime($requested)) <=> ($b ? (reset($b)->begin ?? 0) : strtotime($requested));
        });
        foreach ($moved as $key => $layout) {
            $begin = $layout ? (reset($layout)->begin ?? 0) : strtotime($requested);
            $position = count($result);
            $index = 0;
            foreach ($result as $otherKey => $chain) {
                foreach ($chain as $taskId) {
                    if (static::started($tasks[$taskId])) { $index++; continue 2; }
                }
                $last = end($chain);
                $end = isset($moved[$otherKey]) && $moved[$otherKey]
                    ? (end($moved[$otherKey])->end ?? 0) : strtotime($baseline[$last]->expectedTimeEnd ?? '');
                if ($end !== false && $end >= $begin) { $position = $index; break; }
                $index++;
            }
            $result = array_slice($result, 0, $position, true) + array($key => $blocks[$key]) + array_slice($result, $position, null, true);
        }
        $order = array();
        foreach ($result as $chain) foreach ($chain as $id) $order[] = $id;
        return $order;
    }

    private static function nearest($tasks, $previous, $baseline, $prepared, $options, $id, $now, $calendar, $blocks, $ownKey, $ranges, $busy)
    {
        $requested = strtotime($tasks[$id]->timeStart ?? '');
        $own = $blocks[$ownKey];
        $candidates = array();
        $anchored = false;
        foreach ($own as $taskId) {
            if ($taskId == $id || empty(($tasks[$taskId]->timeStart ?? ''))) continue;
            $anchored = true;
            $layout = static::layout($own, $tasks, $calendar, $now, $taskId, ($tasks[$taskId]->timeStart ?? ''));
            if ($layout) $candidates[($layout[$id]->timeStart ?? '')] = true;
        }
        if (!$anchored) {
            $occupied = $busy;
            foreach ($ranges as $range) $occupied[] = array((reset($range)->begin ?? 0), (end($range)->end ?? 0));
            usort($occupied, function($a, $b) { return $a[0] <=> $b[0]; });
            $begin = strtotime($now);
            $occupied[] = array(PHP_INT_MAX, PHP_INT_MAX);
            foreach ($occupied as $range) {
                $frames = $range[0] > $begin ? $calendar->getFrame($begin, $range[0] - 1) : array();
                if ($frames) {
                    $window = new core_Intervals();
                    $duration = 0;
                    foreach ($own as $taskId) $duration += max(1, (int)($tasks[$taskId]->calcedCurrentDuration ?? 0));
                    foreach ($frames as $frame) $window->add($frame[0], $frame[1]);
                    $first = static::forward($own, $tasks, $window, $begin);
                    if ($first) $candidates[($first[$id]->timeStart ?? '')] = true;
                    $left = $duration;
                    $last = null;
                    foreach (array_reverse($frames) as $frame) {
                        $take = min($left, $frame[1] - $frame[0] + 1);
                        $last = $frame[1] - $take + 1;
                        $left -= $take;
                        if (!$left) break;
                    }
                    if (!$left && $last !== null) {
                        $layout = static::forward($own, $tasks, $window, $last);
                        if ($layout) $candidates[($layout[$id]->timeStart ?? '')] = true;
                    }
                    $layout = static::layout($own, $tasks, $window, $now, $id, date('Y-m-d H:i:s', $requested));
                    if ($layout) $candidates[($layout[$id]->timeStart ?? '')] = true;
                    $edges = array();
                    foreach ($frames as $frame) {
                        $edges[(int)(ceil($frame[0] / 60) * 60)] = true;
                        $edges[(int)(floor($frame[1] / 60) * 60)] = true;
                    }
                    uksort($edges, function($a, $b) use ($requested) { return abs($a - $requested) <=> abs($b - $requested); });
                    foreach (array_slice(array_keys($edges), 0, self::MAX_NEAREST_TRIALS) as $edge) {
                        $layout = static::layout($own, $tasks, $window, $now, $id, date('Y-m-d H:i:s', $edge));
                        if ($layout) $candidates[($layout[$id]->timeStart ?? '')] = true;
                    }
                }
                $begin = max($begin, $range[1] == PHP_INT_MAX ? PHP_INT_MAX : $range[1] + 1);
            }
        }
        $attempts = 0;
        $tried = array();
        while ($candidates && $attempts++ < self::MAX_NEAREST_TRIALS) {
            uksort($candidates, function($a, $b) use ($requested) { return (abs(strtotime($a) - $requested) <=> abs(strtotime($b) - $requested)) ?: strcmp($a, $b); });
            $date = key($candidates);
            unset($candidates[$date]);
            if (isset($tried[$date])) continue;
            $tried[$date] = true;
            $copy = $tasks;
            $copy[$id] = clone $tasks[$id];
            $copy[$id]->timeStart = $date;
            $result = static::propose($copy, $previous, $baseline, $prepared, $options, $id, 'keep', $now);
            if (empty($result->error) && empty($result->conflicts)) return $result;
            $earliest = $result->conflicts[$id]['earliest'] ?? null;
            if (!$anchored && $earliest && !isset($tried[$earliest])) $candidates[$earliest] = true;
            if (!$anchored) foreach ((array)($result->conflicts ?? array()) as $taskId => $conflict) {
                if ($taskId == $id || empty($conflict['earliest']) || empty($conflict['requested'])) continue;
                $delay = strtotime($conflict['earliest']) - strtotime($conflict['requested']);
                $earlier = strtotime($date) - $delay;
                if ($delay > 0 && $earlier >= strtotime($now)) {
                    $candidate = date('Y-m-d H:i:s', $earlier);
                    if (!isset($tried[$candidate])) $candidates[$candidate] = true;
                }
            }
        }
        return (object)array('error' => 'Не е намерено близко допустимо начало, което запазва останалите фиксирани начала, пакетите и технологичните зависимости');
    }

    private static function trial($tasks, $previous, $baseline, $prepared, $options, $id, $now)
    {
        $scheduled = planning_TaskConstraints::calcPreparedTimes($tasks, $previous, $now, $prepared, $options);
        foreach ((array)($options['manualOrderOverrides'] ?? array()) as $assetId => $order) {
            $asset = $scheduled->assets[$assetId] ?? (object)array();
            foreach (array('packageLinkOverrides' => 'packageLinks', 'anchorLinkOverrides' => 'anchorLinks') as $key => $field) {
                $expected = (array)($options[$key][$assetId] ?? ($prepared->assets[$assetId]->{$field} ?? array()));
                $actual = (array)($asset->{$field} ?? array());
                foreach (array_diff_assoc($expected, $actual) as $next => $previousId) {
                    if (isset($tasks[$next], $tasks[$previousId]) && ($tasks[$next]->assetId ?? null) == $assetId
                        && ($tasks[$previousId]->assetId ?? null) == $assetId) {
                        return (object)array('error' => "Подредбата би разкъсала непотвърдена връзка Opr{$previousId} → Opr{$next}");
                    }
                }
                $options[$key][$assetId] = $actual;
            }
            $options['excludedAutoGroupTaskOverrides'][$assetId] = $asset->excludedAutoGroupTasks ?? array();
        }
        $pinChanges = array();
        foreach ($tasks as $taskId => $task) {
            if ($taskId != $id && ($task->timeStart ?? null) != ($baseline[$taskId]->timeStart ?? null)) $pinChanges[$taskId] = $task->timeStart ?? null;
        }
        $changedIds = array_merge(array($id), array_keys($pinChanges));
        $scope = planning_TargetTimeApproval::scope($tasks, $baseline, $scheduled, $prepared, $previous, $id);
        $conflictBaseline = planning_TargetTimeApproval::baseline($baseline, $prepared->targetTimeBaseline ?? null);
        $conflicts = planning_TargetTimes::collectNewConflicts($scheduled, $conflictBaseline, $changedIds, $scope);
        return (object)array('scheduled' => $scheduled, 'tasks' => $tasks, 'options' => $options,
            'pinChanges' => $pinChanges, 'proposedStart' => $tasks[$id]->timeStart ?? null, 'conflicts' => $conflicts);
    }
}
