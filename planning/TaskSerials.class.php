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
    public $listFields = 'tools=Пулт,serial,taskId,labelNo,createdOn,createdBy';
    		
    
    
    const TASK_SERIAL_COUNTER = 1000;

    
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('serial', 'bigint', 'caption=Брояч,mandatory');
		$this->FLD('taskId', 'key(mvc=planning_Tasks,select=title)', 'caption=Задача,mandatory');
		$this->FLD('labelNo', 'int', 'caption=Номер на етикета,mandatory');
		
		$this->setDbUnique('serial');
		$this->setDbUnique('taskId,labelNo');
	}
	
	
	/**
	 * Връща следващия сериен номер
	 * @return string
	 */
	public static function getNextSerial()
	{
		// Намираме последния въведен код
		$query = static::getQuery();
		$query->XPR('maxSerial', 'int', 'MAX(#serial)');
		$code = $query->fetch()->maxCode;
		if(!$code){
			$code = self::TASK_SERIAL_COUNTER;
		};
		
		// Инкрементираме кода, докато достигнем свободен код
		$code++;
		while(self::fetch("#serial = '{$code}'")){
			$code++;
		}
		
		return $code;
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
		if($rec = static::fetch(array("#taskId = [#1#] AND #labelNo = '[#2#]'", $taskId, $labelNo))){
			
			return $rec->serial;
		}
		
		$rec = (object)array('taskId'  => $taskId, 
						     'labelNo' => $labelNo, 
							 'serial'  => static::getNextSerial());
		
		static::save($rec);
		
		return $rec->serial;
	}
}