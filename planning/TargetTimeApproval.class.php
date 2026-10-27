<?php

/**
 * Потвърждава само относимата част на пресметнатия план, а не цялата база.
 */
class planning_TargetTimeApproval extends core_BaseClass
{
    const FIELDS = array(
        'state' => 'състояние', 'actualStart' => 'фактическо начало',
        'assetId' => 'машина', 'productId' => 'етап', 'originId' => 'задание',
        'saoOrder' => 'ред в заданието', 'previousTask' => 'предходна операция',
        'offsetAfter' => 'изчакване', 'dueDate' => 'падеж',
        'progress' => 'прогрес', 'plannedQuantity' => 'планирано количество',
        'indTime' => 'норма', 'simultaneity' => 'едновременност',
        'timeStart' => 'фиксирано начало', 'timeDuration' => 'целева продължителност',
        'calcedCurrentDuration' => 'оставаща продължителност',
        'measureId' => 'мярка', 'indPackagingId' => 'мярка на нормата',
        'labelPackagingId' => 'опаковка', 'labelQuantityInPack' => 'количество в опаковка',
        'isFinal' => 'финален етап', '_targetReadyAfter' => 'готовност след предходните операции',
        '_planningParamSignature' => 'планиращи параметри', '_newPlanningActions' => 'планиращи действия',
    );

    public static function baseline($baseline, $comparison)
    {
        if (!is_object($comparison)) return $baseline;
        foreach ($baseline as $id => $old) {
            $row = $comparison->tasks[$old->assetId ?? 0][$id] ?? null;
            $baseline[$id] = clone $old;
            foreach (array('expectedTimeStart', 'expectedTimeEnd', '_startTimestamp', '_endTimestamp', 'targetStartConflict') as $field) {
                $baseline[$id]->{$field} = $row->{$field} ?? null;
            }
        }
        return $baseline;
    }

    private static function flatten($scheduled, $tasks)
    {
        $result = array();
        foreach ((array)($scheduled->tasks ?? array()) as $rows) $result += $rows;
        foreach ($tasks as $id => $task) {
            if (!isset($result[$id])) $result[$id] = (object)array('assetId' => $task->assetId ?? null);
        }
        return $result;
    }

    private static function links($assets, $tasks)
    {
        $links = planning_TaskConstraints::getSameResourceJobPackageLinks($tasks);
        foreach ((array)$assets as $asset) {
            $links += (array)($asset->anchorLinks ?? array()) + (array)($asset->packageLinks ?? array())
                + (array)($asset->autoPackageLinks ?? array());
        }
        return $links;
    }

    private static function placement($row)
    {
        return array($row->assetId ?? null, $row->expectedTimeStart ?? null, $row->expectedTimeEnd ?? null,
            $row->_startTimestamp ?? null, $row->_endTimestamp ?? null);
    }

    /**
     * Чуждите опашки не се включват изцяло; проследяват се само реалните връзки и ефекти.
     */
    public static function scope($tasks, $baseline, $scheduled, $prepared, $previous, $id)
    {
        $before = static::baseline($baseline, $prepared->targetTimeBaseline ?? null);
        $after = static::flatten($scheduled, $tasks);
        $assets = array_filter(array($baseline[$id]->assetId ?? null, $tasks[$id]->assetId ?? null));
        $scope = array($id => true);
        foreach ($baseline + $tasks as $taskId => $task) {
            if (in_array($task->assetId ?? null, $assets)
                || in_array($tasks[$taskId]->assetId ?? null, $assets)
                || array_slice(static::placement($before[$taskId] ?? null), 0, 3) !== array_slice(static::placement($after[$taskId] ?? null), 0, 3)) $scope[$taskId] = true;
        }
        $edges = array();
        foreach ($previous as $next => $predecessors) foreach ($predecessors as $prev => $constraint) {
            $edges[$next][$prev] = $edges[$prev][$next] = true;
        }
        $links = static::links($prepared->assets ?? array(), $baseline);
        $newLinks = static::links($scheduled->assets ?? array(), $tasks);
        foreach (array($links, $newLinks) as $set) foreach ($set as $next => $prev) {
            $edges[$next][$prev] = $edges[$prev][$next] = true;
        }
        $queue = array_keys($scope);
        for ($i = 0; $i < count($queue); $i++) foreach ($edges[$queue[$i]] ?? array() as $related => $unused) {
            if (isset($scope[$related])) continue;
            $scope[$related] = true;
            $queue[] = $related;
        }
        return $scope;
    }

    private static function data($rec)
    {
        $result = array();
        foreach (self::FIELDS as $field => $label) $result[$field] = $rec->{$field} ?? null;
        return $result;
    }

    public static function snapshot($simulation, $rec, $policy, $resolution)
    {
        $tasks = $simulation->tasks ?? array();
        $baseline = $simulation->baseline ?? array();
        $scheduled = $simulation->scheduled ?? (object)array();
        $prepared = clone ($simulation->context->prepared ?? (object)array());
        $prepared->targetTimeBaseline = $simulation->context->comparison ?? null;
        $previous = $scheduled->previousTasks ?? ($simulation->context->previous ?? array());
        $scope = static::scope($tasks, $baseline, $scheduled, $prepared, $previous, $rec->id ?? -1);
        $before = static::baseline($baseline, $prepared->targetTimeBaseline);
        $after = static::flatten($scheduled, $tasks);
        $oldLinks = static::links($prepared->assets ?? array(), $baseline);
        $newLinks = static::links($scheduled->assets ?? array(), $tasks);
        $snapshot = array('policy' => $policy, 'resolution' => $resolution, 'operations' => array(), 'orders' => array());
        foreach ($scope as $id => $unused) {
            $snapshot['operations'][$id] = array(
                'dataBefore' => static::data($baseline[$id] ?? null), 'dataAfter' => static::data($tasks[$id] ?? null),
                'before' => static::placement($before[$id] ?? null), 'after' => static::placement($after[$id] ?? null),
                'previous' => $previous[$id] ?? array(), 'links' => array($oldLinks[$id] ?? null, $newLinks[$id] ?? null),
            );
        }
        foreach (array_unique(array_filter(array($baseline[$rec->id ?? -1]->assetId ?? null, $rec->assetId ?? null))) as $assetId) {
            $old = (array)($prepared->assets[$assetId]->manualOrder ?? array());
            $new = (array)($simulation->options['manualOrderOverrides'][$assetId] ?? $old);
            $snapshot['orders'][$assetId] = array(array_values(array_filter($old, function($id) use ($scope) { return isset($scope[$id]); })),
                array_values(array_filter($new, function($id) use ($scope) { return isset($scope[$id]); })));
        }
        return static::canonical($snapshot);
    }

    private static function canonical($value)
    {
        if (is_object($value)) $value = get_object_vars($value);
        if (!is_array($value)) return $value;
        foreach ($value as &$item) $item = static::canonical($item);
        unset($item);
        ksort($value);
        return $value;
    }

    public static function describeChanges($old, $new)
    {
        $messages = array();
        $oldRows = $old['operations'] ?? array();
        $newRows = $new['operations'] ?? array();
        foreach ($oldRows + $newRows as $id => $unused) {
            $label = $id < 0 ? 'Новата операция' : "Opr{$id}";
            if (!isset($oldRows[$id], $newRows[$id])) {
                $messages[] = "{$label}: променен е обхватът на засегнатите операции и зависимости.";
                continue;
            }
            $before = $oldRows[$id];
            $after = $newRows[$id];
            $fields = array();
            foreach (self::FIELDS as $field => $caption) foreach (array('dataBefore', 'dataAfter') as $side) {
                if (($before[$side][$field] ?? null) !== ($after[$side][$field] ?? null)) $fields[$field] = $caption;
            }
            if ($fields) $messages[] = "{$label}: променени данни: " . implode(', ', $fields) . '.';
            elseif (($before['previous'] ?? array()) !== ($after['previous'] ?? array())) $messages[] = "{$label}: променени са технологичните зависимости или времената за изчакване.";
            elseif (($before['links'] ?? array()) !== ($after['links'] ?? array())) $messages[] = "{$label}: променени са пакетните връзки или връзката с предходната операция.";
            elseif (($before['before'] ?? null) !== ($after['before'] ?? null) || ($before['after'] ?? null) !== ($after['after'] ?? null)) {
                $dates = array();
                foreach (array(1 => 'начало', 2 => 'край') as $key => $caption) {
                    $from = $before['after'][$key] ?? null;
                    $to = $after['after'][$key] ?? null;
                    if ($from != $to) $dates[] = $caption . ': ' . static::verbalTime($from) . ' → ' . static::verbalTime($to);
                }
                $messages[] = "{$label}: променено е планираното изпълнение" . ($dates ? ' (' . implode('; ', $dates) . ')' : '') . '.';
            }
        }
        foreach (($old['orders'] ?? array()) + ($new['orders'] ?? array()) as $id => $unused) {
            if (($old['orders'][$id] ?? null) !== ($new['orders'][$id] ?? null)) $messages[] = "Машина #{$id}: променена е ръчната подредба на засегнатите операции.";
        }
        if (!$messages) $messages[] = 'Променен е избраният вариант за целевото начало или пакетните връзки.';
        if (count($messages) > 6) return array_merge(array_slice($messages, 0, 6), array('Има и други промени. Прегледайте обновената подредба.'));
        return $messages;
    }

    private static function verbalTime($date)
    {
        return $date && $date < planning_TaskConstraints::NOT_FOUND_DATE ? dt::mysql2verbal($date, 'd.m.Y H:i') : '—';
    }
}
