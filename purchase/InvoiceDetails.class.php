<?php 

/**
 * Детайли на фактурите
 *
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class purchase_InvoiceDetails extends deals_InvoiceDetail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на фактурата';
    
    
    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_Sorting, purchase_Wrapper, plg_RowNumbering, plg_SaveAndNew, plg_AlignDecimals2, doc_plg_HidePrices, deals_plg_DpInvoice,
                        Policy=purchase_PurchaseLastPricePolicy, cat_plg_LogPackUsage, deals_plg_ImportDealDetailProduct, plg_PrevAndNext,cat_plg_ShowCodes';
    
    
    /**
     * Кое е активното меню
     */
    public $pageMenu = 'Фактури';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'invoiceId';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, invoicerPurchase, invoicerFindeal';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, invoicerPurchase, invoicerFindeal';


    /**
     * Кой може да импортира
     */
    public $canImport = 'ceo, invoicerPurchase, invoicerFindeal';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, invoicerPurchase, invoicerFindeal';
    
    
    /**
     * Кой таб да бъде отворен
     */
    public $currentTab = 'Фактури';
    
    
    /**
     * Какви мета данни да изискват продуктите, които да се показват
     */
    public $metaProducts = 'canBuy';
    
    
    /**
     * Полета, които се експортват
     */
    public $exportToMaster = 'quantity, productId=code|name';


    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'autoDiscount,inputDiscount';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('invoiceId', 'key(mvc=purchase_Invoices)', 'caption=Фактура, input=hidden, silent');
        parent::setInvoiceDetailFields($this);
    }


    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'edit' || $action == 'delete' || $action == 'import') && isset($rec->{$mvc->masterKey})) {
            $threadId = $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'threadId');
            if($firstDoc = doc_Threads::getFirstDocument($threadId)){
                if($firstDoc->isInstanceOf('purchase_Purchases')){
                    if(!haveRole('invoicerPurchase,ceo')){
                        $res = 'no_one';
                    }
                } else {
                    if(!haveRole('invoicerFindeal,ceo')){
                        $res = 'no_one';
                    }
                }
            }
        }
    }
}
