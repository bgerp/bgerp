<?php


/**
 * Клас 'planning_AssetSparePartsDetail'
 *
 * Резервни части към оборудвания
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_AssetSparePartsDetail extends core_Detail
{
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Резервна част на оборудване';


    /**
     * Заглавие
     */
    public $title = 'Резервни части на оборудвания';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'assetId,productId,storeId';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_SaveAndNew, plg_Modified, plg_Created, plg_AlignDecimals2, planning_Wrapper';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, planningMaster';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, planningMaster';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, planningMaster';


    /**
     * Кой може да го изтрие?
     */
    public $canList = 'ceo,planning';


    /**
     * Кой може да го изтрие?
     */
    public $canFastconvert = 'ceo,planning';


    /**
     * Кой може да го изтрие?
     */
    public $canAddfromproduct = 'ceo,planning';


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'assetId';


    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 20;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('assetId', 'key(mvc=planning_AssetResources,select=name,allowEmpty)', 'caption=Ресурс, removeAndRefreshForm, silent');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasnotProperties=generic,maxSuggestions=100,forceAjax)', 'caption=Артикул,mandatory,silent,class=w100,tdClass=productCell leftCol wrap');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'mandatory,caption=Основен склад,tdClass=leftCol');

        $this->setDbIndex('assetId');
        $this->setDbUnique('assetId,productId');
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
        $groupId = cat_Groups::fetchField("#sysId = 'replacements'", 'id');

        $form->setFieldTypeParams("productId", array('hasProperties' => 'canStore,canConvert', 'groups' => $groupId));
    }


    /**
     * Подготовка на Детайлите
     */
    public function prepareDetail_($data)
    {
        $type = planning_AssetGroups::fetchField($data->masterData->rec->groupId, 'type');

        if($type != 'material'){
            $data->hide = true;
            return;
        }

        $Tab = Request::get('Tab', 'varchar');
        $data->tabIsSelected = ($Tab == 'planning_AssetSparePartsDetail');
        $res = parent::prepareDetail_($data);
        $count = countR($data->recs);
        $data->TabCaption = "Резервни части|* ({$count})";

        return $res;
    }


    /**
     * Рендиране на рецептите на един артикул
     *
     * @param stdClass $data
     * @return core_ET
     */
    public function renderDetail_($data)
    {
        if($data->hide) return new core_ET("");

        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $title = tr('Резервни части');
        $tpl->append($title, 'title');
        if($this->haveRightFor('add', (object)array('assetId' => $data->masterId))){
            $btnAdd = ht::createLink('', array($this, 'add', 'assetId' => $data->masterId, 'ret_url' => true), false, 'ef_icon=img/16/add.png,caption=Добавяне на нова резервна част на оборудването');
            $tpl->append($btnAdd, 'title');
        }

        $table = cls::get('core_TableView', array('mvc' => $data->listTableMvc));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, 'pallet');

        $details = $table->get($data->rows, $data->listFields);
        $tpl->append($details, 'content');
        if ($data->pager) {
            $tpl->append($data->pager->getHtml(), 'content');
        }

        return $tpl;
    }


    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    protected static function on_AfterPrepareListFields($mvc, $data)
    {
        $horizonMonthNumber = planning_Setup::get('SPARE_PARTS_HORIZON_IN_LIST');
        $data->listFields = arr::make('assetId=Обордуване,productId=Артикул,storeId=Наличност->Склад,quantity=Наличност->Налично,pallet=Наличност->Палет,quantityAll=Наличност->Всичко', true);
        if(isset($data->masterMvc)){
            unset($data->listFields['assetId']);
        } else {
            unset($mvc->listItemsPerPage);
        }

        $plural = str::getPlural($horizonMonthNumber, 'месец', true);
        arr::placeInAssocArray($data->listFields, array('reservedQuantity' => "|Следващите|* {$horizonMonthNumber} |{$plural}|*->|*<span class='small notBolded' title='|Запазено|*'> |Запаз.|*</span>"), null, 'quantityAll');
        arr::placeInAssocArray($data->listFields, array('expectedQuantity' => "|Следващите|* {$horizonMonthNumber} |{$plural}|*->|*<span class='small notBolded' title='|Очаквано|*'> |Очакв.|*</span>"), null, 'reservedQuantity');
        arr::placeInAssocArray($data->listFields, array('resultDiff' => "|Следващите|* {$horizonMonthNumber} |{$plural}|*->|*<span class='small notBolded' title='|Разполагаемо|*'> |Разпол.|*</span>"), null, 'expectedQuantity');
    }


    /**
     * След извличане на записите
     */
    protected static function on_AfterPrepareListRecs($mvc, &$res, $data)
    {
        if(!countR($data->recs) || (isset($data->masterMvc) && !$data->tabIsSelected)) return;
        $productIds = arr::extractValuesFromArray($data->recs, 'productId');

        // Извличане на планираните наличности за посочения хоризонт
        $now = dt::now();
        $horizonDate = dt::addMonths(planning_Setup::get('SPARE_PARTS_HORIZON_IN_LIST'), dt::today(), false);
        $plannedArr = store_StockPlanning::getPlannedQuantities($horizonDate, $productIds);

        // Показване на общите к-ва
        foreach ($data->recs as &$rec){
            $rec->quantity = store_Products::getQuantities($rec->productId, $rec->storeId, $now)->quantity;
            $rec->quantityAll = store_Products::getQuantities($rec->productId, null, $now)->quantity;
            $rec->reservedQuantity = $rec->expectedQuantity = $rec->resultDiff = null;

            // Сумираните к-ва във всички складове се сумират общо
            foreach ($plannedArr as $productInStores){
                if(isset($productInStores[$rec->productId])){
                    $rec->reservedQuantity += $productInStores[$rec->productId]->reserved;
                    $rec->expectedQuantity += $productInStores[$rec->productId]->expected;
                }
            }

            $rec->resultDiff = $rec->quantityAll - $rec->reservedQuantityt + $rec->expectedQuantity;
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        foreach (array('quantity', 'quantityAll', 'reservedQuantity', 'expectedQuantity', 'resultDiff') as $fld){
            $row->{$fld} = core_Type::getByName('double')->toVerbal($rec->{$fld});
        }
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        $row->assetId = planning_AssetResources::getHyperlink($rec->assetId, true);
        $row->ROW_ATTR['class'] .= ' state-' . cat_Products::fetchField($rec->productId, 'state');

        if ($mvc->haveRightFor('fastconvert', $rec)) {
            $url = array($mvc, 'fastconvert', 'id' => $rec->id, 'ret_url' => true);
            $alwaysShow = (!$fields['-singleProduct']);
            core_RowToolbar::createIfNotExists($row->_rowTools);
            $row->_rowTools->addLink('Влагане', $url, array('ef_icon' => 'img/16/produce_in.png', 'title' => 'Добавяне в протокол за влагане към активен сигнал', 'alwaysShow' => $alwaysShow));
        }
    }


    /**
     * След подготовка на съмирито
     */
    protected static function on_AfterPrepareListSummary($mvc, &$res, &$data)
    {
        $data->listTableMvc = clone $mvc;
        $data->listTableMvc->FNC('pallet', 'varchar', 'smartCenter');
        $data->listTableMvc->FNC('quantity', 'double(maxDecimals=3)');
        $data->listTableMvc->FNC('quantityAll', 'double(maxDecimals=3)');
        $data->listTableMvc->FNC('reservedQuantity', 'double(maxDecimals=3)');
        $data->listTableMvc->FNC('expectedQuantity', 'double(maxDecimals=3)');
        $data->listTableMvc->FNC('resultDiff', 'double(maxDecimals=3)');
    }


    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $rows = &$data->rows;
        if (!countR($rows)) return;

        foreach ($rows as $id => $row) {
            $rec = $data->recs[$id];
            foreach (array('quantity', 'quantityAll', 'reservedQuantity', 'expectedQuantity', 'resultDiff') as $fld){
                $row->{$fld} = ht::styleNumber($row->{$fld}, $rec->{$fld});
            }

            $pQuery = rack_Pallets::getQuery();
            $pQuery->where("#storeId = {$rec->storeId} AND #productId = {$rec->productId}");
            $pQuery->show('position');
            $pQuery->orderBy('position', 'ASC');
            $firstPalletPosition = $pQuery->fetch()->position;
            if(!empty($firstPalletPosition)){
                $row->pallet = core_Type::getByName('varchar')->toVerbal($firstPalletPosition);
                if(rack_Pallets::haveRightFor('forceopen', (object)array('storeId' => $rec->storeId))){
                    $row->pallet = ht::createLink($row->pallet, array('rack_Pallets', 'forceopen', 'storeId' => $rec->storeId, 'productId' => $rec->productId, 'position' => $firstPalletPosition));
                }
            }
        }
    }


    /**
     * Подготвка на лист филтъра
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        if($data->masterMvc) return;

        $data->listFilter->showFields = 'assetId,productId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();

        if($rec = $data->listFilter->rec){
            if(isset($rec->assetId)){
                $data->query->where("#assetId = {$rec->assetId}");
            }

            if(isset($rec->productId)){
                $data->query->where("#productId = {$rec->productId}");
            }
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'fastconvert' && isset($rec)) {
            $activeTaskCount = $mvc->getSupportTaskOptions($rec->assetId, $userId, true);
            if(!$activeTaskCount){
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'addfromproduct' && isset($rec)){
            $productRec = cat_Products::fetch($rec->productId, 'groups,state,canConvert,canStore');
            if($productRec->canConvert != 'yes' || $productRec->canStore != 'yes' || $productRec->state != 'active'){
                $requiredRoles = 'no_one';
            } else {
                $groupId = cat_Groups::fetchField("#sysId = 'replacements'", 'id');
                if(!keylist::isIn($groupId, $productRec->groups)){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }


    /**
     * Връзване на артикул към оборудване
     */
    public function act_addfromproduct()
    {
        $this->requireRightFor('addfromproduct');
        expect($productId = Request::get('productId', 'int'));
        $this->requireRightFor('addfromproduct', (object)array('productId' => $productId));

        $form = cls::get('core_Form');
        $form->title = core_Detail::getEditTitle('cat_Products', $productId, 'оборудване', null);
        $form->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden,silent');
        $form->FLD('assetId', 'key(mvc=planning_AssetResources,select=name,allowEmpty)', 'caption=Оборудване,mandatory');
        $form->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'mandatory,caption=Основен склад,tdClass=leftCol');

        $query = static::getQuery();
        $query->where("#productId = {$productId}");
        $query->show('assetId');
        $assetIds = arr::extractValuesFromArray($query->fetchAll(), 'assetId');

        $assetOptions = array();
        $aQuery = planning_AssetResources::getQuery();
        $aQuery->EXT('type', 'planning_AssetGroups', 'externalName=type,externalKey=groupId');
        $aQuery->notIn('id', $assetIds);
        $aQuery->where("#type = 'material'");
        while($aRec = $aQuery->fetch()){
            $assetOptions[$aRec->id] = planning_AssetResources::getRecTitle($aRec, false);
        }
        if(countR($assetOptions)){
            $form->setOptions('assetId', array('' => '') + $assetOptions);
        } else {
            $form->setReadOnly('assetId');
            $form->setError('assetId', "Няма свободни оборудвания за добавяне");
        }

        $form->input(null, 'silent');
        $form->input();
        if($form->isSubmitted()){
            $rec = $form->rec;
            $newRec = (object)array('assetId' => $rec->assetId, 'storeId' => $rec->storeId, 'productId' => $rec->productId);
            $fields = $exRec = null;
            if ($this->isUnique($newRec, $fields, $exRec)) {
                static::save($newRec);

                if ($form->cmd == 'save_n_new') {
                    redirect(array($this, 'addfromproduct', 'productId' => $rec->productId, 'ret_url' => getRetUrl()));
                }
                followRetUrl();
            }
        }

        $form->toolbar->addSbBtn('Запис и Нов', 'save_n_new', null, array('id' => 'saveAndNew', 'order' => '1', 'ef_icon' => 'img/16/save_and_new.png', 'title' => 'Запиши документа и създай нов'));
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Влагане на резервната част към сигнала');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        $this->logInfo('Връзка между резервна част и оборудване');

        return $this->renderWrapping($form->renderHtml());
    }


    /**
     * Екшън за бързо влагане на резервна част
     *
     * @return mixed
     * @throws core_exception_Expect
     */
    public function act_Fastconvert()
    {
        $this->requireRightFor('fastconvert');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('fastconvert', $rec);
        $activeTaskOptions = $this->getSupportTaskOptions($rec->assetId);

        // Ако е само един активния сигнал
        if(countR($activeTaskOptions) == 1) {
            $containerId = key($activeTaskOptions);
            $taskRec = cal_Tasks::fetch("#containerId = {$containerId}");

            // Създаване на ПВ към него и редирект към формата за добавяне на артикул в него
            if(!planning_ConsumptionNotes::haveRightFor('add', (object)array('originId' => $containerId))){
                followRetUrl(null, "Нямате права за създаване на протокол за влагане към|*:" . cal_Tasks::getLink($taskRec->id, 0), 'error');
            }
            $consumptionNoteId = $this->createConsumptionNoteDraft($rec, $containerId);

            if(!planning_ConsumptionNoteDetails::haveRightFor('add', (object)array('noteId' => $consumptionNoteId))){
                followRetUrl(null, "Нямате права за добавяне на артикул към|*:" . planning_ConsumptionNotes::getLink($consumptionNoteId, 0), 'error');
            }

            redirect(array('planning_ConsumptionNoteDetails', 'add', 'noteId' => $consumptionNoteId, 'productId' => $rec->productId));
        }

        // Ако са повече от един се показва форма за избор на конкретен сигнал
        $form = cls::get('core_Form');
        $form->title = "|Избор на сигнал за|* " .  cls::get('planning_AssetResources')->getFormTitleLink($rec->assetId);
        $form->info = tr("Резервна част|*: ") . cat_Products::getHyperlink($rec->productId, true);
        $form->FLD('taskContainerId', 'int', 'caption=Сигнал,mandatory');
        $form->setOptions('taskContainerId', array('' => '') + $activeTaskOptions);
        $form->input();
        if($form->isSubmitted()){
            $taskRec = cal_Tasks::fetch("#containerId = {$form->rec->taskContainerId}");
            if(!planning_ConsumptionNotes::haveRightFor('add', (object)array('originId' => $form->rec->taskContainerId))){
                $form->setError('taskContainerId', "Нямате права за създаване на протокол за влагане към|*:" . cal_Tasks::getLink($taskRec->id, 0));
            }

            if(!$form->gotErrors()){
                $consumptionNoteId = $this->createConsumptionNoteDraft($rec, $form->rec->taskContainerId);
                if(planning_ConsumptionNoteDetails::haveRightFor('add', (object)array('noteId' => $consumptionNoteId))){
                    redirect(array('planning_ConsumptionNoteDetails', 'add', 'noteId' => $consumptionNoteId, 'productId' => $rec->productId));
                } else {
                    $form->setError('taskContainerId', "Нямате права за добавяне на артикул за влагане в|*:" . planning_ConsumptionNotes::getLink($consumptionNoteId, 0));
                }
            }
        }

        $form->toolbar->addSbBtn('Влагане', 'save', 'ef_icon = img/16/move.png, title = Влагане на резервната част към сигнала');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        $this->logInfo('Избор на сигнал към който да се пусне оборудване');

        return $this->renderWrapping($form->renderHtml());
    }


    /**
     * Създаване на протокол за влагане за резервна част
     *
     * @param stdClass $rec
     * @param int $containerId
     * @return int
     */
    private function createConsumptionNoteDraft($rec, $containerId)
    {
        $containerRec = doc_Containers::fetch($containerId);
        $nRec = (object)array('storeId'  => $rec->storeId,
                              'originId' => $containerRec->id,
                              'folderId' => $containerRec->folderId,
                              'threadId' => $containerRec->threadId,
                              'description' => 'Влагане на резервна част',
                              'state'    => 'draft');

        planning_ConsumptionNotes::save($nRec);
        planning_ConsumptionNotes::logWrite('Създаване за влагане на резервна част', $nRec->id);

        return $nRec->id;
    }


    /**
     * Връщат се наличните за избор активни сигнали
     *
     * @param int $assetId    - ид на оборудване
     * @param int|null $cu    - потребител, null за текущия
     * @param bool $onlyCount - само бройка или опции
     * @return array|int
     */
    public function getSupportTaskOptions($assetId, $cu = null, $onlyCount = false)
    {
        $cu = $cu ?? core_Users::getCurrent();
        $driverClassId = cls::get('support_TaskType')->getClassId();
        $tQuery = cal_Tasks::getQuery();
        $tQuery->where("#driverClass = {$driverClassId} AND #state = 'active'");
        $tQuery->where("#assetResourceId = {$assetId}");
        $tQuery->orderBy('createdOn=DESC,id=DESC');
        if($onlyCount) return $tQuery->count();

        $options = array();
        while($tRec = $tQuery->fetch()){
            if(cal_Tasks::haveRightFor('single', $tRec, $cu)){
                $options[$tRec->containerId] = "Tsk{$tRec->id} - " . cal_Tasks::getTitleById($tRec->id, false);
            }
        }

        return $options;
    }


    /**
     * Рендиране на оборудванията към резервната част
     *
     * @param int $productId
     * @return core_ET
     */
    public static function renderProductAssets($productId)
    {
        $tpl = new core_ET("");
        $me = cls::get(get_called_class());

        $rows = array();
        $query = $me->getQuery();
        $query->where("#productId = {$productId}");
        $fields = $me->selectFields();
        $fields['-singleProduct'] = true;
        $fields['-list'] = true;
        while($rec = $query->fetch()){
            $row = $me->recToVerbal($rec, $fields);
            core_RowToolbar::createIfNotExists($row->_rowTools);
            $row->tools = $row->_rowTools->renderHtml();
            $rows[$rec->id] = $row;
        }

        if(!countR($rows)) return $tpl;
        foreach ($rows as $row){
            $blockTpl = new core_ET("<div>[#tools#] [#assetId#]</div>");
            $blockTpl->placeObject($row);
            $blockTpl->removeBlocksAndPlaces();

            $tpl->append($blockTpl);
        }

        return $tpl;
    }
}