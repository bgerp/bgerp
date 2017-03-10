<?php



/**
 * Мениджър за "Продуктови Категории" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_FavouritesCategories extends core_Manager {
    
    /**
     * Заглавие
     */
    var $title = "Продуктови категории";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools2, plg_Printing,
    				 pos_Wrapper';

    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'name, points, createdOn, createdBy';
    
	
	/**
     * Кой може да го прочете?
     */
    var $canRead = 'ceo, pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canAdd = 'ceo, pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'pos, ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,pos';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,pos';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'ceo, pos';
    
    
    /**
     * Заглавие на единичния обект
     */
    public $singleTitle = 'продуктова категория';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
    	$this->FLD('points', 'keylist(mvc=pos_Points, select=name, makeLinks)', 'caption=Точки на продажба');
    	
        $this->setDbUnique('name');
    }
    
    
    /**
     * Връща всички продуктови категории
     * @return array $categories - Масив от всички категории
     */
    public static function prepareAll($pointId)
    {
    	$categories = array();
    	$varchar = cls::get('type_Varchar');
    	$query = static::getQuery();
    	$query->where("#points IS NULL OR LOCATE('|{$pointId}|', #points)");
    	while($rec = $query->fetch()) {
    		$rec->name = $varchar->toVerbal($rec->name);
    		$categories[$rec->id] = (object)array('id' => $rec->id, 'name' => $rec->name);
    	}
    	
    	return $categories;
    }
    
    
    /**
     * След началното установяване на този мениджър
     */
    public static function loadSetupData()
    {
    	pos_FavouritesCategories::truncate();
    	if(!self::fetch("#name = 'Най-продавани'")){
    		self::save((object)array('name' => 'Най-продавани'));
    	}
    	
    	$pQuery = pos_Points::getQuery();
    	while($pRec = $pQuery->fetch()){
    		$name = "Налични({$pRec->name})";
    		if(!self::fetch("#name = '{$name}'")){
    			self::save((object)array('name' => $name, 'points' => keylist::addKey('', $pRec->id)));
    		}
    	}
    }
}