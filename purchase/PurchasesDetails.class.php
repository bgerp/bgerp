<?php



/**
 * Клас 'purchase_PurchasesDetails'
 *
 * Детайли на мениджър на документи за покупка на продукти (@see purchase_Requests)
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_PurchasesDetails extends deals_DealDetail
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Детайли на покупки';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'requestId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, purchase_Wrapper, plg_Sorting, plg_RowNumbering,acc_plg_ExpenseAllocation, doc_plg_HidePrices, plg_SaveAndNew, plg_AlignDecimals2,Policy=purchase_PurchaseLastPricePolicy, cat_plg_CreateProductFromDocument,doc_plg_HideMeasureAndQuantityColumns,cat_plg_ShowCodes';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Логистика:Покупки';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, purchase, partner';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, purchase, partner';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'user';
    
    
    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canImportlisted = 'user';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, packQuantity, packPrice, discount, amount';
    

    /**
     * Активен таб
     */
    public $currentTab = 'Покупки';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Какви мета данни да изискват продуктите, които да се показват
     */
    public $metaProducts = 'canBuy';
    
    
    /**
     * Филтър на прототипи по свойство
     */
    public $filterProtoByMeta = 'canBuy';
    
    
    /**
     * Какво движение на партида поражда документа в склада
     *
     * @param out|in|stay - тип движение (излиза, влиза, стои)
     */
    public $batchMovementDocument = 'in';
    
    
    /**
     * Да се показва ли вашия номер
     */
    public $showReffCode = TRUE;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('requestId', 'key(mvc=purchase_Purchases)', 'column=none,notNull,silent,hidden,mandatory');
        
        parent::getDealDetailFields($this);
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
    	parent::inputDocForm($mvc, $form);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add') && isset($rec)){
    		if($requiredRoles != 'no_one'){
    			$roles = purchase_Setup::get('ADD_BY_PRODUCT_BTN');
    			if(!haveRole($roles, $userId)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    	
    	if($action == 'importlisted'){
    		$roles = purchase_Setup::get('ADD_BY_LIST_BTN');
    		if(!haveRole($roles, $userId)){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    }
}