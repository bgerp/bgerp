<?php



/**
 * Клас 'trans_plg_LinesPlugin'
 * Плъгин даващ възможност на даден документ лесно да му се избира транспортна линия
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trans_plg_LinesPlugin extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		setIfNot($mvc->totalWeightFieldName, 'weight');
		setIfNot($mvc->totalVolumeFieldName, 'volume');
		setIfNot($mvc->lineFieldName, 'lineId');
		setIfNot($mvc->palletCountFieldName, 'palletCount');
		
		// Създаваме поле за избор на линия, ако няма такова
		if(!$mvc->getField($mvc->lineFieldName, FALSE)){
			$mvc->FLD($mvc->lineFieldName, 'key(mvc=trans_Lines,select=title,allowEmpty)', 'input=none');
		} else {
			$mvc->setField($mvc->lineFieldName, 'input=none');
		}
		
		// Създаваме поле за брой пакети ако няма
		if(!$mvc->getField($mvc->palletCountFieldName, FALSE)){
			$mvc->FLD($mvc->palletCountFieldName, 'int', 'input=none');
		} else {
			$mvc->setField($mvc->palletCountFieldName, 'input=none');
		}
		
		// Създаваме поле за общ обем
		if(!$mvc->getField($mvc->totalVolumeFieldName, FALSE)){
			$mvc->FLD($mvc->totalVolumeFieldName, 'cat_type_Volume', 'input=none');
		} else {
			$mvc->setField($mvc->totalVolumeFieldName, 'input=none');
		}
		
		// Създаваме поле за общо тегло
		if(!$mvc->getField($mvc->totalWeightFieldName, FALSE)){
			$mvc->FLD($mvc->totalWeightFieldName, 'cat_type_Weight', 'input=none');
		} else {
			$mvc->setField($mvc->totalWeightFieldName, 'input=none');
		}
		
		$mvc->FLD('weightInput', 'cat_type_Weight', 'input=none');
		$mvc->FLD('volumeInput', 'cat_type_Volume', 'input=none');
		$mvc->FLD('palletCountInput', 'int', 'input=none');
	}
	
	
	/**
	 * След подготовка на тулбара на единичен изглед
	 */
	public static function on_AfterPrepareSingleToolbar($mvc, &$data)
	{
		$rec = $data->rec;
		
		if($rec->state != 'rejected'){
			$url = array($mvc, 'changeLine', $rec->id, 'ret_url' => TRUE);
			
			if($mvc->haveRightFor('changeLine', $rec)){
				$data->toolbar->addBtn('Транспорт', $url, "ef_icon=img/16/lorry_go.png, title = Промяна на транспортната информация");
			}
		}
	}
	
	
	/**
	 * Извиква се преди изпълняването на екшън
	 *
	 * @param core_Manager $mvc
	 * @param mixed $res
	 * @param string $action
	 */
	public static function on_BeforeAction($mvc, &$res, $action)
	{
		if($action != 'changeline') return;
		
		$mvc->requireRightFor('changeline');
		expect($id = Request::get('id', 'int'));
		expect($rec = $mvc->fetch($id));
		$mvc->requireRightFor('changeline', $rec);
		
        $exLineId = $rec->lineId;

		$form = cls::get('core_Form');
		
		$form->title = core_Detail::getEditTitle($mvc, $id, 'транспорт', $rec->id);
		$form->FLD('lineId', 'key(mvc=trans_Lines,select=title,allowEmpty,where=#state \\= \\\'active\\\')', 'caption=Транспорт' . ($exLineId?'':''));
		$form->FLD('weight', 'cat_type_Weight', 'caption=Тегло');
		$form->FLD('volume', 'cat_type_Volume', 'caption=Обем');
		$form->FLD('palletsCount', 'int', 'caption=Kолети/палети,unit=бр.');
		$form->setDefault('lineId', $rec->{$mvc->lineFieldName});
		$form->setDefault('weight', $rec->weightInput);
		$form->setDefault('volume', $rec->volumeInput);
		$form->setDefault('palletsCount', $rec->palletCountInput);
		
		$form->input(NULL, 'silent');
		$form->input();
		
		if($form->isSubmitted()){
			$formRec = $form->rec;
			
			// Обновяваме в мастъра информацията за общото тегло/обем и избраната линия
			$rec->weightInput = $formRec->weight;
			$rec->volumeInput = $formRec->volume;
			$rec->palletCountInput = $formRec->palletsCount;
			
			$rec->{$mvc->lineFieldName} = $formRec->lineId;
			$mvc->save($rec);
			$mvc->logWrite('Редакция на транспорта', $rec->id);
			
            // Обновяване на modifiedOn на засегнатите транспортните линии
            if($rec->lineId) {
                $tRec = trans_Lines::fetch($rec->lineId);
                $tRec->modifiedOn = dt::now();
                trans_Lines::save($tRec, 'modifiedOn');
            }
            if($exLineId && $exLineId != $rec->lineId) {
                $tRec = trans_Lines::fetch($exLineId);
                $tRec->modifiedOn = dt::now();
                trans_Lines::save($tRec, 'modifiedOn');
            }
			
			// Редирект след успешния запис
			redirect($mvc->getSingleUrlArray($id), FALSE, '|Промените са записани успешно');
		}
		
		$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
    	$form->toolbar->addBtn('Отказ', array($mvc, 'single', $id),  'ef_icon = img/16/close-red.png');
    		 
    	// Рендиране на формата
    	$res = $form->renderHtml();
    	$res = $mvc->renderWrapping($res);
    	
    	// ВАЖНО: спираме изпълнението на евентуални други плъгини
    	return FALSE;
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'changeline' && isset($rec)){
			
			// На оттеглените не могат да се променят линиите
			if($rec->state == 'rejected'){
				$requiredRoles = 'no_one';
			}
		}
	}


	/**
	 * Изчислява обема и теглото на продуктите в документа
	 * @param core_Mvc $mvc
	 * @param stdClass $res
	 * @param array $products - масив от продуктите
	 * 					[productId]    - ид на продукта
	 * 					[packQuantity] - количество на опаковките
	 * 					[weight]       - единичното тегло
	 * 					[volume]       - единичния обем
	 */
	public static function on_AfterGetMeasures($mvc, &$res, $products)
	{
		$obj = new stdClass();
		$obj->volume = 0;
		$obj->weight = 0;
		
		foreach ($products as $p){
				
			// Ако има изчислен обем
			if($obj->volume !== NULL){
				$volume = $p->volume;
				(!$volume) ? $obj->volume = NULL : $obj->volume += $volume;
			}
				
			if($obj->weight !== NULL){
				$weight = $p->weight;
				(!$weight) ? $obj->weight = NULL : $obj->weight += $weight;
			}
		}
	
		$res = $obj;
	}
}