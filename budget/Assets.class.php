<?php



/**
 * Мениджър на парични средства
 *
 *
 * @category  bgerp
 * @package   budget
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Парични средства
 */
class budget_Assets extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Парични средства';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Парично средство";
    
    
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
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'budget,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,budget';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'number, period, project, sume, currency, rate, company, person, state, location, department, activity';

    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('number','int', 'caption=Номер на операцията');
    	$this->FLD('period','enum(1=Седмичен,
    							  2=Месечен,
    							  3=Тримесечен,
    							  4=Годишен)', 'caption=Период');
    	$this->FLD('project','key(mvc=doc_UnsortedFolders, select=name)', 'caption=Проект');
    	$this->FLD('sume','double', 'caption=Парични средства->Сума');
    	$this->FLD('currency','key(mvc=currency_Currencies, select=code)', 'caption=Парични средства->Валута');
    	$this->FLD('rate','double', 'caption=Парични средства->Курс');
    	$this->FLD('company','key(mvc=crm_Companies, select=name)', 'caption=Контрагент->Фирма');
    	$this->FLD('person','key(mvc=crm_Persons, select=name)', 'caption=Контрагент->Име');
    	$this->FLD('state','enum(1=Идеен проект,
    							 2=Планиране,
    							 3=Проектиране,
    							 4=Работен проект,
    							 5=Реализация)', 'caption=Състояние');
    	$this->FLD('location','key(mvc=crm_Locations, select=title)', 'caption=Подразделение->Локация');
    	$this->FLD('department','key(mvc=hr_Departments, select=name)', 'caption=Подразделение->Отдел');
       	$this->FLD('activity','enum(1=Производство,
    								2=Администрация,
    								3=Маркетинг,
    								4=Логистика)', 'caption=Видове дейности->Дейност');
    }

}