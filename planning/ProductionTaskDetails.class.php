<?php


/**
 * Клас 'planning_ProductionTaskDetails'
 *
 * Мениджър за Прогрес на производствените операции
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2019 Experta OOD
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
    public $interfaces = 'hr_IndicatorsSourceIntf,label_SequenceIntf=planning_interface_TaskLabel';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'taskId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_AlignDecimals2, plg_SaveAndNew, plg_Rejected, plg_Modified, plg_Created, plg_LastUsedKeys, plg_Sorting, planning_Wrapper, plg_Search, planning_Wrapper,plg_GroupByField';
    
    
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
    public $canEdit = 'taskWorker,ceo';
    
    
    /**
     * Кой има право да листва?
     */
    public $canList = 'taskWorker,ceo';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'taskId,type=Действие,serial,productId,taskId,quantity,weight=Тегло (кг),employees,fixedAsset,created=Създаване,info=@';
    
    
    /**
     * Кои колони да скриваме ако янма данни в тях
     */
    public $hideListFieldsIfEmpty = 'serial,weight,employees,fixedAsset,scrappedQuantity,quantityExtended,typeExtended,additional';
    
    
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
     *
     * @var int
     */
    public $listItemsPerPage = 30;
    
    
    /**
     * Рендиране на мастъра под формата за редактиране/добавяне
     */
    public $renderMasterBellowForm = true;
    
    
    /**
     * Каква да е максималната дължина на стринга за пълнотекстово търсене
     * 
     * @see plg_Search
     */
    public $maxSearchKeywordLen = 13;
    
    
    /**
     * Кой може да печата бърз етикет
     */
    public $canPrintfastlabel = 'debug';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('taskId', 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory,caption=Операция');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'silent,caption=Артикул,removeAndRefreshForm=serial|quantity');
        $this->FLD('type', 'enum(input=Влагане,production=Произв.,waste=Отпадък)', 'input=hidden,silent,tdClass=small-field nowrap');
        $this->FLD('serial', 'varchar(32)', 'caption=Сер. №,focus,autocomplete=off,silent');
        $this->FLD('serialType', 'enum(existing=Съществуващ,generated=Генериран,printed=Отпечатан,unknown=Непознат)', 'caption=Тип на серийния номер,input=none');
        $this->FLD('quantity', 'double(Min=0)', 'caption=Количество');
        $this->FLD('scrappedQuantity', 'double(Min=0)', 'caption=Брак,input=none');
        $this->FLD('weight', 'double(Min=0)', 'caption=Тегло,unit=кг');
        $this->FLD('employees', 'keylist(mvc=crm_Persons,select=id)', 'caption=Оператори');
        $this->FLD('fixedAsset', 'key(mvc=planning_AssetResources,select=id)', 'caption=Оборудване,input=none,tdClass=nowrap');
        $this->FLD('notes', 'richtext(rows=2,bucket=Notes)', 'caption=Допълнително->Забележки,autohide');
        $this->FLD('state', 'enum(active=Активирано,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull');
        $this->FLD('norm', 'time', 'caption=Време,input=none');
        
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
            $form->setDefault('fixedAsset', $lastRec->fixedAsset);
        }
        
        // Ако в мастъра са посочени машини, задават се като опции
        if (isset($masterRec->fixedAssets)) {
            $keylist = $masterRec->fixedAssets;
            $arr = keylist::toArray($keylist);
            foreach ($arr as $key => &$value) {
                $value = planning_AssetResources::getTitleById($key, false);
            }
            
            $assetOptions = ((Mode::is('terminalProgressForm')) ? array(' ' => ' ') : array('' => '')) + $arr;
            $form->setOptions('fixedAsset', $assetOptions);
            $form->setField('fixedAsset', 'input,mandatory');
            if(countR($arr) == 1 && !Mode::is('terminalProgressForm')){
                $form->setReadOnly('fixedAsset', key($arr));
            }
        } else {
            $form->setField('fixedAsset', 'input=none');
        }
        
        $productOptions = planning_ProductionTaskProducts::getOptionsByType($rec->taskId, $rec->type);
        $form->setOptions('productId', array('' => '') + $productOptions);
        
        if ($rec->type == 'production') {
            $form->setDefault('productId', $masterRec->productId);
            
            // При редакция на производството само брака може да се променя
            if (isset($rec->id)) {
                $form->setReadOnly('productId');
                $form->setReadOnly('serial');
                $form->setReadOnly('quantity');
                $form->setField('scrappedQuantity', 'input');
                $form->setFieldTypeParams('scrappedQuantity', array('max' => $rec->quantity, 'min' => 0));
                $form->setField('employees', 'input=none');
                $form->setField('fixedAsset', 'input=none');
                $form->setField('notes', 'input=none');
            }
        }
        
        // Ако наличната опция е само една, по дефолт е избрана
        if (countR($productOptions) == 1) {
            $form->setDefault('productId', key($productOptions));
            $form->setReadOnly('productId');
        }
        
        // Ако е избран артикул
        if (isset($rec->productId)) {
            $labelType = (($rec->type == 'production') ? $masterRec->labelType : (($rec->type == 'input') ? 'scan' : 'print'));
            
            if($labelType == 'print'){
                $form->setField('serial', 'input=none');
            } elseif($labelType == 'scan'){
                $form->setField('serial', 'mandatory');
            }
            
            $pRec = cat_Products::fetch($rec->productId, 'measureId,canStore');
            if ($pRec->canStore != 'yes' && $rec->productId == $masterRec->productId) {
                if ($rest = $masterRec->plannedQuantity - $masterRec->totalQuantity) {
                    if($rest > 0){
                        $form->setDefault('quantity', $rest);
                    }
                }
            }
            
            $info = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type, $rec->fixedAsset);
            $shortMeasure = ($rec->productId == $masterRec->productId) ? cat_UoM::getShortName($pRec->measureId) : cat_UoM::getShortName($info->packagingId);
            
            if($rec->type == 'production' && isset($masterRec->packagingId)){
                $unit = $shortMeasure . ' / ' . cat_UoM::getShortName($masterRec->packagingId);
                $form->setField('quantity', "unit={$unit}");
                $packRec = cat_products_Packagings::getPack($masterRec->productId, $masterRec->packagingId);
                $defaultQuantity = is_object($packRec) ? ($packRec->quantity / $masterRec->quantityInPack) : 1;
                $form->setField('quantity', "placeholder={$defaultQuantity}");
                $form->rec->_defaultQuantity = $defaultQuantity;
            } else {
                $unit = cat_UoM::getShortName($info->packagingId);
                $form->setField('quantity', "unit={$unit}");
            }
        }
        
        // Връща избрани оператори от операцията, или ако няма всички от центъра
        $employees = !empty($masterRec->employees) ? planning_Hr::getPersonsCodesArr($masterRec->employees) : planning_Hr::getByFolderId($masterRec->folderId);
       
        if (countR($employees)) {
            $form->setSuggestions('employees', $employees);
            
            if(!empty($masterRec->employees)){
                $form->setField('employees', 'mandatory');
            }
            if(countR($employees) == 1){
                if(!Mode::is('terminalProgressForm')){
                    $form->setDefault('employees', keylist::addKey('', key($employees)));
                }
            }
        } else {
            $form->setField('employees', 'input=none');
        }
        
        // Показване на допълнителна мярка при нужда
        if($rec->type == 'production'){
            if ($masterRec->showadditionalUom == 'no') {
                $form->setField('weight', 'input=none');
            } elseif($masterRec->showadditionalUom == 'mandatory'){
                $form->setField('weight', 'mandatory');
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
            if (empty($rec->serial) && empty($rec->productId) && !empty($masterRec->packagingId)) {
                $form->setError('serial,productId', 'Трябва да е въведен артикул или сериен номер');
            }
            
            if(isset($rec->productId)){
                $productRec = cat_Products::fetch($rec->productId, 'canStore,generic');
                
                if(!empty($rec->serial)){
                    $rec->serial = plg_Search::normalizeText($rec->serial);
                    $rec->serial = str::removeWhiteSpace($rec->serial);
                    if ($Driver = cat_Products::getDriver($rec->productId)) {
                        $rec->serial = $Driver->canonizeSerial($rec->productId, $rec->serial);
                    }
                    
                    if ($exId = self::fetchField("#taskId = {$rec->taskId} AND #serial = '{$rec->serial}' AND #id != '{$rec->id}' AND #state != 'rejected'")) {
                        $form->setWarning('serial', 'Наистина ли, искате да подмените, съществуващия от преди запис|*?');
                        $form->rec->_rejectId = $exId;
                    }
                }
                
                if (!empty($rec->serial)) {
                    $serialInfo = self::fetchSerialInfo($rec->serial, $rec->productId, $rec->taskId, $rec->type);
                    
                    $rec->serialType = $serialInfo['type'];
                    if (isset($serialInfo['error'])) {
                        $form->setError('serial', $serialInfo['error']);
                    }
                }
                
                // Ако артикулът е действие към оборудването
                if ($productRec->canStore != 'yes' && $rec->type == 'input') {
                    $inTp = planning_ProductionTaskProducts::fetchField("#taskId = {$rec->taskId} AND #type = 'input' AND #productId = {$rec->productId}");
                    $inInputTask = planning_Tasks::fetchField("#originId = {$masterRec->originId} AND #inputInTask = {$rec->taskId} AND #state != 'draft' AND #state != 'rejected' AND #state != 'pending' AND #productId = {$rec->productId}");
                    
                    // Подсигуряване че трябва да има норма
                    if (empty($inTp) && empty($inInputTask)) {
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
            
            if (!$form->gotErrors()) {
                $rec->quantity = (!empty($rec->quantity)) ? $rec->quantity : ((!empty($rec->_defaultQuantity)) ? $rec->_defaultQuantity : 1);
                
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
            }
        }
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass     $rec
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
                $rec->serial = $Driver->generateSerial($rec->productId, 'planning_Tasks', $rec->taskId);
                $rec->serialType = 'generated';
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
     * Информация за серийния номер
     *
     * @param string   $serial
     * @param int      $productId
     * @param int      $taskId
     * @param int|NULL $id
     *
     * @return array $res
     */
    private static function fetchSerialInfo($serial, $productId, $taskId, $type = null)
    {
        if (!$Driver = cat_Products::getDriver($productId)) {
            
            return;
        }
        
        $res = array('serial' => $serial, 'productId' => $productId, 'type' => 'unknown');
        $canonizedSerial = $Driver->canonizeSerial($productId, $serial);
        $exRec = self::fetch(array("#serial = '[#1#]'", $canonizedSerial));
        
        if (!empty($exRec)) {
            $res['type'] = 'existing';
            $res['productId'] = $exRec->productId;
            if($type == 'production' && $exRec->type == 'production' && $taskId != $exRec->taskId){
                $res['error'] = 'Серийния номер е произведен по друга операция|*: <b>' . planning_Tasks::getHyperlink($exRec->taskId, true) . '</b>';
            }
        } else {
            if ($pRec = $Driver->getRecBySerial($serial)) {
                $res['type'] = 'existing';
                $res['productId'] = $pRec->id;
            }
        }
        
        $error = '';
        if ($res['productId'] != $productId) {
            $res['error'] = 'Серийния номер е към друг артикул|*: <b>' . cat_Products::getHyperlink($res['productId'], true) . '</b>';
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
        if (isset($rec->fixedAsset)) {
            $row->fixedAsset = planning_AssetResources::getHyperlink($rec->fixedAsset);
        }
        
        $taskRec = planning_Tasks::fetch($rec->taskId);
        $row->taskId = planning_Tasks::getLink($rec->taskId, 0);
        $row->created = "<div class='nowrap'>" . $mvc->getFieldType('createdOn')->toVerbal($rec->createdOn);
        $row->created .= ' ' . tr('от||by') . ' ' . crm_Profiles::createLink($rec->createdBy) . '</div>';
        $row->ROW_ATTR['class'] = ($rec->state == 'rejected') ? 'state-rejected' : (($rec->type == 'input') ? 'row-added' : (($rec->type == 'production') ? 'state-active' : 'row-removed'));
        
        $pRec = cat_Products::fetch($rec->productId, 'measureId,code,isPublic,nameEn,name');
        $row->productId = cat_Products::getShortHyperlink($rec->productId);
        $row->measureId = cat_UoM::getShortName($pRec->measureId);
        
        $foundRec = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type, $rec->fixedAsset);
        $labelPackagingId = (!empty($foundRec->packagingId)) ? $foundRec->packagingId : $pRec->measureId;
        
        if($taskRec->productId != $rec->productId){
            $packagingId = $labelPackagingId;
        } else {
            $packagingId = $pRec->measureId;
        }
        $packagingName = cat_UoM::getShortName($packagingId);
        $labelPackagingName = cat_UoM::getShortName($labelPackagingId);
        
        if (cat_UoM::fetchField($packagingId, 'type') != 'uom') {
            $row->measureId = str::getPlural($rec->quantity, $packagingName, true);
        }
        
        if ($rec->type == 'production') {
            $row->type = (!empty($packagingId) && ($labelPackagingId !== $pRec->measureId)) ? tr("Произв.|* {$labelPackagingName}") : tr('Произвеждане');
        }
        
        $row->scrappedQuantity = '';
        if (!empty($rec->scrappedQuantity)) {
            $row->scrappedQuantity = core_Type::getByName('double(smartRound)')->toVerbal($rec->scrappedQuantity);
            $row->scrappedQuantity = " (" . tr('Брак') . ": {$row->scrappedQuantity})";
        }
        $row->quantity = "<b>{$row->quantity}</b> {$row->measureId} {$row->scrappedQuantity}";
        
        if (isset($rec->employees)) {
            $row->employees = self::getVerbalEmployees($rec->employees);
        }
        
        $rec->_createdDate = dt::verbal2mysql($rec->createdOn, false);
        $row->_createdDate = dt::mysql2verbal($rec->_createdDate, 'd/m/y l');
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
        if (Mode::isReadOnly()) {
            
            return $serialVerbal;
        }
        
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
        if (isset($data->masterMvc)) {
            $selectedTerminalId = Mode::get('taskProgressInTerminal');
            $lastRecId = null;
            
            if(!$selectedTerminalId){
                unset($data->listFields['notes']);
                unset($data->listFields['productId']);
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
        if (!countR($rows)) {
            
            return;
        }
        
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
            
            if (isset($data->masterMvc) && $masterRec->productId != $rec->productId) {
                $row->info = "{$row->productId}";
            }
            
            if(!empty($row->notes)){
                $row->type .= "<small>{$row->notes}</small>";
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
     *
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
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Документа не може да се създава  в нова нишка, ако е възоснова на друг
        if (!empty($data->toolbar->buttons['btnAdd'])) {
            $data->toolbar->removeBtn('btnAdd');
            
            if ($mvc->haveRightFor('add', (object) array('taskId' => $data->masterId, 'type' => 'production'))) {
                $btnName = empty($data->masterData->rec->packagingId) ? 'Произвеждане' : "Произв.|* " . tr(cat_UoM::getTitleById(($data->masterData->rec->packagingId)));
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
        
        $data->listFilter->setField('type', 'input=none');
        $data->listFilter->class = 'simpleForm';
        if (isset($data->masterMvc)) {
            $data->showRejectedRows = true;
            $data->listFilter->FLD('threadId', 'int', 'silent,input=hidden');
            $data->listFilter->view = 'horizontal';
            $data->listFilter->input(null, 'silent');
            unset($data->listFields['taskId']);
            unset($data->listFields['createdOn']);
            unset($data->listFields['createdBy']);
            unset($data->listFields['productId']);
            unset($data->listFields['taskId']);
            $data->groupByField = '_createdDate';
        } else {
            unset($data->listFields['_createdDate']);
        }
        
        $data->listFilter->showFields = 'search';
        
        // Ако има използвани оператори, добавят се за филтриране
        $usedFixedAssets = self::getResourcesInDetails($data->masterId, 'fixedAsset');
        if(countR($usedFixedAssets)){
            $data->listFilter->setOptions('fixedAsset', array('' => '') + $usedFixedAssets);
            $data->listFilter->showFields .= ",fixedAsset";
        }
        
        // Ако има използвани оператори, добавят се за филтриране
        $usedEmployeeIds = self::getResourcesInDetails($data->masterId, 'employees');
        if(countR($usedEmployeeIds)){
            $data->listFilter->setOptions('employees', array('' => '') + $usedEmployeeIds);
            $data->listFilter->showFields .= ",employees";
        }
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        
        // Филтър по избраните стойности
        if ($filter = $data->listFilter->rec) {
            if (!empty($filter->fixedAsset)) {
                $data->query->where("#fixedAsset = '{$filter->fixedAsset}'");
            }
            if (!empty($filter->employees)) {
                $data->query->where("LOCATE('|{$filter->employees}|', #employees)");
            }
            
            if (!empty($filter->serial)) {
                $data->query->like('serial', $filter->serial);
            }
        }
    }
    
    
    /**
     * Извлича използваните ресурси в детайлите
     * 
     * @param int|null $taskId
     * @param string $type
     * @return array $array
     */
    private static function getResourcesInDetails($taskId, $type)
    {
        expect(in_array($type, array('fixedAsset', 'employees')));
        $query = self::getQuery();
        $query->where("#{$type} IS NOT NULL AND #{$type} != ''");
        if(!empty($taskId)){
            $query->where("#taskId = {$taskId}");
        }
        $query->show($type);
        $recs = $query->fetchAll();
        
        // Обединяват се всички записи
        $keylist = '';
        array_walk($recs, function ($obj) use (&$keylist, $type) {
            $keylist = keylist::merge($keylist, $obj->{$type});
        });
        
        // Вербализирането на опциите
        $array = array();
        $keylist = keylist::toArray($keylist);
        foreach ($keylist as $key){
            if(!array_key_exists($key, $array)){
                $value = ($type == 'fixedAsset') ? planning_AssetResources::getTitleById($key) : (crm_Persons::getVerbal($key, 'name') . " ($key)");
                $array[$key] = $value;
            }
        }
        
        return $array;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'reject' || $action == 'edit' || $action == 'delete') && isset($rec->taskId)) {
            $state = $mvc->Master->fetchField($rec->taskId, 'state');
            
            if ($state != 'active' && $state != 'waiting' && $state != 'wakeup') {
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
        
        if ($action == 'edit' && isset($rec)) {
            if ($rec->type != 'production' || $rec->state == 'rejected') {
                $requiredRoles = 'no_one';
            }
        }
        
        if($action == 'printperipherallabel' && isset($rec)){
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
     * Метод за вземане на резултатност на хората. За определена дата се изчислява
     * успеваемостта на човека спрямо ресурса, които е изпозлвал
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
        $query->EXT('indTimeAllocation', 'planning_Tasks', 'externalName=indTimeAllocation,externalKey=taskId');
        $query->EXT('indPackagingId', 'planning_Tasks', 'externalName=indPackagingId,externalKey=taskId');
        $query->EXT('taskModifiedOn', 'planning_Tasks', 'externalName=modifiedOn,externalKey=taskId');
        $query->where("#taskModifiedOn >= '{$timeline}' AND #norm IS NOT NULL");
        
        $iRec = hr_IndicatorNames::force('Време', __CLASS__, 1);
        $classId = planning_Tasks::getClassId();
        $indicatorId = $iRec->id;
        
        while ($rec = $query->fetch()) {
            
            // Ако няма оператори, пропуска се
            $persons = keylist::toArray($rec->employees);
            if (!countR($persons)) {
                continue;
            }
            
            $quantity = $rec->quantity;
            if($rec->type == 'production'){
                $quantityInPack = 1;
                if(isset($rec->indPackagingId)){
                    if($packRec = cat_products_Packagings::getPack($rec->productId, $rec->indPackagingId)){
                        $quantityInPack = $packRec->quantity;
                    }
                }
                
                $quantity = round(($rec->quantity / $quantityInPack), 2);
            }
            
            // Колко е заработката за 1 човек
            $timePerson = ($rec->indTimeAllocation == 'individual') ? $quantity * $rec->norm : (($quantity * $rec->norm) / countR($persons));
            
            $date = dt::verbal2mysql($rec->createdOn, false);
            foreach ($persons as $personId) {
                $key = "{$personId}|{$classId}|{$rec->taskId}|{$rec->state}|{$date}|{$indicatorId}";
                if (!array_key_exists($key, $result)) {
                    $result[$key] = (object) array('date' => $date,
                        'personId' => $personId,
                        'docId' => $rec->taskId,
                        'docClass' => $classId,
                        'indicatorId' => $indicatorId,
                        'value' => 0,
                        'isRejected' => ($rec->state == 'rejected'));
                }
                
                $result[$key]->value += $timePerson;
            }
        }
        
        return $result;
    }
    
    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     *
     * @param datetime $date
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
            expect($quantity = core_Type::getByName('double')->fromVerbal($quantity), 'Невалидно число');
        } elseif($params['type'] == 'production' && isset($taskRec->packagingId)){
            $packRec = cat_products_Packagings::getPack($taskRec->productId, $taskRec->packagingId);
            $quantity = is_object($packRec) ? ($packRec->quantity / $taskRec->quantityInPack) : 1;
        }
        
        expect($quantity > 0, 'Количеството трябва да е положително');
        $rec = (object)array('serialType' => 'unknown', '_generateSerial' => false, 'productId' => $productId, 'taskId' => $taskId, 'quantity' => $quantity, 'type' => $params['type']);
        if(!empty($params['employees'])){
            $params['employees'] = arr::make($params['employees']);
            $rec->employees = keylist::fromArray(array_combine($params['employees'], $params['employees']));
        }
        
        $rec->fixedAsset = (!empty($params['fixedAsset'])) ? $params['fixedAsset'] : null;
        if(!empty($params['weight'])){
            expect($params['weight'] = core_Type::getByName('double')->fromVerbal($params['weight']), 'Невалидно число');
            expect($params['weight'] > 0, 'Теглото трябва да е положително');
            $rec->weight = $params['weight'];
        }
        
        if(!empty($taskRec->fixedAssets)){
            $taskAssets = keylist::toArray($taskRec->fixedAssets);
            if(countR($taskAssets) && empty($rec->fixedAsset)){
                expect(!empty($rec->fixedAsset), 'Задължително трябва да е избрано оборудване');
            }
        }
        
        if(!empty($taskRec->employees) && empty($rec->employees)){
            expect(!empty($rec->employees), 'Задължително трябва да са избрани служители');
        }
        
        if($taskRec->showadditionalUom == 'mandatory' && $rec->type == 'production' && $rec->productId == $taskRec->productId){
            expect($rec->weight, 'Теглото е задължително');
        }
        
        $canStore = cat_Products::fetchField($productId, 'canStore');
        if(!empty($params['serial'])){
            expect(str::containOnlyDigits($params['serial']), 'Серийния номер може да е само от цифри');
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
            $inInputTask = planning_Tasks::fetchField("#originId = {$taskRec->originId} AND #inputInTask = {$rec->taskId} AND #state != 'draft' AND #state != 'rejected' AND #state != 'pending' AND #productId = {$rec->productId}");
            
            // Подсигуряване че трябва да има норма
            if (empty($inTp) && empty($inInputTask)) {
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
}
