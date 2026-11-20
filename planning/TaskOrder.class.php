<?php


/**
 * Позициониране на производствените операции в заданието.
 *
 * @category  bgerp
 * @package   planning
 * @author    Stefan Arsov <stefan.arsof@gmail.com> with Codex
 * @license   GPL 3
 */
class planning_TaskOrder
{
    /**
     * Свързва операциите с етапите на рецептата на целевото задание.
     */
    public static function matchRecipe($templates, $tasks)
    {
        $byProduct = $matches = $used = array();
        foreach ($templates as $key => $stage) {
            if (!empty($stage->_dId) && isset($stage->productId)) {
                $byProduct[$stage->productId][$key] = trim($stage->subTitle ?? '');
            }
        }

        if (!$byProduct || !$tasks) return array();

        // При еднакви етапи първо се запазват изрично избраните редове и подзаглавия.
        // Разместването на операциите не бива да сменя съответствието им.
        ksort($tasks, SORT_NUMERIC);
        foreach ($tasks as $id => $task) {
            $key = $task->systemId ?? null;
            $stages = $byProduct[$task->productId ?? 0] ?? array();
            $subtitle = trim($task->subTitle ?? '');
            if (isset($key, $stages[$key]) && ($subtitle === '' || $stages[$key] === $subtitle || !in_array($subtitle, $stages, true))) {
                $matches[$id] = $key;
                $used[$key] = true;
            }
        }
        foreach (array(true, false) as $bySubtitle) {
            foreach ($tasks as $id => $task) {
                $stages = $byProduct[$task->productId ?? 0] ?? array();
                if (!$stages || isset($matches[$id])) continue;
                if ($bySubtitle) {
                    $subtitle = trim($task->subTitle ?? '');
                    if ($subtitle === '') continue;
                    $stages = array_filter($stages, function ($value) use ($subtitle) { return $value === $subtitle; });
                    if (!$stages) continue;
                }
                $free = array_diff_key($stages, $used);
                $key = array_key_first($free ?: $stages);
                $matches[$id] = $key;
                $used[$key] = true;
            }
        }

        return $matches;
    }


    /** Наличните операции за всеки ред във формата за добавяне. */
    public static function existingByTemplate($templates, $tasks)
    {
        $byTemplate = array();
        foreach (self::matchRecipe($templates, $tasks) as $id => $key) {
            $byTemplate[$key][$id] = $id;
        }
        foreach ($templates as $key => $template) {
            if (!empty($template->_dId)) continue;
            foreach ($tasks as $id => $task) {
                if ((isset($task->systemId) && $task->systemId == $key)
                    || (($task->productId ?? null) == ($template->productId ?? null) && ($task->subTitle ?? '') === ($template->subTitle ?? ''))) {
                    $byTemplate[$key][$id] = $id;
                }
            }
        }

        return $byTemplate;
    }


    /**
     * Вмъква само новия ред. Предходните етапи уточняват мястото в допустимия от рецептата интервал.
     */
    public static function insert($tasks, $task, $templates, $conditions)
    {
        $id = $task->id ?? PHP_INT_MAX;
        // Виртуалният ред получава последен избор измежду повтарящите се етапи.
        $matches = self::matchRecipe($templates, $tasks + array(PHP_INT_MAX => $task));
        $positions = array_flip(array_keys($templates));
        $position = isset($matches[PHP_INT_MAX]) ? $positions[$matches[PHP_INT_MAX]] : null;
        $previous = $next = $after = $before = null;
        list($afterStep, $beforeStep) = self::stepNeighbours($tasks, $task->productId ?? null, $conditions);
        $offset = 0;
        foreach (array_keys($tasks) as $otherId) {
            if (isset($position, $matches[$otherId])) {
                $candidate = $positions[$matches[$otherId]];
                if ($candidate <= $position && ($previous === null || $candidate >= $previous)) {
                    $previous = $candidate;
                    $after = $offset + 1;
                } elseif ($candidate > $position && ($next === null || $candidate < $next)) {
                    $next = $candidate;
                    $before = $offset;
                }
            }
            $offset++;
        }

        if (isset($after)) {
            $at = $after;
            if ($afterStep > $after && (!isset($before) || $afterStep <= $before)) {
                // Подсказката не може да прескочи друг вече поставен рецептурен ред.
                $gap = array_slice($tasks, $after, $afterStep - $after, true);
                if (!array_intersect_key($gap, $matches)) $at = $afterStep;
            }
        } else {
            $at = $before ?? $afterStep ?? $beforeStep ?? count($tasks);
        }

        $ids = array_keys($tasks);
        if (isset($task->previousTask, $tasks[$task->previousTask])) $at = array_search($task->previousTask, $ids) + 1;
        array_splice($ids, $at, 0, array($id));

        // В смесено задание добавянето мести само новия ред.
        if (self::isFixed($task)) return $ids;
        foreach ($tasks as $other) {
            if (self::isFixed($other)) return $ids;
        }
        $rows = array();
        foreach ($ids as $offset => $taskId) {
            $row = clone ($taskId == $id ? $task : $tasks[$taskId]);
            $row->saoOrder = $offset + 1;
            $rows[$taskId] = $row;
        }
        return self::order($rows, $conditions);
    }


    /** Граници според изискваните предходни и следващи етапи. */
    private static function stepNeighbours($tasks, $step, $conditions)
    {
        $after = $before = null;
        foreach (array_values($tasks) as $offset => $other) {
            $product = $other->productId ?? null;
            if (isset($conditions[$step][$product])) $after = $offset + 1;
            if ($before === null && isset($conditions[$product][$step])) $before = $offset;
        }
        return array($after, $before);
    }


    /**
     * Запазва реда на фиксираните операции. Свободните следват своите технологични предходници.
     */
    public static function order($tasks, $conditions, &$debug = null, $changedSteps = array())
    {
        foreach ($changedSteps as $step) {
            $moving = array_filter($tasks, function ($row) use ($step, $tasks) {
                return ($row->productId ?? null) == $step && !isset($tasks[$row->previousTask ?? null]) && !self::isFixed($row);
            });
            if (!$moving) continue;
            $others = array_diff_key($tasks, $moving);
            list($after, $before) = self::stepNeighbours($others, $step, $conditions);
            $at = $after ?? $before;
            if (isset($at)) $tasks = array_slice($others, 0, $at, true) + $moving + array_slice($others, $at, null, true);
        }
        $byStep = $dependencies = array();
        $hasFixed = false;
        foreach ($tasks as $id => $task) {
            $byStep[$task->productId ?? 0][$id] = $id;
            if (self::isFixed($task)) $hasFixed = true;
        }

        $lastFixed = null;
        $hard = array();
        foreach ($tasks as $id => $task) {
            $previous = $task->previousTask ?? null;
            $hard[$id] = isset($tasks[$previous]) ? array($previous => $previous) : array();
            if ($hasFixed && self::isFixed($task)) {
                // Ръчното преместване е отразено в реда преди автоматичното подреждане.
                if (isset($lastFixed)) $hard[$id][$lastFixed] = $lastFixed;
                $pre = $hard[$id];
                $lastFixed = $id;
            } elseif (isset($tasks[$previous])) {
                $pre = array($previous => $previous);
            } else {
                $pre = array();
                if (($task->isFinal ?? null) === 'yes') {
                    foreach ($tasks as $otherId => $other) {
                        if (($other->saoOrder ?? null) < ($task->saoOrder ?? null)) $pre[$otherId] = $otherId;
                    }
                }
                foreach ($conditions[$task->productId ?? 0] ?? array() as $stepId => $condition) {
                    $pre += $byStep[$stepId] ?? array();
                }
            }
            unset($pre[$id]);
            $dependencies[$id] = $pre;
            if (is_array($debug)) $debug[$id] = array('previousTask' => $previous, 'isFinal' => $task->isFinal ?? 'no', 'useOrderFields' => $pre);
        }

        if (count($tasks) < 2) return array_keys($tasks);
        if (!$hasFixed) {
            $strategy = planning_Setup::get('SORT_TASKS_IN_JOB_STRATEGY');
            return cls::getInterface('planning_OrderTasksInJobStrategyIntf', $strategy)->order($dependencies);
        }

        // Избира се първата готова операция в текущия ред, за да останат независимите редове на местата си.
        $order = array();
        while ($dependencies) {
            foreach ($dependencies as $id => $pre) {
                if (!array_intersect_key($pre, $dependencies)) {
                    $order[] = $id;
                    unset($dependencies[$id]);
                    continue 2;
                }
            }
            // При конфликт първо се спазват ръчните връзки и веригата на защитените редове.
            foreach ($dependencies as $id => $pre) {
                if (!array_intersect_key($hard[$id], $dependencies)) {
                    $order[] = $id;
                    unset($dependencies[$id]);
                    continue 2;
                }
            }
            return array_merge($order, array_keys($dependencies));
        }

        return $order;
    }


    /** Неразрешим цикъл между свободни етапи не трябва да обръща реда при всяко планиране. */
    public static function improvesOrder($tasks, $order, $conditions)
    {
        if (array_keys($tasks) === $order) return false;
        $positions = array_flip($order);
        $gain = 0;
        foreach ($tasks as $id => $row) {
            if (self::isFixed($row) || isset($tasks[$row->previousTask ?? null])) continue;
            foreach ($tasks as $otherId => $other) {
                if (!isset($conditions[$row->productId ?? 0][$other->productId ?? 0])) continue;
                $gain += (int)(($other->saoOrder ?? 0) > ($row->saoOrder ?? 0)) - (int)($positions[$otherId] > $positions[$id]);
            }
        }
        return $gain > 0;
    }


    public static function isFixed($task)
    {
        return in_array($task->jobOrderMode ?? null, array('recipe', 'fixed'), true) || ($task->state ?? null) === 'closed';
    }
}
