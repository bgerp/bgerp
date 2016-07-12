<?php


/**
 * Клас 'planning_TaskActions' - Операции със задачи
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
class planning_TaskActions extends core_Manager
{
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Регистър на операциите на задачите';
	
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'debug,ceo';
	
	
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
    public $loadList = 'plg_RowTools';
	
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,type,productId,taskId,jobId,quantity';
    		
    		
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('type', 'enum(input=Вложено,product=Произведено,waste=Отпадък)', 'input=none,mandatory,caption=Действие');
		$this->FLD('productId', 'key(mvc=cat_Products)', 'input=none,mandatory,caption=Артикул');
		$this->FLD('taskId', 'key(mvc=planning_Tasks)', 'input=none,mandatory,caption=Задача');
		$this->FLD('jobId', 'key(mvc=planning_Jobs)', 'input=none,mandatory,caption=Задание');
		$this->FLD('quantity', 'double', 'input=none,mandatory,caption=Количество');
		
		$this->setDbUnique('type,taskId,productId');
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		$row->productId = cat_Products::getHyperlink($rec->productId);
		$row->jobId = planning_Jobs::getLink($rec->jobId, 0);
		$row->taskId = planning_Tasks::getLink($rec->taskId, 0);
	}
	
	
	/**
	 * Записва действие по задача
	 * 
	 * @param int $taskId - ид на задача
	 * @param int $productId - ид на артикул
	 * @param product|input|waste $type - вид на действието
	 * @param int $jobId - ид на задачие
	 * @param int $quantity - количество
	 */
	public static function add($taskId, $productId, $type, $jobId, $quantity)
	{
		if(!$productId) return;
		
		if($rec = self::fetch("#taskId = {$taskId} AND #productId = {$productId} AND #type = '{$type}'")){
			$rec->quantity = $quantity;
			
			return self::save($rec, 'quantity');
		} else {
			$rec = (object)array('taskId'    => $taskId, 
								 'productId' => $productId, 
								 'type'      => $type, 
								 'quantity'  => $quantity, 
								 'jobId'     => $jobId);
			
			return self::save($rec);
		}
	}
	
	
	/**
	 * Връща количеството произведено по задачи по дадено задание
	 * 
	 * @param int $jobId
	 * @param product|input|waste $type
	 * @return double $quantity
	 */
	public static function getQuantityForJob($jobId, $type)
	{
		expect(in_array($type, array('product', 'input', 'waste')));
		expect($jobRec = planning_Jobs::fetch($jobId));
		
		$query = self::getQuery();
		$query->EXT('taskState', 'planning_Tasks', 'externalName=state,externalKey=taskId');
		$query->where("#taskState != 'rejected'");
		$query->where("#type = '{$type}'");
		$query->where("#jobId = {$jobId}");
		$query->where("#productId = {$jobRec->productId}");
		$query->XPR('sumQuantity', 'double', "SUM(#quantity)");
		$query->show('quantity');
		
		$quantity = $query->fetch()->sumQuantity;
		if(!isset($quantity)){
			$quantity = 0;
		}
		
		return $quantity;
	}
}