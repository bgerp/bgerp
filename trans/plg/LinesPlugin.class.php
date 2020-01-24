<?php


/**
 * Клас 'trans_plg_LinesPlugin'
 * Плъгин даващ възможност на даден документ лесно да му се избира транспортна линия
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
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
        setIfNot($mvc->lineFieldName, 'lineId');
        setIfNot($mvc->lineNoteFieldName, 'lineNotes');
        
        // Създаваме поле за избор на линия, ако няма такова
        if (!$mvc->getField($mvc->lineFieldName, false)) {
            $mvc->FLD($mvc->lineFieldName, 'key(mvc=trans_Lines,select=title,allowEmpty)', 'input=none');
        } else {
            $mvc->setField($mvc->lineFieldName, 'input=none');
        }
        
        $mvc->FLD('lineNotes', 'text(rows=2)', 'input=none,caption=Забележки');
        
        if(cls::haveInterface('store_iface_DocumentIntf', $mvc)){
            setIfNot($mvc->totalWeightFieldName, 'weight');
            setIfNot($mvc->totalVolumeFieldName, 'volume');
            
            // Създаваме поле за общ обем
            if (!$mvc->getField($mvc->totalVolumeFieldName, false)) {
                $mvc->FLD($mvc->totalVolumeFieldName, 'cat_type_Volume', 'input=none');
            } else {
                $mvc->setField($mvc->totalVolumeFieldName, 'input=none');
            }
            
            // Създаваме поле за общо тегло
            if (!$mvc->getField($mvc->totalWeightFieldName, false)) {
                $mvc->FLD($mvc->totalWeightFieldName, 'cat_type_Weight', 'input=none');
            } else {
                $mvc->setField($mvc->totalWeightFieldName, 'input=none');
            }
            
            $mvc->FLD('weightInput', 'cat_type_Weight', 'input=none');
            $mvc->FLD('volumeInput', 'cat_type_Volume', 'input=none');
            $mvc->FLD('transUnits', 'blob(serialize, compress)', 'input=none');
            $mvc->FLD('transUnitsInput', 'blob(serialize, compress)', 'input=none');
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        if ($rec->state != 'rejected') {
            $url = array($mvc, 'changeLine', $rec->id, 'ret_url' => true);
           
            if ($mvc->haveRightFor('changeLine', $rec)) {
                $data->toolbar->addBtn('Транспорт', $url, 'ef_icon=img/16/door_in.png, title = Промяна на транспортната информация');
            }
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Manager $mvc
     * @param mixed        $res
     * @param string       $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action != 'changeline') {
            
            return;
        }
        
        $mvc->requireRightFor('changeline');
        expect($id = Request::get('id', 'int'));
        expect($rec = $mvc->fetch($id));
        $mvc->requireRightFor('changeline', $rec);
        
        $exLineId = $rec->lineId;
        $form = cls::get('core_Form');
        
        $form->title = core_Detail::getEditTitle($mvc, $id, 'транспорт', $rec->id);
        $form->FLD('lineId', 'key(mvc=trans_Lines,select=title)', 'caption=Транспорт' . ($exLineId ? '' : ''));
        $form->FLD('lineNotes', 'text(rows=2)', 'caption=Забележки,after=volume');
        
        $form->setOptions('lineId', array('' => '') + trans_Lines::getSelectableLines());
        $form->setDefault('lineId', $rec->{$mvc->lineFieldName});
        $form->setDefault('lineNotes', $rec->lineNotes);
        
        if(cls::haveInterface('store_iface_DocumentIntf', $mvc)){
            $form->FLD('weight', 'cat_type_Weight', 'caption=Тегло');
            $form->FLD('volume', 'cat_type_Volume', 'caption=Обем');
            
            $rec->transUnitsInput = trans_Helper::convertToUnitTableArr($rec->transUnitsInput);
            trans_LineDetails::setTransUnitField($form, $rec->transUnitsInput);
            $form->setDefault('weight', $rec->weightInput);
            $form->setDefault('volume', $rec->volumeInput);
        }
        
        $form->input(null, 'silent');
        $form->input();
        
        if ($form->isSubmitted()) {
            $formRec = $form->rec;
            
            if (isset($formRec->lineId)) {
                
                // Ако има избрана линия, проверка трябва ли задължително да има МОЛ
                $firstDocument = doc_Threads::getFirstDocument($rec->threadId);
                if ($firstDocument && $firstDocument->isInstanceOf('deals_DealMaster')) {
                    if ($methodId = $firstDocument->fetchField('paymentMethodId')) {
                        if (cond_PaymentMethods::isCOD($methodId) && !trans_Lines::fetchField("#id = {$formRec->lineId} AND #forwarderPersonId IS NOT NULL")) {
                            $form->setError('lineId', 'При наложен платеж, избраната линия трябва да има материално отговорно лице|*!');
                        }
                    }
                }
            }
            
            if (!$form->gotErrors()) {
                $rec->lineNotes = $formRec->lineNotes;
                $rec->{$mvc->lineFieldName} = $formRec->lineId;
                
                if(cls::haveInterface('store_iface_DocumentIntf', $mvc)){
                    
                    // Обновяваме в мастъра информацията за общото тегло/обем и избраната линия
                    $rec->weightInput = $formRec->weight;
                    $rec->volumeInput = $formRec->volume;
                    $rec->transUnitsInput = trans_Helper::convertTableToNormalArr($formRec->transUnitsInput);
                }
                
                $mvc->save($rec);
                $mvc->updateMaster($rec);
                $mvc->logWrite('Редакция на транспорта', $rec->id);
                
                if (!$rec->lineId) {
                    trans_LineDetails::delete("#containerId = {$rec->containerId}");
                }
                
                if ($exLineId && $exLineId != $rec->lineId) {
                    $mvc->updateLines[$rec->lineId] = $exLineId;
                }
                
                // Редирект след успешния запис
                redirect($mvc->getSingleUrlArray($id), false, 'Промените са записани успешно|*!');
            }
        }
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $mvc->getSingleUrlArray($id), 'ef_icon = img/16/close-red.png');
        
        // Рендиране на формата
        $res = $form->renderHtml();
        $res = $mvc->renderWrapping($res);
        core_Form::preventDoubleSubmission($res, $form);
        
        // ВАЖНО: спираме изпълнението на евентуални други плъгини
        return false;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'changeline' && isset($rec)) {
            
            // На оттеглените не могат да се променят линиите
            if ($rec->state == 'rejected') {
                $requiredRoles = 'no_one';
            }
            
            if(!cls::haveInterface('store_iface_DocumentIntf', $mvc)){
                $selectableLines = trans_Lines::getSelectableLines();
                if(!countR($selectableLines)){
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'changeline' && isset($rec->lineId)) {
            $lineState = trans_Lines::fetchField($rec->lineId, 'state');
            if ($lineState != 'pending') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        core_Lg::push($rec->tplLang);
        
        if (isset($rec->lineId)) {
            $row->lineId = (isset($fields['-single'])) ? trans_Lines::getHyperlink($rec->lineId) : trans_Lines::getLink($rec->lineId, 0);
            
            if (!Mode::isReadOnly()) {
                $lineState = trans_Lines::fetchField($rec->lineId, 'state');
                $row->lineId = "<span class='state-{$lineState} document-handler' style='line-height:110%'>{$row->lineId}</span>";
            }
        }
        
        if(cls::haveInterface('store_iface_DocumentIntf', $mvc)){
            
            $transInfo = $mvc->getTotalTransportInfo($rec->id);
            $warningWeight = $warningVolume = false;
            
            setIfNot($rec->{$mvc->totalWeightFieldName}, $transInfo->weight);
            $rec->calcedWeight = $rec->{$mvc->totalWeightFieldName};
            $rec->{$mvc->totalWeightFieldName} = ($rec->weightInput) ? $rec->weightInput : $rec->{$mvc->totalWeightFieldName};
            $hintWeight = ($rec->weightInput) ? 'Транспортното тегло е въведено от потребител' : 'Транспортното тегло е сумарно от редовете';
            
            if($rec->calcedWeight && isset($rec->{$mvc->totalWeightFieldName})){
                $percentChange = abs(round((1 - $rec->{$mvc->totalWeightFieldName} / $rec->calcedWeight) * 100, 3));
                if($percentChange >= 25){
                    $warningWeight = true;
                }
            }
            
            if (!isset($rec->{$mvc->totalWeightFieldName})) {
                $row->{$mvc->totalWeightFieldName} = "<span class='quiet'>N/A</span>";
            } else {
                $row->{$mvc->totalWeightFieldName} = $mvc->getFieldType($mvc->totalWeightFieldName)->toVerbal($rec->{$mvc->totalWeightFieldName});
                $row->{$mvc->totalWeightFieldName} = ht::createHint($row->{$mvc->totalWeightFieldName}, $hintWeight);
                
                if($warningWeight){
                    $liveValueVerbal = $mvc->getFieldType($mvc->totalWeightFieldName)->toVerbal($rec->calcedWeight);
                    $row->{$mvc->totalWeightFieldName} = ht::createHint($row->{$mvc->totalWeightFieldName}, "Има разлика от над 25% с изчисленото|* {$liveValueVerbal}", 'warning', false);
                }
            }
            
            setIfNot($rec->{$mvc->totalVolumeFieldName}, $transInfo->volume);
            $rec->calcedVolume = $rec->{$mvc->totalVolumeFieldName};
            
            $rec->{$mvc->totalVolumeFieldName} = ($rec->volumeInput) ? $rec->volumeInput : $rec->{$mvc->totalVolumeFieldName};
            if($rec->calcedVolume && isset($rec->{$mvc->totalVolumeFieldName})){
                $percentChange = abs(round((1 - $rec->{$mvc->totalVolumeFieldName} / $rec->calcedVolume) * 100, 3));
                
                if($percentChange >= 25){
                    $warningVolume = true;
                }
            }
            
            $hintVolume = ($rec->volumeInput) ? 'Транспортният обем е въведен от потребител' : 'Транспортният обем е сумарен от редовете';
            if (!isset($rec->{$mvc->totalVolumeFieldName})) {
                $row->{$mvc->totalVolumeFieldName} = "<span class='quiet'>N/A</span>";
            } else {
                $row->{$mvc->totalVolumeFieldName} = $mvc->getFieldType($mvc->totalVolumeFieldName)->toVerbal($rec->{$mvc->totalVolumeFieldName});
                $row->{$mvc->totalVolumeFieldName} = ht::createHint($row->{$mvc->totalVolumeFieldName}, $hintVolume);
                
                if($warningVolume){
                    $liveVolumeVerbal = $mvc->getFieldType($mvc->totalVolumeFieldName)->toVerbal($rec->calcedVolume);
                    $row->{$mvc->totalVolumeFieldName} = ht::createHint($row->{$mvc->totalVolumeFieldName}, "Има разлика от над 25% с изчисленото|* {$liveVolumeVerbal}", 'warning', false);
                }
            }
            
            if (isset($fields['-single'])) {
                $row->logisticInfo = trans_Helper::displayTransUnits($rec->transUnits, $rec->transUnitsInput);
            }
        }
        
        core_Lg::pop();
    }
    
    
    /**
     * Изчисляване на общото тегло и обем на документа
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     *                        - weight - теглото на реда
     *                        - volume - теглото на реда
     * @param int      $id
     * @param bool     $force
     */
    public static function on_AfterGetTotalTransportInfo($mvc, &$res, $id, $force = false)
    {
        if (!$res) {
            $rec = $mvc->fetchRec($id);
            $res = cls::get($mvc->mainDetail)->getTransportInfo($rec->id, $force);
        }
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        if(!cls::haveInterface('store_iface_DocumentIntf', $mvc)) return;
        
        // Форсиране на мерките на редовете
        $measures = $mvc->getTotalTransportInfo($rec->id, true);
        
        // Ако няма обем или тегло се обновяват ако може
        if (empty($rec->{$mvc->totalVolumeFieldName}) || empty($rec->{$mvc->totalWeightFieldName})) {
            $rec->{$mvc->totalWeightFieldName} = $measures->weight;
            $rec->{$mvc->totalVolumeFieldName} = $measures->volume;
            $mvc->save_($rec, "{$mvc->totalWeightFieldName},{$mvc->totalVolumeFieldName}");
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        if (isset($rec->lineId)) {
            $mvc->updateLines[$rec->lineId] = $rec->lineId;
            if(!cls::haveInterface('store_iface_DocumentIntf', $mvc) && isset($rec->containerId)){
                $mvc->syncLineDetails[$rec->lineId] = $rec->containerId;
            }
        }
    }
    
    
    /**
     * Изчиства записите, заопашени за запис
     */
    public static function on_Shutdown($mvc)
    {
        // Обновяване на линиите
        if (is_array($mvc->updateLines)) {
            $Lines = cls::get('trans_Lines');
            foreach ($mvc->updateLines as $lineId) {
                $Lines->updateMaster($lineId);
            }
        }
        
        if (is_array($mvc->syncLineDetails)) {
            foreach ($mvc->syncLineDetails as $lineId => $containerId) {
                trans_LineDetails::sync($lineId, $containerId);
            }
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
        
        if(cls::haveInterface('store_iface_DocumentIntf', $mvc)){
            $details = arr::make($mvc->details, true);
            $unitsArr = array();
            foreach ($details as $det) {
                if (cls::haveInterface('store_iface_DetailsTransportData', $det)) {
                    $units = cls::get($det)->getTransUnits($masterRec);
                    trans_Helper::sumTransUnits($unitsArr, $units);
                }
            }
            
            // Записват се сумарните ЛЕ от детайлите на документа
            $masterRec->transUnits = $unitsArr;
            $mvc->save_($masterRec, 'transUnits');
        }
        
        // Синхронизиране с транспортната линия ако е избрана
        if (isset($masterRec->lineId)) {
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
     *     	['volume']     double|NULL - общ обем на стоките в документа
     *      ['transportUnits'] array   - използваните ЛЕ в документа, в формата ле -> к-во
     *      
     * @param mixed $id
     * @param int $lineId
     */
    public function on_AfterGetTransportLineInfo($mvc, &$res, $id, $lineId)
    {
        if(cls::haveInterface('store_iface_DocumentIntf', $mvc)){
            $rec = $mvc->fetchRec($id);
            $transInfo = $mvc->getTotalTransportInfo($rec);
            
            if (empty($res['weight'])) {
                $res['weight'] = ($rec->weightInput) ? $rec->weightInput : $transInfo->weight;
            }
            
            if (empty($res['volume'])) {
                $res['volume'] = ($rec->volumeInput) ? $rec->volumeInput : $transInfo->volume;
            }
            
            if (empty($res['state'])) {
                $res['state'] = $rec->state;
            }
            
            $res['transportUnits'] = trans_Helper::getCombinedTransUnits($rec->transUnits, $rec->transUnitsInput);
        }
    }
    
    
    /**
     * Трябва ли ръчно да се подготвя документа в Транспортната линия
     *
     * @param core_Mvc $mvc - документ
     * @param bool     $res - TRUE или FALSE
     * @param mixed    $id  - ид или запис на документа
     *
     * @return void
     */
    public static function on_AfterRequireManualCheckInTransportLine($mvc, &$res, $id)
    {
        if(!cls::haveInterface('store_iface_DocumentIntf', $mvc)){
            $res = false;
        } elseif (!isset($res)) {
            $res = true;
        }
    }
    
    
    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec     Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields  Имена на полетата, които трябва да бъдат записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        // За нескладовите документи
        if(!isset($rec->id) && !cls::haveInterface('store_iface_DocumentIntf', $mvc)){
            $containerId = isset($rec->fromContainerId) ? $rec->fromContainerId : $rec->originId;
            if(isset($containerId)){
                try{
                    // Дали е към някакъв друг документ
                    $Document = doc_Containers::getDocument($containerId);
                   
                    // Ако е към Ф-ра се гледа към кой документ е тя
                    if($Document->isInstanceOf('deals_InvoiceMaster')) {
                        if($invoiceOriginId = $Document->fetchField('sourceContainerId')){
                            $Document = doc_Containers::getDocument($invoiceOriginId);
                        }
                    }
                    
                    // Ако документа източник има този плъгин, ще се копира и транспортната му линия
                    if($Document->getInstance()->hasPlugin('trans_plg_LinesPlugin')){
                        
                        // Ако транспротната му линия все още може да се избира, прехвърля се на документа
                        if($oldLineId = $Document->fetchField($Document->lineFieldName)){
                            $sellectableLines = trans_Lines::getSelectableLines();
                            if(array_key_exists($oldLineId, $sellectableLines)){
                                $rec->{$mvc->lineFieldName} = $oldLineId;
                            }
                        }
                    }
                } catch(core_exception_Expect $e){
                    reportException($e);
                }
            }
        }
    }
}
