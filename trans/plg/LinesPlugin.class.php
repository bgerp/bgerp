<?php



/**
 * Клас 'trans_plg_LinesPlugin'
 * Плъгин даващ възможност на даден документ лесно да му се избира транспортна линия
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
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
		$mvc->declareInterface('store_iface_DocumentIntf');
		setIfNot($mvc->totalWeightFieldName, 'weight');
		setIfNot($mvc->totalVolumeFieldName, 'volume');
		setIfNot($mvc->lineFieldName, 'lineId');
		setIfNot($mvc->lineNoteFieldName, 'lineNotes');
		
		// Създаваме поле за избор на линия, ако няма такова
		if(!$mvc->getField($mvc->lineFieldName, FALSE)){
			$mvc->FLD($mvc->lineFieldName, 'key(mvc=trans_Lines,select=title,allowEmpty)', 'input=none');
		} else {
			$mvc->setField($mvc->lineFieldName, 'input=none');
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
		
		$mvc->FLD('lineNotes', 'text(rows=2)', 'input=none,caption=Забележки');
		$mvc->FLD('weightInput', 'cat_type_Weight', 'input=none');
		$mvc->FLD('volumeInput', 'cat_type_Volume', 'input=none');
		$mvc->FLD('transUnits', 'blob(serialize, compress)', 'input=none');
		$mvc->FLD('transUnitsInput', 'blob(serialize, compress)', 'input=none');
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
				$data->toolbar->addBtn('Транспорт', $url, "ef_icon=img/16/door_in.png, title = Промяна на транспортната информация");
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
		$form->FLD('lineId', 'key(mvc=trans_Lines,select=title)', 'caption=Транспорт' . ($exLineId?'':''));
		$form->FLD('weight', 'cat_type_Weight', 'caption=Тегло');
		$form->FLD('volume', 'cat_type_Volume', 'caption=Обем');
		
		$rec->transUnitsInput = trans_Helper::convertToUnitTableArr($rec->transUnitsInput);
		trans_LineDetails::setTransUnitField($form, $rec->transUnitsInput);
		
		$form->FLD('lineNotes', 'text(rows=2)', 'caption=Забележки');
		$form->setOptions('lineId', array('' => '') + trans_Lines::getActiveLines());
		$form->setDefault('lineId', $rec->{$mvc->lineFieldName});
		$form->setDefault('weight', $rec->weightInput);
		$form->setDefault('volume', $rec->volumeInput);
		$form->setDefault('lineNotes', $rec->lineNotes);
		
		$form->input(NULL, 'silent');
		$form->input();
		
		if($form->isSubmitted()){
			$formRec = $form->rec;
			
			if($formRec->lineId){
				
				// Ако има избрана линия, проверка трябва ли задължително да има МОЛ
				$firstDocument = doc_Threads::getFirstDocument($rec->threadId);
				if($firstDocument && $firstDocument->isInstanceOf('deals_DealMaster')){
					if($methodId = $firstDocument->fetchField('paymentMethodId')){
						if(cond_PaymentMethods::isCOD($methodId) && !trans_Lines::fetchField("#id = {$formRec->lineId} AND #forwarderPersonId IS NOT NULL")){
							$form->setError('lineId', 'При наложен платеж, избраната линия трябва да има материално отговорно лице|*!');
						}
					}
				}
			}
			
			if(!$form->gotErrors()){
				
				// Обновяваме в мастъра информацията за общото тегло/обем и избраната линия
				$rec->weightInput = $formRec->weight;
				$rec->volumeInput = $formRec->volume;
				$rec->lineNotes = $formRec->lineNotes;
				$rec->transUnitsInput = trans_Helper::convertTableToNormalArr($formRec->transUnitsInput);
			
				$rec->{$mvc->lineFieldName} = $formRec->lineId;
				$mvc->save($rec);
				$mvc->updateMaster($rec);
				$mvc->logWrite('Редакция на транспорта', $rec->id);
					
				// Обновяване на modifiedOn на засегнатите транспортните линии
				if($rec->lineId) {
					$tRec = trans_Lines::fetch($rec->lineId);
					$tRec->modifiedOn = dt::now();
				} else {
					trans_LineDetails::delete("#containerId = {$rec->containerId}");
				}
				
				if($exLineId && $exLineId != $rec->lineId) {
					$tRec = trans_Lines::fetch($exLineId);
					$tRec->modifiedOn = dt::now();
					trans_Lines::save($tRec, 'modifiedOn');
				}
					
				// Редирект след успешния запис
				redirect($mvc->getSingleUrlArray($id), FALSE, '|Промените са записани успешно');
			}
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
		$transInfo = $mvc->getTotalTransportInfo($rec->id);
		
		core_Lg::push($rec->tplLang);
		
		setIfNot($rec->{$mvc->totalWeightFieldName}, $transInfo->weight);
		$rec->calcedWeight = $rec->{$mvc->totalWeightFieldName};
		$rec->{$mvc->totalWeightFieldName} = ($rec->weightInput) ? $rec->weightInput : $rec->{$mvc->totalWeightFieldName};
		$hintWeight = ($rec->weightInput) ? 'Транспортното тегло е въведено от потребител' : 'Транспортното тегло е сумарно от редовете';
		if(!isset($rec->{$mvc->totalWeightFieldName})) {
			$row->{$mvc->totalWeightFieldName} = "<span class='quiet'>N/A</span>";
		} else {
			$row->{$mvc->totalWeightFieldName} = $mvc->getFieldType($mvc->totalWeightFieldName)->toVerbal($rec->{$mvc->totalWeightFieldName});
			$row->{$mvc->totalWeightFieldName} = ht::createHint($row->{$mvc->totalWeightFieldName}, $hintWeight);
		}
			
		setIfNot($rec->{$mvc->totalVolumeFieldName}, $transInfo->volume);
		$rec->calcedVolume = $rec->{$mvc->totalVolumeFieldName};
		$rec->{$mvc->totalVolumeFieldName} = ($rec->volumeInput) ? $rec->volumeInput : $rec->{$mvc->totalVolumeFieldName};
		$hintVolume = ($rec->volumeInput) ? 'Транспортният обем е въведен от потребител' : 'Транспортният обем е сумарен от редовете';
		if(!isset($rec->{$mvc->totalVolumeFieldName})) {
			$row->{$mvc->totalVolumeFieldName} = "<span class='quiet'>N/A</span>";
		} else {
			$row->{$mvc->totalVolumeFieldName} = $mvc->getFieldType($mvc->totalVolumeFieldName)->toVerbal($rec->{$mvc->totalVolumeFieldName});
			$row->{$mvc->totalVolumeFieldName} = ht::createHint($row->{$mvc->totalVolumeFieldName}, $hintVolume);
		}
		
		if(isset($fields['-single'])){
			$row->logisticInfo = trans_Helper::displayTransUnits($rec->transUnits, $rec->transUnitsInput);
		}
		
		if(isset($rec->lineId)){
			$row->lineId = (isset($fields['-single'])) ? trans_Lines::getHyperlink($rec->lineId) : trans_Lines::getLink($rec->lineId, 0);
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
			$rec = $mvc->fetchRec($id);
			$Detail = cls::get($mvc->mainDetail);
			$res = cls::get($mvc->mainDetail)->getTransportInfo($rec->id, $force);
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
	
	
	
	/**
	 * Обновява мастъра
	 *
	 * @param mixed $id - ид/запис на мастъра
	 */
	public static function on_AfterUpdateMaster($mvc, &$res, $id)
	{
		$masterRec = $mvc->fetchRec($id);
		$details = arr::make($mvc->details, TRUE);
		
		$unitsArr = array();
		foreach ($details as $det){
			if(cls::haveInterface('store_iface_DetailsTransportData', $det)){
				$units = cls::get($det)->getTransUnits($masterRec);
				trans_Helper::sumTransUnits($unitsArr, $units);
			}
		}
		
		// Записват се сумарните ЛЕ от детайлите на документа
		$masterRec->transUnits = $unitsArr;
		$mvc->save_($masterRec, 'transUnits');
		
		// Синхронизиране с транспортната линия ако е избрана
		if(isset($masterRec->lineId)){
			trans_LineDetails::sync($masterRec->lineId, $masterRec->containerId);
		}
	}
	
	
	/**
	 * Информацията на документа, за показване в транспортната линия
	 * 
	 * @param core_Mvc $mvc
	 * @param $res
	 * 		['baseAmount'] double|NULL - сумата за инкасиране във базова валута
	 * 		['amount']     double|NULL - сумата за инкасиране във валутата на документа
	 * 		['currencyId'] string|NULL - валутата на документа
	 * 		['notes']      string|NULL - забележки за транспортната линия
	 *  	['stores']     array       - склад(ове) в документа
	 *   	['weight']     double|NULL - общо тегло на стоките в документа
	 *     	['volume']     double|NULL - oбщ обем на стоките в документа
	 *      ['transportUnits'] array   - използваните ЛЕ в документа, в формата ле -> к-во
	 *      	[transUnitId] => quantity
	 * @param mixed $id 
	 */
	public function on_AfterGetTransportLineInfo($mvc, &$res, $id)
	{
		$rec = $mvc->fetchRec($id);
		$transInfo = $mvc->getTotalTransportInfo($rec);
		
		if(empty($res['weight'])){
			$res['weight'] = ($rec->weightInput) ? $rec->weightInput : $transInfo->weight;
		}
		
		if(empty($res['volume'])){
			$res['volume'] = ($rec->volumeInput) ? $rec->volumeInput : $transInfo->volume;
		}
		
		if(empty($res['state'])){
			$res['state'] = $rec->state;
		}
		
		$res['transportUnits'] = trans_Helper::getCombinedTransUnits($rec->transUnits, $rec->transUnitsInput);
	}
	
	
	/**
	 * Изпълнява се преди оттеглянето на документа
	 */
	public static function on_BeforeReject(core_Mvc $mvc, &$res, $id)
	{
		$rec = $mvc->fetchRec($id);
		if(isset($rec->lineId)){
			core_Statuses::newStatus('Документа не може да се оттегли докато е включен в транспортна линия|*!', 'error');
	
			return FALSE;
		}
	}
	
	
	/**
	 * Трябва ли ръчно да се подготвя документа в Транспортната линия
	 *
	 * @param core_Mvc $mvc       - документ
	 * @param boolean $res        - TRUE или FALSE
	 * @param mixed $id           - ид или запис на документа
	 * @return void
	 */
	public static function on_AfterRequireManualCheckInTransportLine($mvc, &$res, $id)
	{
		if(!isset($res)) {
			$res = TRUE;
		}
	}
}