<?php


/**
 * Клас 'sales_SalesDetails'
 *
 * Детайли на мениджър на документи за продажба на продукти (@see sales_Sales)
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_SalesDetails extends deals_DealDetail
{
    /**
     * Заглавие
     *
     * @var string
     */
    public $title = 'Детайли на продажби';
    
    
    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'saleId';
    
    
    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'plg_RowTools2, plg_Created, sales_Wrapper, plg_RowNumbering, plg_SaveAndNew, plg_PrevAndNext,
                        plg_AlignDecimals2, plg_Sorting, deals_plg_ImportDealDetailProduct, doc_plg_HidePrices, LastPricePolicy=sales_SalesLastPricePolicy,cat_plg_CreateProductFromDocument,doc_plg_HideMeasureAndQuantityColumns,cat_plg_ShowCodes';
    
    
    /**
     * Активен таб на менюто
     *
     * @var string
     */
    public $menuPage = 'Търговия:Продажби';


    /**
     * Да се показва ли вашия номер
     */
    public $showReffCode = true;


    /**
     * Полета за скриване/показване от шаблоните
     */
    public $toggleFields = 'packagingId=Опаковка,packQuantity=К-во,packPrice=Цена,discount=Отст.,amount=Сума';


    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canEdit = 'sales,ceo,partner';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'sales,ceo,partner';
    
    
    /**
     * Кой има право да изтрива системните данни?
     */
    public $canDeletesysdata = 'sales,ceo,partner';
    
    
    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canImportlisted = 'user';
    
    
    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = 'user';
    
    
    /**
     * Кой може да го изтрие?
     *
     * @var string|array
     */
    public $canDelete = 'sales,ceo,partner';
    
    
    /**
     * Кой може да го импортира артикули?
     *
     * @var string|array
     */
    public $canImport = 'user';
    
    
    /**
     * Кой може да създава артикул директно към документа?
     *
     * @var string|array
     */
    public $canCreateproduct = 'user';
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'saleId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, packQuantity=К-во, packPrice, discount=Отст., amount';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Какви мета данни да изискват продуктите, които да се показват
     */
    public $metaProducts = 'canSell';


    /**
     * Кой може клонира артикулите от оригиналния клониран договор?
     *
     * @var string|array
     */
    public $canCopydetailsfromcloned = 'ceo, sales';


    /**
     * Дали артикула ще произвежда при експедиране артикулите с моментна рецепта
     */
    public $manifactureProductsOnShipment = true;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('saleId', 'key(mvc=sales_Sales)', 'column=none,notNull,silent,hidden,mandatory');
        parent::getDealDetailFields($this);
        $this->setField('packPrice', 'silent');
        $this->setField('discount', 'class=w1');
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = &$form->rec;
        $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
        if (isset($rec->productId)) {
            $pInfo = cat_Products::getProductInfo($rec->productId);
            
            if (isset($pInfo->meta['canStore']) && $masterRec->shipmentStoreId) {
                $deliveryDate = $mvc->Master->getDeliveryDate($masterRec);
                $storeInfo = deals_Helper::checkProductQuantityInStore($rec->productId, $rec->packagingId, $rec->packQuantity, $masterRec->shipmentStoreId, $deliveryDate);
                $form->info = $storeInfo->formInfo;
            }
        }
        
        parent::inputDocForm($mvc, $form);
        
        // След събмит
        if ($form->isSubmitted()) {
            
            // Подготовка на сумата на транспорта, ако има
            sales_TransportValues::prepareFee($rec, $form, $masterRec);
        }
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
            $rec = $data->recs[$id];

            $row->discount = deals_Helper::getDiscountRow($rec->discount, $rec->inputDiscount, $rec->autoDiscount, $masterRec->state);
            $deliveryDate = $mvc->Master->getDeliveryDate($masterRec);
            deals_Helper::getQuantityHint($row->packQuantity, $mvc, $rec->productId, $masterRec->shipmentStoreId, $rec->quantity, $masterRec->state, $deliveryDate);

            if (core_Users::haveRole('ceo,seePriceSale') && isset($row->packPrice)) {
               $hintField = isset($data->listFields['packPrice']) ? 'packPrice' : 'amount';
               $priceDate = ($masterRec == 'draft') ? null : $masterRec->valior;
               
               // Предупреждение дали цената е под себестойност
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
                       $row->{$hintField} = "<span class='priceBellowPrimeCost'>{$row->{$hintField}}</span>";
                       $row->{$hintField} = ht::createHint($row->{$hintField}, $warning, 'img/16/red-warning.png', false)->getContent();
                   }
               } elseif(in_array($masterRec->state, array('pending', 'draft'))){
                   
                   // Предупреждение дали цената е под очакваната за клиента
                   $useQuotationPrice = isset($masterRec->originId);
                   $discountPercent = ($rec->autoDiscount) ? round((1 - (1 - $rec->discountPercent) * (1 - $rec->autoDiscount)), 4) : $rec->discount;
                   $transportFeeRec = sales_TransportValues::get($mvc->Master, $rec->saleId, $rec->id);
                   if($checkedObject = deals_Helper::checkPriceWithContragentPrice($rec->productId, $rec->price, $discountPercent, $rec->quantity, $rec->quantityInPack, $masterRec->contragentClassId, $masterRec->contragentId, $priceDate, $masterRec->priceListId, $useQuotationPrice, $mvc, $masterRec->threadId, $masterRec->currencyRate, $masterRec->currencyId, $transportFeeRec)){
                        $row->{$hintField} = ht::createHint($row->{$hintField}, $checkedObject['hint'], $checkedObject['hintType'], false);
                   }
               }
            }
            
            // Ако е имало проблем при изчисляването на скрития транспорт, показва се хинт
            $fee = sales_TransportValues::get($mvc->Master, $rec->saleId, $rec->id);
            $vat = cat_Products::getVat($rec->productId, $masterRec->valior, $masterRec->vatExceptionId);
            if(doc_plg_HidePrices::canSeePriceFields($mvc->Master, $masterRec)){
                $row->amount = sales_TransportValues::getAmountHint($row->amount, $fee->fee, $vat, $masterRec->currencyRate, $masterRec->chargeVat, $masterRec->currencyId, $fee->explain);
            }
        }
    }
    
    
    /**
     * Изпълнява се преди клониране на детайла
     */
    protected static function on_BeforeSaveClonedDetail($mvc, &$rec, $oldRec)
    {
        $recalcPricesOnClone = sales_Setup::get('RECALC_PRICES_ON_CLONE');
        if($recalcPricesOnClone == 'no'){

            // Ако не може да се изчисли цената и остави оригиналната - приспада се от нея скрития транспорт ако има
            $cRec = sales_TransportValues::get($mvc->Master, $oldRec->saleId, $oldRec->id);
            if (isset($cRec->fee) && $cRec->fee > 0) {
                $rec->price -= $cRec->fee / $rec->quantity;
            }

            return;
        }

        $masterRec = sales_Sales::fetch($rec->saleId);

        // Прави се опит да се преизичсли наново цената
        $listId = ($masterRec->priceListId) ? $masterRec->priceListId : null;
        $policyInfo = cls::get('price_ListToCustomers')->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->quantity, $masterRec->valior, $masterRec->currencyRate, $masterRec->chargeVat, $listId);
        if (isset($policyInfo->price)) {

            // Ако има нова цена подменя се
            $rec->price = $policyInfo->price;
            $rec->price = deals_Helper::getPurePrice($rec->price, cat_Products::getVat($rec->productId, $masterRec->valior, $masterRec->vatExceptionId), $masterRec->currencyRate, $masterRec->chargeVat);
            $rec->discount = $policyInfo->discount;
        } else {;
            $rec->discount = $oldRec->inputDiscount;
            $cRec = sales_TransportValues::get($mvc->Master, $oldRec->saleId, $oldRec->id);
            if (isset($cRec->fee) && $cRec->fee > 0) {
                $rec->price -= $cRec->fee / $rec->quantity;
            }
        }



    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        if($rec->_isClone){
            $masterRec = $mvc->Master->fetch($rec->saleId);
            if($masterRec->deliveryCalcTransport == 'yes'){

                // След клониране се прави опит да се преизчисли транспорта
                sales_TransportValues::recalcTransport($mvc->getClassId(), $rec->id);
                $cRec = sales_TransportValues::get($mvc->Master, $rec->saleId, $rec->id);
                if (isset($cRec)) {
                    // Ако може то той ще се запише
                    $rec->fee = $cRec->fee;
                    $rec->deliveryTimeFromFee = $cRec->deliveryTime;
                    $rec->_transportExplained = $cRec->explain;
                    $rec->syncFee = true;

                    // Към новата или старата цена (без транспорт) се добавя този на новия изчислен транспорт
                    if (isset($rec->fee) && $rec->fee > 0) {
                        $rec->price += $rec->fee / $rec->quantity;
                        $mvc->save_($rec, 'price');
                    }
                }
            }
        }

        // Синхронизиране на сумата на транспорта
        if ($rec->syncFee === true) {
            sales_TransportValues::sync($mvc->Master, $rec->{$mvc->masterKey}, $rec->id, $rec->fee, $rec->deliveryTimeFromFee, $rec->_transportExplained);
        }
    }
    
    
    /**
     * След изтриване на запис
     */
    public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        // Инвалидиране на изчисления транспорт, ако има
        foreach ($query->getDeletedRecs() as $id => $rec) {
            sales_TransportValues::sync($mvc->Master, $rec->saleId, $rec->id, null);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add') && isset($rec)) {
            if ($requiredRoles != 'no_one') {
                $roles = sales_Setup::get('ADD_BY_PRODUCT_BTN');
                
                if (!haveRole($roles, $userId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'importlisted') {
            $roles = sales_Setup::get('ADD_BY_LIST_BTN');
            if (!haveRole($roles, $userId)) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Извиква се преди подготовката на колоните
     */
    protected static function on_BeforePrepareListFields($mvc, &$res, $data)
    {
        $data->showCodeColumn = sales_Setup::get('SHOW_CODE_IN_SEPARATE_COLUMN') == 'yes';
    }
}
