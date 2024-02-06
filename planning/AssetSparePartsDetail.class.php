<?php


/**
 * Клас 'planning_StepConditions'
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
    public $title = 'Резервни части на оборудване';


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
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasnotProperties=generic,maxSuggestions=100,forceAjax)', 'caption=Артикул,mandatory,silent,class=w100,tdClass=productCell leftCol');
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
        $folderId = cat_Categories::fetchField("#sysId = 'replacements'", 'folderId');
        $pQuery = cat_Products::getQuery();
        $pQuery->where("#folderId = {$folderId}");
        $pQuery->show('id');

        $replacementIds = arr::extractValuesFromArray($pQuery->fetchAll(), 'id');
        if(!countR($replacementIds)){
            $form->setError('productId', 'Няма активен складируем и вложим артикул в категория "Резервни части"');
            $form->setReadOnly('productId');
        } else {
            $form->setFieldTypeParams("productId", array('hasProperties' => 'canStore,canConvert', 'onlyIn' => $replacementIds));
        }
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
    public static function on_AfterPrepareListFields($mvc, $data)
    {
        $horizonMonthNumber = planning_Setup::get('SPARE_PARTS_HORIZON_IN_LIST');
        $data->listFields = arr::make('assetId=Обордуване,productId=Артикул,storeId=Наличност->Склад,quantity=Наличност->Налично,quantityAll=Наличност->Всичко налично', true);
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
        $horizonDate = dt::addMonths(planning_Setup::get('SPARE_PARTS_HORIZON_IN_LIST'), date('Y-m-01'), false);
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
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
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
            $row->_rowTools->addLink('Влагане', $url, array('ef_icon' => 'img/16/produce_in.png', 'title' => 'Добавяне в протокол за влагане към активен сигнал', 'alwaysShow' => true));
        }
    }


    /**
     * След подготовка на съмирито
     */
    public static function on_AfterPrepareListSummary($mvc, &$res, &$data)
    {
        $data->listTableMvc = clone $mvc;
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
        if(isset($form->rec->taskContainerId)){
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
    public function createConsumptionNoteDraft($rec, $containerId)
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
}