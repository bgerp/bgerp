<?php
/**
 * Клас 'sales_SalesDetails'
 *
 * Детайли на мениджър на документи за продажба на продукти (@see sales_Sales)
 *
 * @category  bgerp
 * @package   sales
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_ProformaDetails extends deals_InvoiceDetail
{
    /**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Детайли на проформата';


    /**
     * Заглавие в единствено число
     *
     * @var string
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
    public $loadList = 'plg_RowTools, plg_Created, sales_Wrapper, plg_RowNumbering, plg_SaveAndNew,
                        plg_AlignDecimals2, doc_plg_HidePrices,Policy=price_ListToCustomers, LastPricePolicy=sales_SalesLastPricePolicy';
    
    
    /**
     * Активен таб на менюто
     * 
     * @var string
     */
    public $menuPage = 'Търговия:Продажби';
    
    
    /**
     * Кой има право да чете?
     * 
     * @var string|array
     */
    public $canRead = 'ceo, sales';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'ceo, sales';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
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
        
        $this->setDbUnique('proformaId, productId, packagingId');
    }
}
