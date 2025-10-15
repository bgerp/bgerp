<?php


/**
 * Клас 'store_ConsignmentProtocolDetailsSend'
 *
 * Детайли на мениджър на детайлите на протоколите за отговорни пазене-предадени
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
class store_ConsignmentProtocolDetailsSend extends store_InternalDocumentDetail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на протоколите за отговорни пазене-предадени';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'артикул за предаване';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'protocolId';
    
    
    /**
     * Кой може да го импортира артикули?
     *
     * @var string|array
     */
    public $canImport = 'ceo, store, distributor, sales, purchase';
    
    
    /**
     * Кой може да създава артикул директно към документа?
     *
     * @var string|array
     */
    public $canCreateproduct = 'ceo, store, sales, purchase';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, store_Wrapper, plg_RowNumbering, plg_SaveAndNew, 
                        plg_AlignDecimals2, cat_plg_LogPackUsage, LastPricePolicy=sales_SalesLastPricePolicy,cat_plg_CreateProductFromDocument,deals_plg_ImportDealDetailProduct, doc_plg_HidePrices, plg_PrevAndNext,store_plg_TransportDataDetail';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, store, distributor, sales, purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, store, distributor, sales, purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, store, distributor, sales, purchase';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId=Предаваме на Клиент/Доставчик, packagingId, packQuantity=К-во, weight=Тегло,volume=Обем,packPrice, amount,transUnitId=ЛЕ';


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
        $params['hasProperties'] = $mvc->getExpectedProductMetaProperties($masterRec->productType, 'send');
        if($masterRec->productType == 'other'){
            $params['isPublic'] = 'no';
        }

        $data->form->setFieldTypeParams('productId', $params);
    }


    /**
     * След инпутване на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form &$form)
    {
        $rec = &$form->rec;
        
        if (isset($rec->productId)) {
            $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
            $storeInfo = deals_Helper::checkProductQuantityInStore($rec->productId, $rec->packagingId, $rec->packQuantity, $masterRec->storeId, $masterRec->valior);
            $form->info = $storeInfo->formInfo;
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        if (!countR($data->recs)) return;

        $storeId = $data->masterData->rec->storeId;
        foreach ($data->rows as $id => $row) {
            $rec = $data->recs[$id];
            deals_Helper::getQuantityHint($row->packQuantity, $mvc, $rec->productId, $storeId, $rec->quantity, $data->masterData->rec->state, $data->masterData->rec->valior);
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'createproduct' && isset($rec)){
            $productType = store_ConsignmentProtocols::fetchField($rec->protocolId, 'productType');
            if($productType == 'other'){
                $requiredRoles = 'no_one';
            }
        }
    }
}
