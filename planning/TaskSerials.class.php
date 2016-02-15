<?php


/**
 * Клас 'planning_TaskSerials' - Серийни номера по задачи за производство
 *
 * 
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_TaskSerials extends core_Manager
{
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Серийни номера по задачи за производство';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'debug';
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canWrite = 'no_one';
	
	
	/**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools,plg_Created';
	
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,serial,taskId,labelNo,domain,createdOn,createdBy';

    
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('serial', 'bigint', 'caption=Брояч,mandatory');
		$this->FLD('taskId', 'key(mvc=planning_Tasks,select=title)', 'caption=Задача,mandatory');
		$this->FLD('labelNo', 'int', 'caption=Номер на етикета,mandatory');
		$this->FLD('domain', 'enum(auto,labels)', 'caption=Домейн,mandatory,notNull,value=auto');
		
		$this->setDbUnique('serial');
		$this->setDbUnique('taskId,labelNo,domain');
	}
	
	
	/**
	 * Връща следващия сериен номер
	 * 
	 * @return string $serial
	 */
	public static function getNextSerial()
	{
		// Намираме последния въведен код
		$query = static::getQuery();
		$query->XPR('maxSerial', 'int', 'MAX(#serial)');
		$startCounter = $query->fetch()->maxSerial;
		if(!$startCounter){
			$startCounter = core_packs::getConfigValue('planning', 'PLANNING_TASK_SERIAL_COUNTER');
		};
		$serial = $startCounter;
		
		// Инкрементираме кода, докато достигнем свободен код
		$serial++;
		while(self::fetch("#serial = '{$serial}'")){
			$serial++;
		}
		
		return $serial;
	}
	
	
	/**
	 * Връща следващия сериен номер, автоинкрементиран
	 *
	 * @param int $taskId - ид на задача за прозиводство
	 * @return string $serial - сериен номер
	 */
	public static function forceAutoNumber($taskId)
	{
		$query = self::getQuery();
		$query->where("#domain = 'auto'");
		$query->XPR('maxLabelNo', 'int', 'MAX(#labelNo)');
		$labelNo = $query->fetch()->maxLabelNo;
		$labelNo++;
		
		$rec = (object)array('taskId'  => $taskId, 
							 'labelNo' => $labelNo,
							 'domein'  => 'auto', 
							 'serial'  => self::getNextSerial());
		
		self::save($rec);
		
		return $rec->serial;
	}
	
	
	/**
	 * Форсираме сериен номер
	 * 
	 * @param int $id - ид 
	 * @param number $labelNo - номер на етикета
	 * @return int - намерения сериен номер
	 */
	public static function force($taskId, $labelNo = 0)
	{
		if($rec = static::fetch(array("#taskId = [#1#] AND #labelNo = '[#2#]' AND #domain = 'labels'", $taskId, $labelNo))){
			
			return $rec->serial;
		}
		
		$rec = (object)array('taskId'  => $taskId, 
						     'labelNo' => $labelNo, 
							 'domain'  => 'labels',
							 'serial'  => static::getNextSerial());
		
		static::save($rec);
		
		return $rec->serial;
	}
}