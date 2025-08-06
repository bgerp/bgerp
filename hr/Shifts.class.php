<?php

/**
 * Работни смени
 *
 *
 * @category  bgerp
 * @package   hr
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class hr_Shifts extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Работни смени';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Работна смяна';


    /**
     * Страница от менюто
     */
    public $pageMenu = 'Персонал';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, hr_Wrapper, plg_SaveAndNew, plg_State2';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, hrMaster';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, hrMaster';


    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo, hrMaster';


    /**
     * Кой може да го изтрие?
     *
     */
    public $canDelete = 'ceo, hrMaster';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,name,start,duration,state,createdOn,createdBy';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование,mandatory');
        $this->FLD('start', 'hour', 'caption=Начало,mandatory,smartCenter');
        $this->FLD('duration', 'time', 'caption=Продължителност,mandatory');

        $this->setDbUnique('name');
    }


    /**
     * Връща ид-то на смяната в която попада датата за посочения работен интервал
     * връща смяната с най-много прекарано време)
     *
     * @param string $date        - дата
     * @param core_Intervals $Int - инстанция на работен интервал
     * @return int|null           - ид-то на смяната или null ако не е на смяна
     */
    public static function getShiftByInterval($date, core_Intervals $Int)
    {
        // Всички активни смени
        $query = self::getQuery();
        $query->where("#state = 'active'");
        $query->show('name,start,duration');

        $shifts = array();
        while($rec = $query->fetch()){

            // За всяка смяна се гледа колко ѝ е сечението с работния график
            $start = "{$date} {$rec->start}:00";
            $end = dt::addSecs($rec->duration, $start);
            $frame = $Int->getFrame(dt::mysql2timestamp($start), dt::mysql2timestamp($end));
            $timeInFrame = 0;
            foreach($frame as $t) {
                $timeInFrame += $t[1] - $t[0];
            }
            $shifts[$rec->id] = (object)array('name' => $rec->name, 'totalTime' => $timeInFrame, 'start' => $start, 'end' => $end);
        }

        // Подреждане по смените с най-голямо сечение
        arr::sortObjects($shifts, 'totalTime', 'desc');
        $firstRec = $shifts[key($shifts)];

        // Ако смяната с най-голямо сечение е различно от 0 се връща, иначе null
        return $firstRec->totalTime != 0 ? key($shifts) : null;
    }


    /**
     * Ф-я връщаща коя смяна е лицето за дадената дата
     *
     * @param string $date  - за коя дата
     * @param int $personId - ид на лице
     * @return int|null     - ид на смяна
     */
    public static function getShift($date, $personId)
    {
        $scheduleId = planning_Hr::getSchedule($personId);

        $from = dt::addSecs(-2 * 60 * 60, $date);
        $to = dt::addSecs(6 * 60 * 60, "{$date} 23:59:59");
        $Interval   = hr_Schedules::getWorkingIntervals($scheduleId, $from, $to);

        return self::getShiftByInterval($date, $Interval);
    }
}