<?php



/**
 * Мениджър на бонуси
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Бонуси
 */
class trz_Bonuses extends core_Master
{
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'trz_SalaryIndicatorsSourceIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Премии';
    
     
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Премия";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, plg_SaveAndNew, 
                    trz_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,trz';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,trz';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,trz';
    
    
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
    var $listFields = 'id, periodId, personId, type, sum';
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "5.5|Човешки ресурси"; 
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('periodId', 'date',     'caption=Дата');
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител');
    	$this->FLD('type', 'varchar',     'caption=Произход на бонуса');
    	$this->FLD('sum', 'double',     'caption=Сума');
    	
    }
    
    function act_Test()
    {
    	$date = '2013-07-16';
    }
    
    
    /**
     * Интерфейсен метод на trz_SalaryIndicatorsSourceIntf
     * 
     * @param date $date
     * @return array $result
     */
    function getSalaryIndicators($date)
    {
    	$query = self::getQuery();
    	$query->where("#periodId  = '{$date}'");
    	     	 
    	while($rec = $query->fetch()){
    	
    		$result[] = (object)array(
	    		'personId' => $rec->personId, 
	    		'docId'  => $rec->id, 
	    	    'docClass' => core_Classes::fetchIdByName('trz_Bonuses'),
	    		'indicator' => 'bonuses', 
	    		'value' => $rec->sum
	    	);
    	}

    	return $result;
    }

}