<?php



/**
 * Модел за делти при продажби
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_PrimeCostByDocument extends core_Manager
{
     
    /**
     * Себестойности към документ
     */
    public $title = 'Делти при продажба';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper,plg_AlignDecimals2';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'hr_IndicatorsSourceIntf';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'admin,ceo,debug';
	
	
	/**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,valior=Вальор,containerId,productId,quantity,sellCost,primeCost,delta,dealerId,initiatorId';
	
	
    /**
     * Работен кеш
     */
    public static $cache = array();
    
    
    /**
     * Работен кеш
     */
    public static $groupNames = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('valior', 'date(smartTime)', 'caption=Вальор,mandatory');
    	$this->FLD('detailClassId', 'class(interface=core_ManagerIntf)', 'caption=Детайл,mandatory');
    	$this->FLD('detailRecId', 'int', 'caption=Ред от детайл,mandatory, tdClass=leftCol');
    	$this->FLD('containerId', 'int', 'caption=Документ,mandatory');
    	$this->FLD('productId', 'int', 'caption=Артикул,mandatory, tdClass=productCell leftCol wrap');
    	$this->FLD('quantity', 'double', 'caption=Количество,mandatory');
    	$this->FLD('sellCost', 'double', 'caption=Цени->Продажна,mandatory');
    	$this->FLD('primeCost', 'double', 'caption=Цени->Себестойност,mandatory');
    	$this->FNC('delta', 'double', 'caption=Цени->Делта,mandatory');
    	$this->FLD('dealerId', 'user', 'caption=Дилър,mandatory');
    	$this->FLD('initiatorId', 'user', 'caption=Инициатор,mandatory');
    	
    	$this->setDbIndex('detailClassId,detailRecId,productId');
	}
	
	
	/**
	 * Изчисляване на цена за опаковка на реда
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $rec
	 */
	public static function on_CalcDelta(core_Mvc $mvc, $rec)
	{
		if(isset($rec->primeCost)){
			$rec->delta = ($rec->sellCost - $rec->primeCost) * $rec->quantity;
		}
	}
	
	
	/**
	 * Изтрива кешираните записи за документа
	 * 
	 * @param mixed $class
	 * @param int $id
	 * @return void
	 */
	public static function removeByDoc($class, $id)
	{
		$Class = cls::get($class);
		expect($Detail = cls::get($Class->mainDetail));
		
		$query = $Detail->getQuery();
		$query->where("#{$Detail->masterKey} = {$id}");
		$query->show('id');
		$ids = arr::extractValuesFromArray($query->fetchAll(), 'id');
		if(!count($ids)) return;
		
		$ids = implode(',', $ids);
		self::delete("#detailClassId = {$Detail->getClassId()} AND #detailRecId IN ({$ids})");
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 * @param array $fields - полета
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		if(isset($fields['-list'])){
			$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
			$row->containerId = doc_Containers::getDocument($rec->containerId)->getLink(0);
		}
	}
	
	
	/**
	 * Намиране на дилъра и инициатора на документа, данните се кешират
	 * 
	 * @param int $containerId  - контейнер на документ
	 * @return array
	 * 			['dealerId']    - Ид на визитката на дилъра
	 * 			['initiatorId'] - Ид на визитката на инициатора
	 */
	public static function getDealerAndInitiatorId($containerId)
	{
		// Ако няма кеширани данни
		if(!isset(static::$cache[$containerId])){
			$Document = doc_Containers::getDocument($containerId);
			
			// Кой е първия документ в нишката
			$threadId = $Document->fetchField('threadId');
			$firstDoc = doc_Threads::getFirstDocument($threadId);
			$firstDocRec = $firstDoc->fetch('dealerId, initiatorId, folderId');
			
			// Ако няма дилър това е отговорника на папката ако има права `sales`
			if(empty($firstDocRec->dealerId)){
				$inCharge = doc_Folders::fetchField($firstDocRec->folderId, 'inCharge');
	        	if(core_Users::haveRole('sales', $inCharge)){
	           		 $firstDocRec->dealerId = $inCharge;
	        	}
			}
			
			// Ид-та на визитките на дилъра и инициатора
			$dealerId = $initiatorId = NULL;
			if(isset($firstDocRec->dealerId)){
				$dealerId = $firstDocRec->dealerId;
			}
			
			if(isset($firstDocRec->initiatorId)){
				$initiatorId = $firstDocRec->initiatorId;
			}
			
			// Кеширане на дилъра и инициатора на документа
			static::$cache[$containerId] = array('dealerId' => $dealerId, 'initiatorId' => $initiatorId);
		}
		
		// Връщане на кешираните данни
		return static::$cache[$containerId];
	}
	
	
	/**
	 * Заявката към записите от модела, чиито документи са променяни след $timeline
	 * 
	 * @param datetime $timeline  - времева линия след който ще се филтрират документите
	 * @param array $masters      - помощен масив
	 * @return core_Query $iQuery - подготвената заявка
	 */
	private static function getIndicatorQuery($timeline, &$masters)
	{
		$iQuery = self::getQuery();
		
		// Кои документи ще се проверяват дали са променяни
		$documents = array('sales_Sales' => 'sales_SalesDetails', 'sales_Services' => 'sales_ServicesDetails', 'purchase_Services' => 'purchase_ServicesDetails', 'store_ShipmentOrders' => 'store_ShipmentOrderDetails', 'store_Receipts' => 'store_ReceiptDetails');
		$or = FALSE;
		
		$masters = array();
		
		// Обхождане на всички документи за продажба
		foreach ($documents as $Master => $Detail){
			$masterClasId = $Master::getClassId();
			$Detail = cls::get($Detail);
			$detailClassId = $Detail->getClassId();
				
			// За всеки документ, му извличаме детайлите ако той е променян след $timeline
			$dQuery = $Detail->getQuery();
			$dQuery->EXT('state', $Master, "externalName=state,externalKey={$Detail->masterKey}");
			$dQuery->EXT('containerId', $Master, "externalName=containerId,externalKey={$Detail->masterKey}");
			$dQuery->EXT('modifiedOn', $Master, "externalName=modifiedOn,externalKey={$Detail->masterKey}");
			$dQuery->where("#modifiedOn >= '{$timeline}'");
			$dQuery->where("#state != 'draft' AND #state != 'pending' AND #state != 'stopped'");
				
			$fields = "modifiedOn,state,containerId";
			if($Master != 'sales_Sales'){
				$dQuery->EXT('isReverse', $Master, "externalName=isReverse,externalKey={$Detail->masterKey}");
				$fields .= ",isReverse";
			}
				
			$ids = array();
			$dQuery->show($fields);
				
			// Извличане на ид-та на детайлите му и кеш на мастър данните
			while($dRec = $dQuery->fetch()){
				if(!isset($masters[$dRec->containerId])){
					try{
						$masters[$dRec->containerId] = array(doc_Containers::getDocument($dRec->containerId), $dRec->state, $dRec->isReverse);
					} catch(core_exception_Expect $e){
						reportException($e);
						break;
					}
					
				}
		
				$ids[$dRec->id] = $dRec->id;
			}
				
			// Ако има детайли от модела ще търсим точно записите от детайли на документи променяни след timeline
			if(count($ids)){
				$ids = implode(',', $ids);
				$iQuery->where("#detailClassId = {$Detail->getClassId()} AND #detailRecId IN ($ids)", $or);
				$or = TRUE;
			}
		}
		
		// Връщане на готовата заявка
		return $iQuery;
	}
	
	
	/**
	 * Връща индикаторите за делта на търговеца и инициаторът
	 * 
	 * @param array $indicatorRecs - филтрираните записи
	 * @param array $masters       - помощен масив
	 * @param array $personIds     - масив с ид-та на визитките на дилърите
	 * @return array $result       - @see hr_IndicatorsSourceIntf::getIndicatorValues($timeline)
	 */
	private static function getDeltaIndicators($indicatorRecs, $masters, &$personIds)
	{
		$result = $personIds = array();
		if(!count($indicatorRecs)) return $result;
		
		$deltaId = hr_IndicatorNames::force('Delta', __CLASS__, 1)->id;
		$deltaIId = hr_IndicatorNames::force('DeltaI', __CLASS__, 2)->id;
		
		foreach ($indicatorRecs as $rec){
			
			// Намиране на дилъра, инициатора и взимане на данните на мастъра на детайла
			$Document = $masters[$rec->containerId][0];
			$persons = self::getDealerAndInitiatorId($rec->containerId);
			
			// За дилъра и инициатора, ако има ще се подават делтите
			foreach (array('dealerId', 'initiatorId') as $personFld){
				if(!isset($rec->{$personFld})) continue;
					
				// Намиране на визитката на потребителя
				if(!isset($personIds[$rec->{$personFld}])){
					$personIds[$rec->{$personFld}] = crm_Profiles::fetchField("#userId = '{$rec->{$personFld}}'", 'personId');
				}
							
				$personFldValue = $personIds[$rec->{$personFld}];
				$indicatorId = ($personFld == 'dealerId') ? $deltaId : $deltaIId;
							
				// Ключа по който ще събираме е лицето, документа и вальора
				$key = "{$personFldValue}|{$Document->getClassId()}|{$Document->that}|{$rec->valior}|{$indicatorId}";
							
				// Ако документа е обратен
				$sign = ($masters[$rec->containerId][2] == 'yes') ? -1 : 1;
				$delta = $sign * $rec->delta;
							
				// Ако няма данни, добавят се
				if(!array_key_exists($key, $result)){
					$result[$key] = (object)array('date'        => $rec->valior,
												  'personId'    => $personFldValue,
									              'docId'       => $Document->that,
									              'docClass'    => $Document->getClassId(),
									              'indicatorId' => $indicatorId,
									              'value'       => $delta,
									              'isRejected'  => ($masters[$rec->containerId][1] == 'rejected'),);
				} else {
			
					 // Ако има вече се сумират
					$ref = &$result[$key];
					$ref->value += $delta;
			    }
			}
		}
		
		// Връщане на записите
		return $result;
	}
	
	
	/**
	 * Интерфейсен метод на hr_IndicatorsSourceIntf
	 *
	 * @param date $date
	 * @return array $result
	 */
	public static function getIndicatorValues($timeline)
	{
		$result = $masters = array();
		
		// Подготовка на заявката
		$iQuery = self::getIndicatorQuery($timeline, $masters);
		
		// Ако не е намерен променен документ, връща се празен масив
		$wh = $iQuery->getWhereAndHaving();
		if(empty($wh->w)) return array();
		
		// Всички записи
		$indicatorRecs = $iQuery->fetchAll();
		
		// Връщане на индикаторите за делта на търговеца и инициатора
		$result1 = self::getDeltaIndicators($indicatorRecs, $masters, $personIds);
		if(count($result1)){
			$result = array_merge($result1, $result);
		}
		
		// Връщане на индикаторите за сумата на продадените артикули по групи
		$result2 = self::getProductGroupIndicators($indicatorRecs, $masters, $personIds);
		if(count($result2)){
			$result = array_merge($result2, $result);
		}
		
		// Връщане на всички индикатори
        return $result;
	}
	
	
	
	/**
	 * Връща индикаторите за сумата на продадените артикули по групи
	 *
	 * @param array $indicatorRecs - филтрираните записи
	 * @param array $masters       - помощен масив
	 * @param array $personIds     - масив с ид-та на визитките на дилърите
	 * @return array $result       - @see hr_IndicatorsSourceIntf::getIndicatorValues($timeline)
	 */
	private static function getProductGroupIndicators($indicatorRecs, $masters, $personIds)
	{
		$result = array();
		if(!count($indicatorRecs)) return $result;
		
		$selectedGroups = self::cacheGroupNames();
		if(!count($selectedGroups))  return $result;
		
		$productGroups = self::getAllProductGroups($indicatorRecs);
		
		//
		foreach ($indicatorRecs as $rec){
			if(!$rec->dealerId) continue;
			
			// Намиране на първата група от търсените в която се среща артикула
			$groupId = self::getFirstFoundGroupInProduct($productGroups[$rec->productId], $selectedGroups);
			if(!$groupId) continue;
			
			expect($indicatorId = $selectedGroups[$groupId]->id);
			$Document = $masters[$rec->containerId][0];
			$personFldValue = $personIds[$rec->dealerId];
			
			// Подготовка на ключа по-който ще се събират данните
			$key = "{$personFldValue}|{$Document->getClassId()}|{$Document->that}|{$rec->valior}|{$indicatorId}";
			$sign = ($masters[$rec->containerId][2] == 'yes') ? -1 : 1;
			$value = $sign * round($rec->quantity * $rec->sellCost, 2);
			
			// Ако няма данни, добавят се
			if(!array_key_exists($key, $result)){
				$result[$key] = (object)array('date'        => $rec->valior,
											  'personId'    => $personFldValue,
											  'docId'       => $Document->that,
											  'docClass'    => $Document->getClassId(),
											  'indicatorId' => $indicatorId,
											  'value'       => $value,
											  'isRejected'  => ($masters[$rec->containerId][1] == 'rejected'),);
			} else {
				
				// Ако има вече се сумират
				$ref = &$result[$key];
				$ref->value += $delta;
			}
		}
		
		// Връщане на индикаторите
		return $result;
	}
	
	
	/**
	 * Връща първата група от търсените, в която се среща артикула
	 * 
	 * @param unknown $productGroups  - всички групи на даден артикул
	 * @param unknown $selectedGroups - всички търсени групи
	 * @return NULL|int               - ид на намерената група или NULL ако няма
	 */
	private static function getFirstFoundGroupInProduct($productGroups, $selectedGroups)
	{
		if(!count($productGroups) || !count($selectedGroups)) return NULL;
		
		// Обхождат се всички групи
		foreach($selectedGroups as $groupId => $obj)
		{
			// Връща се ид-то на първата група, която е срещната
			if(in_array($groupId, $productGroups)) return $groupId;
		}
		
		return NULL;
	}
	
	
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     * 
     * @return array $result
     */
    public static function getIndicatorNames()
    {
    	$result = array();
    	
    	// Индикатор за делта на търговеца
    	$rec = hr_IndicatorNames::force('Delta', __CLASS__, 1);
    	$result[$rec->id] = $rec->name;
    	
    	// Индикатор за делта на инициатора
    	$rec = hr_IndicatorNames::force('DeltaI', __CLASS__, 2);
    	$result[$rec->id] = $rec->name;
    	
    	// Индикатори за избраните артикулни групи
    	$groupNames = self::cacheGroupNames();
    	if(count($groupNames)){
    		foreach ($groupNames as $indRec){
    			$result[$indRec->id] = $indRec->name;
    		}
    	}
    	
    	// Връщане на всички индикатори
    	return $result;
    }
    
    
    /**
     * Връща и кешира имената на груповите индикатори
     * 
     * @return array
     */
    private static function cacheGroupNames()
    {
    	if(!count(self::$groupNames)){
    		
    		// Ако има селектирани групи
    		$selectedGroups = sales_Setup::get('DELTA_CAT_GROUPS');
    		$selectedGroups = keylist::toArray($selectedGroups);
    		if(count($selectedGroups)){
    			
    			// Форсират им се индикатори
    			foreach ($selectedGroups as $groupId){
    				$groupName = cat_Groups::getVerbal($groupId, 'name');
    				$rec = hr_IndicatorNames::force($groupName, __CLASS__, "group{$groupId}");
    				
    				self::$groupNames[$groupId] = $rec;
    			}
    		}
    	}
    	
    	// Връщане на кешираните групи
    	return self::$groupNames;
    }
    
    
    /**
     * Помощна ф-я връщаща всички  групи на артикулите
     * 
     * @param array $indicatorRecs
     * @return array $groups
     */
    private static function getAllProductGroups($indicatorRecs)
    {
    	$groups = array();
    	if(!count($indicatorRecs)) return $groups;
    	
    	// Извличане на всички артикули от записите
    	$productArr = arr::extractValuesFromArray($indicatorRecs, 'productId');
    	$pQuery = cat_Products::getQuery();
    	$pQuery->show('groups');
    	$pQuery->in("id", $productArr);
    	while($pRec = $pQuery->fetch()){
    		$groups[$pRec->id] = keylist::toArray($pRec->groups);
    	}
    	
    	return $groups;
    }
}