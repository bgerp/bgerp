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
                        Policy=purchase_PurchaseLastPricePolicy, plg_PrevAndNext,cat_plg_ShowCodes';
    
    
    /**
     * Кое е активното меню
     */
    public $pageMenu = 'Фактури';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'invoiceId';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'powerUser';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'invoicer, ceo';
    
    
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
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('invoiceId', 'key(mvc=purchase_Invoices)', 'caption=Фактура, input=hidden, silent');
        parent::setInvoiceDetailFields($this);
    }
}
