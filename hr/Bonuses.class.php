<?php



/**
 * Мениджър на бонуси
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Бонуси
 */
class hr_Bonuses extends core_Master
{

    /**
     * Старото име на класа
     */
    public $oldClassName = 'trz_Bonuses';

    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'hr_IndicatorsSourceIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Премии';
    
     
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Премия";
    

    /**
     * Абривиатура на документа
     */
    public $abbr = 'Bns';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_State, plg_SaveAndNew, doc_plg_TransferDoc, bgerp_plg_Blank,
    				 doc_DocumentPlg, doc_ActivatePlg,
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
    public $rowToolsSingleField = 'type';
    
    
    /**
     * Единична икона
     */
    public $singleIcon = 'img/16/bonuses.png';


    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'trz/tpl/SingleLayoutBonuses.shtml';

    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('date', 'date',     'caption=Дата,oldFieldName=periodId');
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител');
    	$this->FLD('type', 'varchar',     'caption=Произход на бонуса');
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
    
    
    public static function act_Test()
    {
    	$date = '2016-03-01';
    	self::getSalaryIndicators($date);
    }
    
    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     * 
     * @param date $date
     * @return array $result
     */
    public static function getIndicatorValues($timeline)
    {
    	$query = self::getQuery();
        $query->where("#modifiedOn  >= '{$timeline}' AND #state != 'draft' AND #state != 'template' AND #state != 'pending'");
 
    	while($rec = $query->fetch()){
 
    		$result[] = (object)array(
    		    'date' => $rec->date,
	    		'personId' => $rec->personId, 
	    		'docId'  => $rec->id, 
	    	    'docClass' => core_Classes::getId('hr_Bonuses'),
	    		'indicatorId' => 1, 
	    		'value' => $rec->sum,
                'isRejected' => $rec->state == 'rejected',
	    	);
    	}

    	return $result;
    }
    
    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     * 
     * @param date $date
     * @return array $result
     */
    public static function getIndicatorNames()
    {
        return array(1 => 'Бонус');
    }


    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$row = new stdClass();
    	$row->title = $this->getRecTitle($rec);
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->state = $rec->state;
    	$row->recTitle = $this->getRecTitle($rec);
    	
    	return $row;
    }

}