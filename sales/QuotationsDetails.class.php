<?php


/**
 * Мениджър за "Детайли на офертите"
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class sales_QuotationsDetails extends deals_QuotationDetails
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на офертите';

    /**
     * Кой може да променя?
     */
    public $canAdd = 'ceo,sales';


    /**
     * Кой може да импортира?
     */
    public $canImport = 'ceo,sales';


    /**
     * Кой може да променя?
     */
    public $canDelete = 'ceo,sales';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, sales_Wrapper, doc_plg_HidePrices, deals_plg_ImportDealDetailProduct, plg_SaveAndNew, LastPricePolicy=sales_SalesLastPricePolicy, cat_plg_CreateProductFromDocument,plg_PrevAndNext,cat_plg_ShowCodes';


    /**
     * Кой таб да бъде отворен
     */
    public $currentTab = 'Оферти';
    
    
    /**
     * Какви мета данни да изискват продуктите, които да се показват
     */
    public $metaProducts = 'canSell';


    /**
     * Дефолтен шаблон за показване на детайлите
     */
    public $normalDetailFile = 'sales/tpl/LayoutQuoteDetails.shtml';


    /**
     * Кратък шаблон за показване на детайлите
     */
    public $shortDetailFile = 'sales/tpl/LayoutQuoteDetailsShort.shtml';


    /**
     * Най-кратък шаблон за показване на детайлите
     */
    public $shortestDetailFile = 'sales/tpl/LayoutQuoteDetailsShortest.shtml';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('quotationId', 'key(mvc=sales_Quotations)', 'column=none,notNull,silent,hidden,mandatory');
        parent::addDetailFields($this);
    }
    
    
    /**
     * Помощна ф-я за лайв изчисляване на цената
     *
     * @param stdClass $rec
     * @param stdClass $masterRec
     *
     * @return void;
     */
    public static function calcLivePrice($rec, $masterRec, $force = false)
    {
        if ($force !== true && !doc_plg_HidePrices::canSeePriceFields(cls::get(get_called_class())->Master, $masterRec)) {
            
            return;
        }
        
        $listId = ($masterRec->priceListId) ? $masterRec->priceListId : null;
        $policyInfo = cls::get('price_ListToCustomers')->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->quantity, $rec->date, $masterRec->currencyRate, $masterRec->chargeVat, $listId, false);
        
        if (isset($policyInfo->price)) {
            $rec->price = $policyInfo->price;
            $rec->price = deals_Helper::getPurePrice($rec->price, cat_Products::getVat($rec->productId, $rec->date), $masterRec->currencyRate, $masterRec->chargeVat);
            
            // Добавяне на транспортните разходи, ако има
            $fee = sales_TransportValues::get('sales_Quotations', $rec->quotationId, $rec->id)->fee;
            
            if (isset($fee) && $fee > 0) {
                $rec->price += $fee / $rec->quantity;
            }
            
            if (!isset($rec->discount)) {
                $rec->discount = $policyInfo->discount;
            }
        }
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass     $res
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        if (!empty($data->form->rec->id) || $data->form->cmd == 'save_new_row') {
            $data->form->toolbar->addSbBtn('Запис в нов ред', 'save_new_row', null, array('id' => 'saveInNewRec', 'order' => '9', 'ef_icon' => 'img/16/save_and_new.png', 'title' => 'Запиши в нов ред'));
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        parent::inputQuoteDetailsForm($mvc, $form);

        $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
        $priceAtDate = (isset($masterRec->date)) ? $masterRec->date : dt::today();

        if(isset($rec->productId)) {

            if (isset($mvc->LastPricePolicy)) {
                $policyInfoLast = $mvc->LastPricePolicy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->packQuantity, $priceAtDate, $masterRec->currencyRate, $masterRec->chargeVat);
                if ($policyInfoLast->price != 0) {
                    $form->setSuggestions('packPrice', array('' => '', "{$policyInfoLast->price}" => $policyInfoLast->price));
                }
            }
        }
        
        if ($form->isSubmitted()) {
            if (isset($rec->productId)) {
                sales_TransportValues::prepareFee($rec, $form, $masterRec, array('masterMvc' => 'sales_Quotations', 'deliveryLocationId' => 'deliveryPlaceId', 'countryId' => 'contragentCountryId'));
            }
        }
    }
    
    
    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    public static function recToVerbal_($rec, &$fields = array())
    {
        $row = parent::recToVerbal_($rec, $fields);
        $masterRec = sales_Quotations::fetch($rec->quotationId);

        $hintTerm = false;
        $row->tolerance = deals_Helper::getToleranceRow($rec->tolerance, $rec->productId, $rec->quantity);
        $term = $rec->term;
        if (!isset($term)) {
            if ($term = cat_Products::getDeliveryTime($rec->productId, $rec->quantity)) {
                $hintTerm = true;
                if ($deliveryTime = sales_TransportValues::get('sales_Quotations', $rec->quotationId, $rec->id)->deliveryTime) {
                    $term += $deliveryTime;
                }
            }
        }
        
        if (isset($term)) {
            if(empty($masterRec->deliveryTermTime) && empty($masterRec->deliveryTime)){
                $row->term = core_Type::getByName('time(uom=days,noSmart)')->toVerbal($term);
                if ($hintTerm === true) {
                    $row->term = ht::createHint($row->term, 'Срокът на доставка е изчислен автоматично на база количеството и параметрите на артикула');
                }
            } else {
                unset($row->term);
            }
        }

        // Показване на теглото при определени условия
        if ($rec->showMode == 'detailed' || ($rec->showMode == 'auto' && cat_Products::fetchField($rec->productId, 'isPublic') == 'no')) {

            // Показва се теглото, само ако мярката не е производна на килограм
            $kgMeasures = cat_UoM::getSameTypeMeasures(cat_UoM::fetchBySysId('kg')->id);

            if (!array_key_exists($rec->packagingId, $kgMeasures)) {
                $row->weight = deals_Helper::getWeightRow($rec->productId, $rec->packagingId, $rec->quantity, $masterRec->state, $rec->weight);
            } else {
                unset($row->weight);
            }
        } else {
            unset($row->weight);
        }

        return $row;
    }
    
    
    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'createproduct' && isset($rec->cloneId)) {
            $cloneRec = $mvc->fetch($rec->cloneId);
            if ($cloneRec->optional != 'no') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        // Синхронизиране на сумата на транспорта
        if ($rec->syncFee === true) {
            sales_TransportValues::sync($mvc->Master, $rec->quotationId, $rec->id, $rec->fee, $rec->deliveryTimeFromFee, $rec->_transportExplained);
        }
    }
    
    
    /**
     * След изтриване на запис
     */
    public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        // Инвалидиране на изчисления транспорт, ако има
        foreach ($query->getDeletedRecs() as $rec) {
            sales_TransportValues::sync($mvc->Master, $rec->quotationId, $rec->id, null);
        }
    }
    
    
    /**
     * Изпълнява се преди клониране
     */
    protected static function on_BeforeSaveClonedDetail($mvc, &$rec, $oldRec)
    {
        // Изчисляване на транспортните разходи
        if (core_Packs::isInstalled('tcost')) {
            $form = sales_QuotationsDetails::getForm();
            $clone = clone sales_Quotations::fetch($rec->quotationId);
            $clone->deliveryPlaceId = (!empty($clone->deliveryPlaceId)) ? crm_Locations::fetchField(array("#title = '[#1#]' AND #contragentCls = '{$clone->contragentClassId}' AND #contragentId = '{$clone->contragentId}'", $clone->deliveryPlaceId), 'id') : null;
            sales_TransportValues::prepareFee($rec, $form, $clone, array('masterMvc' => 'sales_Quotations', 'deliveryLocationId' => 'deliveryPlaceId'));
        }
        
        $packRec = cat_products_Packagings::getPack($rec->productId, $rec->packagingId);
        $rec->quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
        
        // Ако артикула е стандартен и в момента не може да му се клонира цена да се клонира и старата му
        $isPublic = cat_Products::fetchField($rec->productId, 'isPublic');
        if($isPublic == 'yes'){
            $masterRec = sales_Quotations::fetch($rec->quotationId);
            
            $clone = clone $rec;
            self::calcLivePrice($clone, $masterRec);
            if(empty($clone->price)){
                $rec->price = $oldRec->price;
            }
        }
    }
}
