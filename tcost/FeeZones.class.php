<?php


/**
 * Модел "Взаимодействие на Зони и Налва"
 *
 *
 * @category  bgerp
 * @package   tcost
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tcost_FeeZones extends core_Master
{


	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'trans_FeeZones';
	
	
    /**
     * Полета, които се виждат
     */
    public $listFields = "name,deliveryTermId, createdOn, createdBy";


    /**
     * Заглавие
     */
    public $title = "Имена на зони";


    /**
     * Плъгини за зареждане
     */
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, plg_Printing, tcost_Wrapper";


    /**
     * Време за опресняване информацията при лист на събитията
     */
    public $refreshRowsTime = 5000;


    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,admin,tcost';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,admin,tcost';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,admin,tcost';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,tcost';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,tcost';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,admin,tcost';


    /**
     * Детайли за зареждане
     */
    public $details = "tcost_Fees, tcost_Zones";


    /**
     * Единично поле за RowTools
     */
    public $rowToolsSingleField = 'name';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        //id column
        $this->FLD('name', 'varchar(16)', 'caption=Зона, mandatory');
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms, select = codeName)', 'caption=Условие на доставка, mandatory');
    }
    
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'delete' && isset($rec)){
    		if(tcost_Fees::fetch("#feeId = {$rec->id}") || tcost_Zones::fetch("#zoneId = {$rec->id}")){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
}