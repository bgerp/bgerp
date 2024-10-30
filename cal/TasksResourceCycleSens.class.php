<?php


/**
 * Сензор за цикли на задачите с ресурси
 *
 * @category  bgerp
 * @package   cal
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Цикли на задачите с ресурси
 */
class cal_TasksResourceCycleSens extends sens2_ProtoDriver
{

    /**
     * Заглавие на драйвера
     */
    public $title = 'Цикли на задачите с ресурси';


    /**
     * Входове на контролера
     */
    public $inputs = array(
        'startAfter' => array('caption' => 'Начало след', 'uom' => 'min', 'logPeriod' => 0, 'readPeriod' => 60),
        'endAfter' => array('caption' => 'Край след', 'uom' => 'min', 'logPeriod' => 0, 'readPeriod' => 60),
        'lastClosed' => array('caption' => 'Последно приключен', 'uom' => 'min', 'logPeriod' => 0, 'readPeriod' => 60),
    );


    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @see  sens2_ControllerIntf
     *
     * @param core_Form
     */
    public function prepareConfigForm($form)
    {
        $form->FLD('resource', 'key(mvc=planning_AssetResources, select=name)', 'caption=Ресурс, input, mandatory');
        $form->FLD('timeDeviation', 'time(suggestions=30 мин.|1 час| 2 часа)', 'caption=Време при липса на начало или край->Време, input');
        $form->FLD('timeActiveAdd', 'time(suggestions=2 мин.|5 мин.|10 мин.)', 'caption=Време за добавяне към активността на задачата->Време, input');
    }


    /**
     * Прочитане на входовете
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        $timeDeviation = $config->timeDeviation; // Ако не е зададени начало или край на задача, да се изчисли от времето на другата
        $timeRound = $config->timeActiveAdd; // Закръгляне на времето

        $maxDays = 10; // Максимален брой дни за проверка
        $resArr = array();

        if (!$config->resource) {

            return $resArr;
        }

        $now = dt::now();
        $to = dt::addDays($maxDays, $now);

        $query = cal_Tasks::getQuery();
        $query->where(array("#assetResourceId = '[#1#]'", $config->resource));
        $query->where("#state = 'active' OR #state = 'pending' OR #state = 'waiting' OR #state = 'wakeup'");
//        $query->where("#state != 'draft' AND #state != 'rejected'"); // closed|stopped
        $query->where(array("#expectationTimeStart >= '[#1#]'", $now));
        $query->orWhere(array("#expectationTimeEnd >= '[#1#]'", $now));
        $query->orWhere("#timeStart IS NULL");
        $query->orWhere("#timeEnd IS NULL");
        $query->where(array("#expectationTimeStart <= '[#1#]'", $to));
        $query->orWhere(array("#expectationTimeEnd <= '[#1#]'", $to));
        $query->orWhere("#timeEnd IS NULL");
        $query->orderBy('expectationTimeStart', 'ASC');
        $query->orderBy('expectationTimeEnd', 'ASC');
        $query->orderBy('id', "DESC");

        $pWorkingInterval = planning_AssetResources::getWorkingInterval($config->resource);

        $endIn = null;
        $startIn = dt::addDays($maxDays, $now);
        while ($rec = $query->fetch()) {
            if (!$rec->timeStart && $rec->timeEnd && $timeDeviation) {
                $rec->expectationTimeStart = dt::subtractSecs($timeDeviation, $rec->timeEnd);
            }
            if ($rec->timeStart && !$rec->timeEnd && $timeDeviation) {
                $rec->expectationTimeEnd = dt::addSecs($timeDeviation, $rec->timeStart);
            }
            if ($timeRound) {
                $rec->expectationTimeStart = dt::subtractSecs($timeRound, $rec->expectationTimeStart);
                $rec->expectationTimeEnd = dt::addSecs($timeRound, $rec->expectationTimeEnd);
            }

            if (($rec->expectationTimeStart <= $now) && ($rec->expectationTimeEnd >= $now)) {
                $endIn = isset($endIn) ? max($endIn, $rec->expectationTimeEnd) : $rec->expectationTimeEnd;
            }

            if (($rec->expectationTimeStart > $now)) {
                $startIn = min($startIn, $rec->expectationTimeStart);
            }

            // Проверка за работно време по график
            $pWorkingInterval = planning_AssetResources::getWorkingInterval($config->resource, dt::now(), $rec->expectationTimeEnd);
            if ($pWorkingInterval) {
                try {
                    $frames = $pWorkingInterval->getFrame(dt::mysql2timestamp(dt::now()), dt::mysql2timestamp($rec->expectationTimeEnd));
                } catch (core_exception_Expect $e) {
                    continue;
                }
                if ($frames) {
                    if ($frames[0][0]) {
                        $startIn = min($startIn, dt::timestamp2Mysql($frames[0][0]));
                    }
                    if ($frames[0][1]) {
                        $endIn = max($endIn, dt::timestamp2Mysql($frames[0][1]));
                    }
                }
            }
        }

        $resArr['startAfter'] = $resArr['lastClosed'] = $resArr['endAfter'] = 0;
        if (isset($endIn)) {
            $resArr['endAfter'] = ceil(dt::secsBetween($endIn, $now) / 60);

        } else {
            $resArr['startAfter'] = ceil(dt::secsBetween($startIn, $now) / 60);
        }

        // Намираме последно приключената задача и времето
        $query = cal_Tasks::getQuery();
        $query->where(array("#assetResourceId = '[#1#]'", $config->resource));
        $query->where("#state = 'closed' OR #state = 'stopped'");
        $query->where(array("#expectationTimeEnd <= '[#1#]'", $now));
        $query->orWhere("#timeEnd IS NULL");
        $query->orderBy('expectationTimeEnd', 'DESC');
        $query->orderBy('expectationTimeStart', 'DESC');
        $query->orderBy('id', "DESC");
        $query->limit(1);

        $cRec = $query->fetch();

        if ($cRec) {
            if ($cRec->timeStart && !$cRec->timeEnd && $timeDeviation) {
                $newTimeEnd = dt::addSecs($timeDeviation, $cRec->timeStart);
                if ($newTimeEnd <= $now) {
                    $cRec->expectationTimeEnd = $newTimeEnd;
                }
            }

            $resArr['lastClosed'] = ceil(dt::secsBetween($now, $cRec->expectationTimeEnd) / 60);
        }

        // Намираме задачите, които са в процес на изпълнение, но с време на край по-малко от текущото
        $query = cal_Tasks::getQuery();
        $query->where(array("#assetResourceId = '[#1#]'", $config->resource));
        $query->where("#state = 'active' OR #state = 'pending' OR #state = 'waiting' OR #state = 'wakeup'");
        $query->orWhere(array("#expectationTimeEnd <= '[#1#]'", $now));
        $query->orderBy('expectationTimeEnd', 'DESC');
        $query->orderBy('expectationTimeStart', 'DESC');
        $query->orderBy('id', "DESC");
        $query->limit(1);

        // Времето на послено затваряне е времето на крайният срок на задачата
        if ($cRec) {
            $cRec = $query->fetch();
            if ($cRec->timeStart && !$cRec->timeEnd && $timeDeviation) {
                $newTimeEnd = dt::addSecs($timeDeviation, $cRec->timeStart);
                if ($newTimeEnd <= $now) {
                    $cRec->expectationTimeEnd = $newTimeEnd;
                }
            }

            $lastEnd = ceil(dt::secsBetween($now, $cRec->expectationTimeEnd) / 60);
            if (!$lastEnd) {
                $lastEnd = 0;
            }
            $resArr['lastClosed'] = min($resArr['lastClosed'], $lastEnd);
        }

        return $resArr;
    }
}
