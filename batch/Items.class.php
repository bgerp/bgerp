<?php


/**
 * Партидни наличности
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class batch_Items extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Наличности';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'batch_Wrapper, plg_AlignDecimals2, plg_Search, plg_Sorting, plg_State2';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'productId, batch, storeId';


    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'code=Код, productId, storeId, batch, measureId=Мярка, quantity, state';


    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Наличност';


    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'batchMaster,ceo';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'batch,ceo';


    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';


    /**
     * Детайла, на модела
     */
    public $details = 'batch_Movements';


    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'batch/tpl/SingleLayoutItem.shtml';


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     * @var string
     */
    public $hideListFieldsIfEmpty = 'nullifiedDate';


    /**
     * ИД на склад за незавършено производство
     */
    const WORK_IN_PROGRESS_ID = -1;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=canStore,hasnotProperties=generic,maxSuggestions=100,forceAjax)', 'caption=Артикул,mandatory,silent');
        $this->FLD('batch', 'varchar(128)', 'caption=Партида,mandatory');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад,mandatory');
        $this->FLD('quantity', 'double(smartRound)', 'caption=Наличност');
        $this->FLD('nullifiedDate', 'datetime(format=smartTime)', 'caption=Изчерпано');

        $this->setDbUnique('productId,batch,storeId');
        $this->setDbIndex('productId');
        $this->setDbIndex('storeId');
        $this->setDbIndex('productId,storeId');
        $this->setDbIndex('nullifiedDate');
    }


    /**
     * Връща наличното количество от дадена партида
     *
     * @param int    $productId - артикул
     * @param string $batch     - партида
     * @param int    $storeId   - склад
     *
     * @return float $quantity - к-во на партидата в склада
     */
    public static function getQuantity($productId, $batch, $storeId)
    {
        $quantity = self::fetchField(array("#productId = {$productId} AND #batch = '[#1#]' AND #storeId = '{$storeId}'", $batch), 'quantity');
        
        $quantity = empty($quantity) ? 0 : $quantity;
        
        return $quantity;
    }
    
    
    /**
     * Форсира запис за партида
     *
     * @param int    $productId - ид на артикул
     * @param string $batch     - партиден номер
     * @param int    $storeId   - ид на склад
     *
     * @return int $id - ид на форсирания запис
     */
    public static function forceItem($productId, $batch, $storeId)
    {
        expect($productId);
        expect($batch);
        expect($storeId);
        
        // Имали запис за тази партида
        if ($rec = self::fetch(array("#productId = '{$productId}' AND #batch = '[#1#]' AND #storeId = '{$storeId}'", $batch))) {
            batch_Features::sync($rec->id);
            
            // Връщаме ид-то на записа
            return $rec->id;
        }
        
        // Ако няма записваме го
        $rec = (object) array('productId' => $productId, 'batch' => $batch, 'storeId' => $storeId);
        $id = self::save($rec);
        batch_Features::sync($rec->id);
        
        // Връщаме ид-то на записа
        return $id;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        $row->storeId = ($rec->storeId == batch_Items::WORK_IN_PROGRESS_ID) ? planning_WorkInProgress::getHyperlink() : store_Stores::getHyperlink($rec->storeId, true);

        $measureId = cat_Products::fetchField($rec->productId, 'measureId');
        $row->measureId = cat_UoM::getShortName($measureId);
        
        if ($Definition = batch_Defs::getBatchDef($rec->productId)) {
            $row->batch = $Definition->toVerbal($rec->batch);
        }
        
        if (isset($fields['-single'])) {
            $row->state = $mvc->getFieldType('state')->toVerbal($rec->state);
        }
        
        if (batch_Movements::haveRightFor('list')) {
            $link = array('batch_Movements', 'list', 'batch' => $rec->batch);
            if (isset($fields['-list'])) {
                $link += array('productId' => $rec->productId, 'storeId' => $rec->storeId);
            }
            $row->batch = ht::createLink($row->batch, $link);
        }
        
        if (isset($rec->featureId)) {
            $featRec = batch_Features::fetch($rec->featureId, 'name,classId,value');
            $row->featureId = cls::get($featRec->classId)->toVerbal($featRec->value);
        }

        $row->code = cat_Products::getVerbal($rec->productId, 'code');
        $row->productId = cat_Products::getVerbal($rec->productId, 'name');
        $icon = cls::get('cat_Products')->getIcon($rec->productId);
        $row->productId = ht::createLink($row->productId, cat_Products::getSingleUrlArray($rec->productId), false, "ef_icon={$icon}");
    }


    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     *
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);
        if (!$rec) return;

        // Ъпдейтваме к-та спрямо движенията по партидата
        $quantity = 0;
        $dQuery = batch_Movements::getQuery();
        $dQuery->where("#itemId = {$rec->id}");
        while ($dRec = $dQuery->fetch()) {
            
            // Ако операцията е 'влизане' увеличаваме к-то
            if ($dRec->operation == 'in') {
                $quantity += $dRec->quantity;
            } elseif ($dRec->operation == 'out') {
                
                // Ако операцията е 'излизане' намаляваме к-то
                $quantity -= $dRec->quantity;
            }
        }
        
        // Опресняваме количеството
        $measureId = cat_Products::fetchField($rec->productId, 'measureId');
        $round = cat_UoM::fetchField($measureId, 'round');
        $rec->quantity = round($quantity, $round);
        
        if ($rec->quantity == 0) {
            $rec->nullifiedDate = dt::now();
        } else {
            if (isset($rec->nullifiedDate)) {
                $rec->nullifiedDate = null;
            }
        }
        
        if ($rec->quantity != 0 && $rec->state != 'active') {
            $rec->state = 'active';
        }
        
        $this->save_($rec);
    }
    
    
    /**
     * Крон метод за затваряне на старите партиди
     */
    public function cron_closeOldBatches()
    {
        $query = self::getQuery();
        $query->XPR('quantityCalc', 'double', 'ROUND(#quantity, 4)');
        $query->where("#quantityCalc = 0 AND #state != 'closed'");
        $before = core_Packs::getConfigValue('batch', 'BATCH_CLOSE_OLD_BATCHES');
        $before = dt::addSecs(-1 * $before, dt::now());
        $query->where("#nullifiedDate <= '{$before}'");
        while ($rec = $query->fetch()) {
            $rec->state = 'closed';
            $this->save($rec, 'state');
        }
    }


    /**
     * Задаване на поле за филтър по складове+незав. произв
     *
     * @param core_Form $form
     * @param string $storeFieldName
     * @return void
     */
    public static function setStoreFilter($form, $storeFieldName = 'storeId')
    {
        $storeOptions = array(batch_Items::WORK_IN_PROGRESS_ID => 'Незавършено производство');
        $storeQuery = store_Stores::getQuery();
        $storeQuery->where("#state != 'rejected'");
        while($storeRec = $storeQuery->fetch()) {
            $storeOptions[$storeRec->id] = $storeRec->name;
        }

        if(!$form->getField($storeFieldName, false)){
            $form->FLD($storeFieldName, 'varchar(nullIfEmpty)', 'placeholder=Всички складове,caption=Склад,forceField');
        } else {
            $form->setFieldType($storeFieldName, "varchar(nullIfEmpty)");
            $form->setField($storeFieldName, "placeholder=Всички складове");
        }
        $form->setOptions($storeFieldName, array('' => '') + $storeOptions);
    }


    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->view = 'horizontal';

        self::setStoreFilter($data->listFilter, 'store');
        $data->listFilter->FLD('filterState', 'varchar', 'placeholder=Състояние');
        $options = arr::make('active=Активни,closed=Затворени,all=Всички', true);
        
        // Кои са инсталираните партидни дефиниции
        $definitions = core_Classes::getOptionsByInterface('batch_BatchTypeIntf');
        foreach ($definitions as $def) {
            $Def = cls::get($def);
            
            // Какви опции има за филтъра
            $defOptions = $Def->getListFilterOptions();
            
            // Добавяне към ключа на опцията името на класа за да се знае от къде е дошла
            $newOptions = array();
            foreach ($defOptions as $k => $v) {
                $k = get_class($Def) . "::{$k}";
                $newOptions[$k] = $v;
            }
            
            // Обединяване на опциите
            $options = array_merge($options, $newOptions);
        }
        
        // Сетване на новите опции
        $data->listFilter->setOptions('filterState', $options);
        $data->listFilter->setDefault('filterState', 'active');
        if($mvc instanceof rack_ProductsByBatches){
            $data->listFilter->showFields = 'search,productId,filterState';
        } else {
            $data->listFilter->showFields = 'search,store,productId,filterState';
        }
        $data->listFilter->input();
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        if ($filter = $data->listFilter->rec) {

            // Филтрираме по склад
            if (!empty($filter->store)) {
                $data->query->where("#storeId = '{$filter->store}'");
                unset($data->listFields['storeId']);
                if($filter->store == batch_Items::WORK_IN_PROGRESS_ID){
                    if(planning_WorkInProgress::haveRightFor('list')){
                        $selectedStoreName = ht::createLink('Незавършено производство', array('planning_WorkInProgress', 'list'));
                    } else {
                        $selectedStoreName = tr('Незавършено производство');
                    }
                    $selectedStoreName = "<b>{$selectedStoreName}</b>";
                } else {
                    $selectedStoreName = tr("склад|* <b>") . store_Stores::getHyperlink($filter->store, true) . "</b>";
                }

                $data->title = "|Наличности по партиди в|* {$selectedStoreName}";
            }

            if (isset($filter->productId)) {
                $data->query->where("#productId = {$filter->productId}");
                unset($data->listFields['productId']);
            }

            // Филтрираме по състояние
            if (isset($filter->filterState)) {
                if (strpos($filter->filterState, '::')) {
                    list($definition, $filterValue) = explode('::', $filter->filterState);
                    $Def = cls::get($definition);
                    $featureCaption = null;
                    $Def->filterItemsQuery($data->query, $filterValue, $featureCaption);
                    
                    if (!empty($featureCaption)) {
                        $data->listFields['featureId'] = $featureCaption;
                    }
                } elseif ($filter->filterState != 'all') {
                    $data->query->where("#state = '{$filter->filterState}'");
                }
            }
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        if (!countR($data->rows)) {
            
            return;
        }
        
        foreach ($data->rows as $id => &$row) {
            $row->quantity = ht::styleNumber($row->quantity, $data->recs[$id]->quantity);
        }
    }
    
    
    /**
     * Връща всички складируеми артикули с дефинирани видове партидност
     *
     * @param boolean $showNames - дали да се показват имената
     * @return array $storable - масив с артикули
     */
    public static function getProductsWithDefs($showNames = true)
    {
        $storable = core_Cache::get('batch_Defs', "products" . (int)$showNames);
        if (!$storable) {
            $storable = array();
            $dQuery = batch_Defs::getQuery();
            $dQuery->where('#productId IS NOT NULL');
            $dQuery->show('productId');
            while ($dRec = $dQuery->fetch()) {
                if($showNames){
                    $pRec = cat_Products::fetch($dRec->productId, 'name,isPublic,code,nameEn');
                    if($pRec) {
                        $storable[$dRec->productId] = cat_Products::getRecTitle($pRec, false);
                    }
                } else {
                    $storable[$dRec->productId] = $dRec->productId;
                }
            }
            core_Cache::set('batch_Defs', "products" . (int)$showNames, $storable, 60);
        }
        
        return $storable;
    }
    
    
    /**
     * Връща всички складируеми артикули с дефинирани видове партидност
     *
     * @param int      $productId - артикул
     * @param int|NULL $storeId   - склад
     * @param bool     $pureText  - дали да са само текст
     *
     * @return array $res       - масив с артикули
     */
    public static function getBatches($productId, $storeId = null, $pureText = false)
    {
        $res = array();
        
        $query = self::getQuery();
        $query->where("#productId = {$productId}");
        $query->where("#state != 'closed' AND #quantity != 0");
        if (isset($storeId)) {
            $query->where("#storeId = '{$storeId}'");
        }
        
        $query->show('batch,productId');
        while ($rec = $query->fetch()) {
            $Def = batch_Defs::getBatchDef($rec->productId);
            $value = $Def->toVerbal($rec->batch);
            $value = ($pureText) ? strip_tags($value) : $value;
            
            $res[$rec->batch] = $value;
        }
        
        return $res;
    }
    
    
    /**
     * Подготовка на наличните партиди за един артикул
     *
     * @param stdClass $data
     *
     * @return void
     */
    public function prepareBatches(&$data)
    {
        // Ако артикула няма партидност, не показваме таба
        $canStore = $data->masterData->rec->canStore;
        $definition = batch_Defs::getBatchDef($data->masterId);
        if ($canStore != 'yes' || !$definition) {
            $data->hide = true;
            
            return;
        }
        
        // Име на таба
        $data->definitionRec = batch_Defs::fetch("#productId = '{$data->masterId}'");
        if (batch_Defs::haveRightFor('delete', $data->definitionRec->id)) {
            $retUrl = array($data->masterMvc, 'single', $data->masterId);
            $data->deleteBatchUrl = array('batch_Defs', 'delete', $data->definitionRec->id, 'ret_url' => $retUrl);
        }
        
        $data->TabCaption = 'Партиди';
        $data->Tab = 'top';
        $data->recs = $data->rows = array();
        
        $attr = array('title' => 'Хронология на движенията');
        $attr = ht::addBackgroundIcon($attr, 'img/16/clock_history.png');
        
        // Подготвяме формата за филтър по склад
        $form = cls::get('core_Form');
        $form->FLD("storeId{$data->masterId}", 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,silent');
        $form->FLD("state{$data->masterId}", 'enum(active=Активни,closed=Затворени,inStock=Налични,notInStock=Без наличност,all=Всички)', 'caption=Състояние,silent');
        $form->FLD("filter{$data->masterId}", 'enum(asc=Подредба (Възх.),desc=Подредба (Низх.))', 'caption=Подредба,silent');
        batch_Items::setStoreFilter($form, "storeId{$data->masterId}");
        $form->setDefault("state{$data->masterId}", 'active');
        $form->setDefault("filter{$data->masterId}", 'asc');

        $form->input(null, 'silent');
        $form->view = 'horizontal';
        $form->setAction(getCurrentUrl());
        $form->toolbar->addSbBtn('', 'default', 'id=filter', 'ef_icon=img/16/funnel.png');
        
        // Инпутваме формата
        $form->input();
        $data->form = $form;

        // Намираме наличните партиди на артикула
        $query = $this->getQuery();
        $query->where("#productId = {$data->masterId}");
        $query->orderBy('state');
        
        // Ако филтрираме по склад, оставяме само тези в избрания склад
        if (isset($data->form->rec->{"storeId{$data->masterId}"})) {
            $data->storeId = $data->form->rec->{"storeId{$data->masterId}"};
            $query->where("#storeId = '{$data->storeId}'");
        }


        $filterRec = $data->form->rec;
        $filterState = $filterRec->{"state{$data->masterId}"};
        if ($filterState != 'all') {
            if($filterState == 'inStock'){
                $query->where("#quantity > 0");
            } elseif($filterState == 'notInStock'){
                $query->where("#quantity <= 0");
            } else {
                $query->where("#state = '{$filterRec->{"state{$data->masterId}"}}'");
                $query->orderBy('id', 'desc');
            }
        }
        $data->recs = $query->fetchAll();

        // Добавяне на наличните к-ва без партида
        $storeQuery = store_Products::getQuery();
        $storeQuery->where("#productId = {$data->masterId}");
        $storeQuery->orderBy('storeId', 'ASC');
        if(isset($data->storeId)){
            $storeQuery->where("#storeId = {$data->storeId}");
        }

        // Към складовите наличности се добавя и незавършеното производство
        $stores = $storeQuery->fetchAll();
        if($workInProgressRec = planning_WorkInProgress::fetch("#productId = {$data->masterId}")) {
            if(empty($data->storeId) || $data->storeId == batch_Items::WORK_IN_PROGRESS_ID){
                $workInProgressRec->storeId = batch_Items::WORK_IN_PROGRESS_ID;
                $stores[$workInProgressRec->storeId] = $workInProgressRec;
            }
        }

        $newRecs = array();
        foreach ($stores as $storeRec){
            $onBatches = $count = 0;
            $filtered = array_filter($data->recs, function($a) use(&$onBatches, &$count, $storeRec) {
                if($a->storeId == $storeRec->storeId){
                    if(!empty($a->batch)) {
                        $onBatches += $a->quantity; $count++;
                    }
                    return true;
                }

                return false;
            });

            $orderBy = !empty($filterRec->{"filter{$data->masterId}"}) ? $filterRec->{"filter{$data->masterId}"} : 'asc';
            arr::sortObjects($filtered, 'batch', $orderBy, 'natural');

            if($count > 1){
                $filtered["-{$storeRec->storeId}batches"] = (object)array('storeId' => $storeRec->storeId,
                                                                          'productId' => $data->masterId,
                                                                          'quantity' => $onBatches,
                                                                          'state' => 'active',
                                                                          'batch' => -1);
            }

            $withoutBatch = round($storeRec->quantity - $onBatches, 4);
            if($withoutBatch != 0){
                $filtered["-{$storeRec->storeId}nobatch"] = (object)array('storeId' => $storeRec->storeId,
                    'productId' => $data->masterId,
                    'quantity' => $withoutBatch,
                    'state' => 'active',
                    'batch' => -2);
            }

            $newRecs += $filtered;
        }

        $data->recs = $newRecs;

        // Подготвяме страницирането
        $url = getCurrentUrl();
        $url["storeId{$data->masterId}"] = $form->rec->{"storeId{$data->masterId}"};
        $url["state{$data->masterId}"] = $form->rec->{"state{$data->masterId}"};
        $url["filter{$data->masterId}"] = $form->rec->{"filter{$data->masterId}"};
        $url["#"] = "batchBlock{$data->masterId}";

        $pager = cls::get('core_Pager', array('itemsPerPage' => 20, 'url' => toUrl($url)));
        $pager->setPageVar($data->masterMvc->className, $data->masterId);
        $pager->itemsCount = countR($data->recs);
        $data->pager = $pager;

        // Обръщаме записите във вербален вид
        foreach ($data->recs as $index => $rec) {
            
            // Пропускане на записите, които не трябва да са на тази страница
            if (!$pager->isOnPage()) {
                continue;
            }
            
            // Вербално представяне на записа
            $row = $this->recToVerbal($rec);
            if($rec->batch == -1){
                $row->batch = "<i style='float:left;color:green'>" . tr('Общо по партиди') . "</i>";
            } elseif($rec->batch == -2){
                $row->batch = "<i style='float:left;color:green'>" . tr('Без партида') . "</i>";
            } else {

                // Линк към историята защитена
                $row->batch = "<span style='float:left'>{$row->batch}</span>";
                Request::setProtected('batch,productId,storeId');
                $histUrl = array('batch_Movements', 'list', 'batch' => $rec->batch, 'productId' => $rec->productId, 'storeId' => $rec->storeId);
                $row->icon = ht::createLink('', $histUrl, null, $attr);
                Request::removeProtected('batch,productId,storeId');
            }

            $row->quantity = ht::styleNumber($row->quantity, $rec->quantity);
             
            $data->rows[$index] = $row;
        }
    }
    
    
    /**
     * Рендиране на наличните партиди за един артикул
     *
     * @param stdClass $data
     *
     * @return NULL|core_Et $tpl;
     */
    public function renderBatches($data)
    {
        // Ако не рендираме таба, не правим нищо
        if ($data->hide === true) return;
        
        // Кой е шаблона?
        $title = new core_ET('( [#def#] [#btn#])');
        $tpl = getTplFromFile('batch/tpl/ProductItemDetail.shtml');
        $tpl->replace($data->masterId, 'PRODUCT_ID');
        if (!empty($data->definitionRec)) {
            $title->replace(batch_Templates::getHyperlink($data->definitionRec->templateId), 'def');
            if (isset($data->deleteBatchUrl)) {
                $ht = ht::createLink('', $data->deleteBatchUrl, 'Сигурни ли сте, че искате да изтриете партидната дефиниция|*?', 'ef_icon=img/12/close.png,title=Изтриване на нова партидна дефиниция,style=vertical-align: middle;');
                $title->replace($ht, 'btn');
            }
            if(batch_Defs::haveRightFor('edit', $data->definitionRec)){
                $editBtn = ht::createLink('', array('batch_Defs', 'edit', $data->definitionRec->id), false, 'ef_icon=img/16/edit-icon.png,title=Редактиране на конкретната партидна дефиниция,style=vertical-align: middle;');
                $title->append($editBtn);
            }

            $tpl->append($title, 'definition');
        } elseif ($data->addBatchUrl) {
            $ht = ht::createLink('', $data->addBatchUrl, false, 'ef_icon=img/16/add.png,title=Добавяне на нова партидна дефиниция,style=vertical-align: middle;');
            $tpl->append($ht, 'definition');
        }
        
        // Ако има филтър форма, показваме я
        if (isset($data->form)) {
            $tpl->append($data->form->renderHtml(), 'FILTER');
        }
        
        $fieldSet = cls::get('core_FieldSet');
        $fieldSet->FLD('batch', 'varchar', 'tdClass=leftCol');
        $fieldSet->FLD('storeId', 'varchar', 'tdClass=leftCol');
        $fieldSet->FLD('icon', 'varchar', 'tdClass=small-field');
        $fieldSet->FLD('quantity', 'double');
        
        // Подготвяме таблицата за рендиране
        $table = cls::get('core_TableView', array('mvc' => $fieldSet));
        $fields = arr::make('icon=|*&nbsp;,storeId=Склад,batch=Партида,measureId=Мярка,quantity=Количество', true);
        $fields = core_TableView::filterEmptyColumns($data->rows, $fields, 'icon');

        // Ако е филтрирано по склад, скриваме колонката на склада
        if (isset($data->storeId)) {
            unset($fields['storeId']);
        }
        
        // Рендиране на таблицата с резултатите
        $dTpl = $table->get($data->rows, $fields);
        $tpl->append($dTpl, 'content');
        
        // Ако има пейджър го рендираме
        if (isset($data->pager)) {
            $tpl->append($data->pager->getHtml(), 'content');
        }
        
        // Връщаме шаблона
        return $tpl;
    }


    /**
     * Помощна ф-я за изчличане на наличностите към дадена дата по артикули
     *
     * @param int|null      $productId - ид на артикул
     * @param int|null      $storeId   - ид на склад
     * @param datetime|NULL $date      - към дата, ако е празно текущата
     * @param int|NULL      $limit     - лимит на резултатите
     * @param array         $except    - кой документ да се игнорира
     * @param string|null   $batch     - конкретна партида
     * @param bool          $showMovementsWithClosedBatches - дали да са текущо активн
     * @return array $res - масив с партидите и к-та
     *               ['productId']['batch'] => ['quantity']
     */
    public static function getProductsWithMovement($storeId = null, $productId = null, $date = null, $limit = null, $except = array(), $batch = null, $showMovementsWithClosedBatches = false)
    {
        // Намират се всички движения в посочения интервал за дадения артикул в подадения склад
        $date = $date ?? dt::now();
        $query = batch_Movements::getQuery();
        $query->EXT('state', 'batch_Items', 'externalName=state,externalKey=itemId');
        $query->EXT('productId', 'batch_Items', 'externalName=productId,externalKey=itemId');
        $query->EXT('storeId', 'batch_Items', 'externalName=storeId,externalKey=itemId');
        $query->EXT('batch', 'batch_Items', 'externalName=batch,externalKey=itemId');
        $query->EXT('nullifiedDate', 'batch_Items', 'externalName=nullifiedDate,externalKey=itemId');
        $query->EXT('bQuantity', 'batch_Items', 'externalName=quantity,externalKey=itemId');
        $query->XPR('bQuantityRounded', 'double', 'ROUND(#bQuantity, 3)');
        if($showMovementsWithClosedBatches){
            $nullifiedAfter = strlen($date) == 10 ? "{$date} 00:00:00" : $date;
            $query->where("#state = 'active' OR (#state = 'closed' AND #bQuantityRounded != 0) OR #nullifiedDate >= '{$nullifiedAfter}'");
        } else {
            $query->where("#state != 'closed'");
        }

        $query->show('batch,quantity,operation,date,docType,docId,productId');
        if(isset($productId)){
            $query->where("#productId = {$productId}");
        }
        if(isset($batch)){
            $query->where(array("#batch = '[#1#]'", $batch));
        }

        if(!empty($storeId)){
            $query->where("#storeId = '{$storeId}'");
        }
        $query->where("#date <= '{$date}'");

        $docType = $docId = null;
        if (countR($except) == 2) {
            $docType = cls::get($except[0])->getClassId();
            $docId = $except[1];
        }
        $query->orderBy('id', 'ASC');

        // Ако е указан лимит
        if (isset($limit)) {
            $query->limit($limit);
        }

        // Сумиране на к-то към датата
        $res = array();
        while ($rec = $query->fetch()) {
            if (countR($except) == 2) {
                if ($rec->docType == $docType && $rec->docId == $docId) {
                    continue;
                }
            }
            if (!array_key_exists($rec->productId, $res)){
                $res[$rec->productId] = array();
            }
            if (!array_key_exists($rec->batch, $res[$rec->productId])) {
                $res[$rec->productId][$rec->batch] = 0;
            }

            $sign = ($rec->operation == 'in') ? 1 : -1;
            $res[$rec->productId][$rec->batch] += $sign * $rec->quantity;
        }

        return $res;
    }



    /**
     * Изчислява количествата на партидите на артикул към дадена дата и склад
     *
     * @param int           $productId - ид на артикул
     * @param int|null      $storeId   - ид на склад
     * @param datetime|NULL $date      - към дата, ако е празно текущата
     * @param int|NULL      $limit     - лимит на резултатите
     * @param array         $except    - кой документ да се игнорира
     * @param boolean       $onlyActiveBatches - дали да са само текущо активните партиди
     * @param string|null   $batch     - ид на склад
     * @param boolean       $onlyPositiveBatches - дали да са само положителните наличности
     * @param boolean       $showMovementsWithClosedBatches - дали да се извличат движенията на заторените партиди
     *
     * @return array $res - масив с партидите и к-та
     *               ['batch'] => ['quantity']
     */
    public static function getBatchQuantitiesInStore($productId, $storeId = null, $date = null, $limit = null, $except = array(), $onlyActiveBatches = false, $batch = null, $onlyPositiveBatches = false, $showMovementsWithClosedBatches = false)
    {
        $res = array();
        $date = (isset($date)) ? $date : dt::today();
        $def = batch_Defs::getBatchDef($productId);
        if (!$def) return $res;

        $found = static::getProductsWithMovement($storeId, $productId, $date, $limit, $except, $batch, $showMovementsWithClosedBatches);
        $res = is_array($found[$productId]) ? $found[$productId] : array();

        // Добавяне и на партидите от активни документи в черновата на журнала
        $bQuery = batch_BatchesInDocuments::getQuery();
        $bQuery->EXT('state', 'doc_Containers', 'externalName=state,externalKey=containerId');
        $bQuery->where("#productId = {$productId}");
        if(!empty($storeId)){
            $bQuery->where("#storeId = {$storeId}");
        }
        if(isset($batch)){
            $bQuery->where(array("#batch = '[#1#]'", $batch));
        }
        $bQuery->where("#state = 'active'");
        $bQuery->groupBy('batch');
        $bQuery->where("#date <= '{$date}'");
        $bQuery->show('batch');

        while ($bRec = $bQuery->fetch()) {
            if (!array_key_exists($bRec->batch, $res) && $onlyActiveBatches === false) {
                $res[$bRec->batch] = 0;
            }
        }

        foreach ($res as $b => $q){
            $res[$b] = round($q, 5);
            if($onlyPositiveBatches && $res[$b] <= 0){
                unset($res[$b]);
            }
        }

        // Намерените партиди се подават на партидната дефиниция, ако иска да ги преподреди
        $def->orderBatchesInStore($res, $storeId, $date);
        
        // Връщане на намерените партиди
        return $res;
    }
    
    
    /**
     * Разпределяне на количество по наличните партиди на даден артикул в склада
     * Партидите с отрицателни и нулеви количества се пропускат
     *
     * @param array $bacthesArr
     *                          [име_на_партидата] => [к_во_в_склада]
     * @param float $quantity
     *
     * @return array $allocatedArr - разпределеното к-во, което да се изпише от партидите
     *               с достатъчно количество в склада
     *               [име_на_партидата] => [к_во_за_изписване]
     */
    public static function allocateQuantity($bacthesArr, $quantity)
    {
        expect(is_array($bacthesArr), 'Не е подаден масив');
        expect(is_numeric($quantity), 'Не е число');
        
        $allocatedArr = array();
        $left = $quantity;
        
        foreach ($bacthesArr as $b => $q) {
            if ($left <= 0) {
                break;
            }
            if ($q >= $left) {
                $allocatedArr[$b] = $left;
                $left -= $left;
                $left = round($left, 4);
            } elseif ($q > 0) {
                $allocatedArr[$b] = $q;
                $left -= $allocatedArr[$b];
                $left = round($left, 4);
            } else {
                continue;
            }
        }

        return $allocatedArr;
    }


    /**
     * Помощна ф-я връщаща затворените партиди преди подадена дата
     *
     * @param int $productId
     * @param int $storeId
     * @param date|null $date
     * @param int|null $limit
     * @return array
     */
    public static function getLastClosedBatches($productId, $storeId, $date = null, $limit = null)
    {
        $date = isset($date) ? $date : dt::now();

        $query = static::getQuery();
        $query->where("#storeId = {$storeId} AND #productId = {$productId}");
        $query->where("(#nullifiedDate <= '{$date}' OR #nullifiedDate IS NULL) AND #state = 'closed'");
        $query->orderBy('nullifiedDate', 'DESC');
        $query->show('batch');
        if(isset($limit)){
            $query->limit($limit);
        }

        return arr::extractValuesFromArray($query->fetchAll(), 'batch');
    }
}
