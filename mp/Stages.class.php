<?php


/**
 * Клас 'mp_Stages' - Модел за производствени етапи
 *
 * 
 *
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mp_Stages extends core_Manager
{
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Производствени етапи';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'plg_RowTools, mp_Wrapper, plg_Printing, plg_Sorting';
	
	
	/**
	 * Кой има право да чете?
	 */
	public $canRead = 'ceo,mp';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,mp';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,mp';
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canEdit = 'ceo,mp';
	
	
	/**
	 * Кой има право да добавя?
	 */
	public $canAdd = 'ceo,mp';
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Производствен етап';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('name', 'varchar', 'caption=Заглавие,mandatory');
		$this->FLD('departmentId', 'key(mvc=hr_Departments,select=name,allowEmpty)', 'caption=Център на дейност,mandatory');
		$this->FLD('order', 'int', 'caption=Подредба');
		
		$this->setDbUnique('name,departmentId');
		$this->setDbUnique('order');
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 */
	public static function on_AfterInputEditForm($mvc, &$form)
	{
		if($form->isSubmitted()){
			if(empty($form->rec->order)){
				$form->rec->order = $mvc->getNextOrder();
			}
		}
	}
	
	
	/**
	 * Връща следващия номер
	 */
	private function getNextOrder()
	{
		$query = $this->getQuery();
		$query->XPR('maxOrder', 'int', 'MAX(#order)');
		
		$order = $query->fetch()->maxOrder + 1;
		
		return $order;
	}
	
	
	/**
	 * Подготовка на филтър формата
	 */
	protected static function on_AfterPrepareListFilter($mvc, &$data)
	{
		$data->listFilter->FNC('department', 'key(mvc=mp_Stages,select=name,allowEmpty)', 'caption=Център на дейност,input');
		$data->listFilter->showFields = 'department';
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
		
		$data->listFilter->input();
		
		if($rec = $data->listFilter->rec){
			if($rec->department){
				$data->query->where("#departmentId = {$rec->department}");
			}
		}
		
		// Сортиране на записите по order
		$data->query->orderBy('order');
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
		$row->departmentId = hr_Departments::getHyperlink($rec->departmentId, TRUE);
	}
	
	
	/**
	 * Поставя изискване да се селектират само активните записи
	 */
	public static function on_AfterMakeArray4Select($mvc, &$optArr, $fields = NULL, &$where = NULL)
	{
		if(count($optArr)){
			
			// Към името на записа, добавяме и центъра му на дейност да могат да се различават
			foreach ($optArr as $id => &$title){
				$departmentVerbal = hr_Departments::getTitleById($mvc->fetchField($id, 'departmentId'), FALSE);
				$title .= " ({$departmentVerbal})";
			}
		}
	}
}