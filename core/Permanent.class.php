<?php



/**
 * Максимален размер за полето на манипулатора
 */
defIfNot('CORE_PERMANENT_HANDLER_SIZE', 32);


/**
 * Сол за префика на ключовете
 */
defIfNot('CORE_PERMANENT_PREFIX_SALT', md5(EF_SALT . '_CORE_PERM'));


/**
 * Клас 'core_Permanent' - Кеширане на обекти за по-дълго време, променливи или масиви за определено време
 *
 *
 * @category  bgerp
 * @package   core
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Permanent extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Постоянни данни';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Постоянен обект";
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'admin';
	
	
	/**
	 * Кой може да добавя
	 */
	public $canAdd = 'no_one';
	
	
	/**
	 * Кой може да редактира
	 */
	public $canEdit = 'no_one';
	
	
	/**
	 * Кой може да изтрива
	 */
	public $canDelete = 'no_one';
	

    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    public $fetchFieldsBeforeDelete = 'id,key';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,plg_SystemWrapper';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('key', 'identifier(' . (CORE_PERMANENT_HANDLER_SIZE + 3) . ')', 'caption=Ключ,notNull');
        $this->FLD('data', 'blob(16777215,serialize,compress)', 'caption=Данни');
        $this->FLD('lifetime', 'int', 'caption=Живот,notNull');
        
        $this->setDbUnique('key');
    }
    
    
    /**
     * Записва обект в постоянния кеш
     */
    public static function set($key, $data, $lifetime = 1)
    {
    	// Ключа
    	$key = self::getKey($key);
    	expect(is_numeric($lifetime));
    	expect(!is_null($data));
    	
    	// Колко е живота на кеша
    	$lifetime = time() + ($lifetime * 60);
    	
    	// Подготовка на записа
    	$rec = (object)array('key' => $key, 'data' => (object)array('value' => $data), 'lifetime' => $lifetime);
    	
    	// Запис, ако има стар го замества
    	$me = cls::get(get_called_class());
    	$id = $me->save($rec, NULL, 'REPLACE');
    	Debug::log("PERMANENT_CACHE::set {$key}");
    	
    	return $id;
    }
    
    
    /**
     * Извлича запис от кеша
     * 
     * @param string $key            - ключ на кеша
     * @param datetime $minCreatedOn - от коя дата насетне да се търси
     * @return mixed                 - кешираната стойност или NULL, ако не е намерена
     */
    public static function get($key, $minCreatedOn = NULL)
    {
    	$key = self::getKey($key);
    	
    	// Подготовка на условието
    	$where = "#key = '[#1#]'";
    	if(isset($minCreatedOn)){
    		$where .= " AND #createdOn >= '{$minCreatedOn}'";
    	}
    	
    	// Опит за извличане на данни
    	$rec = self::fetch(array($where, $key), 'data,lifetime', FALSE);
    	
    	if(empty($rec) || !is_object($rec->data)){
    		Debug::log("PERMANENT_CACHE::get {$key} - no exists");
    		
    		return NULL;
    	}
    	 
    	// Ако живота е изтекъл се изтрива записа, вместо да се връща-
    	if($rec->lifetime < time()){
    		self::delete($rec->id);
    		Debug::log("PERMANENT_CACHE::delete {$key} - expired");
    		
    		return NULL;
    	}
    	
    	// Връщане на кешираните данни
    	$value = $rec->data->value;
    	Debug::log("PERMANENT_CACHE::get {$key} - success");
    	
    	return $value;
    }
    
    
    /**
     * Изтриване на постоянния кеш
     * 
     * @param string $key
     * @return int
     */
    public static function remove($key)
    {
    	$key = self::getKey($key);
    	self::delete(array("#key = '[#1#]'", $key));
    }
    
    
    /**
     * Връща ключ за запазване
     * 
     * @param varchar $key - оригиналния ключ
     * @return string $newKey - ключа за запис
     */
    private static function getKey($key)
    {
    	$handler = str::convertToFixedKey($key, CORE_PERMANENT_HANDLER_SIZE - 4, 12);
    	$prefix = md5(EF_DB_NAME . '|' . CORE_PERMANENT_PREFIX_SALT);
    	$prefix = substr($prefix, 0, 6);
    	
    	$newKey = "{$prefix}|{$handler}";
    	
    	return $newKey;
    }
    
    
    /**
     * Изтриване на изтеклите записи, по разписание
     */
    function cron_DeleteExpiredPermData()
    {
    	$query = $this->getQuery();
    	$query->where("#lifetime < " . time());
    	
    	$deletedRecs = 0;
    	while($rec = $query->fetch()) {
    		$deletedRecs += $this->delete($rec->id);
    	}
    	
    	$msg = "Лог: <b style='color:blue;'>{$deletedRecs}</b> постоянни записа с изтекъл срок бяха изтрити";
    }
}