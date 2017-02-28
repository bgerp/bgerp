<?php



/**
 * Мениджър на глоби и удръжки
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Глоби
 */
class hr_Deductions extends core_Master
{
    /**
     * Старо име на класа
     */
    public $oldClassName = 'trz_Fines';


    /**
     * Заглавие
     */
    public $title = 'Удръжки';
    
     
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Удръжка";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_Rejected, plg_State2, plg_SaveAndNew, 
                    hr_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,hr';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,hr';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,hr';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,hr';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,hr';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,hr';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,hr';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, date, personId, type, sum';
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "5.5|Човешки ресурси"; 
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'date';
    
    
    /**
     * Единична икона
     */
    public $singleIcon = 'img/16/banknote.png';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('date', 'date',     'caption=Дата,oldFieldName=periodId');
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител');
    	$this->FLD('type', 'varchar',     'caption=Произход на удръжката');
    	$this->FLD('sum', 'double',     'caption=Сума');
    	
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	// Ако имаме права да видим визитката
    	if(crm_Persons::haveRightFor('single', $rec->personId)){
    		$name = crm_Persons::fetchField("#id = '{$rec->personId}'", 'name');
    		$row->personId = ht::createLink($name, array ('crm_Persons', 'single', 'id' => $rec->personId), NULL, 'ef_icon = img/16/vcard.png');
    	}
    }
}