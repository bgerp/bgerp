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
    public $hideListFieldsIfEmpty = 'serial,weight,employees,fixedAsset,quantity,scrappedQuantity,quantityExtended,typeExtended,additional,batch';


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
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'silent,caption=Артикул,removeAndRefreshForm=serial|quantity');
        $this->FLD('type', 'enum(input=Влагане,production=Произв.,waste=Отпадък)', 'input=hidden,silent,tdClass=small-field nowrap');
        $this->FLD('serial', 'varchar(32)', 'caption=Производ. №,focus,autocomplete=off,silent');
        $this->FLD('serialType', 'enum(existing=Съществуващ,generated=Генериран,printed=Отпечатан,unknown=Непознат)', 'caption=Тип на серийния номер,input=none');
        $this->FLD('quantity', 'double(Min=0)', 'caption=Количество');
        $this->FLD('scrappedQuantity', 'double(Min=0)', 'caption=Брак,input=none');
        $this->FLD('weight', 'double(Min=0)', 'caption=Тегло,unit=кг');
        $this->FLD('employees', 'keylist(mvc=crm_Persons,select=id,select2MinItems=20)', 'caption=Оператори,input=none');
        $this->FLD('fixedAsset', 'key(mvc=planning_AssetResources,select=id)', 'caption=Оборудване,input=none,tdClass=nowrap,smartCenter');
        $this->FLD('date', 'datetime', 'caption=Дата,remember');
        $this->FLD('notes', 'richtext(rows=2,bucket=Notes)', 'caption=Забележки');
        $this->FLD('state', 'enum(active=Активирано,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull');
        $this->FLD('norm', 'planning_type_ProductionRate', 'caption=Време,input=none');

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

        $productOptions = planning_ProductionTaskProducts::getOptionsByType($rec->taskId, $rec->type);
        $form->setOptions('productId', array('' => '') + $productOptions);
        if(!Mode::is('terminalProgressForm')){
            $form->setFieldTypeParams('date', array('defaultTime' => trans_Setup::get('START_WORK_TIME')));
        }

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
        if (countR($productOptions) == 1) {
            $form->setDefault('productId', key($productOptions));
            $form->setReadOnly('productId');
        }

        // Ако е избран артикул
        if (isset($rec->productId)) {
            $pRec = cat_Products::fetch($rec->productId, 'measureId,canStore');

            $labelType = 'print';
            if($rec->type == 'production'){
                $labelType = $masterRec->labelType;
            } elseif($rec->type == 'input' && $pRec->canStore == 'yes'){
                $labelType = 'scan';
            }

            if($labelType == 'print'){
                $form->setField('serial', 'input=none');
            } elseif($labelType == 'scan'){
                $form->setField('serial', 'mandatory');
            }

            if ($pRec->canStore != 'yes' && $rec->productId == $masterRec->productId) {
                if ($rest = $masterRec->plannedQuantity - $masterRec->totalQuantity) {
                    if($rest > 0){
                        $form->setDefault('quantity', $rest);
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

            if(!Mode::is('terminalProgressForm')){
                $form->setField('date', "placeholder=" . dt::now());
            }
        }

        $employees = !empty($masterRec->employees) ? planning_Hr::getPersonsCodesArr($masterRec->employees) : planning_Hr::getByFolderId($masterRec->folderId);
        if (countR($employees)) {
            $form->setSuggestions('employees', $employees);
            $form->setField('employees', 'input');
            $mandatoryOperatorsInTasks = planning_Centers::fetchField("#folderId = {$masterRec->folderId}", 'mandatoryOperatorsInTasks');
            $mandatoryOperatorsInTasks = ($mandatoryOperatorsInTasks == 'auto') ? planning_Setup::get('TASK_PROGRESS_MANDATORY_OPERATOR') : $mandatoryOperatorsInTasks;
            if($mandatoryOperatorsInTasks == 'yes'){
                $form->setField('employees', 'mandatory');
            }

            if(countR($employees) == 1){
                if(!Mode::is('terminalProgressForm')){
                    $form->setDefault('employees', keylist::addKey('', key($employees)));
                }
            }
        }

        // Показване на допълнителна мярка при нужда
        if($rec->type == 'production'){
            if ($masterRec->showadditionalUom == 'no') {
                $form->setField('weight', 'input=none');
            } elseif($masterRec->showadditionalUom == 'mandatory'){
                $form->setField('weight', 'mandatory');
            }

            if(isset($rec->id)){
                $rec->quantity /= $masterRec->quantityInPack;
                foreach (array('serial', 'productId', 'quantity') as $fld){
                    $form->setReadOnly($fld);
                }
            }

        } else {
            $form->setField('weight', 'input=none');
        }

        if(Mode::is('terminalProgressForm')){
            $form->layout = $form->renderLayout();
            jquery_Jquery::run($form->layout, 'prepareKeyboard();');
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

            if($rec->type == 'input'){

                // При влагане ако няма артикул, прави се опит да се намери от произв. номер
                if(!isset($rec->productId) && !empty($rec->serial)){
                    if($pData = static::getProductBySerial($rec->serial)){
                        if(!planning_ProductionTaskProducts::fetchField("#taskId = '{$rec->taskId}' AND #type = 'input' AND #productId = '{$pData['productId']}'")){
                            $form->setError('serial', "Номера е на артикул, който не в допустим за влагане в тази операция|*: <b>" . cat_Products::getHyperlink($pData['productId'], true) . "</b>");
                            return;
                        }
                        $rec->productId = $pData['productId'];
                        if(empty($rec->quantity)){
                            $rec->quantity = $pData['quantity'];
                        }
                    }
                }

                if(!isset($rec->productId)){
                    $form->setError('productId,serial', "Трябва да е посочен артикул или производствен номер");
                    return;
                }
            }

            if(isset($rec->productId)){
                $productRec = cat_Products::fetch($rec->productId, 'canStore,generic');

                if(!empty($rec->serial)){
                    $rec->serial = plg_Search::normalizeText($rec->serial);
                    if(!empty($rec->serial)){
                        $rec->serial = str::removeWhiteSpace($rec->serial);
                        if ($Driver = cat_Products::getDriver($rec->productId)) {
                            $rec->serial = $Driver->canonizeSerial($rec->productId, $rec->serial);
                        }

                        // Проверка на сериния номер
                        $serialInfo = self::fetchSerialInfo($rec->serial, $rec->productId, $rec->taskId, $rec->type);
                        $rec->serialType = $serialInfo['type'];
                        if (isset($serialInfo['error'])) {
                            $form->setError('serial', $serialInfo['error']);
                        } elseif ($serialInfo['type'] == 'existing') {
                            if(!empty($rec->batch) && $rec->batch != $serialInfo['batch']){
                                $form->setError('serial,batch', "Този номер е към друга партида");
                            }
                        }
                    } else {
                        $form->setError('serial', "Невалиден производствен номер");
                    }

                    if ($exId = self::fetchField("#taskId = {$rec->taskId} AND #serial = '{$rec->serial}' AND #id != '{$rec->id}' AND #state != 'rejected'")) {
                        $form->setWarning('serial', 'Наистина ли искате да подмените съществуващия от преди запис|*?');
                        $form->rec->_rejectId = $exId;
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

            } elseif(empty($rec->serial)){
                $form->setError('productId,serial', 'Трябва да е избран артикул');
            }

            if($masterRec->assetId != $rec->fixedAsset){
                $form->setWarning('fixedAsset', "Избраното оборудване е различно от посоченото в операцията! Наистина ли желаете да снените оборудването в операцията?");
            }

            if (!$form->gotErrors()) {
                if(isset($serialInfo)){
                    if(empty($rec->quantity) && !empty($serialInfo['quantity'])){
                        $rec->quantity = $serialInfo['quantity'];
                    }

                    if(empty($rec->batch) && !empty($serialInfo['quantity'])){
                        if(isset($masterRec->storeId) && $masterRec->followBatchesForFinalProduct == 'yes'){
                            $rec->batch = $serialInfo['batch'];
                        }
                    }
                }

                if($rec->_isKgMeasureId){
                    $rec->quantity = !empty($rec->quantity) ? $rec->quantity : ((!empty($rec->weight)) ? $rec->weight : ((!empty($rec->_defaultQuantity)) ? $rec->_defaultQuantity : 1));
                    $rec->weight = $rec->weight;
                } else {
                    $rec->quantity = (!empty($rec->quantity)) ? $rec->quantity : ((!empty($rec->_defaultQuantity)) ? $rec->_defaultQuantity : 1);
                }

                if($rec->type == 'production' && planning_ProductionTaskProducts::isProduct4Task($rec->taskId, $rec->productId) && isset($rec->quantity)){
                    $rec->quantity *= $masterRec->quantityInPack;
                }

                $limit = '';
                if (isset($rec->productId) && $rec->type !== 'production') {
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
            }
        }
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
        if(isset($rec->_rejectId)){
            $exRec = self::fetch($rec->_rejectId);
            $exRec->state = 'rejected';
            $exRec->exState = 'active';
            $mvc->save_($exRec, 'state,modifiedOn,modifiedBy');
            planning_Tasks::logWrite('Оттегляне на детайл', $rec->taskId);
            core_Statuses::newStatus("Оттеглен е записа с номер|* <b>{$rec->serial}</b>");
        }

        if (empty($rec->serial)) {
            if ($Driver = cat_Products::getDriver($rec->productId)) {

                // Генериране на сериен номер, ако може
                $serial = $Driver->generateSerial($rec->productId, 'planning_Tasks', $rec->taskId);
                if(isset($serial)){
                    $rec->serial = $serial;
                    $rec->serialType = 'generated';
                }
            }
        } else {
            if ($Driver = cat_Products::getDriver($rec->productId)) {
                $rec->serial = $Driver->canonizeSerial($rec->productId, $rec->serial);
            }
        }

        if (!empty($rec->serial)) {
            $rec->searchKeywords .= ' ' . plg_Search::normalizeText($rec->serial);
        }
    }


    /**
     * Извлича кой е артикула по дадения сериен номер
     *
     * @param string $serial
     * @return array|null
     */
    private static function getProductBySerial($serial)
    {
        $res = array();
        if($exRec = self::fetch(array("#serial = '[#1#]'", $serial))){
            $res['quantity'] = $exRec->quantity;
            $res['productId'] = $exRec->productId;
        } else {
            if($serialPrintId = label_CounterItems::fetchField(array("#number = '[#1#]'", $serial), 'printId')){
                $printRec = label_Prints::fetch($serialPrintId, 'objectId,classId');
                if($printRec->classId == cat_products_Packagings::getClassId()){
                    $res['productId'] = $exRec->productId;
                }
            }
        }

        return countR($res) ? $res : null;

    }


    /**
     * Информация за серийния номер
     *
     * @param string      $serial
     * @param int         $productId
     * @param int         $taskId
     * @param string|null $type
     *
     * @return array $res
     */
    private static function fetchSerialInfo($serial, $productId, $taskId, $type = null)
    {
        if (!$Driver = cat_Products::getDriver($productId)) return;

        $res = array('serial' => $serial, 'productId' => $productId, 'type' => 'unknown');
        $canonizedSerial = $Driver->canonizeSerial($productId, $serial);
        $exRec = self::fetch(array("#serial = '[#1#]'", $canonizedSerial));

        if (!empty($exRec)) {
            $res['type'] = 'existing';
            $res['productId'] = $exRec->productId;
            $res['batch'] = $exRec->batch;
            $res['quantity'] = $exRec->quantity;

            if(planning_Setup::get('ALLOW_SERIAL_FROM_DIFFERENT_TASKS') != 'yes'){
                if($exRec->state != 'rejected' && $type == 'production' && $exRec->type == 'production' && $taskId != $exRec->taskId){
                    $res['error'] = 'Производственият номер е произведен по друга операция|*: <b>' . planning_Tasks::getHyperlink($exRec->taskId, true) . '</b>';
                }
            }
        } else {

            // Проверка дали серийния номер е за този артикул
            $pRec = $Driver->getRecBySerial($serial);
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
        }

        $error = '';
        if ($res['productId'] != $productId) {
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
        $dateVerbal = !empty($rec->date) ? ht::createHint($dateVerbal, 'Датата е ръчно въведена|*!', 'notice', false) : $dateVerbal;

        $row->date = "<div class='nowrap'>{$dateVerbal}";
        $row->date .= ' ' . tr('от||by') . ' ' . crm_Profiles::createLink($rec->createdBy) . '</div>';
        $row->ROW_ATTR['class'] = ($rec->state == 'rejected') ? 'state-rejected' : (($rec->type == 'input') ? 'row-added' : (($rec->type == 'production') ? 'state-active' : 'row-removed'));

        $pRec = cat_Products::fetch($rec->productId, 'measureId,code,isPublic,nameEn,name');
        $row->productId = cat_Products::getAutoProductDesc($rec->productId, null, 'short', 'internal');
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
        if(!empty($rec->employees) && !empty($rec->norm) && $rec->state != 'rejected'){
            $calcedNormHint = $mvc->calcNormByRec($rec, null, true);
            $row->employees = ht::createHint($row->employees, $calcedNormHint, 'notice', false);
        }

        if(planning_ProductionTaskProducts::isProduct4Task($rec->taskId, $rec->productId)){
            $rec->quantity /= $taskRec->quantityInPack;
        }

        $row->measureId = cat_UoM::getShortName($measureId);
        $labelPackagingName = cat_UoM::getShortName($labelPackagingId);
        if (cat_UoM::fetchField($measureId, 'type') != 'uom') {
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

        if($mvc->haveRightFor('fix', $rec)){
            core_RowToolbar::createIfNotExists($row->_rowTools);
            $row->_rowTools->addLink('Бракуване', array($mvc, 'fix', $rec->id, 'field' => 'scrappedQuantity','ret_url' => true), 'title=Бракуване на произведено количество,ef_icon=img/16/bin_closed.png');
            $row->_rowTools->addLink('Тегло', array($mvc, 'fix', $rec->id, 'field' => 'weight','ret_url' => true), 'title=Въвеждане на тегло,ef_icon=img/16/calculator.png');
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
            $selectedTerminalId = Mode::get('taskProgressInTerminal');

            if(!$selectedTerminalId){
                unset($data->listFields['notes']);
                $data->listTableMvc->FNC('shortUoM', 'varchar', 'tdClass=nowrap');
                $data->listTableMvc->setField('productId', 'tdClass=nowrap');
                $data->listTableMvc->FNC('info', 'varchar', 'tdClass=task-row-info');
                $data->listTableMvc->FNC('created', 'varchar', 'smartCenter');
                $data->listTableMvc->setField('weight', 'smartCenter');
            } else {
                $data->listTableMvc->FNC('quantityExtended', 'varchar', 'tdClass=centerCol');
                if (doc_Setup::get('LIST_FIELDS_EXTRA_LINE') != 'no') {
                    $data->listTableMvc->tableRowTpl = "<tbody class='rowBlock'>[#ADD_ROWS#][#ROW#]</tbody>\n";
                } else {
                    $data->listTableMvc->tableRowTpl = "[#ADD_ROWS#][#ROW#]\n";
                }

                $lastRecId = Mode::get("terminalLastRec{$selectedTerminalId}");
            }
        }

        $rows = &$data->rows;
        if (!countR($rows)) return;

        $weightWarningPercent = ($data->masterData->rec->weightDeviationWarning) ? $data->masterData->rec->weightDeviationWarning : planning_Setup::get('TASK_WEIGHT_TOLERANCE_WARNING');
        $masterRec = $data->masterData->rec;

        $selectRowUrl = array();
        if($terminalId = Mode::get('taskProgressInTerminal')){
            $terminalRec = planning_Points::fetch($terminalId);
            $terminalRec->taskId = Mode::get("currentTaskId{$terminalId}");
            if(planning_Points::haveRightFor('selecttask', $terminalRec)){
                $selectRowUrl = array('planning_Terminal', 'selectTask', $terminalId, 'taskId' => $terminalRec->taskId);
            }
        }

        foreach ($rows as $id => $row) {
            $rec = $data->recs[$id];

            $row->scrappedQuantity = '';
            if (!empty($rec->scrappedQuantity)) {
                $row->scrappedQuantity = core_Type::getByName('double(smartRound)')->toVerbal($rec->scrappedQuantity / $masterRec->quantityInPack);
                $row->scrappedQuantity = " (" . tr('Брак') . ": {$row->scrappedQuantity})";
            }

            if($data->isMeasureKg && ($masterRec->productId == $rec->productId)){
                if($rec->quantity == $rec->weight){
                    unset($row->quantity);
                }
                $row->weight = "<b>{$row->weight}</b> {$row->measureId} {$row->scrappedQuantity}";
            } else {
                $row->quantity = "<b>{$row->quantity}</b> {$row->measureId} {$row->scrappedQuantity}";
            }

            if($id == $lastRecId){
                $row->ROW_ATTR['class'] .= ' lastRow';
            }
            
            if (!empty($row->shortUoM)) {
                $row->quantity = "<b>{$row->quantity}</b>";
                if (!empty($row->scrappedQuantity)) {
                    $hint = "Брак|* {$row->scrappedQuantity} {$row->shortUoM}";
                    $row->quantity = ht::createHint($row->quantity, $hint, 'warning', false, 'width=14px;height=14px');
                }
            }
            
            // Проверка има ли отклонение спрямо очакваното транспортно тегло
            if(!empty($rec->weight)){
                $transportWeight = cat_Products::getTransportWeight($rec->productId, $rec->quantity);
                
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
                if($rec->type != 'production' || !planning_ProductionTaskProducts::isProduct4Task($masterRec->id, $rec->productId)){
                    $row->info = "{$row->productId}";
                }
            }

            if(!empty($rec->notes)){
                $notes = $mvc->getFieldType('notes')->toVerbal($rec->notes);
                $row->type = ht::createHint($row->type, $notes, 'img/16/comment.png');
            }
            
            if(Mode::is('taskProgressInTerminal')){
                $row->typeExtended = "<span class='extended-type'>{$row->type}</span><span class='extended-productId'> » {$row->productId}</span><span class='extended-created fright'>{$row->created}</span>";
                $row->quantityExtended = "<div class='extended-quantity'>{$row->quantity}</div>";
                if(!empty($rec->weight)){
                    $row->quantityExtended .= "<span class='extended-weight'>{$row->weight} " . tr('кг') . "</span>";
                }
                $row->additional = null;
                if(!empty($rec->employees)){
                    $row->additional = "<div class='extended-employees'>{$row->employees}</div>";
                }
                if(!empty($rec->fixedAsset)){
                    $row->additional .= "<div class='extended-fixedAsset'>{$row->fixedAsset}</div>";
                }
                
                if(!empty($rec->serial) && countR($selectRowUrl)){
                    $selectRowUrl['recId'] = $rec->id;
                    $row->serial = ht::createLink($row->serial, $selectRowUrl, false, 'title=Редакция на реда');
                }
            } else {
                if(!empty($rec->serial) && $rec->state != 'rejected'){
                    $row->serial = self::getLink($rec->taskId, $rec->serial);
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
                $data->toolbar->addBtn($btnName, array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'production', 'ret_url' => true), false, 'ef_icon = img/16/package.png,title=Добавяне на произведен артикул');
            }
            
            if ($mvc->haveRightFor('add', (object) array('taskId' => $data->masterId, 'type' => 'input'))) {
                $data->toolbar->addBtn('Влагане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'input', 'ret_url' => true), false, 'ef_icon = img/16/wooden-box.png,title=Добавяне на вложен артикул');
            }
            
            if ($mvc->haveRightFor('add', (object) array('taskId' => $data->masterId, 'type' => 'waste'))) {
                $data->toolbar->addBtn('Отпадък', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'waste', 'ret_url' => true), false, 'ef_icon = img/16/recycle.png,title=Добавяне на отпаден артикул');
            }
        }
    }
    
    
    /**
     * Рендиране на детайла
     */
    public function renderDetail_($data)
    {
        if(!Mode::is('taskInTerminal')){
            
            return parent::renderDetail_($data);
        }
    }
    
    
    /**
     * Подготовка на детайла
     */
    public function prepareDetail_($data)
    {
        if(!Mode::is('taskInTerminal')){
            $data->TabCaption = 'Прогрес';
            $data->Tab = 'top';
            
            parent::prepareDetail_($data);
        }
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('createdOn', 'DESC');
        if(Mode::is('getLinkedObj') || Mode::is('inlineDocument') || Mode::is('taskProgressInTerminal')) {
            
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
            $state = $mvc->Master->fetchField($rec->taskId, 'state');
            if (!in_array($state, array('active', 'wakeup', 'pending'))) {
                $requiredRoles = 'no_one';
            }
        }
        
        // Трябва да има поне един артикул възможен за добавяне
        if ($action == 'add' && isset($rec->type) && $rec->type != 'product' && $rec->type != 'start') {
            if ($requiredRoles != 'no_one') {
                $pOptions = planning_ProductionTaskProducts::getOptionsByType($rec->taskId, $rec->type);
                if (!countR($pOptions)) {
                    $requiredRoles = 'no_one';
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

        if($action == 'edit' && isset($rec)){
            if($rec->type != 'production' || $rec->state == 'rejected'){
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
        $data->singleTitle = ($rec->type == 'input') ? 'влагане' : (($rec->type == 'waste') ? 'отпадък' : 'произвеждане');
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

        if($rec->type == 'production') {
            $taskRec = is_object($taskRec) ? $taskRec : planning_Tasks::fetch($rec->taskId, 'originId,isFinal,productId,measureId,indPackagingId,labelPackagingId,indTimeAllocation,quantityInPack');
            $jobProductId = planning_Jobs::fetchField("#containerId = {$taskRec->originId}", 'productId');

            // Ако артикула е за финален етап вземат се данните от мастъра на операцията
            if(($taskRec->isFinal == 'yes' && $rec->productId == $jobProductId) || $rec->productId == $taskRec->productId){
                if(cat_UoM::fetchField($taskRec->measureId, 'type') == 'uom'){
                    if($taskRec->indPackagingId == $taskRec->measureId){
                        $quantity /= $taskRec->quantityInPack;
                    }
                }

                if($taskRec->measureId != $taskRec->indPackagingId){
                    if ($indQuantityInPack = cat_products_Packagings::getPack($rec->productId, $taskRec->indPackagingId, 'quantity')) {
                        $quantity = ($quantity / $indQuantityInPack);
                    }
                }
            }
        }

        $normFormQuantity = planning_type_ProductionRate::getInSecsByQuantity($rec->norm, $quantity);
        $normFormQuantity = round($normFormQuantity);
        if($verbal) {
            $normFormQuantity = "|Заработка|*: {$normFormQuantity} s";
            if(haveRole('debug')){
                $quantity = round($quantity, 5);
                $normFormQuantity .= " [N:{$rec->norm} - Q:{$quantity}]";
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
        $query->EXT('indTimeAllocation', 'planning_Tasks', 'externalName=indTimeAllocation,externalKey=taskId');
        $query->EXT('indPackagingId', 'planning_Tasks', 'externalName=indPackagingId,externalKey=taskId');
        $query->EXT('labelPackagingId', 'planning_Tasks', 'externalName=labelPackagingId,externalKey=taskId');
        $query->EXT('taskProductId', 'planning_Tasks', 'externalName=productId,externalKey=taskId');
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
            $arr = arr::make("taskId=id,taskMeasureId=measureId,indTimeAllocation=indTimeAllocation,indPackagingId=indPackagingId,labelPackagingId=labelPackagingId,taskProductId=productId,isFinal=isFinal,originId=originId,taskQuantityInPack=quantityInPack", true);
            foreach ($arr as $fldAlias => $fld){
                $taskRec->{$fld} = $rec->{$fldAlias};
            }

            $normFormQuantity = static::calcNormByRec($rec, $taskRec);
            $timePerson = ($rec->indTimeAllocation == 'individual') ? $normFormQuantity : ($normFormQuantity / countR($persons));

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
                
                $result[$key]->value += $timePerson;
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
        expect($taskRec = planning_Tasks::fetch($taskId), 'Няма така задача');
        expect(in_array($params['type'], array('production', 'input', 'waste')));
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
            $serialInfo = self::fetchSerialInfo($params['serial'], $productId, $taskId);
            $rec->serial = $params['serial'];
            $rec->serialType = $serialInfo['type'];
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
        
        // Ако има друг запис със същия номер оттегля се
        if($rejectId = self::fetchField("#taskId = {$taskId} AND #serial = '{$params['serial']}' AND #state != 'rejected'")){
            $rec->_rejectId = $rejectId;
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
        expect($field = Request::get('field', 'enum(scrappedQuantity,weight)'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('fix', $rec);
        $masterRec = planning_Tasks::fetch($rec->taskId);
        $quantity = $rec->quantity / $masterRec->quantityInPack;

        $form = cls::get('core_Form');
        $row = $this->recToVerbal($rec);
        $infoTpl = new core_ET(tr("|*<div class='richtext-info-no-image'>|Артикул|*: [#productId#]<br>|Произв. №|*: [#serial#]<br><!--ET_BEGIN employees-->|Оператори|*: [#employees#]<!--ET_END employees--><br>[#date#]</div>"));
        $infoTpl->placeObject($row);
        $form->info = $infoTpl;

        // Подготовка на формата
        $measureName = cat_UoM::getShortName($masterRec->measureId);
        $docTitle = planning_Tasks::getHyperlink($rec->taskId, true);

        $title = ($field == "scrappedQuantity") ? "Бракуване на произведено количество от" : "Въвеждане на тегло";
        $form->title = "{$title}|* <b style='color:#ffffcc;'>{$docTitle}</b>";
        if($field == "scrappedQuantity"){
            $form->FLD('scrappedQuantity', "double(Min=0,max={$quantity})", "caption=Брак,mandatory,unit= от|* {$quantity} {$measureName}");
            if(!empty($rec->scrappedQuantity)){
                $form->setDefault('scrappedQuantity', $rec->scrappedQuantity / $masterRec->quantityInPack);
            }
        } else {
            $form->setDefault('weight', $rec->weight);
            $form->FLD('weight', "double(Min=0)", "caption=Тегло,unit=кг");
        }
        $form->input();

        // Запис на бракуваното количество
        if($form->isSubmitted()){
            if($field == 'scrappedQuantity'){
                $scrappedQuantity = $form->rec->scrappedQuantity * $masterRec->quantityInPack;
                $rec->scrappedQuantity = $scrappedQuantity;
                $logMsg = "Бракуване";
                $statusMsg = 'Количеството е бракувано успешно|*!';
            } else {
                $rec->weight = $form->rec->weight;
                $logMsg = "Промяна на тегло";
                $statusMsg = 'Теглото е променено успешно|*!';
            }

            $this->save_($rec, $field);
            planning_Tasks::logWrite($logMsg, $rec->taskId);
            followRetUrl(null, $statusMsg);
        }

        $form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/bin_closed.png, title = Бракуване на количество');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);

        return $tpl;
    }
}
