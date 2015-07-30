<?php



/**
 * Базов драйвер за производствени задачи
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_drivers_ProductionTask extends planning_drivers_BaseTask
{
	
	/**
	 * Добавяне на полета към формата на детайла
     * 
     * @param core_Form $form
	 */
	public function addEmbeddedFields(core_Form &$form)
	{
		$form->FLD('totalQuantity', 'double', 'mandatory,caption=Общо к-во');
		$form->FLD('totalWeight', 'double', 'caption=Общо тегло,input=none');
		$form->FLD('fixedAssets', 'keylist(mvc=cat_Products,select=name)', 'caption=Машини');
		
		// Оставяме за избор само артикули ДМА-та
		$products = cat_Products::getByProperty('fixedAsset');
		$form->setSuggestions('fixedAssets', $products);
	}
	
	
	/**
	 * Добавяне на полета към формата на детайла
	 *
	 * @param core_FieldSet $form
	 */
	public function addDetailFields_(core_FieldSet &$form)
	{
		if($this->innerForm->fixedAssets){
			$keylist = $this->innerForm->fixedAssets;
			if(isset($form->rec->data->fixedAsset)){
				$keylist = keylist::merge($keylist, $form->rec->data->fixedAsset);
			}
			$arr = keylist::toArray($keylist);
			
			foreach ($arr as $key => &$value){
				$value = cat_Products::getTitleById($key, FALSE);
			}
			$form->setOptions('fixedAsset', array('' => '') + $arr);
			$form->setField('fixedAsset', 'input');
		}
		$form->setField('message', 'input=none');
	}
	
	
	/**
	 * Рендира вградения обект
	 * 
	 * @param stdClass $data
	 */
	public function renderEmbeddedData($data)
	{
		$tpl = new core_ET(tr("|Общо к-во|*: <b>[#totalQuantity#]</b><br>
							   <!--ET_BEGIN totalWeight-->|Общо тегло|*: [#totalWeight#]<br><!--ET_END totalWeight-->
							   <!--ET_BEGIN fixedAssets-->|Машини|*: [#fixedAssets#]<!--ET_END fixedAssets-->"));
		$tpl->placeObject($data->row);
		
		return $tpl;
	}
	
	
	/**
	 * Подготвя данните необходими за показването на вградения обект
	 */
	public function prepareEmbeddedData()
	{
		$data = new stdClass();
		$data->row = new stdClass();
		
		$Double = cls::get('type_Double', array('params' => array('smartRound' => 'smartRound')));
		$Weight = cls::get('cat_type_Weight');
		
		$data->row->totalQuantity = $Double->toVerbal($this->innerForm->totalQuantity);
		$data->row->totalWeight = $Weight->toVerbal($this->innerForm->totalWeight);
		
		if($this->innerForm->fixedAssets){
			$Keylist = cls::get('type_Keylist', array('params' => array('mvc' => 'cat_Products', 'select' => 'name', 'makeLinks' => 'short')));
			$assetsArr = explode(',', $Keylist->toVerbal($this->innerForm->fixedAssets));
			
			$data->row->fixedAssets = "<ul style='padding-left:12px;margin:0px;list-style:none'>";
			foreach ($assetsArr as $asset){
				$data->row->fixedAssets .= "<li style='padding:0px'>{$asset}</li>";
			}
			$data->row->fixedAssets .= "<ul>";
		}
		
		$assetsRow .= "<ul>";
		$data->row->fixedAssets = str_replace(',', '<br>', $data->row->fixedAssets);
		
		return $data;
	}
	
	
	/**
	 * Ъпдейт на данните на мастъра
	 */
	public function updateEmbedder()
	{
		 $rec = $this->EmbedderRec->fetch();
		
		 $totalQuantity = $this->innerForm->totalQuantity;
		 
		 // Колко е общото к-во досега
		 $dQuery = planning_TaskDetails::getQuery();
		 $dQuery->where("#taskId = {$rec->id}");
		 $dQuery->where("#state != 'rejected'");
		 $dQuery->XPR('sumQuantity', 'double', 'SUM(#quantity)');
		 $dQuery->XPR('sumWeight', 'double', 'SUM(#weight)');
		 $res = $dQuery->fetch();
		 $sumQuantity = $res->sumQuantity;
		 $rec->innerForm->totalWeight = $res->sumWeight;
		      
		 // Изчисляваме колко % от зададеното е направено
		 $rec->progress = round($sumQuantity / $totalQuantity, 2);
		 if($rec->progress > 1){
		 	$rec->progress = 1;
		 }
		 
		 // Обновяваме мастъра
		 planning_Tasks::save($rec);
	}
}