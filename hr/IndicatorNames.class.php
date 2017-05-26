<?php



/**
 * Мениджър на Имената на индикаторите
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Имена на индикатори
 */
class hr_IndicatorNames extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Имена на индикатори';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Индикатор';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created';
    
    
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
	public $canList = 'admin,debug';

    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование,mandatory');
        $this->FLD('uniqueId', 'int', 'caption=Обект,mandatory');
        $this->FLD('classId', 'class', 'caption=Клас,mandatory');
        
        $this->setDbUnique('uniqueId,classId');
    }

    
    /**
     * Връща id-то на дадения индикатор. Ако липсва - добавя го.
     *
     * @param string $name - заглавие на индикатора
     * @param mixed $class - клас на индикатора
     * @param int $uniqueId - уникален номер
     */
    public static function force($name, $class, $uniqueId)
    {
    	$class = cls::get($class);
    	
    	$rec = self::fetch("#classId = {$class->getClassId()} AND #uniqueId = {$uniqueId}");
    	$name = self::normalizeName($name);
    	
    	if(!$rec){
    		$id = self::save((object)array('name' => $name, 'classId' => $class->getClassId(), 'uniqueId' => $uniqueId));
    	} else{
    		if($rec->name != $name){
    			$rec->name = $name;
    			self::save($rec, 'name');
    		}
    		
    		$id = $rec->id;
    	}
    	
    	return $id;
    }
    
    
    public static function normalizeName($name)
    {
    	$name = preg_replace('/\s+/', ' ', $name);
    	$name = str_replace(' ', '_', $name);
    	
    	return $name;
    }
}

