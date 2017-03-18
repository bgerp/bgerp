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
    public $listFields = 'id,valior=Вальор,detailRecId=Документ,productId,quantity,sellCost,primeCost,delta';
	
	
    /**
     * Работен кеш
     */
    public static $cache = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('valior', 'date(smartTime)', 'caption=Вальор,mandatory');
    	$this->FLD('detailClassId', 'class(interface=core_ManagerIntf)', 'caption=Детайл,mandatory');
    	$this->FLD('detailRecId', 'int', 'caption=Ред от детайл,mandatory, tdClass=leftCol');
    	$this->FLD('productId', 'int', 'caption=Артикул,mandatory, tdClass=productCell leftCol wrap');
    	$this->FLD('quantity', 'double', 'caption=Количество,mandatory');
    	$this->FLD('sellCost', 'double', 'caption=Цени->Продажна,mandatory');
    	$this->FLD('primeCost', 'double', 'caption=Цени->Себестойност,mandatory');
    	$this->FNC('delta', 'double', 'caption=Цени->Делта,mandatory');
    	
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
			
			$Detail = cls::get($rec->detailClassId);
			$dRec = $Detail->fetch($rec->detailRecId);
			$row->detailRecId = $Detail->Master->getLink($dRec->{$Detail->masterKey}, 0);
		}
	}
	
	
	/**
	 * Намиране на дилъра и инициатора на документа, данните се кешират
	 * 
	 * @param int $docClass  - ид на клас
	 * @param int $docId     - ид на документ
	 * @return array
	 * 			['dealerId']    - Ид на визитката на дилъра
	 * 			['initiatorId'] - Ид на визитката на инициатора
	 */
	public static function getDealerAndInitiatorId($docClass, $docId)
	{
		// Ако няма кеширани данни
		if(!isset(static::$cache[$docClass][$docId])){
			
			// Кой е първия документ в нишката
			$Class = cls::get($docClass);
			$threadId = $Class->fetchField($docId, 'threadId');
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
				$dealerId = crm_Profiles::fetchField("#userId = {$firstDocRec->dealerId}", "personId");
			}
			
			if(isset($firstDocRec->initiatorId)){
				$initiatorId = crm_Profiles::fetchField("#userId = {$firstDocRec->initiatorId}", "personId");
			}
			
			// Кеширане на дилъра и инициатора на документа
			static::$cache[$docClass][$docId] = array('dealerId' => $dealerId, 'initiatorId' => $initiatorId);
		}
		
		// Връщане на кешираните данни
		return static::$cache[$docClass][$docId];
	}
	
	
	/**
	 * Интерфейсен метод на hr_IndicatorsSourceIntf
	 *
	 * @param date $date
	 * @return array $result
	 */
	public static function getIndicatorValues($timeline)
	{
		$iQuery = self::getQuery();
		
		// Кои документи ще се проверяват дали са променяни
		$documents = array('sales_Sales' => 'sales_SalesDetails', 'sales_Services' => 'sales_ServicesDetails', 'purchase_Services' => 'purchase_ServicesDetails', 'store_ShipmentOrders' => 'store_ShipmentOrderDetails', 'store_Receipts' => 'store_ReceiptDetails');
		$or = FALSE;
		
		$masters = array();
		foreach ($documents as $Master => $Detail){
			$masterClasId = $Master::getClassId();
			$Detail = cls::get($Detail);
			$detailClassId = $Detail->getClassId();
			
			// За всеки документ, му извличаме детайлите ако той е променян след $timeline
			$dQuery = $Detail->getQuery();
			$dQuery->EXT('state', $Master, "externalName=state,externalKey={$Detail->masterKey}");
			$dQuery->EXT('modifiedOn', $Master, "externalName=modifiedOn,externalKey={$Detail->masterKey}");
			$dQuery->where("#modifiedOn >= '{$timeline}'");
			$dQuery->where("#state != 'draft' AND #state != 'pending' AND #state != 'stopped'");
			
			$fields = "modifiedOn,{$Detail->masterKey},state";
			if($Master != 'sales_Sales'){
				$dQuery->EXT('isReverse', $Master, "externalName=isReverse,externalKey={$Detail->masterKey}");
				$fields .= ",isReverse";
			}
			
			$ids = array();
			$dQuery->show($fields);
			
			// Извличане на ид-та на детайлите му и кеш на мастър данните
			while($dRec = $dQuery->fetch()){
				$masters[$detailClassId][$dRec->id] = array($masterClasId, $dRec->{$Detail->masterKey}, $dRec->state, $dRec->isReverse);
				$ids[$dRec->id] = $dRec->id;
			}
			
			// Ако има детайли от модела ще търсим точно записите от детайли на документи променяни след timeline
			if(count($ids)){
				$ids = implode(',', $ids);
				$iQuery->where("#detailClassId = {$Detail->getClassId()} AND #detailRecId IN ($ids)", $or);
				$or = TRUE;
			}
		}
		
		// Ако не е намерен променен документ, връща се празен масив
		$wh = $iQuery->getWhereAndHaving();
		if(empty($wh->w)) return array();
		
		$result = array();
		
		// За всички записи
		while($rec = $iQuery->fetch()){
			
			// Намиране на дилъра, инициатора и взимане на данните на мастъра на детайла
			$master = $masters[$rec->detailClassId][$rec->detailRecId];
			$persons = self::getDealerAndInitiatorId($master[0], $master[1]);
			
			// За дилъра и инициатора, ако има ще се подават делтите
			foreach (array('dealerId', 'initiatorId') as $personFld){
				if(isset($persons[$personFld])){
					$indicatorId = ($personFld == 'dealerId') ? 1 : 2;
					
					// Ключа по който ще събираме е лицето, документа и вальора
					$key = "{$persons[$personFld]}|{$master[0]}|{$master[1]}|{$rec->valior}|{$indicatorId}";
					
					// Ако документа е обратен 
					$sign = ($master[3] == 'yes') ? -1 : 1;
					$delta = $sign * $rec->delta;
					
					// Ако няма данни, добавят се
					if(!array_key_exists($key, $result)){
						$result[$key] = (object)array('date'        => $rec->valior,
								                      'personId'    => $persons[$personFld],
								                      'docId'       => $master[1],
								                      'docClass'    => $master[0],
								                      'indicatorId' => $indicatorId,
								                      'value'       => $delta,
								                      'isRejected'  => ($master[2] == 'rejected'),
						);
					} else {
						
						// Ако има вече се сумират
						$ref = &$result[$key];
						$ref->value += $delta;
					}
				}
			}
		}
		
		// Връщане на намерените резултати
        return $result;
	}

    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     * 
     * @return array $result
     */
    public static function getIndicatorNames()
    {
        return array(1 => 'Delta', 2 => 'DeltaI');
    }

}