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
	public $listFields = 'id, userId, containerId, time, invalidate';
	
	
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
		$this->FLD("cache", "blob(1000000, serialize, compress)", "input=none,caption=Html,column=none");
		$this->FLD("time", "datetime", "input=none,caption=Дата");
	}
	
	
	/**
	 * Връща валидния кеш на документа за потребителя
	 * 
	 * @param int $containerId - ид на контейнера
	 * @param int $userId      - ид на потребителя
	 * @param datetime $time   - дата
	 * 
	 * @return stdClass $cache - записания кеш
	 */
	public static function getDocumentData($containerId, $userId, $time)
	{
		$interval = self::KEEP_MINUTES * 60;
		
		if(!$rec = self::fetch("#userId = {$userId} AND #containerId = {$containerId} AND ADDDATE(#time, INTERVAL {$interval} SECOND) >= '{$time}'")){
			
			$rec = (object)array('userId' => $userId, 'containerId' => $containerId, 'time' => $time);
			$document = doc_Containers::getDocument($containerId);
			$rec->cache = $document->prepareDocument();
			self::save($rec);
		}
		
		return $rec->cache;
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
		$row->invalidate = dt::addSecs(self::KEEP_MINUTES * 60, $rec->time);
	}
	
	
	/**
	 * Инвалидира стария кеш
	 */
	function cron_Invalidate()
	{
		// Изтриваме стария кеш
		$interval = self::KEEP_MINUTES * 60;
		$now = dt::now();
		$this->delete("ADDDATE(#time, INTERVAL {$interval} SECOND) <= '{$now}'");
		
		// Ресетваме ид-та
		$this->db->query("ALTER TABLE {$this->dbTableName} AUTO_INCREMENT = 1");
	}
}