<?php 

/**
 * Работни цикли
 *
 *
 * @category  bgerp
 * @package   hr
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class hr_WorkingCycleDetails extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Работни цикли - детайли';
    
    
    /**
     * @todo Чака за документация...
     */
    public $singleTitle = 'Работен цикъл';
    
    
    /**
     * @todo Чака за документация...
     */
    public $masterKey = 'cycleId';
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = 'Персонал';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_SaveAndNew, plg_RowZebra, plg_PrevAndNext';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'day,mode=Режим,start,duration,break';
    
    
    /**
     * @todo Чака за документация...
     */
    public $rowToolsField = 'day';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,hrMaster';
    
    
    /**
     * Кой може да го изтрие?
     *
     */
    public $canDelete = 'ceo,hrMaster';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('cycleId', 'key(mvc=hr_WorkingCycles,select=name)', 'column=none');
        
        $this->FLD('day', 'int', 'caption=Ден,mandatory');
        $this->FLD('start', 'time(suggestions=00:00|01:00|02:00|03:00|04:00|05:00|06:00|07:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|19:00|20:00|21:00|22:00|23:00,format=H:M,allowEmpty)', 'caption=Начало,remember');
        $this->FLD('duration', 'time(suggestions=00|6:00|6:30|7:00|7:30|8:00|8:30|9:00|9:30|10:00|10:30|11:00|11:30|12:00,allowEmpty)', 'caption=Времетраене,remember');
        $this->FLD('break', 'time(suggestions=00|0:30|00:45|1:00|00,allowEmpty)', 'caption=в т.ч. Почивка,remember');
    }
    
    
    /**
     * Подготовката на формата за въвеждане
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $data->form->setOptions('day', $mvc->getDayOptions($data->masterRec, $data->form->rec->day));
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     */
    public function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        if (countR($data->form->fields['day']->options) <= 1) {
            $data->form->toolbar->removeBtn('saveAndNew');
        }
    }
    
    
    /**
     * Сортиране
     */
    public function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('#day');
    }
    
    
    /**
     * Подготвя масив с дните в даден интервал, като за всеки ден
     *
     * @param int      $cycleId id на цикъл
     * @param datetime $startOn Начало на работният цикъл
     * @param datetime $from    Начална дата за периода
     * @param datetime $to      Крайна дата за периода
     */
    public static function getDayArr($cycleId, $startOn, $from, $to)
    {
        $query = self::getQuery();
        static $cycleDays = array();
        
        if (!isset($cycleDays[$cycleId])) {
            while ($rec = $query->fetch("#cycleId = '{$cycleId}'")) {
                $cycleDays[$cycleId][$rec->day] = (object) array(
                    'start' => $rec->start,
                    'duration' => $rec->duration,
                    'break' => $rec->break,
                    'type' => self::getWorkingShiftType($rec->start, $rec->duration),
                );
            }
        }
        
        $dayNo = dt::daysBetween($from, $startOn) + 1;
        $cycleDuration = hr_WorkingCycles::fetch($cycleId)->cycleDuration;
        $res = array();
        
        while ($from <= $to) {
            $dayInd = $dayNo % $cycleDuration;
            $res[$from] = cal_Calendar::getDayStatus($from);
            
            if (isset($cycleDays[$cycleId][$dayInd], $cycleDays[$cycleId][$dayInd]->duration)) {
                $res[$from] = arr::copy($res[$from], $cycleDays[$cycleId][$dayInd]);
            }
            
            $from = dt::addDays(1, $from);
            $dayNo++;
        }
        
        return $res;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $max = self::getWorkingShiftType($rec->start, $rec->duration);
        
        if ($max == 0) {
            $type = 'почивка';
        } elseif ($max == 3) {
            $type = 'нощен';
        } elseif ($max == 1) {
            $type = 'първи';
        } elseif ($max == 2) {
            $type = 'втори';
        } elseif ($max == 4) {
            $type = 'дневен';
        }
        
        $row->mode = tr($type);
    }
    
    
    /**
     * Връща число от 0 до 4 за типа на режима на смяната
     * 0 - почивка
     * 1 - първи
     * 2 - втори
     * 3 - нощен
     * 4 - дневен
     *
     * @param datetime $start    - започването на режима в секунди
     * @param datetime $duration - продължителността на режима в секънди
     */
    public static function getWorkingShiftType($start, $duration)
    {
        $night = self::getSection($start, $duration, 21 * 60 * 60, 8 * 60 * 60);
        
        $first = self::getSection($start, $duration, 5 * 60 * 60, 8 * 60 * 60);
        
        $second = self::getSection($start, $duration, 13 * 60 * 60, 8 * 60 * 60);
        
        $normal = self::getSection($start, $duration, 9 * 60 * 60, 8 * 60 * 60);
        
        $max = max($night, $first, $second, $normal);
        
        if ($duration == 0) {
            $shiftType = 0;
        } elseif ($max == $night) {
            $shiftType = 3;
        } elseif ($max == $first) {
            $shiftType = 1;
        } elseif ($max == $second) {
            $shiftType = 2;
        } elseif ($max == $normal) {
            $shiftType = 4;
        }
        
        return $shiftType;
    }
    
    
    /**
     * Връща сечението на два периода задаени с начало и продълцителност
     * За периодите се очаква, че са задаени в часове:минути формат
     */
    public static function getSection($start1, $duration1, $start2, $duration2)
    {
        $start = max($start1, $start2);
        $end = min($start1 + $duration1, $start2 + $duration2);
        
        $sec = max(0, $end - $start);
        
        return (int) $sec;
    }
    
    
    /**
     * Връща опциите за дните
     */
    public function getDayOptions($mRec, $day)
    {
        for ($i = 1; $i <= $mRec->cycleDuration; $i++) {
            $opt[$i] = tr('Ден') . $i;
        }
        
        $query = self::getQuery();
        
        while ($rec = $query->fetch(array('#cycleId = [#1#]', $mRec->id))) {
            if ($day != $rec->day) {
                unset($opt[$rec->day]);
            }
        }
        
        return $opt;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public static function on_AfterGetRequiredRoles($mvc, &$roles, $act, $rec = null, $user = null)
    {
        if ($act == 'add') {
            if ($rec->cycleId) {
                $cnt = hr_WorkingCycleDetails::count("#cycleId = {$rec->cycleId}");
                $maxCnt = hr_WorkingCycles::fetchField($rec->cycleId, 'cycleDuration');
                
                if ($cnt >= $maxCnt) {
                    $roles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        // Подготвяме пътя до файла с данните
        $file = 'hr/csv/WorkingCycles.csv';
        
        // Кои колонки ще вкарваме
        $fields = array(
            0 => 'csv_cycleId',
            1 => 'day',
            2 => 'start',
            3 => 'duration',
            4 => 'break',
        );
        
        // Импортираме данните от CSV файла.
        // Ако той не е променян - няма да се импортират повторно
        $cntObj = csv_Lib::importOnce($this, $file, $fields, null, null);
        
        // Записваме в лога вербалното представяне на резултата от импортирането
        $res = $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
        if (!hr_WorkingCycles::count()) {
            $rec->cycleId = 1;
            $rec->createdBy = -1;
        } else {
            $query = hr_WorkingCycles::getQuery();
            $query->where("#sysId = 'dayShift'");
            
            $recSys = $query->fetch();
            
            $rec->cycleId = $recSys->id;
            $rec->createdBy = -1;
        }
    }
}
