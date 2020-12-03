<?php


/**
 * Клас 'purchase_ServicesDetails'
 *
 * Детайли на мениджър на приемателния протокол
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class purchase_ServicesDetails extends deals_DeliveryDocumentDetail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на приемателния протокол';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Услуга';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'shipmentId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, purchase_Wrapper, plg_RowNumbering, plg_SaveAndNew, acc_plg_ExpenseAllocation,
                        plg_AlignDecimals2, plg_Sorting, doc_plg_HidePrices,ReverseLastPricePolicy=sales_SalesLastPricePolicy, 
                        Policy=purchase_PurchaseLastPricePolicy, plg_PrevAndNext,doc_plg_HideMeasureAndQuantityColumns,cat_plg_ShowCodes';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, purchase';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId=Мярка, packQuantity, packPrice, discount, amount';
    
    
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
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('shipmentId', 'key(mvc=purchase_Services)', 'column=none,notNull,silent,hidden,mandatory');
        parent::setDocumentFields($this);
        $this->setFieldTypeParams('packQuantity', 'Min=0');
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
        $masterRec = $data->masterRec;
        $property = ($masterRec->isReverse == 'yes') ? 'canSell' : 'canBuy';
        
        $form->setFieldTypeParams('productId', array('customerClass' => $masterRec->contragentClassId, 'customerId' => $masterRec->contragentId, 'hasProperties' => $property, 'hasnotProperties' => 'canStore,generic'));
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form &$form)
    {
        parent::inputDocForm($mvc, $form);
    }
    
    
    /**
     * След обработка на записите от базата данни
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        $date = ($data->masterData->rec->state == 'draft') ? null : $data->masterData->rec->modifiedOn;
        
        if (countR($data->rows)) {
            foreach ($data->rows as $i => &$row) {
                $rec = &$data->recs[$i];
                $row->productId = cat_Products::getAutoProductDesc($rec->productId, $date, 'title', 'public', $data->masterData->rec->tplLang);
                deals_Helper::addNotesToProductRow($row->productId, $rec->notes);
            }
        }
    }
}
