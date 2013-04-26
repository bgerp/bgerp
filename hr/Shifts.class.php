<?php 


/**
 * Смени
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_Shifts extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Смени";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Смяна";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Персонал";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, hr_Wrapper,  plg_Printing,
                       plg_SaveAndNew, WorkingCycles=hr_WorkingCycles';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,hr';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'admin,hr';
    

    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'hr/tpl/SingleLayoutShift.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory');
        $this->FLD('cycle', 'key(mvc=hr_WorkingCycles,select=name)', "caption=Раб. цикъл");
        $this->FLD('startingOn', 'datetime', "caption=Започване на");
        $this->FLD('employersCnt', 'datetime', "caption=Служители,input=none");
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_BeforePrepareEditForm($mvc, $data)
    {
        if(!$mvc->WorkingCycles->fetch('1=1')) {
            core_Message::redirect("Моля въведете поне един работен режим", 'page_Error', NULL, array('hr_WorkingCycles'));
        }
    }
    
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
       // bp($row, $rec, $mvc);
        $row->calendar = tr($type);
    }
    
    static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	static $shiftMap = array(
    		0 => '',
    		1 => 'I',
    		2 => 'II',
    		3 => 'н',
    		4 => 'д',
 		);

		$year = dt::mysql2verbal($data->rec->startingOn, "Y");
		$month = dt::mysql2verbal($data->rec->startingOn, "n");
		
		 // Таймстамп на първия ден на месеца
        $firstDayTms = mktime(0, 0, 0, $month, 1, $year);

        // Броя на дните в месеца (= на последната дата в месеца);
        $lastDay = date('t', $firstDayTms);
        
        for($i = 1; $i <= $lastDay; $i++){
        	$daysTs = mktime(0, 0, 0, $month, $i, $year);
        	$date = date("Y-m-d H:i", $daysTs);
    		$d[$i]->html = "<span style='float: left;'>" . $shiftMap[static::getShiftDay($data->rec, $date)] . "</span>";
        }
        
        $res->row->month = dt::getMonth($month, $format = 'F', $lg = 'bg');
    	$res->row->calendar = cal_Calendar::renderCalendar($year, $month, $d);

    }
    
    static public function on_AfterRenderSingle($mvc, $data)
    {
    	
    }
    
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
    	jquery_Jquery::enable($tpl);
    	
    	$tpl->push('hr/tpl/style.css', 'CSS');
    }
    
    function act_Test()
    {
    	$id = 3;
    	$rec = self::fetch("#id='{$id}'");
    	
    	$date = '2013-05-03 00:00:00';

    	bp(static::getShiftDay($rec, $date));
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $recShift
     * @param unknown_type $date
     */
    static public function getShiftDay($recShift, $date)
    {
    	// По кой цикъл работи смяната
    	// Кога започва графика на смяната
    	$cycle = $recShift->cycle;
    	$startOn = $recShift->startingOn;
    	
    	// Продължителността на цикъла в дни
    	$cycleDuration = hr_WorkingCycles::fetchField($cycle, 'cycleDuration');
    
    	// В кой ден от цикъла сме
    	$dayIs = (dt::daysBetween($date, $startOn) + 1) % $cycleDuration;
    	
    	// Извличане на данните за циклите
        $stateDetails = hr_WorkingCycleDetails::getQuery();

		// Подробности за конкретния цикъл
		$stateDetails->where("#cycleId='{$cycle}' AND #day='{$dayIs}'");
        
		// Взимаме записа на точно този избран ден от цикъла
		// Кога за почва режима и с каква продължителност е
		$cycleDetails = $stateDetails->fetch();
		$dayStart = $cycleDetails->start;
		$dayDuration = $cycleDetails->duration;
		
		return hr_WorkingCycleDetails::getWorkingShiftType($dayStart, $dayDuration);
    }
}