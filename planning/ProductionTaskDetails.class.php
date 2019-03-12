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
    public $interfaces = 'hr_IndicatorsSourceIntf';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'taskId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_AlignDecimals2, plg_SaveAndNew, plg_Rejected, plg_Modified, plg_Created, plg_LastUsedKeys, plg_Sorting, planning_Wrapper, plg_Search, planning_Wrapper';
    
    
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
    public $canRestore = 'taskWorker,ceo';
    
    
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
    public $listFields = 'type=Действие,serial,productId,taskId,quantity,weight=Тегло (кг),employees,fixedAsset,modified=Модифициране,modifiedOn,modifiedBy,info=@';
    
    
    /**
     * Кои колони да скриваме ако янма данни в тях
     */
    public $hideListFieldsIfEmpty = 'serial,weight,employees,fixedAsset,scrappedQuantity';
    
    
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
    public $listItemsPerPage = 40;
    
    
    /**
     * Рендиране на мастъра под формата за редактиране/добавяне
     */
    public $renderMasterBellowForm = true;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('taskId', 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory,caption=Операция');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'silent,caption=Артикул,removeAndRefreshForm=serial|quantity');
        $this->FLD('type', 'enum(input=Влагане,production=Произв.,waste=Отпадък)', 'input=hidden,silent,tdClass=small-field nowrap');
        $this->FLD('serial', 'varchar(32)', 'caption=Сер. №,smartCenter,focus,autocomplete=off,silent');
        $this->FLD('serialType', 'enum(existing=Съществуващ,generated=Генериран,printed=Отпечатан,unknown=Непознат)', 'caption=Тип на серийния номер,input=none');
        $this->FLD('quantity', 'double(Min=0)', 'caption=Количество');
        $this->FLD('scrappedQuantity', 'double(Min=0)', 'caption=Брак,input=none');
        $this->FLD('weight', 'double', 'caption=Тегло,smartCenter,unit=кг');
        $this->FLD('employees', 'keylist(mvc=crm_Persons,select=id)', 'caption=Работници,tdClass=nowrap');
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
        $masterRec = planning_Tasks::fetch($rec->taskId);
        
        // Добавяме последните данни за дефолтни
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
            $form->setOptions('fixedAsset', array('' => '') + $arr);
            $form->setField('fixedAsset', 'input');
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
            
            if(empty($masterRec->packagingId)){
                $form->setField('serial', 'input=none');
            }
        }
        
        // Ако наличната опция е само една, по дефолт е избрана
        if (count($productOptions) == 1 && $form->cmd != 'refresh') {
            $form->setDefault('productId', key($productOptions));
            $form->setReadOnly('productId');
        }
        
        // Ако е избран артикул
        if (isset($rec->productId)) {
            $pRec = cat_Products::fetch($rec->productId, 'measureId,canStore');
            if ($pRec->canStore != 'yes') {
                $form->setField('serial', 'input=none');
                if ($rest = $masterRec->plannedQuantity - $masterRec->totalQuantity) {
                    $form->setDefault('quantity', $rest);
                }
            }
            
            $info = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type, $rec->fixedAsset);
            $shortMeasure = cat_UoM::getShortName($pRec->measureId);
            if($rec->type == 'production' && isset($masterRec->packagingId) && $masterRec->packagingId != $info->packagingId){
                $shortMeasure = cat_UoM::getShortName($info->packagingId);
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
        
        // Връща избрани служители от операцията, или ако няма всички от центъра
        $employees = !empty($masterRec->employees) ? planning_Hr::getPersonsCodesArr($masterRec->employees) : planning_Hr::getByFolderId($masterRec->folderId);
        if (count($employees)) {
            $form->setSuggestions('employees', $employees);
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
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        
        if ($form->isSubmitted()) {
            if(!empty($rec->serial)){
                $rec->serial = plg_Search::normalizeText($rec->serial);
                $rec->serial = str::removeWhitespaces($rec->serial);
                if ($rec->productId && ($Driver = cat_Products::getDriver($rec->productId))) {
                    $rec->serial = $Driver->canonizeSerial($rec->productId, $rec->serial);
                }
            }
            
            $masterRec = planning_Tasks::fetch($rec->taskId);
            if (empty($rec->serial) && empty($rec->productId) && !empty($masterRec->packagingId)) {
                $form->setError('serial,productId', 'Трябва да е въведен артикул или сериен номер');
            }
            
            if(isset($rec->productId)){
                $rec->_generateSerial = false;
                $canStore = cat_Products::fetchField($rec->productId, 'canStore');
                if($canStore == 'yes' && $rec->type == 'production' && !empty($masterRec->packagingId)){
                    $rec->_generateSerial = true;
                }
                
                if ($canStore == 'yes') {
                    if ($rec->type == 'production' && !empty($masterRec->packagingId) && !empty($rec->serial)) {
                        if (self::fetchField("#taskId = {$rec->taskId} AND #serial = '{$rec->serial}' AND #id != '{$rec->id}'")) {
                            $form->setError('serial', 'Сер. № при произвеждане трябва да е уникален');
                        }
                    }
                    
                    if (!empty($rec->serial)) {
                        $serialInfo = self::fetchSerialInfo($rec->serial, $rec->productId, $rec->packagingId, $rec->id);
                        $rec->serialType = $serialInfo['type'];
                        
                        if (isset($serialInfo['error'])) {
                            $form->setError('serial', $serialInfo['error']);
                        }
                    }
                } elseif ($rec->type == 'input') {
                    
                    // Ако артикула е действие към оборудването
                    $inTp = planning_ProductionTaskProducts::fetchField("#taskId = {$rec->taskId} AND #type = 'input' AND #productId = {$rec->productId}");
                    $inInputTask = planning_Tasks::fetchField("#originId = {$masterRec->originId} AND #inputInTask = {$rec->taskId} AND #state != 'draft' AND #state != 'rejected' AND #state != 'pending' AND #productId = {$rec->productId}");
                    
                    // Подсигуряване че трябва да има норма
                    if (empty($inTp) && empty($inInputTask)) {
                        if (!planning_AssetResources::getNormRec($rec->fixedAsset, $rec->productId)) {
                            $form->setError('productId,fixedAsset', 'Изберете оборудване, което има норма за действието');
                        }
                    }
                }
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
        if ($rec->_generateSerial === true) {
            if ($Driver = cat_Products::getDriver($rec->productId)) {
                $rec->serial = $Driver->generateSerial($rec->productId, 'planning_Tasks', $rec->taskId);
                $rec->serialType = 'generated';
            }
        }
        
        if (!empty($rec->serial)) {
            if ($Driver = cat_Products::getDriver($rec->productId)) {
                $rec->serial = $Driver->canonizeSerial($rec->productId, $rec->serial);
            }
            
            $rec->searchKeywords .= ' ' . plg_Search::normalizeText($rec->serial);
        }
    }
    
    
    /**
     * Информация за серийния номер
     *
     * @param string   $serial
     * @param int      $productId
     * @param int      $packagingId
     * @param int|NULL $id
     *
     * @return array $res
     */
    private static function fetchSerialInfo($serial, $productId, $packagingId, $id)
    {
        if (!$Driver = cat_Products::getDriver($productId)) {
            
            return;
        }
        $res = array('serial' => $serial, 'productId' => $productId, 'type' => 'unknown');
        
        $canonizedSerial = $Driver->canonizeSerial($productId, $serial);
        $exRec = self::fetch(array("#serial = '[#1#]' AND #id != '[#2#]'", $canonizedSerial, $productId));
        if (!empty($exRec)) {
            $res['type'] = 'existing';
            $res['productId'] = $exRec->productId;
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
        
        $row->taskId = planning_Tasks::getLink($rec->taskId, 0);
        $row->modified = "<div class='nowrap'>" . $mvc->getFieldType('modifiedOn')->toVerbal($rec->modifiedOn);
        $row->modified .= ' ' . tr('от||by') . ' ' . crm_Profiles::createLink($rec->modifiedBy) . '</div>';
        
        $row->ROW_ATTR['class'] = ($rec->state == 'rejected') ? 'state-rejected' : (($rec->type == 'input') ? 'row-added' : (($rec->type == 'production') ? 'state-active' : 'row-removed'));
        if ($rec->state == 'rejected') {
            $row->ROW_ATTR['title'] = tr('Оттеглено от') . ' ' . core_Users::getVerbal($rec->modifiedBy, 'nick');
        }
        
        $pRec = cat_Products::fetch($rec->productId, 'measureId,code,isPublic,nameEn,name');
        $row->productId = cat_Products::getShortHyperlink($rec->productId);
        $row->measureId = cat_UoM::getShortName($pRec->measureId);
        
        $foundRec = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type, $rec->fixedAsset);
        $packagingId = (!empty($foundRec->packagingId)) ? $foundRec->packagingId : $pRec->measureId;
        $packagingName = cat_UoM::getShortName($packagingId);
        
        if (cat_UoM::fetchField($packagingId, 'type') != 'uom') {
            $row->measureId = str::getPlural($rec->quantity, $packagingName, true);
        }
        
        if ($rec->type == 'production') {
            $row->type = (!empty($packagingId)) ? tr("Произв.|* {$packagingName}") : tr('Произвеждане');
        }
        
        $row->scrappedQuantity = '';
        if (!empty($rec->scrappedQuantity)) {
            $row->scrappedQuantity = core_Type::getByName('double(smartRound)')->toVerbal($rec->scrappedQuantity);
            $row->scrappedQuantity = " (" . tr('Брак') . ": {$row->scrappedQuantity})";
        }
        $row->quantity = "<b>{$row->quantity}</b> <span style='font-weight:normal'>{$row->measureId}</span> {$row->scrappedQuantity}";
        
        if (!empty($rec->notes)) {
            $notes = $mvc->getFieldType('notes')->toVerbal($rec->notes);
            $row->productId .= "<small>{$notes}</small>";
        }
        
        if (!empty($rec->serial)) {
            $row->serial = self::getLink($rec->taskId, $rec->serial);
        }
        
        if (isset($rec->employees)) {
            $row->employees = self::getVerbalEmployees($rec->employees);
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
            $data->listTableMvc->FNC('shortUoM', 'varchar', 'tdClass=nowrap');
            $data->listTableMvc->setField('productId', 'tdClass=nowrap');
            $data->listTableMvc->FNC('modified', 'varchar', 'smartCenter');
            $data->listTableMvc->FNC('info', 'varchar', 'tdClass=task-row-info');
            unset($data->listFields['productId']);
        }
        
        $rows = &$data->rows;
        if (!count($rows)) {
            
            return;
        }
        
        $weightWarningPercent = ($data->masterData->rec->weightDeviationWarning) ? $data->masterData->rec->weightDeviationWarning : planning_Setup::get('TASK_WEIGHT_TOLERANCE_WARNING');
        $masterRec = $data->masterData->rec;
        
        foreach ($rows as $id => $row) {
            $rec = $data->recs[$id];
            
            if (!empty($row->shortUoM)) {
                $row->quantity = "<b>{$row->quantity}</b>";
                if (!empty($row->scrappedQuantity)) {
                    $hint = "Брак|* {$row->scrappedQuantity} {$row->shortUoM}";
                    $row->quantity = ht::createHint($row->quantity, $hint, 'warning', false, 'width=14px;height=14px');
                }
            }
            
            if(!empty($rec->weight)){
                $transportWeight = cat_Products::getTransportWeight($rec->productId, $rec->quantity);
                
                // Проверка има ли отклонение спрямо очакваното транспортно тегло
                if(!empty($transportWeight)){
                    $deviation = abs(round(($transportWeight - $rec->weight) / (($transportWeight + $rec->weight) / 2), 2));
                    
                    // Показване на предупреждение или нотификация, ако има разминаване в теглото
                    if($deviation > $weightWarningPercent){
                        $row->weight = ht::createHint($row->weight, 'Значително разминаване спрямо очакваното транспортно тегло', 'warning', false);
                    } elseif(!empty($masterRec->weightDeviationNotice) && $deviation > $masterRec->weightDeviationNotice){
                        $row->weight = ht::createHint($row->weight, 'Разминаване спрямо очакваното транспортно тегло', 'notice', false);
                    }
                }
            }
            
            if (isset($data->masterMvc) && $masterRec->productId != $rec->productId) {
                $row->info = "{$row->productId}";
            }
        }
    }
    
    
    /**
     * Показва вербалното име на служителите
     *
     * @param string $employees - кейлист от служители
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
                $btnName = empty($data->masterData->rec->packagingId) ? 'Произвеждане' : "Произв.|* " . tr(cat_Uom::getTitleById(($data->masterData->rec->packagingId)));
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
     * Подготвя детайла
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
        if(Mode::is('getLinkedFiles') || Mode::is('inlineDocument')) {
            
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
            unset($data->listFields['modifiedOn']);
            unset($data->listFields['modifiedBy']);
            unset($data->listFields['productId']);
        }
        $data->listFilter->showFields = 'serial';
        
        // Ако има използвани служители, добавят се за филтриране
        $usedFixedAssets = self::getResourcesInDetails($data->masterId, 'fixedAsset');
        if(count($usedFixedAssets)){
            $data->listFilter->setOptions('fixedAsset', array('' => '') + $usedFixedAssets);
            $data->listFilter->showFields .= ",fixedAsset";
        }
        
        // Ако има използвани служители, добавят се за филтриране
        $usedEmployeeIds = self::getResourcesInDetails($data->masterId, 'employees');
        if(count($usedEmployeeIds)){
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
        if (($action == 'add' || $action == 'reject' || $action == 'restore' || $action == 'edit' || $action == 'delete') && isset($rec->taskId)) {
            $state = $mvc->Master->fetchField($rec->taskId, 'state');
            
            if ($state != 'active' && $state != 'waiting' && $state != 'wakeup') {
                $requiredRoles = 'no_one';
            }
        }
        
        // Трябва да има поне един артикул възможен за добавяне
        if ($action == 'add' && isset($rec->type) && $rec->type != 'product' && $rec->type != 'start') {
            if ($requiredRoles != 'no_one') {
                $pOptions = planning_ProductionTaskProducts::getOptionsByType($rec->taskId, $rec->type);
                if (!count($pOptions)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'edit' && isset($rec)) {
            if ($rec->type != 'production' || $rec->state == 'rejected') {
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
        $query->where("#modifiedOn >= '{$timeline}' AND #norm IS NOT NULL");
        $query->EXT('indTimeAllocation', 'planning_Tasks', 'externalName=indTimeAllocation,externalKey=taskId');
        $query->EXT('indPackagingId', 'planning_Tasks', 'externalName=indPackagingId,externalKey=taskId');
        
        
        $iRec = hr_IndicatorNames::force('Време', __CLASS__, 1);
        $classId = planning_Tasks::getClassId();
        $indicatorId = $iRec->id;
        
        while ($rec = $query->fetch()) {
            
            // Ако няма служители, пропуска се
            $persons = keylist::toArray($rec->employees);
            if (!count($persons)) {
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
            $timePerson = ($rec->indTimeAllocation == 'individual') ? $quantity * $rec->norm : (($quantity * $rec->norm) / count($persons));
            
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
     * Изпълнява се преди възстановяването на документа
     */
    protected static function on_BeforeRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        $limit = '';
        if (!$mvc->checkLimit($rec, $limit)) {
            $limit = core_Type::getByName('double(smartRound)')->toVerbal($limit);
            core_Statuses::newStatus("Не може да се възстанови, защото ще се надвиши максималното количество от|*: <b>{$limit}</b>", 'error');
            
            return false;
        }
    }
}
