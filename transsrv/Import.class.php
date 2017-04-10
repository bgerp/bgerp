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
    	expect($data = Request::get('d'));
    	$data = base64_decode($data);
    	$data = gzuncompress($data);
    	$data = json_decode($data);
    	$data = (object)$data;
    	
    	// Има ли папка на доставчик
    	$folderId = self::getFolderId($data);
    	if(!$folderId) {
    		redirect(array('bgerp_Portal', 'Show'), FALSE, "Не може да бъде определена папка на фирмата", 'warning');
    	}
    	
    	$costItemId = NULL;
    	if(isset($data->ourReff)){
    		$doc = doc_Containers::getDocumentByHandle($data->ourReff);
    		if(is_object($doc)){
    			
    			// Ако цитирания документ има логистични данни, взимат се те
    			if($doc->haveInterface('trans_LogisticDataIntf')){
    				if($doc->fetchField('state') == 'active'){
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
    					
    					$threadId = $doc->fetchField('threadId');
    					$firstDoc = doc_Threads::getFirstDocument($threadId);
    					
    					if(is_object($firstDoc) && $firstDoc->isInstanceOf('deals_DealMaster') && $firstDoc->fetchField('state') == 'active'){
    						
    						// Форсиране на нашия реф като разходно перо
    						$listId = acc_Lists::fetchBySystemId('costObjects')->id;
    						if(!acc_Items::isItemInList($firstDoc->getClassId(), $firstDoc->that, 'costObjects')){
    							$costItemId = acc_Items::force($firstDoc->getClassId(), $firstDoc->that, $listId);
    							doc_ExpensesSummary::save((object)array('containerId' => $firstDoc->fetchField('containerId')));
    						} else {
    							$costItemId = acc_Items::fetchItem($firstDoc->getClassId(), $firstDoc->that)->id;
    						}
    						
    						if(isset($costItemId)){
    							$data->costItemId = $costItemId;
    						}
    					}
    				}
    			}
    		}
    	}
    	
    	try{
    		// Форсира покупка
    		$purchaseId = self::forcePurchaseId($folderId, $data);
    		
    		// Добавя транспортната услуга към покупката
    		if($purchaseId){
    			$purRec = purchase_Purchases::fetch($purchaseId, 'threadId,containerId');
    			doc_ThreadUsers::addShared($purRec->threadId, $purRec->containerId, core_Users::getCurrent());
    				
    			$data->fromCountry = drdata_Countries::fetchField("#formalName = '{$data->fromCountry}'", 'id');
    			$data->toCountry = drdata_Countries::fetchField("#formalName = '{$data->toCountry}'", 'id');
    				
    			core_Request::setProtected('d');
    			redirect(array('purchase_PurchasesDetails', 'CreateProduct', 'requestId' => $purchaseId, 'innerClass' => transsrv_ProductDrv::getClassId(), 'd' => $data, 'ret_url' => purchase_Purchases::getSingleUrlArray($purchaseId)));
    		}
    		
    	} catch(core_exception_Expect $e){
    		reportException($e);
    		return;
    	}
    	
    	// Редирект
    	redirect(array('bgerp_Portal', 'Show'), FALSE, 'Проблем при добавянето');
    }
    
    
    /**
     * Намиране на папка
     * 
     * @param stdClass $data
     * @return NULL|int
     */
    private static function getFolderId($data)
    {
    	expect($data->companyName);
    	 
    	$query = crm_Companies::getQuery();
    	$query->where(array("#name = '[#1#]'", $data->companyName));
    	$cloneQuery = clone $query;
    	
    	$supplierGroupId = crm_Groups::getIdFromSysId('suppliers');
    	$query->likeKeylist("groupList", $supplierGroupId);
    	
    	// С приоритет е папката на фирма в група доставчици със същото име
    	if($companyRec = $query->fetch()){
    		return crm_Companies::forceCoverAndFolder($companyRec->id);
    	}
    	
    	// Ако няма фирма в група доставчици, гледа се във всички фирми тогава
    	if($companyRec = $cloneQuery->fetch()){
    		return crm_Companies::forceCoverAndFolder($companyRec->id);
    	}
    	
    	return NULL;
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