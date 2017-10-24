<?php



/**
 * Мениджър за "Детайли на заявките за продажба" 
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class sales_SaleRequestDetails extends deals_DealDetail {
    
    /**
     * Заглавие
     */
    public $title = 'Детайли на заявките за продажба';
    
    
    /**
	 * Мастър ключ към заявката
	 */
	public $masterKey = 'requestId';
    
    
    /**
     * Кой може да променя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_AlignDecimals2, plg_RowNumbering, sales_Wrapper, doc_plg_HidePrices, plg_PrevAndNext';
    
    
    /**
     * Кой може да променя?
     */
    public $canList = 'no_one';
    
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, uomId, packQuantity, packPrice, discount, amount';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,discount,amount';
    
    
    /**
     * Помощен масив (@see deals_Helper)
     */
    public static $map = array('priceFld' => 'price', 'quantityFld' => 'quantity', 'valior' => 'createdOn');
    
    
  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('requestId', 'key(mvc=sales_SaleRequests)', 'column=none,notNull,silent,hidden,mandatory');
    	parent::getDealDetailFields($this);
    }
}