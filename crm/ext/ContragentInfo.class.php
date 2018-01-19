<?php



/**
 * Информация за контрагенти
 *
 * @category  bgerp
 * @package   crm
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     0.12
 */
class crm_ext_ContragentInfo extends core_manager
{
	
	
	/**
     * Заглавие
     */
    public $title = 'Информация за контрагенти';

    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Информация за контрагента';
    
    
    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'crm_Wrapper,plg_Created,plg_Sorting';
    
    
    /**
     * Кой може да редактира
     */
    public $canWrite = 'no_one';


    /**
     * Кой може да редактира
     */
    public $canList = 'debug';  
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'contragentId=Контрагент,customerSince=Първо задание,overdueSales=Просрочени сделки,createdBy';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
    	$this->FLD('contragentClassId', 'int');
    	$this->FLD('contragentId', 'int', 'tdClass=leftCol');
    	$this->FLD('customerSince', 'date');
    	$this->FLD('overdueSales', 'enum(yes=Да)');
    	
    	$this->setDbIndex('contragentClassId');
    	$this->setDbUnique('contragentClassId,contragentId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	try{
    		$row->contragentId = cls::get($rec->contragentClassId)->getHyperlink($rec->contragentId, TRUE);
    	} catch(core_exception_Expect $e){
    		$row->contragentId = "<span class='red'>" . tr('Проблем с показването') . "</span>";
    	}
    }
    
    
    /**
     * След подготовка на тулбара
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
    	if(haveRole('debug')){
    		$rec = core_Cron::getRecForSystemId('Gather_contragent_info');
    		$url = array('core_Cron', 'ProcessRun', str::addHash($rec->id), 'forced' => 'yes');
    		$data->toolbar->addBtn('Преизчисляване', $url, 'title=Преизчисляване на баланса,ef_icon=img/16/arrow_refresh.png,target=cronjob');
    	}
    }
    
    
    /**
     * Връща датата на активиране на най-старата продажба
     * 
     * @param int $contragentClassId - ид на класа на контрагента
     * @param int $contragentId      - ид на контрагента
     * @return NULL|date             - най-ранната дата от която е клиент
     */
    private static function getFirstSaleDate($contragentClassId, $contragentId)
    {
    	// намиране на най-старата активна/приключена сделка на контрагента
    	$saleQuery = sales_Sales::getQuery();
    	$saleQuery->XPR('customerSince', 'date', 'MIN(DATE(COALESCE(#activatedOn, #valior)))');
    	$saleQuery->where("#contragentClassId = {$contragentClassId} AND #contragentId = {$contragentId}");
    	$saleQuery->where("#state = 'active' || #state = 'closed'");
    	$saleQuery->show('customerSince');
    	
    	$found = $saleQuery->fetch();
    	
    	return (is_object($found)) ? $found->customerSince : NULL;
    }
    
    
    /**
     * Връща екстендъра на контрагента
     * 
     * @param int $contragentClassId - ид на класа на контрагента
     * @param int $contragentId      - ид на контрагента
     * @return stdClass|FALSE        - намерения запис
     */
    public static function getByContragent($contragentClassId, $contragentId)
    {
    	return self::fetch("#contragentClassId = {$contragentClassId} AND #contragentId = {$contragentId}");
    }
    
    
    /**
     * Връща датата от която е клиент контрагента
     * 
     * @param int $contragentClassId - ид на класа на контрагента
     * @param int $contragentId      - ид на контрагента
     * @return NULL|date             - най-ранната дата от която е клиент
     */
    public static function getCustomerSince($contragentClassId, $contragentId)
    {
    	$exRec = self::getByContragent($contragentClassId, $contragentId);
    	
    	if(empty($exRec->customerSince)){
    		$customerSince = self::getFirstSaleDate($contragentClassId, $contragentId);
    		if(!empty($customerSince)){
    			if(is_object($exRec)){
    				$exRec->customerSince = $customerSince;
    				$fields = 'customerSince';
    			} else {
    				$fields = NULL;
    				$exRec = self::prepareNewRec($contragentClassId, $contragentId, array('customerSince' => $customerSince));
    			}
    			
    			self::save($exRec, $fields);
    		}
    	}
    	
    	return $exRec->customerSince;
    }
    
    
    /**
     * Всички записи от модела
     * @return array $res - записите, групирани по контрагенти
     */
    private static function getAll()
    {
    	$res = array();
    	
    	// Съществуващите записи
    	$query = self::getQuery();
    	$query->where("#contragentClassId IS NOT NULL");
    	while($rec = $query->fetch()){
    		$res[$rec->contragentClassId][$rec->contragentId] = $rec;
    	}
    	
    	return $res;
    }
    
    
    /**
     * Всички дати от кога са клиентите
     * 
     * @param int $contragentClassId
     */
    private static function getFirstSaleDates($contragentClassId)
    {
    	$res = array();
    	$saleQuery = sales_Sales::getQuery();
    	$saleQuery->XPR('customerSince', 'date', 'MIN(DATE(COALESCE(#activatedOn, #valior)))');
    	$saleQuery->where("#state = 'active' || #state = 'closed'");
    	$saleQuery->where("#contragentClassId = {$contragentClassId}");
    	$saleQuery->show('contragentId,customerSince');
    	$saleQuery->groupBy('contragentId');
    	
    	while($sRec = $saleQuery->fetch()){
    		if(!empty($sRec->customerSince)){
    			$res[$sRec->contragentId] = $sRec->customerSince;
    		}
    	}
    	
    	return $res;
    }
    
    
    /**
     * Подготвя нов запис
     * 
     * @param int $contragentClassId
     * @param int $contragentId
     * @param array $params
     * @return StdClass
     */
    private static function prepareNewRec($contragentClassId, $contragentId, $params = array())
    {
    	$newArr = array('contragentId'      => $contragentId, 
    			        'contragentClassId' => $contragentClassId, 
    			        'createdBy'         => core_Users::SYSTEM_USER);
    	
    	if(is_array($params)){
    		$newArr += $params;
    	}
    	
    	$newRec = (object)$newArr;
    	if(empty($newRec->createdOn)){
    		$newRec->createdOn = dt::now();
    	}
    	
    	return $newRec;
    }
    
    
    /**
     * Събиране на информация за контрагентите
     */
    function cron_GatherInfo()
    {
    	$now = dt::now();
    	$existing = self::getAll();
    	 
    	$uArr = array(core_Users::ANONYMOUS_USER, core_Users::SYSTEM_USER);
    	$contragentClasses = core_Classes::getOptionsByInterface('crm_ContragentAccRegIntf', 'id');
    	
    	// За всички контрагенти
    	foreach ($contragentClasses as $classId){
    		$saveArray = array();
    		$exRecs = $existing[$classId];
	    	
    		// За всички неоттеглени контрагенти
	    	$ContragentClass = cls::get($classId);
	    	$cQuery = $ContragentClass::getQuery();
	    	$cQuery->where("#folderId IS NOT NULL");
	    	$cQuery->where("#state != 'rejected'");
	    	$cQuery->show('folderId,id');
	    	
	    	// Дигане на тайм лимита за всеки случай
	    	$count = $cQuery->count() * 0.012;
	    	core_App::setTimeLimit($count, FALSE, 300);
	    	
	    	// От кога са клиенти
	    	$customersSince = self::getFirstSaleDates($classId);
	    	$overdues = self::getOverdueSales($classId);
	    	
	    	// За всеки
	    	while($cRec = $cQuery->fetch()){
	    		
	    		if(array_key_exists($cRec->id, $exRecs)){
	    			$r = $exRecs[$cRec->id];
	    		} else {
	    			$r = self::prepareNewRec($classId, $cRec->id, array('createdOn' => $now));
	    		}
	    		
	    		$r->overdueSales = array_key_exists($cRec->id, $overdues) ? 'yes' : NULL;
	    		
	    		//..и е стар запис създаден от системата
	    		if(array_key_exists($cRec->id, $exRecs)){
	    			if(in_array($exRecs[$cRec->id]->createdBy, $uArr)){
	    				$r->customerSince = array_key_exists($cRec->id, $customersSince) ? $customersSince[$cRec->id] : NULL;
	    			}
	    		}
	    		
	    		if(isset($r->overdueSales) || isset($r->customerSince)){
	    			$saveArray[$cRec->id] = $r;
	    		}
	    	}
	    	
	    	// Запис на новите данни
	    	if(count($saveArray)){
	    		$this->saveArray($saveArray);
	    	}
    	}
    }
    
    
    /**
     * Всички просрочени продажби
     * 
     * @param int $contragentClassId
     * @return array $res
     */
    private function getOverdueSales($contragentClassId)
    {
    	$saleQuery = sales_Sales::getQuery();
    	$saleQuery->where("#contragentClassId = {$contragentClassId}");
    	$saleQuery->where("#state = 'active' AND #paymentState = 'overdue'");
    	$saleQuery->show('id,contragentId');
    	$res = arr::extractValuesFromArray($saleQuery->fetchAll(), 'contragentId');
    	
    	return $res;
    }
}