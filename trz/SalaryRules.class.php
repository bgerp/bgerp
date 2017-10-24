<?php



/**
 * Мениджър на заплати
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заплати
 */
class trz_SalaryRules extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Правила';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Правило';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_Rejected,  plg_SaveAndNew, 
                    trz_Wrapper,plg_State2';
   // plg_Created, plg_RowTools, , cond_Wrapper, acc_plg_Registry
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,trz';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,trz';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,trz';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,trz';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,trz';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,positionId, name, function, state';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Константа за грешка при изчисление
     */
    const CALC_ERROR = "Грешка при изчисляване";
    
    
    static $aggregate = array("SUM", "MIN", "MAX", "CNT", "AVR");
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name',    'varchar', 'caption=Наименование,width=100%,mandatory');
    	$this->FLD('positionId', 'key(mvc=hr_Positions,select=name)', 'caption=Позиция, mandatory,oldField=possitionId,autoFilter');
    	$this->FLD('function',    'text(rows=2)', 'caption=Правило,width=100%,mandatory');
    	$this->FLD('state', 'enum(active=Активен,closed=Затворен,)', 'caption=Видимост,input=none,notSorting,notNull,value=active');
    }

    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        
        $opt = self::prepareContextArr();
        
        $form->setSuggestions('function', $opt);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $Double = cls::get('type_Double', array('params' => array('decimals' => 2)));

        //$row->factor = $Double->toVerbal($rec->factor);

    }

    
    /**
     * Изчислява израза
     *
     * @param text $expr - формулата
     * @param array $context - параметрите
     * @param int $person - потребител
     * @return string $res - изчисленото резултата
     */
    public static function calcExpr($expr, $context)
    {
        if(is_array($context)){ 
    
            $expr = strtr($expr, $context);
        }

        if(str::prepareMathExpr($expr) === FALSE) {
            $res = self::CALC_ERROR;
        } else {
            $res = str::calcMathExpr($expr, $success);
            if($success === FALSE) {
                $res = self::CALC_ERROR;
            }
        }
         
        return $res;
    }
    
    
    static function SUM($person, $indicator)
    {   
        $date = self::getPeriod($date);

        $query = trz_SalaryIndicators::getQuery();
        $query->where("#personId = '{$person}' AND #indicator = '{$indicator}' AND (#date >= '{$date->firstDay}' AND #date <= '{$date->lastDay}')");
        
        $value = 0;

        while($rec = $query->fetch()){
            $value += $rec->value;
        }
        
        $res = array(); 
        $res["SUM({$indicator})"] = $value;
 
        return $res;
    }

    
    static function AVR($person, $indicator)
    {
        $date = self::getPeriod($date);

        $query = trz_SalaryIndicators::getQuery();
        $query->where("#personId = '{$person}' AND #indicator = '{$indicator}' AND (#date >= '{$date->firstDay}' AND #date <= '{$date->lastDay}')");
        
        $value = 0;
        $cnt = 0;

        while($rec = $query->fetch()){
            
            $value += $rec->value;
            $cnt++;
        }
        
        if($cnt !== 0) {
            $val = $value / $cnt;
        }

        $res = array();
        $res["AVR({$indicator})"] = $value;
 
        return $res;
    }
    
    
    static function MIN($person, $indicator)
    {
        $date = self::getPeriod($date);

        $query = trz_SalaryIndicators::getQuery();
        $query->where("#personId = '{$person}' AND #indicator = '{$indicator}' AND (#date >= '{$date->firstDay}' AND #date <= '{$date->lastDay}')");
        
        $value = array();

        while($rec = $query->fetch()){
            
            $value[] = $rec->value;
        }
        
        $res = array();
        if(count($value) > 0) {
           
            $res["MIN({$indicator})"] = min($value);
        } else {
            $res["MIN({$indicator})"] = 0;
        }
        
        return $res;
    }
    
    
    static function MAX($person, $indicator)
    {
        $date = self::getPeriod($date);

        $query = trz_SalaryIndicators::getQuery();
        $query->where("#personId = '{$person}' AND #indicator = '{$indicator}' AND (#date >= '{$date->firstDay}' AND #date <= '{$date->lastDay}')");
        
        $value = array();

        while($rec = $query->fetch()){
            
            $value[] = $rec->value;
        }

        $res = array();
        if(count($value) > 0) {
            $res["MAX({$indicator})"] = max($value);
        } else {
            $res["MAX({$indicator})"] = 0;
        }

        return $res;
    }
    
    
    static function CNT($person, $indicator)
    {
        $date = self::getPeriod($date);

        $query = trz_SalaryIndicators::getQuery();
        $query->where("#personId = '{$person}' AND #indicator = '{$indicator}' AND (#date >= '{$date->firstDay}' AND #date <= '{$date->lastDay}')");
        
        $value = array();

        while($rec = $query->fetch()){
            $value[] = $rec;
        }
        
        $res = array();
        $res["CNT({$indicator})"] = count($value);
 
        return $res;
    }
    
    
    public static function applyRule($date)
    {
        $date = self::getPeriod($date);

        $queryInd = trz_SalaryIndicators::getQuery();
        $queryInd->where("#date >= '{$date->firstDay}' AND #date <= '{$date->lastDay}'");
        
        $query = self::getQuery();
        
        
        while($recInd = $queryInd->fetch()) { 
            //personId
            //departmentId
            //positionId
            //indicator
            
            //if($recInd->positionId !== NULL) {
          
                $query->where("#positionId = '{$recInd->positionId}' AND #state = 'active'");
              
                $context = self::getContext($recInd->personId,$recInd->indicator);
                
                while($rec = $query->fetch()){ 
              
                    $value = self::calcExpr($rec->function, $context);
                    
                    $recPayroll = new stdClass();
                    $recPayroll->periodId = $date->lastDay;
                    $recPayroll->personId = $recInd->personId;
                    $recPayroll->rule  = $rec->id;
                    $recPayroll->sum  = $value;
                   
                    $self = cls::get('trz_SalaryPayroll');
                    $exRec = new stdClass();
                   
                    // Ако имаме уникален запис го записваме
                    // в противен слувай го ъпдейтваме
                    if($self->isUnique($recPayroll, $fields, $exRec)){
                        $self::save($recPayroll);
                        
                    } else { 
                        $recPayroll->id = $exRec->id;
                        $self::save($recPayroll);
                    }
                }
            //}
        }
    }
    
    
    static function getContext($person, $indicator)
    {
        $arr = self::prepareContextArr();
        
        $context = array();
        
        foreach($arr as $ind) {
           $aggr = strstr($ind, "(", TRUE);

           $calck = self::$aggr($person, $indicator);
          
           if(is_array($calck)) {
               foreach($calck as $id=>$v){
                   $arr[$id] = $v;
               }
           } 
        }
       
        return $arr;
    }
    
    
    static function getPeriod($date = NULL)
    {
        if(!$date) {
            list($year, $month, $day) = explode("-", dt::now());
        } else {
            list($year, $month, $day) = explode("-", $date);
        }
        
        list($d, $hour) = explode(" ", $day);
        
        $firstDay = strstr(dt::timestamp2Mysql(mktime(0,0,0,$month,1,$year)), " ", TRUE);
        $lastDay = dt::getLastDayOfMonth($date);
        
        $date = (object) array('d' => $d, 'm' => $month, 'Y' => $year, 'firstDay' => $firstDay, 'lastDay' => $lastDay);
        
        return $date;
        
    }
    
    
    static function prepareContextArr()
    {
        $indicatorNames = trz_SalaryIndicators::getIndicatorNames();
        $context = array();
        
        if(is_array($indicatorNames)) {
            foreach($indicatorNames as $name) {
        
                foreach(static::$aggregate as $agr) {
                    $k = "$agr({$name})";
                    $context[$k] = $k;
                }
            }
        }
        
        return $context;
    }
    
    
    function act_Test()
    {
        $date = '2016-12-01';
        self::applyRule($date);
    }
    
    /**
     * Изпращане на данните към показателите
     */
    public static function cron_SalaryRules()
    {
        //$date = '2016-12-01';
        $date = dt::now();
      
        self::applyRule($date);
    }
}
