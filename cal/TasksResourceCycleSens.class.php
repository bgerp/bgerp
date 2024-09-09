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
    }


    /**
     * Прочитане на входовете
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        $maxDays = 10;
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
        $query->where(array("#expectationTimeStart <= '[#1#]'", $to));
        $query->orWhere(array("#expectationTimeEnd <= '[#1#]'", $to));
        $query->orWhere("#timeEnd IS NULL");
        $query->orderBy('expectationTimeStart', 'ASC');
        $query->orderBy('expectationTimeEnd', 'ASC');
        $query->orderBy('id', "DESC");

        $endIn = null;
        $startIn = dt::addDays($maxDays);
        while ($rec = $query->fetch()) {
            if (($rec->expectationTimeStart <= $now) && ($rec->expectationTimeEnd >= $now)) {
                $endIn = isset($endIn) ? max($endIn, $rec->expectationTimeEnd) : $rec->expectationTimeEnd;
            }

            if (($rec->expectationTimeStart > $now)) {
                $startIn = min($startIn, $rec->expectationTimeStart);
            }
        }

        $resArr['startAfter'] = $resArr['endAfter'] = 0;
        if (isset($endIn)) {
            $resArr['endAfter'] = ceil(dt::secsBetween($endIn, $now) / 60);

        } else {
            $resArr['startAfter'] = ceil(dt::secsBetween($startIn, $now) / 60);
        }

        return $resArr;
    }
}
