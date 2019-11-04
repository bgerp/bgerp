<?php


/**
 * Клас 'planning_ProductionTaskProducts'
 *
 * Артикули към производствените операции
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
class planning_ProductionTaskProducts extends core_Detail
{
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Заглавие
     */
    public $title = 'Детайл на производствените операции';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'type,productId,plannedQuantity=Количества->Планирано,limit=Количества->Макс.,totalQuantity=Количества->Изпълнено,packagingId=Количества->Мярка,storeId,indTime=Норма,totalTime=Общо';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'indTime,totalTime,limit,storeId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_AlignDecimals2, planning_plg_ReplaceEquivalentProducts, plg_SaveAndNew, plg_Modified, plg_Created, planning_Wrapper';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'taskPlanning, ceo';
    
    
    /**
     * Кой има право да променя взаимно заменяемите артикули?
     */
    public $canReplaceproduct = 'taskPlanning, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'taskPlanning, ceo';
    
    
    /**
     * Кой има право да добавя артикули към активна операция?
     */
    public $canAddtoactive = 'taskPlanning, ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'taskPlanning,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canList = 'no_one';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'taskId';
    
    
    /**
     * Активен таб на менюто
     */
    public $currentTab = 'Операции';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'totalQuantity';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('taskId', 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory,caption=Операция');
        $this->FLD('type', 'enum(input=Влагане,waste=Отпадък,production=Произвеждане)', 'caption=За,remember,silent,input=hidden');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=10,forceAjax,titleFld=name)', 'class=w100,silent,mandatory,caption=Артикул,removeAndRefreshForm=packagingId|limit|indTime,tdClass=productCell leftCol wrap');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=shortName)', 'mandatory,caption=Пр. единица,tdClass=small-field nowrap');
        $this->FLD('plannedQuantity', 'double(smartRound,Min=0)', 'mandatory,caption=Планирано к-во');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад');
        $this->FLD('quantityInPack', 'double', 'mandatory,input=none');
        $this->FLD('totalQuantity', 'double(smartRound)', 'caption=Количество->Изпълнено,input=none,notNull');
        $this->FLD('indTime', 'time(noSmart,decimals=2)', 'caption=Норма');
        $this->FLD('limit', 'double(min=0)', 'caption=Макс. к-во,input=none');
        $this->FLD('totalTime', 'time(noSmart)', 'caption=Норма->Общо,input=none');
        
        $this->setDbUnique('taskId,productId');
        $this->setDbIndex('taskId,productId,type');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $form->setDefault('type', 'input');
        $masterRec = planning_Tasks::fetch($data->masterId);
        
        // Ако има тип
        if (isset($rec->type)) {
            $meta = ($rec->type == 'input') ? 'canConvert' : (($rec->type == 'waste') ? 'canStore,canConvert' : 'canManifacture');
            $onlyInGroups = ($rec->type == 'waste') ? cat_Groups::getKeylistBySysIds('waste') : null;
            $form->setFieldTypeParams('productId', array('hasProperties' => $meta, 'groups' => $onlyInGroups));
        }
        
        if (isset($rec->productId)) {
            $packs = cat_Products::getPacks($rec->productId);
            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', key($packs));
            
            $productInfo = cat_Products::getProductInfo($rec->productId);
            if (!isset($productInfo->meta['canStore'])) {
                $form->setField('storeId', 'input=none');
                
                if ($rec->type == 'input') {
                    $form->setField('limit', 'input');
                    if (isset($masterRec->fixedAssets) && empty($rec->id)) {
                        
                        // Задаване на дефолтен лимит ако има
                        $norm = planning_AssetGroups::getNorm($masterRec->fixedAssets, $rec->productId);
                        if (array_key_exists($rec->productId, $norm)) {
                            $form->setDefault('limit', $norm[$rec->productId]->limit);
                            $form->setDefault('indTime', $norm[$rec->productId]->indTime);
                        }
                    }
                }
            } elseif (empty($rec->id)) {
                $form->setDefault('storeId', $masterRec->storeId);
            }
            
            // Поле за бързо добавяне на прогрес, ако може
            if (empty($rec->id) && $rec->type != 'waste' && planning_ProductionTaskDetails::haveRightFor('add', (object) array('taskId' => $masterRec->id))) {
                $caption = ($rec->type == 'input') ? 'Вложено' : 'Произведено';
                $form->FLD('inputedQuantity', 'double(Min=0)', "caption={$caption},before=storeId");
            }
            
            $shortUomId = cat_Products::fetchField($masterRec->productId, 'measureId');
            $shortUom = cat_UoM::getShortName($shortUomId);
            $unit = tr('за') . ' ' . core_Type::getByName('double(smartRound)')->toVerbal($masterRec->plannedQuantity) . ' ' . $shortUom;
            $unit = str_replace('&nbsp;', ' ', $unit);
            $form->setField('plannedQuantity', array('unit' => $unit));
            
            if(isset($rec->id)){
                $form->setReadOnly('productId');
                if($data->action != 'replaceproduct'){
                    $form->setReadOnly('packagingId');
                }
                
                if (!haveRole('ceo,planningMaster')) {
                    $form->setReadOnly('indTime');
                }
            }
        } else {
            $form->setField('packagingId', 'input=none');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        
        if ($form->isSubmitted()) {
            if ($rec->type == 'waste') {
                $selfValue = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $rec->productId);
                if (!isset($selfValue)) {
                    $form->setWarning('productId', 'Отпадъкът няма себестойност');
                }
            }
            
            $pInfo = cat_Products::getProductInfo($rec->productId);
            $rec->quantityInPack = ($pInfo->packagings[$rec->packagingId]) ? $pInfo->packagings[$rec->packagingId]->quantity : 1;
            
            // Проверка дали артикула може да бъде избран
            $msg = $error = null;
            if (!self::canAddProductToTask($rec, $msg, $error)) {
                $method = ($error === true) ? 'setError' : 'setWarning';
                $form->{$method}('productId', $msg);
            }
            
            if (isset($rec->limit)) {
                if ($rec->plannedQuantity > $rec->limit) {
                    $form->setError('plannedQuantity,limit', 'Планираното количество е повече от зададения лимит');
                }
                
                if ($rec->inputedQuantity > $rec->limit) {
                    $caption = ($rec->type == 'input') ? 'Вложеното' : (($rec->type == 'waste') ? 'Отпадъкът' : 'Произведеното');
                    $form->setError('inputedQuantity,limit', "{$caption} е повече от зададения лимит");
                }
            }
        }
    }
    
    
    /**
     * Подготвя детайла
     */
    public function prepareDetail_($data)
    {
        if(!Mode::is('taskInTerminal')){
            $data->TabCaption = 'Артикули';
            $data->Tab = 'top';
        }
        
        parent::prepareDetail_($data);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->productId = cat_Products::getShortHyperlink($rec->productId);
        $row->ROW_ATTR['class'] = ($rec->type == 'input') ? 'row-added' : (($rec->type == 'waste') ? 'row-removed' : 'state-active');
        deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
        
        if (isset($rec->storeId)) {
            $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec->taskId)) {
            $state = $mvc->Master->fetchField($rec->taskId, 'state');
            if (in_array($state, array('active', 'waiting', 'wakeup', 'draft'))) {
                if ($action == 'add') {
                    $requiredRoles = $mvc->getRequiredRoles('addtoactive', $rec);
                }
            } else {
                $requiredRoles = 'no_one';
            }
        }
        
        if (($action == 'delete') && isset($rec->taskId)) {
            if (planning_ProductionTaskDetails::fetchField("#taskId = {$rec->taskId} AND #productId = {$rec->productId}")) {
                $requiredRoles = 'no_one';
            }
        }
        
        if($action == 'replaceproduct' && isset($rec)){
            if($rec->type == 'production' || planning_ProductionTaskDetails::fetch("#taskId = {$rec->taskId} AND #productId = {$rec->productId}")){
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Обновяване на изпълненото количество
     *
     * @param int    $taskId    - ид на задача
     * @param int    $productId - ид на артикул
     * @param string $type      - вид на действието
     *
     * @return void
     */
    public static function updateTotalQuantity($taskId, $productId, $type)
    {
        $rec = self::fetch("#taskId = {$taskId} AND #productId = {$productId} AND #type = '{$type}'");
        if (empty($rec)) {
            
            return;
        }
        
        $rec->totalQuantity = $rec->totalTime = 0;
        $query = planning_ProductionTaskDetails::getQuery();
        $query->where("#taskId = {$taskId} AND #productId = {$productId} AND #type = '{$type}' AND #state != 'rejected'");
        $query->show('quantity,norm');
        
        while ($dRec = $query->fetch()) {
            $rec->totalQuantity += $dRec->quantity;
            $rec->totalTime += ($dRec->norm * $dRec->quantity);
        }
        
        self::save($rec, 'totalQuantity,totalTime');
    }
    
    
    /**
     * Намира всички допустими артикули от дадения тип за една операция
     *
     * @param int       $taskId
     * @param string    $type
     *
     * @return array
     */
    public static function getOptionsByType($taskId, $type)
    {
        $taskRec = planning_Tasks::fetchRec($taskId);
        $usedProducts = $options = array();
        expect(in_array($type, array('input', 'waste', 'production')));
        
        if ($type == 'production') {
            $options[$taskRec->productId] = cat_Products::getTitleById($taskRec->productId, false);
        }
        
        $query = self::getQuery();
        $query->where("#taskId = {$taskId}");
        $query->where("#type = '{$type}'");
        $query->show('productId');
        while ($rec = $query->fetch()) {
            $options[$rec->productId] = cat_Products::getTitleById($rec->productId, false);
            $usedProducts[$rec->productId] = $rec->productId;
        }
        
        if ($type == 'input') {
            
            // Всички избрани вложими артикули от задачи към същото задание
            $tQuery = planning_Tasks::getQuery();
            $tQuery->notIn('productId', array_keys($options));
            $tQuery->where("#originId = {$taskRec->originId} AND #inputInTask = {$taskRec->id} AND #state != 'draft' AND #state != 'rejected' AND #state != 'pending'");
            $tQuery->show('productId');
            
            $taskOptions = array();
            while ($tRec = $tQuery->fetch()) {
                $taskOptions[$tRec->productId] = cat_Products::getTitleById($tRec->productId, false);
                $usedProducts[$tRec->productId] = $tRec->productId;
            }
            
            if (count($taskOptions)) {
                $options += array('t' => (object) array('group' => true, 'title' => tr('Задачи'))) + $taskOptions;
            }
            
            // Ако има избрано оборудване
            if (!empty($taskRec->fixedAssets)) {
                
                // Ако има добавят се с групата на оборудването в опциите
                $norms = planning_AssetResourcesNorms::getNormOptions($taskRec->fixedAssets, $usedProducts);
                if (count($norms)) {
                    $options += $norms;
                }
            }
        }
        
        return $options;
    }
    
    
    /**
     * Информация за артикула в операцията
     *
     * @param mixed    $taskId    - ид или запис на операция
     * @param int      $productId - ид на артикул
     * @param string   $type      - вид на действието
     * @param int|NULL $assetId   - конкретно оборудване
     *
     * @return stdClass
     *                  o productId       - ид на артикула
     *                  o packagingId     - ид на опаковката
     *                  o quantityInPack  - к-во в опаковката
     *                  o plannedQuantity - планирано к-во
     *                  o totalQuantity   - изпълнено к-во
     *                  o indTime         - норма
     *                  o limit           - лимит, ако има
     */
    public static function getInfo($taskId, $productId, $type, $assetId = null)
    {
        expect(in_array($type, array('input', 'waste', 'production')));
        
        // Ако артикула е същия като от операцията, връща се оттам
        $taskRec = planning_Tasks::fetchRec($taskId, 'totalQuantity,fixedAssets,productId,indTime,packagingId,plannedQuantity,measureId');
        if ($taskRec->productId == $productId) {
            if(empty($taskRec->packagingId)){
                $taskRec->packagingId = $taskRec->measureId;
            }
            
            return $taskRec;
        }
        
        // Ако има запис в артикули за него, връща се оттам
        $query = self::getQuery();
        $query->where("#taskId = {$taskRec->id} AND #productId = {$productId} AND #type = '{$type}'");
        $query->show('productId,indTime,packagingId,plannedQuantity,totalQuantity,limit');
        
        if ($rec = $query->fetch()) {
            
            return $rec;
        }
        
        // Ако е влагане и артикула в избран като вложим за тая операция, връща се оттам
        if ($type == 'input') {
            $tQuery = planning_Tasks::getQuery();
            $tQuery->where("#productId = {$productId} AND #inputInTask = {$taskRec->id} AND #state != 'rejected' AND #state != 'closed' AND #state != 'draft' AND #state != 'pending'");
            $tQuery->show('productId,packagingId,plannedQuantity,totalQuantity');
            if ($tRec = $tQuery->fetch()) {
                $tRec->totalQuantity = (!empty($tRec->totalQuantity)) ? $tRec->totalQuantity : 0;
                
                return $tRec;
            }
        }
        
        if (isset($assetId)) {
            $normRec = planning_AssetResources::getNormRec($assetId, $productId);
            if (!empty($normRec)) {
                
                return $normRec;
            }
        } elseif (isset($taskRec->fixedAssets)) {
            $norms = planning_AssetGroups::getNorm($taskRec->fixedAssets, $productId);
            if (array_key_exists($productId, $norms)) {
                
                return $norms[$productId];
            }
        }
        
        return false;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        if (!empty($rec->inputedQuantity)) {
            $dRec = (object) array('taskId' => $rec->taskId, 'productId' => $rec->productId, 'type' => $rec->type, 'quantity' => $rec->inputedQuantity);
            planning_ProductionTaskDetails::save($dRec);
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Документа не може да се създава  в нова нишка, ако е възоснова на друг
        if (!empty($data->toolbar->buttons['btnAdd'])) {
            $data->toolbar->removeBtn('btnAdd');
            
            if (cat_Products::getByProperty('canManifacture', null, 1)) {
                if ($mvc->haveRightFor('add', (object) array('taskId' => $data->masterId, 'type' => 'production'))) {
                    $data->toolbar->addBtn('За произвеждане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'production', 'ret_url' => true), false, 'ef_icon = img/16/package.png,title=Добавяне на производим артикул');
                }
            }
            
            if (cat_Products::getByProperty('canConvert', null, 1)) {
                if ($mvc->haveRightFor('add', (object) array('taskId' => $data->masterId, 'type' => 'input'))) {
                    $data->toolbar->addBtn('За влагане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'input', 'ret_url' => true), false, 'ef_icon = img/16/wooden-box.png,title=Добавяне на вложим артикул');
                }
            }
            
            if (cat_Products::getByProperty('canStore,canConvert', null, 1, cat_Groups::getKeylistBySysIds('waste'))) {
                if ($mvc->haveRightFor('add', (object) array('taskId' => $data->masterId, 'type' => 'waste'))) {
                    $data->toolbar->addBtn('За отпадък', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'waste', 'ret_url' => true), false, 'ef_icon = img/16/recycle.png,title=Добавяне на отпаден артикул');
                }
            }
        }
        
        // Ако се показва в терминала, колонката за артикул да е в отделен ред
        if(Mode::is('taskInTerminal')){
            $data->listFields['productId'] = '@';
        }
    }
    
    
    /**
     * Преди подготовка на заглавието на формата
     */
    protected static function on_BeforePrepareEditTitle($mvc, &$res, $data)
    {
        $data->singleTitle = ($data->form->rec->type == 'input') ? 'артикул за влагане' : (($data->form->rec->type == 'waste') ? 'отпадъчен артикул' : 'заготовка');
    }
    
    
    /**
     * Помощна ф-я проверяваща може ли артикула да бъде избран
     *
     * @param int         $taskId
     * @param int         $productId
     * @param string|NULL $msg
     * @param bool|NULL   $error
     *
     * @return bool
     */
    private static function canAddProductToTask($rec, &$msg = null, &$error = null)
    {
        $taskRec = planning_Tasks::fetch($rec->taskId);
        
        // Ако има норма за артикула
        if (isset($taskRec->fixedAssets)) {
            $norm = planning_AssetGroups::getNorm($taskRec->fixedAssets, $rec->productId);
            if (array_key_exists($rec->productId, $norm)) {
                if ($rec->indTime != $norm[$rec->productId]->indTime) {
                    $indTime = core_Type::getByName('time(noSmart)')->toVerbal($norm[$rec->productId]->indTime);
                    $msg = "Нормата се различава от очакваната|* <b>{$indTime}</b>";
                    $error = 'FALSE';
                    
                    return false;
                }
            }
        }
        
        // Ако е избран да се влага от друга задача
        $inTaskId = planning_Tasks::fetchField("#inputInTask = {$taskRec->id} AND #productId = {$rec->productId} AND (#state = 'active' || #state = 'wakeup' || #state = 'stopped' || #state = 'closed')");
        if (!empty($inTaskId)) {
            $inTaskId = planning_Tasks::getLink($inTaskId, 0);
            $msg = "Артикулът е избран да се влага в операцията от|* <b>{$inTaskId}</b>";
            $error = 'FALSE';
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        if(!Mode::is('taskInTerminal')){
            $data->listTableMvc->setField('packagingId', 'smartCenter');
            $data->listTableMvc->setField('plannedQuantity', 'smartCenter');
            $data->listTableMvc->setField('totalQuantity', 'smartCenter');
            $data->listTableMvc->setField('indTime', 'smartCenter');
            $data->listTableMvc->setField('totalTime', 'smartCenter');
        }
    }
}
