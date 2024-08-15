<?php


/**
 * Клас 'store_ConsignmentProtocolDetailsReceived'
 *
 * Детайли на мениджър на детайлите на протоколите за отговорно пазене-върнати
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_ConsignmentProtocolDetailsReceived extends store_InternalDocumentDetail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на протоколите за отговорно пазене-върнати';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'върнат артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'protocolId';
    
    
    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'plg_RowTools2, plg_Created, store_Wrapper, plg_RowNumbering, plg_SaveAndNew, 
                        plg_AlignDecimals2, LastPricePolicy=sales_SalesLastPricePolicy,cat_plg_CreateProductFromDocument,deals_plg_ImportDealDetailProduct,plg_PrevAndNext,store_plg_TransportDataDetail';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, store, distributor';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, store, distributor';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, store, distributor';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId=Получено от Клиент/Доставчик, packagingId, packQuantity=К-во, weight=Тегло,volume=Обем, packPrice, amount,transUnitId=ЛЕ';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price, amount, discount, packPrice';
    
    
    /**
     * Кой може да го импортира артикули?
     *
     * @var string|array
     */
    public $canImport = 'ceo, store, distributor';
    
    
    /**
     * Да се забрани ли създаването на нова партида
     */
    public $cantCreateNewBatch = true;


    /**
     * Кеширане на получените чужди ариткули в нишката
     */
    public static $cacheConsignmentInThread = array();


    /**
     * Какво движение на партида поражда документа в склада
     *
     * @param out|in|stay - тип движение (излиза, влиза, стои)
     */
    public $batchMovementDocument = 'in';


    /**
     * Кой може да създава артикул директно към документа?
     *
     * @var string|array
     */
    public $canCreateproduct = 'ceo, store';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('protocolId', 'key(mvc=store_ConsignmentProtocols)', 'column=none,notNull,silent,hidden,mandatory');
        parent::setFields($this);
        $this->FLD('clonedFromDetailId', "int", 'caption=От кое поле е клонирано,input=none');
        $this->FLD('clonedFromDetailClass', "int", 'caption=От кое поле е клонирано,input=none');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm(core_Mvc $mvc, &$data)
    {
        $masterRec = $data->masterRec;
        $params = array('customerClass' => $masterRec->contragentClassId, 'customerId' => $masterRec->contragentId, 'hasnotProperties' => 'generic');
        $params['hasProperties'] = $mvc->getExpectedProductMetaProperties($masterRec->productType, 'receive');
        if($masterRec->productType == 'other'){
            $params['isPublic'] = 'no';
        }
        $data->form->setFieldTypeParams('productId', $params);
    }


    /**
     * Ф-я извличаща получените чужди артикули от ПОП в сделка към нишката
     *
     * @param int $threadId
     * @param bool $detailed
     * @return array $res
     */
    public static function getReceivedOtherProductsFromSale($threadId, $detailed = true)
    {
        $res = array();
        $saleId = null;

        if(!array_key_exists("{$threadId}{$detailed}", static::$cacheConsignmentInThread)){
            // Прави се опит за намиране на продажбата от първия документ в нишката
            $firstDocument = doc_Threads::getFirstDocument($threadId);
            if($firstDocument->isInstanceOf('sales_Sales')) {
                $saleId = $firstDocument->that;
            } elseif($firstDocument->isInstanceOf('planning_Jobs')){
                $saleId = $firstDocument->fetchField('saleId');
            } elseif($firstDocument->isInstanceOf('planning_Tasks')){
                $jobContainerId = $firstDocument->fetchField('originId');
                $saleId = planning_Jobs::fetchField("#containerId={$jobContainerId}", 'saleId');
            }

            if(isset($saleId)) {
                // Само ако ПВ е към задание със сделка в чиято нишка има ПОП за чужди артикули с получени материали
                $saleThreadId = sales_Sales::fetchField($saleId, 'threadId');
                $cQuery = store_ConsignmentProtocolDetailsReceived::getQuery();
                $cQuery->EXT('measureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
                $cQuery->EXT('threadId', 'store_ConsignmentProtocols', 'externalName=threadId,externalKey=protocolId');
                $cQuery->EXT('productType', 'store_ConsignmentProtocols', 'externalName=productType,externalKey=protocolId');
                $cQuery->EXT('state', 'store_ConsignmentProtocols', 'externalName=state,externalKey=protocolId');
                $cQuery->where("#threadId = {$saleThreadId} AND #state = 'active' AND #productType = 'other'");
                if(!$detailed){
                    $cQuery->show('productId');
                }

                $cQuery->orderBy('id', 'ASC');
                $classId = store_ConsignmentProtocolDetailsReceived::getClassId();
                while($rec = $cQuery->fetch()){
                    if(!$detailed) {
                        $res[$rec->productId] = $rec->productId;
                        continue;
                    }
                    // Подготовка на сумираните данни
                    if(!array_key_exists($rec->productId, $res)){
                        $res[$rec->productId] = array();
                    }
                    if(!array_key_exists($rec->packagingId, $res[$rec->productId])){
                        $res[$rec->productId][$rec->packagingId] = array('batches' => array(), 'productId' => $rec->productId, 'packagingId' => $rec->packagingId, 'quantityInPack' => $rec->quantityInPack);
                    }
                    $res[$rec->productId][$rec->packagingId]['totalQuantity'] += $rec->quantityInPack * $rec->packQuantity;

                    // Добавяне на посочените партиди към реда
                    if(core_Packs::isInstalled('batch')){
                        $bQuery = batch_BatchesInDocuments::getQuery();
                        $bQuery->where("#detailClassId = {$classId} AND #detailRecId = {$rec->id}");
                        while($bRec = $bQuery->fetch()){
                            if($batchDef = batch_Defs::getBatchDef($bRec->productId)){
                                $bArr = array_keys($batchDef->makeArray($bRec->batch));
                                foreach ($bArr as $b){
                                    $bKey = md5($b);
                                    $res[$rec->productId][$rec->packagingId]['batches'][$bKey]['batch'] = $bRec->batch;
                                    $res[$rec->productId][$rec->packagingId]['batches'][$bKey]['quantity'] += $bRec->quantity;
                                }
                            }
                        }
                    }
                }
            }

            static::$cacheConsignmentInThread["{$threadId}{$detailed}"] = $res;
        }

        return static::$cacheConsignmentInThread["{$threadId}{$detailed}"];
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'createproduct' && isset($rec)){
            $productType = store_ConsignmentProtocols::fetchField($rec->protocolId, 'productType');
            if($productType != 'other'){
                $requiredRoles = 'no_one';
            }
        }
    }
}
