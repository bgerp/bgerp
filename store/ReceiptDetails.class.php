<?php


/**
 * Клас 'store_ReceiptDetails'
 *
 * Детайли на мениджър на детайлите на складовите разписки (@see store_ReceiptDetails)
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_ReceiptDetails extends deals_DeliveryDocumentDetail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на складовите разписки';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Продукт';
    
    
    /**
     * Интерфейс на драйверите за импортиране
     */
    public $importInterface = 'store_iface_ImportDetailIntf';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'receiptId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, store_Wrapper, plg_SaveAndNew, plg_RowNumbering,store_plg_RequestDetail,Policy=purchase_PurchaseLastPricePolicy, 
                        plg_AlignDecimals2,deals_plg_ImportDealDetailProduct, plg_Sorting, doc_plg_HidePrices, ReverseLastPricePolicy=sales_SalesLastPricePolicy, 
                        Policy=purchase_PurchaseLastPricePolicy,acc_plg_ExpenseAllocation, plg_PrevAndNext,cat_plg_ShowCodes,store_plg_TransportDataDetail,import2_Plugin';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Логистика:Складове';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, store, purchase, sales';
    
    /**
     * Кой може да го импортира артикули?
     *
     * @var string|array
     */
    public $canImport = 'user';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, store, purchase, sales';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, store, purchase, sales';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, packQuantity, packPrice, discount, amount, weight=Тегло, volume=Обем,info=Инфо';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price, amount, discount, packPrice';
    
    
    /**
     * Полета за скриване/показване от шаблоните
     */
    public $toggleFields = 'packagingId=Опаковка,packQuantity=Количество,packPrice=Цена,discount=Отстъпка,amount=Сума,weight=Обем,volume=Тегло,info=Инфо';
    
    
    /**
     * Какво движение на партида поражда документа в склада
     *
     * @param out|in|stay - тип движение (излиза, влиза, стои)
     */
    public $batchMovementDocument = 'in';
    
    
    /**
     * Да се показва ли кода като в отделна колона
     */
    public $showCodeColumn = true;
    
    
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
        $this->FLD('receiptId', 'key(mvc=store_Receipts)', 'column=none,notNull,silent,hidden,mandatory');
        parent::setDocumentFields($this);
        $this->FLD('baseQuantity', 'double(minDecimals=2)', 'after=showMode,caption=Допълнителна мярка->Засклаждане,input=hidden,autohide');
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
        $masterRec = $data->masterRec;
        $property = ($masterRec->isReverse == 'yes') ? 'canSell' : 'canBuy';
        $form->setFieldTypeParams('productId', array('customerClass' => $masterRec->contragentClassId, 'customerId' => $masterRec->contragentId, 'hasProperties' => $property));
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
                
                $row->productId = cat_Products::getAutoProductDesc($rec->productId, $date, 'short', 'public', $data->masterData->rec->tplLang, 1, false);
                deals_Helper::addNotesToProductRow($row->productId, $rec->notes);
            }
        }
    }
    
    
    /**
     * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
     */
    public static function on_AfterGetRowInfo($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        $masterRec = store_Receipts::fetch($rec->receiptId, 'isReverse,storeId');
        if ($masterRec->isReverse == 'yes') {
            $res->operation['in'] = $masterRec->storeId;
            unset($res->operation['out']);
        }
    }
}
