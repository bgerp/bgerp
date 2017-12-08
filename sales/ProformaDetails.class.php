<?php



/**
 * Клас 'sales_ProformaDetails'
 *
 * Детайли на мениджър на документи за продажба на продукти (@see sales_pROFORMAS)
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_ProformaDetails extends deals_InvoiceDetail
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Детайли на проформата';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'proformaId';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'plg_RowTools2, plg_Created, sales_Wrapper, plg_RowNumbering, plg_SaveAndNew,
                        plg_AlignDecimals2, plg_Sorting, doc_plg_HidePrices,deals_plg_DpInvoice,Policy=price_ListToCustomers, 
                        LastPricePolicy=sales_SalesLastPricePolicy,plg_PrevAndNext,cat_plg_ShowCodes';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Търговия:Продажби';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, sales';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'ceo, sales';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';


    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Какви мета данни да изискват продуктите, които да се показват
     */
    public $metaProducts = 'canSell';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('proformaId', 'key(mvc=sales_Proformas)', 'column=none,notNull,silent,hidden,mandatory');
        parent::setInvoiceDetailFields($this);
    }
}
