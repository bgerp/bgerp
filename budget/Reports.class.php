<?php



/**
 * Мениджър на справки по подразделения / дейности
 *
 *
 * @category  bgerp
 * @package   budget
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     По подразделения / Дейности
 */
class budget_Reports extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'По подразделения / Дейности';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Дейност";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools2, plg_Created, plg_SaveAndNew, 
                    budget_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,budget';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,budget';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,budget';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,budget';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'budget,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,budget';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'location, department, activity, fromDate, toDate, state, accountId,
                       baseQuantity, baseAmount, debitQuantity, debitAmount';

    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('location','key(mvc=crm_Locations, select=title)', 'caption=Подразделение->Локация');
    	$this->FLD('department','key(mvc=hr_Departments, select=name)', 'caption=Подразделение->Отдел');
       	$this->FLD('activity','enum(1=Производство,
    								2=Администрация,
    								3=Маркетинг,
    								4=Логистика)', 'caption=Видове дейности->Дейност');
    	$this->FLD('fromDate', 'date', 'caption=Период->от');
        $this->FLD('toDate', 'date', 'caption=Период->до');
        $this->FLD('state', 'enum(draft=Горещ,active=Активен,rejected=Изтрит)', 'caption=Тип');
        $this->FLD('accountId', 'key(mvc=acc_Accounts,select=title)', 'caption=Сметка->име');
        $this->FLD('baseQuantity', 'double', 'caption=База->К-во');
        $this->FLD('baseAmount', 'double(decimals=2)', 'caption=База->Сума');
        $this->FLD('debitQuantity', 'double', 'caption=Оборот->К-во');
        $this->FLD('debitAmount', 'double(decimals=2)', 'caption=Оборот->Сума');

    }
 
}