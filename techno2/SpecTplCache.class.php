<?php

/**
 * Кеш на изгледа на спецификациите по дата
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno2_SpecTplCache extends core_Master
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'techno_SpecTplCache';
	
	
	/**
	 * Необходими плъгини
	 */
	public $loadList = 'plg_RowTools, techno2_Wrapper';
	
	
	/**
	 * Заглавие
	 */
	public $singleTitle = 'История на изгледа на спецификацията';
	 
	
	/**
	 * Заглавие на мениджъра
	 */
	public $title = "Нестандартни артикули";
	
	
	/**
	 * Права за писане
	 */
	public $canWrite = 'no_one';
	
	
	/**
	 * Права за запис
	 */
	public $canRead = 'ceo, techno';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, techno';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, techno';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'id, specId, time';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'specId';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD("specId", "key(mvc=techno2_SpecificationDoc,select=title)", "input=none,caption=Спецификация");
		$this->FLD("cache", "blob(1000000, serialize, compress)", "input=none,caption=Html,column=none");
		$this->FLD("time", "datetime", "input=none,caption=Дата");
	}
	
	
	/**
	 * Връща кеширания изглед на спецификацията за зададената дата
	 * 
	 * @param mixed $id - ид/запис на спецификация
	 * @param datetime $time - време
	 * @return core_ET - шаблона
	 */
	public static function getTpl($id, $time)
	{
		$rec = techno2_SpecificationDoc::fetchRec($id);
		$cache = techno2_SpecTplCache::fetchField("#specId = {$rec->id} AND #time = '{$time}'", 'cache');
		
		return $cache;
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		if(isset($fields['-single'])){
			$row->cache = new ET($rec->cache);
		}
	}
}