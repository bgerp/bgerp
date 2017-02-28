<?php



/**
 * Заглавия на свойствата
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_FeatureTitles extends core_Manager
{
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Заглавия на свойства";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'acc_WrapperSettings, plg_State2, plg_Search,
                     plg_Created, plg_Sorting';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Счетоводство:Настройки';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,acc';
        
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Заглавие на свойство';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'acc, ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да го редактира?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Работен кеш
     */
    private static $cache = array();
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 40;
    

    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Черта');
    }

    
    /**
     * Връща id на посочения признак. Ако го няма - създава го.
     */
    public static function fetchIdByTitle($title)
    {
        $id = acc_FeatureTitles::fetchField(array("#title = '[#1#]'", $title), 'id');
        if(!isset($id)) {
            $ftRec = (object) array('title' => $title);
            acc_FeatureTitles::save($ftRec);
            $id = $ftRec->id;
        }

        return $id;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на ключа
     */
    public static function getTitleById($id, $escaped = TRUE)
    {
    	if(!count(self::$cache)){
    		$query = self::getQuery();
    		$query->show('id,title');
    		self::$cache = $query->fetchAll();
    	}
    	
    	return self::$cache[$id]->title;
    }

}