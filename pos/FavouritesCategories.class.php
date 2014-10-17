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
    var $loadList = 'plg_Created, plg_RowTools, plg_Printing,
    				 pos_Wrapper';

    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, name, createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
	
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
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Връща всички продуктови категории
     * @return array $categories - Масив от всички категории
     */
    public static function prepareAll()
    {
    	$categories = array();
    	$varchar = cls::get('type_Varchar');
    	$query = static::getQuery();
    	while($rec = $query->fetch()) {
    		$rec->name = $varchar->toVerbal($rec->name);
    		$categories[$rec->id] = (object)array('id' => $rec->id, 'name' => $rec->name);
    	}
    	
    	return $categories;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	if(!$mvc->count()){
    		$mvc->save((object)array('name' => 'Най-продавани'));
    	} 
    }
}