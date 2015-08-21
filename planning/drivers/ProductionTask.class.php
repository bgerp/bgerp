<?php



/**
 * Драйвер за задачи за производство
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title Задача за производство
 */
class planning_drivers_ProductionTask extends tasks_BaseDriver
{
	
	
	/**
	 * Шаблон за обвивката този драйвер
	 */
	protected $singleLayoutFile = 'planning/tpl/SingleLayoutProductionTask.shtml';
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver = 'planning,ceo';
	
	
	/**
	 * От кои класове може да се избира драйвера
	 */
	public $availableClasses = 'planning_Tasks';
	
	
	/**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
		$fieldset->FLD('totalQuantity', 'double(smartRound)', 'mandatory,caption=Общо к-во');
		$fieldset->FLD('totalWeight', 'cat_type_Weight', 'caption=Общо тегло,input=none');
		$fieldset->FLD('fixedAssets', 'keylist(mvc=cat_Products,select=name,makeLinks=short)', 'caption=Машини');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	protected static function on_AfterPrepareEditForm($Driver, &$data)
	{
		// Оставяме за избор само артикули ДМА-та
		$products = cat_Products::getByProperty('fixedAsset');
		$data->form->setSuggestions('fixedAssets', $products);
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_BaseClass $Driver
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	protected static function on_AfterRecToVerbal($Driver, &$row, $rec)
	{
		if($rec->fixedAssets){
			$assetsArr = explode(',', $row->fixedAssets);
				
			$row->fixedAssets = "<ul style='padding-left:12px;margin:0px;list-style:none'>";
			foreach ($assetsArr as $asset){
				$row->fixedAssets .= "<li style='padding:0px'>{$asset}</li>";
			}
			
			$row->fixedAssets .= "<ul>";
		}
	}
	
	
	/**
	 * Преди рендиране на шаблона
	 */
	protected static function on_AfterRenderSingleLayout($Driver, &$tpl, $data)
	{
		$tpl = getTplFromFile($Driver->singleLayoutFile);
	}
	
	
	/**
     * Обновяване на данните на мастъра
     * 
     * @param stdClass $rec - запис на ембедъра
     * @param void
     */
	public function updateEmbedder(&$rec)
	{
		 // Колко е общото к-во досега
		 $dQuery = tasks_TaskDetails::getQuery();
		 $dQuery->where("#taskId = {$rec->id}");
		 $dQuery->where("#state != 'rejected'");
		 $dQuery->XPR('sumQuantity', 'double', 'SUM(#quantity)');
		 $dQuery->XPR('sumWeight', 'double', 'SUM(#weight)');
		 $dQuery->show('sumQuantity,sumWeight');
		 
		 $res = $dQuery->fetch();
		 $sumQuantity = $res->sumQuantity;
		 
		 // Преизчисляваме общото тегло
		 $rec->totalWeight = $res->sumWeight;
		      
		 // Изчисляваме колко % от зададеното количество е направено
		 $rec->progress = round($sumQuantity / $rec->totalQuantity, 2);
	}
	
	
	/**
     * Възможност за промяна след обръщането на данните във вербален вид
     *
     * @param stdClass $row
     * @param stdClass $rec
     * @return void
     */
	public function recToVerbalDetail(&$row, $rec)
	{
		if($rec->operation){
			$verbal = arr::make('start=Пускане,production=Произвеждане,waste=Отпадък,scrap=Бракуване,stop=Спиране');
			if(isset($verbal[$rec->operation])){
				$row->operation = $verbal[$rec->operation];
			}
		}
	}
	
	
	/**
     * Възможност за промяна след подготовката на формата на детайла
     *
     * @param stdClass $data
     * @return void
     */
	public function prepareEditFormDetail(&$data)
	{
		$form = &$data->form;
		$form->setFieldType('operation', 'enum(start=Пускане,production=Произвеждане,waste=Отпадък,scrap=Бракуване,stop=Спиране)');
		$form->setField('operation', 'input,mandatory');
		
		$form->setField('message', 'input=none');
		
		if(isset($data->masterRec->fixedAssets)){
			$keylist = $data->masterRec->fixedAssets;
			$arr = keylist::toArray($keylist);
			
			foreach ($arr as $key => &$value){
				$value = cat_Products::getTitleById($key, FALSE);
			}
			$form->setOptions('fixedAsset', array('' => '') + $arr);
			$form->setField('fixedAsset', 'input');
		}
		
		// Показваме полето за въвеждане на код само при операция "произвеждане"
		if($form->rec->operation == 'production'){
			$form->setField('code', 'input');
		}
	}
	
	
	/**
     * Възможност за промяна след рендирането на детайла
     * 
     * @param core_ET $tpl
     * @param stdClass $data
     * @return void
     */
    public function renderDetail(&$tpl, $data)
    {
    	// Добавяме бутон за добавяне на прогрес при нужда
    	if(tasks_TaskDetails::haveRightFor('add', (object)array('taskId' => $data->masterId))){
    		$ht = ht::createLink('', array('tasks_TaskDetails', 'add', 'taskId' => $data->masterId, 'ret_url' => TRUE), FALSE, 'ef_icon=img/16/add.png,title=Добавяне на прогрес към задачата');
    		$tpl->append($ht, 'ADD_BTN');
    	} 
    }
    
    
    /**
     * Възможност за промяна след подготовката на лист тулбара
     *
     * @param stdClass $data
     * @return void
     */
    public function prepareListToolbarDetail(&$data)
    {
    	// Премахваме стандартния бутон за добавяне
    	parent::prepareListToolbarDetail($data);
    	$data->toolbar->removeBtn('btnAdd');
    }
}