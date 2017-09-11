<?php

/**
 * Кеш на данните на документи
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_DocumentCache extends core_Master
{
    
    
    /**
     * Масив с containerId, които да се инвалидират
     */
    static $invalidateCIdArr = array();
    
	
	/**
	 * Необходими плъгини
	 */
	public $loadList = 'plg_RowTools, doc_Wrapper';
	 
	
	/**
	 * Заглавие на мениджъра
	 */
	public $title = "Кеш на данните на документи";
	
	
	/**
	 * Права за писане
	 */
	public $canWrite = 'no_one';
	
	
	/**
	 * Права за запис
	 */
	public $canRead = 'ceo, admin';
	
	
	/**
	 * Права за запис
	 */
	public $canDelete = 'ceo, admin';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, admin, debug';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, admin';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'key, containerId, userId, cache, createdOn';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'key';
	
	
	/**
	 * Файл с шаблон за единичен изглед
	 */
	public $singleLayoutFile = 'cat/tpl/SingleLayoutTplCache.shtml';

    
    /**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD("key", "varchar(32)", "input=none,caption=Ключ");
		$this->FLD("userId", "user", "input=none,caption=Потребител");
		$this->FLD("containerId", "key(mvc=doc_Containers)", "input=none,caption=Документ");
		$this->FLD("cache", "blob(10000000, serialize, compress,maxRows=5)", "input=none,caption=Html,column=none");
		$this->FLD("createdOn", "datetime(format=smartTime)", "input=none,caption=Създаване, oldFieldName=time");

        $this->setDbUnique('key');
	}
	
	
	/**
	 * Връща валидния кеш на документа за потребителя
	 * 
	 * @param int $containerId - ид на контейнера
	 * @param int $userId      - ид на потребителя
	 * @param datetime $modifiedOn   - време на последно модифициране на документа
	 * 
	 * @return stdClass $cache - записания кеш
	 */
	public static function getCache($cRec, $document)
	{
        if($cRec->id == Request::get('Cid')) return FALSE;
        
        $key = $document->generateCacheKey($cRec);

		if($key && $rec = self::fetch("#key = '{$key}'")){
            if(dt::addSecs(doc_Setup::get('CACHE_LIFETIME'), $rec->createdOn) < dt::now() ) {
                $me = cls::get('doc_DocumentCache');
                $me->invalidate();

                return FALSE;
            }
            
 		    return $rec->cache;
		} 
	}
    
	
    /**
     * Записва документ в кеша
     */
    public static function setCache($cRec, $document, $tpl)
    {
        if($key = $document->generateCacheKey($cRec)) {

            $rec = (object)array(   'key' => $key,
                                    'userId' => core_Users::getCurrent(), 
                                    'containerId' => $cRec->id,
                                    'createdOn' => dt::now(),
                                    'cache' => $tpl);
            
            return self::save($rec, NULL, 'REPLACE');
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
		$row->containerId = doc_Containers::getDocument($rec->containerId)->getLink(0);
	}
	
	
	/**
	 * Инвалидира стария кеш
	 */
	function invalidate()
	{
		$now = dt::now();
		
		// Вземам всички записи които са над 4+ минути. За всеки един взимам броя на минутите които е над 4
		$query = $this->getQuery();
        
        // Изтриваме с по-голяма вероятност, записите, които са стоели по-дълго след края на кеша
		$cnt = $query->delete("TIME_TO_SEC(TIMEDIFF('{$now}', #createdOn)) >= (" . doc_Setup::get('CACHE_LIFETIME') . " - (RAND() * 120))");

		self::logDebug("Изтрити кеширани документа: " . $cnt);
		
		// Ресетваме ид-та веднъж на 1000 минути
        if(round((time()/60) % 1000) == 500) {
		    $this->db->query("ALTER TABLE {$this->dbTableName} AUTO_INCREMENT = 1");
		    self::logInfo("Ресетнати id-та");
        }
	}
	
	
	/**
	 * Инвалидира кеша на документите в нишката
	 * 
	 * @param int $threadId - ид на нишка
	 * @return int $res - броя на изтритите записи
	 */
	public static function threadCacheInvalidation($threadId)
	{
		$res = 0;
		
		// Ако не е включено кеширането на документите в нишката не правим нищо
		if(!(doc_Setup::get('CACHE_LIFETIME') > 0)) return $res;
		expect($threadId);
		
		// Намираме контейнерите в нишката
		$query = doc_Containers::getQuery();
		$query->where("#threadId = {$threadId}");
		$query->show('id');
		
		// За всеки инвалидираме му кеша
		while($cRec = $query->fetch()){
			$res += self::delete("#containerId = '{$cRec->id}'");
		}
		
		return $res;
	}
	
	
	/**
	 * Инвалидира кеша на посочения документ
	 * 
	 * @param int $containerId - ид на контейнер на документ
	 * @return int - броя на изтритите записи
	 */
	public static function cacheInvalidation($containerId)
	{
		expect($containerId);
		
		return self::delete("#containerId = '{$containerId}'");
	}
	
	
	/**
	 * Инвалидира кешовете на документите с посочен originId
	 * 
	 * @param int $originId - ид на контейнера, на източника на документа
	 * @return int $deleted - броя изтрити записи
	 */
	public static function invalidateByOriginId($originId)
	{
		$deleted = 0;
		
		// Ако не кешираме, няма какво да инвалидираме
		if(!(doc_Setup::get('CACHE_LIFETIME') > 0)) return $deleted;
		
		// Намираме контейнерите, които са с този ориджин
		$query = doc_Containers::getQuery();
		$query->where("#originId = {$originId}");
		$query->show('id');
		while($rec = $query->fetch()){
			
			// Инвалидираме им кеша
			$delCount = static::cacheInvalidation($rec->id);
			$deleted += $delCount;
		}
		
		return $deleted;
	}
	
	/**
	 * Добавя containerId, за инвалидиране в on_Shutdown
	 * 
	 * @param integer $cId
	 */
	public static function addToInvalidateCId($cId)
	{
	    if ($cId) {
	        
	        cls::get(get_called_class());
	        
	        self::$invalidateCIdArr[$cId] = $cId;
	    }
	}
	
	
	/**
	 * 
	 * 
	 * @param doc_DocumentCache $mvc
	 */
    public static function on_Shutdown($mvc)
    {
        if (empty(self::$invalidateCIdArr)) return ;
        
        foreach (self::$invalidateCIdArr as $cId) {
            
            if (!$cId) continue;
            
            self::cacheInvalidation($cId);
        }
    }
}
