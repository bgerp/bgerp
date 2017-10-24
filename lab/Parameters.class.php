<?php



/**
 * Мениджър за параметрите в лабораторията
 *
 *
 * @category  bgerp
 * @package   lab
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class lab_Parameters extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = "Параметри за лабораторни тестове";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_State2,
                             plg_RowTools2, plg_Printing, lab_Wrapper,
                             plg_Sorting, fileman_Files';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,name,state';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'lab,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'lab,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'lab,ceo';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'lab,ceo';
    
    
    /**
     * Полетата, които ще се показват в единичния изглед
     */
    public $singleFields = 'id,name,type,dimension,
                             precision,description,state';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Параметър";
    
         
    /**
     * Икона по подразбиране за единичния обект
     */
    public $singleIcon = 'img/16/pipette.png';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Параметър');
        $this->FLD('type', 'enum(number=Числов,bool=Да/Не,text=Текстов)', 'caption=Тип');
        $this->FLD('dimension', 'varchar(16)', 'caption=Размерност,notSorting,oldFieldName=dimention');
        $this->FLD('precision', 'int', 'caption=Прецизност,notSorting');
        $this->FLD('description', 'richtext(bucket=Notes)', 'caption=Описание,notSorting');
        
        $this->setDbUnique('name,dimension');
    }
    
    
    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Сортиране на записите по name
        $data->query->orderBy('name=ASC');
    }
    
    
    /**
     * Линкове към single
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->name = Ht::createLink($row->name, array($mvc, 'single', $rec->id));
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        // Пътя до файла с данните
        $file = "lab/csv/lab_Parameters.csv";
        
        // Импортираме данните от CSV файла. 
        // Ако той не е променян - няма да се импортират повторно
        $cntObj = csv_Lib::importOnce($mvc, $file, array('name', 'type', 'dimension', 'precision', 'description'));
            
        // Записваме в лога вербалното представяне на резултата от импортирането
        $res .= $cntObj->html;
    }

}