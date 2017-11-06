<?php



/**
 * Мениджър на отчети за Индикаторите
 *
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Счетоводство » Общи цели
 */
class acc_reports_TotalRep extends frame2_driver_TableData
{                  
	
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'manager,ceo';

    
    /**
     * Полета от таблицата за скриване, ако са празни
     *
     * @var int
     */
    //protected $filterEmptyListFields = 'deliveryTime';
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    // protected $groupByField = 'person';
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     * @var varchar
     */
    // protected $hashField = '$recIndic';
    
    
    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var varchar
     */
    // protected $newFieldToCheck = 'docId';

    
    /**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
	    $fieldset->FLD('roleId', 'keylist(mvc=core_Roles,select=role,groupBy=type, orderBy=orderByRole)', 'caption=Роли');
	    $fieldset->FLD('targets', 'table(columns=month|year|target,captions=Месец|Година|Таргет,widths=8em|8em|10em,month_opt=01|01|02|04|05|06|07|08|09|10|11|12,year_opt=2017|2018|2019)', "caption=Цели,single=none");
	}
      

    /**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param frame2_driver_Proto $Driver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
	{
	    $form = &$data->form;
	    
	}
    
	
	/**
	 * Кои записи ще се показват в таблицата
	 * 
	 * @param stdClass $rec
	 * @param stdClass $data
	 * @return array
	 */
	protected function prepareRecs($rec, &$data = NULL)
	{   
        if(is_string($rec->targets)) {
            $rec->targets = json_decode($rec->targets, TRUE);
        }
		$recs = array();
        
        // Индикатора Делта
        $deltaId = hr_IndicatorNames::fetchField("#name = 'Delta'", 'id');

        foreach($rec->targets['month'] as $i => $month) {
            $year = $rec->targets['year'][$i];
            $target = (int) $rec->targets['target'][$i];
            if(!($month > 0 && $year > 0 && $target > 0) || !$deltaId) continue;

            $res = new stdClass();
            $from = "{$year}-{$month}-01";
            $to   = dt::getLastDayOfMonth($from);
            
            $delta = 0;
            $query = hr_Indicators::getQuery(); 
            $query->where("(#date >= '{$from}' AND #date <= '{$to}') && #indicatorId = {$deltaId}");
            while($recIndic = $query->fetch()){
                // Проверка дали служителя е от посочената роля
                if($recIndic->roleId) {
                }
                $delta += $recIndic->value;
            }

            $res->period = "{$month}/{$year}";
            $res->speed = round(100 * $delta/$target, 2);

            $recs[$res->period] = $res;
        }
 
        return $recs;
	}
	
	
	/**
	 * Връща фийлдсета на таблицата, която ще се рендира
	 *
	 * @param stdClass $rec   - записа
	 * @param boolean $export - таблицата за експорт ли е
	 * @return core_FieldSet  - полетата
	 */
	protected function getTableFieldSet($rec, $export = FALSE)
	{
		$fld = cls::get('core_FieldSet');
	
	 
	    $fld->FLD('period', 'varchar','caption=Период');
	    $fld->FLD('speed', 'double', 'caption=Резултат');
	 
		return $fld;
	}
	
	
    /**
	 * Вербализиране на редовете, които ще се показват на текущата страница в отчета
	 *
	 * @param stdClass $rec  - записа
	 * @param stdClass $dRec - чистия запис
	 * @return stdClass $row - вербалния запис
	 */
	protected function detailRecToVerbal($rec, &$dRec)
	{ 
		$isPlain = Mode::is('text', 'plain');
		$Int = cls::get('type_Int');
		$Date = cls::get('type_Date');
		$Double = cls::get('type_Double');
		$Double->params['decimals'] = 2;
		$row = new stdClass();

	    $row->speed = $Double->toVerbal($dRec->speed);
	    $row->period = $dRec->period;
        
        if($row->period == $key = date("m/Y")) {
            $row->ROW_ATTR['class'] = 'highlight';
        }
 
        return $row;
	}

    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $arr = array();
        
        $key = date("m/Y");
        
        $ratio = self::getWorkingDaysBetween(date("Y-m-01"), dt::now()) / self::getWorkingDaysBetween(date("Y-m-01"),  date("Y-m-t"));
 
        if($ratio == 0) return;

        $value = $data->rec->data->recs[$key]->speed / $ratio;

        if(!($value >= 40 && $value <=160)) return;

        $scale = array(
            'majorTicks' => array(40, 60, 80, 100, 120, 140, 160),
            'minValue' => 40,
            'maxValue' => 160,
            'units' => '%',
            'highlights' => array(
                (object) array('from' => 40, 'to' =>80, 'color' => '#ff6600'),
                (object) array('from' => 80, 'to' =>100, 'color' => '#ffcc66'),
                (object) array('from' => 100, 'to' =>160, 'color' => '#66ff00'),

            ),
        );

        $gauge = canvasgauge_Gauge::drawRadial($value, NULL, $scale); 

        $tpl->append($gauge, 'DRIVER_FIELDS');
    }

    /**
     * Връша броя на работните дни между посочените дати
     */
    private static function getWorkingDaysBetween($from, $to)
    {
        $res = 0;

        while($from <= $to) {
            if(!cal_Calendar::isHoliday($from)) {
                $res++;
            }
            $from = dt::addDays(1, $from);
        }

        return $res;
    }
    
    
 }