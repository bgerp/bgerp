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
    	$form->toolbar->addBtn('Отказ', $mvc->getSingleUrlArray($id),  'ef_icon = img/16/close-red.png');
    		 
    	// Рендиране на формата
    	$res = $form->renderHtml();
    	$res = $mvc->renderWrapping($res);
    	core_Form::preventDoubleSubmission($res, $form);
    	
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
	 * След преобразуване на записа в четим за хора вид
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$measures = $mvc->getTotalTransportInfo($rec->id);
		
		core_Lg::push($rec->tplLang);
		
		setIfNot($rec->{$mvc->totalWeightFieldName}, $measures->weight);
		$rec->calcedWeight = $rec->{$mvc->totalWeightFieldName};
		$rec->{$mvc->totalWeightFieldName} = ($rec->weightInput) ? $rec->weightInput : $rec->{$mvc->totalWeightFieldName};
		$hintWeight = ($rec->weightInput) ? 'Транспортното тегло е въведено от потребител' : 'Транспортното тегло е сумарно от редовете';
		if(!isset($rec->{$mvc->totalWeightFieldName})) {
			$row->{$mvc->totalWeightFieldName} = "<span class='quiet'>N/A</span>";
		} else {
			$row->{$mvc->totalWeightFieldName} = $mvc->getFieldType($mvc->totalWeightFieldName)->toVerbal($rec->{$mvc->totalWeightFieldName});
			$row->{$mvc->totalWeightFieldName} = ht::createHint($row->{$mvc->totalWeightFieldName}, $hintWeight);
		}
			
		setIfNot($rec->{$mvc->totalVolumeFieldName}, $measures->volume);
		$rec->calcedVolume = $rec->{$mvc->totalVolumeFieldName};
		$rec->{$mvc->totalVolumeFieldName} = ($rec->volumeInput) ? $rec->volumeInput : $rec->{$mvc->totalVolumeFieldName};
		$hintVolume = ($rec->volumeInput) ? 'Транспортният обем е въведен от потребител' : 'Транспортният обем е сумарен от редовете';
		if(!isset($rec->{$mvc->totalVolumeFieldName})) {
			$row->{$mvc->totalVolumeFieldName} = "<span class='quiet'>N/A</span>";
		} else {
			$row->{$mvc->totalVolumeFieldName} = $mvc->getFieldType($mvc->totalVolumeFieldName)->toVerbal($rec->{$mvc->totalVolumeFieldName});
			$row->{$mvc->totalVolumeFieldName} = ht::createHint($row->{$mvc->totalVolumeFieldName}, $hintVolume);
		}
		
		core_Lg::pop();
	}
	
	
	/**
	 * Изчисляване на общото тегло и обем на документа
	 * 
	 * @param core_Mvc $mvc
	 * @param stdClass $res
	 * 			- weight - теглото на реда
	 * 			- volume - теглото на реда
	 * @param int $id
	 * @param boolean $force
	 */
	public static function on_AfterGetTotalTransportInfo($mvc, &$res, $id, $force = FALSE)
	{
		if(!$res){
			$Detail = cls::get($mvc->mainDetail);
			$res = cls::get($mvc->mainDetail)->getTransportInfo($id, $force);
		}
	}
	
	
	/**
	 * Функция, която се извиква след активирането на документа
	 */
	public static function on_AfterActivation($mvc, &$rec)
	{
		// Форсиране на мерките на редовете
		$measures = $mvc->getTotalTransportInfo($rec->id, TRUE);
		
		// Ако няма обем или тегло се обновяват ако може
		if(empty($rec->{$mvc->totalVolumeFieldName}) || empty($rec->{$mvc->totalWeightFieldName})){
			$rec->{$mvc->totalWeightFieldName} = $measures->weight;
			$rec->{$mvc->totalVolumeFieldName} = $measures->volume;
			$mvc->save_($rec, "{$mvc->totalWeightFieldName},{$mvc->totalVolumeFieldName}");
		}
	}
}