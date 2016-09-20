<?php



/**
 * Мениджър на заплати
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заплати
 */
class trz_SalaryRules extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Правила';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Правило';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_Rejected,  plg_SaveAndNew, 
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
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,trz';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,trz';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,trz';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,name, function, indicators, factor';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('name',    'varchar(64)', 'caption=Име на правилото,width=100%,mandatory');
    	$this->FLD('function',    'enum(const=константа, average=средно, summary=сумарно)', 'caption=Функция,width=100%,mandatory');
    	$this->FLD('indicators',    'varchar', 'caption=Индикатор,width=100%,mandatory');
    	$this->FLD('factor',    'double(Min=0)', 'caption=Коефициент,width=100%,mandatory');
    }

    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    
        $indicatorNames = trz_SalaryIndicators::getIndicatorNames();
        
        if(is_array($indicatorNames)) {
            $data->form->setOptions('indicators', array('' => '') + $indicatorNames);
        }
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
        $Double = cls::get('type_Double', array('params' => array('decimals' => 2)));

        $row->factor = $Double->toVerbal($rec->factor);

    }
}
