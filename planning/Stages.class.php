<?php


/**
 * Клас 'planning_Stages' - Модел за производствени етапи
 *
 * 
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_Stages extends core_Manager
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'mp_Stages';
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Производствени етапи';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'plg_RowTools, planning_Wrapper, plg_Printing, plg_Sorting, bgerp_plg_Blank';
	
	
	/**
	 * Кой има право да чете?
	 */
	public $canRead = 'ceo,planning';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,planning';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,planning';
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canEdit = 'ceo,planning';
	
	
	/**
	 * Кой има право да добавя?
	 */
	public $canAdd = 'ceo,planning';
	
	
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
		$this->FLD('order', 'int', 'caption=Подредба');
		$this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
		
		$this->setDbUnique('name');
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
		
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
	 *
	 * @param core_Mvc $mvc
	 * @param string $res
	 * @param string $action
	 * @param stdClass $rec
	 * @param int $userId
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
		if(($action == 'delete') && isset($rec)){
			if(isset($rec->lastUsedOn)){
				$res = 'no_one';
			}
		}
	}
	
	
	/**
	 * Форсиране на етап, ако няма създава нов, иначе връща съществуващия
	 * 
	 * @param string $name - името на етапа
	 * @return int $id - ид-то на етапа
	 */
	public static function force($name)
	{
		$id = static::fetchField(array("#name = '[#1#]'", $name), 'id');
		if($id) return $id;
		
		return static::save((object)array('name' => $name));
	}
}