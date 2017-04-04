<?php


/**
 * Импортирани транспортни услуги
 *
 *
 * @category  extrapack
 * @package   epbags
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Импортирани търговски услуги
 */
class transsrv_Import extends core_BaseClass
{
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canEdit = 'no_one';
	
	
	/**
	 * Кой има право да добавя?
	 */
	public $canAdd = 'no_one';
	
	
	/**
	 * Кой може да го изтрие?
	 */
	public $canDelete = 'no_one';
    
    
    /**
     * Импортира транспортна услуга и я добавя към чернова покупка
     */
    function act_Import()
    {
    	// Декодиране на данните
    	expect($data = Request::get('d', 'varchar'));
    	$data = base64_decode($data);
    	$data = gzuncompress($data);
    	$data = json_decode($data);
    	$data = (object)$data;
    	
    	// Има ли папка на доставчик
    	$folderId = self::getFolderId($data);
    	if(!$folderId) return;
    	
    	//$data->ourReff = "Sal1778";
    	if(isset($data->ourReff)){
    		$doc = doc_Containers::getDocumentByHandle($data->ourReff);
    		if(is_object($doc)){
    			if($doc->haveInterface('trans_LogisticDataIntf')){
    				$rData = (object)$doc->getLogisticData();
    				foreach (array('from', 'to') as $prefix){
    					if($rData->{"{$prefix}Country"} == $data->{"{$prefix}Country"}){
    						setIfNot($data->{"{$prefix}PCode"}, $rData->{"{$prefix}PCode"});
    						setIfNot($data->{"{$prefix}Place"}, $rData->{"{$prefix}Place"});
    						setIfNot($data->{"{$prefix}Address"}, $rData->{"{$prefix}Address"});
    						setIfNot($data->{"{$prefix}Company"}, $rData->{"{$prefix}Company"});
    						setIfNot($data->{"{$prefix}Person"}, $rData->{"{$prefix}Person"});
    					}
    				}
    			}
    		}
    	}
    	
    	try{
    		// Форсира транспортната услуга
    		$productId = self::forceProductId($data, $folderId);
    		
    		// Ако има вече
    		if($productId){
    			
    			// Форсира покупка
    			$purchaseId = self::forcePurchaseId($folderId, $data);
    		
    			// Добавя транспортната услуга към покупката
    			if($purchaseId){
    				purchase_Purchases::addRow($purchaseId, $productId, 1, $data->price);
    			}
    			
    			redirect(purchase_Purchases::getSingleUrlArray($purchaseId), FALSE, 'Успешно добавяне');
    		}
    		
    	} catch(core_exception_Expect $e){
    		reportException($e);
    		return;
    	}
    	
    	// Редирект
    	redirect(array('bgerp_Portal', 'Show'), FALSE, 'Проблем при добавянето');
    }
    
    
    /**
     * Намира папка
     * 
     * @param stdClass $data
     * @return NULL|int
     */
    private static function getFolderId($data)
    {
    	expect($data->companyName);
    	 
    	$query = crm_Companies::getQuery();
    	$query->where(array("#name = '[#1#]'", $data->companyName));
    	 
    	$supplierGroupId = crm_Groups::getIdFromSysId('suppliers');
    	$query->likeKeylist("groupList", $supplierGroupId);
    	if($companyRec = $query->fetch()){
    		return crm_Companies::forceCoverAndFolder($companyRec->id);
    	}
    	
    	return NULL;
    }
    
    
    /**
     * Форсира транспортна услуга
     * 
     * @param stdClass $data
     * @param int $folderId
     * @return int
     */
    private static function forceProductId($data, $folderId)
    {
    	$Products = cls::get('cat_Products');
    	$Driver = cls::get('transsrv_ProductDrv');
    	$driverId = $Driver->getClassId();
    	
    	$productRec = clone $data;
    	$productRec->folderId = $folderId;
    	$productRec->fromCountry = drdata_Countries::fetchField("#letterCode2 = '{$productRec->fromCountry}'", 'id');
    	$productRec->toCountry = drdata_Countries::fetchField("#letterCode2 = '{$productRec->toCountry}'", 'id');
    	$productRec->innerClass = $driverId;
    	unset($productRec->companyName);
    	unset($productRec->price);
    	
    	$hash = cat_Products::getHash($productRec);
    	$pQuery = cat_Products::getQuery();
    	$pQuery->where("#innerClass = {$driverId} AND #folderId = {$folderId}");
    	
    	while($pRec = $pQuery->fetch()){
    		$pHash = cat_Products::getHash($pRec);
    		if($pHash == $hash) {
    			cat_Products::logDebug("Транспортната услуга е вече създадена", $pRec->id);
    			return $pRec->id;
    		}
    	}
    	
    	$metas = $Driver->getDefaultMetas();
    	$productRec->meta = cls::get('type_Set')->fromVerbal($metas);
    	$productRec->name = $Driver->getProductTitle($productRec);
    	$productRec->measureId = $Driver->getDefaultUomId();
    	
    	core_Users::forceSystemUser();
    	$productId = $Products->save($productRec);
    	core_Users::cancelSystemUser();
    	cat_Products::logDebug("Импортиране на транспортна услуга", $productId);
    	if($productId){
    		$handle = cat_Products::getHandle($productId);
    		core_Statuses::newStatus("Импортиране на транспортна услуга|* {$handle}");
    	}
    	
    	return $productId;
    }
    
    
    /**
     * Форсира покупка
     * 
     * @param int $folderId
     * @param stdClass $data
     * @return int
     */
    private static function forcePurchaseId($folderId, $data)
    {
    	$chargeVat = ($data->fromCountry != 'BG' || $data->toCountry != 'BG') ? 'no' : 'separate';
    	
    	$purQuery = purchase_Purchases::getQuery();
    	$purQuery->where("#folderId = '{$folderId}'");
    	$purQuery->where("#chargeVat = '{$chargeVat}'");
    	$purQuery->where("#state = 'draft'");
    	$purQuery->orderBy('valior', 'DESC');
    	$purQuery->show('id');
    	
    	if($pRec = $purQuery->fetch()){
    		cat_Products::logDebug("Има вече чернова покупка за транспортна услуга", $pRec->id);
    		return $pRec->id;
    	}
    	
    	$Cover = doc_Folders::getCover($folderId);
    	$options = array('template' => doc_TplManager::fetchField("#name = 'Заявка за транспорт'", 'id'), 'chargeVat' => $chargeVat);
    	$purchaseId = purchase_Purchases::createNewDraft($Cover->getClassId(), $Cover->that, $options);
    	
    	$handle = purchase_Purchases::getHandle($purchaseId);
    	core_Statuses::newStatus("Успешно създадена заявка за транспорт|* {$handle}");
    	purchase_Purchases::logDebug("Създаване на заявка за транспорт", $purchaseId);
    	
    	return $purchaseId;
    }
}