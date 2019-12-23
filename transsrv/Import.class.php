<?php


/**
 * Импортирани транспортни услуги
 *
 *
 * @category  extrapack
 * @package   epbags
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
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
    public function act_Import()
    {
        // Декодиране на данните
        expect($data = Request::get('d'));
        $data = base64_decode($data);
        $data = gzuncompress($data);
        $data = json_decode($data);
        $data = (object) $data;
        
        // Има ли папка на доставчик
        $folderId = self::getFolderId($data);
        if (!$folderId) {
            redirect(array('bgerp_Portal', 'Show'), false, 'Не може да бъде определена папка на фирмата', 'warning');
        }
        
        $costItemId = null;
        if (isset($data->ourReff)) {
            $doc = doc_Containers::getDocumentByHandle($data->ourReff);
            
            if (is_object($doc)) {
                
                // Ако цитирания документ има логистични данни, взимат се те
                if ($doc->haveInterface('trans_LogisticDataIntf')) {
                    $state = $doc->fetchField('state');
                    if (in_array($state, array('draft', 'active', 'pending'))) {
                        $rData = (object) $doc->getLogisticData();
                        
                        foreach (array('from', 'to') as $prefix) {
                            if ($rData->{"{$prefix}Country"} == $data->{"{$prefix}Country"}) {
                                setIfNot($data->{"{$prefix}PCode"}, $rData->{"{$prefix}PCode"});
                                setIfNot($data->{"{$prefix}Place"}, $rData->{"{$prefix}Place"});
                                setIfNot($data->{"{$prefix}Address"}, $rData->{"{$prefix}Address"});
                                setIfNot($data->{"{$prefix}Company"}, $rData->{"{$prefix}Company"});
                                setIfNot($data->{"{$prefix}Person"}, $rData->{"{$prefix}Person"});
                            }
                        }
                        
                        $threadId = $doc->fetchField('threadId');
                        $firstDoc = doc_Threads::getFirstDocument($threadId);
                        
                        if (is_object($firstDoc) && ($firstDoc->isInstanceOf('deals_DealMaster') || $firstDoc->isInstanceOf('store_Transfers')) && $firstDoc->fetchField('state') == 'active') {
                            
                            // Форсиране на нашия реф като разходно перо
                            $listId = acc_Lists::fetchBySystemId('costObjects')->id;
                            if (!acc_Items::isItemInList($firstDoc->getClassId(), $firstDoc->that, 'costObjects')) {
                                $costItemId = acc_Items::force($firstDoc->getClassId(), $firstDoc->that, $listId);
                                doc_ExpensesSummary::save((object) array('containerId' => $firstDoc->fetchField('containerId')));
                            } else {
                                $costItemId = acc_Items::fetchItem($firstDoc->getClassId(), $firstDoc->that)->id;
                            }
                            
                            if (isset($costItemId)) {
                                $data->costItemId = $costItemId;
                            }
                        }
                    }
                }
            }
        }
        
        try {
            // Форсира покупка
            $purchaseId = self::forcePurchaseId($folderId, $data);
            
            // Добавя транспортната услуга към покупката
            if ($purchaseId) {
                $purRec = purchase_Purchases::fetch($purchaseId, 'threadId,containerId');
                doc_ThreadUsers::addShared($purRec->threadId, $purRec->containerId, core_Users::getCurrent());
                
                $data->fromCountry = drdata_Countries::fetchField(array("#commonName = '[#1#]'", $data->fromCountry), 'id');
                $data->toCountry = drdata_Countries::fetchField(array("#commonName = '[#1#]'", $data->toCountry), 'id');
                
                core_Request::setProtected('d');
                redirect(array('purchase_PurchasesDetails', 'CreateProduct', 'requestId' => $purchaseId, 'innerClass' => transsrv_ProductDrv::getClassId(), 'd' => $data, 'ret_url' => purchase_Purchases::getSingleUrlArray($purchaseId)));
            }
        } catch (core_exception_Expect $e) {
            reportException($e);
            
            return;
        }
        
        // Редирект
        redirect(array('bgerp_Portal', 'Show'), false, 'Проблем при добавянето');
    }
    
    
    /**
     * Намиране на папка
     *
     * @param stdClass $data
     *
     * @return NULL|int
     */
    private static function getFolderId($data)
    {
        expect($data->companyName);
        
        $query = crm_Companies::getQuery();
        $query->where(array("#name = '[#1#]'", $data->companyName));
        $cloneQuery = clone $query;
        
        $supplierGroupId = crm_Groups::getIdFromSysId('suppliers');
        $query->likeKeylist('groupList', $supplierGroupId);
        
        // С приоритет е папката на фирма в група доставчици със същото име
        if ($companyRec = $query->fetch()) {
            
            return crm_Companies::forceCoverAndFolder($companyRec->id);
        }
        
        // Ако няма фирма в група доставчици, гледа се във всички фирми тогава
        if ($companyRec = $cloneQuery->fetch()) {
            
            return crm_Companies::forceCoverAndFolder($companyRec->id);
        }
    }
    
    
    /**
     * Форсира покупка
     *
     * @param int      $folderId
     * @param stdClass $data
     *
     * @return int
     */
    private static function forcePurchaseId($folderId, $data)
    {
        $fromCountryId = drdata_Countries::fetchField("#commonName = '{$data->fromCountry}'");
        $toCountryId = drdata_Countries::fetchField("#commonName = '{$data->toCountry}'");
        $fromEu = drdata_Countries::isEu($fromCountryId);
        $toEu = drdata_Countries::isEu($toCountryId);
        
        /*
    	 * Натоварване - разтоварване
    	 * България - България - 20% ДДС
    	 * България - страна от ЕС (фирма) - 20% ДДС на отделен ред във фактурата
    	 * Страна от ЕС - България - 20% ДДС
    	 * България - страна извън ЕС - 0% ДДС
    	 * Страна извън ЕС - България - 0% ДДС
    	 */
        $chargeVat = 'separate';
        if (($data->fromCountry == 'Bulgaria' && !$toEu) || ($data->toCountry == 'Bulgaria' && !$fromEu)) {
            $chargeVat = 'no';
        }
        
        $purQuery = purchase_Purchases::getQuery();
        $purQuery->where("#folderId = '{$folderId}'");
        $purQuery->where("#chargeVat = '{$chargeVat}'");
        $purQuery->where("#state = 'draft'");
        $purQuery->orderBy('valior', 'DESC');
        $purQuery->show('id');
        
        if ($pRec = $purQuery->fetch()) {
            cat_Products::logDebug('Има вече чернова покупка за транспортна услуга', $pRec->id);
            
            return $pRec->id;
        }
        
        $Cover = doc_Folders::getCover($folderId);
        $options = array('template' => doc_TplManager::fetchField("#name = 'Заявка за транспорт'", 'id'), 'chargeVat' => $chargeVat);
        $purchaseId = purchase_Purchases::createNewDraft($Cover->getClassId(), $Cover->that, $options);
        
        $handle = purchase_Purchases::getHandle($purchaseId);
        core_Statuses::newStatus("Успешно създадена заявка за транспорт|* {$handle}");
        purchase_Purchases::logDebug('Създаване на заявка за транспорт', $purchaseId);
        
        return $purchaseId;
    }
}
