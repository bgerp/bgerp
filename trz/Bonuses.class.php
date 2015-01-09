<?php



/**
 * Мениджър на бонуси
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Бонуси
 */
class trz_Bonuses extends core_Master
{
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'trz_SalaryIndicatorsSourceIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Премии';
    
     
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Премия";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, plg_SaveAndNew, 
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
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,trz';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,trz';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,trz';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,trz';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, periodId, personId, type, sum';
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "5.5|Човешки ресурси"; 
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('periodId', 'date',     'caption=Дата');
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител');
    	$this->FLD('type', 'varchar',     'caption=Произход на бонуса');
    	$this->FLD('sum', 'double',     'caption=Сума');
    	
    }
    
    public static function act_Test()
    {
    	$date = '2013-07-16';
    }
    
    
    /**
     * Интерфейсен метод на trz_SalaryIndicatorsSourceIntf
     * 
     * @param date $date
     * @return array $result
     */
    public static function getSalaryIndicators($date)
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