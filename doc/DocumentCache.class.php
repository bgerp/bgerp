<?php

/**
 * Кеш на данните на някой документи
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
	 * Необходими плъгини
	 */
	public $loadList = 'plg_RowTools, doc_Wrapper';
	 
	
	/**
	 * Заглавие на мениджъра
	 */
	public $title = "Кеш на данните на някой документи";
	
	
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
	public $canList = 'ceo, admin';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, admin';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'id, userId, containerId, cache, time, usage,invalidate';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'productId';
	
	
	/**
	 * Файл с шаблон за единичен изглед на статия
	 */
	public $singleLayoutFile = 'cat/tpl/SingleLayoutTplCache.shtml';
	
	
	/**
	 * Колко минути да стои жив кеша
	 */
	const KEEP_MINUTES = 5;
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD("userId", "user", "input=none,caption=Потребител");
		$this->FLD("containerId", "key(mvc=doc_Containers)", "input=none,caption=Документ");
		$this->FLD("cache", "blob(1000000, serialize, compress,maxRows=5)", "input=none,caption=Html,column=none");
		$this->FLD("time", "datetime(format=smartTime)", "input=none,caption=Създаване");
		$this->FLD("usage", "datetime(format=smartTime)", "input=none,caption=Употреба");
		$this->FLD("invalidate", "datetime(format=smartTime)", "input=none,caption=Изтриване");

        $this->setDbUnique('userId,containerId');
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
	public static function getDocumentData($containerId, $userId, $modifiedOn)
	{
        if($containerId == Request::get('Cid')) return FALSE;

		if($rec = self::fetch("#userId = {$userId} AND #containerId = {$containerId} AND  #time >'{$modifiedOn}'")){
			
            // Записваме използването на кеша
            $rec->usage = dt::now();
			self::save($rec, 'usage');
			
 		    return $rec->cache;
		} 
	}



    /**
     * Записва документ в кеша
     */
    public static function setDocumentData($containerId, $userId, $document)
    {
        if($containerId == Request::get('Cid')) return FALSE;

        $interval = self::KEEP_MINUTES * 60;
		$now = dt::now();

        $rec = (object)array(   'userId' => $userId, 
						        'containerId' => $containerId, 
						        'time' => $now,
							    'usage' => $now,
							    'invalidate' => dt::addSecs($interval, $time),
                                'cache' => $document);

        return self::save($rec, NULL, 'REPLACE');
    }
	
	
	/**
	 * Инвалидира всичкия кеш за този потребител за този документ
	 */
	public static function invalidate($containerId, $userId)
	{
		self::delete("#userId = {$userId} AND #containerId = {$containerId}");
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
	function cron_Invalidate()
	{
		$now = dt::now();
		
		// Вземам всички аписи които са над 4+ минути. За всеки един взимам броя на минутите които е над 4
		$query = $this->getQuery();
		$query->XPR('minutes', 'double', "ROUND(time_to_sec(TIMEDIFF('{$now}', #invalidate)) / 60)");
		$query->where("#minutes >= 4");
		$query->show('invalidate,time,minutes,usage,containerId');
		
		while($rec = $query->fetch()){
			
			// Ако документа е бил скоро използван, регенерираме му кеша, и не го изтриваме
			if(dt::addSecs(2 * 60, $rec->usage) > $now){
				
				// $document = doc_Containers::getDocument($rec->containerId);
				// $rec->cache = $document->prepareDocument();
				// $this->save($rec, 'cache');
				// continue;
			}
			
			// Колко минути са над 3
			$mCount = $rec->minutes - 3;
			
			// Ако е над три минути, директно го трием
			if($mCount >= 3) {
				$this->delete($rec->id);
				continue;
			}
			
			// След това трием с вероятност
			$prob = abs(1 / (3 - $mCount));
			if(rand(1, 100) < $prob * 100){
				$this->delete($rec->id);
			}
		}
		
		// вземам всички записи които са над 4+ минути. За всеки един взимам броя на минутите които е над 4
		// mCount. С вероятност 1 / 3 - $mCount изтривам текущия запис
		// rand(1, 100) <  (1 / 3 - $mCount) * 100; Или ако mCount = 3 || 3+ пак трия
		
		// Ресетваме ид-та
		$this->db->query("ALTER TABLE {$this->dbTableName} AUTO_INCREMENT = 1");
	}
}