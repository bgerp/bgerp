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
    public $singleTitle = 'Продукт';
    
    
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
    public $loadList = 'plg_RowTools2, plg_Created, store_Wrapper, plg_RowNumbering, plg_SaveAndNew, doc_plg_HidePrices,store_plg_RequestDetail,
                        plg_AlignDecimals2, plg_Sorting, doc_plg_TplManagerDetail, LastPricePolicy=sales_SalesLastPricePolicy,
                        ReversePolicy=purchase_PurchaseLastPricePolicy, plg_PrevAndNext,cat_plg_ShowCodes,store_plg_TransportDataDetail,import2_Plugin';
    
    
    /**
     * Да се показва ли кода като в отделна колона
     */
    public $showCodeColumn = true;
    
    
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
     * Кой може да го изтрие?
     *
     * @var string|array
     */
    public $canDelete = 'ceo,store,sales,purchase';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'info=@Колети, productId, packagingId, packQuantity, packPrice, discount, amount, weight=Тегло, volume=Обем, transUnitId = ЛЕ';
    
    
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
    public $toggleFields = 'packagingId=Опаковка,packQuantity=Количество,packPrice=Цена,discount=Отстъпка,amount=Сума,weight=Обем,volume=Тегло,info=Инфо';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'info,discount,reff,transUnitId';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'requestedQuantity,weight,volume';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('shipmentId', 'key(mvc=store_ShipmentOrders)', 'column=none,notNull,silent,hidden,mandatory');
        parent::setDocumentFields($this);
        $this->FLD('baseQuantity', 'double(minDecimals=2)', 'after=showMode,caption=Допълнително->Изписване,input=hidden');
        $this->FLD('showMode', 'enum(auto=По подразбиране,detailed=Разширен,short=Съкратен)', 'caption=Допълнително->Изглед,notNull,default=short,value=short,after=notes');
        
        $this->setFieldTypeParams('packQuantity', 'min=0');
    }
    
    
    /**
     * Достъпните продукти
     */
    protected function getProducts($masterRec)
    {
        $property = ($masterRec->isReverse == 'yes') ? 'canBuy' : 'canSell';
        $property .= ',canStore';
        
        // Намираме всички продаваеми продукти, и оттях оставяме само складируемите за избор
        $products = cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->date, $property);
        
        return $products;
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
                $masterStore = $masterRec->storeId;
                $storeInfo = deals_Helper::checkProductQuantityInStore($rec->productId, $rec->packagingId, $rec->packQuantity, $masterStore, $foundQuantity);
                $form->info = $storeInfo->formInfo;
                if (!empty($foundQuantity) && $foundQuantity > 0) {
                    $form->setSuggestions('baseQuantity', array('' => '', "{$foundQuantity}" => $foundQuantity));
                }
            }
            
            if ($masterRec->template) {
                $tplRec = doc_TplManager::fetch($masterRec->template);
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
        
        if (!count($data->recs)) {
            
            return;
        }
        
        $storeId = $data->masterData->rec->storeId;
        foreach ($rows as $id => $row) {
            $rec = $data->recs[$id];
            $warning = deals_Helper::getQuantityHint($rec->productId, $storeId, $rec->quantity, $data->masterData->rec->state);
            
            if (strlen($warning) && in_array($data->masterData->rec->state, array('draft', 'pending'))) {
                $row->packQuantity = ht::createHint($row->packQuantity, $warning, 'warning', false, null, 'class=doc-negative-quantiy');
            }
            
            if ($rec->price < cat_Products::getSelfValue($rec->productId, null, $rec->quantity)) {
                if (!core_Users::haveRole('partner') && isset($row->packPrice)) {
                    $row->packPrice = ht::createHint($row->packPrice, 'Цената е под себестойността', 'warning', false);
                }
            }
        }
    }
    
    
    /**
     * Преди подготовката на полетата за листовия изглед
     */
    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        if (!empty($data->masterData->rec->deliveryTime)) {
            $data->showReffCode = true;
        }
    }
    
    
    /**
     * След обработка на записите от базата данни
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        core_Lg::push($data->masterData->rec->tplLang);
        
        $date = ($data->masterData->rec->state == 'draft') ? null : $data->masterData->rec->modifiedOn;
        if (count($data->rows)) {
            $totalLU = array();
            foreach ($data->rows as $i => &$row) {
                $rec = &$data->recs[$i];
                
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
}
