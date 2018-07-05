<?php 


/**
 * Детайл на смените
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_ShiftDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Грaфици - детайли';
    
    /**
     * @todo Чака за документация...
     */
    public $masterKey = 'shiftId';
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = 'Персонал';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_SaveAndNew, plg_RowZebra, Shifts=hr_Shifts';
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,startingOn,day,mode=Режим,start,duration,break';
    
    /**
     * @todo Чака за документация...
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('cycleId', 'key(mvc=hr_WorkingCycles,select=name)', 'caption=Раб. цикъл,column=none');
        $this->FLD('shiftId', 'key(mvc=hr_Shifts,select=name)', 'column=none');
        
        $this->FLD('startingOn', 'datetime', 'caption=Започване на');
        $this->FLD('day', 'int', 'caption=Ден,mandatory');
        $this->FLD('start', 'time(suggestions=00:00|01:00|02:00|03:00|04:00|05:00|06:00|07:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|19:00|20:00|21:00|22:00|23:00,format=H:M)', 'caption=Начало');
        $this->FLD('duration', 'time(suggestions=00|6:00|6:30|7:00|7:30|8:00|8:30|9:00|9:30|10:00|10:30|11:00|11:30|12:00)', 'caption=Времетраене');
        $this->FLD('break', 'time(suggestions=00|0:30|00:45|1:00|00)', 'caption=Почивка');
    }
    
    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $rec = $data->form->rec;
        
        $query = hr_WorkingCycleDetails::getQuery();
        
        while ($rec = $query->fetch("#cycleId='{$rec->shiftId}'")) {
            $details[] = $rec;
        }
        
        foreach ($details as $d) {
            $days[$d->day] = $d->day;
        }
        
        $data->form->setDefault('cycleId', $data->form->rec->shiftId);
        $data->form->setDefault('startingOn', dt::now());
        
        $data->form->setSuggestions('day', $days);
        
        $data->form->setReadonly('cycleId');
        
        //$data->form->setSuggestions('day', hr_WorkingCycleDetails::getDayOptions($data->masterRec, $data->form->rec->day));
    }
    
    
    /**
     * Сортиране
     */
    public function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('#day');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $max = hr_WorkingCycleDetails::getWorkingShiftType($rec->start, $rec->duration);
        
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
}
