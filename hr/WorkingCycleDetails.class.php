<?php 


/**
 * Работни цикли
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_WorkingCycleDetails extends core_Detail
{
    
    /**
     * Заглавие
     */
    var $title = "Работни цикли - детайли";
    
    
    var $masterKey = 'cycleId';
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Персонал";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_SaveAndNew, plg_RowZebra';

    var $listFields = 'day,mode=Режим,start,duration,break';
    
    var $rowToolsField = 'day';
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('cycleId', 'key(mvc=hr_WorkingCycles,select=name)', 'column=none');

        $this->FLD('day', 'int', 'caption=Ден,mandatory');
        $this->FLD('start', 'time(suggestions=00:00|01:00|02:00|03:00|04:00|05:00|06:00|07:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|19:00|20:00|21:00|22:00|23:00,format=H:M)', 'caption=Начало');
        $this->FLD('duration', 'time(suggestions=6:00|6:30|7:00|7:30|8:00|8:30|9:00|9:30|10:00|10:30|11:00|11:30|12:00)', 'caption=Времетраене');
        $this->FLD('break',    'time(suggestions=0:30|00:45|1:00)', 'caption=Почивка');

    }
    
    /**
     * Подготовката на формата за въвеждане
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
 
        $data->form->setOptions('day', $mvc->getDayOptions($data->masterRec, $data->form->rec->day));


    }


    /**
     * Сортиране
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {

        $data->query->orderBy('#day');
    }



    /**
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $night = self::getSection($rec->start, $rec->duration, 21*60*60, 8*60*60);
        
        $first = self::getSection($rec->start, $rec->duration, 5*60*60, 8*60*60);
        
        $second = self::getSection($rec->start, $rec->duration, 13*60*60, 8*60*60);
        
        $normal = self::getSection($rec->start, $rec->duration, 9*60*60, 8*60*60);

        $max = max($night, $first, $second, $normal);
        
        if($rec->duration == 0) {
            $type = 'почивка';
        } elseif($max == $night) {
            $type = 'нощен';
        } elseif ($max == $first) {
            $type = 'първи';
        } elseif ($max == $second) {
            $type = 'втори';
        } elseif ($max == $normal) {
            $type = 'дневен';
        }

        $row->mode = tr($type);
    }

    /**
     * Връща сечението на два периода задаени с начало и продълцителност
     * За периодите се очаква, че са задаени в часове:минути формат
     */
    static function getSection($start1, $duration1, $start2, $duration2)
    {   
        
        $start = max($start1, $start2);
        $end = min($start1 + $duration1, $start2 + $duration2);

        $sec = max(0, $end - $start);

        return (int) $sec;
    }


    /**
     * Връща опциите за дните
     */
    function getDayOptions($mRec, $day)
    {
        for($i = 1; $i <= $mRec->cycleDuration; $i++) {
            $opt[$i] = tr('Ден') . $i;
        }
        
        $query = self::getQuery();
        while($rec = $query->fetch("#cycleId = $mRec->id")) {
            if($day != $rec->day) {
                unset($opt[$rec->day]);
            }
        }

        return $opt;
    }



    /**
     *
     */
    function on_AfterGetRequiredRoles($mvc, &$roles, $act, $rec, $user = NULL)
    {
        if($act == 'add') {
            if($rec->cycleId) {
                $cnt = hr_WorkingCycleDetails::count("#cycleId = {$rec->cycleId}");
                $maxCnt = hr_WorkingCycles::fetchField($rec->cycleId, 'cycleDuration');
                if($cnt >= $maxCnt) {
                    $roles = 'no_one';
                }
            }
        } 
    }


}