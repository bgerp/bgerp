<?php



/**
 * Мениджър на баланси
 *
 *
 * @category  bgerp
 * @package   budget
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Баланси
 */
class budget_Balances extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Баланси';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Баланс";
    
    
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
	 * Кой може да го разглежда?
	 */
	var $canList = 'budget,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,budget';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,budget';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,budget';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'fromDate, toDate, state, accountId,
                       baseQuantity, baseAmount, debitQuantity, debitAmount, creditQuantity, 
                       creditAmount, blQuantity, blAmount';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	
        $this->FLD('fromDate', 'date', 'caption=Период->от');
        $this->FLD('toDate', 'date', 'caption=Период->до');
        $this->FLD('state', 'enum(draft=Горещ,active=Активен,rejected=Изтрит)', 'caption=Тип');
        $this->FLD('accountId', 'key(mvc=acc_Accounts,select=title)', 'caption=Сметка->име');
        $this->FLD('baseQuantity', 'double', 'caption=База->К-во');
        $this->FLD('baseAmount', 'double(decimals=2)', 'caption=База->Сума');
        $this->FLD('debitQuantity', 'double', 'caption=Дебит->К-во');
        $this->FLD('debitAmount', 'double(decimals=2)', 'caption=Дебит->Сума');
        $this->FLD('creditQuantity', 'double', 'caption=Кредит->К-во');
        $this->FLD('creditAmount', 'double(decimals=2)', 'caption=Кредит->Сума');
        $this->FLD('blQuantity', 'double', 'caption=Салдо->К-во');
        $this->FLD('blAmount', 'double(decimals=2)', 'caption=Салдо->Сума');
    }
 
}