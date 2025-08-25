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
    public $loadList = 'plg_Created, plg_RowTools2, hr_Wrapper, plg_SaveAndNew, plg_State2, plg_Sorting';


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
    public $listFields = 'id,name,start,duration,color,state,createdOn,createdBy';


    /**
     * Кой може да проверява смяна
     */
    public $canCheck = 'ceo, hrMaster, debug';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование,mandatory');
        $this->FLD('start', 'hour', 'caption=Начало,mandatory,smartCenter');
        $this->FLD('duration', 'time', 'caption=Продължителност,mandatory');
        $this->FLD('color', 'color_Type', 'caption=Цвят,smartCenter');

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
            $shifts[$rec->id] = (object)array('name' => $rec->name, 'totalTime' => $timeInFrame);
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
     * @param string $date     - за коя дата
     * @param int $personId    - ид на лице
     * @param null $scheduleId - ид на лице
     * @return int|null        - ид на смяна
     */
    public static function getShift($date, $personId, &$scheduleId = null)
    {
        // Какъв е графикът на лицето
        $scheduleId = planning_Hr::getSchedule($personId);

        // Ще се вземе графика от 22 часа на предходния ден до 06 часа на следващия ден
        $from = dt::addSecs(-2 * 60 * 60, $date);
        $to = dt::addSecs(6 * 60 * 60, "{$date} 23:59:59");
        $Interval   = hr_Schedules::getWorkingIntervals($scheduleId, $from, $to);

        // Определяне в коя смяна е лицето на тази дата спрямо интервала
        return self::getShiftByInterval($date, $Interval);
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if ($mvc->haveRightFor('check')) {
            $data->toolbar->addBtn('Проверка на смяна', array($mvc, 'check', 'ret_url' => true), 'title=Проверка на смяна на лице,ef_icon=img/16/arrow_refresh.png');
        }
    }


    /**
     * Екшън за проверка на смяна на лице
     *
     * @return mixed
     */
    public function act_Check()
    {
        $form = cls::get('core_Form');
        $form->title = tr('Проверка на смяна на лице');
        $form->FLD('personId', 'key2(mvc=crm_Persons,select=names,allowEmpty)', 'caption=Лице,mandatory');
        $form->FLD('date', 'date', 'caption=Дата,mandatory');

        $emplGroupId = crm_Groups::getIdFromSysId('employees');
        $form->setFieldTypeParams('personId', array('groups' => keylist::addKey('', $emplGroupId)));
        $form->input();

        if ($form->isSubmitted()) {
            $rec = &$form->rec;

            $scheduleId = null;
            $shiftId = self::getShift($rec->date, $rec->personId, $scheduleId);
            $scheduleName = isset($scheduleId) ? hr_Schedules::getHyperlink($scheduleId, true)->getContent() : null;
            if($shiftId) {
                $shiftName = hr_Shifts::getTitleById($shiftId);
                $info = "Смяната на лицето е|* <b>{$shiftName}</b> |по график|* <b>{$scheduleName}</b>";

            } else {
                $info = "Лицето не е на смяна по график|* <b>{$scheduleName}</b>";
            }

            $form->info = "<div class='richtext-info-no-image'>" . tr($info) . "</div>";
        }

        $form->toolbar->addSbBtn('Проверка', 'save', 'ef_icon = img/16/arrow_refresh.png, title = Реконтиране');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        $tpl = $this->renderWrapping($form->renderHtml());

        return $tpl;
    }
}