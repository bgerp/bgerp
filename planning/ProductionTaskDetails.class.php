<?php


/**
 * Клас 'planning_ProductionTaskDetails'
 *
 * Мениджър за Прогрес на производствените операции
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_ProductionTaskDetails extends doc_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Прогрес на производствените операции';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Прогрес';


    /**
     * Интерфейси
     */
    public $interfaces = 'hr_IndicatorsSourceIntf,label_SequenceIntf=planning_interface_TaskLabelDetail';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'taskId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_AlignDecimals2, plg_SaveAndNew, plg_Rejected, plg_Modified, plg_Created, plg_LastUsedKeys, plg_Sorting, planning_Wrapper, plg_Search, plg_GroupByField';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'fixedAsset';
    
    
    /**
     * Кой има право да оттегля?
     */
    public $canReject = 'taskWorker,ceo';
    
    
    /**
     * Кой има право да възстановява?
     */
    public $canRestore = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'taskWorker,ceo';
    
    
    /**
     * Кой има право да редактира?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да листва?
     */
    public $canList = 'taskWorker,ceo';


    /**
     * Кой има право да оправя записите?
     */
    public $canFix = 'taskWorker,ceo';


    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'taskId,type=Операция,serial,productId,taskId,quantity,weight=Тегло (кг),employees,fixedAsset,date=Дата,info=@';
    
    
    /**
     * Кои колони да скриваме ако янма данни в тях
     */
    public $hideListFieldsIfEmpty = 'serial,weight,employees,fixedAsset,quantity,quantityExtended,typeExtended,additional,batch';


    /**
     * Активен таб на менюто
     */
    public $currentTab = 'Операции->Прогрес';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'productId,type,fixedAsset,employees,notes';


    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 30;


    /**
     * Рендиране на мастъра под формата за редактиране/добавяне
     */
    public $renderMasterBellowForm = true;


    /**
     * Каква да е максималната дължина на стринга за пълнотекстово търсене
     */
    public $maxSearchKeywordLen = 13;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('taskId', 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory,caption=Операция');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'silent,caption=Артикул,removeAndRefreshForm=serial|quantity,mandatory');
        $this->FLD('type', 'enum(input=Влагане,production=Произв.,waste=Отпадък,scrap=Бракуване)', 'input=hidden,silent,tdClass=small-field nowrap');
        $this->FLD('serial', 'varchar(32)', 'caption=Производ. №,focus,autocomplete=off,silent');
        $this->FLD('serialType', 'enum(existing=Съществуващ,generated=Генериран,printed=Отпечатан,unknown=Непознат)', 'caption=Тип на серийния номер,input=none');
        $this->FLD('quantity', 'double(Min=0)', 'caption=Количество,silent');
        $this->FLD('weight', 'double(Min=0)', 'caption=Тегло,unit=кг');
        $this->FLD('employees', 'keylist(mvc=crm_Persons,select=id,makeLinks)', 'caption=Оператори,input=hidden');
        $this->FLD('fixedAsset', 'key(mvc=planning_AssetResources,select=id)', 'caption=Допълнително->Оборудване,input=none,tdClass=nowrap,smartCenter');
        $this->FLD('date', 'datetime', 'caption=Допълнително->Дата');
        $this->FNC('otherEmployees', 'planning_type_Operators(mvc=crm_Persons)', 'caption=Допълнително->Други оператори,input');        
        $this->FLD('notes', 'richtext(rows=2,bucket=Notes)', 'caption=Допълнително->Забележки');
        $this->FLD('state', 'enum(active=Активирано,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull');
        $this->FLD('norm', 'planning_type_ProductionRate', 'caption=Време,input=none');
        $this->FNC('scrapRecId', 'int', 'caption=Време,input=hidden,silent');

        $this->setDbIndex('type');
        $this->setDbIndex('serial');
        $this->setDbIndex('taskId,productId');
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$data->form->rec;

        // Ако с бракува конкретен ред, задават се дефолтите от предишните
        if($rec->type == 'scrap' && isset($rec->scrapRecId)){
            $scrapRec = $mvc->fetch($rec->scrapRecId);
            $form->setDefault('serial', $scrapRec->serial);
            $form->setDefault('employees', $scrapRec->employees);
            $form->setDefault('fixedAsset', $scrapRec->fixedAsset);
        }

        // Добавяне на последните данни за дефолтни
        $masterRec = planning_Tasks::fetch($rec->taskId);
        $query = $mvc->getQuery();
        $query->where("#taskId = {$rec->taskId}");
        $query->orderBy('id', 'DESC');

        // Задаваме последно въведените данни
        if ($lastRec = $query->fetch()) {
            $form->setDefault('employees', $lastRec->employees);
        }

        // Ако в мастъра са посочени машини, задават се като опции
        if (isset($masterRec->assetId)) {
            $allowedAssets = array($masterRec->assetId => $masterRec->assetId);
            if($Driver = cat_Products::getDriver($masterRec->productId)){
                $productionData = $Driver->getProductionData($masterRec->productId);
                if(is_array($productionData['fixedAssets'])){
                    $allowedAssets += $productionData['fixedAssets'];
                }
            }

            // Достъпни са посочените в етапа папки
            $assetOptions = array();
            $assetsInFolder = planning_AssetResources::getByFolderId($masterRec->folderId, $masterRec->assetId, 'planning_Tasks', true);
            $allowedAssets = array_intersect_key($allowedAssets, $assetsInFolder);
            foreach ($allowedAssets as $assetId){
                $assetOptions[$assetId] = planning_AssetResources::getTitleById($assetId, false);
            }

            $form->setOptions('fixedAsset', $assetOptions);
            $form->setField('fixedAsset', 'input,mandatory');
            $form->setDefault('fixedAsset', $masterRec->assetId);
        } else {
            $form->setField('fixedAsset', 'input=none');
        }

        $options = planning_ProductionTaskProducts::getOptionsByType($rec->taskId, $rec->type);
        if($rec->type == 'scrap'){
            $optionField = 'serial';
            $form->setField('serial', 'removeAndRefreshForm=productId|quantity|scrapRecId');
            $form->setFieldType('serial', "enum("  . arr::fromArray($options) . ")");
            $form->setFieldTypeParams('serial', 'minimumResultsForSearch=0');
            $form->setDefault('serial', key($options));
        } else {
            $optionField = 'productId';
            $form->setOptions('productId', array('' => '') + $options);
        }

        $form->setDefault('date', Mode::get('taskProgressDate'));
        $form->setFieldTypeParams('date', array('defaultTime' => trans_Setup::get('START_WORK_TIME')));
        if(!empty($rec->date)){
            $today = dt::today();
            $checkDate = dt::verbal2mysql($rec->date, false);
            if($checkDate != $today){
                $dateMsg = ($checkDate < $today) ? tr('Датата е в миналото') : tr('Датата е в бъдещето');
                $form->info = "<div class='richtext-info-no-image'>{$dateMsg}!</div>";
            }
        }

        if ($rec->type == 'production') {
            if($masterRec->isFinal != 'yes'){
                $form->setDefault('productId', $masterRec->productId);
            } else {
                $form->setDefault('productId', planning_Jobs::fetchField("#containerId = {$masterRec->originId}", 'productId'));
            }
        }

        // Ако наличната опция е само една, по дефолт е избрана
        if (countR($options) == 1) {
            $form->setDefault($optionField, key($options));
            $form->setReadOnly('productId');
        }

        if($rec->type == 'scrap'){
            $scrapProductId = planning_ProductionTaskDetails::fetchField("#taskId = {$rec->taskId} AND #serial = '{$rec->serial}'", 'productId');
            $form->setOptions('productId', array($scrapProductId => cat_Products::getTitleById($scrapProductId, false)));
            $form->setDefault('productId', $scrapProductId);
            $form->setField('quantity', 'caption=Брак');
            $form->setField('weight', 'caption=Тегло');
            $availableScrap = static::getAvailableScrap($rec->serial, $rec->taskId);

            $defaultScrapQuantity = $availableScrap['quantity'];
            $defaultWeight = $availableScrap['weight'];
            if(isset($rec->scrapRecId)){
                $scrapRec = static::fetch($rec->scrapRecId);
                $measureRound = cat_UoM::fetchField($data->masterRec->measureId, 'round');
                $scrapRecQuantity = round($scrapRec->quantity / $data->masterRec->quantityInPack, $measureRound);
                if($scrapRecQuantity < $defaultScrapQuantity){
                    $defaultScrapQuantity = $scrapRecQuantity;
                    $defaultWeight = $scrapRec->weight;
                }
            }

            $form->setField('quantity', "placeholder={$defaultScrapQuantity}");
            $form->setField('weight', "placeholder={$defaultWeight}");
            $form->rec->_defaultScrapQuantity = $defaultScrapQuantity;
            $form->rec->_defaultScrapWeight = $defaultWeight;

            $form->setFieldTypeParams('quantity', array('max' => $availableScrap['quantity']));
            $form->setFieldTypeParams('weight', array('max' => $availableScrap['weight']));
        }

        // Ако е избран артикул
        if (isset($rec->productId)) {
            $pRec = cat_Products::fetch($rec->productId, 'measureId,canStore');
            if($rec->type == 'production' && $masterRec->labelType == 'scan'){
                $form->setField('serial', 'mandatory');
            } elseif($rec->type == 'input'){
                $availableSerialsToInput = static::getAvailableSerialsToInput($rec->productId, $rec->taskId);
                if(countR($availableSerialsToInput)){
                    $serialOptions = array_combine(array_keys($availableSerialsToInput), array_keys($availableSerialsToInput));
                    $form->setField('serial', 'removeAndRefreshForm=quantity|batch');
                    $form->setOptions('serial', $serialOptions);
                    $form->setDefault('serial', key($serialOptions));
                } else {
                    $form->setField('serial', 'input=none');
                }
            }

            if ($pRec->canStore != 'yes' && $rec->type == 'production' && $rec->productId == $masterRec->productId) {
                if ($rest = $masterRec->plannedQuantity - $masterRec->totalQuantity) {
                    if($rest > 0){
                        $form->setField('quantity', "placeholder={$rest}");
                        $form->rec->_defaultQuantity = $rest;
                    }
                }
            }

            if($pRec->canStore == 'no'){
                $form->setField('weight', 'input=none');
            }

            $productIsTaskProduct = planning_ProductionTaskProducts::isProduct4Task($masterRec, $rec->productId);
            $info = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type, $rec->fixedAsset);

            $shortMeasureId = ($productIsTaskProduct) ? $masterRec->measureId : $info->packagingId;
            $shortMeasure = cat_UoM::getShortName($shortMeasureId);
            $rec->_isKgMeasureId = ($shortMeasureId == cat_UoM::fetchBySinonim('kg')->id);

            $fieldName = 'quantity';
            if($rec->type == 'production' && isset($masterRec->labelPackagingId) && $masterRec->labelPackagingId != $masterRec->measureId && $productIsTaskProduct){

                $unit = $shortMeasure . ' / ' . cat_UoM::getShortName($masterRec->labelPackagingId);
                $form->setField($fieldName, "unit={$unit}");
                $defaultQuantity = $masterRec->labelQuantityInPack;
                if(!$defaultQuantity){
                    $defaultQuantity = planning_Tasks::getDefaultQuantityInLabelPackagingId($rec->productId, $masterRec->measureId, $masterRec->labelPackagingId);
                }

                $form->setField('quantity', "placeholder={$defaultQuantity}");
                if($rec->_isKgMeasureId){
                    $form->setField('quantity', "caption=Нето");
                    $form->setField('weight', "placeholder={$defaultQuantity}");
                }
                $form->rec->_defaultQuantity = $defaultQuantity;
            } else {
                $unitMeasureId = isset($info->packagingId) ? $info->packagingId : $info->measureId;
                $unit = cat_UoM::getShortName($unitMeasureId);
                $form->setField('quantity', "unit={$unit}");
                if($rec->_isKgMeasureId){
                    $form->setField('quantity', "caption=Нето");
                    $form->setField('weight', "unit={$unit}");
                }
            }
            $form->setField('date', "placeholder=" . dt::now());
        } else {
            if($rec->type == 'input'){
                $form->setField('serial', 'input=none');
            }
        }
        $employees = !empty($masterRec->employees) ? planning_Hr::getPersonsCodesArr(keylist::merge($masterRec->employees, $rec->employees)) : planning_Hr::getByFolderId($masterRec->folderId, $rec->employees);

        if (countR($employees)) {
            $form->setSuggestions('employees', array('' => '') + $employees);
            $form->setField('employees', 'input');
            $mandatoryOperatorsInTasks = planning_Centers::fetchField("#folderId = {$masterRec->folderId}", 'mandatoryOperatorsInTasks');
            $mandatoryOperatorsInTasks = ($mandatoryOperatorsInTasks == 'auto') ? planning_Setup::get('TASK_PROGRESS_MANDATORY_OPERATOR') : $mandatoryOperatorsInTasks;
            if($mandatoryOperatorsInTasks == 'yes'){
                $form->setField('employees', 'mandatory');
            }

            if(countR($employees) == 1){
                $form->setDefault('employees', keylist::addKey('', planning_Hr::getPersonIdByCode(key($employees))));
            }
        }

        // Показване на допълнителна мярка при нужда
        if($rec->type == 'production'){
            if ($masterRec->showadditionalUom == 'no') {
                $form->setField('weight', 'input=none');
            } elseif($masterRec->showadditionalUom == 'mandatory'){
                $form->setField('weight', 'mandatory');
            }
        } elseif($rec->type != 'scrap') {
            $form->setField('weight', 'input=none');
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;

        if ($form->isSubmitted()) {
            $masterRec = planning_Tasks::fetch($rec->taskId);
            if (empty($rec->serial) && empty($rec->productId) && !empty($masterRec->labelPackagingId)) {
                $form->setError('serial,productId', 'Трябва да е въведен артикул или сериен номер');
            }

            if(isset($rec->productId)){
                $productRec = cat_Products::fetch($rec->productId, 'canStore,generic');

                if(!empty($rec->serial)){
                    $rec->serial = plg_Search::normalizeText($rec->serial);
                    if(!empty($rec->serial)){
                        $checkProductId = ($rec->type == 'production') ? planning_Jobs::fetchField("#containerId = {$masterRec->originId}", 'productId') : $rec->productId;
                        $rec->serial = str::removeWhiteSpace($rec->serial);
                        if ($Driver = cat_Products::getDriver($checkProductId)) {
                            $rec->serial = $Driver->canonizeSerial($checkProductId, $rec->serial);
                        }

                        if(in_array($rec->type, array('production', 'scrap'))){
                            // Проверка на сериния номер
                            $serialInfo = self::getProductionSerialInfo($rec->serial, $rec->productId, $rec->taskId);
                            $rec->serialType = $serialInfo['type'];
                            if (isset($serialInfo['error'])) {
                                $form->setError('serial', $serialInfo['error']);
                            } elseif ($serialInfo['type'] == 'existing') {
                                if(!empty($rec->batch) && isset($serialInfo['batch']) && $rec->batch != $serialInfo['batch']){
                                    $form->setError('serial,batch', "Този номер е към друга партида");
                                }
                            }
                        } else {
                            $availableSerialsToInput = static::getAvailableSerialsToInput($rec->productId, $rec->taskId);
                            $serialInfo = $availableSerialsToInput[$rec->serial];
                            $form->setDefault('quantity', $serialInfo['quantity']);
                        }

                    } else {
                        $form->setError('serial', "Невалиден производствен номер");
                    }
                }

                // Ако артикулът е действие към оборудването
                if ($productRec->canStore != 'yes' && $rec->type == 'input') {
                    $inTp = planning_ProductionTaskProducts::fetchField("#taskId = {$rec->taskId} AND #type = 'input' AND #productId = {$rec->productId}");
                    // Подсигуряване, че трябва да има норма
                    if (empty($inTp)) {
                        if (!planning_AssetResources::getNormRec($rec->fixedAsset, $rec->productId)) {
                            $form->setError('productId,fixedAsset', 'Изберете оборудване, което има норма за действието');
                        }
                    }
                }

                if($productRec->generic == 'yes') {
                    $form->setError('productId', 'Избраният артикул е генеричен|*! |Трябва да бъде заместен|*!');
                }
            }

            if($masterRec->assetId != $rec->fixedAsset){
                $form->setWarning('fixedAsset', "Избраното оборудване е различно от посоченото в операцията! Наистина ли желаете да снените оборудването в операцията?");
            }

            if($rec->type == 'scrap'){
                if(!empty($rec->quantity) && !empty($rec->weight)){
                    $form->setError('weight,quantity', 'При бракуване трябва да е попълнено само едно от полетата');
                }
            }

            if (!$form->gotErrors()) {
                if($rec->type == 'scrap'){
                    if(empty($rec->quantity) && empty($rec->weight)){
                        $rec->quantity = $rec->_defaultScrapQuantity;
                        $rec->weight = $rec->_defaultScrapWeight;
                    } elseif(!empty($rec->quantity) && empty($rec->weight)){
                        if(isset($rec->_defaultScrapWeight)){
                            $singleWeight = $rec->_defaultScrapWeight / $rec->_defaultScrapQuantity;
                            $kgRound = cat_UoM::fetchBySinonim('kg')->round;
                            $rec->weight = round($rec->quantity * $singleWeight, $kgRound);
                        }
                    }elseif(!empty($rec->weight) && empty($rec->quantity)){
                        $singleWeight = $rec->_defaultScrapWeight / $rec->_defaultScrapQuantity;
                        $mRound = cat_UoM::fetchField($masterRec->measureId, 'round');
                        $rec->quantity = round($rec->weight / $singleWeight, $mRound);
                    }
                }

                if(isset($serialInfo)){
                    if(empty($rec->quantity) && !empty($serialInfo['quantity'])){
                        if(isset($rec->_defaultQuantity)){
                            $rec->quantity = min($serialInfo['quantity'], $rec->_defaultQuantity);
                        } else {
                            $rec->quantity = $serialInfo['quantity'];
                        }
                    }

                    if(empty($rec->batch)){
                        if($masterRec->followBatchesForFinalProduct == 'yes'){
                            $rec->batch = $serialInfo['batch'];
                        }
                    }
                }

                if($masterRec->followBatchesForFinalProduct == 'yes' && empty($rec->batch) && $rec->type == 'production'){
                    $form->setError('batch', "Посочете партида! В операцията е избрано да се отчита по партида");
                }

                // Опит за приспадане на параметър от стойността на теглото
                if(isset($rec->weight) && $rec->type == 'production'){
                    $weightMsg = $weightMsgType = null;
                    $rec->_subtractedWeight = static::subtractParamValueFromWeight($rec->taskId, $masterRec->originId, $rec->weight, $weightMsg, $weightMsgType);
                    if($weightMsgType == 'warning'){
                        $form->setWarning('weight', $weightMsg);
                    } elseif($weightMsgType == 'error'){
                        $form->setError('weight', $weightMsg);
                    }
                }

                if (!$form->gotErrors()) {
                    if(!empty($rec->otherEmployees)){
                        $rec->employees = keylist::merge($rec->employees, $rec->otherEmployees);
                    }

                    if(isset($rec->_subtractedWeight)){
                        $rec->weight = $rec->_subtractedWeight;
                    }

                    if($rec->_isKgMeasureId){
                        $rec->quantity = !empty($rec->quantity) ? $rec->quantity : ((!empty($rec->weight)) ? $rec->weight : ((!empty($rec->_defaultQuantity)) ? $rec->_defaultQuantity : 1));
                        $rec->weight = $rec->weight;
                    } else {
                        $rec->quantity = (!empty($rec->quantity)) ? $rec->quantity : ((!empty($rec->_defaultQuantity)) ? $rec->_defaultQuantity : 1);
                    }

                    if(in_array($rec->type, array('production', 'scrap')) && planning_ProductionTaskProducts::isProduct4Task($rec->taskId, $rec->productId) && isset($rec->quantity)){
                        $rec->quantity *= $masterRec->quantityInPack;
                    }

                    $limit = '';
                    if (isset($rec->productId) && !in_array($rec->type, array('production', 'scrap'))) {
                        if (!$mvc->checkLimit($rec, $limit)) {
                            $limit = core_Type::getByName('double(smartRound)')->toVerbal($limit);
                            $form->setError('quantity', "Надвишаване на допустимото максимално количество|* <b>{$limit}</b>");
                        }
                    }

                    $info = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type, $rec->fixedAsset);
                    if (isset($info->indTime)) {
                        $rec->norm = $info->indTime;
                    }

                    if($masterRec->assetId != $rec->fixedAsset){
                        $rec->newAssetId = $rec->fixedAsset;
                    }

                    // Ако има ръчно въведена дата в прогреса, записва се в сесията, иначе се трие от там
                    if(!empty($rec->date)){
                        Mode::setPermanent("taskProgressDate", $rec->date);
                    } else {
                        Mode::setPermanent("taskProgressDate", null);
                    }
                }
            }
        }
    }


    /**
     * Помощна ф-я изваждаща стойността на определен параметър от теглото
     *
     * @param int $taskId          - ид на ПО
     * @param int $originId        - ид на ориджина на ПО
     * @param double $weight       - тегло
     * @param string|null $msg     - съобщение за грешка/предупреждение
     * @param string|null $msgType - грешка или предупреждение или null ако няма
     * @return null|double         - приспаднатото тегло
     */
    public static function subtractParamValueFromWeight($taskId, $originId, $weight, &$msg = null, &$msgType = null)
    {
        if(is_null($weight)) return;

        $result = $weight;
        $tareWeightParamId = planning_Setup::get('TASK_WEIGHT_SUBTRACT_PARAM_VALUE');
        if(!$tareWeightParamId) return null;

        $taskClassId = planning_Tasks::getClassId();
        $taskWeightSubtractValue = cat_products_Params::fetchField("#paramId = {$tareWeightParamId} AND #classId = {$taskClassId} AND #productId = {$taskId}", 'paramValue');
        if(!isset($taskWeightSubtractValue)){
            $productId = planning_Jobs::fetchField("#containerId = {$originId}", 'productId');
            $taskWeightSubtractValue = cat_Products::getParams($productId, $tareWeightParamId);
        }

        $paramName = cat_Params::getVerbal($tareWeightParamId, 'typeExt');
        if(isset($taskWeightSubtractValue)){

            // Ако параметъра е формула, се прави опит за изчислението ѝ
            if(cat_Params::haveDriver($tareWeightParamId, 'cond_type_Formula')){
                Mode::push('text', 'plain');
                $taskWeightSubtractValue = cat_Params::toVerbal($tareWeightParamId, $taskClassId, $taskId, $taskWeightSubtractValue);
                Mode::pop('text');
                if ($taskWeightSubtractValue === cat_BomDetails::CALC_ERROR) {
                    $msg = "Не може да бъде изчислена и приспадната от теглото стойността на|* <b>{$paramName}</b>";
                    $msgType = 'warning';
                    return $result;
                }
            }
        }

        // Приспадане и проверка
        $round = cat_UoM::fetchBySysId('kg')->round;
        $result = $result - $taskWeightSubtractValue;
        $result = round($result, $round);
        if($result <= 0){
            $subtractTareWeightValVerbal = cls::get('cat_type_Weight')->toVerbal($taskWeightSubtractValue);
            $msg = "Получава се невалидно тегло, като се приспадне стойността от параметъра|* <b>{$paramName}</b> : {$subtractTareWeightValVerbal}";
            $msgType = 'error';
            $result = null;
        }

        return $result;
    }


    /**
     * Преди запис на документ
     *
     * @param core_Manager $mvc
     * @param $res
     * @param $rec
     * @return void
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        $serialProductId = $rec->productId;
        if($rec->type == 'production'){
            $originId = planning_Tasks::fetchField("#id = {$rec->taskId}", 'originId');
            $serialProductId = planning_Jobs::fetchField("#containerId = {$originId}", 'productId');
        }

        if (empty($rec->serial)) {
            if ($Driver = cat_Products::getDriver($serialProductId)) {

                // Генериране на сериен номер, ако може
                $serial = $Driver->generateSerial($serialProductId, 'planning_Tasks', $rec->taskId);
                if(isset($serial)){
                    $rec->serial = $serial;
                    $rec->serialType = 'generated';
                }
            }
        } else {
            if ($Driver = cat_Products::getDriver($serialProductId)) {
                $rec->serial = $Driver->canonizeSerial($serialProductId, $rec->serial);
            }
        }

        if (!empty($rec->serial)) {
            $rec->searchKeywords .= ' ' . plg_Search::normalizeText($rec->serial);
        }
    }


    /**
     * Връща вече произведените серийни номера по артикула за влагане
     *
     * @param int $productId
     * @param int $taskId
     * @return array $res
     */
    private static function getAvailableSerialsToInput($productId, $taskId)
    {
        $res = array();
        $canStore = cat_Products::fetchField($productId, 'canStore');
        if($canStore != 'yes') return $res;

        // Кои са наличните за предишните операции за етапа от операцията
        $taskRec = planning_Tasks::fetch($taskId, 'originId,productId,labelPackagingId,measureId');
        $previousTaskIds = planning_Steps::getPreviousStepTaskIds($taskRec->productId, $taskRec->originId);
        if(!countR($previousTaskIds)) return $res;

        // За всяка една от тях се сумират произведените к-ва по сериен номер от операции в това задание
        $query = static::getQuery();
        $query->in("taskId", $previousTaskIds);
        $query->where("#productId = {$productId} AND #type IN ('production', 'scrap') AND #state != 'rejected'");
        while($rec = $query->fetch()){
            if(!array_key_exists($rec->serial, $res)){
                $res[$rec->serial] = array('serial' => $rec->serial, 'productId' => $rec->productId, 'batch' => $rec->batch, 'type' => 'existing');
            }
            $sign = ($rec->type == 'scrap') ? -1 : 1;
            $res[$rec->serial]['quantity'] += $sign * $rec->quantity;
        }

        return $res;
    }


    /**
     * Информация за серийния номер
     *
     * @param string      $serial
     * @param int         $productId
     * @param int         $taskId
     *
     * @return array $res
     */
    private static function getProductionSerialInfo($serial, $productId, $taskId)
    {
        $taskRec = planning_Tasks::fetch($taskId, 'originId,productId,labelPackagingId,measureId');
        $res = array('serial' => $serial, 'productId' => $productId, 'type' => 'unknown');

        // Търси се в другите ПО от това задание дали вече се използва този сериен номер
        // със същата опаковка за етикетиране
        $foundFromOtherTask = null;
        $foundRecs = array();
        $query = static::getQuery();
        $query->EXT('originId', 'planning_Tasks', "externalName=originId,externalKey=taskId");
        $query->EXT('measureId', 'planning_Tasks', "externalName=measureId,externalKey=taskId");
        $query->EXT('labelPackagingId', 'planning_Tasks', "externalName=labelPackagingId,externalKey=taskId");
        $labelPackagingValue = isset($taskRec->labelPackagingId) ? $taskRec->labelPackagingId : $taskRec->measureId;
        $query->where("#originId = {$taskRec->originId} AND #taskId != {$taskRec->id}");
        $query->where("#labelPackagingId = {$labelPackagingValue} OR (#labelPackagingId IS NULL AND #measureId = {$labelPackagingValue})");

        // Сумира се реално произведеното по този проз. номер по операция
        $query->where(array("#serial = '[#1#]' AND #type IN ('production', 'scrap') AND #state != 'rejected'", $serial));

        while($rec = $query->fetch()){
            if(!array_key_exists($rec->taskId, $foundRecs)){
                $foundRecs[$rec->taskId] = (object)array('serial' => $rec->serial, 'productId' => $rec->productId, 'batch' => $rec->batch, 'type' => 'existing');
            }
            $sign = ($rec->type == 'scrap') ? -1 : 1;
            $foundRecs[$rec->taskId]->quantity += $sign * $rec->quantity;
            $date = isset($rec->date) ? $rec->date : $rec->createdOn;
            $foundRecs[$rec->taskId]->date = max($foundRecs[$rec->taskId]->date , $date);
        }

        // Връща се информацията от операцията с най-малко произведено к-во
        if(countR($foundRecs)){
            arr::sortObjects($foundRecs, 'date', 'DESC');
            $firstFound = (array)$foundRecs[key($foundRecs)];
            $foundFromOtherTask = $firstFound;
        }

        // Изчисляване сумарно по този произ. номер в текущата операция
        $cQuery = static::getQuery();
        $cQuery->where(array("#taskId = {$taskRec->id} AND #serial = '[#1#]' AND #type IN ('production', 'scrap') AND #state != 'rejected'", $serial));
        while($cRec = $cQuery->fetch()){
            $sign = ($cRec->type == 'scrap') ? -1 : 1;
            $res['totalQuantity'] += $sign * $cRec->quantity;
            $res['batch'] = $cRec->batch;
            $res['type'] = 'existing';
        }

        // Ако номера е от предходна ПО
        if(isset($foundFromOtherTask)){
            if(!empty($res['totalQuantity'])){

                // то ще се предложи за к-во остатъка от намереното к-во от там и прозиведеното досега
                $left = $foundFromOtherTask['quantity'] - $res['totalQuantity'];
                if($left > 0){
                    $foundFromOtherTask['quantity'] = $left;
                } else {
                    unset($foundFromOtherTask['quantity']);
                }
            }
            $res = $foundFromOtherTask;

            return $res;
        }

        // Ако номера е от текущата ПО и не се среща в друга приемаме го такъв какъвто е
        if(!empty($res['totalQuantity'])) return $res;

        // Ако номера не е наличен в прогрес на ПО
        $Driver = cat_Products::getDriver($productId);
        if(!$Driver) return $res;

        // Проверка дали серийния номер е за този артикул
        $pRec = $Driver->getRecBySerial($serial);

        // Ако не е намерен артикул търси се в етикет от опаковка
        $serialProductId = is_object($pRec) ? $pRec->id : null;
        if(empty($serialProductId)){
            if($serialPrintId = label_CounterItems::fetchField(array("#number = '[#1#]'", $serial), 'printId')){
                $printRec = label_Prints::fetch($serialPrintId, 'objectId,classId');
                if($printRec->classId == cat_products_Packagings::getClassId()){
                    $serialProductId = cls::get($printRec->classId)->fetchField($printRec->objectId, 'productId');
                }
            }
        }

        if (isset($serialProductId)) {
            $res['type'] = 'existing';
            $res['productId'] = $serialProductId;
        }

        $error = '';
        $jobProductId = planning_Jobs::fetchField("#containerId = {$taskRec->originId}", 'productId');
        if ($res['productId'] != $productId && $res['productId'] != $jobProductId) {
            $res['error'] = 'Производственият номер е към друг артикул|*: <b>' . cat_Products::getHyperlink($res['productId'], true) . '</b>';
        } elseif (!$Driver->checkSerial($productId, $serial, $error)) {
            $res['error'] = $error;
        }

        return $res;
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $taskRec = planning_Tasks::fetch($rec->taskId);

        $row->taskId = planning_Tasks::getLink($rec->taskId, 0);
        $date = !empty($rec->date) ? $rec->date : $rec->createdOn;
        $dateVerbal = $mvc->getFieldType('createdOn')->toVerbal($date);
        $createdOnVerbal = $mvc->getFieldType('createdOn')->toVerbal($rec->createdOn);
        $dateVerbal = !empty($rec->date) ? ht::createHint($dateVerbal, "Датата е ръчно въведена на|*: {$createdOnVerbal}", 'notice', false) : $dateVerbal;

        $row->date = "<div class='nowrap'>{$dateVerbal}";
        $row->date .= ' ' . tr('от||by') . ' ' . crm_Profiles::createLink($rec->createdBy) . '</div>';
        $row->ROW_ATTR['class'] = ($rec->state == 'rejected') ? 'state-rejected' : (($rec->type == 'input') ? 'row-added' : (($rec->type == 'production') ? 'state-active' : (($rec->type == 'scrap') ? 'state-hidden' : 'row-removed')));

        $pRec = cat_Products::fetch($rec->productId, 'measureId,code,isPublic,nameEn,name');
        $row->productId = cat_Products::getVerbal($rec->productId, 'name');
        $singleUrl = cat_Products::getSingleUrlArray($rec->productId);
        $row->productId = countR($singleUrl) ? ht::createLinkRef($row->productId, $singleUrl) : $row->productId;
        $foundRec = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type, $rec->fixedAsset);

        if($taskRec->productId != $foundRec->productId){
            $measureId = $foundRec->packagingId;
            $labelPackagingId = (!empty($foundRec->packagingId)) ? $foundRec->packagingId : $pRec->measureId;
        } else {
            $measureId = $foundRec->measureId;
            $labelPackagingId = (!empty($foundRec->labelPackagingId)) ? $foundRec->labelPackagingId : $foundRec->measureId;
        }

        if (isset($rec->employees)) {
            $row->employees = self::getVerbalEmployees($rec->employees);
        }

        // Показване на хинт към изчисленото време
        if(!empty($rec->employees) && $rec->state != 'rejected'){
            if(!empty($rec->norm)){
                $calcedNormHint = $mvc->calcNormByRec($rec, null, true);
                $row->employees = ht::createHint($row->employees, $calcedNormHint, 'notice', false);
            } else {
                $row->employees = ht::createHint($row->employees, 'Няма начислена заработка', 'warning', false);
            }
        }

        if(planning_ProductionTaskProducts::isProduct4Task($rec->taskId, $rec->productId)){
            $rec->quantity /= $taskRec->quantityInPack;
        }

        $row->measureId = cat_UoM::getShortName($measureId);
        $labelPackagingName = cat_UoM::getShortName($labelPackagingId);
        if ($measureId && cat_UoM::fetchField($measureId, 'type') != 'uom') {
            $row->measureId = str::getPlural($rec->quantity, $row->measureId, true);
        }

        if ($rec->type == 'production') {
            $row->type = (!empty($labelPackagingName) && ($labelPackagingId !== $measureId)) ? tr("Произв.|* {$labelPackagingName}") : tr('Произвеждане');
        }

        $rec->_groupedDate = dt::verbal2mysql($date, false);
        $row->_groupedDate = dt::mysql2verbal($rec->_groupedDate, 'd/m/y l');
        if(empty($taskRec->prevAssetId)){
            unset($row->fixedAsset);
        } else {
            $row->fixedAsset = planning_AssetResources::getShortName($rec->fixedAsset, !Mode::isReadOnly());
        }

        if($mvc->haveRightFor('add', (object)array('taskId' => $rec->taskId, 'type' => 'scrap', 'scrapRecId' => $rec->id))){
            core_RowToolbar::createIfNotExists($row->_rowTools);
            $row->_rowTools->addLink('Бракуване', array($mvc, 'add', 'taskId' => $rec->taskId, 'type' => 'scrap', 'scrapRecId' => $rec->id, 'ret_url' => true), 'title=Бракуване на прогреса,ef_icon=img/16/bin_closed.png');
        }
        if($mvc->haveRightFor('fix', $rec)){
            core_RowToolbar::createIfNotExists($row->_rowTools);
            $row->_rowTools->addLink('Тегло', array($mvc, 'fix', $rec->id,'ret_url' => true), 'title=Въвеждане на тегло,ef_icon=img/16/calculator.png');
        }
    }


    /**
     * Връща серийния номер като линк, ако е от друга операция
     *
     * @param int    $taskId - в коя операция ще се показва
     * @param string $serial - серийния номер
     *
     * @return core_ET|string $serialVerbal  - серийния номер като линк, или вербалното му представяне
     */
    public static function getLink($taskId, $serial)
    {
        $serialVerbal = core_Type::getByName('varchar(32)')->toVerbal($serial);
        if (Mode::isReadOnly()) return $serialVerbal;

        // Линк към прогреса филтриран по сериен номер
        if (planning_ProductionTaskDetails::haveRightFor('list')) {
            $serialVerbal = ht::createLink($serialVerbal, array('planning_ProductionTaskDetails', 'list', 'search' => $serialVerbal), false, 'title=Към историята на серийния номер');
        }

        return $serialVerbal;
    }


    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $data->isMeasureKg = ($data->masterData->rec->measureId == cat_UoM::fetchBySinonim('kg')->id);
        $lastRecId = null;

        if (isset($data->masterMvc)) {
            unset($data->listFields['notes']);
            $data->listTableMvc->FNC('shortUoM', 'varchar', 'tdClass=nowrap');
            $data->listTableMvc->setField('productId', 'tdClass=nowrap');
            $data->listTableMvc->FNC('info', 'varchar', 'tdClass=task-row-info');
            $data->listTableMvc->FNC('created', 'varchar', 'smartCenter');
            $data->listTableMvc->setField('weight', 'smartCenter');
        }

        $rows = &$data->rows;
        if (!countR($rows)) return;

        $weightWarningPercent = ($data->masterData->rec->weightDeviationWarning) ? $data->masterData->rec->weightDeviationWarning : planning_Setup::get('TASK_WEIGHT_TOLERANCE_WARNING');
        $masterRec = $data->masterData->rec;

        $recsBySerials = array();
        $checkSerials4Warning = planning_Setup::get('WARNING_DUPLICATE_TASK_PROGRESS_SERIALS');
        array_walk($data->recs, function($a) use (&$recsBySerials){if($a->type != 'scrap'){if(!array_key_exists($a->serial, $recsBySerials)){$recsBySerials[$a->serial] = 0;}$recsBySerials[$a->serial] += 1;}});

        foreach ($rows as $id => $row) {
            $rec = $data->recs[$id];
            if($data->isMeasureKg && ($masterRec->productId == $rec->productId)){
                if($rec->quantity == $rec->weight){
                    unset($row->quantity);
                }
                $row->weight = "<b>{$row->weight}</b> {$row->measureId}";
            } else {
                $row->quantity = "<b>{$row->quantity}</b> {$row->measureId}";
            }

            if($id == $lastRecId){
                $row->ROW_ATTR['class'] .= ' lastRow';
            }
            
            if (!empty($row->shortUoM)) {
                $row->quantity = "<b>{$row->quantity}</b>";
            }
            
            // Проверка има ли отклонение спрямо очакваното транспортно тегло
            if(!empty($rec->weight)){
                $weightQuantity = $rec->quantity;
                if($rec->type == 'production'){
                    $weightQuantity = $rec->quantity * $masterRec->quantityInPack;
                }
                $transportWeight = cat_Products::getTransportWeight($rec->productId, $weightQuantity);
                
                if(!empty($transportWeight)){
                    $deviation = abs(round(($transportWeight - $rec->weight) / (($transportWeight + $rec->weight) / 2), 2));
                    $expectedWeightVerbal = core_Type::getByName('double(smartRound)')->toVerbal($transportWeight);
                    
                    // Показване на предупреждение или нотификация, ако има разминаване в теглото
                    if($deviation > $weightWarningPercent){
                        $row->weight = ht::createHint($row->weight, "Значително разминаване спрямо очакваното транспортно тегло от|* {$expectedWeightVerbal} |кг|*", 'warning', false);
                    } elseif(!empty($masterRec->weightDeviationNotice) && $deviation > $masterRec->weightDeviationNotice){
                        $row->weight = ht::createHint($row->weight, "Разминаване спрямо очакваното транспортно тегло от|* {$expectedWeightVerbal} |кг|*", 'notice', false);
                    }
                }
                
                // Ако има избрано отклонение спрямо средното тегло
                if($masterRec->weightDeviationAverageWarning && $rec->state != 'rejected'){
                    
                    // Колко е средното тегло досега
                    if($average = self::getAverageWeight($rec->taskId, $rec->productId)){
                        $singleWeight = $rec->weight / $rec->quantity;
                        $deviation = abs(round(($average - $singleWeight) / (($average + $singleWeight) / 2), 2));
                        
                        // Има ли разминаване спрямо средното тегло
                        if($deviation > $masterRec->weightDeviationAverageWarning){
                            $expectedWeightVerbal = core_Type::getByName('double(smartRound)')->toVerbal($average * $rec->quantity);
                            $row->weight = ht::createHint($row->weight, "Разминаване спрямо средното транспортно тегло в операцията от|* {$expectedWeightVerbal} |кг|*", 'error', false);
                        }
                    }
                }
            }
            
            if (isset($data->masterMvc)) {
                if(($rec->type != 'production' && $rec->type != 'scrap') || !planning_ProductionTaskProducts::isProduct4Task($masterRec->id, $rec->productId)){
                    $row->info = "{$row->productId}";
                }
            }

            if(!empty($rec->notes)){
                $notes = $mvc->getFieldType('notes')->toVerbal($rec->notes);
                $row->type = ht::createHint($row->type, $notes, 'img/16/comment.png');
            }
            
            if(!empty($rec->serial) && $rec->state != 'rejected'){
                $row->serial = self::getLink($rec->taskId, $rec->serial);
            }

            if($checkSerials4Warning == 'yes' && $rec->type != 'scrap'){
                if($recsBySerials[$rec->serial] > 1){
                    $row->serial = ht::createHint($row->serial, 'Номера се повтаря в операцията|*!', 'warning');
                }
            }
        }
    }
    
    
    /**
     * Показва вербалното име на операторите
     *
     * @param string $employees - кейлист от оператори
     * @return string $verbalEmployees
     */
    public static function getVerbalEmployees($employees)
    {
        $verbalEmployees = array();
        $employees = keylist::toArray($employees);
        foreach ($employees as $eId) {
            $el = planning_Hr::getCodeLink($eId);
            $verbalEmployees[$eId] = $el;
        }
        
        return implode(', ', $verbalEmployees);
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        // Ъпдейт на общото к-во в детайла
        planning_ProductionTaskProducts::updateTotalQuantity($rec->taskId, $rec->productId, $rec->type);

        if(isset($rec->newAssetId)){
            Mode::setPermanent("newAsset{$rec->taskId}", $rec->newAssetId);
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Документът не може да се създава в нова нишка, ако е въз основа на друг
        if (!empty($data->toolbar->buttons['btnAdd'])) {
            $data->toolbar->removeBtn('btnAdd');
            $masterRec = $data->masterData->rec;
            if ($mvc->haveRightFor('add', (object) array('taskId' => $data->masterId, 'type' => 'production'))) {
                $btnName = (empty($masterRec->labelPackagingId) || $masterRec->labelPackagingId == $masterRec->measureId) ? 'Прогрес' : "Прогрес|* " . tr(cat_UoM::getTitleById(($masterRec->labelPackagingId)));
                $data->toolbar->addBtn($btnName, array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'production', 'ret_url' => true), false, 'ef_icon = img/16/package.png,title=Добавяне на прогрес по операцията');
            }
            
            if ($mvc->haveRightFor('add', (object) array('taskId' => $data->masterId, 'type' => 'input'))) {
                $data->toolbar->addBtn('Влагане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'input', 'ret_url' => true), false, 'ef_icon = img/16/wooden-box.png,title=Добавяне на вложен артикул');
            }
            
            if ($mvc->haveRightFor('add', (object) array('taskId' => $data->masterId, 'type' => 'waste'))) {
                $data->toolbar->addBtn('Отпадък', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'waste', 'ret_url' => true), false, 'ef_icon = img/16/recycle.png,title=Добавяне на отпаден артикул');
            }

            if ($mvc->haveRightFor('add', (object) array('taskId' => $data->masterId, 'type' => 'scrap'))) {
                $data->toolbar->addBtn('Бракуване', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'scrap', 'ret_url' => true), false, 'ef_icon = img/16/bin_closed.png,title=Бракуване на прогрес по операцията');
            }
        }
    }
    
    
    /**
     * Подготовка на детайла
     */
    public function prepareDetail_($data)
    {
        $data->TabCaption = 'Прогрес';
        $data->Tab = 'top';
        parent::prepareDetail_($data);
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('createdOn', 'DESC');
        if(Mode::is('getLinkedObj') || Mode::is('inlineDocument')) {
            
            return ;
        }

        $data->listFilter->showFields .= 'search';
        $data->listFilter->setField('type', 'input=none');
        $data->listFilter->class = 'simpleForm';
        if (isset($data->masterMvc)) {
            $data->listFilter->showFields .= ",threadId";
            $data->showRejectedRows = true;
            $data->listFilter->FLD('threadId', 'int', 'silent,input=hidden');
            $data->listFilter->view = 'horizontal';
            $data->listFilter->input('threadId', 'silent');

            unset($data->listFields['taskId']);
            unset($data->listFields['createdOn']);
            unset($data->listFields['createdBy']);
            unset($data->listFields['productId']);
            unset($data->listFields['taskId']);
            $data->groupByField = '_createdDate';
        } else {
            unset($data->listFields['_createdDate']);

            $assetInTasks = planning_AssetResources::getUsedAssetsInTasks();
            if(countR($assetInTasks)){
                $data->listFilter->setOptions('fixedAsset', array('' => '') + $assetInTasks);
                $data->listFilter->showFields .= ",fixedAsset";
            }

            $employees = planning_Hr::getByFolderId();
            if(countR($employees)){
                $data->listFilter->setSuggestions('employees', array('' => '') + $employees);
                $data->listFilter->showFields .= ",employees";
            }
        }

        $caption = isset($data->masterMvc) ? '' : 'Филтрирай';
        $data->listFilter->toolbar->addSbBtn($caption, 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        
        // Филтър по избраните стойности
        if ($filter = $data->listFilter->rec) {
            if (!empty($filter->fixedAsset)) {
                $data->query->where("#fixedAsset = '{$filter->fixedAsset}'");
            }
            if (!empty($filter->employees)) {
                $data->query->likeKeylist("employees", $filter->employees);
            }
            
            if (!empty($filter->serial)) {
                $data->query->like('serial', $filter->serial);
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (in_array($action, array('add', 'edit', 'delete', 'reject', 'fix')) && isset($rec->taskId)) {
            $masterRec = $mvc->Master->fetch($rec->taskId, 'timeClosed,state');
            if(in_array($masterRec->state, array('rejected', 'draft', 'waiting'))){
                $requiredRoles = 'no_one';
            } elseif($masterRec->state == 'closed'){
                $howLong = dt::addSecs(planning_Setup::get('TASK_PROGRESS_ALLOWED_AFTER_CLOSURE'), $masterRec->timeClosed);
                if(dt::now() >= $howLong){
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        // Трябва да има поне един артикул възможен за добавяне
        if ($action == 'add' && isset($rec->type)) {
            if ($requiredRoles != 'no_one') {
                $pOptions = planning_ProductionTaskProducts::getOptionsByType($rec->taskId, $rec->type);
                if (!countR($pOptions)) {
                    $requiredRoles = 'no_one';
                }

                if($rec->type == 'scrap' && isset($rec->scrapRecId)){
                    $exRec = static::fetch("#id = {$rec->scrapRecId}", 'type,state,taskId');
                    if($exRec->state == 'rejected' || $exRec->type != 'production' || $exRec->taskId != $rec->taskId){
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
        
        if($action == 'printperipherallabel' && isset($rec)){
            if($rec->type != 'production' || $rec->state == 'rejected'){
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'fix' && isset($rec)){
            if($rec->state == 'rejected' || $rec->type != 'production'){
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Преди подготовка на заглавието на формата
     */
    protected static function on_BeforePrepareEditTitle($mvc, &$res, $data)
    {
        $rec = &$data->form->rec;
        $titleArr = array('production' => 'прогрес', 'input' => 'влагане', 'waste' => 'отпадък', 'scrap' => 'брак');
        $data->singleTitle = $titleArr[$rec->type];
    }


    /**
     * Връща изчислената норма, спрямо количеството
     *
     * @param stdClass $rec          - запис
     * @param stdClass|null $taskRec - запис на операция или null ако ще се извлича на момента
     * @param boolean $verbal        - дали да е вербално или не
     * @return string                - изчислената норма в секунди
     */
    public static function calcNormByRec($rec, $taskRec = null, $verbal = false)
    {
        $quantity = $rec->quantity;

        if(in_array($rec->type, array('production', 'scrap'))) {
            $taskRec = is_object($taskRec) ? $taskRec : planning_Tasks::fetch($rec->taskId, 'originId,isFinal,productId,measureId,indPackagingId,labelPackagingId,indTimeAllocation,quantityInPack,labelQuantityInPack');
            $jobProductId = planning_Jobs::fetchField("#containerId = {$taskRec->originId}", 'productId');

            // Ако артикула е артикула от заданието и операцията е финална или артикула е този от операцията за междинен етап
            if(($taskRec->isFinal == 'yes' && $rec->productId == $jobProductId) || $rec->productId == $taskRec->productId){

                if(cat_UoM::fetchField($taskRec->measureId, 'type') == 'uom'){
                    if($taskRec->indPackagingId == $taskRec->measureId){
                        $quantity /= $taskRec->quantityInPack;
                    }
                }

                if($taskRec->measureId != $taskRec->indPackagingId){
                    if(!empty($taskRec->labelQuantityInPack)){
                        $quantity = ($quantity / $taskRec->labelQuantityInPack);
                    } elseif ($indQuantityInPack = cat_products_Packagings::getPack($rec->productId, $taskRec->indPackagingId, 'quantity')) {
                        $quantity = ($quantity / $indQuantityInPack);
                    }
                }
            }
        }

        $normFormQuantity = planning_type_ProductionRate::getInSecsByQuantity($rec->norm, $quantity);
        $normFormQuantity = round($normFormQuantity);
        if($verbal) {
            $normFormQuantityVerbal = ($normFormQuantity > 60) ? round($normFormQuantity / 60, 2) . " min" : $normFormQuantity . " s";
            $normFormQuantity = "|Заработка|*: {$normFormQuantityVerbal}";
            if(haveRole('debug')){
                $quantity = round($quantity, 5);
                $normFormQuantity .= " [N:{$rec->norm}; Q:{$quantity}]";
            }
        }

        return $normFormQuantity;
    }


    /**
     * Метод за вземане на резултатност на хората. За определена дата се изчислява
     * успеваемостта на човека спрямо ресурса, които е използвал
     *
     * @param datetime $timeline - Времето, след което да се вземат всички модифицирани/създадени записи
     *
     * @return array $result  - масив с обекти
     *
     * 			o date        - дата на стайноста
     * 		    o personId    - ид на лицето
     *          o docId       - ид на документа
     *          o docClass    - клас ид на документа
     *          o indicatorId - ид на индикатора
     *          o value       - стойноста на индикатора
     *          o isRejected  - оттеглена или не. Ако е оттеглена се изтрива от индикаторите
     */
    public static function getIndicatorValues($timeline)
    {
        $result = array();
        $query = self::getQuery();
        $query->EXT('productMeasureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
        $query->EXT('taskMeasureId', 'planning_Tasks', 'externalName=measureId,externalKey=taskId');
        $query->EXT('indPackagingId', 'planning_Tasks', 'externalName=indPackagingId,externalKey=taskId');
        $query->EXT('labelPackagingId', 'planning_Tasks', 'externalName=labelPackagingId,externalKey=taskId');
        $query->EXT('taskProductId', 'planning_Tasks', 'externalName=productId,externalKey=taskId');
        $query->EXT('indTimeAllocation', 'planning_Tasks', 'externalName=indTimeAllocation,externalKey=taskId');
        $query->EXT('taskQuantityInPack', 'planning_Tasks', 'externalName=quantityInPack,externalKey=taskId');
        $query->EXT('isFinal', 'planning_Tasks', 'externalName=isFinal,externalKey=taskId');
        $query->EXT('originId', 'planning_Tasks', 'externalName=originId,externalKey=taskId');
        $query->EXT('taskModifiedOn', 'planning_Tasks', 'externalName=modifiedOn,externalKey=taskId');
        $query->where("#taskModifiedOn >= '{$timeline}' AND #norm IS NOT NULL AND #employees IS NOT NULL");

        $iRec = hr_IndicatorNames::force('Време', __CLASS__, 1);
        $classId = planning_Tasks::getClassId();
        $indicatorId = $iRec->id;

        while ($rec = $query->fetch()) {

            // Ако няма оператори, пропуска се
            $persons = keylist::toArray($rec->employees);
            if (!countR($persons)) continue;

            $taskRec = new stdClass();
            $arr = arr::make("taskId=id,taskMeasureId=measureId,indTimeAllocation=indTimeAllocation,indPackagingId=indPackagingId,labelPackagingId=labelPackagingId,taskProductId=productId,isFinal=isFinal,originId=originId,taskQuantityInPack=quantityInPack,labelQuantityInPack=labelQuantityInPack", true);
            foreach ($arr as $fldAlias => $fld){
                $taskRec->{$fld} = $rec->{$fldAlias};
            }

            $normFormQuantity = static::calcNormByRec($rec, $taskRec);
            $timePerson = ($rec->indTimeAllocation == 'individual') ? $normFormQuantity : ($normFormQuantity / countR($persons));
            $sign = ($rec->type != 'scrap') ? 1 : -1;

            $date = !empty($rec->date) ? $rec->date : $rec->createdOn;
            $date = dt::verbal2mysql($date, false);
            foreach ($persons as $personId) {
                $key = "{$personId}|{$classId}|{$rec->taskId}|{$rec->state}|{$date}|{$indicatorId}";
                if (!array_key_exists($key, $result)) {
                    $result[$key] = (object) array('date'        => $date,
                                                   'personId'    => $personId,
                                                   'docId'       => $rec->taskId,
                                                   'docClass'    => $classId,
                                                   'indicatorId' => $indicatorId,
                                                   'value'       => 0,
                                                   'isRejected'  => ($rec->state == 'rejected'));
                }
                
                $result[$key]->value += $sign * $timePerson;
            }
        }
        
        return $result;
    }
    
    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     *
     * @return array $result
     */
    public static function getIndicatorNames()
    {
        $result = array();
        $rec = hr_IndicatorNames::force('Време', __CLASS__, 1);
        $result[$rec->id] = $rec->name;
        
        return $result;
    }
    
    
    /**
     * Проверка дали лимита е надвишен
     *
     * @param stdClass $rec
     * @param float    $limit
     *
     * @return bool
     */
    private function checkLimit($rec, &$limit = null)
    {
        $info = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type, $rec->fixedAsset);
        if (empty($info->limit)) {
            
            return true;
        }
        
        $query = self::getQuery();
        $query->XPR('sum', 'double', 'SUM(#quantity)');
        $query->where("#taskId = {$rec->taskId} AND #productId = {$rec->productId} AND #fixedAsset = '{$rec->fixedAsset}' AND #id != '{$rec->id}' AND #state = 'active'");
        $query->show('sum');
        $sum = $query->fetch()->sum;
        $sum += $rec->quantity;
        
        if ($sum > $info->limit) {
            $limit = $info->limit;
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Добавяне на прогрес към ПО
     * 
     * @param int $taskId
     * @param array $params
     * @return stdClass $rec
     */
    public static function add($taskId, $params)
    {
        //@todo да го преработя за брака да може да се добавя
        expect($taskRec = planning_Tasks::fetch($taskId), 'Няма така задача');
        expect(in_array($params['type'], array('production', 'input', 'waste', 'scrap')));
        $productId = (isset($params['productId'])) ? $params['productId'] : (($params['type'] == 'production') ? $taskRec->productId : null);
        expect($productId, 'Не е посочен артикул');
        $options = planning_ProductionTaskProducts::getOptionsByType($taskRec->id, $params['type']);
        expect(array_key_exists($productId, $options), $options);

        $quantity = ($params['quantity']) ? $params['quantity'] : 1;
        if(!empty($quantity)){
            $quantity *= $taskRec->quantityInPack;
            expect($quantity = core_Type::getByName('double')->fromVerbal($quantity), 'Невалидно число');
        } elseif($params['type'] == 'production' && isset($taskRec->labelPackagingId)){
            $packRec = cat_products_Packagings::getPack($taskRec->productId, $taskRec->packagingId);
            $quantity = is_object($packRec) ? ($packRec->quantity / $taskRec->quantityInPack) : 1;
        }
        
        expect($quantity > 0, 'Количеството трябва да е положително');
        $rec = (object)array('serialType' => 'unknown', '_generateSerial' => false, 'productId' => $productId, 'taskId' => $taskId, 'quantity' => $quantity, 'type' => $params['type']);
        if(!empty($params['employees'])){
            $params['employees'] = arr::make($params['employees']);
            $rec->employees = keylist::fromArray(array_combine($params['employees'], $params['employees']));
        }

        if(!empty($params['date'])){
            if(strlen($params['date']) == 10){
                $params['date'] .= " " . trans_Setup::get('START_WORK_TIME') . ":00";
            }

            expect($date = dt::verbal2mysql($params['date']), 'Невалидна дата');
            $rec->date = $date;
        }

        $rec->fixedAsset = (!empty($params['fixedAsset'])) ? $params['fixedAsset'] : null;
        if(!empty($params['weight'])){
            expect($params['weight'] = core_Type::getByName('double')->fromVerbal($params['weight']), 'Невалидно число');
            expect($params['weight'] > 0, 'Теглото трябва да е положително');
            $rec->weight = $params['weight'];
        }
        
        if(!empty($taskRec->assetId)){
            expect(!empty($rec->fixedAsset), 'Задължително трябва да е избрано оборудване');
        }
        
        if($taskRec->showadditionalUom == 'mandatory' && $rec->type == 'production' && $rec->productId == $taskRec->productId){
            expect($rec->weight, 'Теглото е задължително');
        }

        $canStore = cat_Products::fetchField($productId, 'canStore');
        if(!empty($params['serial'])){
            expect(str::containOnlyDigits($params['serial']), 'Серийният номер може да е само от цифри');
            $params['serial'] = plg_Search::normalizeText($params['serial']);
            $params['serial'] = str::removeWhiteSpace($params['serial']);
            if ($Driver = cat_Products::getDriver($productId)) {
                $params['serial'] = $Driver->canonizeSerial($productId, $params['serial']);
            }

            //@todo
            //$serialInfo = self::fetchSerialInfo($params['serial'], $rec->taskId, $rec->type);

            //$rec->serial = $params['serial'];
            //$rec->serialType = $serialInfo['type'];
        }

        if($taskRec->labelType == 'scan' || $rec->type == 'input'){
            expect($params['serial'], 'Трябва да е сканиран сериен номер|*!');
        }
        
        if($rec->type == 'input' && $canStore != 'yes') {
            $inTp = planning_ProductionTaskProducts::fetchField("#taskId = {$rec->taskId} AND #type = 'input' AND #productId = {$rec->productId}");
            // Подсигуряване, че трябва да има норма
            if (empty($inTp)) {
                expect(planning_AssetResources::getNormRec($rec->fixedAsset, $rec->productId), 'Изберете оборудване, което има норма за действието');
            }
        }
        
        $info = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type, $rec->fixedAsset);
        if (isset($info->indTime)) {
            $rec->norm = $info->indTime;
        }

        cls::get(get_called_class())->save($rec);
        
        return $rec;
    }
    
    
    /**
     * Колко е единичното средно тегло на артикула от операцията
     * 
     * @param int $taskId
     * @param int $productId
     * @return double $average
     */
    public static function getAverageWeight($taskId, $productId)
    {
        $arr = array();
        $query = self::getQuery();
        $query->where("#taskId = {$taskId} AND #productId = {$productId} AND #type = 'production' AND #state != 'rejected'");
        while ($fRec = $query->fetch()){
            $weight = $fRec->weight / $fRec->quantity;
            $arr[] = max(array($weight / 10, 1));
        }
        sort($arr);
        unset($arr[countR($arr) - 1]);
        unset($arr[0]);
        $sum = array_sum($arr);
        $average = round($sum / countR($arr), 4);
       
        return $average;
    }


    /**
     * Екшън за поправка на ред
     */
    public function act_Fix()
    {
        $this->requireRightFor('fix');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('fix', $rec);

        $form = cls::get('core_Form');
        $row = $this->recToVerbal($rec);
        $infoTpl = new core_ET(tr("|*<div class='richtext-info-no-image'>|Артикул|*: [#productId#]<br>|Произв. №|*: [#serial#]<br><!--ET_BEGIN employees-->|Оператори|*: [#employees#]<!--ET_END employees--><br>[#date#]</div>"));
        $infoTpl->placeObject($row);
        $form->info = $infoTpl;

        // Подготовка на формата
        $docTitle = planning_Tasks::getHyperlink($rec->taskId, true);
        $title = "Въвеждане на тегло";
        $form->title = "{$title}|* <b style='color:#ffffcc;'>{$docTitle}</b>";
        $form->setDefault('weight', $rec->weight);
        $form->FLD('weight', "double(Min=0)", "caption=Тегло,unit=кг");
        $form->input();

        // Запис на бракуваното количество
        if($form->isSubmitted()){
            $rec->weight = $form->rec->weight;
            $logMsg = "Промяна на тегло";
            $statusMsg = 'Теглото е променено успешно|*!';
            $this->save_($rec, 'weight');
            planning_Tasks::logWrite($logMsg, $rec->taskId);
            followRetUrl(null, $statusMsg);
        }

        $form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/bin_closed.png, title = Бракуване на количество');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);

        return $tpl;
    }


    /**
     * Рекалкулиране на заработките на конкретната ПО
     *
     * @param int $taskId         - ид на операция
     * @param string|null $type   - тип на прогреса (null за всички)
     * @param int|null $productId - ид на артикул
     * @return void
     */
    public static function recalcIndTime($taskId, $type = null, $productId = null)
    {
        $toSave = array();
        $me = cls::get(get_called_class());

        // Филтриране на нужните редове
        $query = $me->getQuery();
        $query->where("#taskId = {$taskId}");
        if(isset($type)){
            $query->where("#type = '{$type}'");
        }
        if(isset($productId)){
            $query->where("#productId = {$productId}");
        }

        // За всеки ред се изчислява наново нормата му, ако е променена се обновява
        while($rec = $query->fetch()){
            $info = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type, $rec->fixedAsset);
            if (isset($info->indTime) && $rec->norm != $info->indTime) {
                $rec->norm = $info->indTime;
                $toSave[$rec->id] = $rec;
            }
        }

        if(countR($toSave)){
            $me->saveArray($toSave, 'id,norm');
        }
    }


    /**
     * Помощна функция изчисляваща колко е допустимия брак за този производствен номер
     *
     * @param string $serial
     * @param int $taskId
     * @return array $res
     */
    public static function getAvailableScrap($serial, $taskId)
    {
        $produced = $scrapped = $weightScrapped = $weightProduced = 0;
        $query = static::getQuery();
        $query->EXT('quantityInPack', 'planning_Tasks', 'externalName=quantityInPack,externalKey=taskId');
        $query->where(array("#taskId = {$taskId} AND #state != 'rejected' AND #serial = '[#1#]' AND #type IN ('production', 'scrap')", $serial));

        while($rec = $query->fetch()){
            $quantityInPack = 1;
            if(planning_ProductionTaskProducts::isProduct4Task($rec->taskId, $rec->productId)){
                $quantityInPack = $rec->quantityInPack;
            }

            if($rec->type == 'scrap'){
                $scrapped += $rec->quantity / $quantityInPack;
                if(isset($rec->weight)){
                    $weightScrapped += $rec->weight;
                }
            } else{
                $produced += $rec->quantity / $quantityInPack;
                if(isset($rec->weight)){
                    $weightProduced += $rec->weight;
                }
            }
        }

        $round = cat_UoM::fetchField(planning_Tasks::fetchField($taskId, 'measureId'), 'round');
        $res = array();
        $res['quantity'] = round($produced - $scrapped, $round);

        $roundKg = cat_UoM::fetchBySinonim('kg')->round;
        $res['weight'] = round($weightProduced - $weightScrapped, $roundKg);

        return $res;
    }


    /**
     * Създаване на шаблона за общия List-изглед
     */
    public function renderDetailLayout_($data)
    {
        $className = cls::getClassName($this);

        // Шаблон за листовия изглед
        $listLayout = new ET("
            <div class='clearfix21 {$className}'>
            	<div class='listTopContainer clearfix21'>[#ListFilter#]</div>
                [#ListToolbar#]
                [#ListPagerTop#]
                [#ListTable#]
                [#ListPagerBottom#]
            </div>
        ");

        return $listLayout;
    }
}
