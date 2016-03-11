<?php 


/**
 * Детайли на фактурите
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_InvoiceDetails extends deals_InvoiceDetail
{
    
    
    /**
     * Заглавие
     */
    public $title = "Детайли на фактурата";
    
    
    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Старо име на класа
     */
    public $oldClassName = 'acc_InvoiceDetails';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, plg_Sorting, sales_Wrapper, plg_RowNumbering, plg_SaveAndNew, plg_AlignDecimals2, doc_plg_HidePrices, deals_plg_DpInvoice,Policy=price_ListToCustomers, LastPricePolicy=sales_SalesLastPricePolicy';
    
    
    /**
     * Кое е активното меню
     */
    public $pageMenu = "Фактури";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'invoiceId';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'invoicer, ceo';
    
    
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
    public $metaProducts = 'canSell';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('invoiceId', 'key(mvc=sales_Invoices)', 'caption=Фактура, input=hidden, silent');
        parent::setInvoiceDetailFields($this);
    }
}