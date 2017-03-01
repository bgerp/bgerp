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
	 * Ако в документите не е зададен, кой е дилъра на сделката
	 * се опитваме да го намерим, от папката
	 * 
	 * @param stdClass $rec
	 * @return int $dealerId
	 */
	public static function getDealerId($rec)
	{
	    
	    $Class = cls::get($rec->detailClassId);
	     
	    $dRec = $Class->fetch($rec->detailRecId);
	     
	    $masterRec = $Class->Master->fetch($dRec->{$Class->masterKey});
	    
	    $firstDoc = doc_Threads::getFirstDocument($masterRec->threadId);

	    $dealerId = $firstDoc->rec()->dealerId;
	    
	    // Ако няма, но отговорника на папката е търговец - него
	    if(empty($dealerId)){
	        $inCharge = doc_Folders::fetchField($firstDoc->rec()->folderId, 'inCharge');
	        if(core_Users::haveRole('sales', $inCharge)){
	            $dealerId = $inCharge;
	        }
	    } 
	    
	    $dealerId = crm_Profiles::fetchField("#userId = {$dealerId}", "personId");

	    return $dealerId;
	}
	
	
	/**
	 * Интерфейсен метод на hr_IndicatorsSourceIntf
	 *
	 * @param date $date
	 * @return array $result
	 */
	public static function getIndicatorValues($timeline)
	{
        // Чака за имплементация
        return array();
	}
}