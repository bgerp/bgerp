<?php



/**
 * Мениджър на Имената на показателите
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Имена на показатели
 */
class hr_IndicatorNames extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Имена на показатели';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Име на показател';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'debug,admin';

    
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'hr_Wrapper';
	
	
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование,mandatory');
        $this->FLD('uniqueId', 'int', 'caption=Обект,mandatory');
        $this->FLD('classId', 'class(interface=hr_IndicatorsSourceIntf,select=title)', 'caption=Клас,mandatory');
        
        $this->setDbUnique('uniqueId,classId');
    }

    
    /**
     * Връща id-то на дадения индикатор. Ако липсва - добавя го.
     *
     * @param string $name   - заглавие на индикатора
     * @param mixed $class   - клас на индикатора
     * @param int $uniqueId  - уникален номер
     * @return stdClass $rec - форсирания запис
     */
    public static function force($name, $class, $uniqueId)
    {
    	$class = cls::get($class);
    	
    	$rec = self::fetch("#classId = {$class->getClassId()} AND #uniqueId = {$uniqueId}");
    	$name = self::normalizeName($name);
    	
    	if(!$rec){
    		$rec = (object)array('name' => $name, 'classId' => $class->getClassId(), 'uniqueId' => $uniqueId);
    		self::save($rec);
    	} else{
    		if($rec->name != $name){
    			$rec->name = $name;
    			cls::get(get_called_class())->save_($rec, 'name');
    		}
    	}
    	
    	return $rec;
    }
    
    
    /**
     * Нормализира името на индикатора
     * 
     * @param string $name
     * @return string $name
     */
    public static function normalizeName($name)
    {
    	$name = preg_replace('/\s+/', ' ', $name);
    	$name = str_replace(' ', '_', $name);
    	
    	return $name;
    }
}