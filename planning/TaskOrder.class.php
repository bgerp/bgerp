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
     * Вмъква операция според рецептата и технологичните предходни.
     * При възстановяване ползва ръчните връзки на оттеглените операции от заданието
     * и актуализира режима auto/recipe на подадения запис според текущата рецепта.
     */
    public static function insert($tasks, $task, $templates, $conditions, $withdrawnPrevious = null)
    {
        $id = $task->id ?? PHP_INT_MAX;
        // Новият ред избира последен при еднакви етапи. Възстановеният запазва реда си на създаване.
        $matchId = isset($withdrawnPrevious) ? $id : PHP_INT_MAX;
        $matches = self::matchRecipe($templates, $tasks + array($matchId => $task));
        $positions = array_flip(array_keys($templates));
        $position = isset($matches[$matchId]) ? $positions[$matches[$matchId]] : null;
        if (isset($withdrawnPrevious) && ($task->state ?? null) !== 'closed'
            && in_array($task->jobOrderMode ?? null, array('auto', 'recipe'), true)) {
            $task->jobOrderMode = isset($position) ? 'recipe' : 'auto';
        }
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
        $manualAt = null;
        $previousId = $task->previousTask ?? null;
        if (isset($withdrawnPrevious)) {
            $previousId = self::skipWithdrawn($previousId, $withdrawnPrevious);
            if (!isset($tasks[$previousId])) {
                foreach ($ids as $offset => $otherId) {
                    if (self::skipWithdrawn($tasks[$otherId]->previousTask ?? null, $withdrawnPrevious) == $id) {
                        $manualAt = $offset;
                        break;
                    }
                }
            }
        }
        if (isset($tasks[$previousId])) $manualAt = array_search($previousId, $ids) + 1;
        array_splice($ids, $manualAt ?? $at, 0, array($id));

        // В смесено задание се запазва относителният ред на заварените операции.
        $keepOrder = self::isFixed($task) || isset($withdrawnPrevious, $manualAt);
        $rows = array();
        foreach ($ids as $offset => $taskId) {
            $row = clone ($taskId == $id ? $task : $tasks[$taskId]);
            $row->saoOrder = $offset + 1;
            $rows[$taskId] = $row;
            if (self::isFixed($row)) $keepOrder = true;
        }
        $finals = self::getFinalOrder($rows, $conditions);
        $regular = array_diff_key($rows, $finals);
        $order = ($keepOrder || isset($finals[$id])) ? array_keys($regular) : self::orderByPredecessors($regular, $conditions);

        return array_merge($order, array_keys($finals));
    }


    /** Следва само изрично зададените предходни. Старите данни могат да съдържат цикъл. */
    private static function skipWithdrawn($id, $withdrawnPrevious)
    {
        $seen = array();
        while (isset($withdrawnPrevious[$id]) && !isset($seen[$id])) {
            $seen[$id] = true;
            $id = $withdrawnPrevious[$id];
        }

        return $id;
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
    public static function order($tasks, $conditions, &$debug = null, $changedSteps = array(), &$finalsChanged = null)
    {
        $finals = self::getFinalOrder($tasks, $conditions);
        $finalsChanged = $finals && array_slice(array_keys($tasks), -count($finals)) !== array_keys($finals);
        $regular = array_diff_key($tasks, $finals);
        $sortRegular = !$changedSteps;
        if ($changedSteps) {
            foreach ($regular as $row) {
                if (!self::isFixed($row) && in_array($row->productId ?? null, $changedSteps)) {
                    $sortRegular = true;
                    break;
                }
            }
        }
        $order = $sortRegular ? self::orderByPredecessors($regular, $conditions, $debug, $changedSteps) : array_keys($regular);
        if (is_array($debug)) {
            foreach ($finals as $id => $position) {
                $debug[$id] = array('isFinal' => 'yes', 'finalPosition' => count($order) + $position + 1);
            }
        }

        return array_merge($order, array_keys($finals));
    }


    /**
     * Ръчна финална извън рецептата събира накрая финалните без рецептурно място.
     * Ключовете са ид-та, стойностите са позициите им във финалната група.
     */
    public static function getFinalOrder($tasks, $conditions)
    {
        $finals = array();
        $hasManualFinal = false;
        foreach ($tasks as $id => $row) {
            if (!self::isFinalOutsideRecipe($row) || ($row->state ?? null) === 'rejected') continue;
            $finals[$id] = $row;
            // Нулата също е валиден ред от шаблона.
            if (!isset($row->systemId) && empty($row->clonedFromId)) $hasManualFinal = true;
        }
        if (!$hasManualFinal || count($tasks) < 2) return array();
        if (count($finals) == 1) return array_flip(array_keys($finals));
        // Ид на операцията следва реда на добавяне. Виртуалният ред в прегледа е най-новият.
        uksort($finals, function ($a, $b) {
            return ($a < 0 ? PHP_INT_MAX : $a) <=> ($b < 0 ? PHP_INT_MAX : $b);
        });
        $nonStorable = $storable = array();
        foreach ($finals as $id => $row) {
            if (($row->canStore ?? null) === 'yes') {
                $storable[$id] = $row->productId ?? null;
            } else {
                $nonStorable[$id] = $row->productId ?? null;
            }
        }
        $finals = $nonStorable + $storable;

        // Транзитивните предходни запазват и веригите през трети финален етап.
        $before = array();
        foreach ($finals as $id => $step) {
            $before[$id] = array();
            foreach ($finals as $other => $otherStep) {
                if ($step != $otherStep && isset($conditions[$step][$otherStep])) $before[$id][$other] = true;
            }
        }
        if (!array_filter($before)) return array_flip(array_keys($finals));
        foreach ($finals as $via => $step) {
            foreach ($finals as $id => $otherStep) {
                if (isset($before[$id][$via])) $before[$id] += $before[$via];
            }
        }
        $required = $before;
        $neededByNonStorable = array();
        foreach ($before as $id => $predecessors) {
            foreach ($predecessors as $previous => $unused) {
                // Взаимно зависимите етапи се решават по складируемост и ред на добавяне.
                if (isset($before[$previous][$id])) unset($required[$id][$previous]);
            }
            if (array_key_exists($id, $nonStorable)) $neededByNonStorable += $required[$id];
        }
        $pending = $nonStorable + array_intersect_key($storable, $neededByNonStorable) + $storable;
        $order = array();
        while ($pending) {
            foreach ($pending as $id => $step) {
                if (array_intersect_key($required[$id], $pending)) continue;
                $order[$id] = count($order);
                unset($pending[$id]);
                break;
            }
        }
        return $order;
    }


    public static function isFinalOutsideRecipe($task)
    {
        return ($task->isFinal ?? null) === 'yes' && in_array($task->jobOrderMode ?? null, array('auto', 'fixedFinal'), true);
    }


    /** Недопустима предходна операция в задание с ръчно добавени финални етапи. */
    public static function conflictsWithFinalOrder($finals, $id, $previous)
    {
        return isset($finals[$previous]) && (!isset($finals[$id]) || $finals[$previous] >= $finals[$id]);
    }


    public static function orderByPredecessors($tasks, $conditions, &$debug = null, $changedSteps = array())
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
        return in_array($task->jobOrderMode ?? null, array('recipe', 'fixed', 'fixedFinal'), true) || ($task->state ?? null) === 'closed';
    }
}
