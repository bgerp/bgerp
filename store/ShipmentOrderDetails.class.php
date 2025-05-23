<?php


/**
 * Клас 'store_ShipmentOrderDetails'
 *
 * Детайли на мениджър на експедиционни нареждания (@see store_ShipmentOrders)
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_ShipmentOrderDetails extends deals_DeliveryDocumentDetail
{
    /**
     * Заглавие
     *
     * @var string
     */
    public $title = 'Детайли на ЕН';
    
    
    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'shipmentId';
    
    
    /**
     * Интерфейс на драйверите за импортиране
     */
    public $importInterface = 'store_iface_ImportDetailIntf';
    
    
    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'plg_RowTools2, plg_Created, store_Wrapper, plg_RowNumbering,store_plg_RequestDetail, plg_SaveAndNew, doc_plg_HidePrices,
                        plg_AlignDecimals2,deals_plg_ImportDealDetailProduct, plg_Sorting, doc_plg_TplManagerDetail, LastPricePolicy=sales_SalesLastPricePolicy,
                        ReversePolicy=purchase_PurchaseLastPricePolicy, plg_PrevAndNext,acc_plg_ExpenseAllocation,cat_plg_CreateProductFromDocument,cat_plg_ShowCodes,store_plg_TransportDataDetail,import2_Plugin';
    
    
    /**
     * Да се показва ли кода като в отделна колона
     */
    public $showCodeColumn = true;


    /**
     * Кой има право да разбива партидите?
     */
    public $canSplitbatches = 'ceo,store,sales,purchase';


    /**
     * Да се показва ли вашия номер
     */
    public $showReffCode = true;


    /**
     * Активен таб на менюто
     *
     * @var string
     */
    public $menuPage = 'Логистика:Складове';
    
    
    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canEdit = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = 'ceo,store,sales,purchase';
    
    /**
     * Кой може да го импортира артикули?
     *
     * @var string|array
     */
    public $canImport = 'user';
    
    
    /**
     * Кой може да го изтрие?
     *
     * @var string|array
     */
    public $canDelete = 'ceo,store,sales,purchase';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'info=@Колети, productId, packagingId, packQuantity=К-во, packPrice, discount=Отст., amount, weight=Тегло, netWeight=Нето,tareWeight=Тара, volume=Обем, transUnitId = ЛЕ';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Полета за скриване/показване от шаблоните
     */
    public $toggleFields = 'packagingId=Опаковка,packQuantity=К-во,packPrice=Цена,discount=Отст.,amount=Сума,weight=Обем,netWeight=Нето,tareWeight=Тара,volume=Тегло,info=Инфо';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'info,discount,reff,transUnitId';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'requestedQuantity,weight,volume,netWeight,tareWeight,transUnitId,transUnitQuantity,tariffCode';
    
    
    /**
     * Полета, които се експортват
     */
    public $exportToMaster = 'quantity, productId=code|name';


    /**
     * Дали артикула ще произвежда при експедиране артикулите с моментна рецепта
     */
    public $manifactureProductsOnShipment = true;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('shipmentId', 'key(mvc=store_ShipmentOrders)', 'column=none,notNull,silent,hidden,mandatory');
        parent::setDocumentFields($this);
        $this->FLD('baseQuantity', 'double(minDecimals=2)', 'after=showMode,caption=Допълнително->Изписване,input=hidden');
        $this->FLD('showMode', 'enum(auto=По подразбиране,detailed=Разширен,short=Съкратен)', 'caption=Допълнително->Изглед,notNull,default=short,value=short,after=notes');
        $this->FLD('tariffCode', 'varchar', 'caption=Логистична информация->Митнически код,input=none');
        $this->setFieldTypeParams('packQuantity', 'min=0');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm(core_Mvc $mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        $masterRec = $data->masterRec;
        $property = ($masterRec->isReverse == 'yes') ? 'canBuy' : 'canSell';

        $productTypeParams = array('customerClass' => $masterRec->contragentClassId, 'customerId' => $masterRec->contragentId, 'hasProperties' => $property, 'hasnotProperties' => 'generic');
        if($masterRec->isReverse == 'no'){
            $priceData = array('valior' => $masterRec->valior, 'rate' => $masterRec->currencyRate, 'chargeVat' => $masterRec->chargeVat, 'currencyId' => $masterRec->currencyId, 'threadId' => $masterRec->threadId);
            $productTypeParams['priceData'] = $priceData;
        } else {
            if($mvc->Master->isDocForReturnFromDocument($masterRec)){
                $form->setReadOnly('packPrice');
            }
        }
        $form->setFieldTypeParams('productId', $productTypeParams);

        if (isset($rec->productId)) {
            $tariffCode = cat_Products::getParams($rec->productId, 'customsTariffNumber');
            $form->setField('tariffCode', "input,placeholder={$tariffCode}");
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form &$form)
    {
        $rec = &$form->rec;
        
        if (!$form->isSubmitted()) {
            if ($mvc->masterKey && $rec->{$mvc->masterKey}) {
                $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
            }
            
            if (isset($rec->productId, $masterRec)) {
                $foundQuantity = null;
                $masterStore = $masterRec->storeId;
                $canStore = cat_Products::fetchField($rec->productId, 'canStore');
                if ($canStore == 'yes') {
                    $deliveryDate = !empty($masterRec->deliveryTime) ? $masterRec->deliveryTime : $masterRec->valior;
                    $storeInfo = deals_Helper::checkProductQuantityInStore($rec->productId, $rec->packagingId, $rec->packQuantity, $masterStore, $deliveryDate, $foundQuantity);
                    $form->info = $storeInfo->formInfo;
                    if (!empty($foundQuantity) && $foundQuantity > 0) {
                        $form->setSuggestions('baseQuantity', array('' => '', "{$foundQuantity}" => $foundQuantity));
                    }
                }
            }
        }
        
        parent::inputDocForm($mvc, $form);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $rows = &$data->rows;
        
        if (!countR($data->recs)) {
            
            return;
        }
        
        $masterRec = $data->masterData->rec;
        foreach ($rows as $id => $row) {
            if ($row instanceof core_ET) {
                continue;
            }

            $rec = $data->recs[$id];
            $deliveryDate = !empty($masterRec->deliveryTime) ? $masterRec->deliveryTime : $masterRec->valior;
            deals_Helper::getQuantityHint($row->packQuantity, $mvc, $rec->productId, $masterRec->storeId, $rec->quantity, $masterRec->state, $deliveryDate, $masterRec->threadId);
            
            if (core_Users::haveRole('ceo,seePriceSale') && isset($row->packPrice) && $masterRec->isReverse == 'no') {
                $priceDate = ($masterRec == 'draft') ? null : $masterRec->valior;

                $comparedWithPrimeCostObj = sales_PrimeCostByDocument::comparePriceWithPrimeCost($rec->price, $rec->productId, $rec->packagingId, $rec->quantity, $masterRec->containerId, $priceDate, $mvc, $rec->id);
                if($comparedWithPrimeCostObj->bellowPrimeCost){
                    $warning = 'Цената е под себестойността';
                    if(isset($comparedWithPrimeCostObj->primeCost)){
                        $comparedWithPrimeCostObj->primeCost /= $masterRec->currencyRate;
                        $primeCostVerbal = core_Type::getByName('double(smartRound,minDecimals=2)')->toVerbal($comparedWithPrimeCostObj->primeCost * $rec->quantityInPack);
                        $warning = "{$warning}|*: {$primeCostVerbal} {$masterRec->currencyId} |без ДДС|*";
                        if($comparedWithPrimeCostObj->isCache){
                            $warning .= " (|Кеш|*)";
                        }
                    }
                    if(!Mode::isReadOnly()){
                        $row->packPrice = "<span class='priceBellowPrimeCost'>{$row->packPrice}</span>";
                        $row->packPrice = ht::createHint($row->packPrice, $warning, 'img/16/red-warning.png', false)->getContent();
                    }
                } elseif(in_array($masterRec->state, array('pending', 'draft'))) {

                    $listId = null;
                    $useQuotationPrice = false;
                    if($firstDocument = doc_Threads::getFirstDocument($masterRec->threadId)){
                        if($firstDocument->isInstanceOf('sales_Sales')){
                            $firstDocumentRec = $firstDocument->fetch('originId,priceListId');
                            $useQuotationPrice = isset($firstDocumentRec->originId);
                            $listId = $firstDocumentRec->priceListId;
                        }
                    }
                    
                    // Предупреждение дали цената е под очакваната за клиента
                    if($checkedObject = deals_Helper::checkPriceWithContragentPrice($rec->productId, $rec->price, $rec->discount, $rec->quantity, $rec->quantityInPack, $masterRec->contragentClassId, $masterRec->contragentId, $priceDate, $listId, $useQuotationPrice, $mvc, $masterRec->threadId, $masterRec->currencyRate, $masterRec->currencyId)){
                        $row->packPrice = ht::createHint($row->packPrice, $checkedObject['hint'], $checkedObject['hintType'], false);
                    }
                }
            }
        }
    }
    
    
    /**
     * След обработка на записите от базата данни
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        core_Lg::push($data->masterData->rec->tplLang);
        
        $date = ($data->masterData->rec->state == 'draft') ? null : $data->masterData->rec->modifiedOn;
        if (countR($data->rows)) {
            foreach ($data->rows as $i => &$row) {
                $rec = &$data->recs[$i];

                core_RowToolbar::createIfNotExists($row->_rowTools);
                cat_Products::addButtonsToDocToolbar($rec->productId, $row->_rowTools, $mvc->className, $rec->id);
                $row->productId = cat_Products::getAutoProductDesc($rec->productId, $date, $rec->showMode, 'public', $data->masterData->rec->tplLang, 1, false);
                deals_Helper::addNotesToProductRow($row->productId, $rec->notes);
            }
        }
        
        core_Lg::pop();
    }
    
    
    /**
     * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
     */
    public static function on_AfterGetRowInfo($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        $masterRec = store_ShipmentOrders::fetch($rec->shipmentId, 'isReverse,storeId');
        if ($masterRec->isReverse == 'yes') {
            $res->operation['out'] = $masterRec->storeId;
            unset($res->operation['in']);
        }
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        store_DocumentPackagingDetail::addBtnsToToolbar($data->toolbar, $mvc->Master, $data->masterId);
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (in_array($action, array('add', 'import', 'createproduct')) && isset($rec)) {
            $masterRec = $mvc->Master->fetch($rec->shipmentId);
            if ($masterRec->isReverse == 'yes' && isset($masterRec->reverseContainerId)) {
                $requiredRoles = 'no_one';
            }
        }
    }
}
