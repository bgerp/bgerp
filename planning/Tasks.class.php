<?php


/**
 * Мениджър на Производствени операции
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Производствени операции
 */
class planning_Tasks extends core_Master
{
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = true;


    /**
     * Шаблон за единичен изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutTask.shtml';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title,assetId,employees,description,productId';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'doc_plg_Prototype, doc_SharablePlg, doc_DocumentPlg, plg_RowTools2, planning_plg_StateManager, plg_Sorting, planning_Wrapper, acc_plg_DocumentSummary, plg_Search, plg_Clone, plg_Printing, plg_RefreshRows, plg_LastUsedKeys, support_plg_IssueSource, bgerp_plg_Blank';


    /**
     * На колко време да се рефрешва лист изгледа
     */
    public $refreshRowsTime = 15000;


    /**
     * Заглавие
     */
    public $title = 'Производствени операции';


    /**
     * Скриване на полето за споделени потребители
     */
    public $hideSharedUsersFld = true;


    /**
     * Единично заглавие
     */
    public $singleTitle = 'Производствена операция';


    /**
     * Абревиатура
     */
    public $abbr = 'Opr';


    /**
     * Клас обграждащ горния таб
     */
    public $tabTopClass = 'portal planning';


    /**
     * Дали да се подреждат по дата
     */
    public $orderByDateField = false;


    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/task-normal.png';


    /**
     * Да не се кешира документа
     */
    public $preventCache = true;

    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'firstProgress=Начало,lastProgressProduction=Край,dependantProgress=Пред.,prevExpectedTimeEnd=Пред. край,expectedTimeStart=Тек. начало,title=Текуща,progress=Прогрес,expectedTimeEnd=Тек. край,nextExpectedTimeStart=След. начало,nextId=Следв.,dueDate=Падеж,originId=Задание,jobQuantity=Тираж (Зад.),plannedQuantity=Тираж (ПО),folderId,assetId,saleId=Доставка,notes=Забележка';


    /**
     * Поле за търсене по потребител
     */
    public $filterFieldUsers = false;


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, taskSee';


    /**
     * Кой може да записва преподредените задачи?
     */
    public $canSavereordertasks = 'ceo, taskSee';


    /**
     * Кой може да записва преподредените задачи?
     */
    public $canPasteselected = 'ceo, taskSee';


    /**
     * Кой може да го добавя?
     */
    public $canAdd = 'ceo, task';


    /**
     * Кой може да ги създава от задания?
     */
    public $canCreatejobtasks = 'ceo, task';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, taskSee';


    /**
     * Кой може да преизчислява заработките на прогреса на операцията?
     */
    public $canRecalcindtime = 'ceo,task';


    /**
     * Блок за тейбъл роу
     */
    public $tableRowTpl = "<tbody class='rowBlock' [#TBODY_ROW_ATTR#]>[#ROW#][#ADD_ROWS#]</tbody>";


    /**
     * Кой може да го активира?
     */
    public $canActivate = 'ceo, task';


    /**
     * Кой може да променя състоянието?
     */
    public $canChangestate = 'ceo, taskWorker';


    /**
     * Кой може да го редактира?
     */
    public $canEdit = 'ceo, task';


    /**
     * Кой може да го прави на заявка?
     */
    public $canPending = 'ceo, task';


    /**
     * Може ли да се редактират активирани документи
     */
    public $canEditActivated = true;


    /**
     * Да се показва антетка
     */
    public $showLetterHead = true;


    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'expectedTimeStart,activatedOn,createdOn,dueDate,lastChangeStateOn,timeClosed,firstProgress,lastProgress,lastProgressProduction';


    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;


    /**
     * Кои са детайлите на класа
     */
    public $details = 'planning_ProductionTaskDetails,planning_ProductionTaskProducts';


    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'planning_ProductionTaskProducts';


    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'progress,totalWeight,totalNetWeight,scrappedQuantity,producedQuantity,totalQuantity,plannedQuantity,timeStart,timeEnd,timeDuration,systemId,orderByAssetId,prevAssetId,expectedTimeStart,expectedTimeEnd,prevErrId,nextErrId,timeClosed';


    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'assetId';


    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'barcode_SearchIntf,label_SequenceIntf=planning_interface_TaskLabel';


    /**
     * Да се проверява ли дали има разминаване с к-то в опаковката
     */
    public $dontCheckQuantityInPack = true;


    /**
     * Дали в лист изгледа да се показва полето за филтър по състояние
     * @param bool
     * @see acc_plg_DocumentSummary
     */
    public $filterAllowState = false;


    /**
     * Дали да се помни последно избраната папка в лист изгледа
     *
     * @see acc_plg_DocumentSummary
     * @var bool
     */
    public $rememberListFilterFolderId = true;


    /**
     * Опашка за оборудванията на, които да се преподредят машините
     */
    protected $reorderTasksInAssetId = array();


    /**
     * На кои операции трябва да се преизчисли нормата на детайлите
     */
    protected $recalcProducedDetailIndTime = array();


    /**
     * Опашка за ПО-та, които трябва да се преподредят в рамките на заданието
     */
    protected $reorderTasksByJobIds = array();


    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 20;


    /**
     * Да се показват ли бъдещи периоди в лист изгледа
     */
    public $filterFutureOptions = true;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие,width=100%,silent,input=hidden,tdClass=small');
        $this->FLD('productId', 'key2(mvc=cat_ProductsProxy,select=name,selectSourceArr=planning_Steps::getSelectableSteps,allowEmpty,forceAjax,forceOpen)', 'mandatory,class=w100,caption=Етап,removeAndRefreshForm=packagingId|measureId|quantityInPack|paramcat|plannedQuantity|indPackagingId|storeId|assetId|employees|labelPackagingId|labelQuantityInPack|labelType|labelTemplate|indTime|isFinal|paramcat|isFinal|wasteProductId|wasteStart|wastePercent|indTimeAllocation|showadditionalUom|description,silent');
        $this->FLD('measureId', 'key(mvc=cat_UoM,select=name,select=shortName)', 'mandatory,caption=Мярка,removeAndRefreshForm=quantityInPack|plannedQuantity|labelPackagingId|indPackagingId,silent,input=hidden');
        $this->FLD('totalWeight', 'cat_type_Weight(smartRound=no)', 'caption=Общо Бруто,input=none');
        $this->FLD('totalNetWeight', 'cat_type_Weight(smartRound=no)', 'caption=Общо Нето,input=none');
        $this->FLD('plannedQuantity', 'double(smartRound,Min=0)', 'mandatory,caption=Планирано');
        $this->FLD('isFinal', 'enum(yes=Да,no=Не)', 'input=hidden,caption=Финална,silent');
        $this->FLD('quantityInPack', 'double', 'mandatory,caption=К-во в мярка,input=none');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,input=none');
        $this->FLD('assetId', 'key(mvc=planning_AssetResources,select=name,allowEmpty)', 'caption=Оборудване,silent,removeAndRefreshForm=orderByAssetId|startAfter|freeTimeAfter|simultaneity');
        $this->FLD('simultaneity', 'double(min=0)', 'caption=Едновременност,input=hidden');
        $this->FLD('prevAssetId', 'key(mvc=planning_AssetResources,select=name)', 'caption=Оборудване (Старо),input=none');
        $this->FLD('employees', 'keylist(mvc=crm_Persons,select=id,makeLinks,select2MinItems=0)', 'caption=Оператори,silent');
        $this->FNC('startAfter', 'varchar', 'caption=Започва след,silent,placeholder=Първа,class=w100');
        $this->FLD('showadditionalUom', 'enum(no=Изключено,yes=Включено)', 'caption=Отчитане на тегло,notNull,value=yes,autohide,class=w100');
        if (core_Packs::isInstalled('batch')) {
            $this->FLD('followBatchesForFinalProduct', 'enum(yes=На производство по партида,no=Без отчитане)', 'caption=Партида,input=none');
        }

        $this->FLD('manualPreviousTask', 'key(mvc=planning_Tasks,select=title)', 'caption=Предходна операция,input=none');

        $this->FLD('mandatoryDocuments', 'classes(select=title)', 'caption=Задължителни,hint=Задължително изискуеми документи (поне един от всеки избран тип) за да може да бъде приключена операцията');
        $this->FLD('indPackagingId', 'key(mvc=cat_UoM,select=name)', 'silent,class=w25,removeAndRefreshForm,class=w25,caption=Нормиране->Мярка,input=hidden,tdClass=small-field nowrap');
        $this->FLD('indTimeAllocation', 'enum(common=Общо,individual=Поотделно)', 'caption=Нормиране->Разпределяне,smartCenter,notNull,value=individual');
        $this->FLD('indTime', 'planning_type_ProductionRate', 'caption=Нормиране->Норма,smartCenter');
        $this->FLD('labelPackagingId', 'key(mvc=cat_UoM,select=name)', 'caption=Етикиране->Опаковка,input=hidden,tdClass=small-field nowrap,placeholder=Няма,silent,removeAndRefreshForm=labelQuantityInPack|labelTemplate,oldFieldName=packagingId');
        $this->FLD('labelQuantityInPack', 'double(smartRound,Min=0)', 'caption=Етикиране->В опаковка,tdClass=small-field nowrap,input=hidden,oldFieldName=packagingQuantityInPack');
        $this->FLD('labelType', 'enum(print=Генериране,scan=Въвеждане,both=Комбинирано,autoPrint=Генериране и Печат)', 'caption=Етикиране->Производ. №,tdClass=small-field nowrap,notNull,value=both,input=hidden');
        $this->FLD('labelTemplate', 'key(mvc=label_Templates,select=title)', 'caption=Етикиране->Шаблон,tdClass=small-field nowrap,input=hidden');
        $this->FLD('timeStart', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00,format=smartTime)', 'caption=Целеви времена->Начало, changable, tdClass=leftColImportant');
        $this->FLD('timeDuration', 'time', 'caption=Целеви времена->Продължителност,changable');
        $this->FLD('calcedDuration', 'time', 'caption=Целеви времена->Нетна продължителност,input=none');
        $this->FLD('calcedCurrentDuration', 'time', 'caption=Целеви времена->Изчислена продължителност,input=none');
        $this->FLD('timeEnd', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00,format=smartTime)', 'caption=Целеви времена->Край,changable, tdClass=leftColImportant,formOrder=103');
        $this->FLD('wasteProductId', 'key2(mvc=cat_ProductsProxy,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax)', 'caption=Отпадък->Артикул,silent,class=w100,removeAndRefreshForm=wasteStart|wastePercent,autohide');
        $this->FLD('wasteStart', 'double(smartRound)', 'caption=Отпадък->Начален,autohide');
        $this->FLD('wastePercent', 'percent(Min=0)', 'caption=Отпадък->Допустим,autohide');
        $this->FLD('expectedTimeStart', 'datetime', 'caption=Планирани времена->Начало,input=none,tdClass=leftCol');
        $this->FLD('expectedTimeEnd', 'datetime', 'caption=Планирани времена->Край,input=none,tdClass=leftCol');

        $this->FLD('totalQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Количество,after=labelPackagingId,input=none');
        $this->FLD('scrappedQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Брак,input=none');
        $this->FLD('producedQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Заскладено,input=none');

        $this->FLD('progress', 'percent', 'caption=Прогрес,input=none,notNull,value=0');
        $this->FLD('systemId', 'int', 'silent,input=hidden');

        $this->FLD('deviationNettoNotice', 'percent(Min=0,smartRound)', 'caption=Прагове при разминаване на нетото в прогреса->Информация,autohide');
        $this->FLD('deviationNettoWarning', 'percent(Min=0,smartRound)', 'caption=Прагове при разминаване на нетото в прогреса->Предупреждение,autohide');
        $this->FLD('deviationNettoCritical', 'percent(Min=0,smartRound)', 'caption=Прагове при разминаване на нетото в прогреса->Критично,autohide');

        $this->FLD('subTitle', 'varchar(24)', 'caption=Допълнително->Подзаглавие,width=100%,recently');
        $this->FLD('description', 'richtext(rows=2,bucket=Notes,passage)', 'caption=Допълнително->Описание,autoHide');
        $this->FLD('notes', 'varchar(255)', 'caption=Допълнително->Забележка');
        $this->FLD('orderByAssetId', 'double(smartRound)', 'silent,input=hidden,caption=Подредба,smartCenter');
        $this->FLD('saoOrder', 'double(smartRound)', 'caption=Структура и подредба->Подредба,input=none,column=none,order=100000');

        $this->FLD('prevErrId', 'key(mvc=planning_Tasks,select=title)', 'input=none,caption=Предишна грешка');
        $this->FLD('nextErrId', 'key(mvc=planning_Tasks,select=title)', 'input=none,caption=Следваща грешка');
        $this->FLD('freeTimeAfter', 'enum(yes,no)', 'input=none,notNull,value=no');

        $this->FLD('firstProgress', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00,format=smartTime)', 'caption=Първи Прогрес (всички),changable, tdClass=leftColImportant,input=none');
        $this->FLD('lastProgress', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00,format=smartTime)', 'caption=Последен Прогрес (всички),changable, tdClass=leftColImportant,input=none');
        $this->FLD('lastProgressProduction', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00,format=smartTime)', 'caption=Последен Прогрес (произвеждане),changable, tdClass=leftColImportant,input=none');

        $this->setDbIndex('labelPackagingId');
        $this->setDbIndex('productId');
        $this->setDbIndex('assetId,orderByAssetId');
        $this->setDbIndex('assetId');
        $this->setDbIndex('modifiedOn');
        $this->setDbIndex('saoOrder');
        $this->setDbIndex('state');
    }


    /**
     * Подготвя формата за филтриране
     */
    public function prepareListFilter_($data)
    {
        // Добавяне на полето за падежа на заданието за да може да се филтрира по него
        $data = parent::prepareListFilter_($data);
        $data->listFilter->EXT('dueDate', 'planning_Jobs', 'externalName=dueDate,remoteKey=containerId,externalFieldName=originId,caption=Задание->Падеж');
        $data->query->fields['dueDate'] = $data->listFilter->getField('dueDate');

        return $data;
    }


    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $data->paramData = cat_products_Params::prepareClassObjectParams($mvc, $data->rec);

        if ($Driver = cat_Products::getDriver($data->rec->productId)) {
            $pData = $Driver->getProductionData($data->rec->productId);
            $in = $pData['planningParams'];
            if (!countR($in)) {
                unset($data->paramData->addUrl);
            }

            if ($pData['showPreviousJobField']) {
                $originRec = doc_Containers::getDocument($data->rec->originId)->fetch('oldJobId,productId');

                if ($originRec->oldJobId) {
                    $oldJobProductId = planning_Jobs::fetchField($originRec->oldJobId, 'productId');
                    $data->row->previousJob = planning_Jobs::getHyperlink($originRec->oldJobId, true, false, array('limit' => 64));
                    $data->row->previousJobCaption = ($originRec->productId == $oldJobProductId) ? tr('Предходно') : tr('Подобно');
                }
            }
        }

        if(isset($data->rec->assetId)){
            $Assets = cls::get('planning_AssetResources');
            Mode::push('text', 'xhtml');
            $data->assetData = cat_products_Params::prepareClassObjectParams($Assets, $Assets->fetch($data->rec->assetId));
            Mode::pop('text', 'xhtml');
        }
    }


    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        $tpl->prepend(getTplFromFile('planning/tpl/TaskStatistic.shtml'), 'ABOVE_LETTER_HEAD');
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param core_Mvc $mvc
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if (isset($data->paramData)) {
            $paramTpl = cat_products_Params::renderParams($data->paramData);
            $tpl->append($paramTpl, 'PARAMS');
        }

        if(isset($data->assetData) && countR($data->assetData->params)){
            Mode::push('text', 'xhtml');
            $data->assetData->paramCaption = 'От обордуването';
            $assetTpl = cat_products_Params::renderParams($data->assetData);
            $tpl->append($assetTpl, 'ASSET_PARAMS');
            Mode::pop('text');
        }
        $tpl->append('no-border', 'LETTER_HEAD_TABLE_CLASS');

        // Показване на обобщението на отпадъка в статистиката
        $wasteArr = planning_ProductionTaskProducts::getTotalWasteArr($data->rec->threadId);
        if(countR($wasteArr)){
            foreach ($wasteArr as $wasteRow){
                $cloneTpl = clone $tpl->getBlock('WASTE_BLOCK_ROW');
                $cloneTpl->replace($wasteRow->productLink, 'wasteProducedProductId');
                $cloneTpl->replace($wasteRow->class, 'wasteClass');
                $cloneTpl->replace($wasteRow->quantityVerbal, 'wasteQuantity');
                $cloneTpl->removeBlocksAndPlaces();
                $tpl->append($cloneTpl, 'WASTE_BLOCK_TABLE_ROW');
            }
        }

        $subProductArr = planning_ProductionTaskProducts::getSubProductsArr($data->rec->threadId);
        if(countR($subProductArr)){
            foreach ($subProductArr as $subRow){
                $cloneTpl = clone $tpl->getBlock('SUB_PRODUCT_BLOCK_ROW');
                $cloneTpl->replace($subRow->productLink, 'subProductId');
                $cloneTpl->replace($subRow->quantityVerbal, 'subProductQuantity');
                $cloneTpl->removeBlocksAndPlaces();
                $tpl->append($cloneTpl, 'SUB_BLOCK_TABLE_ROW');
            }
        }
    }


    /**
     * Какво е заглавието на етапа в операцията
     *
     * @param int $productId
     * @return mixed|string
     */
    private function getStepTitle($productId)
    {
        if ($Driver = cat_Products::getDriver($productId)) {
            $pData = $Driver->getProductionData($productId);
            if (!empty($pData['name'])) return $pData['name'];
        }

        return cat_Products::getTitleById($productId, false);
    }


    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    public static function recToVerbal_($rec, &$fields = '*')
    {
        core_Debug::startTimer('RENDER_VERBAL');
        $row = parent::recToVerbal_($rec, $fields);
        $mvc = cls::get(get_called_class());

        $red = new color_Object('#FF0000');
        $blue = new color_Object('green');
        $grey = new color_Object('#bbb');

        $progressPx = min(170, round(170 * $rec->progress));
        $progressRemainPx = 170 - $progressPx;

        $color = ($rec->progress <= 1) ? $blue : $red;
        $row->progressBar = "<div style='white-space: nowrap; display: inline-block;'><div style='display:inline-block;top:-5px;border-bottom:solid 11px {$color}; width:{$progressPx}px;'> </div><div style='display:inline-block;top:-5px;border-bottom:solid 11px {$grey};width:{$progressRemainPx}px;'></div></div>";
        $grey->setGradient($color, $rec->progress);

        $origin = doc_Containers::getDocument($rec->originId);
        $row->folderId = doc_Folders::getFolderTitle($rec->folderId);

        $row->productId = $mvc->getStepTitle($rec->productId);
        if (!empty($rec->subTitle)) {
            $row->productId .= " <i>{$mvc->getFieldType('subTitle')->toVerbal($rec->subTitle)}</i>";
        }

        if (!Mode::isReadOnly()) {
            $row->productId = ht::createLink($row->productId, cat_Products::getSingleUrlArray($rec->productId));
        }

        if(isset($fields['-detail'])){
            $rec->notConvertedQuantity = $mvc->getLeftOverQuantityInStock($rec);
            $row->notConvertedQuantity = core_Type::getByName('double(smartRound,Min=0)')->toVerbal($rec->notConvertedQuantity);
            $row->notConvertedQuantity = "<b class='red'>{$row->notConvertedQuantity}</b>";
        }

        foreach (array('plannedQuantity', 'totalQuantity', 'scrappedQuantity', 'producedQuantity', 'notConvertedQuantity') as $quantityFld) {
            $row->{$quantityFld} = ($rec->{$quantityFld}) ? $row->{$quantityFld} : 0;
            $row->{$quantityFld} = ht::styleNumber($row->{$quantityFld}, $rec->{$quantityFld});
        }

        if (isset($rec->storeId)) {
            $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        }

        if (in_array($rec->state, array('closed', 'rejected'))) {
            if(isset($fields['-detail'])){
                $row->state = ($rec->state == 'closed') ? tr("Прикл.") : $row->state;
            }
            $row->expectedTimeStart = "<i class = 'quiet'>{$row->state}</i>";
            $row->expectedTimeEnd = "<i class = 'quiet'>{$row->state}</i>";
        } elseif(!Mode::is('isReorder')) {
            // Проверяване на времената
            foreach (array('expectedTimeStart' => 'timeStart', 'expectedTimeEnd' => 'timeEnd') as $eTimeField => $timeField) {

                // Вербализиране на времената
                $DateTime = core_Type::getByName('datetime(format=smartTime)');
                $row->{$eTimeField} = '<span class=quiet>N/A</span>';
                if (!empty($rec->{$eTimeField})) {
                    $row->{$eTimeField} = $DateTime->toVerbal($rec->{$eTimeField});
                    if ($eTimeField == 'expectedTimeStart') {
                        $now = dt::now();
                        if (in_array($rec->state, array('wakeup', 'stopped', 'active'))) {
                            if ($rec->expectedTimeEnd < $now) {
                                $row->expectedTimeStart = ht::createHint("<span class='red'>{$row->expectedTimeStart}</span>", 'Планираният край е в миналото', 'warning');
                            }
                        }
                    }
                }

                if ($rec->{$timeField}) {
                    $row->{$timeField} = $DateTime->toVerbal($rec->{$timeField});
                }

                if (!empty($rec->{$timeField})) {
                    $hint = "Зададено|*: {$row->{$timeField}}";
                    $row->{$eTimeField} = ht::createHint($row->{$eTimeField}, $hint, 'img/16/pin.png', false, array('height' => '12', 'width' => '12'));
                }
            }

            if (!empty($rec->prevErrId)) {
                $row->expectedTimeStart = ht::createHint($row->expectedTimeStart, "Има проблем с предходната операция|* #{$mvc->getHandle($rec->prevErrId)}", 'img/16/red-warning.png', false);
            }

            if (!empty($rec->nextErrId)) {
                $row->expectedTimeStart = ht::createHint($row->expectedTimeStart, "Има проблем със следващата операция|* #{$mvc->getHandle($rec->nextErrId)}", 'img/16/red-warning.png');
            }

            if ($rec->freeTimeAfter == 'yes') {
                $row->expectedTimeStart = ht::createHint($row->expectedTimeStart, "Има свободно време между края на тази операция и началото на следващата|*!", 'warning');
            }

            if (!empty($rec->expectedTimeEnd) && $rec->expectedTimeEnd >= ("{$origin->fetchField('dueDate')} 23:59:59")) {
                $useField = isset($fields['-list']) ? 'expectedTimeStart' : 'expectedTimeEnd';
                $row->{$useField} = ht::createHint($row->{$useField}, "Планирания край е след падежа на заданието|*!", 'img/16/red-warning.png');
            }

            $expectedDuration = dt::secsBetween($rec->expectedTimeEnd, $rec->expectedTimeStart);
            $durationUom = ($expectedDuration < 60) ? 'seconds' : (($expectedDuration < 3600) ? 'minutes' : 'hours');
            $row->expectedDuration = empty($expectedDuration) ? '<span class=quiet>N/A</span>' : core_Type::getByName("time(uom={$durationUom},noSmart)")->toVerbal($expectedDuration);
        }

        $calcedDurationUom = ($rec->calcedDuration < 60) ? 'seconds' : (($rec->calcedDuration < 3600) ? 'minutes' : 'hours');
        $row->calcedDuration = core_Type::getByName("time(uom={$calcedDurationUom},noSmart)")->toVerbal($rec->calcedDuration);
        if(isset($rec->assetId)){
            if(isset($fields['-single'])) {
                $row->assetId = new core_ET(planning_AssetResources::getTitleById($rec->assetId));
                $assetSingleUrlArray = planning_AssetResources::getSingleUrlArray($rec->assetId);
                if(!Mode::isReadOnly()){
                    if(countR($assetSingleUrlArray)){
                        $assetSingleUrlArray['Tab'] = 'Tasks';
                    }
                    $row->assetId = ht::createLink($row->assetId, $assetSingleUrlArray, false, 'ef_icon=img/16/equipment.png');
                }
            }
            if(planning_Tasks::haveRightFor('list') && !Mode::is('printing')) {
                if(isset($fields['-single'])) {
                    $row->assetId->append(ht::createLink('', array('planning_Tasks', 'list', 'folder' => $rec->folderId, 'assetId' => $rec->assetId), false, 'ef_icon=img/16/funnel.png,title=Филтър по център на дейност и оборудване'));
                } else {
                    $row->assetId = ht::createLink(planning_AssetResources::getTitleById($rec->assetId), array('planning_Tasks', 'list', 'folder' => $rec->folderId, 'assetId' => $rec->assetId), false, 'ef_icon=img/16/equipment.png,title=Филтър по център на дейност и оборудване');
                }
            }
        }

        // Показване на разширеното описание на артикула
        if (isset($fields['-single'])) {
            $eFields = static::getExpectedDeviations($rec, true);
            $row->deviationNettoNotice = $eFields['notice'];
            $row->deviationNettoWarning = $eFields['warning'];
            $row->deviationNettoCritical = $eFields['critical'];

            $dependantTaskArr = planning_StepConditions::getPrevAndNextTasks($rec);
            if (!empty($dependantTaskArr[$rec->id]['previous'])) {
                $dependantTask = planning_StepConditions::renderTaskBlock($dependantTaskArr[$rec->id]['previous'], 'bigBar');
                $row->dependantProgress = implode("", $dependantTask);
            }

            if (isset($rec->assetId)) {
                if (planning_AssetResources::haveRightFor('recalctime', (object)array('id' => $rec->assetId)) && !Mode::is('printing')) {
                    if (!in_array($rec->state, array('draft', 'waiting', 'rejected'))) {
                        $row->recalcBtn = ht::createLink('', array('planning_AssetResources', 'recalcTimes', $rec->assetId, 'ret_url' => true), false, 'ef_icon=img/16/arrow_refresh.png, title=Преизчисляване на времената на операциите към оборудването');
                    }
                }
            }

            if (!Mode::is('printing')) {
                $row->toggleBtn = "<a href=\"javascript:toggleDisplay('{$rec->id}inf')\"  style=\"background-image:url(" . sbf('img/16/toggle1.png', "'") . ');" class=" plus-icon more-btn"> </a>';
                $row->productDescriptionStyle = 'display: none;';
            }

            $jobRec = planning_Jobs::fetch("#containerId = {$rec->originId}");
            $row->productDescription = cat_Products::getAutoProductDesc($jobRec->productId, null, 'detailed', 'job');
            Mode::push('text', 'xhtml');
            $packagingData = planning_Jobs::getJobProductPackagingData($jobRec);
            unset($packagingData->listFields['user']);
            unset($packagingData->listFields['eanCode']);
            $packagingTpl = cls::get('cat_products_Packagings')->renderPackagings($packagingData);
            $row->jobProductPackagings = $packagingTpl;
            Mode::pop('text', 'xhtml');

            $row->tId = $rec->id;

            if (core_Packs::isInstalled('batch')) {
                if ($BatchDef = batch_Defs::getBatchDef($rec->productId)) {
                    if ($BatchDef instanceof batch_definitions_Job) {
                        $row->batch = $BatchDef->getDefaultBatchName($origin->that);
                    }
                }
            }

            if (empty($rec->labelPackagingId)) {
                $row->labelPackagingId = "<span class='quiet'>N/A</span>";
                $row->labelQuantityInPack = "<span class='quiet'>N/A</span>";
            } else {
                if (empty($rec->labelQuantityInPack)) {
                    $labelProductId = ($rec->isFinal == 'yes') ? $origin->fetchField('productId') : $rec->productId;
                    $quantityInPackDefault = static::getDefaultQuantityInLabelPackagingId($labelProductId, $rec->measureId, $rec->labelPackagingId, $rec->id);
                    $expectedLabelQuantityInPack = $quantityInPackDefault;
                    $quantityInPackDefault = "<span style='color:blue'>" . core_Type::getByName('double(smartRound)')->toVerbal($quantityInPackDefault) . "</span>";
                    $quantityInPackHint = ($rec->isFinal == 'yes') ? 'Средно от въведения прогрес или от опаковката/мярката на артикула' : 'От опаковката/мярката на артикула';
                    $quantityInPackDefault = ht::createHint($quantityInPackDefault, $quantityInPackHint);
                    $row->labelQuantityInPack = $quantityInPackDefault;
                } else {
                    $row->labelQuantityInPack .= " {$row->measureId}";
                    $expectedLabelQuantityInPack = $rec->labelQuantityInPack;
                }

                if (cat_UoM::fetchField($rec->labelPackagingId, 'type') != 'uom') {
                    $expectedLabelPacks = core_Type::getByName('double(smartRound,maxDecimals=1)')->toVerbal($rec->plannedQuantity / $expectedLabelQuantityInPack);

                    // Преброяване на уникалните произв. номера
                    $dQuery = planning_ProductionTaskDetails::getQuery();
                    $checkProductId = ($rec->isFinal == 'yes') ? $jobRec->productId : $rec->productId;
                    $dQuery->where("#taskId = {$rec->id} AND #productId = {$checkProductId} AND #type = 'production' AND #state != 'rejected'");
                    $dQuery->XPR('countSerials', 'int', 'COUNT(DISTINCT(#serial))');
                    $producedCountVerbal = core_Type::getByName('int')->toVerbal($dQuery->fetch()->countSerials);
                    $expectedLabelPacks = "<span style='color:green'>{$producedCountVerbal}</span> / {$expectedLabelPacks}";
                    $row->labelPackagingId .= ", {$expectedLabelPacks} " . tr('бр.');
                }
            }

            $row->labelTemplate = (isset($rec->labelTemplate)) ? ht::createHint(ht::createLink("№{$rec->labelTemplate}", label_Templates::getSingleUrlArray($rec->labelTemplate)), label_Templates::getTitleById($rec->labelTemplate)) : "<span class='quiet'>N/A</span>";

            // Линк към отпечаванията ако има
            if (label_Prints::haveRightFor('list')) {
                if ($printCount = label_Prints::count("#classId = {$mvc->getClassId()} AND #objectId = {$rec->id}")) {
                    $row->printCount = core_Type::getByName('int')->toVerbal($printCount);
                    $row->printCount = ht::createLink($row->printCount, array('label_Prints', 'list', 'classId' => $mvc->getClassId(), 'objectId' => $rec->id, 'ret_url' => true));
                }
            }

            if ($rec->isFinal == 'yes') {
                $row->productCaption = tr('Финален етап');
                $row->originProductId = cat_Products::getHyperlink($origin->fetchField('productId'), true);
            } else {
                $row->productCaption = tr('Етап');
                unset($row->isFinal);
            }

            $row->originId = $origin->getHyperlink(true);
            $row->jobState = $origin->fetchField('state');

            if (isset($rec->wasteProductId)) {
                $row->wasteProductId = cat_Products::getHyperlink($rec->wasteProductId, true);
                $row->wasteStart = isset($row->wasteStart) ? $row->wasteStart : 'n/a';
                $row->wastePercent = isset($row->wastePercent) ? $row->wastePercent : 'n/a';
                $row->wasteProductId = ht::createHint($row->wasteProductId, "Начален|*: {$row->wasteStart}, |Допустим|*: {$row->wastePercent}");
            }

            if (!empty($rec->employees)) {
                $employees = planning_Hr::getPersonsCodesArr($rec->employees, true);
                $row->employees = implode(', ', $employees);
            }

            if ($rec->isFinal == 'yes') {
                $compareMeasureId = cat_Products::fetchField($jobRec->productId, 'measureId');
                $expectedMeasureQuantityInPack = ($rec->measureId == $compareMeasureId) ? 1 : cat_products_Packagings::getPack($jobRec->productId, $rec->measureId)->quantity;
            } else {
                $compareMeasureId = cat_Products::fetchField($rec->productId, 'measureId');
                $expectedMeasureQuantityInPack = ($rec->measureId == $compareMeasureId) ? 1 : cat_products_Packagings::getPack($rec->productId, $rec->measureId)->quantity;
            }

            // Ако има разминаване с очакваното к-во в опаковка да се покаже
            if ($rec->quantityInPack != $expectedMeasureQuantityInPack) {
                $dUoms = cat_UoM::getShortName($rec->measureId) . "/" . cat_UoM::getShortName($compareMeasureId);
                $quantityInPackVerbal = core_Type::getByName('double(smartRound)')->toVerbal($rec->quantityInPack);
                $diffMsg = "Отношението на |{$dUoms}|* се разминава със записаното при създаването на Операцията|*: {$quantityInPackVerbal}! |Приключете операцията и създайте нова (без да клонирате!), за да продължите с актуалното количество!";
                $row->plannedQuantity = ht::createHint($row->plannedQuantity, $diffMsg, 'img/16/red-warning.png', false);
            }

            $canStore = cat_Products::fetchField($rec->productId, 'canStore');
            $row->producedCaption = ($canStore == 'yes') ? tr('В склад') : tr('Изпълн.');

            // Ако има избрано оборудване
            if (isset($rec->assetId)) {
                $hintSimultaneity = false;
                $assetSimultaneity = $rec->simultaneity;
                if (!isset($assetSimultaneity)) {
                    $assetSimultaneity = planning_AssetResources::fetchField($rec->assetId, 'simultaneity');
                    $hintSimultaneity = true;
                }
                $row->simultaneity = core_Type::getByName('int')->toVerbal($assetSimultaneity);
                if ($hintSimultaneity) {
                    $row->simultaneity = ht::createHint("<span style='color:blue'>{$row->simultaneity}</span>", 'Зададено е в оборудването');
                }

                if (isset($rec->prevAssetId)) {
                    $row->assetId = ht::createHint($row->assetId, "Предишно оборудване|*: " . planning_AssetResources::getTitleById($rec->prevAssetId), 'warning', false);
                }

                if (haveRole('debug')) {
                    $row->orderByAssetId = isset($rec->orderByAssetId) ? $row->orderByAssetId : 'n/a';
                    $row->assetId = ht::createHint($row->assetId, "Подредба|*: {$row->orderByAssetId}", 'img/16/bug.png');
                }

                if (!in_array($rec->state, array('closed', 'rejected'))) {

                    // Показва се след коя ще започне
                    $startAfter = $mvc->getPrevOrNextTask($rec);
                    if (isset($startAfter)) {
                        $startAfterTitle = $mvc->getAlternativeTitle($startAfter, true);
                        if(!Mode::isReadOnly()){
                            $singleUrl = planning_Tasks::getSingleUrlArray($startAfter);
                            if(countR($singleUrl)){
                                $startAfterTitle = ht::createLink($startAfterTitle, $singleUrl);
                            }

                            $startAfterTitleFull = $mvc->getAlternativeTitle($startAfter);
                            $startAfterTitle = ht::createHint($startAfterTitle, "|*{$startAfterTitleFull}", 'notice', false);
                        }
                        $row->startAfter = $startAfterTitle;
                    } else {
                        $row->startAfter = tr('Първа за оборудването');
                    }
                }
            } else {
                $row->assetId = "<span class='quiet'>N/A</span>";
                $row->assetId = ht::createHint($row->assetId, 'Операцията няма да може да стане заявка/да бъде активирана, докато няма избрано оборудване|*!', 'warning');
            }

            if($rec->state != 'rejected'){
                $taskCount = planning_Tasks::count("#originId = {$rec->originId} AND #saoOrder IS NOT NULL AND #state != 'rejected'");
                $row->taskCount = core_Type::getByName('int')->toVerbal($taskCount);
            }

            $prevTaskHint = false;
            $prevTaskId = $rec->manualPreviousTask;
            if(empty($row->manualPreviousTask)){
                $prevTaskId = key($mvc->getPreviousTaskIds($rec, 1));
                $prevTaskHint = true;
            }
            if(!empty($prevTaskId)){
                $row->manualPreviousTask = $mvc->getHyperlink($prevTaskId);
                if($prevTaskHint){
                    $row->manualPreviousTask = ht::createHint($row->manualPreviousTask, 'Автоматично определена', 'notice', false);
                }
            }

            if(!Mode::isReadOnly()){
                if($mvc->haveRightFor('editprevioustask', $rec)){
                    $rec->_hasManualPreviousTask = true;
                    if(empty($row->manualPreviousTask)){
                        $row->manualPreviousTask = tr('Няма');
                    }
                    $row->manualPreviousTask .= ht::createLink('', array($mvc, 'editprevioustask', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/edit-icon.png,title=Задаване/промяна на предходен етап|*!');
                }
            }

            // Показване на невложеното от предходна ПО
            $notConvertedFromPreviousTasks = array();
            $previousTaskIds = $mvc->getPreviousTaskIds($rec);
            foreach ($previousTaskIds as $prevTaskId){
                $leftOver = $mvc->getLeftOverQuantityInStock($prevTaskId);
                if(!empty($leftOver)){
                    $prevTaskRec = $mvc->fetch($prevTaskId);
                    $prevRecStepMeasureId = cat_Products::fetchField($prevTaskRec->productId, 'measureId');
                    $prevRecStepMeasureVerbal = cat_UoM::getShortName($prevRecStepMeasureId);
                    $tmpString = "{$prevTaskRec->saoOrder}. " . cat_Products::getTitleById($prevTaskRec->productId) . ": {$leftOver} {$prevRecStepMeasureVerbal}";
                    $notConvertedFromPreviousTasks[] = $tmpString;
                }
            }
            if(countR($notConvertedFromPreviousTasks)){
                $row->notConvertedFromPreviousTasks = implode('<br>', $notConvertedFromPreviousTasks);
                $row->notConvertedFromPreviousTasks = "<b class='red'>{$row->notConvertedFromPreviousTasks}</b>";
            } else {
                if(!empty($row->manualPreviousTask)){
                    $row->notConvertedFromPreviousTasks = tr("Няма");
                }
            }

            if(!empty($rec->mandatoryDocuments)){
                $row->mandatoryDocuments = ht::createHint(tr('Има посочени'), $row->mandatoryDocuments);
            } else {
                $row->mandatoryDocuments = tr('Няма');
            }

            $jobNotes = $origin->fetchField('notes');
            if(!empty($jobNotes)){
                $row->jobNotes = core_Type::getByName('richtext(hideTextAfterLength=100)')->toVerbal($jobNotes);
            }

            // Показване на теглото на брака в сингъла на ПО
            if(!empty($rec->scrappedQuantity) && $rec->showadditionalUom == 'yes'){
                if(!cat_UoM::isWeightMeasure($rec->measureId)){
                    $sQuery = planning_ProductionTaskDetails::getQuery();
                    $sQuery->where("#taskId = {$rec->id} AND #type = 'scrap' AND #state != 'rejected' AND #productId = {$rec->productId}");
                    $sQuery->XPR('sum', 'double', 'SUM(#netWeight)');
                    $scrappedNetWeight = $sQuery->fetch()->sum;
                    if($scrappedNetWeight){
                        $scrappedNetWeightVerbal = core_Type::getByName('cat_type_Weight')->toVerbal($scrappedNetWeight);
                        $row->scrappedQuantity = "{$row->scrappedQuantity} <small style='font-weight:normal;color:darkblue;font-style:italic;' class='secondMeasure'>({$scrappedNetWeightVerbal}) </small>";
                    }
                }
            }
        }

        if (empty($rec->indTime)) {
            $row->indTime = "<span class='quiet'>N/A</span>";
        }

        Mode::push('text', 'plain');
        $plannedQuantityVerbal = $mvc->getFieldType('plannedQuantity')->toVerbal($rec->plannedQuantity) . " " . cat_UoM::getShortName($rec->measureId);
        Mode::pop('text');
        $row->progress = "<span style='color:{$grey};'>{$row->progress}</span>";
        core_Debug::stopTimer('RENDER_VERBAL');

        return $row;
    }


    /**
     * Коя е предходната ПО в рамките на заданието
     *
     * @param stdClass|int $rec
     * @param int|null $limit
     * @return array
     */
    private function getPreviousTaskIds($rec, $limit = null)
    {
        $rec = $this->fetchRec($rec);
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#saoOrder < '{$rec->saoOrder}' AND #originId = {$rec->originId} AND #state != 'rejected' AND #id != '{$rec->id}'");
        $tQuery->orderBy('saoOrder', "DESC");

        return arr::extractValuesFromArray($tQuery->fetchAll(), 'id');
    }


    /**
     * Какво е дефолтното количество в опаковката за етикетиране
     *
     * @param int $productId
     * @param int $measureId
     * @param int $labelPackagingId
     * @param int|null $taskId
     * @return float|int $quantityInPackDefault
     */
    public static function getDefaultQuantityInLabelPackagingId($productId, $measureId, $labelPackagingId, $taskId = null)
    {
        $productMeasureId = cat_Products::fetchField($productId, 'measureId');
        if (isset($taskId)) {

            // Показване на средното к-во в опаковка от реалните данни
            $taskRec = planning_Tasks::fetch($taskId);
            if ($taskRec->isFinal != 'yes') {
                $dQuery = planning_ProductionTaskDetails::getQuery();
                $dQuery->where("#taskId = {$taskId} AND #productId = {$productId} AND #type='production' AND #state != 'rejected'");
                $dRecs = array();
                while ($dRec = $dQuery->fetch()) {
                    $dRecs[$dRec->serial] += $dRec->quantity;
                }
                $detailsCount = countR($dRecs);
                if ($detailsCount) {
                    $round = cat_UoM::fetchField($measureId, 'round');
                    $res = round((array_sum($dRecs) / $detailsCount) / $taskRec->quantityInPack, $round);

                    return $res;
                }
            }
        }

        $packRec = cat_products_Packagings::getPack($productId, $labelPackagingId);
        $quantityInPackDefault = is_object($packRec) ? $packRec->quantity : 1;

        if (isset($measureId) && $productMeasureId != $measureId) {
            $packRec1 = cat_products_Packagings::getPack($productId, $measureId);
            $quantityInSecondMeasure = is_object($packRec1) ? $packRec1->quantity : 1;
            $quantityInPackDefault = (1 / $quantityInSecondMeasure) * $quantityInPackDefault;
            $round = cat_UoM::fetchField($measureId, 'round');
            $quantityInPackDefault = round($quantityInPackDefault, $round);
        }

        return $quantityInPackDefault;
    }


    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();

        $row->title = self::getRecTitle($rec);
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->recTitle = $row->title;
        $row->state = $rec->state;
        $row->subTitle = doc_Containers::getDocument($rec->originId)->getShortHyperlink();

        return $row;
    }


    /**
     * Прави заглавие на ПО от данните в записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $me = cls::get(get_called_class());
        $title = "Opr{$rec->id} - {$me->getStepTitle($rec->productId)}";
        if (!empty($rec->subTitle)) {
            $title .= " {$me->getFieldType('subTitle')->toVerbal($rec->subTitle)}";
        }

        return $title;
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;

        if ($form->isSubmitted()) {

            // Ако е финална операция
            $productId = $rec->productId;
            if ($rec->isFinal == 'yes') {
                $productId = doc_Containers::getDocument($rec->originId)->fetchField('productId');
                if ($otherTaskId = planning_Tasks::fetchField("#originId = {$rec->originId} AND #state != 'rejected' AND #isFinal = 'yes' AND #productId != {$rec->productId}")) {
                    $otherTaskLink = planning_Tasks::getHyperlink($otherTaskId, true);
                    $form->setWarning('productId', "По заданието вече има операция за друг финален етап|*: {$otherTaskLink}");
                }
            }

            $packRec = cat_products_Packagings::getPack($productId, $rec->measureId);
            $rec->quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
            $rec->title = cat_Products::getTitleById($rec->productId);

            planning_Centers::checkDeviationPercents($form);

            if (in_array($form->cmd, array('save_pending', 'save_pending_new'))) {
                if (empty($rec->indTime) && empty($rec->timeDuration) && (empty($rec->timeStart) || empty($rec->timeEnd))) {
                    $form->setError('timeDuration,indTime,timeStart,timeEnd', "Необходими са данни за да се изчисли продължителността на операцията|*!");
                }
            }

            if (in_array($rec->state, array('active', 'wakeup', 'stopped')) && !$form->_cloneForm) {
                if (empty($rec->timeDuration) && empty($rec->assetId)) {
                    $form->setError('timeDuration,assetId,indTime', "Продължителността/нормата и оборудването са задължителни при започната операция|*!");
                }
            }

            if ($rec->timeStart && $rec->timeEnd && ($rec->timeStart > $rec->timeEnd)) {
                $form->setError('timeEnd', 'Крайният срок трябва да е след началото на операцията');
            }

            if (!empty($rec->timeStart) && !empty($rec->timeDuration) && !empty($rec->timeEnd)) {
                if (strtotime(dt::addSecs($rec->timeDuration, $rec->timeStart)) != strtotime($rec->timeEnd)) {
                    $form->setWarning('timeStart,timeDuration,timeEnd', 'Въведеното начало плюс продължителността не отговарят на въведената крайната дата');
                }
            }

            $whenToUnsetStartAfter = ((empty($rec->id) || $rec->state == 'draft') && !empty($rec->startAfter) && $form->cmd == 'save');
            if ($whenToUnsetStartAfter) {
                $form->setWarning('startAfter', "Операцията е чернова. Автоматично ще се добави последна към избраното оборудване|*!");
            }

            if (!$form->rec->_editActive) {
                if (isset($rec->wasteProductId)) {
                    $wasteRec = cat_Products::fetch($rec->wasteProductId, 'measureId,generic');
                    if ($wasteRec->generic == 'yes') {
                        $form->setError('wasteProductId', "Избраният отпадък е генеричен (обобщаващ)|*! |Трябва да бъде заместен с конкретния такъв|*!");
                    }

                    if (($rec->wasteStart + $rec->wastePercent) <= 0) {
                        $form->setError('wasteStart,wastePercent', "Количеството на отпадъка не може да се сметне|*!");
                    }
                } else {
                    if (isset($rec->wasteStart) || isset($rec->wastePercent)) {
                        $form->setError('wasteProductId,wasteStart,wastePercent', "Не е посочен отпадък|*!");
                    }
                }
            }

            if (!$form->gotErrors()) {
                $rec->_fromForm = true;

                // Ако не е въведено точен час се добавя началото на работното време на машината
                if (!empty($rec->timeStart) && isset($rec->assetId)) {
                    if (strpos($rec->timeStart, ' 00:00:00') !== false) {
                        if ($scheduleId = planning_AssetResources::getScheduleId($rec->assetId)) {
                            $timeStartDate = dt::verbal2mysql($rec->timeStart, false);
                            $startTimes = hr_Schedules::getStartingTimes($scheduleId, $timeStartDate, $timeStartDate);
                            if (isset($startTimes[$timeStartDate])) {
                                $rec->timeStart = str_replace(' 00:00:00', " {$startTimes[$timeStartDate]}", $rec->timeStart);
                            }
                        }
                    }
                }

                if ($whenToUnsetStartAfter) {
                    $rec->startAfter = null;
                }

                if(empty($rec->timeDuration)){
                    if(!empty($rec->timeStart) && !empty($rec->timeEnd)){
                        $rec->timeDuration = dt::secsBetween($rec->timeEnd, $rec->timeStart);
                    }
                }
            }
        }
    }


    /**
     * Добавя допълнителни полетата в антетката
     *
     * @param core_Master $mvc
     * @param NULL|array $resArr
     * @param object $rec
     * @param object $row
     */
    protected static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
        unset($resArr['ident']);
        unset($resArr['versionAndDate']);
        unset($resArr['createdBy']);
        unset($resArr['createdOn']);

        $display = (in_array($rec->state, array('pending', 'draft', 'waiting', 'rejected', 'stopped')) || haveRole('task,officer')) ? 'block' : 'none';
        $toggleClass = (in_array($rec->state, array('pending', 'draft', 'waiting', 'rejected', 'stopped')) || haveRole('task,officer')) ? 'show-btn' : '';

        if (Mode::is('printing')) {
            $resArr['info'] = array('name' => tr('Операция'), 'val' => tr("|*<table style='display:{$display}' class='docHeaderVal'>
                <tr><td style='font-weight:normal'>№:</td><td>[#ident#]</td></tr>
                <tr><td style='font-weight:normal'>|Създаване от|*:</td><td>[#createdBy#]</td></tr>
                <tr><td style='font-weight:normal'>|Създаване на|*:</td><td>[#createdOn#]</td></tr>
                </table>"));
        }

        if ($rec->showadditionalUom == 'no') {
            unset($row->totalWeight);
            unset($row->totalNetWeight);
            unset($row->totalNetWeight);
        } else {
            $centerRec = planning_Centers::fetch("#folderId = {$rec->folderId}", 'useTareFromParamId,useTareFromPackagings');
            $row->totalWeight = empty($rec->totalWeight) ? "<span class='quiet'>N/A</span>" : $row->totalWeight;
            if (empty($centerRec->useTareFromParamId) && empty($centerRec->useTareFromPackagings)) {
                unset($row->totalNetWeight);
            } else {
                $row->totalNetWeight = empty($rec->totalNetWeight) ? "<span class='quiet'>N/A</span>" : $row->totalNetWeight;
            }
        }

        $canStore = cat_Products::fetchField($rec->productId, 'canStore');
        if ($canStore == 'yes') {
            $resArr['additional'] = array('name' => tr('Изчисляване на тегло'), 'val' => tr("|*<table style='display:{$display}' class='docHeaderVal'>
                <!--ET_BEGIN totalWeight--><tr><td style='font-weight:normal'>|Общо бруто|*:</td><td>[#totalWeight#]</td></tr><!--ET_END totalWeight-->
                <!--ET_BEGIN totalNetWeight--><tr><td style='font-weight:normal'>|Общо нето|*:</td><td>[#totalNetWeight#]</td></tr><!--ET_END totalNetWeight-->
                <!--ET_BEGIN notifications--><tr><td colspan='2'>[#notifications#]</td></tr><!--ET_END notifications-->
                <tr><td style='font-weight:normal'>|Режим|*:</td><td>[#showadditionalUom#]</td></tr>
                </table>"));

            if ($rec->showadditionalUom == 'yes') {
                $row->notifications = implode(' ', array($row->deviationNettoNotice, $row->deviationNettoWarning, $row->deviationNettoCritical));
            }
        }

        $resArr['labels'] = array('name' => tr('Етикетиране'), 'val' => tr("|*<table style='display:{$display}' class='docHeaderVal'>
                <tr><td style='font-weight:normal'>|Производ. №|*:</td><td>[#labelType#]</td></tr>
                <tr><td style='font-weight:normal'>|Опаковка|*:</td><td>[#labelPackagingId#]</td></tr>
                <tr><td style='font-weight:normal'>|В опаковка|*:</td><td>[#labelQuantityInPack#]</td></tr>
                <tr><td style='font-weight:normal'>|Шаблон|*:</td><td>[#labelTemplate#]</td></tr>
                <!--ET_BEGIN printCount-->
                <tr><td style='font-weight:normal'>|Отпечатвания|*:</td><td>[#printCount#]</td></tr>
                <!--ET_END printCount-->
                </table>"));

        $resArr['indTimes'] = array('name' => tr('Заработка'), 'val' => tr("|*<table style='display:{$display}' class='docHeaderVal'>
                <tr><td style='font-weight:normal'>|Норма|*:</td><td>[#indTime#]</td></tr>
                <tr><td style='font-weight:normal'>|Мярка|*:</td><td>[#indPackagingId#]</td></tr>
                <tr><td style='font-weight:normal'>|Разпределяне|*:</td><td>[#indTimeAllocation#]</td></tr>
                <!--ET_BEGIN simultaneity--><tr><td style='font-weight:normal'>|Едновременност|*:</td><td>[#simultaneity#]</td></tr><!--ET_END simultaneity-->
                </table>"));

        if (core_Packs::isInstalled('batch')) {
            if ($rec->followBatchesForFinalProduct != 'no') {
                $batchTpl = planning_ProductionTaskDetails::renderBatchesSummary($rec);
                if ($batchTpl instanceof core_ET) {
                    $resArr['batches'] = array('name' => tr('Партиди'), 'val' => $batchTpl->getContent());
                }
            }
        }

        if (!Mode::is('printing')) {
            $toggleBtnJs = "javascript:toggleDisplayByClass('btnShowHeaderInfo', 'docHeaderVal')";
            $hideBtn = ht::createLink('', $toggleBtnJs, false, array('id' => 'btnShowHeaderInfo', 'class' => "more-btn {$toggleClass}", 'title' => tr('Показване/Скриване на настройките на операцията')));
            $hideBtn = $hideBtn->getContent();
            $resArr['toggle'] = array('name' => "<div style='float:right'>{$hideBtn}</div>", 'val' => tr(""));
        }

        if (isset($rec->indPackagingId) && !empty($rec->indTime)) {
            $row->indTime = core_Type::getByName("planning_type_ProductionRate(measureId={$rec->indPackagingId})")->toVerbal($rec->indTime);
        }
    }


    /**
     * След подготовка на антетката
     */
    protected static function on_AfterPrepareHeaderLines($mvc, &$res, $headerArr)
    {
        if (Mode::is('screenMode', 'narrow') && !Mode::is('printing')) {
            $res = new ET("<table class='subInfo'>");
            foreach ((array)$headerArr as $value) {
                $val = new ET("<td class='antetkaCell' style=\"padding-bottom: 10px;\"><b>{$value['val']}</b></td>");
                $name = new ET("<td class='nowrap' style='width: 1%;border-bottom: 1px solid #ccc; font-weight: bold;'>{$value['name']}</td>");
                $res->append('<tr>');
                $res->append($name);
                $res->append('</tr><tr>');
                $res->append($val);
                $res->append('</tr>');
            }
            $res->append("</table>");
        }
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
        core_Debug::startTimer('UPDATE_TASK_MASTER');
        $rec = $this->fetch($id);
        $originalProgress = $rec->progress;
        $updateFields = 'totalQuantity,totalWeight,totalNetWeight,scrappedQuantity,producedQuantity,progress,modifiedOn,modifiedBy,prevAssetId,assetId,lastProgress,lastProgressProduction,firstProgress';

        // Ако е записано в сесията, че е подменена машината да се подмени и в операцията
        if ($newAssetId = Mode::get("newAsset{$rec->id}")) {
            $rec->prevAssetId = $rec->assetId;
            $rec->assetId = $newAssetId;
            Mode::setPermanent("newAsset{$rec->id}", null);

            // Новата и старата машина се заопашават
            $this->reorderTasksInAssetId[$rec->assetId] = $rec->assetId;
            if (isset($rec->prevAssetId)) {
                $this->reorderTasksInAssetId[$rec->prevAssetId] = $rec->prevAssetId;
            }
            $this->logWrite("Промяна на оборудването ", $rec->id);
        }

        // Колко е общото к-во досега
        $dQuery = planning_ProductionTaskDetails::getQuery();
        $productId = ($rec->isFinal == 'yes') ? planning_Jobs::fetchField("#containerId = {$rec->originId}", 'productId') : $rec->productId;
        $dQuery->where("#taskId = {$rec->id} AND #state != 'rejected'");

        $rec->lastProgressProduction = $rec->lastProgress = $rec->firstProgress = null;
        $rec->totalWeight = $rec->totalQuantity = $rec->scrappedQuantity = $rec->totalNetWeight = 0;
        while ($dRec = $dQuery->fetch()) {
            if($dRec->productId == $productId && in_array($dRec->type, array('production', 'scrap'))){
                if ($dRec->type == 'production') {
                    $quantity = $dRec->quantity / $rec->quantityInPack;
                    $rec->totalQuantity += $quantity;
                    $rec->totalWeight += $dRec->weight;
                    if (isset($dRec->netWeight)) {
                        $rec->totalNetWeight += $dRec->netWeight;
                    }
                } else {
                    $rec->scrappedQuantity += $dRec->quantity / $rec->quantityInPack;
                    $rec->totalWeight -= $dRec->weight;
                    if (isset($dRec->netWeight)) {
                        $rec->totalNetWeight -= $dRec->netWeight;
                    }
                }
            }

            // Преизчисляване на последните/първите прогреси
            $progressDate = $dRec->date ?? $dRec->createdOn;
            if(in_array($dRec->type, array('production', 'scrap'))){
                if($progressDate > $rec->lastProgressProduction){
                    $rec->lastProgressProduction = $progressDate;
                }
            }
            if($progressDate > $rec->lastProgress){
                $rec->lastProgress = $progressDate;
            }
            if(is_null($rec->firstProgress) || $progressDate < $rec->firstProgress){
                $rec->firstProgress = $progressDate;
            }
        }

        // Изчисляваме колко % от зададеното количество е направено
        if (!empty($rec->plannedQuantity)) {
            $percent = ($rec->totalQuantity - $rec->scrappedQuantity) / $rec->plannedQuantity;
            $rec->progress = round($percent, 2);
        }

        $rec->progress = max(array($rec->progress, 0));

        $producedQuantity = 0;
        $noteQuery = planning_DirectProductionNote::getQuery();
        $noteQuery->where("#productId = {$productId} AND #state = 'active' AND #originId = {$rec->containerId}");
        while ($nRec = $noteQuery->fetch()) {
            if ($nRec->packagingId == $rec->measureId) {
                $producedQuantity += $nRec->packQuantity;
            } else {
                $producedQuantity += $nRec->quantity;
            }
        }

        // Обновяване на произведеното по заданието
        if ($producedQuantity != $rec->producedQuantity) {
            planning_Jobs::updateProducedQuantity($rec->originId);
        }
        $rec->producedQuantity = $producedQuantity;

        // Ако има промяна в прогреса (само ако не е приключена операцията)
        $autoActivation = ($rec->state == 'pending' && planning_ProductionTaskDetails::count("#taskId = {$rec->id}"));
        if($rec->state != 'closed'){
            $reorder = false;
            if ($rec->progress > $originalProgress) {

                // Ако прогреса е увеличен - става първа
                $rec->orderByAssetId = 0.5;
                $reorder = true;
            } elseif($autoActivation) {
                $rec->orderByAssetId = null;

                // Ако само е активирана - БЕЗ да е увеличен прогреса
                $query = static::getQuery();
                $query->where("#assetId = {$rec->assetId} AND  #state IN ('pending', 'stopped')");
                $query->orderBy("orderByAssetId", 'ASC');
                $query->show('id,orderByAssetId');
                $query->limit(1);
                $firstPendingRec = $query->fetch();

                // Намества се преди първата спряна/заявка
                if(is_object($firstPendingRec)){
                    $rec->orderByAssetId = $firstPendingRec->orderByAssetId - 0.5;
                } else {

                    // Ако няма заявки/спрени - мести се след първата активна/събудена
                    $query1 = static::getQuery();
                    $query1->where("#assetId = {$rec->assetId} AND #state IN ('active', 'wakeup')");
                    $query1->orderBy("orderByAssetId", 'ASC');
                    $query1->show('id,orderByAssetId');
                    $query1->limit(1);
                    $lastActiveRec = $query1->fetch();
                    if(is_object($lastActiveRec)){
                        $rec->orderByAssetId = $lastActiveRec->orderByAssetId + 0.5;
                    } else {

                        // Ако няма и такива - става първа
                        $rec->orderByAssetId = 0.5;
                    }
                }
                $reorder = true;
            }

            // Ако ще се преподреждат
            if($reorder){
                if (isset($rec->assetId)) {
                    $this->reorderTasksInAssetId[$rec->assetId] = $rec->assetId;
                }
                $updateFields .= ',orderByAssetId';
                $rec->_stopReorder = true;
            }
        }

        // При първо добавяне на прогрес, ако е в заявка - се активира автоматично
        if ($autoActivation) {
            planning_plg_StateManager::changeState($this, $rec, 'activate');
            $this->logWrite('Активиране при прогрес', $rec->id);
            core_Statuses::newStatus('Операцията е активирана след добавяне на прогрес|*!');
        }

        $res = $this->save_($rec, $updateFields);
        plg_Search::forceUpdateKeywords($this, $rec);

        core_Debug::stopTimer('UPDATE_TASK_MASTER');
        core_Debug::log('END UPDATE_TASK_MASTER: ' . round(core_Debug::$timers['UPDATE_TASK_MASTER']->workingTime, 2));

        return $res;
    }


    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $Cover = doc_Folders::getCover($folderId);

        return $Cover->isInstanceOf('planning_Centers');
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' || $action == 'edit' || $action == 'changestate') {
            if (isset($rec->originId)) {
                $origin = doc_Containers::getDocument($rec->originId);
                $state = $origin->fetchField('state');
                $notAllowedStates = ($action == 'edit') ? array('closed', 'rejected', 'draft') : array('closed', 'rejected', 'draft', 'stopped');
                if (in_array($state, $notAllowedStates)) {
                    $requiredRoles = 'no_one';
                }
            }
        }

        if ($action == 'add') {
            if (isset($rec->originId)) {
                // Може да се добавя само към активно задание
                if ($origin = doc_Containers::getDocument($rec->originId)) {
                    if (!$origin->isInstanceOf('planning_Jobs')) {
                        $requiredRoles = 'no_one';
                    }
                }
            } elseif ($rec->folderId) {
                $requiredRoles = 'no_one';
            }
        }

        // Ако има прогрес, операцията не може да се оттегля
        if ($action == 'reject' && isset($rec)) {
            if (planning_ProductionTaskDetails::fetchField("#taskId = {$rec->id} AND #state != 'rejected'")) {
                $requiredRoles = 'no_one';
            } elseif (!haveRole('task,ceo', $userId)) {
                $requiredRoles = 'no_one';
            }
        }

        if ($action == 'restore' && $rec) {
            if (isset($rec->originId)) {
                $origin = doc_Containers::getDocument($rec->originId);
                $state = $origin->fetchField('state');
                if ($state == 'rejected') {
                    $requiredRoles = 'no_one';
                }
            }
        }

        if ($action == 'printlabel' && isset($rec)) {
            if (empty($rec->labelPackagingId)) {
                $requiredRoles = 'no_one';
            }
        }

        if ($action == 'createjobtasks' && isset($rec)) {
            if (empty($rec->type) || empty($rec->jobId)) {
                $requiredRoles = 'no_one';
            } elseif (!in_array($rec->type, array('all', 'clone', 'cloneAll'))) {
                $requiredRoles = 'no_one';
            } else {
                $jobRec = planning_Jobs::fetch($rec->jobId);
                if (!$mvc->haveRightFor('add', (object)array('folderId' => $rec->folderId, 'originId' => $jobRec->containerId))) {
                    $requiredRoles = 'no_one';
                } else {
                    if ($rec->type == 'clone') {
                        if (empty($rec->cloneId)) {
                            $requiredRoles = 'no_one';
                        }
                    } elseif ($rec->type == 'all') {
                        $defaultTasks = cat_Products::getDefaultProductionTasks($jobRec, $jobRec->quantity);

                        $defaultTaskCount = countR($defaultTasks);
                        if (!$defaultTaskCount) {
                            $requiredRoles = 'no_one';
                        } else {
                            $tQuery = planning_Tasks::getQuery();
                            $tQuery->where("#originId = {$jobRec->containerId} AND #systemId IS NOT NULL AND #state != 'rejected'");
                            $tQuery->show('systemId');
                            $exSystemIds = arr::extractValuesFromArray($tQuery->fetchAll(), 'systemId');
                            $remainingSystemTasks = array_diff_key($defaultTasks, $exSystemIds);
                            if (!countR($remainingSystemTasks) || $defaultTaskCount == 1) {
                                $requiredRoles = 'no_one';
                            }
                        }
                    } elseif($rec->type == 'cloneAll'){
                        if (empty($rec->jobsToCloneTasksFrom)) {
                            $requiredRoles = 'no_one';
                        } else {
                            // Дали може да се клонират неклонираните от предишно задание
                            $oldTasks = planning_Tasks::getTasksByJob($rec->jobsToCloneTasksFrom, array('draft', 'waiting', 'active', 'wakeup', 'stopped', 'closed', 'pending'), false, true);
                            if(!countR($oldTasks)){
                                $requiredRoles = 'no_one';
                            } else {
                                $tQuery = planning_Tasks::getQuery();
                                $tQuery->where("#originId = {$jobRec->containerId} AND #state != 'rejected'");
                                $tQuery->in('clonedFromId', array_keys($oldTasks));
                                $tQuery->show('clonedFromId');
                                $exClonedIds = arr::extractValuesFromArray($tQuery->fetchAll(), 'clonedFromId');
                                $remainingTasksToClone = array_diff_key($oldTasks, $exClonedIds);
                                if (!countR($remainingTasksToClone) || countR($oldTasks) == 1) {
                                    $requiredRoles = 'no_one';
                                }
                            }
                        }
                    }
                }
            }
        }

        if($action == 'editprevioustask'){
            $requiredRoles = $mvc->getRequiredRoles('edit', $rec, $userId);
            if(isset($rec)){
                if(planning_Tasks::count("#originId = {$rec->originId} AND #state != 'rejected'") <= 1){
                    $requiredRoles = 'no_one';
                }
            }
        }

        if ($action == 'activate' && isset($rec)) {
            if ($rec->state != 'pending') {
                $requiredRoles = 'no_one';
            }
        }

        if ($action == 'recalcindtime' && isset($rec)) {
            if (!planning_ProductionTaskDetails::count("#taskId = {$rec->id}") || $rec->state == 'rejected') {
                $requiredRoles = 'no_one';
            }
        }

        if (($action == 'stop' || $action == 'wakeup' || $action == 'activateagain' || $action == 'activate') && isset($rec)) {
            if (!haveRole('ceo,task', $userId)) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * След успешен запис
     */
    protected static function on_AfterCreate($mvc, &$rec)
    {
        $mvc->reorderTasksByJobIds[$rec->originId] = $rec->originId;
        $mvc->setLastInJobQueue($rec);

        // Ако записа е създаден с клониране не се прави нищо
        if ($rec->_isClone === true) return;

        $saveProducts = array();
        if (isset($rec->originId)) {
            $originDoc = doc_Containers::getDocument($rec->originId);
            $originRec = $originDoc->fetch();

            // Ако е по източник
            if (isset($rec->systemId)) {
                $tasks = cat_Products::getDefaultProductionTasks($originRec, $originRec->quantity);
                if (isset($tasks[$rec->systemId])) {
                    $def = $tasks[$rec->systemId];

                    // Намираме на коя дефолтна операция отговаря и се извличат продуктите от нея
                    foreach (array('production' => 'production', 'input' => 'input', 'waste' => 'waste') as $var => $type) {
                        if (is_array($def->products[$var])) {
                            foreach ($def->products[$var] as $p) {
                                $p = (object)$p;
                                $nRec = new stdClass();
                                $nRec->taskId = $rec->id;
                                $nRec->packagingId = $p->packagingId;
                                $nRec->quantityInPack = $p->quantityInPack;
                                if($p->isPrevStep){
                                    $nRec->plannedQuantity = ($p->packQuantity / $originRec->quantity) * $rec->plannedQuantity;
                                } else {
                                    $nRec->plannedQuantity = $p->packQuantity * $rec->plannedQuantity;
                                }

                                $nRec->productId = $p->productId;
                                $nRec->type = $type;
                                $nRec->storeId = $rec->storeId;
                                $saveProducts[] = $nRec;
                            }
                        }
                    }
                }
            } else {
                $lastProductBomRec = cat_Products::getLastActiveBom($rec->productId);
                if(is_object($lastProductBomRec)){
                    $bQuery = cat_BomDetails::getQuery();
                    $bQuery->where("#bomId = {$lastProductBomRec->id} AND #parentId IS NULL");
                    while($bRec = $bQuery->fetch()){
                        $quantityP = cat_BomDetails::calcExpr($bRec->propQuantity, $bRec->params);
                        if ($quantityP == cat_BomDetails::CALC_ERROR) {
                            $quantityP = 0;
                        }

                        $nRec = new stdClass();
                        $nRec->taskId = $rec->id;
                        $nRec->packagingId = $bRec->packagingId;
                        $nRec->quantityInPack = $bRec->quantityInPack;
                        $nRec->plannedQuantity = $quantityP * $rec->plannedQuantity;
                        $nRec->productId = $bRec->resourceId;
                        $nRec->type = ($bRec->type == 'pop') ? 'waste' : (($bRec->type == 'subProduct' ? 'production': 'input'));

                        $saveProducts[] = $nRec;
                    }
                }
            }

            if ($rec->isFinal == 'yes') {
                $nRec = new stdClass();
                $nRec->taskId = $rec->id;
                $nRec->productId = $originRec->productId;
                $nRec->type = 'production';
                $saveProducts[] = $nRec;
            }
        }

        if(countR($saveProducts)){
            core_Users::forceSystemUser();
            foreach ($saveProducts as $pRec){
                planning_ProductionTaskProducts::save($pRec);
            }
            core_Users::cancelSystemUser();
        }
    }


    /**
     * Подготовка на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;

        $form->setField('state', 'input=hidden');
        $fixedAssetOptions = array();

        if (isset($rec->systemId)) {
            $form->setField('prototypeId', 'input=none');
        }
        if (empty($rec->id)) {
            if ($folderId = Request::get('folderId', 'key(mvc=doc_Folders)')) {
                unset($rec->threadId);
                $rec->folderId = $folderId;
            }
        } else {
            if ($data->action != 'clone' && in_array($rec->state, array('active', 'wakeup', 'stopped'))) {
                $form->setField('wasteProductId', 'input=none');
                $form->setField('wasteStart', 'input=none');
                $form->setField('wastePercent', 'input=none');
                $form->rec->_editActive = true;
            }
        }
        $form->setFieldTypeParams('productId', array('centerFolderId' => $rec->folderId));
        $centerRec = planning_Centers::fetch("#folderId = {$rec->folderId}");
        if (!empty($centerRec->deviationNettoNotice)) {
            $form->setField("deviationNettoNotice", "placeholder=" . $mvc->getFieldType('deviationNettoNotice')->toVerbal($centerRec->deviationNettoNotice));
        }
        if (!empty($centerRec->deviationNettoCritical)) {
            $form->setField("deviationNettoCritical", "placeholder=" . $mvc->getFieldType('deviationNettoCritical')->toVerbal($centerRec->deviationNettoCritical));
        }
        $placeholderNetWarning = !empty($centerRec->deviationNettoWarning) ? $centerRec->deviationNettoWarning : planning_Setup::get('TASK_NET_WEIGHT_WARNING');
        $form->setField("deviationNettoWarning", "placeholder=" . $mvc->getFieldType('deviationNettoWarning')->toVerbal($placeholderNetWarning));

        // За произвеждане може да се избере само артикула от заданието
        try {
            $origin = doc_Containers::getDocument($rec->originId);
        } catch (core_exception_Expect $e) {
            followRetUrl(null, '|Има грешка при създаването', 'error');
        }

        $originRec = $origin->fetch();

        // Задаване на дефолти от шаблонни ПО
        $tasks = cat_Products::getDefaultProductionTasks($originRec, $originRec->quantity);
        if (isset($rec->systemId, $tasks[$rec->systemId]) && empty($rec->id)) {
            $taskData = (array)$tasks[$rec->systemId];
            unset($taskData['products']);
            foreach ($taskData as $fieldName => $defaultValue) {
                $form->setDefault($fieldName, $defaultValue);
            }
            if (!empty($taskData['fixedAssets'])) {
                $fixedAssetOptions = keylist::toArray($taskData['fixedAssets']);
            }

            if (isset($taskData['productId'])) {
                $isFinal = planning_Steps::getRec('cat_Products', $taskData['productId'])->isFinal;

                $form->setReadOnly('productId');
                $form->setDefault('isFinal', $isFinal);
            }
        }

        $mandatoryClassOptions = planning_Steps::getMandatoryClassOptions();
        $form->setSuggestions("mandatoryDocuments", array('' => '') + $mandatoryClassOptions);

        if (isset($rec->productId)) {

            $wasteSysId = cat_Groups::getKeylistBySysIds('waste');
            $form->setFieldTypeParams("wasteProductId", array('hasProperties' => 'canStore,canConvert', 'groups' => $wasteSysId));
            $form->setField('labelType', 'input');
            $form->setField('measureId', 'input');

            $eQuery = static::getQuery();
            $eQuery->where("#id != '{$rec->id}' AND #productId = {$rec->productId}");
            $eQuery->show('indPackagingId,indTimeAllocation');
            $eQuery->orderBy('id', 'DESC');
            $lastTask4Step = $eQuery->fetch();
            if ($lastTask4Step) {
                foreach (array('indPackagingId', 'indTimeAllocation') as $exFld) {
                    if (!empty($lastTask4Step->{$fld})) {
                        $form->setDefault($exFld, $lastTask4Step->{$fld});
                    }
                }
            }

            if (core_Packs::isInstalled('batch')) {
                if (batch_Defs::getBatchDef($originRec->productId)) {
                    $form->setField('followBatchesForFinalProduct', 'input');
                }
            }

            // Ако не е системна, взима се дефолта от драйвера
            $productionData = array();
            if ($Driver = cat_Products::getDriver($rec->productId)) {
                $productionData = $Driver->getProductionData($rec->productId);
            }

            if (!isset($rec->systemId) && empty($rec->id)) {
                $defFields = arr::make("employees=employees,labelType=labelType,labelTemplate=labelTemplate,isFinal=isFinal,wasteProductId=wasteProductId,wastePercent=wastePercent,wasteStart=wasteStart,storeId=storeIn,indTime=norm,showadditionalUom=calcWeightMode,mandatoryDocuments=mandatoryDocuments");
                foreach ($defFields as $fld => $val) {
                    $form->setDefault($fld, $productionData[$val]);
                }
            }

            // Ако артикула от етапа е генеричен предлагат се за избор неговите еквивалентни
            if (isset($productionData['wasteProductId'])) {
                $wasteOptions = planning_GenericMapper::getEquivalentProducts($productionData['wasteProductId'], null, true, true);
                if (countR($wasteOptions)) {
                    $form->setFieldType('wasteProductId', 'int(maxRadio=1)');
                    $form->setOptions('wasteProductId', $wasteOptions);
                }
            }

            if (isset($productionData['description'])) {
                $form->setDefault('description', $productionData['description']);
            }
            if (isset($productionData['fixedAssets'])) {
                $fixedAssetOptions = $productionData['fixedAssets'];
            }

            $employeeOptions = planning_Hr::getByFolderId($rec->folderId, $rec->employees);
            if (countR($employeeOptions)) {
                $form->setSuggestions('employees', array('' => '') + $employeeOptions);
            } else {
                $form->setField('employees', 'input=none');
            }

            $productId4Form = ($rec->isFinal == 'yes') ? $originRec->productId : $rec->productId;
            $productRec = cat_Products::fetch($productId4Form, 'canConvert,canStore,measureId');
            $similarMeasures = cat_UoM::getSameTypeMeasures($productRec->measureId);
            if ($rec->isFinal == 'yes') {
                $form->info = "<div class='richtext-info-no-image'>" . tr('Финална операция към|* ') . $origin->getHyperlink(true) . "</div>";
                $measureOptions = array();
                $jobPackagingType = cat_UoM::fetchField($originRec->packagingId, 'type');

                // Ако заданието е в мярка тя е по-дефолт първата избрана
                if ($jobPackagingType == 'uom') {
                    $measureOptions[$originRec->packagingId] = cat_UoM::getTitleById($originRec->packagingId, false);
                } else {
                    // Ако е за опаковка, то дефолт е основната мярка
                    $measureOptions[$productRec->measureId] = cat_UoM::getTitleById($productRec->measureId, false);
                }

                // Ако има втора мярка
                if ($originRec->allowSecondMeasure == 'yes') {
                    // добавя се и тя
                    $measureOptions[$originRec->secondMeasureId] = cat_UoM::getTitleById($originRec->secondMeasureId, false);
                }

                // Ако някоя от произовдните на основната му мярка е налична в опциите - добавят се и останалите
                if (countR(array_intersect_key($measureOptions, $similarMeasures)) || $originRec->allowSecondMeasure == 'yes') {
                    // както и производните на основната му мярка, които са опаковки
                    $packMeasures = cat_Products::getPacks($productRec->id, null, true);
                    $leftMeasures = array_intersect_key($similarMeasures, $packMeasures);
                    $leftMeasures = array_keys($leftMeasures);
                    foreach ($leftMeasures as $lMeasureId) {
                        if (!array_key_exists($lMeasureId, $measureOptions)) {
                            $measureOptions[$lMeasureId] = cat_UoM::getTitleById($lMeasureId, false);
                        }
                    }
                }
            } else {
                $measureOptions = cat_Products::getPacks($rec->productId, $rec->measureId, true);
            }

            $measuresCount = countR($measureOptions);
            $form->setOptions('measureId', $measureOptions);
            $form->setDefault('measureId', key($measureOptions));
            if ($measuresCount == 1) {
                $form->setField('measureId', 'input=hidden');
            }

            $form->setFieldTypeParams("indTime", array('measureId' => $rec->measureId));
            if ($rec->isFinal == 'yes') {
                $packType = cat_UoM::fetchField($originRec->packagingId, 'type');
                $defaultPlannedQuantity = $originRec->quantity;
                if ($rec->measureId != $originRec->packagingId) {
                    if ($originRec->allowSecondMeasure == 'yes') {
                        if ($packType == 'uom') {
                            if (!array_key_exists($originRec->packagingId, $similarMeasures)) {
                                if ($pQuantity = cat_products_Packagings::getPack($productRec->id, $originRec->packagingId, 'quantity')) {
                                    $defaultPlannedQuantity *= $pQuantity;
                                }
                            }

                            if (array_key_exists($rec->measureId, $similarMeasures)) {
                                $defaultPlannedQuantity = cat_UoM::convertValue($defaultPlannedQuantity, $productRec->measureId, $rec->measureId);
                            } else {
                                if ($pQuantity = cat_products_Packagings::getPack($productRec->id, $rec->measureId, 'quantity')) {
                                    $defaultPlannedQuantity /= $pQuantity;
                                }
                            }
                        } else {
                            if (!array_key_exists($rec->measureId, $similarMeasures)) {
                                if ($pQuantity = cat_products_Packagings::getPack($productRec->id, $originRec->secondMeasureId, 'quantity')) {
                                    $defaultPlannedQuantity /= $pQuantity;
                                }
                            } else {
                                $defaultPlannedQuantity = cat_UoM::convertValue($defaultPlannedQuantity, $productRec->measureId, $rec->measureId);
                            }
                        }
                    } else {
                        $defaultPlannedQuantity = cat_UoM::convertValue($defaultPlannedQuantity, $productRec->measureId, $rec->measureId);
                    }
                } else {
                    $defaultPlannedQuantity /= $originRec->quantityInPack;
                }

                $round = cat_UoM::fetchField($rec->measureId, 'round');
                $form->setDefault('plannedQuantity', round($defaultPlannedQuantity, $round));
            }

            if (countR($fixedAssetOptions)) {
                $cloneArr = $fixedAssetOptions;
                $fixedAssetOptions = array();
                array_walk($cloneArr, function ($a) use (&$fixedAssetOptions) {
                    $fixedAssetOptions[$a] = planning_AssetResources::getTitleById($a, false);
                });
            }

            cat_products_Params::addProductParamsToForm($mvc, $rec->id, $originRec->productId, $rec->productId, $form);

            // Ако дефолтите са от шаблонна операция, то нейните параметри са с приоритет
            if (isset($rec->systemId, $tasks[$rec->systemId])) {
                $taskData = (array)$tasks[$rec->systemId];
                if (countR($taskData['params'])) {
                    foreach ($taskData['params'] as $pId => $pVal) {
                        $form->rec->{"paramcat{$pId}"} = $pVal;
                    }
                }
            }

            if ($productRec->canStore == 'yes') {
                $packs = planning_Tasks::getAllowedLabelPackagingOptions($rec->measureId, $productId4Form, $rec->labelPackagingId);
                $form->setOptions('labelPackagingId', array('' => '') + $packs);
                $indPacks = array($rec->measureId => cat_UoM::getTitleById($rec->measureId, false)) + cat_products_Packagings::getOnlyPacks($productId4Form);

                $form->setOptions('indPackagingId', $indPacks);
                if (isset($productionData) && array_key_exists($productionData['normPackagingId'], $packs)) {
                    $form->setDefault('indPackagingId', $productionData['normPackagingId']);
                }

                if ($rec->isFinal != 'yes') {
                    if (array_key_exists($productionData['labelPackagingId'], $packs)) {
                        $form->setDefault('labelPackagingId', $productionData['labelPackagingId']);
                    }
                }

                $form->setField('storeId', 'input');
                $form->setField('labelPackagingId', 'input');
                $form->setField('indPackagingId', 'input');
            } else {
                $form->setField('showadditionalUom', 'input=none');
                $form->setDefault('indPackagingId', $rec->measureId);
            }

            $jobQuantityVerbal = core_Type::getByName('double(smartRound)')->toVerbal($originRec->quantity / $originRec->quantityInPack);
            $jobMeasureVerbal = cat_UoM::getSmartName($originRec->packagingId, $originRec->quantity);
            $unit = "|за количество от заданието|* <b>{$jobQuantityVerbal} {$jobMeasureVerbal}</b>";
            if ($measuresCount == 1) {
                $measureShort = cat_UoM::getShortName($rec->measureId);
                $unit = "{$measureShort} {$unit}";
            }
            $form->setField('plannedQuantity', "unit={$unit}");

            if (isset($rec->labelPackagingId)) {
                $form->setField('labelQuantityInPack', 'input');
                $form->setField('labelTemplate', 'input');

                if ($rec->isFinal != 'yes' && $rec->labelPackagingId == $productionData['labelPackagingId']) {
                    if (empty($rec->id)) {
                        $stepMeasureId = cat_Products::fetchField($rec->productId, 'measureId');
                        $stepSimilarMeasures = cat_UoM::getSameTypeMeasures($stepMeasureId);
                        if (array_key_exists($productRec->measureId, $stepSimilarMeasures)) {
                            $productionData['labelQuantityInPack'] = cat_UoM::convertValue($productionData['labelQuantityInPack'], $stepMeasureId, $productRec->measureId);
                        }
                        $form->setDefault('labelQuantityInPack', $productionData['labelQuantityInPack']);
                    }
                }

                $quantityInPackDefault = static::getDefaultQuantityInLabelPackagingId($productId4Form, $rec->measureId, $rec->labelPackagingId, $rec->id);
                $form->setField('labelQuantityInPack', "placeholder={$quantityInPackDefault}");

                $templateOptions = static::getAllAvailableLabelTemplates($rec->labelTemplate);
                $form->setOptions('labelTemplate', $templateOptions);
                $form->setDefault('labelTemplate', key($templateOptions));
            } else {
                $form->setField('labelTemplate', 'input=hidden');
            }

            if (empty($rec->id)) {
                $form->setDefault('indPackagingId', $rec->measureId);
            }

            if ($rec->productId == $originRec->productId) {
                $toProduce = ($originRec->quantity - $originRec->quantityProduced);
                if ($toProduce > 0) {
                    $packRec = cat_products_Packagings::getPack($rec->productId, $rec->measureId);
                    $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
                    $round = cat_UoM::fetchField($rec->measureId, 'round');
                    $form->setDefault('plannedQuantity', round($toProduce / $quantityInPack, $round));
                }
            }

            if (isset($rec->indPackagingId)) {
                $form->setFieldTypeParams('indTime', array('measureId' => $rec->indPackagingId));
            }

            if (isset($rec->wasteProductId)) {
                $wasteProductMeasureId = cat_Products::fetchField($rec->wasteProductId, 'measureId');
                $form->setField("wasteStart", "unit=" . cat_UoM::getShortName($wasteProductMeasureId));
            }
        } else {
            $form->setField('employees', 'input=hidden');
        }

        // Добавяне на наличните за избор оборудвания
        $fixedAssetOptions = countR($fixedAssetOptions) ? $fixedAssetOptions : planning_AssetResources::getByFolderId($rec->folderId, $rec->assetId, 'planning_Tasks', true);
        $countAssets = countR($fixedAssetOptions);

        if ($countAssets) {
            $form->setField('assetId', 'input');
            if ($countAssets == 1 && empty($rec->id)) {
                $form->setDefault('assetId', key($fixedAssetOptions));
            } else {
                $fixedAssetOptions = array('' => '') + $fixedAssetOptions;
            }

            $form->setOptions('assetId', $fixedAssetOptions);
        } else {
            $form->setField('assetId', 'input=none');
        }

        // Ако има избрано оборудване се добавя след края на коя операция да започне тази
        $form->input('assetId', 'silent');
        if (isset($rec->assetId)) {
            $assetSimultaneity = planning_AssetResources::fetchField($rec->assetId, 'simultaneity');
            $form->setField('simultaneity', "input,placeholder={$assetSimultaneity}");
            if ($data->action != 'clone') {
                $taskOptions = planning_AssetResources::getAssetTaskOptions($rec->assetId, false, "ASC",true);
                unset($taskOptions[$rec->id]);

                $form->setField('startAfter', 'input');
                if (countR($taskOptions)) {
                    $form->setOptions('startAfter', array('' => '') + $taskOptions);
                    $form->setDefault('startAfter', $mvc->getPrevOrNextTask($rec));
                } else {
                    $form->setReadOnly('startAfter');
                }
            }
        } else {
            $form->setField('simultaneity', 'input=none');
            $form->setField('startAfter', 'input=none');
        }

        if (isset($rec->id)) {
            $form->setReadOnly('productId');
            if ($data->action != 'clone') {
                if (planning_ProductionTaskDetails::fetchField("#taskId = {$rec->id}")) {
                    $form->setReadOnly('labelPackagingId');
                    if ($form->getFieldParam('labelQuantityInPack', 'input') != 'hidden') {
                        $form->setReadOnly('labelQuantityInPack');
                    }
                    $form->setReadOnly('measureId');
                }

                if (planning_ProductionTaskDetails::fetchField("#taskId = {$rec->id} AND #state != 'rejected'")) {
                    $form->setReadOnly('showadditionalUom');
                }
            }
        }
    }


    /**
     * Връща алтернативно заглавие за операцията
     *
     * @param int|stdClass $taskId
     * @param bool $isShort
     * @return string
     */
    public function getAlternativeTitle($taskId, $isShort = false)
    {
        $taskRec = static::fetchRec($taskId);
        $job = doc_Containers::getDocument($taskRec->originId);
        $jobTitle = cat_Products::fetchField($job->fetchField('productId'), 'name');

        if($isShort){
            $oprTitle = "Opr{$taskRec->id}/";
            $jobTitle = str::limitLen($jobTitle, 36);
        } else {
            $productTitle = str::limitLen(cat_Products::fetchField($taskRec->productId, 'name'), 36);
            $oprTitle = "Opr{$taskRec->id}-{$productTitle} / ";
        }

        $jobTitle = "Job{$job->that}-{$jobTitle}";
        $title = "{$oprTitle}{$jobTitle}";

        return $title;
    }


    /**
     * Връща допустимите за етикетиране мерки/опаковки
     *
     * @param int $selectedMeasureId - мярка на артикул
     * @param int $productId - ид на артикул
     * @param int|null $exId - ид на вече избрана мярка
     * @return array $packs          - допустимите за избор опаковки + мярка "брой"(ако се поддържа)
     */
    public static function getAllowedLabelPackagingOptions($selectedMeasureId, $productId = null, $exId = null)
    {
        $packs = array();
        if ($selectedMeasureId == cat_UoM::fetchBySysId('pcs')->id) {
            $packs[$selectedMeasureId] = cat_UoM::getTitleById($selectedMeasureId, false);
        }

        if (isset($productId)) {
            $packs += cat_products_Packagings::getOnlyPacks($productId);
        } else {
            $packs += cat_UoM::getPackagingOptions();
        }
        if (isset($exId) && !array_key_exists($exId, $packs)) {
            $packs[$exId] = cat_UoM::getTitleById($exId, false);
        }

        if (countR($packs)) {
            $packs = array('' => '') + $packs;
        }

        return $packs;
    }


    /**
     * Изчисляване следващата или предишната операция от тази
     *
     * @param stdClass $rec
     * @param boolean $withProgress
     * @param boolean $next
     * @return null|int
     */
    private function getPrevOrNextTask($rec, $withProgress = false, $next = true)
    {
        if (empty($rec->assetId)) return null;

        $query = planning_Tasks::getQuery();
        $query->where("#assetId = {$rec->assetId} AND #orderByAssetId IS NOT NULL");
        $dir = ($next) ? "DESC" : "ASC";
        $query->orderBy('orderByAssetId', $dir);
        $query->show('id');
        $query->limit(1);

        if ($withProgress) {
            $query->where("#progress != 0");
        }

        if (isset($rec->id) && isset($rec->orderByAssetId)) {
            $sign = ($next) ? "<" : ">";
            $query->where("#orderByAssetId {$sign} {$rec->orderByAssetId}");
        }

        return $query->fetch()->id;
    }


    /**
     * Показване на остатъчната наличност на невложеното но произведено по операцията
     *
     * @param stdClass $rec
     * @return double|null
     */
    private function getLeftOverQuantityInStock($rec)
    {
        $rec = $this->fetchRec($rec);
        $notConvertedQuantity = null;
        $productRec = cat_Products::fetch($rec->productId, 'canStore');
        if($productRec->canStore == 'yes'){
            if(core_Packs::isInstalled('batch')){
                if($BatchDef = batch_Defs::getBatchDef($rec->productId)){
                    $autoTaskBatchValue = $BatchDef->getAutoValue('planning_Tasks', $rec->id, null, null);

                    if(!empty($autoTaskBatchValue)){
                        $batches = batch_Items::getBatchQuantitiesInStore($rec->productId, null, null, null, array(), false, $autoTaskBatchValue);
                        if(array_key_exists($autoTaskBatchValue, $batches)){
                            $notConvertedQuantity = $batches[$autoTaskBatchValue];
                        }
                    }
                }
            } else {
                $notConvertedQuantity = store_Products::getQuantities($rec->productId)->free;
            }
        }

        return $notConvertedQuantity;
    }


    /**
     * Подготвя задачите към заданията
     */
    public function prepareTasks($data)
    {
        if ($data->masterMvc instanceof planning_AssetResources) {
            if(empty($data->masterData->rec->simultaneity)) {
                $data->hide = true;
                return;
            }
            $data->TabCaption = 'Операции';
        }

        $data->pager = cls::get('core_Pager', array('itemsPerPage' => 10));
        $data->pager->setPageVar($data->masterMvc->className, $data->masterId);
        $data->recs = $data->rows = array();

        // Всички създадени задачи към заданието
        $query = $this->getQuery();
        $query->XPR('orderByDate', 'datetime', "COALESCE(#expectedTimeStart, 9999999999999)");
        $query->where("#state != 'rejected'");

        if ($data->masterMvc instanceof planning_AssetResources) {
            $query->orderBy('orderByDate', 'ASC');
            $query->where("#assetId = {$data->masterId}");
            $query->in("state", array('pending', 'active', 'wakeup', 'stopped'));
        } else {
            $query->orderBy('saoOrder', 'ASC');
            $query->where("#originId = {$data->masterData->rec->containerId}");
        }
        $data->pager->setLimit($query);

        $fields = $this->selectFields();
        $fields['-list'] = $fields['-detail'] = true;

        // Подготвяне на данните
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $row = planning_Tasks::recToVerbal($rec, $fields);
            if (!empty($rec->assetId)) {
                $row->assetId = planning_AssetResources::getShortName($rec->assetId, !Mode::isReadOnly());
            }

            $row->title = ht::createElement("span", array('id' => planning_Tasks::getHandle($rec->id)), $row->title);

            // Показване и колонка с % отпадък
            $taskWastePercent = null;
            planning_ProductionTaskProducts::getTotalWasteArr($rec->threadId, $taskWastePercent);
            if(isset($taskWastePercent)){
                $row->taskWastePercent = core_Type::getByName('percent(smartRound)')->toVerbal($taskWastePercent);
            }

            $row->plannedQuantity .= " " . $row->measureId;
            $row->totalQuantity .= " " . $row->measureId;
            $row->producedQuantity .= " " . $row->measureId;
            if(!empty($rec->notConvertedQuantity)){
                $row->notConvertedQuantity .= " " . $row->measureId;
            } else {
                unset($row->notConvertedQuantity);
            }

            // Показване на протоколите за производство
            $notes = array();
            $nQuery = planning_DirectProductionNote::getQuery();
            $nQuery->where("#originId = {$rec->containerId} AND #state != 'rejected'");
            $nQuery->show('id');
            while ($nRec = $nQuery->fetch()) {
                $notes[] = planning_DirectProductionNote::getLink($nRec->id, 0);
            }
            $countNotes = countR($notes);
            if ($countNotes) {
                $row->info .= "<div style='padding-bottom:7px;' class='taskInJobListRow small pnotes{$rec->id}'>" . implode(' | ', $notes) . "</div>";
                if(!Mode::isReadOnly()){
                    $row->producedQuantity = "{$row->producedQuantity}&nbsp;<a id= 'btn{$rec->id}' href=\"javascript:toggleDisplayByClass('btn{$rec->id}','pnotes{$rec->id}')\"  style=\"background-image:url(" . sbf('img/16/toggle1.png', "'") . ');" class=" plus-icon more-btn", title="' . tr('Допълнителна информация за транспорта') . "\"</a>";
                }
            }

            // Линк към разходите, ако ПО е разходен обект
            if (acc_Items::isItemInList($this, $rec->id, 'costObjects')) {
                $costsCount = doc_ExpensesSummary::fetchField("#containerId = {$rec->containerId}", 'count');

                $costsCount = !empty($costsCount) ? $costsCount : 0;
                $linkArr = array();
                if (haveRole('ceo, acc, purchase, sales') && $this->haveRightFor('single', $rec->id)) {
                    $linkArr = array($this, 'single', $rec->id, 'Sid' => $rec->containerId);
                }
                $costsCount = core_Type::getByName('int')->toVerbal($costsCount);
                $row->costsCount = ht::createLinkRef($costsCount, $linkArr, false, 'title=Показване на разходите към документа');
            }

            // Показване на ПВ към операцията, групирани по тяхното състояние
            $notesByStates = array();
            $noteQuery = planning_ConsumptionNotes::getQuery();
            $noteQuery->where("#threadId = {$rec->threadId} AND #state != 'rejected'");
            $noteQuery->XPR('count', 'int', 'COUNT(#id)');
            $noteQuery->groupBy('state');
            $noteQuery->show('state,count');
            while($noteRec = $noteQuery->fetch()){
                $noteCountVerbal = core_Type::getByName('int')->toVerbal($noteRec->count);
                $notesByStates[] = " <div class='state-{$noteRec->state} consumptionNoteBubble'>{$noteCountVerbal}</div>";
            }
            if(countR($notesByStates)){
                $row->progress .= " <small><i>" . tr('ПВ') . ":" . implode($notesByStates) . "</i></small>";
            }

            $data->rows[$rec->id] = $row;
        }

        Mode::push('forListRows', true);
        $this->invoke('AfterPrepareListRows', array($data, $data));

        // Ако потребителя може да добавя операция от съответния тип, ще показваме бутон за добавяне
        if ($data->masterMvc instanceof planning_Jobs) {
            if ($this->haveRightFor('add', (object)array('originId' => $data->masterData->rec->containerId))) {
                if (!Mode::isReadOnly()) {
                    $data->addUrlArray = array('planning_Jobs', 'selectTaskAction', 'originId' => $data->masterData->rec->containerId, 'ret_url' => true);
                }
            }
        }
    }


    /**
     * Рендира задачите на заданията
     */
    public function renderTasks($data)
    {
        $tpl = new ET('');

        if ($data->masterMvc instanceof planning_AssetResources) {
            if($data->hide) return null;

            $data->TabCaption = 'Операции';
            $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        }

        // Рендиране на таблицата с намерените задачи
        $listTableMvc = clone $this;
        $listTableMvc->FNC('costsCount', 'int');
        $listTableMvc->FNC('notConvertedQuantity', 'int');
        $listTableMvc->FNC('taskWastePercent', 'int', 'tdClass=small quiet');

        $table = cls::get('core_TableView', array('mvc' => $listTableMvc));
        $fields = arr::make('saoOrder=№,expectedTimeStart=Начало,title=Операция,progress=Прогрес,plannedQuantity=План.,totalQuantity=Произв.,producedQuantity=Заскл.,notConvertedQuantity=Невл.,costsCount=Разходи,taskWastePercent=Отп., assetId=Оборудв.,info=@info');
        $fields['taskWastePercent'] = "|*<small class='quiet'>|Отп.|*</small>";
        if ($data->masterMvc instanceof planning_AssetResources) {
            unset($fields['assetId']);
        }

        $data->listFields = core_TableView::filterEmptyColumns($data->rows, $fields, 'assetId,costsCount,taskWastePercent,notConvertedQuantity');
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $contentTpl = $table->get($data->rows, $data->listFields);
        if (isset($data->pager)) {
            $contentTpl->append($data->pager->getHtml());
        }

        // Имали бутони за добавяне
        if (isset($data->addUrlArray)) {
            $btn = ht::createLink('', $data->addUrlArray, false, "title=Създаване на производствена операция към задание,ef_icon=img/16/add.png");
            $contentTpl->append($btn, 'btnTasks');
        }

        if ($data->masterMvc instanceof planning_AssetResources) {
            $tpl->append("Производствени операции (заявки, активни, събудени, спрени)", 'title');
            $tpl->append($contentTpl, 'content');
        } else {
            $tpl = $contentTpl;
        }

        // Връщаме шаблона
        return $tpl;
    }


    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->setFieldTypeParams('folder', array('coverClasses' => 'planning_Centers'));
        $data->listFilter->setField('folder', 'silent,autoFilter');
        $orderByField = 'orderByDate';

        // Добавят се за избор само използваните в ПО оборудвания
        $assetInTasks = planning_AssetResources::getUsedAssetsInTasks($data->listFilter->rec->folder);
        if (countR($assetInTasks)) {
            $data->listFilter->setField('assetId', 'caption=Оборудване,silent,autoFilter');
            $data->listFilter->setOptions('assetId', array('' => '') + $assetInTasks);
            $data->listFilter->showFields .= ',assetId';
            $data->listFilter->input('assetId', 'silent');
            $data->listFilter->input('assetId');
        }

        $mvc->listItemsPerPage = 20;
        if ($filter = $data->listFilter->rec) {
            if (isset($filter->assetId)) {
                $mvc->listItemsPerPage = 2000;
                $data->query->where("#assetId = {$filter->assetId}");
                $orderByField = 'orderByAssetId';

                $cUrl = getCurrentUrl();
                if(!Mode::get('isReorder')){
                    $cUrl['isFinalSelect'] = 'all';
                    $cUrl['state'] = 'activeAndPending';
                    $cUrl['selectPeriod'] = 'gr0';
                    unset($cUrl['folder']);
                    unset($cUrl['search']);
                    $cUrl['reorder'] = true;
                    $cUrl['ret_url'] = true;
                    $data->listFilter->toolbar->addBtn("Подреждане", $cUrl, 'title=Преподреждане на операциите,ef_icon=img/16/arrow_switch2.png');
                }
            }
        }

        $orderByDir = 'ASC';
        if (!Request::get('Rejected', 'int')) {
            $data->listFilter->FNC('isFinalSelect', 'enum(all=Всички,yes=Финален етап,no=Междинен етап)', 'caption=Вид етап,input');
            $data->listFilter->setOptions('state', arr::make('activeAndPending=Заявки+Активни+Събудени+Спрени,draft=Чернова,active=Активен,closed=Приключен, stopped=Спрян, wakeup=Събуден,waiting=Чакащо,pending=Заявка,all=Всички', true));
            $data->listFilter->showFields .= ',state,isFinalSelect';
            $data->listFilter->input('state,isFinalSelect');
            $data->listFilter->setDefault('state', 'activeAndPending');
            $data->listFilter->setDefault('isFinalSelect', 'all');
            $orderByDateCoalesce = 'COALESCE(#expectedTimeStart, 9999999999999)';

            if ($filter = $data->listFilter->rec) {
                if($filter->isFinalSelect != 'all'){
                    $data->query->where("#isFinal = '{$filter->isFinalSelect}'");
                }

                if ($filter->state == 'activeAndPending') {
                    $data->query->where("#state IN ('active', 'pending', 'wakeup', 'stopped', 'rejected')");
                } elseif ($filter->state != 'all') {
                    $data->query->where("#state = '{$filter->state}' OR #state = 'rejected'");
                    if ($filter->state == 'closed') {
                        $orderByField = 'orderByDate';
                        $orderByDir = 'DESC';
                        $orderByDateCoalesce = 'COALESCE(#timeClosed, 0)';

                        //$data->listFields['']
                    }
                }

                if ($filter->filterDateField == 'dueDate') {
                    if (!isset($filter->assetId)) {
                        $orderByDir = 'ASC';
                        $orderByDateCoalesce = '#dueDate';
                    }
                    arr::placeInAssocArray($data->listFields, array('dueDate' => 'Падеж'), null, 'expectedTimeStart');
                }
            }
        } else {
            $orderByDateCoalesce = 'COALESCE(#expectedTimeStart, 0)';
            $orderByDir = 'DESC';
        }

        $data->query->XPR('orderByDate', 'datetime', $orderByDateCoalesce);
        $data->query->orderBy($orderByField, $orderByDir);

        if(Mode::get('isReorder')){
            $data->listFilter->hide = true;
            $mvc->cacheAssetDataOnShutdown[$filter->assetId] = $filter->assetId;
        }
    }


    /**
     * След рендиране на List Summary-то
     */
    public static function on_AfterRenderListSummary($mvc, &$tpl, $data)
    {
        if(Mode::get('isReorder')) {
            $tpl = new core_ET("");
        }
    }


    /**
     * Преди изпълнението на контролерен екшън
     *
     * @param core_Manager $mvc
     * @param core_ET      $res
     * @param string       $action
     */
    protected static function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if (in_array(strtolower($action), array('list', 'default'))) {
            Mode::set('isReorder', Request::get('reorder', 'int'));

            if(Mode::is('isReorder', true)){
                Mode::set('wrapper', 'page_Empty');
                unset($mvc->_plugins['planning_Wrapper']);
                $mvc->rememberListFilterFolderId = null;
            }
        }
    }


    /**
     * Връща масив от задачи към дадено задание
     *
     * @param mixed $jobs - ид или масив от задания
     * @param mixed $states - В кои състояния
     * @param boolean $verbal - вербални или записи
     * @param boolean $skipTasksWithClosedParams - да се пропуснат ли операциите с деактивирани параметри
     * @param null|bool $isFinal                 - дали да са само финалните или не
     * @param bool $skipWithClosedSteps          -
     * @return array $res      - масив с намерените задачи
     */
    public static function getTasksByJob($jobs, $states, $verbal = true, $skipTasksWithClosedParams = false, $isFinal = null, $skipWithClosedSteps = false)
    {
        $res = array();

        // Всички ПО към посочените задания
        $jobs = arr::make($jobs);
        $jQuery = planning_Jobs::getQuery();
        $jQuery->in('id', $jobs);
        $jQuery->show('containerId');
        $containers = arr::extractValuesFromArray($jQuery->fetchAll(), 'containerId');
        $query = static::getQuery();
        $query->in("originId", $containers);

        $states = arr::make($states, true);
        $query->in("state", $states);
        $query->EXT('productState', 'cat_Products', 'externalName=state,externalKey=productId');
        $query->orderBy("saoOrder", 'ASC');
        if(isset($isFinal)){
            $isFinalVal = $isFinal ? 'yes' : 'no';
            $query->where("#isFinal = '{$isFinalVal}'");
        }

        $taskClassId = planning_Tasks::getClassId();
        while ($rec = $query->fetch()) {
            if($skipWithClosedSteps){
                if($rec->productState != 'active') continue;
            }
            if($skipTasksWithClosedParams){

                // Ако е посочено че се търсят само ПО с незакрити параметри оставят се само те
                $pQuery = cat_products_Params::getQuery();
                $pQuery->EXT('state', 'cat_Params', 'externalName=state,externalKey=paramId');
                $pQuery->where("#classId = {$taskClassId} AND #productId = {$rec->id} AND #state = 'closed'");
                if($pQuery->count()) continue;
            }

            $res[$rec->id] = ($verbal) ? self::getLink($rec->id, false) : $rec;
        }

        return $res;
    }


    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        // Ако ПО е към задание по продажба - добавя се хендлъра на продажбата в ключовите думи
        if($jobRec = planning_Jobs::fetch("#containerId = '{$rec->originId}'", 'saleId,productId')){
            $res .= ' ' . plg_Search::normalizeText(sales_Sales::getHandle($jobRec->saleId));

            // Добавяне на драйвера на артикула в ключовите думи
            $productDriverClass = cat_Products::getVerbal($jobRec->productId, 'innerClass');
            $res .= ' ' . plg_Search::normalizeText($productDriverClass);
            $res .= ' ' . plg_Search::normalizeText(planning_Jobs::getTitleById($jobRec->id));
        }

        // Добавяне на всички ключови думи от прогреса
        if(isset($rec->id)){
            $dQuery = planning_ProductionTaskDetails::getQuery();
            $dQuery->XPR('concat', 'varchar', 'GROUP_CONCAT(#searchKeywords)');
            $dQuery->where("#taskId = {$rec->id}");
            $dQuery->limit(1);
            if ($keywords = $dQuery->fetch()->concat) {
                $keywords = str_replace(' , ', ' ', $keywords);
                $res = ' ' . $res . ' ' . $keywords;
            }
        }
    }


    /**
     * Връща количеството произведено по задачи по дадено задание
     *
     * @param int|stdClass $jobId
     * @return double $quantity
     */
    public static function getProducedQuantityForJob($jobId)
    {
        $jobRec = planning_Jobs::fetchRec($jobId);
        $kgDerivitives = cat_UoM::getSameTypeMeasures(cat_UoM::fetchBySysId('kg')->id);

        $sum = 0;
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#originId = {$jobRec->containerId} AND (#productId = {$jobRec->productId} OR #isFinal = 'yes')");
        $tQuery->where("#state != 'rejected' AND #state != 'pending'");
        $tQuery->show('totalQuantity,scrappedQuantity,measureId,quantityInPack,showadditionalUom,totalNetWeight,totalWeight');

        while ($tRec = $tQuery->fetch()) {
            // Ако заданието е в мярка производна на кг и ПО е в мярка различна от кг, то ще се взима реалното нето тегло
            if (array_key_exists($jobRec->packagingId, $kgDerivitives) && !array_key_exists($tRec->measureId, $kgDerivitives)) {
                if ($tRec->showadditionalUom == 'yes') {
                    $sum += $tRec->totalNetWeight;
                    continue;
                }
            }

            $sumRec = ($tRec->totalQuantity - $tRec->scrappedQuantity) * $tRec->quantityInPack;
            if ($pQuantity = cat_products_Packagings::getPack($jobRec->productId, $jobRec->packagingId, 'quantity')) {
                $tRec->quantityInPack = $pQuantity;
                $sumRec /= $tRec->quantityInPack;
            }

            $sum += $sumRec;
        }

        $quantity = (!empty($sum)) ? round($sum, 5) : 0;

        return $quantity;
    }


    /**
     * Връща името на операцията готово за партида
     *
     * @param mixed $taskId - ид на операцията
     *
     * @return string $batchName - името на партидата, генерирана от операцията
     */
    public static function getBatchName($taskId)
    {
        $rec = self::fetchRec($taskId);
        $productName = cat_Products::getVerbal($rec->productId, 'name');
        $code = cat_Products::getVerbal($rec->productId, 'code');
        $batchName = "{$productName}/{$code}/Opr{$rec->id}";
        $batchName = str::removeWhiteSpace($batchName);

        return $batchName;
    }


    /**
     * В кои корици може да се вкарва документа
     *
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getCoversAndInterfacesForNewDoc()
    {
        return array('folderClass' => 'planning_Centers');
    }


    /**
     * Търси по подадения баркод
     *
     * @param string $str
     *
     * @return array
     * ->title - заглавие на резултата
     * ->url - линк за хипервръзка
     * ->comment - html допълнителна информация
     * ->priority - приоритет
     */
    public function searchByCode($str)
    {
        $resArr = array();
        $str = trim($str);

        $taskDetailQuery = planning_ProductionTaskDetails::getQuery();
        $str = str_pad($str, 13, '0', STR_PAD_LEFT);
        $taskDetailQuery->where(array("#serial = '[#1#]'", $str));

        $isPartner = core_Packs::isInstalled('colab') && core_Users::isContractor();
        $taskDetailQuery->EXT('threadId', 'planning_Tasks', "externalName=threadId,externalKey=taskId");
        while ($dRec = $taskDetailQuery->fetch()) {

            if($isPartner){
                $threadRec = doc_Threads::fetch($dRec->threadId);
                if(!colab_Threads::haveRightFor('single', $threadRec)) continue;
            }

            $res = new stdClass();
            $tRec = $this->fetch($dRec->taskId);
            $res->title = tr('ПО') . ': ' . $tRec->title;

            if ($this->haveRightFor('single', $tRec)) {
                if (doc_Threads::haveRightFor('single', $tRec->threadId)) {
                    $hnd = $this->getHandle($tRec->id);
                    $res->url = array('doc_Containers', 'list', 'threadId' => $tRec->threadId, 'docId' => $hnd, 'Q' => $str, '#' => $dRec->serial);
                } else {
                    $res->url = array('planning_Tasks', 'single', $dRec->taskId, 'Q' => $str);
                }

                $dRow = planning_ProductionTaskDetails::recToVerbal($dRec);
                $res->comment = tr('Артикул') . ': ' . $dRow->productId . ' ' . tr('Количество') . ': ' . $dRow->quantity . $dRow->shortUoM;
                if ($tRec->progress) {
                    $progress = $this->getVerbal($tRec, 'progress');
                    $res->title .= ' (' . $progress . ')';
                }
            }

            $res->priority = 1;
            if ($dRec->state == 'active') {
                $res->priority = 2;
            } else if ($dRec->state == 'rejected') {
                $res->priority = 0;
            }

            $resArr[] = $res;
        }

        return $resArr;
    }


    /**
     * Поставя бутони за генериране на други банкови документи възоснова
     * на този, само ако документа е "чернова"
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;

        // Бутон за добавяне на документ за производство
        if (planning_DirectProductionNote::haveRightFor('add', (object)array('originId' => $rec->containerId))) {
            $pUrl = array('planning_DirectProductionNote', 'add', 'originId' => $rec->containerId, 'ret_url' => true);
            $data->toolbar->addBtn('Произвеждане', $pUrl, 'ef_icon = img/16/page_paste.png,title=Създаване на протокол за производство от операцията');
        }

        // Бутон за добавяне на документ за производство
        if (planning_ConsumptionNotes::haveRightFor('add', (object)array('originId' => $rec->containerId))) {
            $pUrl = array('planning_ConsumptionNotes', 'add', 'originId' => $rec->containerId, 'ret_url' => true);
            $data->toolbar->addBtn('Влагане', $pUrl, 'ef_icon = img/16/produce_in.png,title=Създаване на протокол за влагане от операцията');
        }

        // Бутон за добавяне на документ за влагане
        if (planning_ReturnNotes::haveRightFor('add', (object)array('originId' => $rec->containerId))) {
            $pUrl = array('planning_ReturnNotes', 'add', 'originId' => $rec->containerId, 'ret_url' => true);
            $data->toolbar->addBtn('Връщане', $pUrl, 'ef_icon = img/16/produce_out.png,title=Създаване на протокол за връщане към заданието,row=2');
        }

        // Бутон за добавяне на документ за влагане
        if ($mvc->haveRightFor('recalcindtime', $rec)) {
            $data->toolbar->addBtn('Преизч. заработки', array($mvc, 'recalcindtimes', $rec->id, 'ret_url' => true), 'ef_icon = img/16/arrow_refresh.png,title=Преизчисляване на заработките към операцията,row=2,warning=Наистина ли желаете да преизчислите заработките в прогреса|*?');
        }

        if ($data->toolbar->haveButton('btnActivate')) {
            $data->toolbar->renameBtn('btnActivate', 'Стартиране');
        }
    }


    /**
     * След промяна на състоянието
     */
    protected static function on_AfterChangeState($mvc, &$rec, $action)
    {
        // При затваряне се попълва очаквания край, ако не може да се изчисли
        if ($action == 'closed') {
            if(empty($rec->timeEnd) && !isset($rec->timeStart, $rec->timeDuration)){
                $rec->timeEnd = dt::now();
                $mvc->save_($rec, 'timeEnd');
            }

            if(doc_Containers::fetchField("#threadId = {$rec->threadId} AND #state IN ('pending', 'draft')")){
                core_Statuses::newStatus('В операцията има документ/и на "Заявка/Чернова"!', 'warning');
            }
        }

        // При промяна на състоянието да се преизчислят последните/първите времена
        if(in_array($rec->state, array('active', 'stopped', 'wakeup', 'closed'))){
            static::recalcTaskLastProgress($rec->id);
        }
    }


    /**
     * Рекалкулиране на прогресите на операцията
     *
     * @param int|null $taskId            - ид на операция, null ако е за всички
     * @param array|null $states          - операциите в кои състояние
     * @param null|int $closedInLastDays  - приключвните в последните колко
     * @return void
     */
    public static function recalcTaskLastProgress($taskId = null, $states = null, $closedInLastDays = null)
    {
        $Tasks = cls::get('planning_Tasks');
        $tQuery = planning_ProductionTaskDetails::getQuery();
        $tQuery->EXT('taskState', 'planning_Tasks', "externalName=state,externalKey=taskId");
        $tQuery->EXT('timeClosed', 'planning_Tasks', "externalName=timeClosed,externalKey=taskId");
        $tQuery->XPR('lastProgress', 'datetime', 'MAX(COALESCE(#date, #createdOn))');
        $tQuery->XPR('firstProgress', 'datetime', 'MIN(COALESCE(#date, #createdOn))');
        $tQuery->XPR('lastProgressProduction', 'datetime', "MAX(CASE #type WHEN 'production' THEN COALESCE(#date, #createdOn) ELSE NULL END)");
        $tQuery->where("#state != 'rejected'");
        $tQuery->show('taskId,lastProgress,firstProgress,lastProgressProduction');
        $tQuery->groupBy('taskId');
        if(isset($taskId)){
            $tQuery->where("#taskId = $taskId");
        }
        if(isset($states)){
            $states = arr::make($states, true);
            $tQuery->in("taskState", $states);
            if(isset($closedInLastDays)){
                $beforeTime = dt::addDays(-1 * $closedInLastDays);
                $tQuery->orWhere("#taskState = 'closed' AND #timeClosed >= '{$beforeTime}'");
            }
        } else {
            if(isset($closedInLastDays)) {
                $beforeTime = dt::addDays(-1 * $closedInLastDays);
                $tQuery->where("(#taskState = 'closed' AND #timeClosed >= '{$beforeTime}') OR #timeClosed IS NULL");
            }
        }

        $taskArr = array();
        while($tRec = $tQuery->fetch()){
            $taskArr[$tRec->taskId] = (object)array('id' => $tRec->taskId, 'lastProgress' => $tRec->lastProgress, 'firstProgress' => $tRec->firstProgress, 'lastProgressProduction' => $tRec->lastProgressProduction);
        }

        if(countR($taskArr)){
            $Tasks->saveArray($taskArr, 'id,lastProgress,firstProgress,lastProgressProduction');
        }
    }


    /**
     * Връща масив от използваните нестандартни артикули в документа
     *
     * @param int $id - ид на документа
     *
     * @return array $res - масив с използваните документи
     *               ['class'] - инстанция на документа
     *               ['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
        $rec = $this->fetchRec($id);

        $res = array();
        $cid = cat_Products::fetchField($rec->productId, 'containerId');
        $res[$cid] = $cid;

        $dQuery = planning_ProductionTaskProducts::getQuery();
        $dQuery->where("#taskId = '{$rec->id}'");
        $dQuery->groupBy('productId');
        $dQuery->show('productId');
        while ($dRec = $dQuery->fetch()) {
            $cid = cat_Products::fetchField($dRec->productId, 'containerId');
            $res[$cid] = $cid;
        }

        return $res;
    }


    /**
     * @param int $objectId
     * @see crm_ContragentAccRegIntf::getItemRec
     *
     */
    public static function getItemRec($objectId)
    {
        $self = cls::get(get_called_class());
        $result = null;

        if ($rec = $self->fetch($objectId)) {
            $title = $self->getVerbal($rec, 'productId');
            $origin = doc_Containers::getDocument($rec->originId);
            if ($origin->isInstanceOf('planning_Jobs')) {
                $title = $origin->getVerbal('productId') . " - {$title}";
            }

            $result = (object)array(
                'num' => '#' . $self->getHandle($rec->id),
                'title' => $title,
                'features' => array('Папка' => doc_Folders::getTitleById($rec->folderId))
            );
        }

        return $result;
    }


    /**
     * Екшън за създаване на задачи към задание
     *
     * @return void
     * @throws core_exception_Expect
     */
    public function act_CreateJobTasks()
    {
        planning_Tasks::requireRightFor('createjobtasks');
        expect($type = Request::get('type', 'enum(all,clone,cloneAll)'));
        expect($jobId = Request::get('jobId', 'int'));
        $cloneCn = Request::get('cloneNotes', 'int');
        expect($jobRec = planning_Jobs::fetch($jobId));

        // Ако ще се клонира съществуваща операция или ще се клонират всички от предходното
        if ($type == 'clone' || $type == 'cloneAll') {
            $oldJobRec = isset($jobRec->oldJobId) ? planning_Jobs::fetch($jobRec->oldJobId) : $jobRec;
            $tasksToClone = array();
            $count = $clonedConsumptionNotes = 0;
            if($type == 'clone'){
                expect($cloneId = Request::get('cloneId', 'int'));
                planning_Tasks::requireRightFor('createjobtasks', (object)array('jobId' => $jobRec->id, 'cloneId' => $cloneId, 'type' => 'clone'));
                expect($taskRec = $this->fetch($cloneId));
                $tasksToClone[$taskRec->id] = $taskRec;
            } else {
                $selected = Request::get('selected', 'varchar');
                $selectedArr = empty($selected) ? array() : array_combine(explode('|', $selected), explode('|', $selected));
                $jobsToCloneFrom = Request::get('jobsToCloneTasksFrom', 'varchar');
                $jobsToCloneFrom = !empty($jobsToCloneFrom) ? keylist::toArray($jobsToCloneFrom) : $jobRec->oldJobId;

                if(!countR($selectedArr)) followRetUrl(null, '|Не са избрани шаблонни операции за клониране', 'warning');

                // От предходните ще се клонират САМО избраните
                $oldTasks = planning_Tasks::getTasksByJob($jobsToCloneFrom, array('draft', 'waiting', 'active', 'wakeup', 'stopped', 'closed', 'pending'), false, true);
                $tasksToClone = array_intersect_key($oldTasks, $selectedArr);
            }

            foreach ($tasksToClone as $taskRec){
                $newTask = clone $taskRec;
                plg_Clone::unsetFieldsNotToClone($this, $newTask, $taskRec);

                // Преконвертиране на планираното к-во към новото от заданието, да се запази същото отношение
                $q = $oldJobRec->quantity / $jobRec->quantity;
                $round = cat_UoM::fetchField($newTask->measureId, 'round');
                $newTask->plannedQuantity = round($taskRec->plannedQuantity / $q, $round);

                $newTask->_isClone = true;
                $newTask->originId = $jobRec->containerId;
                $newTask->state = 'draft';
                $newTask->clonedFromId = $newTask->id;
                unset($newTask->id);
                unset($newTask->threadId);
                unset($newTask->containerId);
                unset($newTask->createdOn);
                unset($newTask->createdBy);
                unset($newTask->activatedOn);
                unset($newTask->activatedBy);
                unset($newTask->systemId);

                if ($this->save($newTask)) {
                    $this->invoke('AfterSaveCloneRec', array($taskRec, &$newTask));
                    $this->logWrite('Клониране от предходна операция', $newTask->id);

                    $pQuery = cat_products_Params::getQuery();
                    $pQuery->where("#classId = {$this->getClassId()} AND #productId = {$taskRec->id}");
                    while($pRec = $pQuery->fetch()){
                        $newParamRec = clone $pRec;
                        unset($newParamRec->id);
                        $newParamRec->productId = $newTask->id;
                        $newParamRec->paramValue = cat_Params::getReplacementValueOnClone($newParamRec->paramId, 'planning_Tasks', $taskRec->id,$newParamRec->paramValue);
                        cat_products_Params::save($newParamRec);
                    }
                }
                $count++;

                // Клониране и на протоколите за влагане
                if($cloneCn){
                    $Consumptions = cls::get('planning_ConsumptionNotes');
                    $cQuery = $Consumptions->getQuery();
                    $cQuery->where("#state != 'rejected' AND #threadId = {$taskRec->threadId}");
                    while($consRec = $cQuery->fetch()){
                        $newConsRec = clone $consRec;
                        plg_Clone::unsetFieldsNotToClone($Consumptions, $newConsRec, $consRec);
                        unset($newConsRec->id, $newConsRec->threadId, $newConsRec->containerId, $newConsRec->createdOn, $newConsRec->createdBy, $newConsRec->activatedBy, $newConsRec->activatedOn);

                        $newConsRec->_isClone = true;
                        $newConsRec->originId = $newTask->containerId;
                        $newConsRec->state = 'draft';
                        $newConsRec->threadId = $newTask->threadId;
                        $newConsRec->folderId = $newTask->folderId;
                        $newConsRec->clonedFromId = $newConsRec->id;

                        if ($Consumptions->save($newConsRec)) {
                            $clonedConsumptionNotes++;
                            $Consumptions->invoke('AfterSaveCloneRec', array($consRec, &$newConsRec));
                        }
                    }
                }
            }

            $msg = "Успешно клонирани операции|*: {$count}";
            if(!empty($clonedConsumptionNotes)){
                $msg.= ", |Успешно клонирани протоколи за влагане|*: {$clonedConsumptionNotes}";
            }
            followRetUrl(null, $msg);
        } elseif ($type == 'all') {
            $selected = Request::get('selected', 'varchar');
            $selectedArr = !strlen($selected) ? array() : explode('|', $selected);
            if(!countR($selectedArr)) followRetUrl(null, 'Не са избрани шаблонни операции за клониране', 'warning');

            // Ако ще се клонират всички шаблонни операции
            planning_Tasks::requireRightFor('createjobtasks', (object)array('jobId' => $jobRec->id, 'type' => 'all'));
            $msgType = 'notice';
            $msg = 'Успешно създаване на избраните дефолтни операции|*!';
            $defaultTasks = cat_Products::getDefaultProductionTasks($jobRec, $jobRec->quantity);

            $num = 1;
            foreach ($defaultTasks as $sysId => $defaultTask) {
                if(!in_array($sysId, $selectedArr)) continue;
                try {
                    unset($defaultTask->products);
                    $newTask = clone $defaultTask;
                    $newTask->originId = $jobRec->containerId;
                    $newTask->systemId = $sysId;

                    if(empty($defaultTask->plannedQuantity)){
                        $newTask->plannedQuantity = $jobRec->quantity;
                        $newTask->quantityInPack = 1;
                        $newTask->measureId = cat_Products::fetchField($defaultTask->productId, 'measureId');
                    }

                    // Ако има едно оборудване попълва се то по-дефолт
                    $assets = keylist::toArray($defaultTask->fixedAssets);
                    if(countR($assets)){
                        $newTask->assetId = key($assets);
                    }

                    // Клонират се в папката на посочения в тях център, ако няма в центъра от заданието, ако и там няма в Неопределения
                    $folderId = isset($defaultTask->centerId) ? planning_Centers::fetchField($defaultTask->centerId, 'folderId') : ((!empty($jobRec->department)) ? planning_Centers::fetchField($jobRec->department, 'folderId') : null);
                    if (!planning_Tasks::canAddToFolder($folderId)) {
                        $folderId = planning_Centers::getUndefinedFolderId();
                    }

                    $Cover = doc_Folders::getCover($folderId);
                    $autoCreateTaskState = $Cover->fetchField('autoCreateTaskState');
                    $newTask->state = ($autoCreateTaskState == 'auto') ? planning_Setup::get('AUTO_CREATE_TASK_STATE'): $autoCreateTaskState;

                    $newTask->folderId = $folderId;
                    $newTask->saoOrder = $num;
                    $ProductionData = cat_Products::getDriver($newTask->productId)->getProductionData($newTask->productId);
                    $newTask->isFinal = $ProductionData['isFinal'];

                    $this->save($newTask);

                    // Ако има параметри от рецептата се прехвърлят 1 към 1
                    $saveParams = array();

                    $paramValues = cat_Products::getParams($jobRec->productId);
                    $stepParams = cat_Products::getParams($defaultTask->productId);
                    if($StepDriver = cat_Products::getDriver($defaultTask->productId)) {
                        $pData = $StepDriver->getProductionData($defaultTask->productId);
                        $prevTaskRecs = static::getPrevParamValues($jobRec->containerId, $pData['planningParams']);
                        if(is_array($pData['planningParams'])){
                            foreach ($pData['planningParams'] as $pId){
                                if(array_key_exists($pId, $paramValues)){
                                    $v = $paramValues[$pId];
                                } elseif(array_key_exists($pId, $stepParams)){
                                    $v = $stepParams[$pId];
                                } elseif(array_key_exists($pId, $prevTaskRecs)){
                                    $v = $prevTaskRecs[$pId];
                                } else {
                                    $v = cat_Params::getDefaultValue($pId, $this->getClassId(), $newTask->id);
                                }

                                if(isset($v)){
                                    $paramRec = (object)array('classId' => $this->getClassId(), 'productId' => $newTask->id, 'paramId' => $pId, 'paramValue' => $v);
                                    $saveParams[$pId] = $paramRec;
                                }
                            }
                        }
                    }

                    if(is_array($defaultTask->params)){
                        foreach ($defaultTask->params as $pId => $pVal){
                            $paramRec = (object)array('classId' => $this->getClassId(), 'productId' => $newTask->id, 'paramId' => $pId, 'paramValue' => $pVal);
                            $saveParams[$pId] = $paramRec;
                        }
                    }

                    foreach ($saveParams as $pRec){
                        cat_products_Params::save($pRec);
                    }

                    $this->logWrite('Автоматично създаване от задание', $newTask->id);
                } catch (core_exception_Expect $e) {
                    reportException($e);
                    $msg = 'Проблем при създаване на операция';
                    $msgType = 'error';
                }

                $num++;
            }

            followRetUrl(null, $msg, $msgType);
        }

        followRetUrl(null, '|Имаше проблем', 'error');
    }


    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    public static function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        // Ако се иска директно контиране редирект към екшъна за контиране
        if (isset($data->form) && $data->form->isSubmitted() && $data->form->rec->id) {

            $retUrl = getRetUrl();
            if ($retUrl['Ctr'] == 'planning_Jobs') {
                if ($retUrl['Act'] == 'selectTaskAction') {
                    if ($data->form->cmd == 'save_pending_new') {
                        $data->retUrl = $retUrl;
                    }
                } elseif ($retUrl['Act'] == 'single') {
                    $jobThreadId = planning_Jobs::fetchField($retUrl['id'], 'threadId');
                    if (doc_Threads::haveRightFor('single', $jobThreadId)) {
                        $newRetUrl = array('doc_Containers', 'list', 'threadId' => $jobThreadId, "#" => $mvc->getHandle($data->form->rec->id));
                    } else {
                        $newRetUrl = $retUrl;
                        $newRetUrl["#"] = $mvc->getHandle($data->form->rec->id);
                    }

                    $data->retUrl = $newRetUrl;
                }
            }
        }
    }


    /**
     * Връща наличните за избор шаблони за производствени операции
     *
     * @param int|null $exTemplateId - ид на вече избран шаблон ако има да се добави към опциите
     * @return array $options
     */
    public static function getAllAvailableLabelTemplates($exTemplateId = null)
    {
        $options = array();
        $labelTemplateRecs = label_Templates::getTemplatesByClass(get_called_class());
        foreach ($labelTemplateRecs as $templateRec) {
            $options[$templateRec->id] = $templateRec->title;
        }

        if (isset($exTemplateId)) {
            if (!array_key_exists($exTemplateId, $options)) {
                $options[$exTemplateId] = label_Templates::fetchField($exTemplateId, 'title');
            }
        }

        return $options;
    }


    /**
     * Параметрите на бутона за етикетиране
     */
    protected static function on_AfterGetLabelTemplates($mvc, &$res, $rec, $series = 'label', $ignoreWithPeripheralDriver = true)
    {
        $rec = $mvc->fetchRec($rec);
        if (isset($rec->labelTemplate) && !array_key_exists($rec->labelTemplate, $res)) {
            $templateSeries = label_Templates::fetchField($rec->labelTemplate, 'series');
            if ($templateSeries == $series) {
                $res[$rec->labelTemplate] = label_Templates::fetch($rec->labelTemplate);
            }
        }
    }


    /**
     * Изпълнява се след подготовката на листовия изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        if(Mode::is('isReorder')){
            unset($data->title);
        }
    }


    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        unset($data->title);
        $data->listTableId = 'dragTable';
        core_Debug::startTimer('RENDER_TABLE');
        $rows = &$data->rows;
        if (!countR($rows)) return;

        // Ако е филтрирано по център на дейност
        core_Debug::startTimer('RENDER_HEADER');
        $paramCache = array();
        $fieldsToFilterIfEmpty = array('dependantProgress', 'saleId', 'prevExpectedTimeEnd', 'notes');

        // Кои ще са планиращите параметри
        $plannedParams = array();
        // Еднократно извличане на таговете на листваните операции и заданията към тях
        $containerIds = arr::extractValuesFromArray($data->recs, 'containerId');
        $containerIds += arr::extractValuesFromArray($data->recs, 'originId');
        $tagsArr = tags_Logs::getTagsFromContainers($containerIds);
        $inlineTags = array();
        foreach ($tagsArr as $cId => $tagArr) {
            $tagsStr = '';
            array_walk($tagArr, function ($a) use (&$tagsStr) {
                $tagsStr .= $a['span'];
            });
            $inlineTags[$cId] = "<span class='documentTags'>{$tagsStr}</span>";
        }

        // Ако има избрано оборудване добавят се параметрите от него и от групата му
        if (isset($data->listFilter->rec->assetId)) {
            $assetRec = planning_AssetResources::fetch($data->listFilter->rec->assetId, 'planningParams,groupId');
            $plannedParams += keylist::toArray($assetRec->planningParams);
            $groupParams = planning_AssetGroups::fetchField($assetRec->groupId, 'planningParams');
            $plannedParams += keylist::toArray($groupParams);
            unset($data->listFields['assetId']);
        }
        if (isset($data->listFilter->rec->folder)) {
            unset($data->listFields['folderId']);
        }
        //unset($data->listFields['firstProgress']);
       // unset($data->listFields['lastProgressProduction']);

        if (Mode::is('isReorder')) {
            $data->stopListRefresh = true;
            unset($data->listFields['_rowTools']);
        }

        // Ако има избран център - тези параметри от тях/ ако няма всички параметри от центровете с листвани задачи
        if(!$data->masterMvc){
            if (isset($data->listFilter->rec->folder)) {
                $folderIds = array($data->listFilter->rec->folder);
            } else {
                $folderIds = arr::extractValuesFromArray($data->recs, 'folderId');
            }

            if(countR($folderIds)){
                $cQuery = planning_Centers::getQuery();
                $cQuery->in('folderId', $folderIds);
                $cQuery->where("#planningParams IS NOT NULL");
                $cQuery->show('planningParams');
                while($cRec = $cQuery->fetch()){
                    $plannedParams += keylist::toArray($cRec->planningParams);
                }
            }
        }

        // Ако има намерени планиращи параметри - показват се в таблицата
        $firstColumnsIfNotSelected = arr::make(array_keys($data->listFields), true);

        if (countR($plannedParams)) {
            $pQuery = cat_Params::getQuery();
            $pQuery->in('id', $plannedParams);
            $paramCache = $pQuery->fetchAll();
            $data->listFieldsParams = cat_Params::getOrderedArr($paramCache, 'desc');

            // и той има избрани параметри за планиране, добавят се в таблицата
            $paramFields = array();
            foreach ($data->listFieldsParams as $paramId) {
                $paramRec = $paramCache[$paramId];
                $fullName = cat_Params::getVerbal($paramRec, 'typeExt');
                $paramExt = explode(' » ', $fullName);
                if (countR($paramExt) == 1) {
                    $paramExt[1] = $paramExt[0];
                    $paramExt[0] = " ";
                }
                if ($fullName != $paramExt[1] && !Mode::is('isReorder')) {
                    $paramExt[1] = ht::createHint($paramExt[1], $fullName);
                }
                $paramFields["param_{$paramRec->id}"] = "|*<small>{$paramExt[1]}</small>";
                $data->listTableMvc->FNC("param_{$paramRec->id}", 'varchar', 'tdClass=taskParamCol');
            }
            $data->listTableMvc->setField("assetId", 'tdClass=small');
            $fieldsToFilterIfEmpty = array_merge($paramFields, $fieldsToFilterIfEmpty);
            $data->listFields += $paramFields;
        }

        $tableClass = 'small';
        $data->listTableMvc->FNC('prevExpectedTimeEnd', 'datetime');
        $data->listTableMvc->FNC('nextExpectedTimeStart', 'datetime');
        $data->listTableMvc->FNC('dueDate', 'date');
        $data->listTableMvc->FNC('dependantProgress', 'datetime');
        $data->listTableMvc->FNC('nextId', 'datetime');
        $data->listTableMvc->FNC('saleId', 'varchar');
        $data->listTableMvc->FNC('jobQuantity', 'double', 'smartCenter');
        if (Mode::is('isReorder')){
            $data->listTableMvc->tableRowTpl = "[#ROW#]";
            unset($data->listFields['folderId']);
            $tableClass = 'reorderSmallCol';
        }

        $data->listTableMvc->setField('notes', 'tdClass=notesCol');
        foreach (array('prevExpectedTimeEnd', 'expectedTimeStart', 'expectedTimeEnd', 'nextExpectedTimeStart', 'dueDate', 'dependantProgress', 'nextId', 'title', 'originId', 'progress', 'saleId') as $fld) {
            $dateClass = in_array($fld, array('expectedTimeStart', 'expectedTimeEnd')) ? "{$tableClass} openModal" : $tableClass;
            $data->listTableMvc->setField($fld, "tdClass={$dateClass}");
        }
        $data->listTableMvc->setField('dependantProgress', "tdClass={$tableClass} dependantProgress");

        core_Debug::stopTimer('RENDER_HEADER');
        $showSaleInList = planning_Setup::get('SHOW_SALE_IN_TASK_LIST');
        $displayPlanningParamsCount = countR($data->listFieldsParams);

        // Еднократно извличане на специфичните параметри за показваните операции
        $taskSpecificParams = array();
        if ($displayPlanningParamsCount) {

            // Ако в операцията има конкретно избрани параметри - ще се използват те с приоритет
            core_Debug::startTimer('RENDER_VERBAL_PARAM_HEADER');
            $taskParamQuery = cat_products_Params::getQuery();
            $taskParamQuery->where("#classId = {$mvc->getClassId()}");
            $taskParamQuery->in('productId', array_keys($data->recs));
            $taskParamQuery->in('paramId', $data->listFieldsParams);
            while ($taskParamRec = $taskParamQuery->fetch()) {
                Mode::push('taskListMode', true);
                $taskParamVal = cat_Params::toVerbal($paramCache[$taskParamRec->paramId], $mvc->getClassId(), $taskParamRec->productId, $taskParamRec->paramValue);
                Mode::pop('taskListMode');
                $taskSpecificParams[$taskParamRec->productId][$taskParamRec->paramId] = $taskParamVal;
            }
            core_Debug::stopTimer('RENDER_VERBAL_PARAM_HEADER');
        }

        // Еднократно извличане на зависимите предходни операции
        core_Debug::startTimer('RENDER_DEPENDANT');
        $dependantTaskArr = planning_StepConditions::getPrevAndNextTasks($data->recs);
        core_Debug::stopTimer('RENDER_DEPENDANT');

        // Еднократно извличане на заданията за бързодействие
        $jobRecs = array();
        $jQuery = planning_Jobs::getQuery();
        $jQuery->in("containerId", arr::extractValuesFromArray($data->recs, 'originId'));
        $jQuery->show('id,containerId,productId,dueDate,quantityInPack,quantity,packagingId,saleId');

        while ($jRec = $jQuery->fetch()) {
            $jobRecs[$jRec->containerId] = $jRec;
            $taskByJob = planning_Tasks::getTasksByJob($jRec->id, 'active,wakeup,closed,stopped,pending', false);
            $jobRecs[$jRec->containerId]->tasks = array();
            $i = 1;
            foreach ($taskByJob as $jobTask){
                $jobRecs[$jRec->containerId]->tasks[$i] = $jobTask;
                $i++;
            }

            if($showSaleInList != 'no'){
                if(!empty($jRec->saleId)){
                    $jRec->_saleId = sales_Sales::getLink($jRec->saleId, 0);
                    $saleRec = sales_Sales::fetch($jRec->saleId, 'deliveryTermTime,deliveryTime,activatedOn');
                    $deliveryDate = null;
                    if (!empty($saleRec->deliveryTime)) {
                        $deliveryDate = $saleRec->deliveryTime;
                    } elseif (!empty($saleRec->deliveryTermTime)) {
                        $deliveryDate = dt::addSecs($saleRec->deliveryTermTime, $saleRec->activatedOn);
                    }

                    if(!empty($deliveryDate)){
                        $jRec->_saleId .= " [" . dt::mysql2verbal($deliveryDate, 'd.m.y') . "]";
                    } else {
                        $jRec->_saleId .= " <span class='quiet'>[n/a]</span>";
                    }
                }
            }

            // Взимане с приоритет от кеша на параметрите на артикула от заданието
            $jobParams = core_Permanent::get("taskListJobParams{$jRec->productId}");
            if (!is_array($jobParams)) {
                $jobParams = cat_Products::getParams($jRec->productId, null, true);
                core_Permanent::set("taskListJobParams{$jRec->productId}", $jobParams, 5);
            }
            $jobRecs[$jRec->containerId]->params = $jobParams;
        }

        // Дали заданията и операциите са всичките с една мярка
        $jobPacks = arr::extractValuesFromArray($jobRecs, 'packagingId');
        $haveDiffJobPacks = countR($jobPacks) > 1;

        $measuresArr = $productIds = array();
        foreach ($data->recs as $r1){
            $measuresArr[$r1->measureId] = $r1->measureId;
            $productIds[$r1->productId] = $r1->productId;
        }
        $haveDiffMeasure = countR($measuresArr) > 1;
        $haveDiffProductIds = countR($productIds) > 1;

        foreach ($rows as $id => $row) {
            core_Debug::startTimer('RENDER_ROW');
            $rec = $data->recs[$id];
            if($saleIdRow = $jobRecs[$rec->originId]->_saleId){
                $row->saleId = $saleIdRow;
            }

            // Ако има планирани предходни операции - да се показват с техните прогреси
            if (!empty($dependantTaskArr[$rec->id]['previous'])) {
                $dependantTaskBlocks = planning_StepConditions::renderTaskBlock($dependantTaskArr[$rec->id]['previous'], 'reorderBlocks', null, !Mode::is('isReorder'));
                if(countR($dependantTaskBlocks)){
                    $row->dependantProgress = implode("", $dependantTaskBlocks);
                }
            }

            // Добавяне на дата атрибут за да може с драг и дроп да се преподреждат ПО в списъка
            $row->ROW_ATTR['data-id'] = $rec->id;

            if($haveDiffProductIds || isset($data->masterMvc)){
                $stepTitle = str::limitLen($mvc->getStepTitle($rec->productId), 32);
                if (!empty($rec->subTitle)) {
                    $stepTitle .= "|{$mvc->getFieldType('subTitle')->toVerbal($rec->subTitle)}";
                }
                $row->title = "{$rec->id}|{$stepTitle}";
            } else {
                $row->title = $rec->id;
                if(!empty($rec->subTitle)){
                    $row->title .= "|" . $mvc->getFieldType('subTitle')->toVerbal($rec->subTitle);
                }
            }

            $titleAttr = array('title' => "#" . $mvc->getTitleById($rec->id));
            $singleUrl = static::getSingleUrlArray($rec->id);

            if(Mode::get('isReorder')){
                $titleAttr['data-doubleclick-url'] = toUrl($singleUrl);
                $titleAttr['class'] = 'doubleclicklink';
                $row->title = ht::createElement("span", $titleAttr, $row->title, true);

                $rowNoteAttr = array('class' => 'notesHolder', 'id' => "notesHolder{$rec->id}", 'data-prompt-text' => tr('Забележка на|*: ') . $mvc->getRecTitle($rec));
                $rowNoteAttr['data-url'] = $mvc->haveRightFor('edit', $rec) ? toUrl(array($mvc, 'editnotes', $rec->id), 'local') : null;
                $row->notes = ht::createElement("span", $rowNoteAttr, $row->notes, true);
                if (!$mvc->haveRightFor('edit', $rec)) {
                    $row->ROW_ATTR['data-dragging'] = "false";
                    $row->ROW_ATTR['class'] .= " state-forbidden";
                    $row->ROW_ATTR['style'] = 'opacity:0.2';
                }
            } else {
                $row->title = ht::createLink($row->title, $singleUrl, false, $titleAttr);
            }

            if ($displayPlanningParamsCount) {

                // Кои са параметрите от артикула на заданието за операцията
                $jobParams = $jobRecs[$rec->originId]->params;
                foreach ($data->listFieldsParams as $paramId) {
                    $live = true;
                    $pValue = array_key_exists($paramId, $jobParams) ? $jobParams[$paramId] : null;
                    if (is_array($taskSpecificParams[$rec->id]) && array_key_exists($paramId, $taskSpecificParams[$rec->id])) {
                        $pValue = $taskSpecificParams[$rec->id][$paramId];
                        $live = false;
                    }

                    if (isset($pValue)) {
                        $pSuffix = cat_Params::getVerbal($paramCache[$paramId], 'suffix');
                        $row->{"param_{$paramId}"} = $pValue;
                        if (!empty($pSuffix)) {
                            $row->{"param_{$paramId}"} .= " {$pSuffix}";
                        }
                        if ($live) {
                            $row->{"param_{$paramId}"} = "<span style='color:blue'>{$row->{"param_{$paramId}"}}</span>";
                        }
                    }
                }
            }

            // Допълнителна обработка на показването на заданието в списъка на ПО
            $row->dueDate = core_Type::getByName('datetime(format=smartTime)')->toVerbal($jobRecs[$rec->originId]->dueDate);
            $jobPackQuantity = $jobRecs[$rec->originId]->quantity / $jobRecs[$rec->originId]->quantityInPack;
            $quantityStr = core_Type::getByName('double(smartRound)')->toVerbal($jobPackQuantity) . " " . ($haveDiffJobPacks ? "<span class='small'>" . cat_UoM::getSmartName($jobRecs[$rec->originId]->packagingId, $jobPackQuantity) . "</span>" : '');

            if($dependantTaskArr[$rec->id]['previous'][0]){
                $rec->prevExpectedTimeEnd = $dependantTaskArr[$rec->id]['previous'][0]->expectedTimeEnd;
            }

            if(!empty($dependantTaskArr[$rec->id]['next'][0])){
                $rec->nextExpectedTimeStart = $dependantTaskArr[$rec->id]['next'][0]->expectedTimeStart;
            } else {
                $rec->nextExpectedTimeStart = $jobRecs[$rec->originId]->dueDate;
            }

            foreach (array('prevExpectedTimeEnd', 'expectedTimeStart', 'expectedTimeEnd', 'nextExpectedTimeStart') as $fld) {
                $row->{$fld} = $mvc->getDateFieldVerbal($rec, $fld, Mode::is('isReorder'));
                if(Mode::is('isReorder')){
                    $row->{$fld} = ht::createElement("span", array('id' => "{$fld}{$rec->id}"), $row->{$fld}, true)->getContent();
                }
            }

            if(!empty($dependantTaskArr[$rec->id]['next'])){
                $nextArr = planning_StepConditions::renderTaskBlock($dependantTaskArr[$rec->id]['next'], 'reorderBlocks', 1, !Mode::is('isReorder'));
                $row->nextId = $nextArr[key($nextArr)];
            }

            if(!empty($rec->dueDate)){
                $row->dueDate = dt::mysql2verbal($rec->dueDate, 'd.m.y');
                $row->dueDate = ht::createElement("span", array('data-date' => "{$rec->dueDate} 00:00:00", 'class' => "dueDateCol"), $row->dueDate, true)->getContent();
            }

            if(Mode::get('isReorder')){
                $jobTitle = planning_Jobs::getTitleById($jobRecs[$rec->originId]);
                $singleJobUrl = toUrl(planning_Jobs::getSingleUrlArray($jobRecs[$rec->originId]));
                $row->originId = ht::createElement("span", array('class' => 'doubleclicklink', 'data-doubleclick-url' => $singleJobUrl, 'title' => $jobTitle, 'onmouseUp' => 'selectInnerText(this);'), $jobTitle, true);
            } else {
                $row->originId = planning_Jobs::getHyperlink($jobRecs[$rec->originId], true);
            }

            $row->jobQuantity = $quantityStr;
            $row->plannedQuantity = $row->plannedQuantity . ($haveDiffMeasure ? " <span class='small'>" . cat_UoM::getSmartName($rec->measureId, $rec->plannedQUantity) . "</span>" : '');

            core_Debug::stopTimer('RENDER_ROW');
        }

        // Преподреждане на колонките
        $orderedParamByUser = type_Table::toArray(planning_Setup::get('ORDER_TASK_PARAMS_IN_LIST'));
        if(countR($orderedParamByUser)){
            $selectedParams = arr::extractValuesFromArray($orderedParamByUser, 'paramId');

            if(isset($selectedParams['_rest_'])){
                foreach ($firstColumnsIfNotSelected as $oField){
                    if($oField != '_rowTools'){
                        $selectedParams[$oField] = $oField;
                    }
                }
            }

            $data->listFields = arr::reorderArrayByOrderedKeys($data->listFields, $selectedParams, $firstColumnsIfNotSelected);
        }

        // Скриване на колонките
        $hideColumns = type_Table::toArray(planning_Setup::get('ORDER_TASK_PARAMS_HIDE_IN_LIST'));
        if(countR($hideColumns)) {
            $hideColumns = arr::extractValuesFromArray($hideColumns, 'paramId');
            $data->listFields = array_diff_key($data->listFields, $hideColumns);
        }
        $data->listFields = core_TableView::filterEmptyColumns($rows, $data->listFields, $fieldsToFilterIfEmpty);

        // При показване на приключените да се подмени колонката за датата
        if ($data->listFilter->rec->state == 'closed') {
            $modifiedData = array();
            $firstProgressCaption = $data->listFields['firstProgress'];
            $lastProgressProductionCaption = $data->listFields['lastProgressProduction'];
            unset($data->listFields['firstProgress'], $data->listFields['lastProgressProduction']);

            foreach ($data->listFields as $key => $value) {
                if ($key == "expectedTimeStart") {
                    $modifiedData["firstProgress"] = $firstProgressCaption;
                }elseif ($key === "expectedTimeEnd") {
                    $modifiedData["lastProgressProduction"] = $lastProgressProductionCaption;
                } else {
                    $modifiedData[$key] = $value;
                }
            }
            $data->listFields = $modifiedData;
        } else {
            unset($data->listFields['firstProgress'], $data->listFields['lastProgressProduction']);
        }

        core_Debug::stopTimer('RENDER_TABLE');
    }


    /**
     * Вербално представяне на полето
     *
     * @param stdClass $rec
     * @param string $fld
     * @param boolean $isReorder
     * @return $res
     */
    private function getDateFieldVerbal($rec, $fld, $isReorder = false)
    {
        $res = $datePure = null;

        if(!empty($rec->{$fld})) {
            $datePure = strlen($rec->{$fld}) == 10 ? "{$rec->{$fld}} 00:00:00" : $rec->{$fld};
            $res =  $isReorder ? dt::mysql2verbal($datePure, 'd.m.y H:i') : core_Type::getByName('datetime(format=smartTime)')->toVerbal($datePure);
        }

        $attr = array('data-date' => "{$datePure}", 'class' => "{$fld}Col");
        if(in_array($fld, array('expectedTimeStart', 'expectedTimeEnd'))){
            $attr['class'] .= " modalDateCol";
            $attr['data-task-id'] = $rec->id;
            $attr['data-task-field'] = $fld;
            if(!empty($rec->timeStart)){
                $res = ht::createElement('img', array('style' => 'height:12px;width:12px;', 'src' => sbf('img/16/pin.png', '')))->getContent() . " " . $res;
                $attr['data-manual-date'] = "{$datePure}";
            } elseif(!empty($rec->timeEnd)){
                $res = ht::createElement('img', array('style' => 'height:12px;width:12px;', 'src' => sbf('img/16/pin.png', '')))->getContent() . " " . $res;
                $attr['data-manual-date'] = "{$datePure}";
            }
            $attr['data-modal-caption'] = ($fld == 'expectedTimeStart') ? tr('Ново целево начало за') : tr('Нов целеви край за');
            $attr['data-modal-caption'] .= ": #" . $this->getTitleById($rec->id);
        }

        $res = $isReorder ? ht::createElement("span", $attr, $res, true)->getContent() : $res;

        return $res;
    }


    /**
     * Функция по подразбиране, за връщане на хеша на резултата
     *
     * @param core_Mvc $mvc
     * @param string $res
     * @param string $status
     */
    protected function on_AfterGetContentHash($mvc, &$res, &$status)
    {
        // Хеша е датата на последна модификация на движенията
        $mQuery = $mvc->getQuery();
        $mQuery->orderBy('modifiedOn', 'DESC');
        $mQuery->show('modifiedOn');
        $mQuery->limit(1);

        $assetId = Request::get('assetId');
        if (!empty($assetId)) {
            if(strpos($assetId, '_') !== false){
                // по неясни причини от някъде идва с хеш за това колкото да работи се маха
                list($assetId,) = explode('_', $assetId);
            }
            $mQuery->where(array("#assetId = '[#1#]'", $assetId));
        }

        $folderId = Request::get('folderId');
        if (!empty($folderId)) {
            if(strpos($folderId, '_') !== false){
                // по неясни причини от някъде идва с хеш за това колкото да работи се маха
                list($folderId,) = explode('_', $folderId);
            }
            $mQuery->where(array("#folderId = '[#1#]'", $folderId));
        }

        $res = md5(trim($mQuery->fetch()->modifiedOn));
    }


    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Първичния ключ на направения запис
     * @param stdClass     $rec     Всички полета, които току-що са били записани
     * @param string|array $fields  Имена на полетата, които sa записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        // Ако има избрано оборудване, задачата се поставя на правилното място и се преподреждат задачите на машината
        if(isset($rec->assetId)){
            if($rec->_stopReorder) return;

            // Ако не е минато през формата
            if(!$rec->_fromForm){

                // Ако няма начало изчислява се да започне след последната
                if($rec->state == 'active' && $rec->brState == 'pending'){
                    // При активиране от чернова - намърдва се най-накрая
                    $rec->startAfter = $mvc->getPrevOrNextTask($rec);
                } elseif($rec->state == 'rejected' || ($rec->state == 'closed' && in_array($rec->brState, array('stopped', 'active', 'wakeup')))){

                    // При оттегляне изчезва от номерацията
                    $rec->orderByAssetId = $rec->startAfter = null;
                } elseif(in_array($rec->state, array('pending', 'active', 'wakeup')) && in_array($rec->brState, array('rejected', 'closed'))){

                    // При възстановяване в намърдва се най-накрая
                    $rec->startAfter = $mvc->getPrevOrNextTask($rec);
                } elseif($rec->state == 'pending' && in_array($rec->brState, array('draft', 'waiting'))) {

                    // Ако става на заявка от чакащо/чернова
                    $rec->startAfter = $mvc->getPrevOrNextTask($rec);
                }
            }

            if(!empty($rec->startAfter)){
                // Ако има посочена след коя е - намъква се след нея
                $orderByAssetId = $mvc->fetchField($rec->startAfter, 'orderByAssetId');
                $rec->orderByAssetId = $orderByAssetId + 0.5;
            } else {
                if(in_array($rec->state, array('pending', 'active', 'wakeup'))){
                    $firstTaskId = key(planning_AssetResources::getAssetTaskOptions($rec->assetId));
                    $orderByAssetId = ($firstTaskId) ? $mvc->fetchField($firstTaskId, 'orderByAssetId') : 1;
                    $rec->orderByAssetId = $orderByAssetId - 0.5;
                }
            }

            if($rec->orderByAssetId != $rec->_exAssetId){
                $mvc->save_($rec, 'orderByAssetId');
                if(isset($rec->assetId)){
                    $mvc->reorderTasksInAssetId[$rec->assetId] = $rec->assetId;
                }
            }

            if(isset($rec->_exAssetId) && $rec->assetId != $rec->_exAssetId){
                $mvc->reorderTasksInAssetId[$rec->_exAssetId] = $rec->_exAssetId;
            }
        }

        // Маркиране на операцията, ако е променена нормата ѝ, да се преизчислят нормите на детайлите ѝ
        if($rec->_exIndTime != $rec->indTime){
            $product4Task = ($rec->isFinal == 'yes') ? planning_Jobs::fetchField("#containerId = {$rec->originId}", 'productId') : $rec->productId;
            if(planning_ProductionTaskDetails::count("#taskId = {$rec->id} AND #type = 'production' AND #productId = {$product4Task}")){
                $mvc->recalcProducedDetailIndTime[$rec->id] = (object)array('id' => $rec->id, 'productId' => $product4Task);
            }
        }

        if($rec->state == 'pending' && in_array($rec->brState, array('draft', 'waiting'))){
            if($Driver = cat_Products::getDriver($rec->productId)){
                $pData = $Driver->getProductionData($rec->productId);

                // Ако има планиращи действия
                if(is_array($pData['actions'])){
                    $actionsWithNorms = isset($rec->assetId) ? planning_AssetResourcesNorms::getNormOptions($rec->assetId, array(), true) : array();
                    $addedPlannedActions = 0;

                    $now = dt::now();
                    foreach ($pData['actions'] as $actionId){
                        if(planning_ProductionTaskProducts::fetchField("#taskId = {$rec->id} AND #type = 'input' AND #productId = {$actionId}")) continue;

                        // Ако няма норма за планираното действие - ще се пропуска
                        if(!in_array($actionId, $actionsWithNorms)) continue;

                        // Ще се създава запис за планираното действие за влагане
                        $inputRec = (object)array('taskId' => $rec->id, 'productId' => $actionId, 'type' => 'input', 'quantityInPack' => 1, 'plannedQuantity' => 1, 'packagingId' => cat_Products::fetchField($actionId, 'measureId'), 'createdOn' => $now, 'modifiedBy' => core_Users::SYSTEM_USER, 'modifiedOn' => $now);
                        if($normRec = planning_AssetResources::getNormRec($rec->assetId, $actionId)){
                            $inputRec->indTime = $normRec->indTime;
                        }
                        planning_ProductionTaskProducts::save($inputRec);
                        $addedPlannedActions++;
                    }

                    if($addedPlannedActions){
                        core_Statuses::newStatus('Добавени са планираните действия за операцията|*!');
                    }
                }
            }
        }

        if($rec->state == 'rejected'){
            $mvc->reorderTasksByJobIds[$rec->originId] = $rec->originId;
            $rec->saoOrder = null;
            $mvc->save_($rec, 'saoOrder');
        }

        // Копиране на параметрите на артикула към операцията
        if (is_array($rec->_params)) {
            cat_products_Params::saveParams($mvc, $rec);
        }
    }


    /**
     * Възстановяване на оттеглен документ
     *
     * @param core_Mvc $mvc
     * @param mixed    $res
     * @param int      $id
     */
    protected static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        $mvc->reorderTasksByJobIds[$rec->originId] = $rec->originId;
        $mvc->setLastInJobQueue($rec);
    }


    /**
     * Връща файла, който се използва в документа
     *
     * @param object $rec
     * @return array
     */
    public function getLinkedFiles($rec)
    {
        $fhArr = array();
        $rec = $this->fetchRec($rec);
        if(!empty($rec->description)){
            $fhArr += fileman_RichTextPlg::getFiles($rec->description);
        }

        if($Driver = cat_Products::getDriver($rec->productId)){
            $fhArr += $Driver->getLinkedFiles($rec->productId);
        }

        return $fhArr;
    }

    /**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    public static function on_AfterSessionClose($mvc)
    {
        core_Debug::startTimer('AFTER_SESSION_TASKS');

        // Задачите към заопашените оборудвания се преподреждат
        if (countR($mvc->reorderTasksInAssetId)) {
            core_Debug::startTimer('REORDER_TASK_ASSET_SHUTDOWN');
            foreach ($mvc->reorderTasksInAssetId as $assetId) {
                if(isset($assetId)){
                    planning_AssetResources::reOrderTasks($assetId);
                }
            }
            core_Debug::stopTimer('REORDER_TASK_ASSET_SHUTDOWN');
        }

        if (countR($mvc->recalcProducedDetailIndTime)) {
            foreach ($mvc->recalcProducedDetailIndTime as $rec) {
                planning_ProductionTaskDetails::recalcIndTime($rec->id, 'production', $rec->productId);
                core_Statuses::newStatus('Нормата е променена. Преизчислени са заработките на прогреса|*!');
            }
        }

        if ($mvc->recalcTaskTimes) {
            core_Debug::startTimer('TASKS_AFTER_SESSION_RECALC_TIMES');
            cls::get('planning_AssetResources')->cron_RecalcTaskTimes();
            core_Debug::stopTimer('TASKS_AFTER_SESSION_RECALC_TIMES');
        }

        core_Debug::stopTimer('AFTER_SESSION_TASKS');
    }


    /**
     * При шътдаун на скрипта преизчислява наследените роли и ролите на потребителите
     */
    public static function on_Shutdown($mvc)
    {
        // Преподреждане на операциите в рамките на бутнатите задания
        if (countR($mvc->reorderTasksByJobIds)) {
            core_Debug::startTimer('REORDER_BY_JOB');
            foreach ($mvc->reorderTasksByJobIds as $originId) {
                $mvc->reorderTasksInJob($originId);
                core_Statuses::newStatus('Преподредени са операциите в заданието|*!');
            }
            core_Debug::stopTimer('REORDER_BY_JOB');
        }

        if (countR($mvc->cacheAssetDataOnShutdown)) {
            core_Debug::startTimer('CACHE_ON_SHUTDOWN');
            foreach ($mvc->cacheAssetDataOnShutdown as $assetId) {
                $cacheData = array('assetId' => planning_AssetResources::fetch($assetId), 'tasks' => planning_AssetResources::getAssetTaskOptions($assetId, true));
                $originIds = arr::extractValuesFromArray($cacheData['tasks'], 'originId');

                $jQuery = planning_Jobs::getQuery();
                $jQuery->in('containerId', $originIds);
                while($jRec = $jQuery->fetch()){
                    $cacheData['jobs'][$jRec->containerId]['tasks'] = array();
                    $taskByJob = planning_Tasks::getTasksByJob($jRec->id, 'active,wakeup,closed,stopped,pending', false);
                    $i = 1;
                    foreach ($taskByJob as $jobTask){
                        $cacheData['jobs'][$jRec->containerId]['tasks'][$i] = $jobTask;
                        $i++;
                    }
                }

                core_Cache::set('planning_Tasks',"reorderAsset{$assetId}", $cacheData, 60);
            }
            core_Debug::stopTimer('CACHE_ON_SHUTDOWN');
        }
    }


    /**
     * След рендиране на лист таблицата
     */
    protected static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
        // Включване на драг и дроп ако има избрано оборудване
        jquery_Jquery::enable($tpl);

        if(isset($data->listFilter->rec->assetId)){
            $assetId = $data->listFilter->rec->assetId;
            if(Mode::get('isReorder')){
                $headerTpl = new core_ET("<div class='reorderTableHeader'><span id='reorderTableHeaderAssetId'>[#assetId#]</span> [#backBtn#] [#saveBtn#] [#changeAssetBtn#] <div id='editWatchHolder'>[#editWatchBlock#]</div></div>");
                $headerTpl->append($mvc->getEditWatchHtml($assetId), 'editWatchBlock');
                $headerTpl->append(planning_AssetResources::getHyperlink($assetId), 'assetId');
                $backUrl = toUrl(getRetUrl());

                $hash = str::addHash($assetId, 6, 'RO');
                $saveBtnAttr = array('id' => 'saveBtn', 'title' => 'Запис на направените промени');
                if ($mvc->haveRightFor('savereordertasks', (object)array('assetId' => $assetId))) {
                    $saveBtnAttr['data-url'] = toUrl(array($mvc, 'savereordertasks', 'assetId' => $assetId, 'hash' => $hash), 'local');
                }

                $changeBtnArr = array('id' => 'changeBtn', 'title' => 'Преместване на операции на друга машина', 'data-error' => tr('Не са селектирани редове|*!'));
                if ($mvc->haveRightFor('savereordertasks', (object)array('assetId' => $assetId))) {
                    $hash = str::addHash($assetId, 6, 'PT');
                    $changeBtnArr['data-url'] = toUrl(array($mvc, 'changeAsset', 'assetId' => $assetId, 'hash' => $hash, 'ret_url' => true));
                }

                $headerTpl->append(ht::createFnBtn('Запис', '', false, $saveBtnAttr), 'saveBtn');
                $headerTpl->append(ht::createFnBtn('Отказ', '', false, array('id' => 'backBtn', 'data-url' => $backUrl, 'title' => 'Връщане назад')), 'assetId');
                $headerTpl->append(ht::createFnBtn('Смяна оборудване', '', false, $changeBtnArr), 'changeAssetBtn');
                $tpl->prepend($headerTpl);

                core_Ajax::subscribe($tpl, array($mvc, 'reorderTaskWatch', 'assetId' => $assetId, 'isReorder' => true), 'editWatchTasks', 5000);
                $dataUrl = toUrl(array($mvc, 'livereorder', 'assetId' => $assetId), 'local');
                $tpl->append("data-url={$dataUrl}", 'TABLE_ATTR');

                $scriptUrl = "https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js";
                $tpl->push($scriptUrl, 'JS');

                $scriptUrl = "https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js";
                $tpl->push($scriptUrl, 'JS');

                $scriptUrl = "https://cdnjs.cloudflare.com/ajax/libs/colresizable/1.6.0/colResizable-1.6.min.js";
                $tpl->push($scriptUrl, 'JS');

                $tpl->push('planning/js/Tasks.js', 'JS');
                $tpl->push('planning/tpl/TaskReordering.css', 'CSS');

                jqueryui_Ui::enable($tpl);
                $modalTpl = getTplFromFile('planning/tpl/DatePickerModal.shtml');
                $timeTpl = core_Type::getByName('hour')->renderInput('timePicker', null, array('class' => 'pickerSelect'));
                $modalTpl->replace($timeTpl, 'TIME_PICKER');
                $tpl->append($modalTpl);
            } else{
                jquery_Jquery::runAfterAjax($tpl, 'makeTooltipFromTitle');
            }
        }
    }


    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    protected static function on_AfterPrepareEditToolbar($mvc, $data)
    {
        $form = $data->form;
        $rec = $form->rec;
        if($form->toolbar->haveButton('activate')){
            $form->toolbar->removeBtn('activate');
        }

        if($form->toolbar->haveButton('btnPending')){
            if(empty($form->rec->id)){
                $data->form->toolbar->addSbBtn('Запис и Нов', 'save_pending_new', null, array('id' => 'saveAndNew', 'ef_icon' => 'img/16/tick-circle-frame.png', 'title' => 'Записване на операцията и към следващата'));
            }

            $form->toolbar->renameBtn('btnPending', 'Запис');
            $form->toolbar->setBtnOrder('saveAndNew', '2');
            $form->toolbar->setBtnOrder('btnPending', '1');
            $form->toolbar->setBtnOrder('save', '3');
            if(isset($rec->id) && $rec->state != 'draft'){
                $form->toolbar->removeBtn('save');
            }
        }
    }


    /**
     * Преди запис
     */
    protected static function on_BeforeSave($mvc, &$id, $rec, $fields = null, $mode = null)
    {
        if(in_array($rec->state, array('waiting', 'pending'))) {

            // Определяне на сътоянието при запис
            if(empty($rec->brState)){
                $rec->brState = 'draft';
            }
            if((empty($rec->timeDuration) && empty($rec->assetId))){
                $rec->brState = ($rec->state == 'pending') ? 'pending' : $rec->brState;
                $rec->state = 'waiting';
                core_Statuses::newStatus('Операцията няма избрано оборудване или продължителност. Преминава в чакащо състояние докато не се уточнят|*!');
            }

            $rec->state =  (empty($rec->timeDuration) && empty($rec->assetId)) ? 'waiting' : 'pending';
        }

        $rec->freeTimeAfter = 'no';

        // Запомняне на предишните стойностти на определени полета
        if(isset($rec->id)){
            $exRec = $mvc->fetch($rec->id, 'orderByAssetId,assetId,indTime,plannedQuantity', false);

            // Ако е сменено планираното к-во преизчислява се прогреса
            if (!empty($rec->plannedQuantity) && $rec->plannedQuantity != $exRec->plannedQuantity) {
                $percent = ($rec->totalQuantity - $rec->scrappedQuantity) / $rec->plannedQuantity;
                $rec->progress = round($percent, 2);
            }

            $rec->_exAssetId = $exRec->assetId;
            $rec->_exOrderByAssetId = $exRec->orderByAssetId;
            $rec->_exIndTime = $exRec->indTime;
            if(isset($rec->assetId) && $rec->assetId != $rec->_exAssetId){
                $rec->prevAssetId = $rec->_exAssetId;
            }
        }
    }


    /**
     * Функция, която прихваща след активирането на документа
     */
    protected static function on_AfterActivation($mvc, &$rec)
    {
        $now = dt::now();
        if(isset($rec->wasteProductId)){

            // Ако отпадъчният артикул е ръчно добавен - нищо не се прави
            if(planning_ProductionTaskProducts::fetchField("#taskId = {$rec->id} AND #type = 'waste' AND #productId = {$rec->wasteProductId}")) return;

            // Добавяне на отпадъка при първоначално активиране
            $wasteMeasureId = cat_Products::fetchField($rec->wasteProductId, 'measureId');
            $productId = ($rec->isFinal == 'yes') ? planning_Jobs::fetchField("#containerId = {$rec->originId}", 'productId') : $rec->productId;

            $calcedWasteQuantity = $rec->wasteStart;
            if(isset($rec->wastePercent)){

                // Калкулира се прогнозното количество на отпадъка
                $calcedWasteQuantity = null;
                if($conversionRate = cat_Products::convertToUom($productId, $wasteMeasureId)){
                    $calcedWasteQuantity = $rec->wasteStart + ($rec->plannedQuantity * $rec->quantityInPack * $conversionRate) * $rec->wastePercent;
                    $uomRound = cat_UoM::fetchField($wasteMeasureId, 'round');
                    $calcedWasteQuantity = round($calcedWasteQuantity, $uomRound);
                }
            }

            $wasteRec = (object)array('taskId' => $rec->id, 'productId' => $rec->wasteProductId, 'type' => 'waste', 'quantityInPack' => 1, 'plannedQuantity' => $calcedWasteQuantity, 'packagingId' => $wasteMeasureId, 'createdOn' => core_Users::getCurrent(), 'createdBy' => core_Users::getCurrent(), 'modifiedOn' => $now, 'createdOn' => $now);
            planning_ProductionTaskProducts::save($wasteRec);
        }
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $assetId = Request::get('assetId', 'int');
        if(isset($assetId) && !Request::get('Rejected')){
            if(planning_AssetResources::haveRightFor('recalctime', (object)array('id' => $assetId))){
                if (!Mode::is('isReorder')) {
                    $data->toolbar->addBtn('Преизчисляване', array('planning_AssetResources', 'recalcTimes', $assetId, 'ret_url' => true), 'ef_icon=img/16/arrow_refresh.png, title=Преизчисляване на времената на операциите към оборудването');
                }
            }
        }

        if (Mode::is('isReorder')) {
            $data->toolbar->removeBtn('btnPrint');
            $data->toolbar->removeBtn('binBtn');
        }
    }


    /**
     * Екшън за рекалкулиране на заработките
     */
    function act_Recalcindtimes()
    {
        $this->requireRightFor('recalcindtimes');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('recalcindtimes', $rec);

        planning_ProductionTaskDetails::recalcIndTime($rec->id);
        $this->touchRec($rec);
        $this->logWrite('Преизчисляване на заработките', $rec->id);

        followRetUrl(null, '|Заработките са преизчислени успешно|*!');
    }


    /**
     * Помощен масив за връщане на очакваните стойности на предупрежденията
     *
     * @param stdClass $rec
     * @param boolean $verbal
     * @return array $res
     */
    public static function getExpectedDeviations($rec, $verbal = false)
    {
        $res = array();
        $centerRec = planning_Centers::fetch("#folderId = {$rec->folderId}");
        $res['notice'] = !empty($rec->deviationNettoNotice) ? $rec->deviationNettoNotice : $centerRec->deviationNettoNotice;
        if($verbal && isset($res['notice'])){
            $res['notice'] = core_Type::getByName('percent(smartRound)')->toVerbal($res['notice']);
            $res['notice'] = !empty($rec->deviationNettoNotice) ?  $res['notice'] : "<span style='color:blue'>{$res['notice']}</span>";
            $noticeHint = !empty($rec->deviationNettoNotice) ? 'Информация' : 'Информация (от центъра на дейност)';
            $res['notice'] = ht::createHint($res['notice'], $noticeHint, 'img/16/green-info.png', false);
        }

        $res['critical'] = !empty($rec->deviationNettoCritical) ? $rec->deviationNettoCritical : $centerRec->deviationNettoCritical;
        if($verbal && isset($res['critical'])){
            $res['critical'] = core_Type::getByName('percent(smartRound)')->toVerbal($res['critical']);
            $res['critical'] = !empty($rec->deviationNettoCritical) ?  $res['critical'] : "<span style='color:blue'>{$res['critical']}</span>";
            $criticalHint = !empty($rec->deviationNettoNotice) ? 'Критично' : 'Критично (от центъра на дейност)';
            $res['critical'] = ht::createHint($res['critical'], $criticalHint, 'img/16/red-warning.png', false);
        }

        $res['warning'] = !empty($rec->deviationNettoWarning) ? $rec->deviationNettoWarning : (($centerRec->deviationNettoWarning) ? $centerRec->deviationNettoWarning : planning_Setup::get('TASK_NET_WEIGHT_WARNING'));
        if($verbal && isset($res['warning'])){
            $res['warning'] = core_Type::getByName('percent(smartRound)')->toVerbal($res['warning']);
            $res['warning'] = !empty($rec->deviationNettoWarning) ?  $res['warning'] : "<span style='color:blue'>{$res['warning']}</span>";
            $warningHint = !empty($rec->deviationNettoWarning) ?  'Предупреждение' : (($centerRec->deviationNettoWarning) ? 'Предупреждение (от центъра на дейност)' : 'Предупреждение (от настройката по подразбиране)');
            $res['warning'] = ht::createHint($res['warning'], $warningHint, 'warning', false);
        }

        return $res;
    }


    /**
     * Разрешено ли е на потребителя да произвежда след приключването на дадената ПО
     *
     * @param int $taskId - ид на операция
     * @param int|null $userId - ид на потребител
     * @param string $roles4FirstHorizon - роли, които да има потребителя за първия хоризонт
     * @param string $roles4SecondHorizon - роли, които да има потребителя за втория хоризонт
     * @return bool
     */
    public static function isProductionAfterClosureAllowed($taskId, $userId = null, $roles4FirstHorizon = 'taskPostProduction,ceo', $roles4SecondHorizon = 'taskPostProduction,ceo')
    {
        $now = dt::now();
        $masterRec = static::fetch($taskId, 'timeClosed,state,originId,productId,isFinal');
        $horizon1 = dt::addSecs(planning_Setup::get('TASK_PROGRESS_ALLOWED_AFTER_CLOSURE'), $masterRec->timeClosed);
        $horizon2 = dt::addSecs(planning_Setup::get('TASK_PRODUCTION_PROGRESS_ALLOWED_AFTER_CLOSURE'), $masterRec->timeClosed);

        // Ако времето е след първия хоризонт
        if($now >= $horizon1){

            // И сме след втория никой не може нищо
            if($now >= $horizon2){
                return false;
            } else {

                // Ако сме преди втория и има за произвеждане повече от 1 артикул да може да се произвежда
                $productionCount = planning_ProductionTaskProducts::count("#type = 'production' AND #taskId = {$taskId}");
                $allowedCount = ($masterRec->isFinal == 'yes') ? 1 : 0;
                if($productionCount != $allowedCount){
                    if(!haveRole($roles4SecondHorizon, $userId)){
                        return false;
                    }
                } else {
                    return false;
                }
            }

            // Ако е преди първия хоризонт се изисква роля за пост продукция
        } elseif(!haveRole($roles4FirstHorizon, $userId)){
            return false;
        }

        return true;
    }


    /**
     * Линк към мастъра, подходящ за показване във форма
     *
     * @param int $id - ид на записа
     * @return string $masterTitle - линк заглавие
     */
    public function getFormTitleLink($id)
    {
        $res = parent::getFormTitleLink($id);
        $rec = static::fetchRec($id);

        return $res . " [№:{$rec->saoOrder}]";
    }


    /**
     * Преподреждане на операциите в едно задание
     *
     * @param int $containerId
     * @return null|array
     */
    public function reorderTasksInJob($containerId)
    {
        // Кои са неоттеглените ПО към заданието
        $debugRes = array();
        $jobRec = planning_Jobs::fetch("#containerId = {$containerId}");
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#originId = {$jobRec->containerId} AND #state != 'rejected'");
        $tQuery->orderBy('saoOrder', "ASC");
        $allTasks = $tQuery->fetchAll();

        if(!countR($allTasks)) return;

        // Извличане на предходните етапи от етапите на операциите
        $productionStepIds = arr::extractValuesFromArray($allTasks, 'productId');
        $conditions = planning_StepConditions::getConditionalArr($productionStepIds);

        $res = array();
        foreach ($allTasks as $tRec){
            $debugRes[$tRec->id] = array('manualPreviousTask' => $tRec->manualPreviousTask);

            // За всяка операция се търсят от останалите операции, които са за нейни предходни етапи
            $cProductId = $tRec->productId;

            $prevTaskArr = array();
            if($tRec->isFinal == 'yes'){
                $prevTaskArr = array_filter($allTasks, function($a) use ($tRec){
                    return $a->saoOrder < $tRec->id && $a->id != $tRec->id;
                });
            }

            $foundArr = array_filter($allTasks, function($a) use (&$conditions, $cProductId){
                if(is_array($conditions[$cProductId])){
                    return array_key_exists($a->productId, $conditions[$cProductId]);
                }
                return false;
            });

            $previousTasks = $prevTaskArr + $foundArr;
            $prevTaskArr = arr::extractValuesFromArray($previousTasks, 'id');
            $prevTaskCalc = isset($tRec->manualPreviousTask) ? array($tRec->manualPreviousTask => $tRec->manualPreviousTask) : $prevTaskArr;
            $res[$tRec->id] = $prevTaskCalc;

            $debugRes[$tRec->id]['previousTasks'] = $prevTaskArr;
            $debugRes[$tRec->id]['isFinal'] = $tRec->isFinal;
            $debugRes[$tRec->id]['useOrderFields'] = $prevTaskCalc;
        }

        $orderStrategyClassId = planning_Setup::get('SORT_TASKS_IN_JOB_STRATEGY');
        $SortInterface = cls::getInterface('planning_OrderTasksInJobStrategyIntf', $orderStrategyClassId);
        $sortedArr = $SortInterface->order($res);

        $num = 1;
        $updateArr = array();
        foreach ($sortedArr as $taskId){
            $updateArr[] = (object)array('id' => $taskId, 'saoOrder' => $num);
            $num++;
        }

        // Обновяване на преизчислената подредба
        cls::get('planning_Tasks')->saveArray($updateArr, 'id,saoOrder');

        return array('debug' => $debugRes, 'updated' => $updateArr);
    }


    /**
     * Екшън за промяна на предходните етапи на операцията
     */
    public function act_Editprevioustask()
    {
        $this->requireRightFor('editprevioustask');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('editprevioustask', $rec);

        $form = cls::get('core_Form');
        $form->title = 'Избор на предходна операция|* <b>' . planning_Tasks::getHyperlink($id, true) . '</b>';
        $form->FLD('manualPreviousTask', 'key(mvc=planning_Tasks,select=name,allowEmpty)', 'caption=Пр. операция');

        $options = array();
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#originId = {$rec->originId} AND #state != 'rejected' AND #id != {$rec->id}");
        $tQuery->orderBy('saoOrder', "ASC");
        while($tRec = $tQuery->fetch()){
            $options[$tRec->id] = $this->getTitleById($tRec->id, false);
        }
        $form->setOptions('manualPreviousTask', array('' => '') + $options);
        $form->setDefault('manualPreviousTask', $rec->manualPreviousTask);
        $autoPreviousTaskId = key($this->getPreviousTaskIds($rec, 1));
        $form->setDefault('manualPreviousTask', $autoPreviousTaskId);

        $form->input();
        if ($form->isSubmitted()) {
            $msg = null;
            $fRec = $form->rec;

            if (empty($fRec->manualPreviousTask) || ($fRec->manualPreviousTask != $rec->manualPreviousTask && $autoPreviousTaskId != $fRec->manualPreviousTask)) {
                if(empty($fRec->manualPreviousTask)){
                    $sRec = (object) array('id' => $id, 'saoOrder' => 0.5);
                    $this->save_($sRec, 'saoOrder');
                } else {
                    $sRec = (object) array('id' => $id, 'manualPreviousTask' => $fRec->manualPreviousTask);
                    $this->save_($sRec, 'manualPreviousTask');
                }

                $this->logInAct('Ръчно избиране на предходна операция', $rec);
                $this->reorderTasksInJob($rec->originId);
                $msg = 'Предходната операция е избрана успешно|*!';
            }

            return followRetUrl(null, $msg);
        }

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        return $this->renderWrapping($form->renderHtml());
    }


    /**
     * Добавяне на операция на края на заданието
     *
     * @param stdClass $rec
     */
    private function setLastInJobQueue($rec)
    {
        $query = $this->getQuery();
        $query->where("#originId = {$rec->originId} AND #state != 'rejected'");
        $query->XPR('maxSaoOrder', 'double', 'MAX(#saoOrder)');
        $maxSaoOrder = $query->fetch()->maxSaoOrder;
        $maxSaoOrder = isset($maxSaoOrder) ? $maxSaoOrder : 0;
        $rec->saoOrder = $maxSaoOrder + 0.5;
        $this->save_($rec, 'saoOrder');
    }


    /**
     * Помощна ф-я извличаща предишните стойности на параметрите от заданието
     *
     * @param int $originId
     * @param array $params
     * @return array
     */
    public static function getPrevParamValues($originId, $params)
    {
        $prevRecValues = array();
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#state NOT IN ('draft', 'rejected') AND #originId = {$originId}");
        $tQuery->show('id');

        $prevTaskIds = arr::extractValuesFromArray($tQuery->fetchAll(), 'id');
        if(countR($prevTaskIds)){

            // Какви са предишните стойности на параметрите от ПО-та за този етап
            $me = cls::get(get_called_class());
            $prevParamQuery = cat_products_Params::getQuery();
            $prevParamQuery->where("#classId = {$me->getClassId()}");
            $prevParamQuery->in('productId', $prevTaskIds);
            $prevParamQuery->in("paramId", $params);
            $prevParamQuery->orderBy('id', 'ASC');
            $prevParamQuery->show('paramValue,paramId');
            while($prevRec = $prevParamQuery->fetch()){
                $prevRecValues[$prevRec->paramId] = $prevRec->paramValue;
            }
        }

        return $prevRecValues;
    }


    /**
     * След намиране на текста за грешка на бутона за 'Приключване'
     *
     * @param stdClass $rec
     * @return null|string
     */
    public function getCloseBtnError($rec)
    {
        if(empty($rec->mandatoryDocuments)) return;

        // Ако няма някой от задължителните документи да не може да се приключи операцията
        $errorArr = array();
        $mandatoryArr = keylist::toArray($rec->mandatoryDocuments);
        foreach ($mandatoryArr as $classId){
            if(!doc_Containers::count("#threadId = {$rec->threadId} AND #state IN ('active', 'pending') AND #docClass = {$classId}")){
                $errorArr[] = tr(cls::get($classId)->singleTitle);
            }
        }

        if(countR($errorArr)){
            $msg = 'Задължително е да има|* създадени на заявка/активни следните документи|*: ' . implode(', ', $errorArr);

            return $msg;
        }
    }


    /**
     * Помощна ф-я извличаща операциите създадени чрез клониране от дадена
     *
     * @param int $cloneFromId
     * @param int$containerId
     * @return array $exLinkArray
     */
    public static function getTasksClonedFromOtherTasks($cloneFromId, $containerId)
    {
        $exLinkArray = array();
        $exQuery = planning_Tasks::getQuery();
        $exQuery->where("#originId = {$containerId} AND #state != 'rejected' AND #clonedFromId = {$cloneFromId}");

        $exQuery->show('id');
        while($exRec = $exQuery->fetch()) {
            if (planning_Tasks::haveRightFor('single', $exRec->id)) {
                $exLinkArray[] = ht::createLinkRef('', planning_Tasks::getSingleUrlArray($exRec->id), false, "title=Към операция|* #" . planning_Tasks::getHandle($exRec->id));
            }
        }

        return $exLinkArray;
    }


    /**
     * Изпълнява се преди възстановяването на документа
     */
    public static function on_BeforeRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        if($rec->isFinal == 'yes'){
            $jobRec = doc_Containers::getDocument($rec->originId)->fetch();
            if($jobRec->allowSecondMeasure == 'no'){
                $derivativeMeasures = cat_UoM::getSameTypeMeasures(cat_Products::fetchField($jobRec->productId, 'measureId'));
                if(!array_key_exists($rec->measureId, $derivativeMeasures)){
                    core_Statuses::newStatus("Не може да се възстанови операцията, защото заданието вече не поддържа избраната мярка в операцията|*!", 'error');

                    return false;
                }
            }
        }
    }


    /**
     * Екшън за преподребдане
     */
    public function act_Livereorder()
    {
        self::requireRightFor('list');
        $assetId = Request::get('assetId', 'int');
        $inOrderTasks = Request::get('orderedTasks', 'varchar');
        $manualTimes = Request::get('manualTimes', 'varchar');
        $forceReorder = Request::get('forceReorder', 'int');

        $inOrderTasks = json_decode($inOrderTasks);
        $manualTimes = json_decode($manualTimes, true);
        $manualTimes = is_array($manualTimes) ? $manualTimes : array('expectedTimeStart' => array(), 'expectedTimeEnd' => array());
        $cachedData = core_Cache::get('planning_Tasks',"reorderAsset{$assetId}");

        // Коя задача след коя се намира
        $reference = array();
        foreach ($inOrderTasks as $k => $v){
            $reference[$v] = array('id' => $v);
            if($k){
                $reference[$v]['after'] = $inOrderTasks[$k-1];
            }
        }
        $cachedTasks = $cachedData['tasks'];
        foreach ($reference as $id => $refRec){
            if($refRec['after']){
                $cachedTasks[$id]->orderByAssetIdCalc = $cachedTasks[$reference[$id]['after']]->orderByAssetIdCalc + 0.5;
            }
        }

        arr::sortObjects($cachedTasks, 'orderByAssetIdCalc');
        $dates = arr::extractValuesFromArray($cachedData['tasks'], 'expectedTimeStart');
        unset($dates['']);
        $minDate = min($dates);

        // Разделяне на задачите на такива с целево време и без
        $Interval = planning_AssetResources::getWorkingInterval($cachedData['assetId'], $minDate);
        $tasksWithManualBegin = $tasksWithoutManualBegin = array();
        array_filter($cachedTasks, function ($t) use (&$tasksWithManualBegin, &$tasksWithoutManualBegin, $manualTimes) {
            $t->timeStart = array_key_exists($t->id, $manualTimes['expectedTimeStart']) ? $manualTimes['expectedTimeStart'][$t->id] : $t->timeStart;
            $t->timeEnd = array_key_exists($t->id, $manualTimes['expectedTimeEnd']) ? $manualTimes['expectedTimeEnd'][$t->id] : $t->timeEnd;

            if(!empty($t->timeStart)) {
                $tasksWithManualBegin[$t->id] = $t;
            } elseif(!empty($t->timeEnd)) {
                $t->timeStart = dt::addSecs(-1 * $t->calcedCurrentDuration, $t->timeEnd);
                $tasksWithManualBegin[$t->id] = $t;
            } else {
                $tasksWithoutManualBegin[$t->id] = $t;
            }
        });

        $interruptionArr = planning_Steps::getInterruptionArr($cachedData['tasks']);

        // Захранване на графика с продължителноста на задачите
        $new = $new2 = array();
        foreach (array('tasksWithManualBegin', 'tasksWithoutManualBegin') as $varName) {
            $arr = ${"{$varName}"};
            foreach ($arr as $taskRec) {
                $new2[$taskRec->id] = array('expectedTimeStart' => $taskRec->expectedTimeStart,
                    'expectedTimeEnd' => $taskRec->expectedTimeEnd,
                    'calcedDuration' => $taskRec->calcedDuration);

                $begin = null;
                if (!empty($taskRec->timeStart)) {
                    $begin = strtotime($taskRec->timeStart);
                }

                // Ще се върне резултата за новата продължителност на задачите
                $new[$taskRec->id] = array('expectedTimeStart' => null, 'expectedTimeEnd' => null, 'orderByAssetId' => $taskRec->orderByAssetId);

                $timeArr = $Interval->consume($taskRec->calcedCurrentDuration, $begin, null, $interruptionArr);
                if (is_array($timeArr)) {
                    $startDate = date('Y-m-d H:i', $timeArr[0]);
                    $endDate = date('Y-m-d H:i', $timeArr[1]);
                    $taskRec->expectedTimeStart = "$startDate";
                    $taskRec->expectedTimeEnd = "$endDate";

                    $new[$taskRec->id]['expectedTimeStartPure'] = $startDate;
                    $new[$taskRec->id]['expectedTimeEndPure'] = $endDate;
                    $new[$taskRec->id]['expectedTimeStart'] = $this->getDateFieldVerbal($taskRec, 'expectedTimeStart', true);
                    $new[$taskRec->id]['expectedTimeEnd'] = $this->getDateFieldVerbal($taskRec, 'expectedTimeEnd', true);
                } else{
                    $new[$taskRec->id]['expectedTimeStart'] = ' ';
                    $new[$taskRec->id]['expectedTimeEnd'] = ' ';
                }
            }
        }

        $res = array();
        foreach ($new as $taskId => $taskArr){
            if(!in_array($taskId, $inOrderTasks)) continue;

            // Ще се върнат новите начала на подредените задачи
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => "expectedTimeStart{$taskId}", 'html' => $taskArr['expectedTimeStart'], 'replace' => true);
            $res[] = $resObj;

            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => "expectedTimeEnd{$taskId}", 'html' => $taskArr['expectedTimeEnd'], 'replace' => true);
            $res[] = $resObj;

            $taskRec = $cachedData['tasks'][$taskId];
            $jobTasks = $cachedData['jobs'][$taskRec->originId]['tasks'];
            $filteredKeys = array_keys(array_filter($jobTasks, function($o) use ($taskRec) { return $o->id == $taskRec->id;}));

            // Ако началата на предходните и следващите задачи са преподредени ще се заместят и те в зависимите задачи
            $currentKey = $filteredKeys[0];
            $prevOrder = $currentKey - 1;
            $nextOrder = $currentKey + 1;
            if(isset($jobTasks[$prevOrder])){
                if(array_key_exists($jobTasks[$prevOrder]->id, $new)){
                    $prevEnd = $new[$jobTasks[$prevOrder]->id]['expectedTimeEndPure'];
                    $prevStartHtml = ht::createElement("span", array('data-date' => "{$prevEnd}", 'class' => "prevExpectedTimeEndCol"), dt::mysql2verbal($prevEnd), true)->getContent();

                    $resObj = new stdClass();
                    $resObj->func = 'html';
                    $resObj->arg = array('id' => "prevExpectedTimeEnd{$taskId}", 'html' => $prevStartHtml, 'replace' => true);
                    $res[] = $resObj;
                }
            }

            if(isset($jobTasks[$nextOrder])){
                if(array_key_exists($jobTasks[$nextOrder]->id, $new)){
                    $nextStart = $new[$jobTasks[$nextOrder]->id]['expectedTimeStartPure'];
                    $nextStartHtml = ht::createElement("span", array('data-date' => "{$nextStart}", 'class' => "nextExpectedTimeStartCol"), dt::mysql2verbal($nextStart), true)->getContent();

                    $resObj = new stdClass();
                    $resObj->func = 'html';
                    $resObj->arg = array('id' => "nextExpectedTimeStart{$taskId}", 'html' => $nextStartHtml, 'replace' => true);
                    $res[] = $resObj;
                }
            }
        }

        if($forceReorder){
            uasort($new, function ($a, $b) {
                // Ако няма дата, задаваме максимална стойност
                $timeLeft = empty($a['expectedTimeStartPure']) ? PHP_INT_MAX : strtotime($a['expectedTimeStartPure']);
                $timeRight = empty($b['expectedTimeStartPure']) ? PHP_INT_MAX : strtotime($b['expectedTimeStartPure']);

                // Сортиране по време (възходящ ред)
                if ($timeLeft !== $timeRight) return $timeLeft - $timeRight;

                // Ако датите са еднакви, сортираме по orderByAssetId (като число)
                return intval($a['orderByAssetId']) - intval($b['orderByAssetId']);
            });

            $resObj = new stdClass();
            $resObj->func = 'forceSort';
            $resObj->arg = array('inOrder' => array_keys($new));

            $res[] = $resObj;
        }

        $resObj = new stdClass();
        $resObj->func = 'compareDates';
        $res[] = $resObj;

        // Показване на статусите веднага
        $hitTime = Request::get('hitTime', 'int');
        $idleTime = Request::get('idleTime', 'int');
        $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
        $res = array_merge($res, (array) $statusData);

        return $res;
    }


    /**
     * След подготовка на полетата
     */
    protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        if(!Mode::is('isReorder')) return;

        $data->sortableTable = false;
    }


    /**
     * Екшън за преподреждане на разместените операции
     */
    public function act_savereordertasks()
    {
        $success = true;
        try{
            $this->requireRightFor('savereordertasks');
            $assetId = Request::get('assetId', 'int');
            expect($hash = Request::get('hash'));
            expect(str::checkHash($hash, 6, 'RO'));
        } catch (Exception $e){
            reportException($e);
            $success = false;
        }

        if($success){
            $inOrderTasks = Request::get('orderedTasks', 'varchar');
            $inOrderTasks = json_decode($inOrderTasks);

            $manualTimes = Request::get('manualTimes', 'varchar');
            $manualTimes = json_decode($manualTimes, true);
            $manualTimes = is_array($manualTimes) ? $manualTimes : array('expectedTimeStart' => array(), 'expectedTimeEnd' => array());

            $tasks = array();
            $cachedData = core_Cache::get('planning_Tasks',"reorderAsset{$assetId}");
            foreach ($inOrderTasks as $i => $taskId){
                $tasks[$taskId] = $cachedData['tasks'][$taskId];
            }

            core_Debug::startTimer('TASKS_LIVE_REORDER_TASKS');
            planning_AssetResources::reOrderTasks($assetId, $tasks, true, $manualTimes);
            unset($this->reorderTasksInAssetId[$assetId]);
            core_Debug::stopTimer('TASKS_LIVE_REORDER_TASKS');
            planning_AssetResources::logWrite('Ръчни преподреждане на операциите', $assetId);

            $this->recalcTaskTimes = true;
            $count = countR($tasks);
            core_Statuses::newStatus("Преподредени са|* {$count} |на|* " . planning_AssetResources::getTitleById($assetId));
            core_Cache::remove('planning_Tasks', "reorderAsset{$assetId}");

            $cu = core_Users::getCurrent();
            core_Permanent::remove("folderFilter{$this->className}|{$cu}");
            core_Permanent::remove("isReorderingTasks|{$assetId}|{$cu}");
        } else {
            core_Statuses::newStatus("Нямате права за преподреждане|*!", 'error');
        }

        $res = array();
        $resObj = new stdClass();
        $resObj->func = 'redirect';
        $resObj->arg = array('url' => toUrl(array($this, 'list', 'assetId' => $assetId)));
        $res[] = $resObj;

        // Показване на статусите веднага
        $hitTime = Request::get('hitTime', 'int');
        $idleTime = Request::get('idleTime', 'int');
        $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
        $res = array_merge($res, (array) $statusData);

        return $res;
    }


    /**
     * Екшън за редактиране на забележката на операцията
     */
    public function act_editnotes()
    {
        expect(Request::get('ajax_mode'));
        $this->requireRightFor('edit');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('edit', $rec);
        $notes = Request::get('notes', 'varchar');

        $rec->notes = $notes;
        $this->save_($rec, 'notes');
        $this->logWrite('Промяна на забележка', $rec->id);

        // Показване на статусите веднага
        $res = array();
        $resObj = new stdClass();
        $resObj->func = 'html';
        $resObj->arg = array('id' => "notesHolder{$rec->id}", 'html' => core_Type::getByName('varchar')->toVerbal($notes), 'replace' => true);
        $res[] = $resObj;

        return $res;
    }


    /**
     * Връща Хтмл с потребителите разглеждащи преподреждането на операциите
     *
     * @param int $assetId
     * @return string
     */
    private function getEditWatchHtml($assetId)
    {
        $cu = core_Users::getCurrent();
        core_Permanent::set("isReorderingTasks|{$assetId}|{$cu}", $cu, 5);

        $res  = array();
        $editedCache = core_Permanent::getLikeKey("isReorderingTasks|{$assetId}|");
        foreach ($editedCache as $userId){
            $res[$userId] = "<span class='otherEditors'>" . core_Users::getNick($userId) . "</span>";
        }
        unset($res[$cu]);

        return countR($res) ? implode('', $res) : ' ';
    }


    /**
     * Кои потребители текущо са отворили преподреждането на операциите
     *
     * @return array
     */
    public function act_reorderTaskWatch()
    {
        expect(Request::get('isReorder'));
        expect($assetId = Request::get('assetId', 'varchar'));
        $this->requireRightFor('list');
        $otherEditorsHtml = $this->getEditWatchHtml($assetId);

        $resObj = new stdClass();
        $resObj->func = 'html';
        $resObj->arg = array('id' => "editWatchHolder", 'html' => $otherEditorsHtml, 'replace' => true);

        return array($resObj);
    }


    /**
     * Връща папките на системите, в които може да се пусне сигнала
     *
     * @param stdClass $rec
     * @return array
     */
    public function getIssueSystemFolders_($rec)
    {
        $rec = $this->fetchRec($rec);
        if ($Driver = cat_Products::getDriver($rec->productId)) {
            $pData = $Driver->getProductionData($rec->productId);

            return isset($pData['supportSystemFolderId']) ? array($pData['supportSystemFolderId']) : array();
        }

        return array();
    }


    /**
     * Връща запис с подразбиращи се данни за сигнала
     *
     * @param int $id Кой е пораждащия комит
     * @return stdClass за cal_Tasks
     * @see support_IssueCreateIntf
     */
    public function getDefaultIssueRec_($id)
    {
        $rec = $this->fetchRec($id);

        return (object)array('title' => tr('Към|*: ') . doc_Containers::getDocument($rec->originId)->getTitleById());
    }


    /**
     * Екшън за смяна на оборудването
     */
    public function act_changeAsset()
    {
        $this->requireRightFor('pasteselected');
        $assetId = Request::get('assetId', 'int');
        expect($hash = Request::get('hash'));
        expect(str::checkHash($hash, 6, 'PT'));

        $selectedIds = Request::get('selectedIds');
        $selectedIds = json_decode($selectedIds);
        expect(count($selectedIds));

        // Подготовка на формата за избор на дестинация
        $form = cls::get('core_Form');
        $form->title = "Преместване на избрани операции от|* " . cls::get('planning_AssetResources')->getFormTitleLink($assetId);
        $form->info = "<div class='formCustomInfo'>" . tr('Преместване на') . ":<br>";
        foreach ($selectedIds as $selectId){
            $selectedTaskTitle = $this->getAlternativeTitle($selectId);
            $selectedTaskSingleUrl = $this->getSingleUrlArray($selectId);
            $form->info .= (countR($selectedTaskSingleUrl) ? ht::createLink($selectedTaskTitle, $selectedTaskSingleUrl, false, "ef_icon={$this->getSingleIcon($selectId)}") : $selectedTaskTitle) . "<br>";
        }
        $form->info .= "</div>";
        $form->FLD('newAssetId', 'key(mvc=planning_AssetResources,select=shortName, allowEmpty)', 'caption=Към,mandatory,silent,removeAndRefreshForm=afterId');
        $form->FLD('after', 'int', 'caption=След,placeholder=Първа за оборудването,input=hidden,maxRadio=1');

        // В кои центрове участва избраното оборудване
        $assetClassId = planning_AssetResources::getClassId();
        $aQuery = planning_AssetResourceFolders::getQuery();
        $aQuery->where("#objectId = {$assetId} AND #classId = {$assetClassId}");
        $aQuery->show('folderId');
        $folderIds = arr::extractValuesFromArray($aQuery->fetchAll(), 'folderId');

        // Извличане на другите оборудвания към същите центрове на дейност
        $aQuery1 = planning_AssetResourceFolders::getQuery();
        $aQuery1->where("#classId = {$assetClassId} AND #coverClass = " . planning_Centers::getClassId());
        $aQuery1->EXT('coverClass', 'doc_Folders', 'externalKey=folderId');
        $aQuery1->show('objectId');
        if(countR($folderIds)){
            $aQuery1->in('folderId', $folderIds);
        } else {
            $aQuery1->where("1=2");
        }

        // Групиране на оборудванията в секции от същите или от други центрове на дейност
        $options = array();
        $assetsInSameFolder = arr::extractValuesFromArray($aQuery1->fetchAll(), 'objectId');
        foreach($assetsInSameFolder as $aId){
            $options[$aId] = planning_AssetResources::getTitleById($aId);
        }
        $form->setOptions('newAssetId', array('' => '') + $options);
        $form->input(null, 'silent');
        $form->input();

        $rec = $form->rec;
        if(isset($rec->newAssetId)){
            $form->setField('after', 'input');
            $tasksInAssetArr = planning_AssetResources::getAssetTaskOptions($rec->newAssetId, false, 'ASC',true);
            $form->setOptions('after', array('' => '') + $tasksInAssetArr);
        }

        if($form->isSubmitted()){

            $tQuery = static::getQuery();
            $tQuery->in('id', $selectedIds);
            $tQuery->show('folderId,productId,assetId');
            $taskFullArr = $tQuery->fetchAll();
            $refTaskRec = !empty($rec->after) ? planning_Tasks::fetch($rec->after) : null;

            // От избраните ПО се проверява, кои могат да се поставят след посочената
            $tasksToMove = $tasksNotToMove = $errorTaskMoves = array();
            array_walk($taskFullArr, function($a) use($refTaskRec, $rec, &$tasksToMove, &$tasksNotToMove){
                $allowedAssetArr = array();
                if($Driver = cat_Products::getDriver($a->productId)) {
                    $productionData = $Driver->getProductionData($a->productId);
                    if (is_array($productionData['fixedAssets'])) {
                        $allowedAssetArr = $productionData['fixedAssets'];
                    }
                }

                // Трябва да са в същия ЦД и машината на операцията да е от позволените
                $allowedAssetArr = countR($allowedAssetArr) ? $allowedAssetArr : array_keys(planning_AssetResources::getByFolderId($a->folderId, $a->assetId, 'planning_Tasks', true));

                if(!is_object($refTaskRec)){
                    if(in_array($rec->newAssetId, $allowedAssetArr)){
                        $tasksToMove[$a->id] = $a;
                    }
                } elseif($refTaskRec->folderId == $a->folderId && in_array($refTaskRec->assetId, $allowedAssetArr) && $refTaskRec->id != $a->id) {
                    $tasksToMove[$a->id] = $a;
                }

                if(!array_key_exists($a->id, $tasksToMove)){
                    $tasksNotToMove[$a->id] = "#" . $this->getHandle($a->id);
                }
            });

            $movedArr = array();
            $tasksByNow = $tasksToInsert = array();
            array_walk($tasksInAssetArr, function($a, $k) use (&$tasksByNow) {$tasksByNow[$k] = (object)array('id' => $k);});
            array_walk($tasksToMove, function($a, $k) use (&$tasksToInsert) {$tasksToInsert[$k] = (object)array('id' => $k);});

            $keys = array_keys($tasksByNow);
            $pos = array_search($rec->after, $keys, true);

            if ($pos === false) {
                // Ключът не е намерен, вмъкваме в началото
                $newOrdered = $tasksToInsert + $tasksByNow;
            } else {
                // Ключът е намерен, вмъкваме след него
                $before = array_slice($tasksByNow, 0, $pos+1, true);
                $after = array_slice($tasksByNow, $pos+1, null, true);
                $newOrdered = $before + $tasksToInsert + $after;
            }

            foreach ($tasksToMove as $tRec){
                $tRec->prevAssetId = $tRec->assetId;
                $tRec->assetId = $rec->newAssetId;
                $tRec->modifiedOn = dt::now();
                $tRec->modifiedBy = core_Users::getCurrent();

                $this->save_($tRec, 'assetId,prevAssetId,modifiedBy,modifiedOn');
                $this->logWrite("Преместване на друго оборудване", $tRec->id);
                $movedArr[] = "#" . $this->getHandle($tRec->id);
            }

            planning_AssetResources::reOrderTasks($rec->newAssetId, $newOrdered, true);
            planning_AssetResources::reOrderTasks($assetId);

            if(countR($movedArr)){
                $msgPart = isset($rec->after) ? "|са преместени след|* #{$this->getHandle($rec->after)}" : "са премести като първи за оборудването";
                $implodedMoved = implode(', ', $movedArr);
                core_Statuses::newStatus("Операциите|*: {$implodedMoved} {$msgPart}", 'notice', null, 180);
            }

            if(countR($tasksNotToMove)){
                $implodedNotMoved = implode(', ', $tasksNotToMove);
                core_Statuses::newStatus("Следните операции не могат да се преместят след избраната|*: {$implodedNotMoved}", 'warning', null, 180);
            }

            if(countR($errorTaskMoves)){
                $implodedErrorMoved = implode(', ', $errorTaskMoves);
                core_Statuses::newStatus("Имаше проблем при преместването на следните операции|*: {$implodedErrorMoved}", 'error', null, 180);
            }

            if($form->cmd == 'saveAndRed'){
                $nUrl = getRetUrl();
                $nUrl['assetId'] = $rec->newAssetId;
                unset($nUrl['ret_url']);

                redirect($nUrl, false, 'Отворено е новото оборудване');
            }

            followRetUrl();
        }

        $form->toolbar->addSbBtn('Премести', 'save', 'ef_icon = img/16/move.png, title=Преместване и оставане в текущото оборудване');
        $form->toolbar->addSbBtn('Премести и отиди', 'saveAndRed', 'ef_icon = img/16/move.png, title=Преместване и отваряне на друго оборудване');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        // Рендиране на опаковката
        $tpl = $this->renderWrapping($form->renderHtml());

        return $tpl;
    }
}
