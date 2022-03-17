<?php


/**
 * Помощен клас-имплементация на интерфейса import_DriverIntf
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Импорт на експедирани/доставени артикули
 */
class store_iface_ImportShippedProducts extends import2_AbstractDriver
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'store_iface_ImportDetailIntf';
    
    
    /**
     * Кой може да избира драйвъра
     */
    protected $canSelectDriver = 'ceo, store, purchase, sales';
    
    
    /**
     * Заглавие
     */
    public $title = 'Импорт на експедирани/доставени артикули';
    
    
    /**
     * Добавя специфични полета към формата за импорт на драйвера
     *
     * @param core_Manager  $mvc
     * @param core_FieldSet $form
     *
     * @return void
     */
    public function addImportFields($mvc, core_FieldSet $form)
    {
        $rec = $form->rec;
        $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
        $docs = $this->getShippedDocuments($mvc, $masterRec);
        
        $form->FLD('doc', 'int', 'caption=Документи,removeAndRefreshForm=products,silent,mandatory,class=w25');
        $form->setOptions('doc', array('' => '') + $docs);
        if (countR($docs) == 1) {
            $form->setDefault('doc', key($docs));
        }
    }
    
    
    /**
     * Подготвя импортиращата форма
     *
     * @param core_Manager  $mvc
     * @param core_FieldSet $form
     *
     * @return void
     */
    public function prepareImportForm($mvc, core_FieldSet $form)
    {
        $rec = $form->rec;
        
        if (isset($rec->doc)) {
            $Document = doc_Containers::getDocument($rec->doc);
            if ($Document->getInstance() instanceof sales_Sales) {
                $Master = 'sales_Sales';
                $Detail = 'sales_SalesDetails';
            } elseif ($Document->getInstance() instanceof purchase_Purchases) {
                $Master = 'purchase_Purchases';
                $Detail = 'purchase_PurchasesDetails';
            } else {
                $Master = ($mvc instanceof store_ShipmentOrderDetails) ? 'store_Receipts' : 'store_ShipmentOrders';
                $Detail = ($mvc instanceof store_ShipmentOrderDetails) ? 'store_ReceiptDetails' : 'store_ShipmentOrderDetails';
            }
            
            $Detail = cls::get($Detail);
            
            // Извличат се артикулите от избрания документ
            $dQuery = $Detail->getQuery();
            $dQuery->EXT('containerId', $Master, "externalName=containerId,externalKey={$Detail->masterKey}");
            $dQuery->where("#containerId = {$rec->doc}");
            $dQuery->show('productId,packagingId,quantityInPack,quantity,price,discount');
            while ($dRec = $dQuery->fetch()) {
                $caption = str_replace(',', ' ', cat_Products::getTitleById($dRec->productId));
                $key = "product{$dRec->productId}+{$dRec->packagingId}+{$dRec->id}";
                
                $shortUom = cat_UoM::getShortName($dRec->packagingId);
                $form->FLD($key, 'double(Min=0)', "input,caption={$caption}->К-во,unit={$shortUom}");
                $form->setDefault($key, $dRec->quantity / $dRec->quantityInPack);
                $rec->detailsDef[$key] = $dRec;
            }
        }
    }
    
    
    /**
     * Проверява събмитнатата форма
     *
     * @param core_Manager  $mvc
     * @param core_FieldSet $form
     *
     * @return void
     */
    public function checkImportForm($mvc, core_FieldSet $form)
    {
        if ($form->isSubmitted()) {
            $form->rec->importRecs = $this->getImportRecs($mvc, $form->rec);
        }
    }
    
    
    /**
     * Връща записите, подходящи за импорт в детайла
     *
     * @param array $recs
     *                    o productId        - ид на артикула
     *                    o quantity         - к-во в основна мярка
     *                    o quantityInPack   - к-во в опаковка
     *                    o packagingId      - ид на опаковка
     *                    o batch            - дефолтна партида, ако може
     *                    o notes            - забележки
     *                    o $this->masterKey - ид на мастър ключа
     *
     * @return void
     */
    private function getImportRecs(core_Manager $mvc, $rec)
    {
        $recs = array();
        if (!is_array($rec->detailsDef)) {
            
            return $recs;
        }
        foreach ($rec->detailsDef as $key => $dRec) {
            
            // Ако има въведено количество записва се
            if (!empty($rec->{$key})) {
                unset($dRec->id);
                $dRec->quantity = $rec->{$key} * $dRec->quantityInPack;
                $dRec->{$mvc->masterKey} = $rec->{$mvc->masterKey};
                $dRec->isEdited = true;
                $recs[] = $dRec;
            }
        }
        
        return $recs;
    }
    
    
    /**
     * Намира всички експедиционни документи в нишката
     *
     * @param core_Mvc $mvc
     * @param stdClass $masterRec
     * @param int|NULL $limit
     *
     * @return array $result
     */
    private function getShippedDocuments($mvc, $masterRec, $limit = null)
    {
        $result = array();
        $firstDocument = doc_Threads::getFirstDocument($masterRec->threadId);
        $actions = type_Set::toArray($firstDocument->fetchField('contoActions'));
        
        if (!empty($actions['ship'])) {
            $result[$firstDocument->fetchField('containerId')] = $firstDocument->getHandle();
        }
        
        // Всички ЕН и СР в нишката
        $Class = ($mvc instanceof store_ShipmentOrderDetails) ? 'store_Receipts' : 'store_ShipmentOrders';
        $query = $Class::getQuery();
        $query->where("#state = 'active' AND #threadId = {$masterRec->threadId}");
        $query->show('containerId');
        if (isset($limit)) {
            $query->limit($limit);
        }
        
        while ($dRec = $query->fetch()) {
            $result[$dRec->containerId] = $Class::getHandle($dRec->id);
        }
        
        return $result;
    }
    
    
    /**
     * Може ли драйвера за импорт да бъде избран
     *
     * @param core_Manager $mvc      - клас в който ще се импортира
     * @param int|NULL     $masterId - ако импортираме в детайл, id на записа на мастъра му
     * @param int|NULL     $userId   - ид на потребител
     *
     * @return bool - може ли драйвера да бъде избран
     */
    public function canSelectDriver(core_Manager $mvc, $masterId = null, $userId = null)
    {
        if (isset($masterId)) {
            $masterFields = ($mvc->Master instanceof store_DocumentMaster) ? 'isReverse,threadId' : 'threadId';
            $masterRec = $mvc->Master->fetchRec($masterId, $masterFields);

            if (isset($masterRec->isReverse) && $masterRec->isReverse != 'yes') {

                return false;
            }

            $docs = $this->getShippedDocuments($mvc, $masterRec, 1);
            if (!countR($docs)) {
                
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Импортиране на детайла (@see import2_DriverIntf)
     *
     * @param object $rec
     *
     * @return void
     */
    public function doImport(core_Manager $mvc, $rec)
    {
        if (!is_array($rec->importRecs)) return;

        foreach ($rec->importRecs as $iRec) {
            expect($iRec->productId, 'Липсва продукт ид');
            expect(cat_Products::fetchField($iRec->productId), 'Няма такъв артикул');
            expect($iRec->packagingId, 'Няма опаковка');
            expect(cat_UoM::fetchField($iRec->packagingId), 'Несъществуваща опаковка');
            expect($iRec->{$mvc->masterKey}, 'Няма мастър кей');
            expect($mvc->Master->fetch($iRec->{$mvc->masterKey}), 'Няма такъв запис на мастъра');
            expect($mvc->haveRightFor('add', (object) array($mvc->masterKey => $iRec->{$mvc->masterKey})), 'Към този мастър не може да се добавя артикул');
            
            $exRec = deals_Helper::fetchExistingDetail($mvc, $iRec->{$mvc->masterKey}, $iRec->id, $iRec->productId, $iRec->packagingId, $iRec->price, $iRec->discount, null, null, $iRec->batch, $iRec->expenseItemId, $iRec->notes);
            if ($exRec) {
                core_Statuses::newStatus('Записът не е импортиран, защото има дублиране', 'warning');
                continue;
            }

            $mvc->save($iRec);
        }

        if($mvc->Master instanceof store_DocumentMaster){
            $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
            $masterRec->reverseContainerId = $rec->doc;
            $masterRec->_replaceReverseContainerId = true;

            $mvc->Master->save($masterRec);
        }
    }
}
