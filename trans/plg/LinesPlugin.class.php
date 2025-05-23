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
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class trans_plg_LinesPlugin extends core_Plugin
{
    /**
     * Константа за текст-а в лога при редакция
     */
    const EDIT_LOG_ACTION = 'Редакция на транспорта';


    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->declareInterface('trans_TransportableIntf');

        setIfNot($mvc->lineFieldName, 'lineId');
        setIfNot($mvc->lineNoteFieldName, 'lineNotes');

        // Създаваме поле за избор на линия, ако няма такова
        if (!$mvc->getField($mvc->lineFieldName, false)) {
            $mvc->FLD($mvc->lineFieldName, 'key(mvc=trans_Lines,select=title,allowEmpty)', 'input=none');
        } else {
            $mvc->setField($mvc->lineFieldName, 'input=none');
        }

        $mvc->FLD('lineNotes', 'richtext(rows=2, bucket=Notes)', 'input=none,caption=Забележки');

        if(cls::haveInterface('store_iface_DocumentIntf', $mvc)){
            setIfNot($mvc->totalWeightFieldName, 'weight');
            setIfNot($mvc->totalVolumeFieldName, 'volume');
            setIfNot($mvc->totalNetWeightFieldName, 'netWeight');
            setIfNot($mvc->totalTareWeightFieldName, 'tareWeight');

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

            // Създаваме поле за общо тегло
            if (!$mvc->getField($mvc->totalNetWeightFieldName, false)) {
                $mvc->FLD($mvc->totalNetWeightFieldName, 'cat_type_Weight', 'input=none');
            } else {
                $mvc->setField($mvc->totalNetWeightFieldName, 'input=none');
            }

            // Създаваме поле за общо тегло
            if (!$mvc->getField($mvc->totalTareWeightFieldName, false)) {
                $mvc->FLD($mvc->totalTareWeightFieldName, 'cat_type_Weight', 'input=none');
            } else {
                $mvc->setField($mvc->totalTareWeightFieldName, 'input=none');
            }

            $mvc->FLD('weightInput', 'cat_type_Weight', 'input=none');
            $mvc->FLD('netWeightInput', 'cat_type_Weight', 'input=none');
            $mvc->FLD('tareWeightInput', 'cat_type_Weight', 'input=none');
            $mvc->FLD('volumeInput', 'cat_type_Volume', 'input=none');
            $mvc->FLD('transUnits', 'blob(serialize, compress)', 'input=none');
            $mvc->FLD('transUnitsInput', 'blob(serialize, compress)', 'input=none');

            $dateFields = $mvc->getShipmentDateFields();
            foreach ($dateFields as $dateField => $dateObj){
                $mvc->FLD($dateField, $dateObj['type'], "{$dateObj['input']},caption={$dateObj['caption']},forceField");
                if(isset($dateObj['autoCalcFieldName'])){
                    $mvc->FLD($dateObj['autoCalcFieldName'], $dateObj['type'], "input=none");
                    $mvc->setField($dateField, "autoCalcDateField={$dateObj['autoCalcFieldName']}");
                }
            }
        }
    }


    /**
     * След подготовка на тулбара на единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;

        if ($rec->state != 'rejected') {
            if ($mvc->haveRightFor('changeline', $rec)) {
                $attr = arr::make('ef_icon=img/16/lorry_go.png, title = Промяна на логистичните данни на документа');
                if(isset($rec->{$mvc->lineFieldName})){
                    $lineState = trans_Lines::fetchField($rec->{$mvc->lineFieldName}, 'state');
                    if(in_array($lineState, array('active', 'closed'))){
                        $attr['warning'] = "Документът е включен в Активирана/Приключена Транспортна линия! Сигурни ли сте, че искате да промените Логистичните данни?";
                    }
                }
                $data->toolbar->addBtn('Транспорт', array($mvc, 'changeline', $rec->id, 'ret_url' => true), $attr);
            }
        }

        if (Request::get('editTrans')) {
            bgerp_Notifications::clear(array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'editTrans' => true), '*');
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
        if ($action != 'changeline') return;

        $mvc->requireRightFor('changeline');
        expect($id = Request::get('id', 'int'));
        expect($rec = $mvc->fetch($id));
        $mvc->requireRightFor('changeline', $rec);

        $exLineId = $rec->lineId;
        $form = cls::get('core_Form');
        $form->setAction(getCurrentUrl());
        $form->title = core_Detail::getEditTitle($mvc, $id, 'транспорт', $rec->id);
        $form->FLD('id', 'int', 'input=hidden,silent,caption=№');
        $form->FLD('lineFolderId', 'int', 'caption=Избор на транспортна линия->От папка,silent,removeAndRefreshForm=lineId');
        $form->FLD('lineId', 'key(mvc=trans_Lines,select=title,minimumResultsForSearch=0)', 'caption=Избор на транспортна линия->Транспорт,class=w100');
        $form->FLD('lineNotes', 'richtext(rows=2, bucket=Notes)', 'caption=Логистична информация->Забележки,after=volume');

        // Показване на полетата за датите
        $lineDateFields = null;
        if(cls::haveInterface('store_iface_DocumentIntf', $mvc)){
            $lineDateFields = $mvc->getShipmentDateFields($rec);
            foreach ($lineDateFields as $dateField => $dateObj){
                $form->FLD($dateField, $dateObj['type'], "caption=Времена->{$dateObj['caption']},forceField");
                $form->setDefault($dateField, $rec->{$dateField});
                if(!in_array($rec->state, array('draft', 'pending'))){
                    if($dateObj['readOnlyIfActive']){
                        $form->setReadOnly($dateField);
                    }
                }

                if(isset($dateObj['placeholder'])){
                    $placeholder = $form->getFieldType($dateField)->toVerbal($dateObj['placeholder']);
                    $form->setField($dateField, "placeholder={$placeholder}");
                }
            }
            $mvc->recalcAutoDates[$rec->id] = $rec;
        }

        $form->input(null, 'silent');
        $folderOptions = trans_Lines::getSelectableFolderOptions();

        // Ако има избрана линия за избрана папка е избраната на линията
        if(isset($rec->{$mvc->lineFieldName})){
            $lineFolderId = trans_Lines::fetchField($rec->{$mvc->lineFieldName}, 'folderId');
            $form->setDefault('lineFolderId', $lineFolderId);
            $form->setDefault('lineId', $rec->{$mvc->lineFieldName});
            if(!array_key_exists($lineFolderId, $folderOptions)){
                $folderOptions[$lineFolderId] = doc_Folders::getTitleById($lineFolderId, false);
            }
        } else {
            $userDefaultFolder = cls::get('trans_Lines')->getDefaultFolder();
            if(array_key_exists($userDefaultFolder, $folderOptions)){
                $form->setDefault('lineFolderId', $userDefaultFolder);
            }
        }

        $form->setDefault('lineFolderId', key($folderOptions));
        $linesArr = trans_Lines::getSelectableLines($form->rec->lineFolderId);
        if(isset($rec->{$mvc->lineFieldName}) && !array_key_exists($rec->{$mvc->lineFieldName}, $linesArr)){
            $linesArr[$rec->{$mvc->lineFieldName}] = trans_Lines::getTitleById($rec->{$mvc->lineFieldName}, false);
        }

        if(!countR($folderOptions)){
            $form->info = tr("|*<div style='margin:5px;color:red; background-color:yellow; border: dotted 1px red; padding:5px;'>|Потребителят няма споделени папки от, които да избира транспортна линия|*</div>");
            $form->setField('lineFolderId', 'input=none');
            $form->setField('lineId', 'input=none');
        } else {
            $form->setOptions('lineFolderId', $folderOptions);
            $form->setOptions('lineId', array('' => '') + $linesArr);
        }

        if(!countR($linesArr)){
            $form->info = tr("|*<div class='formCustomInfo'>|Няма транспортни линии на заявка с бъдеща дата в избраната папка|*!</div>");
        }
        $form->setDefault('lineNotes', $rec->lineNotes);

        // Ако е складов документ показват се и полета за складова информация
        if(cls::haveInterface('store_iface_DocumentIntf', $mvc)){
            $form->FLD('weight', 'cat_type_Weight', 'caption=Логистична информация->Бруто');
            $form->FLD('netWeight', 'cat_type_Weight', 'caption=Логистична информация->Нето');
            $form->FLD('tareWeight', 'cat_type_Weight', 'caption=Логистична информация->Тара');
            $form->FLD('volume', 'cat_type_Volume', 'caption=Логистична информация->Обем');

            $weightInput = Request::get('forceWeight', 'varchar') ? Request::get('forceWeight', 'varchar') : $rec->weightInput;
            $netWeightInput = Request::get('forceNetWeight', 'varchar') ? Request::get('forceNetWeight', 'varchar') : $rec->netWeightInput;
            $tareWeightInput = Request::get('forceTareWeight', 'varchar') ? Request::get('forceTareWeight', 'varchar') : $rec->tareWeightInput;
            $forceTransUnits = Request::get('forceTransUnits') ? Request::get('forceTransUnits') : $rec->transUnitsInput;

            $rec->transUnitsInput = trans_Helper::convertToUnitTableArr($forceTransUnits);
            trans_LineDetails::setTransUnitField($form, $rec->transUnitsInput);
            $form->setDefault('weight', $weightInput);
            $form->setDefault('netWeight', $netWeightInput);
            $form->setDefault('tareWeight', $tareWeightInput);
            $form->setDefault('volume', $rec->volumeInput);
        }

        $form->input();

        if ($form->isSubmitted()) {
            $formRec = $form->rec;
            if (isset($formRec->lineId)) {
                $lineRec = trans_Lines::fetch("#id = {$formRec->lineId}");
                
                // Ако има избрана линия, проверка трябва ли задължително да има МОЛ
                $firstDocument = doc_Threads::getFirstDocument($rec->threadId);
                if ($firstDocument && $firstDocument->isInstanceOf('deals_DealMaster')) {
                    if ($methodId = $firstDocument->fetchField('paymentMethodId')) {
                        if (cond_PaymentMethods::isCOD($methodId) && !trans_Lines::fetchField("#id = {$formRec->lineId} AND #forwarderPersonId IS NOT NULL")) {
                            $form->setError('lineId', 'При наложен платеж, избраната линия трябва да има материално отговорно лице|*!');
                        }
                    }
                }

                if(cls::haveInterface('store_iface_DocumentIntf', $mvc)) {
                    $clone = clone $rec;
                    foreach ($form->rec as $f => $v) {
                        $clone->{$f} = $v;
                    }
                    $lineDateFields = $mvc->getShipmentDateFields($clone);
                    $deliveryOn = !empty($form->rec->deliveryTime) ? $form->rec->deliveryTime : $lineDateFields['deliveryTime']['placeholder'];

                    if($lineRec->start < $deliveryOn) {
                        $deliveryOn = dt::mysql2verbal($deliveryOn);
                        $form->setWarning('lineId', "Началото на линията е преди очакваната дата на товарене|*: {$deliveryOn}!");
                    }
                }
            }

            // Проверка на логистичната информация
            $checkTransData = deals_Helper::checkTransData($formRec->weight, $formRec->netWeight, $formRec->tareWeight, 'weight', 'netWeight', 'tareWeight');
            if(countR($checkTransData['errors'])){
                foreach ($checkTransData['errors'] as $errArr){
                    $form->setError($errArr['fields'], $errArr['text']);
                }
            }

            if (!$form->gotErrors()) {
                $rec->lineNotes = $formRec->lineNotes;
                $rec->{$mvc->lineFieldName} = $formRec->lineId;
                if(is_array($lineDateFields)){
                    foreach ($lineDateFields as $dateFld => $dateObj){
                        if(!in_array($rec->state, array('draft', 'pending'))) {
                            if ($dateObj['readOnlyIfActive']) continue;
                        }
                        $rec->{$dateFld} = $formRec->{$dateFld};
                    }
                }

                if(cls::haveInterface('store_iface_DocumentIntf', $mvc)){

                    // Обновяваме в мастъра информацията за общото тегло/обем и избраната линия
                    $rec->weightInput = $formRec->weight;
                    $rec->volumeInput = $formRec->volume;
                    $rec->netWeightInput = $formRec->netWeight;
                    $rec->tareWeightInput = $formRec->tareWeight;
                    $rec->transUnitsInput = trans_Helper::convertTableToNormalArr($formRec->transUnitsInput);
                } elseif($mvc instanceof cash_Document){
                    if(isset($rec->{$mvc->lineFieldName}) && empty($rec->peroCase)){
                        if($lineCaseId = trans_Lines::fetchField($rec->{$mvc->lineFieldName}, 'defaultCaseId')){
                            $rec->peroCase = $lineCaseId;
                        }
                    }
                }

                core_Cache::remove($mvc->className, "earliestDateAllAvailable{$rec->containerId}");
                core_Cache::remove($mvc->className, "loadingDate{$rec->containerId}");

                $rec->_changeLine = true;
                $mvc->save($rec);
                $mvc->updateMaster($rec);
                $mvc->logWrite(static::EDIT_LOG_ACTION, $rec->id);

                // Нотифициране на всички други потребители, редактирали транспорта преди
                static::notifyTransportEditors($mvc, $rec);

                if (!$rec->lineId) {
                    trans_LineDetails::delete("#containerId = {$rec->containerId}");
                }

                if ($exLineId && $exLineId != $rec->lineId) {
                    $mvc->updateLines[$exLineId] = $exLineId;
                }

                // Редирект след успешния запис
                followRetUrl(null, '|Промените са записани успешно|*!');
            }
        }

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        // Рендиране на формата
        $res = $form->renderHtml();
        $res = $mvc->renderWrapping($res);
        core_Form::preventDoubleSubmission($res, $form);

        // ВАЖНО: спираме изпълнението на евентуални други плъгини
        return false;
    }


    /**
     * Изпращане на нотификации на другите потребителите, редактирали транспорта
     *
     * @param core_Mvc $mvc     - документ
     * @param stdCLass|int $rec - запис
     * @param int|null $userId  - ид на потребител, null за текущия
     * @return void
     */
    private static function notifyTransportEditors($mvc, $rec, $userId = null)
    {
        // Кои са потребителите променяли транспорта
        $userId = isset($userId) ? $userId : core_Users::getCurrent('id');
        $rec = $mvc->fetchRec($rec);
        $oRecs = log_Data::getObjectRecs($mvc->className, $rec->id, 'write', static::EDIT_LOG_ACTION);
        $editorsArr = arr::extractValuesFromArray($oRecs, 'userId');

        // Подготовка на съобщението
        $handle = $mvc->getHandle($rec);
        $lineRec = isset($rec->lineId) ? trans_Lines::fetch($rec->lineId) : null;
        $currentUserNick = core_Users::getCurrent('nick');

        // Оставят се само потребителите различни от посочения, които са редактирали транспорта
        unset($editorsArr[$userId]);

        // Изпращане на нотификация, ако все още имат достъп до документа
        foreach ($editorsArr as $editorUserId){
            $url = null;

            // Ако документа е към ТЛ и има достъп до нея - линка сочи на там, иначе към сингъла на документа
            if(is_object($lineRec) && trans_Lines::haveRightFor('single', $lineRec, $editorUserId)){
                $url = array('doc_Containers', 'list', 'threadId' => $lineRec->threadId, '#' => $handle, 'editTrans' => true);
            } elseif($mvc->haveRightFor('single', $rec->id, $editorUserId)){
                $url = array('doc_Containers', 'list', 'threadId' => $rec->threadId, "#" => $handle, 'editTrans' => true);
            }

            if(is_array($url)){
                $customUrl = $url;
                unset($customUrl['#']);
                bgerp_Notifications::add("|*{$currentUserNick} |промени информацията за транспорта на|* #{$handle}", $customUrl, $editorUserId, null, $url);
            }
        }
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

        if ($action == 'changeline' && isset($rec->{$mvc->lineFieldName})) {
            $lineState = trans_Lines::fetchField($rec->{$mvc->lineFieldName}, 'state');
            if (!in_array($lineState, array('pending', 'active', 'closed'))) {
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
        $showTransInfo = trans_Setup::get('SHOW_LOG_INFO_IN_DOCUMENTS');

        if (isset($rec->lineId)) {
            if($showTransInfo == 'show' || ($showTransInfo == 'hide' && !Mode::isReadOnly())){
                $lineRec = trans_Lines::fetch($rec->lineId);
                $row->lineId = '';
                if(isset($mvc->termDateFld) && $lineRec->start != $rec->{$mvc->termDateFld}){
                    $lineDate = str_replace(' 00:00', '', dt::mysql2verbal($lineRec->start, 'd.m.Y H:i'));
                    $row->lineId .= $lineDate . '/';
                }
                $row->lineId .= trans_Lines::getVerbal($lineRec, 'title');
                if(!Mode::is('printing') && doc_Threads::haveRightFor('single', $lineRec->threadId)){
                    $lineSingleUrl = array('doc_Containers', 'list', 'threadId' => $lineRec->threadId, '#' => $mvc->getHandle($rec));
                    $row->lineId = ht::createLink($row->lineId, $lineSingleUrl, false, 'ef_icon=img/16/lorry_go.png,title=Разглеждане на транспортната линия');
                }

                if(!Mode::is('printing')){
                    $row->lineId = "<span class='document-handler state-{$lineRec->state}'>{$row->lineId}</span>";
                }

                if(!empty($lineRec->forwarderId)){
                    $row->lineForwarderId = crm_Companies::getHyperlink($lineRec->forwarderId);
                }

                if(!empty($lineRec->vehicle)){
                    $row->lineVehicleId = core_Type::getByName('varchar')->toVerbal($lineRec->vehicle);
                    if ($vehicleRec = trans_Vehicles::fetch(array("#name = '[#1#]'", $lineRec->vehicle))) {
                        if(!empty($vehicleRec->number)){
                            $row->lineVehicleId = trans_Vehicles::getVerbal($vehicleRec, 'number');
                        }
                    }
                }
            } else {
                unset($row->lineId);
            }
        }

        if(cls::haveInterface('store_iface_DocumentIntf', $mvc)){

            $transInfo = $mvc->getTotalTransportInfo($rec->id);
            $warningWeight = $warningVolume = false;

            // Вербално показване на общото бруто тегло
            setIfNot($rec->{$mvc->totalWeightFieldName}, $transInfo->weight);
            $rec->calcedWeight = $rec->{$mvc->totalWeightFieldName};
            $rec->{$mvc->totalWeightFieldName} = ($rec->weightInput) ? $rec->weightInput : $rec->{$mvc->totalWeightFieldName};
            $hintWeight = ($rec->weightInput) ? 'Транспортното тегло е въведено от потребител' : 'Транспортното тегло е сумарно от редовете';
            $hintNetWeight = ($rec->netWeightInput) ? 'Нето теглото е въведено от потребител' : 'Нето теглото е сумарно от редовете';
            $hintTareWeight = ($rec->tareWeightInput) ? 'Теглото на тарата е въведено от потребител' : 'Теглото на тарата е сумарно от редовете';

            $weightIsLive = !$rec->weightInput;
            $netWeightIsLive = !$rec->netWeightInput;
            $volumeIsLive = !$rec->volumeInput;
            $tareWeightIsLive = !$rec->tareWeightInput;

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
                if($weightIsLive && !Mode::isReadOnly()){
                    $row->{$mvc->totalWeightFieldName} = "<span style='color:blue'>{$row->{$mvc->totalWeightFieldName}}</span>";
                }
                if(isset($rec->calcedWeight) && $rec->weightInput){
                    $hintWeight .= "|*. |Сумарно от редовете|*: " . $mvc->getFieldType($mvc->totalWeightFieldName)->toVerbal($rec->calcedWeight);
                }

                $row->{$mvc->totalWeightFieldName} = ht::createHint($row->{$mvc->totalWeightFieldName}, $hintWeight, 'noicon', false);
                if($warningWeight){
                    $liveValueVerbal = $mvc->getFieldType($mvc->totalWeightFieldName)->toVerbal($rec->calcedWeight);
                    $row->{$mvc->totalWeightFieldName} = ht::createHint($row->{$mvc->totalWeightFieldName}, "Има разлика от над 25% с изчисленото|* {$liveValueVerbal}", 'warning', false);
                }
            }

            // Вербално показване на общото нето тегло
            setIfNot($rec->{$mvc->totalNetWeightFieldName}, $transInfo->netWeight);
            $rec->calcedNetWeight = $rec->{$mvc->totalNetWeightFieldName};
            $rec->{$mvc->totalNetWeightFieldName} = ($rec->netWeightInput) ? $rec->netWeightInput : $rec->{$mvc->totalNetWeightFieldName};

            if (!isset($rec->{$mvc->totalNetWeightFieldName})) {
                $row->{$mvc->totalNetWeightFieldName} = "<span class='quiet'>N/A</span>";
            } else {
                $row->{$mvc->totalNetWeightFieldName} = $mvc->getFieldType($mvc->totalNetWeightFieldName)->toVerbal($rec->{$mvc->totalNetWeightFieldName});
                if($netWeightIsLive && !Mode::isReadOnly()){
                    $row->{$mvc->totalNetWeightFieldName} = "<span style='color:blue'>{$row->{$mvc->totalNetWeightFieldName}}</span>";
                }
                if(isset($rec->calcedNetWeight) && $rec->netWeightInput){
                    $hintNetWeight .= "|*. |Сумарно от редовете|*: " . $mvc->getFieldType($mvc->totalNetWeightFieldName)->toVerbal($rec->calcedNetWeight);
                }

                $row->{$mvc->totalNetWeightFieldName} = ht::createHint($row->{$mvc->totalNetWeightFieldName}, $hintNetWeight, 'noicon', false);
            }

            // Вербално показване на общото нето тегло
            setIfNot($rec->{$mvc->totalTareWeightFieldName}, $transInfo->tareWeight);
            $rec->calcedTareWeight = $rec->{$mvc->totalTareWeightFieldName};
            $rec->{$mvc->totalTareWeightFieldName} = ($rec->tareWeightInput) ? $rec->tareWeightInput : $rec->{$mvc->totalTareWeightFieldName};

            if (!isset($rec->{$mvc->totalTareWeightFieldName})) {
                $row->{$mvc->totalTareWeightFieldName} = "<span class='quiet'>N/A</span>";
            } else {
                $row->{$mvc->totalTareWeightFieldName} = $mvc->getFieldType($mvc->totalTareWeightFieldName)->toVerbal($rec->{$mvc->totalTareWeightFieldName});
                if($tareWeightIsLive && !Mode::isReadOnly()){
                    $row->{$mvc->totalTareWeightFieldName} = "<span style='color:blue'>{$row->{$mvc->totalTareWeightFieldName}}</span>";
                }
                if(isset($rec->calcedTareWeight)&& $rec->tareWeightInput){
                    $hintTareWeight .= "|*. |Сумарно от редовете|*: " . $mvc->getFieldType($mvc->totalTareWeightFieldName)->toVerbal($rec->calcedTareWeight);
                }

                $row->{$mvc->totalTareWeightFieldName} = ht::createHint($row->{$mvc->totalTareWeightFieldName}, $hintTareWeight, 'noicon', false);
            }

            // Вербално показване на общия обем
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
                if($volumeIsLive && !Mode::isReadOnly()){
                    $row->{$mvc->totalVolumeFieldName} = "<span style='color:blue'>{$row->{$mvc->totalVolumeFieldName}}</span>";
                }
                $row->{$mvc->totalVolumeFieldName} = ht::createHint($row->{$mvc->totalVolumeFieldName}, $hintVolume, 'noicon', false);

                if($warningVolume){
                    $liveVolumeVerbal = $mvc->getFieldType($mvc->totalVolumeFieldName)->toVerbal($rec->calcedVolume);
                    $row->{$mvc->totalVolumeFieldName} = ht::createHint($row->{$mvc->totalVolumeFieldName}, "Има разлика от над 25% с изчисленото|* {$liveVolumeVerbal}", 'warning', false);
                }
            }

            if (isset($fields['-single'])) {
                if(!empty($rec->transUnitsInput)){
                    $units = $rec->transUnitsInput;
                    $hint = '|Лог. ед. са ръчно въведени за целия документ|*';
                    $hintType = 'notice';
                } else {
                    $units = ($rec->transUnits) ? $rec->transUnits : $transInfo->transUnits;
                    $rec->transUnitsCalced = ($rec->transUnits) ? $rec->transUnits : $transInfo->transUnits;
                    $hint = tr('Лог. ед. са изчислени сумарно за документа');
                    $hintType = 'noicon';
                }

                if(countR($units)){
                    $row->logisticInfo = trans_Helper::displayTransUnits($units);
                    $row->logisticInfo = ht::createHint($row->logisticInfo, $hint, $hintType, false);
                    if(empty($rec->transUnitsInput) && empty($rec->transUnits) && !Mode::isReadOnly()){
                        $row->logisticInfo = "<span style='color:blue'>{$row->logisticInfo}</span>";
                    }
                }
            }

            $dateFields = !in_array($rec->state, array('draft', 'pending')) ? $mvc->getShipmentDateFields() : $mvc->getShipmentDateFields($rec, true);
            $datesArr = array();

            // За дефолтните дати
            foreach ($dateFields as $dateFld => $dateObj){
                $value = $rec->{$dateFld};

                if(!empty($dateObj['placeholder']) && empty($rec->{$dateFld})){
                    $row->{$dateFld} = $mvc->getFieldType($dateFld)->toVerbal($dateObj['placeholder']);
                    if(!Mode::isReadOnly()){
                        $row->{$dateFld} = "<span style='color:blue;'>{$row->{$dateFld}}</span>";
                        $row->{$dateFld} = ht::createHint($row->{$dateFld}, 'Изчислено е автоматично|*!');
                    }
                    $value = $dateObj['placeholder'];
                }

                if (Mode::is('printing') || Mode::is('text', 'xhtml')) {
                    if($dateObj['displayExternal'] !== true && $showTransInfo == 'hide'){
                        unset($row->{$dateFld});
                    }
                }

                if(!empty($value)){
                    $datesArr[$dateFld] = array('key' => $dateFld, 'value' => $value, 'caption' => $dateObj['caption']);
                    $compareDate = (strlen($value) == 10) ? "{$value} 23:59:50" : $value;
                    $datesArr[$dateFld]['compareDate'] = $compareDate;
                }
            }

            // Ако не не са във възходящ ред да се оцветят в червено
            if(!Mode::isReadOnly()){

                // Проверяват се датите
                $now = dt::now();
                $warnings = array();
                foreach ($datesArr as $i => $dObj){
                    if($i != 0){
                        if($dObj['value'] < $datesArr[$i-1]['value']){
                            $warnings[$dObj['key']][] = "Датата е преди|* " . '"|' . $datesArr[$i-1]['caption'] . '|*"';
                        }
                    }

                    if(in_array($rec->state, array('draft', 'pending'))){
                        if($dObj['compareDate'] < $now) {
                            $warnings[$dObj['key']][] = "Датата е в миналото|*!";
                        }
                    }
                }

                // За всяко генерирано предупреждение - датата се разкрасява да се види
                foreach ($warnings as $warningFld => $fieldWarningArr){
                    foreach ($fieldWarningArr as $warningMsg){
                        $row->{$warningFld} = ht::createHint($row->{$warningFld}, $warningMsg, 'warning');
                    }
                    $row->{$warningFld}->prepend("<div class='shipmentErrorDateBlock'>");
                    $row->{$warningFld}->prepend("</div>");
                }
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
     * При оттегляне на документ
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        // При оттегляне, ако е към т.линия кара се да се обнови
        $rec = $mvc->fetchRec($id);
        if($rec->brState == 'active'){
            if(isset($rec->lineId)){
                $mvc->updateLines[$rec->lineId] = $rec->lineId;
            }
        }
    }


    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        if(isset($rec->lineId)){
            $mvc->updateLines[$rec->lineId] = $rec->lineId;
        }

        if(!cls::haveInterface('store_iface_DocumentIntf', $mvc)) return;

        // Форсиране на мерките на редовете
        $measures = $mvc->getTotalTransportInfo($rec->id, true);

        // Кеширане на изчислено тегло/обем/ле ако не са изчислени
        $updateFields = array();
        if(empty($rec->{$mvc->totalVolumeFieldName})) {
            $rec->{$mvc->totalWeightFieldName} = $measures->weight;
            $updateFields[] = $mvc->totalWeightFieldName;
        }

        if(empty($rec->{$mvc->totalWeightFieldName})) {
            $rec->{$mvc->totalVolumeFieldName} = $measures->volume;
            $updateFields[] = $mvc->totalVolumeFieldName;
        }

        if(empty($rec->{$mvc->totalNetWeightFieldName})) {
            $rec->{$mvc->totalNetWeightFieldName} = $measures->netWeight;
            $updateFields[] = $mvc->totalNetWeightFieldName;
        }


        if(empty($rec->transUnits)) {
            $rec->transUnits = $measures->transUnits;
            $updateFields[] = 'transUnits';
        }

        if(countR($updateFields)){
            $mvc->save_($rec, $updateFields);
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
        if($rec->_fromForm){
            if(cls::haveInterface('store_iface_DocumentIntf', $mvc)){
                if(in_array($rec->state, array('draft', 'pending'))){
                    $mvc->recalcAutoDates[$rec->id] = $rec;
                }
            }
        }

        if (isset($rec->lineId)) {
            if($rec->_changeLine || $rec->_fromForm) {
                $mvc->updateLines[$rec->lineId] = $rec->lineId;
                $mvc->syncLineDetails[$rec->lineId] = $rec->containerId;
            }
        }
    }


    /**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    public static function on_AfterSessionClose($mvc)
    {
        // Обновяване на линиите
        if (is_array($mvc->syncLineDetails)) {
            foreach ($mvc->syncLineDetails as $lineId => $containerId) {
                trans_LineDetails::sync($lineId, $containerId);
            }
        }

        if (is_array($mvc->updateLines)) {
            $Lines = cls::get('trans_Lines');
            foreach ($mvc->updateLines as $lineId) {
                $Lines->updateMaster($lineId);
            }
        }

        if (is_array($mvc->recalcAutoDates)) {
            foreach ($mvc->recalcAutoDates as $rec) {
                $mvc->recalcShipmentDateFields($rec, true);
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

        // Синхронизиране с транспортната линия ако е избрана
        if (isset($masterRec->lineId)) {
            cls::get('trans_Lines')->updateMaster($masterRec->lineId);
        }
    }


    /**
     * Информацията на документа, за показване в транспортната линия
     *
     * @param core_Mvc $mvc
     *
     * @return array
     *               ['baseAmount']     double|NULL - сумата за инкасиране във базова валута
     *               ['amount']         double|NULL - сумата за инкасиране във валутата на документа
     *               ['amountVerbal']   double|NULL - сумата за инкасиране във валутата на документа
     *               ['currencyId']     string|NULL - валутата на документа
     *               ['notes']          string|NULL - забележки за транспортната линия
     *               ['stores']         array       - склад(ове) в документа
     *               ['weight']         double|NULL - общо тегло на стоките в документа
     *               ['volume']         double|NULL - общ обем на стоките в документа
     *               ['transportUnits'] array   - използваните ЛЕ в документа, в формата ле -> к-во
     *               ['contragentName'] double|NULL - име на контрагента
     *               ['address']        double|NULL - адрес ба диставка
     *               ['storeMovement']  string|NULL - посока на движението на склада
     *               ['locationId']     string|NULL - ид на локация на доставка (ако има)
     *               ['addressInfo']    string|NULL - информация за адреса
     *               ['countryId']      string|NULL - ид на държава
     *
     * @param mixed $id
     * @param int $lineId
     * @return void
     */
    public function on_AfterGetTransportLineInfo($mvc, &$res, $id, $lineId)
    {
        if(cls::haveInterface('store_iface_DocumentIntf', $mvc)){
            $rec = $mvc->fetchRec($id);
            $transInfo = $mvc->getTotalTransportInfo($rec);
            if(core_Packs::isInstalled('rack')){
                if($zoneRec = rack_Zones::fetch("#containerId = {$rec->containerId}", 'id,readiness')){
                    $res['zoneId'] = $zoneRec->id;
                    $res['readiness'] = ($zoneRec->readiness) ? $zoneRec->readiness : 0;
                }
            }

            if (empty($res['weight'])) {
                $res['weight'] = ($rec->weightInput) ? $rec->weightInput : $transInfo->weight;
            }

            if (empty($res['volume'])) {
                $res['volume'] = ($rec->volumeInput) ? $rec->volumeInput : $transInfo->volume;
            }

            if (empty($res['state'])) {
                $res['state'] = $rec->state;
            }

            $units =  !empty($rec->transUnitsInput) ? $rec->transUnitsInput : $transInfo->transUnits;
            $res['transportUnits'] = $units;
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

                        // Ако транспортната му линия все още може да се избира, прехвърля се на документа
                        if($oldLineId = $Document->fetchField($Document->lineFieldName)){
                            $sellectableLines = trans_Lines::getSelectableLines();
                            if(array_key_exists($oldLineId, $sellectableLines)){
                                $rec->{$mvc->lineFieldName} = $oldLineId;

                                if($mvc instanceof cash_Document){
                                    $lineCaseId = trans_Lines::fetchField($oldLineId, 'defaultCaseId');
                                    if($lineCaseId && empty($rec->peroCase)){
                                        $rec->peroCase = $lineCaseId;
                                    }
                                }
                            }
                        }
                    }
                } catch(core_exception_Expect $e){
                    reportException($e);
                }
            }
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        if ($form->isSubmitted()) {
            $rec->_fromForm = true;
        }
    }


    /**
     * След взимане на полетата, които да не се клонират
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $rec
     */
    public static function on_AfterGetFieldsNotToClone($mvc, &$res, $rec)
    {
        $unsetFields = array($mvc->lineFieldName, $mvc->lineNoteFieldName);
        if(cls::haveInterface('store_iface_DocumentIntf', $mvc)){
            $unsetFields = array_merge($unsetFields, array('weightInput', 'volumeInput', 'transUnits', 'transUnitsInput', $mvc->totalWeightFieldName, $mvc->totalVolumeFieldName, $mvc->totalNetWeightFieldName), array_keys($mvc->getShipmentDateFields()));
        }

        foreach ($unsetFields as $fld){
            $res[$fld] = $fld;
        }
    }


    /**
     * Метод по подразбиране за преизчисляване на автоматично изчислените дати
     */
    public static function on_AfterRecalcShipmentDateFields($mvc, &$res, &$rec, $save = false)
    {
        if(isset($res)) return;

        $updateFields = array();

        // Извличат се изчислените дати
        $shippedDates = $mvc->getShipmentDateFields($rec);

        foreach ($shippedDates as $dateFld => $obj){

            // Ако има лайв изчислена записва се в река
            if(isset($obj['autoCalcFieldName']) && !empty($obj['placeholder'])){
                $rec->{$obj['autoCalcFieldName']} = $obj['placeholder'];
                $updateFields[$obj['autoCalcFieldName']] = $obj['autoCalcFieldName'];
            }
        }

        $res = false;
        if(countR($updateFields)){
            // Ако се иска да се обнови сега записа - обновява се
            if($save){
                $mvc->save_($rec, $updateFields);
            }
            $res = true;
        }
    }
}