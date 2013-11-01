<?php
/**
 * Клас 'trans_vehicles'
 *
 * Детайли на мениджър на експедиционни нареждания (@see store_ShipmentOrders)
 *
 * @category  bgerp
 * @package   trans
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trans_Vehicles extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Превозни средства';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Превозни средство';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'plg_RowTools, plg_Created, plg_Modified, trans_Wrapper, plg_RowNumbering';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, trans';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, trans';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, trans';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo, trans';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, trans';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'RowNumb=Пулт,name,type,operator,modifiedOn,modifiedBy';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('name', 'varchar(120)', 'caption=Име,mandatory');
    	$this->FLD('type', 'enum(truck=Камион,minibus=Минибус,pickup=Пикап)', 'caption=Вид');
    	$this->FLD('operator', 'user(roles=trans)', 'caption=Шофьор, mandatory');
    	$this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
    	
    	$this->setdbUnique('name');
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'delete' && isset($rec->lastUsedOn)){
    		$res = 'no_one';
    	}
    }
}