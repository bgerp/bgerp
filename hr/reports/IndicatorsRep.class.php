<?php



/**
 * Мениджър на отчети за Индикаторите за ефективност
 *
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Gabriela Petrova <gab4eto@gmail.com> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Персонал » Индикатори за ефективност
 */
class hr_reports_IndicatorsRep extends frame2_driver_TableData
{                  
	
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'manager,ceo';
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'person';
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     * @var varchar
     */
    protected $hashField = '$recIndic';
    
    
    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var varchar
     */
    protected $newFieldToCheck = 'docId';


    /**
     * Полета с възможност за промяна
     */
    protected $changeableFields = 'periods';
    
    
    /**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('periods', 'key(mvc=acc_Periods,select=title)', 'caption=Месец,after=title');
		$fieldset->FLD('indocators', 'keylist(mvc=hr_IndicatorNames,select=name,allowEmpty)', 'caption=Индикатори,after=periods');
		$fieldset->FLD('personId', 'type_UserList', 'caption=Потребител,after=indocators');
		$fieldset->FLD('formula', 'text(rows=2)', 'caption=Формула,after=indocators,single=none');
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
	    
        $periodToday = acc_Periods::fetchByDate(dt::now());
        $form->setDefault('periods', $periodToday->id);
        $form->setSuggestions('formula', hr_IndicatorNames::getFormulaSuggestions());
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
		$recs = array();
		$periodRec = acc_Periods::fetch($rec->periods);

		// Ако има избрани потребители, взимат се те. Ако няма всички потребители
	    $users = (!empty($rec->personId)) ? keylist::toArray($rec->personId) : core_Users::getByRole('powerUser');
	    
	    // Извличат се ид-та на визитките на избраните потребители
	    $personIds = array();
	    $pQuery = crm_Profiles::getQuery();
	    $pQuery->in("userId", $users);
	    $pQuery->show('personId');
	    $personIds = arr::extractValuesFromArray($pQuery->fetchAll(), 'personId');
	    
	    // Извличане на индикаторите за посочените дати, САМО за избраните лица
	    $query = hr_Indicators::getQuery();
	    $query->where("(#date >= '{$periodRec->start}' AND #date <= '{$periodRec->end}')");
	    $query->in("personId", $personIds);
	    
	    // Ако са посочени индикатори извличат се само техните записи
	    if(!empty($rec->indocators)){
	    	$indicators = keylist::toArray($rec->indocators);
	    	$query->in('indicatorId', $indicators);
	    }
	    
	    $context = array();
	    $personNames = array();
	    
	    // за всеки един индикатор
	    while($recIndic = $query->fetch()){
	    	$key = "{$recIndic->personId}|{$recIndic->indicatorId}";
	    	$keyContext = "{$recIndic->personId}|formula";
	    	
	        // Добавя се към масива, ако го няма
	        if(!array_key_exists($key, $recs)) {
	        	if(!array_key_exists($recIndic->personId, $personNames)){
	        		$personNames[$recIndic->personId] = str::utf2ascii(crm_Persons::fetchField($recIndic->personId, 'name'));
	        	}
	        	
	        	$recs[$key]= (object) array ('num'         => 0,
	                						 'date'        => $recIndic->date,
	                						 'docId'       => $recIndic->docId,
	                						 'person'      => $recIndic->personId,
	                						 'indicatorId' => $recIndic->indicatorId,
	                						 'value'       => $recIndic->value,
	            							 'personName'  => $personNames[$recIndic->personId], 
	            );
	        } else {
	            $obj = &$recs[$key];
	            $obj->value += $recIndic->value;
	        }  
	        
	        $iName = hr_IndicatorNames::fetchField($recIndic->indicatorId, 'name');
	        
	        $context[$recIndic->personId]["$" . $iName] += $recIndic->value;
	    }
	    
	    if(!empty($rec->formula)){
	    	foreach ($context as $pId => $arr){
	    		$recs["{$pId}|formula"] = (object)array('person' => $pId, 'personName' => $personNames[$pId], 'indicatorId' => 'formula', 'context' => $arr);
	    	}
	    }
	    
	    // Ако има такива сортираме ги по име
	    uasort($recs, function($a, $b){
	    	if($a->personName == $b->personName) {
	    		return ($a->indicatorId < $b->indicatorId) ? -1 : 1;
	    	}
	    	
	    	return (strnatcasecmp($a->personName, $b->personName) < 0) ? -1 : 1;
	    });
	    
	    $num = 1;
        $total = array();
	    foreach($recs as $r) {
	        $r->num = $num;
	        $num++;
	        
	        if($r->indicatorId == 'formula') continue;
            $total[$r->indicatorId] += $r->value;
	    }
	    
        foreach($total as $ind => $val) {
            $r = new stdClass();
            $r->person = 0;
            $r->indicatorId = $ind;
            $r->value = $val;
            $num++;
            $r->num = $num;
            $recs['0|' . $ind] = $r;
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
	
		$fld->FLD('num', 'varchar','caption=№');
		$fld->FLD('person', 'varchar', 'caption=Служител');
		$fld->FLD('indicator', 'varchar', 'caption=Показател');
		$valueAttr = ($export === FALSE) ? 'smartCenter,caption=Стойност' : 'caption=Стойност';
		$fld->FLD('value', 'double(smartRound,decimals=2)', $valueAttr);
		
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

		// Линк към служителя
		if(isset($dRec->person)) {
            if($dRec->person > 0) {
                $userId = crm_Profiles::fetchField("#personId = '{$dRec->person}'",'userId');
                $nick = crm_Profiles::createLink($userId)->getContent();
                $row->person = crm_Persons::fetchField($dRec->person, 'name') . " (" . $nick .")";
            } else {
                $row->person = 'Общо';
            }
		}
		
		if($isPlain){
			$row->person = strip_tags(($row->person instanceof core_ET) ? $row->person->getContent() : $row->person);
		}

		if(isset($dRec->num)) {
		    $row->num = $Int->toVerbal($dRec->num);
		}

		if(isset($dRec->indicatorId)) {
			if($dRec->indicatorId != 'formula'){
				$row->indicator = hr_IndicatorNames::fetchField($dRec->indicatorId, 'name');
			} elseif($rec->formula) {
				$row->indicator = tr('Формула');
				$newContext = self::fillMissingIndicators($dRec->context, $rec->formula);
				
				uksort($newContext, "str::sortByLengthReverse");
				$expr = strtr($rec->formula, $newContext);
				
				if(str::prepareMathExpr($expr) === FALSE) {
					$row->value = '<small style="font-style:italic;color:red;">' . tr("Невъзможно изчисление") . '</small>';
				} else {
					$value = str::calcMathExpr($expr, $success);
					$row->value = $Double->toVerbal($value);
				}
			}
		}

	    if(isset($dRec->value) && empty($row->value)) {
		    if(!$isPlain && !Mode::isReadOnly()){
		    	$row->value = $Double->toVerbal($dRec->value);
		    	
		    	if(!$isPlain){
		    		$row->value = ht::styleIfNegative($row->value, $dRec->value);
		    	}
		    	
		    	$start = acc_Periods::fetchField($rec->periods, 'start');
		    	$date = new DateTime($start);
		    	$startMonth = $date->format('Y-m-01');
		    	
		    	$haveRight = hr_Indicators::haveRightFor('list');
		    	$url = array('hr_Indicators', 'list', 'period' => $startMonth, 'indicatorId' => $dRec->indicatorId);
		    	if(!empty($dRec->person)){
		    		$url['personId'] = $dRec->person;
		    	}
		    	
		    	if($haveRight !== TRUE){
		    		core_Request::setProtected('period,personId,indicatorId,force');
		    		$url['force'] = TRUE;
		    	}
		    	
		    	$row->value = ht::createLinkRef($row->value, toUrl($url), FALSE, 'target=_blank,title=Към документите формирали записа');
		    	
		    	if($haveRight !== TRUE){
		    		core_Request::removeProtected('period,personId,indicatorId,force');
		    	}
		    } else {
		    	$row->value = frame_CsvLib::toCsvFormatDouble($dRec->value);
		    }
	    }
		
		return $row;
	}
	
	
	/**
	 * Допълване на липсващите индикатори от формулата с такива със стойност 0
	 * 
	 * @param array $context
	 * @param string $formula
	 * @return array $arr
	 */
	private static function fillMissingIndicators($context, $formula)
	{
		$arr = array();
		$formulaIndicators = hr_Indicators::getIndicatorsInFormula($formula);
		if(!count($formulaIndicators)) return $arr;
		
		foreach($formulaIndicators as $name){
			$key = "$" . $name;
			if(!array_key_exists($key, $context)){
				$context[$key] = 0;
			}
		}
		
		return $context;
	}
	
	
    /**
	 * След вербализирането на данните
	 *
	 * @param frame2_driver_Proto $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $row
	 * @param stdClass $rec
	 * @param array $fields
	 */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        // потребителите
        if(isset($rec->personId)){
            $persons = keylist::toArray($rec->personId);
            foreach ($persons as $userId => &$nick) {
                $nick = crm_Profiles::createLink($userId)->getContent();
            }
            
            $row->persons = implode(', ', $persons);
        }
        

        if(isset($rec->periods)){
            // избраният месец
            $row->month = acc_Periods::fetchField("#id = '{$rec->periods}'", 'title');
        }
        
        if(isset($rec->formula)){
        	$row->formula = core_Type::getByName('text')->toVerbal($rec->formula);
        }
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
    	$fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Формула|*</b></small></legend>
							    <!--ET_BEGIN formula--><small>[#formula#]</small></div><!--ET_END formula--></fieldset><!--ET_END BLOCK-->"));
    
    	foreach (array('indocators', 'formula') as $fld){
    		if(isset($data->rec->{$fld})){
    			$fieldTpl->append($data->row->{$fld}, $fld);
    		}
    	}
    
    	$tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
}