<?php



/**
 * Мениджър на заплати
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заплати
 */
class trz_SalaryRules extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = ' Правила';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected,  plg_SaveAndNew, 
                    trz_Wrapper, trz_SalaryWrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,trz';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,trz';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,trz';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, from, to, personId, departmentId, positionId, conditionExpr, amountExpr';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('from',    'date', 'caption=Период->От,mandatory,width=100%');
    	$this->FLD('to',    'date', 'caption=Период->До,mandatory,width=100%');
    	$this->FLD('personId',    'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Лице,mandatory,width=100%');
    	$this->FLD('departmentId',    'key(mvc=hr_Departments, select=name)', 'caption=Отдел,mandatory,width=100%');
    	$this->FLD('positionId',    'key(mvc=hr_Positions, select=name)', 'caption=Длъжност,mandatory,width=100%');
    	$this->FLD('conditionExpr',    'text', 'caption=Условие,mandatory,width=100%');
    	$this->FLD('amountExpr',    'text', 'caption=Сума,mandatory,width=100%');
    	
    }

}