<?php



/**
 * Мениджър на заплати
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заплати
 */
class trz_SalaryPayroll extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Ведомост';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, plg_Rejected,  plg_SaveAndNew, 
                    trz_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,trz';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,trz';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,trz';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,trz';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,trz';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, periodId, ruleId, personId, amount';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	 $this->FLD('periodId',    'key(mvc=acc_Periods, select=title, where=#state !\\= \\\'closed\\\', allowEmpty=true)', 'caption=Период,width=100%');
    	 $this->FLD('ruleId',    'key(mvc=trz_SalaryRules, select=conditionExpr, allowEmpty=true)', 'caption=Правило,width=100%');
    	 $this->FLD('personId',    'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Лице,width=100%');
    	 $this->FLD('amount',    'double', 'caption=Сума,mandatory,width=100%');
    }
    
}