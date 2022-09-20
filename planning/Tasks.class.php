<?php


/**
 * Мениджър на Производствени операции
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
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
     * Може ли подредбата да има поднива
     *
     * @see plg_StructureAndOrder
     */
    public $canHaveSubLevel = false;


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
    public $loadList = 'doc_plg_Prototype, doc_DocumentPlg, plg_RowTools2, planning_plg_StateManager, plg_Sorting, plg_StructureAndOrder, planning_Wrapper, acc_plg_DocumentSummary, plg_Search, plg_Clone, plg_Printing, plg_RefreshRows, plg_LastUsedKeys, bgerp_plg_Blank';


    /**
     * На колко време да се рефрешва лист изгледа
     */
    public $refreshRowsTime = 3000;


    /**
     * Дали автоматично да се филтрира по полето за подредба
     */
    public $autoOrderBySaoOrder = false;


    /**
     * Заглавие
     */
    public $title = 'Производствени операции';
    
    
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
    public $listFields = 'expectedTimeStart=Начало,title,progress,dependantProgress=Предх.Оп.,folderId,originId=@';
    
    
    /**
     * Поле за търсене по потребител
     */
    public $filterFieldUsers = false;
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, taskWorker';


    /**
     * Кой може да го добавя?
     */
    public $canAdd = 'ceo, taskPlanning';


    /**
     * Кой може да ги създава от задания?
     */
    public $canCreatejobtasks = 'ceo, taskPlanning';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,taskWorker';


    /**
     * Кой може да преизчислява заработките на прогреса на операцията?
     */
    public $canRecalcindtime = 'ceo,planningMaster';


    /**
     * Кой може да го активира?
     */
    public $canActivate = 'ceo, taskPlanning';


    /**
     * Кой може да го активира?
     */
    public $canChangestate = 'ceo, taskWorker';
    
    
    /**
     * Кой може да го редактира?
     */
    public $canEdit = 'ceo, taskPlanning';


    /**
     * Кой може да го прави на заявка?
     */
    public $canPending = 'ceo, taskPlanning';


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
    public $filterDateField = 'expectedTimeStart,activatedOn,createdOn,dueDate';
    
    
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
    public $cloneDetails = 'planning_ProductionTaskProducts,cat_products_Params';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'progress,totalWeight,totalNetWeight,scrappedQuantity,producedQuantity,totalQuantity,plannedQuantity,timeStart,timeEnd,timeDuration,systemId,orderByAssetId,prevAssetId,expectedTimeStart,expectedTimeEnd';
    
    
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
     * Работен кеш
     */
    public $changedAssets = array();


    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 20;


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'dependantProgress';


    /**
     * Да се показват ли бъдещи периоди в лист изгледа
     */
    public $filterFutureOptions = true;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие,width=100%,silent,input=hidden');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=planning_Steps::getSelectableSteps,allowEmpty,forceAjax,forceOpen)', 'mandatory,class=w100,caption=Етап,removeAndRefreshForm=packagingId|measureId|quantityInPack|paramcat|plannedQuantity|indPackagingId|storeId|assetId|employees|labelPackagingId|labelQuantityInPack|labelType|labelTemplate|indTime|isFinal|paramcat|isFinal|wasteProductId|wasteStart|wastePercent,silent');
        $this->FLD('measureId', 'key(mvc=cat_UoM,select=name,select=shortName)', 'mandatory,caption=Мярка,removeAndRefreshForm=quantityInPack|plannedQuantity|labelPackagingId|indPackagingId,silent,input=hidden');
        $this->FLD('totalWeight', 'cat_type_Weight(smartRound=no)', 'caption=Общо Бруто,input=none');
        $this->FLD('totalNetWeight', 'cat_type_Weight(smartRound=no)', 'caption=Общо Нето,input=none');
        $this->FLD('plannedQuantity', 'double(smartRound,Min=0)', 'mandatory,caption=Планирано');
        $this->FLD('isFinal', 'enum(yes=Да,no=Не)', 'input=hidden,caption=Финална,silent');
        $this->FLD('quantityInPack', 'double', 'mandatory,caption=К-во в мярка,input=none');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,input=none');
        $this->FLD('assetId', 'key(mvc=planning_AssetResources,select=name)', 'caption=Оборудване,silent,removeAndRefreshForm=orderByAssetId|startAfter|freeTimeAfter|simultaneity');
        $this->FLD('simultaneity', 'double(min=0)', 'caption=Едновременност,input=hidden');
        $this->FLD('prevAssetId', 'key(mvc=planning_AssetResources,select=name)', 'caption=Оборудване (Старо),input=none');
        $this->FLD('employees', 'keylist(mvc=crm_Persons,select=id,makeLinks,select2MinItems=0)', 'caption=Оператори,silent');
        $this->FNC('startAfter', 'varchar', 'caption=Започва след,silent,placeholder=Първа');
        $this->FLD('showadditionalUom', 'enum(no=Изключено,yes=Включено,mandatory=Задължително)', 'caption=Отчитане на тегло,notNull,value=yes,autohide');
        if(core_Packs::isInstalled('batch')){
            $this->FLD('followBatchesForFinalProduct', 'enum(yes=На производство по партида,no=Без отчитане)', 'caption=Отчитане,input=none');
        }        
        $this->FLD('indPackagingId', 'key(mvc=cat_UoM,select=name)', 'silent,class=w25,removeAndRefreshForm,caption=Нормиране->Мярка,input=hidden,tdClass=small-field nowrap');
        $this->FLD('indTimeAllocation', 'enum(common=Общо,individual=Поотделно)', 'caption=Нормиране->Разпределяне,smartCenter,notNull,value=individual');
        $this->FLD('indTime', 'planning_type_ProductionRate', 'caption=Нормиране->Норма,smartCenter');
        $this->FLD('labelPackagingId', 'key(mvc=cat_UoM,select=name)', 'caption=Етикиране->Опаковка,input=hidden,tdClass=small-field nowrap,placeholder=Няма,silent,removeAndRefreshForm=labelQuantityInPack|labelTemplate,oldFieldName=packagingId');
        $this->FLD('labelQuantityInPack', 'double(smartRound,Min=0)', 'caption=Етикиране->В опаковка,tdClass=small-field nowrap,input=hidden,oldFieldName=packagingQuantityInPack');
        $this->FLD('labelType', 'enum(print=Отпечатване,scan=Сканиране,both=Сканиране и отпечатване)', 'caption=Етикиране->Етикет,tdClass=small-field nowrap,notNull,value=both,input=hidden');
        $this->FLD('labelTemplate', 'key(mvc=label_Templates,select=title)', 'caption=Етикиране->Шаблон,tdClass=small-field nowrap,input=hidden');
        $this->FLD('timeStart', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00,format=smartTime)', 'caption=Целеви времена->Начало, changable, tdClass=leftColImportant');
        $this->FLD('timeDuration', 'time', 'caption=Целеви времена->Продължителност,changable');
        $this->FLD('calcedDuration', 'time', 'caption=Целеви времена->Нетна продължителност,input=none');
        $this->FLD('timeEnd', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00,format=smartTime)', 'caption=Целеви времена->Край,changable, tdClass=leftColImportant,formOrder=103');
        $this->FLD('wasteProductId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax)', 'caption=Отпадък->Артикул,silent,class=w100,removeAndRefreshForm=wasteStart|wastePercent,autohide');
        $this->FLD('wasteStart', 'double(smartRound)', 'caption=Отпадък->Начален,autohide');
        $this->FLD('wastePercent', 'percent(Min=0)', 'caption=Отпадък->Допустим,autohide');
        $this->FLD('expectedTimeStart', 'datetime', 'caption=Планирани времена->Начало,input=none,tdClass=leftCol');
        $this->FLD('expectedTimeEnd', 'datetime', 'caption=Планирани времена->Край,input=none');

        $this->FLD('totalQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Количество,after=labelPackagingId,input=none');
        $this->FLD('scrappedQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Брак,input=none');
        $this->FLD('producedQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Заскладено,input=none');

        $this->FLD('progress', 'percent', 'caption=Прогрес,input=none,notNull,value=0');
        $this->FLD('systemId', 'int', 'silent,input=hidden');
        $this->FLD('subTitle', 'varchar(20)', 'caption=Допълнително->Подзаглавие,width=100%,recently');
        $this->FLD('description', 'richtext(rows=2,bucket=Notes,passage)', 'caption=Допълнително->Описание,autoHide');
        $this->FLD('orderByAssetId', 'double(smartRound)', 'silent,input=hidden,caption=Подредба,smartCenter');

        $this->FLD('prevErrId', 'key(mvc=planning_Tasks,select=title)', 'input=none,caption=Предишна грешка');
        $this->FLD('nextErrId', 'key(mvc=planning_Tasks,select=title)', 'input=none,caption=Следваща грешка');
        $this->FLD('freeTimeAfter', 'enum(yes,no)', 'input=none,notNull,value=no');

        $this->setDbIndex('productId');
        $this->setDbIndex('assetId,orderByAssetId');
        $this->setDbIndex('assetId');
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

        if($Driver = cat_Products::getDriver($data->rec->productId)){
            $pData = $Driver->getProductionData($data->rec->productId);
            $in = $pData['planningParams'];
            if(!countR($in)){
                unset($data->paramData->addUrl);
            }
            if($pData['showPreviousJobField']){
                $originRec = doc_Containers::getDocument($data->rec->originId)->fetch('oldJobId,productId');
                if($originRec->oldJobId){
                    $oldJobProductId = planning_Jobs::fetchField($originRec->oldJobId, 'productId');
                    $data->row->previousJob = planning_Jobs::getHyperlink($originRec->oldJobId, true);
                    $data->row->previousJobCaption = ($originRec->productId == $oldJobProductId) ? tr('Предходно') : tr('Подобно');
                }
            }
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
     * @param core_ET  $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if (isset($data->paramData)) {
            $paramTpl = cat_products_Params::renderParams($data->paramData);
            $tpl->append($paramTpl, 'PARAMS');
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
        if($Driver = cat_Products::getDriver($productId)){
            $pData = $Driver->getProductionData($productId);
            if(!empty($pData['name'])) return $pData['name'];
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
        $row = parent::recToVerbal_($rec, $fields);
        $mvc = cls::get(get_called_class());
        $row->title = self::getHyperlink($rec->id, isset($fields['-list']));

        $red = new color_Object('#FF0000');
        $blue = new color_Object('green');
        $grey = new color_Object('#bbb');

        $progressPx = min(200, round(200 * $rec->progress));
        $progressRemainPx = 200 - $progressPx;

        $color = ($rec->progress <= 1) ? $blue : $red;
        $row->progressBar = "<div style='white-space: nowrap; display: inline-block;'><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$color}; width:{$progressPx}px;'> </div><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$grey};width:{$progressRemainPx}px;'></div></div>";
        $grey->setGradient($color, $rec->progress);

        $origin = doc_Containers::getDocument($rec->originId);
        $row->folderId = doc_Folders::getFolderTitle($rec->folderId);

        $row->productId = $mvc->getStepTitle($rec->productId);
        if(!empty($rec->subTitle)){
            $row->productId .= ", <i>{$mvc->getFieldType('subTitle')->toVerbal($rec->subTitle)}</i>";
        }

        if(!Mode::isReadOnly()){
            $row->productId = ht::createLink($row->productId, cat_Products::getSingleUrlArray($rec->productId));
        }

        foreach (array('plannedQuantity', 'totalQuantity', 'scrappedQuantity', 'producedQuantity') as $quantityFld) {
            $row->{$quantityFld} = ($rec->{$quantityFld}) ? $row->{$quantityFld} : 0;
            $row->{$quantityFld} = ht::styleNumber($row->{$quantityFld}, $rec->{$quantityFld});
        }

        if (isset($rec->storeId)) {
            $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        }

        if(in_array($rec->state, array('closed', 'rejected'))){
            $row->expectedTimeStart = "<i class = 'quiet'>{$row->state}</i>";
            $row->expectedTimeEnd = "<i class = 'quiet'>{$row->state}</i>";
        } else {
            // Проверяване на времената
            foreach (array('expectedTimeStart' => 'timeStart', 'expectedTimeEnd' => 'timeEnd') as $eTimeField => $timeField) {

                // Вербализиране на времената
                $DateTime = core_Type::getByName('datetime(format=smartTime)');
                $row->{$eTimeField} = '<span class=quiet>N/A</span>';
                if(!empty($rec->{$eTimeField})){
                    $row->{$eTimeField} = $DateTime->toVerbal($rec->{$eTimeField});
                    if($eTimeField == 'expectedTimeStart'){
                        $now = dt::now();
                        if(in_array($rec->state, array('wakeup', 'stopped', 'active'))){
                            if($rec->expectedTimeEnd < $now){
                                $row->expectedTimeStart = ht::createHint("<span class='red'>{$row->expectedTimeStart}</span>", 'Планираният край е в миналото', 'warning');
                            }
                        }
                    }
                }

                if($rec->{$timeField}){
                    $row->{$timeField} = $DateTime->toVerbal($rec->{$timeField});
                }

                $hint = null;
                if(!empty($rec->{$timeField})){
                    $hint = "Зададено|*: {$row->{$timeField}}";

                    if(!empty($rec->{$eTimeField})){
                        // Колко е разликата в минути между тях?
                        $diff = dt::secsBetween($rec->{$eTimeField}, $rec->{$timeField});
                        if ($diff != 0) {
                            $diffVerbal = cls::get('type_Time')->toVerbal($diff);
                            $diffVerbal = ($diff > 0) ? "+{$diffVerbal}" : $diffVerbal;
                            $hint .= " ({$diffVerbal})";
                        }
                    }
                }

                if(isset($hint)){
                    $row->{$eTimeField} = ht::createHint($row->{$eTimeField}, $hint, 'notice', true, array('height' => '12', 'width' => '12'));
                }
            }

            if(!empty($rec->prevErrId)){
                $row->expectedTimeStart = ht::createHint($row->expectedTimeStart, "Има проблем с предходната операция #{$mvc->getHandle($rec->prevErrId)}", 'img/16/red-warning.png', false);
            }

            if(!empty($rec->nextErrId)){
                $row->expectedTimeStart = ht::createHint($row->expectedTimeStart, "Има проблем със следващата операция #{$mvc->getHandle($rec->nextErrId)}", 'img/16/red-warning.png');
            }

            if($rec->freeTimeAfter == 'yes'){
                $row->expectedTimeStart = ht::createHint($row->expectedTimeStart, "Има свободно време между края на тази операция и началото на следващата|*!", 'warning');
            }

            if(!empty($rec->expectedTimeEnd) && $rec->expectedTimeEnd >= ("{$origin->fetchField('dueDate')} 23:59:59")){
                $useField = isset($fields['-list']) ? 'expectedTimeStart' : 'expectedTimeEnd';
                $row->{$useField} = ht::createHint($row->{$useField}, "Планирания край е след падежа на заданието|*!", 'img/16/red-warning.png');
            }

            $expectedDuration = dt::secsBetween($rec->expectedTimeEnd, $rec->expectedTimeStart);
            $durationUom = ($expectedDuration < 60) ? 'seconds' : (($expectedDuration < 3600) ? 'minutes' : 'hours');
            $row->expectedDuration = empty($expectedDuration) ? '<span class=quiet>N/A</span>' : core_Type::getByName("time(uom={$durationUom},noSmart)")->toVerbal($expectedDuration);
        }

        $calcedDurationUom = ($rec->calcedDuration < 60) ? 'seconds' : (($rec->calcedDuration < 3600) ? 'minutes' : 'hours');
        $row->calcedDuration = empty($calcedDurationUom) ? '<span class=quiet>N/A</span>' : core_Type::getByName("time(uom={$calcedDurationUom},noSmart)")->toVerbal($rec->calcedDuration);

        // Показване на разширеното описание на артикула
        if (isset($fields['-single'])) {
            $dependentTasks = planning_StepConditions::getDependantTasksProgress($rec, true);
            if(is_array($dependentTasks[$rec->id])){
                $row->dependantProgress = implode("", $dependentTasks[$rec->id]);
            }

            if(isset($rec->assetId)){
                if(planning_AssetResources::haveRightFor('recalctime', (object)array('id' => $rec->assetId)) && !Mode::is('printing')){
                    if(!in_array($rec->state, array('draft', 'waiting', 'rejected'))){
                        $row->recalcBtn = ht::createLink('', array('planning_AssetResources', 'recalcTimes', $rec->assetId, 'ret_url' => true), false, 'ef_icon=img/16/arrow_refresh.png, title=Преизчисляване на времената на операциите към оборудването');
                    }
                }
            }

            if(!Mode::is('printing')){
                $row->toggleBtn = "<a href=\"javascript:toggleDisplay('{$rec->id}inf')\"  style=\"background-image:url(" . sbf('img/16/toggle1.png', "'") . ');" class=" plus-icon more-btn"> </a>';
                $row->productDescriptionStyle = 'display: none;';
            }

            $jobProductId = planning_Jobs::fetchField("#containerId = {$rec->originId}", 'productId');
            $row->productDescription = cat_Products::getAutoProductDesc($jobProductId, null, 'detailed', 'job');
            $row->tId = $rec->id;

            if(core_Packs::isInstalled('batch')){
                if($BatchDef = batch_Defs::getBatchDef($rec->productId)){
                    if($BatchDef instanceof batch_definitions_Job){
                        $row->batch = $BatchDef->getDefaultBatchName($origin->that);
                    }
                }
            }

            if(empty($rec->labelPackagingId)){
                $row->labelPackagingId = "<span class='quiet'>N/A</span>";
                $row->labelQuantityInPack = "<span class='quiet'>N/A</span>";
            } else {
                if(empty($rec->labelQuantityInPack)){
                    $labelProductId = ($rec->isFinal == 'yes') ? $origin->fetchField('productId') : $rec->productId;
                    $quantityInPackDefault = static::getDefaultQuantityInLabelPackagingId($labelProductId, $rec->measureId, $rec->labelPackagingId);
                    $quantityInPackDefault = "<span style='color:blue'>" . core_Type::getByName('double(smartRound)')->toVerbal($quantityInPackDefault) . "</span>";
                    $quantityInPackDefault = ht::createHint($quantityInPackDefault, 'От опаковката/мярката на артикула');
                    $row->labelQuantityInPack = $quantityInPackDefault;
                } else {
                    $row->labelQuantityInPack .= " {$row->measureId}";
                }
            }

            $row->labelTemplate = (isset($rec->labelTemplate)) ? ht::createHint(ht::createLink("№{$rec->labelTemplate}", label_Templates::getSingleUrlArray($rec->labelTemplate)), label_Templates::getTitleById($rec->labelTemplate)) : "<span class='quiet'>N/A</span>";

            // Линк към отпечаванията ако има
            if(label_Prints::haveRightFor('list')){
                if($printCount = label_Prints::count("#classId = {$mvc->getClassId()} AND #objectId = {$rec->id}")){
                    $row->printCount = core_Type::getByName('int')->toVerbal($printCount);
                    $row->printCount = ht::createLink($row->printCount, array('label_Prints', 'list', 'classId' => $mvc->getClassId(), 'objectId' => $rec->id, 'ret_url' => true));
                }
            }

            if($rec->isFinal == 'yes'){
                $row->productCaption = tr('Финален етап');
                $row->originProductId = cat_Products::getHyperlink($origin->fetchField('productId'), true);
            } else {
                $row->productCaption = tr('Етап');
                unset($row->isFinal);
            }

            $row->originId = $origin->getHyperlink(true);
            if(isset($rec->wasteProductId)){
                $row->wasteProductId = cat_Products::getHyperlink($rec->wasteProductId, true);
                $row->wasteStart = isset($row->wasteStart) ? $row->wasteStart : 'n/a';
                $row->wastePercent = isset($row->wastePercent) ? $row->wastePercent : 'n/a';
                $row->wasteProductId = ht::createHint($row->wasteProductId, "Начален|*: {$row->wasteStart}, |Допустим|*: {$row->wastePercent}");
            }

            if(!empty($rec->employees)){
                $employees = planning_Hr::getPersonsCodesArr($rec->employees, true);
                $row->employees = implode(', ', $employees);
            }
        } else {
            if($mvc->haveRightFor('copy2clipboard', $rec)){
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $copyUrl = toUrl(array($mvc, 'copy2clipboard', $rec->id, 'ret_url' => true), 'local');
                $row->_rowTools->addLink('Избор', '', "ef_icon=img/16/copy16.png,title=Запомняне на операцията в клипборда,data-url={$copyUrl},class=copy2clipboard");
            }

            // Ако може да се пейства операция от клипборда
            $rememberedTaskRec = Mode::get('rememberedTask');
            if(is_object($rememberedTaskRec)){

                // Ако има предишна операция, ще може да се поставя след нея
                if(!$mvc->getPrevOrNextTask($rec)){
                    if($mvc->haveRightFor('pastefromclipboard', (object)array('refTaskId' => $rec->id, 'place' => 'before'))){
                        core_RowToolbar::createIfNotExists($row->_rowTools);
                        $pasteUrl = toUrl(array($mvc, 'pastefromclipboard', 'refTaskId' => $rec->id, 'place' => 'before', 'ret_url' => true), 'local');
                        $row->_rowTools->addLink("Постави преди", '', "ef_icon=img/16/paste_plain.png,title=Поставяне на|* #{$mvc->getHandle($rememberedTaskRec->id)} |преди|* #{$mvc->getHandle($rec->id)},data-url={$pasteUrl},class=copy2clipboard");
                    }
                }

                if($rememberedTaskRec->id != $rec->taskId){
                    if($mvc->haveRightFor('pastefromclipboard', (object)array('refTaskId' => $rec->id, 'place' => 'after'))){
                        core_RowToolbar::createIfNotExists($row->_rowTools);
                        $pasteUrl = toUrl(array($mvc, 'pastefromclipboard', 'refTaskId' => $rec->id, 'place' => 'after', 'ret_url' => true), 'local');
                        $row->_rowTools->addLink("Постави след", '', "ef_icon=img/16/paste_plain.png,title=Поставяне на|* #{$mvc->getHandle($rememberedTaskRec->id)} |след|* #{$mvc->getHandle($rec->id)},data-url={$pasteUrl},class=copy2clipboard");
                    }
                }
            }

            $row->dueDate = $origin->getVerbal('dueDate');
            $jobPackQuantity = $origin->fetchField('packQuantity');
            $quantityStr = core_Type::getByName('double(smartRound)')->toVerbal($jobPackQuantity) . " " . cat_UoM::getSmartName($origin->fetchField('packagingId'), $jobPackQuantity);
            $row->originId = tr("|*<small> <span class='quiet'>|падеж|* </span>{$row->dueDate} <span class='quiet'>|по|*</span> {$origin->getShortHyperlink()}, <span class='quiet'>|к-во|*</span> {$quantityStr}</small>");
        }

        if(empty($rec->indTime)){
            $row->indTime = "<span class='quiet'>N/A</span>";
        }

        // Ако има избрано оборудване
        if(isset($rec->assetId)){
            $hintSimultaneity = false;
            $assetSimultaneity = $rec->simultaneity;
            if(!isset($assetSimultaneity)){
                $assetSimultaneity = planning_AssetResources::fetchField($rec->assetId, 'simultaneity');
                $hintSimultaneity = true;
            }
            $row->simultaneity = core_Type::getByName('int')->toVerbal($assetSimultaneity);
            if($hintSimultaneity){
                $row->simultaneity = ht::createHint("<span style='color:blue'>{$row->simultaneity}</span>", 'Зададено е в оборудването');
            }

            $row->assetId = planning_AssetResources::getHyperlink($rec->assetId, true);
            if(planning_Tasks::haveRightFor('list') && !Mode::is('printing')){
                $row->assetId->append(ht::createLink('', array('planning_Tasks', 'list', 'folder' => $rec->folderId, 'assetId' => $rec->assetId), false, 'ef_icon=img/16/funnel.png,title=Филтър по център на дейност и оборудване'));
            }
            if(isset($fields['-single']) && isset($rec->prevAssetId)){
                $row->assetId = ht::createHint($row->assetId, "Предишно оборудване|*: " . planning_AssetResources::getTitleById($rec->prevAssetId), 'warning', false);
            }

            if(haveRole('debug')){
                $row->orderByAssetId = isset($rec->orderByAssetId) ? $row->orderByAssetId : 'n/a';
                $row->assetId = ht::createHint($row->assetId, "Подредба|*: {$row->orderByAssetId}", 'img/16/bug.png');
            }

            if(isset($fields['-single']) && !in_array($rec->state, array('closed', 'rejected'))){

                // Показва се след коя ще започне
                $startAfter = $mvc->getPrevOrNextTask($rec);
                if(isset($startAfter)){
                    $row->startAfter = $mvc->getHyperlink($startAfter, true);
                } else {
                    $row->startAfter = tr('Първа за оборудването');
                }
            }
        } else {
            $row->assetId = "<span class='quiet'>N/A</span>";
            $row->assetId = ht::createHint($row->assetId, 'Операцията няма да може да стане заявка/да бъде активирана, докато няма избрано оборудване|*!', 'warning');
        }

        $canStore = cat_products::fetchField($rec->productId, 'canStore');
        $row->producedCaption = ($canStore == 'yes') ? tr('Заскладено') : tr('Изпълнено');
        $row->progress = (isset($fields['-list']) && empty($rec->progress)) ? ("<i>" . $mvc->getFieldType('plannedQuantity')->toVerbal($rec->plannedQuantity) . " " . cat_UoM::getShortName($rec->measureId) . "</i>") : "<span style='color:{$grey};'>{$row->progress}</span>";

        return $row;
    }


    /**
     * Какво е дефолтното количество в опаковката за етикетиране
     *
     * @param int $productId
     * @param int $measureId
     * @param int $labelPackagingId
     * @return float|int $quantityInPackDefault
     */
    public static function getDefaultQuantityInLabelPackagingId($productId, $measureId, $labelPackagingId)
    {
        $packRec = cat_products_Packagings::getPack($productId, $labelPackagingId);
        $quantityInPackDefault = is_object($packRec) ? $packRec->quantity : 1;
        $productMeasureId = cat_Products::fetchField($productId, 'measureId');

        if($productMeasureId != $measureId){
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
        $me =  cls::get(get_called_class());
        $title = "Opr{$rec->id} - {$me->getStepTitle($rec->productId)}";
        if(!empty($rec->subTitle)){
            $title .= ", <i>{$me->getFieldType('subTitle')->toVerbal($rec->subTitle)}</i>";
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
            if($rec->isFinal == 'yes'){
                $productId = doc_Containers::getDocument($rec->originId)->fetchField('productId');
                if($otherTaskId = planning_Tasks::fetchField("#originId = {$rec->originId} AND #state != 'rejected' AND #isFinal = 'yes' AND #productId != {$rec->productId}")) {
                    $otherTaskLink = planning_Tasks::getHyperlink($otherTaskId, true);
                    $form->setError('productId', "По заданието вече има операция за друг финален етап|*: {$otherTaskLink}");
                }
            }

            $packRec = cat_products_Packagings::getPack($productId, $rec->measureId);
            $rec->quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
            $rec->title = cat_Products::getTitleById($rec->productId);

            if(in_array($form->cmd, array('save_pending', 'save_pending_new'))){
                if(empty($rec->indTime) && empty($rec->timeDuration)){
                    $form->setError('timeDuration,indTime', "На операцията трябва да може да ѝ се изчисли продължителността|*!");
                }
            }

            if(in_array($rec->state, array('active', 'wakeup', 'stopped'))){
                if(empty($rec->timeDuration) && empty($rec->assetId)){
                    $form->setError('timeDuration,assetId,indTime', "На започната операция, не може да се махне продължителността/нормата или оборудването|*!");
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
            if($whenToUnsetStartAfter){
                $form->setWarning('startAfter', "Операцията е чернова. Автоматично ще се добави последна към избраното оборудване|*!");
            }

            if(!$form->rec->_editActive){
                if(isset($rec->wasteProductId)){
                    $wasteRec = cat_Products::fetch($rec->wasteProductId, 'measureId,generic');
                    if($wasteRec->generic == 'yes'){
                        $form->setError('wasteProductId', "Избраният отпадък е генеричен (обобщаващ)|*! |Трябва да бъде заместен с конкретния такъв|*!");
                    }

                    if(($rec->wasteStart + $rec->wastePercent) <= 0){
                        $form->setError('wasteStart,wastePercent', "Количеството на отпадъка не може да се сметне|*!");
                    }
                } else {
                    if(isset($rec->wasteStart) || isset($rec->wastePercent)){
                        $form->setError('wasteProductId,wasteStart,wastePercent', "Не е посочен отпадък|*!");
                    }
                }
            }

            if(!$form->gotErrors()){
                $rec->_fromForm = true;

                // Ако не е въведено точен час се добавя началото на работното време на машината
                if(!empty($rec->timeStart) && isset($rec->assetId)){
                    if (strpos($rec->timeStart, ' 00:00:00') !== false) {
                        if($scheduleId = planning_AssetResources::getScheduleId($rec->assetId)){
                            $timeStartDate = dt::verbal2mysql($rec->timeStart, false);
                            $startTimes = hr_Schedules::getStartingTimes($scheduleId, $timeStartDate, $timeStartDate);
                            if(isset($startTimes[$timeStartDate])){
                                $rec->timeStart = str_replace(' 00:00:00', " {$startTimes[$timeStartDate]}", $rec->timeStart);
                            }
                        }
                    }
                }

                if($whenToUnsetStartAfter){
                    $rec->startAfter = null;
                }
            }
        }
    }


    /**
     * Добавя допълнителни полетата в антетката
     *
     * @param core_Master $mvc
     * @param NULL|array  $resArr
     * @param object      $rec
     * @param object      $row
     */
    protected static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
        unset($resArr['ident']);
        unset($resArr['versionAndDate']);
        unset($resArr['createdBy']);
        unset($resArr['createdOn']);

        if(Mode::is('printing')){
            $resArr['info'] = array('name' => tr('Операция'), 'val' => tr("|*<table>
                <tr><td style='font-weight:normal'>№:</td><td>[#ident#]</td></tr>
                <tr><td style='font-weight:normal'>|Създаване от|*:</td><td>[#createdBy#]</td></tr>
                <tr><td style='font-weight:normal'>|Създаване на|*:</td><td>[#createdOn#]</td></tr>
                </table>"));
        }

        if($rec->showadditionalUom == 'no'){
            unset($row->totalWeight);
            unset($row->totalNetWeight);
            unset($row->totalNetWeight);
        } else {
            $centerRec = planning_Centers::fetch("#folderId = {$rec->folderId}", 'useTareFromParamId,useTareFromPackagings');
            $row->totalWeight = empty($rec->totalWeight) ? "<span class='quiet'>N/A</span>" : $row->totalWeight;
            if(empty($centerRec->useTareFromParamId) && empty($centerRec->useTareFromPackagings)) {
                unset($row->totalNetWeight);
            } else {
                $row->totalNetWeight = empty($rec->totalNetWeight) ? "<span class='quiet'>N/A</span>" : $row->totalNetWeight;
            }
        }

        $canStore = cat_Products::fetchField($rec->productId, 'canStore');
        if($canStore == 'yes'){
            $resArr['additional'] = array('name' => tr('Изчисляване на тегло'), 'val' => tr("|*<table>
                <!--ET_BEGIN totalWeight--><tr><td style='font-weight:normal'>|Общо бруто|*:</td><td>[#totalWeight#]</td></tr><!--ET_END totalWeight-->
                <!--ET_BEGIN totalNetWeight--><tr><td style='font-weight:normal'>|Общо нето|*:</td><td>[#totalNetWeight#]</td></tr><!--ET_END totalNetWeight-->
                <tr><td style='font-weight:normal'>|Режим|*:</td><td>[#showadditionalUom#]</td></tr>
                </table>"));
        }

        $resArr['labels'] = array('name' => tr('Етикетиране'), 'val' => tr("|*<table>
                <tr><td style='font-weight:normal'>|Производ. №|*:</td><td>[#labelType#]</td></tr>
                <tr><td style='font-weight:normal'>|Опаковка|*:</td><td>[#labelPackagingId#]</td></tr>
                <tr><td style='font-weight:normal'>|В опаковка|*:</td><td>[#labelQuantityInPack#]</td></tr>
                <tr><td style='font-weight:normal'>|Шаблон|*:</td><td>[#labelTemplate#]</td></tr>
                <!--ET_BEGIN printCount-->
                <tr><td style='font-weight:normal'>|Отпечатвания|*:</td><td>[#printCount#]</td></tr>
                <!--ET_END printCount-->
                </table>"));

        $resArr['indTimes'] = array('name' => tr('Заработка'), 'val' => tr("|*<table>
                <tr><td style='font-weight:normal'>|Норма|*:</td><td>[#indTime#]</td></tr>
                <tr><td style='font-weight:normal'>|Мярка|*:</td><td>[#indPackagingId#]</td></tr>
                <tr><td style='font-weight:normal'>|Разпределяне|*:</td><td>[#indTimeAllocation#]</td></tr>
                <!--ET_BEGIN simultaneity--><tr><td style='font-weight:normal'>|Едновременност|*:</td><td>[#simultaneity#]</td></tr><!--ET_END simultaneity-->
                </table>"));

        if(core_Packs::isInstalled('batch')){
            $batchTpl = planning_ProductionTaskDetails::renderBatchesSummary($rec);
            if($batchTpl instanceof core_ET){
                $resArr['batches'] = array('name' => tr('Партиди'), 'val' => $batchTpl->getContent());
            }
        }

        if(isset($rec->indPackagingId) && !empty($rec->indTime)){
            $row->indTime = core_Type::getByName("planning_type_ProductionRate(measureId={$rec->indPackagingId})")->toVerbal($rec->indTime);
        }



    }


    /**
     * След подготовка на антетката
     */
    protected static function on_AfterPrepareHeaderLines($mvc, &$res, $headerArr)
    {
       if(Mode::is('screenMode', 'narrow') && !Mode::is('printing')) {
            $res = new ET("<table class='subInfo'>");
            foreach ((array) $headerArr as $value) {
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
        $rec = $this->fetch($id);
        $originalProgress = $rec->progress;
        $updateFields = 'totalQuantity,totalWeight,totalNetWeight,scrappedQuantity,producedQuantity,progress,modifiedOn,modifiedBy,prevAssetId,assetId';

        // Ако е записано в сесията, че е подменена машината да се подмени и в операцията
        if($newAssetId = Mode::get("newAsset{$rec->id}")){
            $rec->prevAssetId = $rec->assetId;
            $rec->assetId = $newAssetId;
            Mode::setPermanent("newAsset{$rec->id}", null);

            // Новата и старата машина се заопашават
            $this->reorderTasksInAssetId[$rec->assetId] = $rec->assetId;
            if(isset($rec->prevAssetId)){
                $this->reorderTasksInAssetId[$rec->prevAssetId] = $rec->prevAssetId;
            }
            $this->logWrite("Промяна на оборудването ", $rec->id);
        }

        // Колко е общото к-во досега
        $dQuery = planning_ProductionTaskDetails::getQuery();
        $productId = ($rec->isFinal == 'yes') ? planning_Jobs::fetchField("#containerId = {$rec->originId}", 'productId') : $rec->productId;
        $dQuery->where("#taskId = {$rec->id} AND #productId = {$productId} AND (#type = 'production' OR #type = 'scrap') AND #state != 'rejected'");

        $rec->totalWeight = $rec->totalQuantity = $rec->scrappedQuantity = $rec->totalNetWeight = 0;
        while($dRec = $dQuery->fetch()){
            if($dRec->type == 'production'){
                $quantity = $dRec->quantity / $rec->quantityInPack;
                $rec->totalQuantity += $quantity;
                $rec->totalWeight += $dRec->weight;
                if(isset($dRec->netWeight)){
                    $rec->totalNetWeight += $dRec->netWeight;
                }
            } else {
                $rec->scrappedQuantity += $dRec->quantity / $rec->quantityInPack;
                $rec->totalWeight -= $dRec->weight;
                if(isset($dRec->netWeight)){
                    $rec->totalNetWeight -= $dRec->netWeight;
                }
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
        while($nRec = $noteQuery->fetch()){
            if($nRec->packagingId == $rec->measureId){
                $producedQuantity += $nRec->packQuantity;
            } else {
                $producedQuantity += $nRec->quantity;
            }
        }

        // Обновяване на произведеното по заданието
        if($producedQuantity != $rec->producedQuantity){
            planning_Jobs::updateProducedQuantity($rec->originId);
        }
        $rec->producedQuantity = $producedQuantity;

        // Ако има промяна в прогреса
        if($rec->progress != $originalProgress){
            $rec->orderByAssetId = null;
            if($lastTaskWithProgressId = $this->getPrevOrNextTask($rec, true)){
                $orderByAssetId = $this->fetchField($lastTaskWithProgressId, 'orderByAssetId');
                $rec->orderByAssetId = $orderByAssetId + 0.5;
            } else {
                $rec->orderByAssetId = 0.5;
            }

            if(isset($rec->assetId)){
                $this->reorderTasksInAssetId[$rec->assetId] = $rec->assetId;
            }

            $updateFields .= ',orderByAssetId';
            $rec->_stopReorder = true;
        }

        // При първо добавяне на прогрес, ако е в заявка - се активира автоматично
        if($rec->state == 'pending' && planning_ProductionTaskDetails::count("#taskId = {$rec->id}")){
            planning_plg_StateManager::changeState($this, $rec, 'activate');
            $this->logWrite('Активиране при прогрес', $rec->id);
            core_Statuses::newStatus('Операцията е активирана след добавяне на прогрес|*!');
        }

        return $this->save_($rec, $updateFields);
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
            }
        }

        if ($action == 'restore' && $rec) {
            if (isset($rec->originId)) {
                $origin = doc_Containers::getDocument($rec->originId);
                $state = $origin->fetchField('state');
                if($state == 'rejected'){
                    $requiredRoles = 'no_one';
                }
                if(static::fetch("#originId = {$rec->originId} AND #isFinal = 'yes' AND #state != 'rejected' AND #productId != {$rec->productId}")){
                    $requiredRoles = 'no_one';
                }
            }
        }

        if($action == 'printlabel' && isset($rec)){
            if(empty($rec->labelPackagingId)){
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'createjobtasks' && isset($rec)){
            if(empty($rec->type) || empty($rec->jobId)){
                $requiredRoles = 'no_one';
            } elseif(!in_array($rec->type, array('all', 'clone'))){
                $requiredRoles = 'no_one';
            } else {
                $jobRec = planning_Jobs::fetch($rec->jobId);
                if(!$mvc->haveRightFor('add', (object)array('folderId' => $rec->folderId, 'originId' => $jobRec->containerId))){
                    $requiredRoles = 'no_one';
                } else {
                    if($rec->type == 'clone'){
                        if(empty($rec->cloneId) || empty($jobRec->oldJobId)){
                            $requiredRoles = 'no_one';
                        }
                    } elseif($rec->type == 'all') {
                        $defaultTasks = cat_Products::getDefaultProductionTasks($jobRec, $jobRec->quantity);
                        $defaultTaskCount = countR($defaultTasks);
                        if(!$defaultTaskCount){
                            $requiredRoles = 'no_one';
                        } else {
                            $tQuery = planning_Tasks::getQuery();
                            $tQuery->where("#originId = {$jobRec->containerId} AND #systemId IS NOT NULL AND #state != 'rejected'");
                            $tQuery->show('systemId');
                            $exSystemIds = arr::extractValuesFromArray($tQuery->fetchAll(), 'systemId');
                            $remainingSystemTasks = array_diff_key($defaultTasks, $exSystemIds);
                            if(!countR($remainingSystemTasks) || $defaultTaskCount == 1){
                                $requiredRoles = 'no_one';
                            }
                        }
                    }
                }
            }
        }

        if($action == 'reordertask') {

            // Който може да редактира ПО може и да я преподрежда
            $requiredRoles = $mvc->getRequiredRoles('edit', $rec, $userId);
            if(isset($rec->id)){
                if(empty($rec->assetId)){
                    $requiredRoles = 'no_one';
                } elseif(!in_array($rec->state, array('active', 'wakeup', 'pending', 'stopped'))){
                    $requiredRoles = 'no_one';
                } elseif(!empty($rec->startAfter)){
                    $startAfterTask = $mvc->fetch($rec->startAfter, 'state,assetId');
                    if(!in_array($startAfterTask->state, array('stopped', 'pending', 'active', 'wakeup')) || $rec->assetId != $startAfterTask->assetId){
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }

        if($action == 'activate' && isset($rec)) {
            if ($rec->state != 'pending') {
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'recalcindtime' && isset($rec)){
            if(!planning_ProductionTaskDetails::count("#taskId = {$rec->id}") || $rec->state == 'rejected'){
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'copy2clipboard'){
            $requiredRoles = $mvc->getRequiredRoles('edit', $rec);
        }

        // След коя операция може да се пейстне запомнената в клипборда
        if($action == 'pastefromclipboard'){
            $requiredRoles = $mvc->getRequiredRoles('edit', $rec);
            $rememberedTaskRec = Mode::get('rememberedTask');
            if(!is_object($rememberedTaskRec)){
                $requiredRoles = 'no_one';
            }else {
                if(isset($rec)){
                    if(empty($rec->refTaskId)){
                        $requiredRoles = 'no_one';
                    } else {
                        $refTaskRec = $mvc->fetch($rec->refTaskId);
                        if($rememberedTaskRec->folderId != $refTaskRec->folderId || $rememberedTaskRec->id == $refTaskRec->id || empty($rememberedTaskRec->assetId) || empty($refTaskRec->assetId) || in_array($rememberedTaskRec, array('stopped', 'rejected')) || !in_array($refTaskRec->assetId, $rememberedTaskRec->_allowableAssets)){
                            $requiredRoles = 'no_one';
                        }
                    }
                }
            }
        }
    }


    /**
     * След успешен запис
     */
    protected static function on_AfterCreate($mvc, &$rec)
    {
        // Ако записа е създаден с клониране не се прави нищо
        if ($rec->_isClone === true) return;

        if (isset($rec->originId)) {
            $originDoc = doc_Containers::getDocument($rec->originId);
            $originRec = $originDoc->fetch();

            // Ако е по източник
            if (isset($rec->systemId)) {
                $tasks = cat_Products::getDefaultProductionTasks($originRec, $originRec->quantity);
                if (isset($tasks[$rec->systemId])) {
                    $def = $tasks[$rec->systemId];

                    // Намираме на коя дефолтна операция отговаря и извличаме продуктите от нея
                    foreach (array('production' => 'production', 'input' => 'input', 'waste' => 'waste') as $var => $type) {
                        if (is_array($def->products[$var])) {
                            foreach ($def->products[$var] as $p) {
                                $p = (object) $p;
                                $nRec = new stdClass();
                                $nRec->taskId = $rec->id;
                                $nRec->packagingId = $p->packagingId;
                                $nRec->quantityInPack = $p->quantityInPack;
                                $nRec->plannedQuantity = ($p->packQuantity / $originRec->quantity) * $rec->plannedQuantity;
                                $nRec->productId = $p->productId;
                                $nRec->type = $type;
                                $nRec->storeId = $rec->storeId;

                                core_Users::forceSystemUser();
                                planning_ProductionTaskProducts::save($nRec);
                                core_Users::cancelSystemUser();
                            }
                        }
                    }
                }
            } elseif($rec->isFinal == 'yes') {
                $nRec = new stdClass();
                $nRec->taskId = $rec->id;
                $nRec->productId = $originRec->productId;
                $nRec->type = 'production';
                core_Users::forceSystemUser();
                planning_ProductionTaskProducts::save($nRec);
                core_Users::cancelSystemUser();
            }
        }

        // Копиране на параметрите на артикула към операцията
        if (is_array($rec->_params)) {
            cat_products_Params::saveParams($mvc, $rec);
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
        $form->setField('saoRelative', "caption=Подредба в заданието->Спрямо");
        $form->setField('saoPosition', "caption=Подредба в заданието->Положение");

        if (isset($rec->systemId)) {
            $form->setField('prototypeId', 'input=none');
        }
        if (empty($rec->id)) {
            if ($folderId = Request::get('folderId', 'key(mvc=doc_Folders)')) {
                unset($rec->threadId);
                $rec->folderId = $folderId;
            }
        } else {
            if($data->action != 'clone' && in_array($rec->state, array('active', 'wakeup', 'stopped'))){
                $form->setField('wasteProductId', 'input=none');
                $form->setField('wasteStart', 'input=none');
                $form->setField('wastePercent', 'input=none');
                $form->rec->_editActive = true;
            }
        }
        $form->setFieldTypeParams('productId', array('centerFolderId' => $rec->folderId));

        // За произвеждане може да се избере само артикула от заданието
        try{
            $origin = doc_Containers::getDocument($rec->originId);
        } catch(core_exception_Expect $e){
            wp($e, $rec, $form, core_Users::getCurrent());
            followRetUrl(null, 'Има грешка при създаването', 'error');
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
            if(!empty($taskData['fixedAssets'])){
                $fixedAssetOptions = keylist::toArray($taskData['fixedAssets']);
            }

            if(isset($taskData['productId'])){
                $form->setReadOnly('productId');
            }
        }

        if (isset($rec->productId)) {
            $wasteSysId = cat_Groups::getKeylistBySysIds('waste');
            $form->setFieldTypeParams("wasteProductId", array('hasProperties' => 'canStore,canConvert', 'groups' => $wasteSysId));
            $form->setField('labelType', 'input');
            $form->setField('measureId', 'input');

            if(core_Packs::isInstalled('batch')){
                if(batch_Defs::getBatchDef($originRec->productId)){
                    $form->setField('followBatchesForFinalProduct', 'input');
                }
            }

            // Ако не е системна, взима се дефолта от драйвера
            $productionData = array();
            if($Driver = cat_Products::getDriver($rec->productId)) {
                $productionData = $Driver->getProductionData($rec->productId);
            }

            if(empty($rec->systemId) && empty($rec->id)){
                $defFields = arr::make("employees=employees,labelType=labelType,labelTemplate=labelTemplate,isFinal=isFinal,wasteProductId=wasteProductId,wastePercent=wastePercent,wasteStart=wasteStart,storeId=storeIn,indTime=norm");
                foreach ($defFields as $fld => $val){
                    $form->setDefault($fld, $productionData[$val]);
                }
            }

            // Ако артикула от етапа е генеричен предлагат се за избор неговите еквивалентни
            if(isset($productionData['wasteProductId'])){
                $wasteOptions = planning_GenericMapper::getEquivalentProducts($productionData['wasteProductId']);
                if(countR($wasteOptions)){
                    $form->setFieldType('wasteProductId', 'int');
                    $form->setOptions('wasteProductId', $wasteOptions);
                }
            }

            if(isset($productionData['fixedAssets'])){
                $fixedAssetOptions = $productionData['fixedAssets'];
            }

            $employeeOptions = planning_Hr::getByFolderId($rec->folderId, $rec->employees);
            if(countR($employeeOptions)){
                $form->setSuggestions('employees',  array('' => '') + $employeeOptions);
            } else {
                $form->setField('employees', 'input=none');
            }

            $productId4Form = ($rec->isFinal == 'yes') ? $originRec->productId : $rec->productId;
            $productRec = cat_Products::fetch($productId4Form, 'canConvert,canStore,measureId');
            $similarMeasures = cat_UoM::getSameTypeMeasures($productRec->measureId);
            if($rec->isFinal == 'yes'){
                $form->info = "<div class='richtext-info-no-image'>" . tr('Финална операция към|* ') . $origin->getHyperlink(true) . "</div>";
                $measureOptions = array();
                $jobPackagingType = cat_UoM::fetchField($originRec->packagingId, 'type');

                // Ако заданието е в мярка тя е по-дефолт първата избрана
                if($jobPackagingType == 'uom'){
                    $measureOptions[$originRec->packagingId] = cat_UoM::getTitleById($originRec->packagingId, false);
                } else {
                    // Ако е за опаковка, то дефолт е основната мярка
                    $measureOptions[$productRec->measureId] = cat_UoM::getTitleById($productRec->measureId, false);
                }

                // Ако има втора мярка
                if($originRec->allowSecondMeasure == 'yes'){
                    // добавя се и тя
                    $measureOptions[$originRec->secondMeasureId] = cat_UoM::getTitleById($originRec->secondMeasureId, false);
                }

                // Ако някоя от произовдните на основната му мярка е налична в опциите - добавят се и останалите
                if(countR(array_intersect_key($measureOptions, $similarMeasures)) || $originRec->allowSecondMeasure == 'yes'){
                    // както и производните на основната му мярка, които са опаковки
                    $packMeasures = cat_Products::getPacks($productRec->id, true);
                    $leftMeasures = array_intersect_key($similarMeasures, $packMeasures);
                    $leftMeasures = array_keys($leftMeasures);
                    foreach ($leftMeasures as $lMeasureId){
                        if(!array_key_exists($lMeasureId, $measureOptions)){
                            $measureOptions[$lMeasureId] = cat_UoM::getTitleById($lMeasureId, false);
                        }
                    }
                }
            } else {
                $measureOptions = cat_Products::getPacks($rec->productId, true);
            }

            $measuresCount = countR($measureOptions);
            $form->setOptions('measureId', $measureOptions);
            $form->setDefault('measureId', key($measureOptions));
            if($measuresCount == 1){
                $form->setField('measureId', 'input=hidden');
            }

            $form->setFieldTypeParams("indTime", array('measureId' => $rec->measureId));
            if($rec->isFinal == 'yes'){
                $packType = cat_UoM::fetchField($originRec->packagingId, 'type');
                $defaultPlannedQuantity = $originRec->quantity;
                if($rec->measureId != $originRec->packagingId){
                    if($originRec->allowSecondMeasure == 'yes'){
                        if($packType == 'uom') {
                            if(!array_key_exists($originRec->packagingId, $similarMeasures)){
                                if ($pQuantity = cat_products_Packagings::getPack($productRec->id, $originRec->packagingId, 'quantity')) {
                                    $defaultPlannedQuantity *= $pQuantity;
                                }
                            }

                            if(array_key_exists($rec->measureId, $similarMeasures)){
                                $defaultPlannedQuantity = cat_UoM::convertValue($defaultPlannedQuantity, $productRec->measureId, $rec->measureId);
                            } else {
                                if ($pQuantity = cat_products_Packagings::getPack($productRec->id, $rec->measureId, 'quantity')) {
                                    $defaultPlannedQuantity /= $pQuantity;
                                }
                            }
                        } else {
                            if(!array_key_exists($rec->measureId, $similarMeasures)){
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

            if(countR($fixedAssetOptions)){
                $cloneArr = $fixedAssetOptions;
                $fixedAssetOptions = array();
                array_walk($cloneArr, function($a) use (&$fixedAssetOptions){$fixedAssetOptions[$a] = planning_AssetResources::getTitleById($a, false);});
            }

            if (empty($rec->id)) {
                cat_products_Params::addProductParamsToForm($mvc, $rec->id, $originRec->productId, $rec->productId, $form);
            }

            if (isset($rec->systemId, $tasks[$rec->systemId])) {
                $taskData = (array)$tasks[$rec->systemId];
                if(countR($taskData['params'])){
                    foreach ($taskData['params'] as $pId => $pVal){
                        $form->setDefault("paramcat{$pId}", $pVal);
                    }
                }
            }

            if ($productRec->canStore == 'yes') {
                $packs = planning_Tasks::getAllowedLabelPackagingOptions($rec->measureId, $productId4Form, $rec->labelPackagingId);
                $form->setOptions('labelPackagingId', array('' => '') + $packs);
                $indPacks = array($rec->measureId => cat_UoM::getTitleById($rec->measureId, false)) + cat_products_Packagings::getOnlyPacks($productId4Form);

                $form->setOptions('indPackagingId', $indPacks);
                if(isset($productionData) && array_key_exists($productionData['normPackagingId'], $packs)){
                    $form->setDefault('indPackagingId', $productionData['normPackagingId']);
                }

                if($rec->isFinal != 'yes'){
                    if(array_key_exists($productionData['labelPackagingId'], $packs)){
                        $form->setDefault('labelPackagingId', $productionData['labelPackagingId']);
                    }
                }

                $form->setField('storeId', 'input');
                $form->setField('labelPackagingId', 'input');
                $form->setField('indPackagingId', 'input');
                $defaultShowAdditionalUom = planning_Setup::get('TASK_WEIGHT_MODE');
                $form->setDefault('showadditionalUom', $defaultShowAdditionalUom);

                if($defaultShowAdditionalUom == $rec->showadditionalUom){
                    $form->setField('showadditionalUom', 'autohide=any');
                }
            } else {
                $form->setField('showadditionalUom', 'input=none');
                $form->setDefault('indPackagingId', $rec->measureId);
            }

            if($measuresCount == 1){
                $measureShort = cat_UoM::getShortName($rec->measureId);
                $form->setField('plannedQuantity', "unit={$measureShort}");
            }

            if(isset($rec->labelPackagingId)){
                $form->setField('labelQuantityInPack', 'input');
                $form->setField('labelTemplate', 'input');
                $form->setDefault('indPackagingId', $rec->labelPackagingId);

                if($rec->isFinal != 'yes' && $rec->labelPackagingId == $productionData['labelPackagingId']){
                    $stepMeasureId = cat_Products::fetchField($rec->productId, 'measureId');
                    $stepSimilarMeasures = cat_UoM::getSameTypeMeasures($stepMeasureId);
                    if(array_key_exists($productRec->measureId, $stepSimilarMeasures)){
                        $productionData['labelQuantityInPack'] = cat_UoM::convertValue($productionData['labelQuantityInPack'], $stepMeasureId, $productRec->measureId);
                    }
                    $form->setDefault('labelQuantityInPack', $productionData['labelQuantityInPack']);
                }

                $quantityInPackDefault = static::getDefaultQuantityInLabelPackagingId($productId4Form, $rec->measureId, $rec->labelPackagingId);
                $form->setField('labelQuantityInPack', "placeholder={$quantityInPackDefault}");

                $templateOptions = static::getAllAvailableLabelTemplates($rec->labelTemplate);
                $form->setOptions('labelTemplate', $templateOptions);
                $form->setDefault('labelTemplate', key($templateOptions));
                $defaultIndPackagingId = $rec->labelPackagingId;
            } else {
                $form->setField('labelTemplate', 'input=hidden');
                $defaultIndPackagingId = $rec->measureId;
            }

            if(empty($rec->id)){
                $form->setDefault('indPackagingId', $defaultIndPackagingId);
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

            if(isset($rec->indPackagingId)){
                $form->setFieldTypeParams('indTime', array('measureId' => $rec->indPackagingId));
            }

            if(isset($rec->wasteProductId)){
                $wasteProductMeasureId = cat_Products::fetchField($rec->wasteProductId, 'measureId');
                $form->setField("wasteStart", "unit=" . cat_UoM::getShortName($wasteProductMeasureId));
            }
        } else {
            $form->setField('employees', 'input=hidden');
        }

        // Добавяне на наличните за избор оборудвания
        $fixedAssetOptions = countR($fixedAssetOptions) ? $fixedAssetOptions : planning_AssetResources::getByFolderId($rec->folderId, $rec->assetId, 'planning_Tasks', true);
        $countAssets = countR($fixedAssetOptions);

        if($countAssets){
            $form->setField('assetId', 'input');
            if($countAssets == 1 && empty($rec->id)){
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
        if(isset($rec->assetId)){
            $assetSimultaneity = planning_AssetResources::fetchField($rec->assetId, 'simultaneity');
            $form->setField('simultaneity', "input,placeholder={$assetSimultaneity}");
            if($data->action != 'clone'){
                $assetTasks = planning_AssetResources::getAssetTaskOptions($rec->assetId, true);
                unset($assetTasks[$rec->id]);
                $taskOptions = array();
                foreach ($assetTasks as $tRec){
                    $job = doc_Containers::getDocument($tRec->originId);
                    $jobTitle =  str::limitLen("Job{$job->that}-" . cat_Products::getVerbal($job->fetchField('productId'), 'name'), 42);
                    $productTitle = str::limitLen(cat_Products::getVerbal($tRec->productId, 'name'), 42);
                    $title = "Opr{$tRec->id}/{$jobTitle}/{$productTitle}";
                    $taskOptions[$tRec->id] = $title;
                }

                $form->setField('startAfter', 'input');
                if(countR($taskOptions)){
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
            if($data->action != 'clone' && planning_ProductionTaskDetails::fetchField("#taskId = {$rec->id}")){
                $form->setReadOnly('labelPackagingId');
                if($form->getFieldParam('labelQuantityInPack', 'input') != 'hidden'){
                    $form->setReadOnly('labelQuantityInPack');
                }
                $form->setReadOnly('measureId');
            }
        }
    }


    /**
     * Връща допустимите за етикетиране мерки/опаковки
     *
     * @param int $selectedMeasureId - мярка на артикул
     * @param int $productId         - ид на артикул
     * @param int|null $exId         - ид на вече избрана мярка
     * @return array $packs          - допустимите за избор опаковки + мярка "брой"(ако се поддържа)
     */
    public static function getAllowedLabelPackagingOptions($selectedMeasureId, $productId = null, $exId = null)
    {
        $packs = array();
        if($selectedMeasureId == cat_UoM::fetchBySysId('pcs')->id){
            $packs[$selectedMeasureId] = cat_UoM::getTitleById($selectedMeasureId, false);
        }

        if(isset($productId)){
            $packs += cat_products_Packagings::getOnlyPacks($productId);
        } else {
            $packs += cat_UoM::getPackagingOptions();
        }
        if(isset($exId) && !array_key_exists($exId, $packs)){
            $packs[$exId] = cat_UoM::getTitleById($exId, false);
        }

        if(countR($packs)){
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
        if(empty($rec->assetId)) return null;

        $query = planning_Tasks::getQuery();
        $query->where("#assetId = {$rec->assetId} AND #orderByAssetId IS NOT NULL");
        $dir = ($next) ? "DESC" : "ASC";
        $query->orderBy('orderByAssetId', $dir);
        $query->show('id');
        $query->limit(1);


        if($withProgress){
            $query->where("#progress != 0");
        }

        if(isset($rec->id) && isset($rec->orderByAssetId)){
            $sign = ($next) ? "<" : ">";
            $query->where("#orderByAssetId {$sign} {$rec->orderByAssetId}");
        }

        return $query->fetch()->id;
    }


    /**
     * Подготвя задачите към заданията
     */
    public function prepareTasks($data)
    {
        if($data->masterMvc instanceof planning_AssetResources){
            $data->TabCaption = 'Операции';
        }

        $data->pager = cls::get('core_Pager', array('itemsPerPage' => 10));
        $data->pager->setPageVar($data->masterMvc->className, $data->masterId);
        $data->recs = $data->rows = array();

        // Всички създадени задачи към заданието
        $query = $this->getQuery();
        $query->XPR('orderByDate', 'datetime', "COALESCE(#expectedTimeStart, 9999999999999)");
        $query->where("#state != 'rejected'");
        $query->orderBy('saoOrder', 'ASC');
        if($data->masterMvc instanceof planning_AssetResources){
            $query->where("#assetId = {$data->masterId}");
        } else {
            $query->where("#originId = {$data->masterData->rec->containerId}");
        }
        $data->pager->setLimit($query);

        $fields = $this->selectFields();
        $fields['-list'] = $fields['-detail'] = true;

        // Подготвяне на данните
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $row = planning_Tasks::recToVerbal($rec, $fields);
            if(!empty($rec->assetId)){
                $row->assetId = planning_AssetResources::getShortName($rec->assetId, !Mode::isReadOnly());
            }

            $row->title = ht::createElement("span", array('id' => planning_Tasks::getHandle($rec->id)), $row->title);
            $row->plannedQuantity .= " " . $row->measureId;
            $row->totalQuantity .= " " . $row->measureId;
            $row->producedQuantity .= " " . $row->measureId;

            // Показване на протоколите за производство
            $notes = array();
            $nQuery = planning_DirectProductionNote::getQuery();
            $nQuery->where("#originId = {$rec->containerId} AND #state != 'rejected'");
            $nQuery->show('id');
            while($nRec = $nQuery->fetch()){
                $notes[] = planning_DirectProductionNote::getLink($nRec->id, 0);
            }
            if (countR($notes)) {
                $row->info .= "<div style='padding-bottom:7px' class='small'>" . implode(' | ', $notes) . "</div>";
            }

            // Линк към разходите, ако ПО е разходен обект
            if(acc_Items::isItemInList($this, $rec->id, 'costObjects')){
                $costsCount = doc_ExpensesSummary::fetchField("#containerId = {$rec->containerId}", 'count');

                $costsCount = !empty($costsCount) ? $costsCount : 0;
                $linkArr = array();
                if (haveRole('ceo, acc, purchase, sales') && $this->haveRightFor('single', $rec->id)) {
                    $linkArr = array($this, 'single', $rec->id, 'Sid' => $rec->containerId);
                }
                $costsCount = core_Type::getByName('int')->toVerbal($costsCount);
                $row->costsCount = ht::createLinkRef($costsCount, $linkArr, false, 'title=Показване на разходите към документа');
            }

            $data->rows[$rec->id] = $row;
        }

        Mode::push('forListRows', true);
        $this->invoke('AfterPrepareListRows', array($data, $data));

        // Ако потребителя може да добавя операция от съответния тип, ще показваме бутон за добавяне
        if($data->masterMvc instanceof planning_Jobs){
            if ($this->haveRightFor('add', (object) array('originId' => $data->masterData->rec->containerId))) {
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
        if($data->masterMvc instanceof planning_AssetResources){
            $data->TabCaption = 'Операции';
            $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        }

        // Рендиране на таблицата с намерените задачи
        $listTableMvc = clone $this;
        $listTableMvc->FNC('costsCount', 'int');

        $table = cls::get('core_TableView', array('mvc' => $listTableMvc));
        $fields = arr::make('saoOrder=Ред,expectedTimeStart=Начало,title=Операция,progress=Прогрес,plannedQuantity=Планирано,totalQuantity=Произведено,producedQuantity=Заскладено,costsCount=Разходи, assetId=Оборудване,info=@info');
        if($data->masterMvc instanceof planning_AssetResources){
            unset($fields['assetId']);
        }

        $data->listFields = core_TableView::filterEmptyColumns($data->rows, $fields, 'assetId,costsCount');
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $contentTpl = $table->get($data->rows, $data->listFields);
        if(isset($data->pager)){
            $contentTpl->append($data->pager->getHtml());
        }

        // Имали бутони за добавяне
        if (isset($data->addUrlArray)) {
            $btn = ht::createLink('', $data->addUrlArray, false, "title=Създаване на производствена операция към задание,ef_icon=img/16/add.png");
            $contentTpl->append($btn, 'btnTasks');
        }

        if($data->masterMvc instanceof planning_AssetResources){
            $tpl->append(tr('Производствени операции'), 'title');
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
        $data->listFilter->setFieldTypeParams('folder', array('containingDocumentIds' => planning_Tasks::getClassId()));
        $data->listFilter->setField('folder', 'autoFilter,silent');
        $orderByField = 'orderByDate';

        // Добавят се за избор само използваните в ПО оборудвания
        $assetInTasks = planning_AssetResources::getUsedAssetsInTasks($data->listFilter->rec->folder);
        if(countR($assetInTasks)){
            $data->listFilter->setField('assetId', 'caption=Оборудване,autoFilter');
            $data->listFilter->setOptions('assetId', array('' => '') + $assetInTasks);
            $data->listFilter->showFields .= ',assetId';
            $data->listFilter->input('assetId');
        }

        $mvc->listItemsPerPage = 20;
        if($filter = $data->listFilter->rec){
            if (isset($filter->assetId)) {
                $mvc->listItemsPerPage = 200;
                $data->query->where("#assetId = {$filter->assetId}");
                $orderByField = 'orderByAssetId';
            }
        }

        $orderByDir = 'ASC';
        if (!Request::get('Rejected', 'int')) {
            $data->listFilter->setOptions('state', arr::make('activeAndPending=Заявки+Активни+Събудени+Спрени,draft=Чернова,active=Активен,closed=Приключен, stopped=Спрян, wakeup=Събуден,waiting=Чакащо,pending=Заявка,all=Всички', true));
            $data->listFilter->showFields .= ',state';
            $data->listFilter->input('state');
            $data->listFilter->setDefault('state', 'activeAndPending');

            $orderByDateCoalesce = 'COALESCE(#expectedTimeStart, 9999999999999)';

            if ($filter = $data->listFilter->rec) {
                if ($filter->state == 'activeAndPending') {
                    $data->query->where("#state IN ('active', 'pending', 'wakeup', 'stopped', 'rejected')");
                } elseif($filter->state != 'all') {
                    $data->query->where("#state = '{$filter->state}' OR #state = 'rejected'");

                    if($filter->state == 'closed'){
                        $orderByDir = 'DESC';
                        $orderByDateCoalesce = 'COALESCE(#timeClosed, 0)';
                    }
                }

                if($filter->filterDateField == 'dueDate'){
                    if(!isset($filter->assetId)){
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
    }


    /**
     * Връща масив от задачи към дадено задание
     *
     * @param int  $jobId      - ид на задание
     * @param mixed $states    - В кои състояния
     * @param boolean $verbal  - вербални или записи
     *
     * @return array $res      - масив с намерените задачи
     */
    public static function getTasksByJob($jobId, $states, $verbal = true)
    {
        $res = array();
        $oldContainerId = planning_Jobs::fetchField($jobId, 'containerId');
        $query = static::getQuery();
        $query->where("#originId = {$oldContainerId}");
        $states = arr::make($states, true);
        $query->in("state", $states);

        while ($rec = $query->fetch()) {
            $res[$rec->id] = ($verbal) ? self::getLink($rec->id, false) : $rec;
        }

        return $res;
    }


    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        if (empty($rec->id)) return;

        // Добавяне на всички ключови думи от прогреса
        $dQuery = planning_ProductionTaskDetails::getQuery();
        $dQuery->XPR('concat', 'varchar', 'GROUP_CONCAT(#searchKeywords)');
        $dQuery->where("#taskId = {$rec->id}");
        $dQuery->limit(1);

        if ($keywords = $dQuery->fetch()->concat) {
            $keywords = str_replace(' , ', ' ', $keywords);
            $res = ' ' . $res . ' ' . $keywords;
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

        $sum = 0;
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#originId = {$jobRec->containerId} AND (#productId = {$jobRec->productId} OR #isFinal = 'yes')");
        $tQuery->where("#state != 'rejected' AND #state != 'pending'");
        $tQuery->show('totalQuantity,scrappedQuantity,measureId,quantityInPack');
        while($tRec = $tQuery->fetch()){
            $sumRec = ($tRec->totalQuantity - $tRec->scrappedQuantity) * $tRec->quantityInPack;
            if($pQuantity = cat_products_Packagings::getPack($jobRec->productId, $jobRec->packagingId, 'quantity')){
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
        $taskDetailQuery->where(array("#serial = '[#1#]'", $str));

        while($dRec = $taskDetailQuery->fetch()) {

            $res = new stdClass();
            $tRec = $this->fetch($dRec->taskId);
            $res->title = tr('ПО') . ': ' . $tRec->title;

            if ($this->haveRightFor('single', $tRec)) {
                if (doc_Threads::haveRightFor('single', $tRec->threadId)) {
                    $hnd = $this->getHandle($tRec->id);
                    $res->url = array('doc_Containers', 'list', 'threadId' => $tRec->threadId, 'docId' => $hnd, 'serial' => $str, 'Q' => $str, '#' => $hnd);
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
        if (planning_DirectProductionNote::haveRightFor('add', (object) array('originId' => $rec->containerId))) {
            $pUrl = array('planning_DirectProductionNote', 'add', 'originId' => $rec->containerId, 'ret_url' => true);
            $data->toolbar->addBtn('Произвеждане', $pUrl, 'ef_icon = img/16/page_paste.png,title=Създаване на протокол за производство от операцията');
        }

        // Бутон за добавяне на документ за производство
        if (planning_ConsumptionNotes::haveRightFor('add', (object) array('originId' => $rec->containerId))) {
            $pUrl = array('planning_ConsumptionNotes', 'add', 'originId' => $rec->containerId, 'ret_url' => true);
            $data->toolbar->addBtn('Влагане', $pUrl, 'ef_icon = img/16/produce_in.png,title=Създаване на протокол за влагане от операцията');
        }

        // Бутон за добавяне на документ за влагане
        if (planning_ConsumptionNotes::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
            $pUrl = array('planning_ReturnNotes', 'add', 'threadId' => $rec->threadId, 'ret_url' => true);
            $data->toolbar->addBtn('Връщане', $pUrl, 'ef_icon = img/16/produce_out.png,title=Създаване на протокол за връщане към заданието,row=2');
        }

        // Бутон за добавяне на документ за влагане
        if ($mvc->haveRightFor('recalcindtime', $rec)) {
            $data->toolbar->addBtn('Преизч. заработки', array($mvc, 'recalcindtimes', $rec->id, 'ret_url' => true), 'ef_icon = img/16/arrow_refresh.png,title=Преизчисляване на заработките към операцията,row=2,warning=Наистина ли желаете да преизчислите заработките в прогреса|*?');
        }

        if($data->toolbar->haveButton('btnActivate')){
            $data->toolbar->renameBtn('btnActivate', 'Стартиране');
        }
    }


    /**
     * След промяна на състоянието
     */
    protected static function on_AfterChangeState($mvc, &$rec, $action)
    {
        // При затваряне се попълва очаквания край, ако не може да се изчисли
        if($action == 'closed' && empty($rec->timeEnd) && !isset($rec->timeStart, $rec->timeDuration)){
            $rec->timeEnd =  dt::now();
            $mvc->save_($rec, 'timeEnd');
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
     * @see crm_ContragentAccRegIntf::getItemRec
     *
     * @param int $objectId
     */
    public static function getItemRec($objectId)
    {
        $self = cls::get(get_called_class());
        $result = null;

        if ($rec = $self->fetch($objectId)) {
            $title = $self->getVerbal($rec, 'productId');
            $origin = doc_Containers::getDocument($rec->originId);
            if($origin->isInstanceOf('planning_Jobs')){
                $title = $origin->getVerbal('productId') . " - {$title}";
            }

            $result = (object) array(
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
        expect($type = Request::get('type', 'enum(all,clone)'));
        expect($jobId = Request::get('jobId', 'int'));
        expect($jobRec = planning_Jobs::fetch($jobId));

        // Ако ще се клонира съществуваща операция
        if($type == 'clone'){
            expect($cloneId = Request::get('cloneId', 'int'));
            planning_Tasks::requireRightFor('createjobtasks', (object)array('jobId' => $jobRec->id, 'cloneId' => $cloneId, 'type' => 'clone'));
            expect($taskRec = $this->fetch($cloneId));

            $newTask = clone $taskRec;
            plg_Clone::unsetFieldsNotToClone($this, $newTask, $taskRec);

            $newTask->plannedQuantity = $taskRec->plannedQuantity;
            $newTask->_isClone = true;
            $newTask->originId = $jobRec->containerId;
            $newTask->state = 'draft';
            $newTask->clonedFromId = $newTask->id;
            unset($newTask->id);
            unset($newTask->threadId);
            unset($newTask->containerId);
            unset($newTask->createdOn);
            unset($newTask->createdBy);
            unset($newTask->systemId);

            if ($this->save($newTask)) {
                $this->invoke('AfterSaveCloneRec', array($taskRec, &$newTask));
                $this->logWrite('Клониране от предходно задание', $newTask->id);
            }

            followRetUrl(null, 'Операцията е клонирана успешно');
        } elseif($type == 'all'){

            // Ако ще се клонират всички шаблонни операции
            planning_Tasks::requireRightFor('createjobtasks', (object)array('jobId' => $jobRec->id, 'type' => 'all'));
            $msgType = 'notice';
            $msg = 'Операциите са успешно създадени';

            $defaultTasks = cat_Products::getDefaultProductionTasks($jobRec, $jobRec->quantity);
            foreach ($defaultTasks as $sysId => $defaultTask){
                try{
                    if(planning_Tasks::fetchField("#originId = {$jobRec->containerId} AND #systemId = {$sysId} AND #state != 'rejected'")) continue;

                    unset($defaultTask->products);
                    $newTask = clone $defaultTask;
                    $newTask->originId = $jobRec->containerId;
                    $newTask->systemId = $sysId;

                    // Клонират се в папката на посочения в тях център, ако няма в центъра от заданието, ако и там няма в Неопределения
                    $folderId = isset($defaultTask->centerId) ? planning_Centers::fetchField($defaultTask->centerId, 'folderId') : ((!empty($jobRec->department)) ? planning_Centers::fetchField($jobRec->department, 'folderId') : null);
                    if(!planning_Tasks::canAddToFolder($folderId)){
                        $folderId = planning_Centers::getUndefinedFolderId();
                    }
                    $newTask->folderId = $folderId;
                    $this->save($newTask);
                    $this->logWrite('Автоматично създаване от задание', $newTask->id);
                } catch(core_exception_Expect $e){
                    reportException($e);
                    $msg = 'Проблем при създаване на операция';
                    $msgType = 'error';
                }
            }

            followRetUrl(null, $msg, $msgType);
        }

        followRetUrl(null, 'Имаше проблем', 'error');
    }


    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    public static function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        // Ако се иска директно контиране редирект към екшъна за контиране
        if (isset($data->form) && $data->form->isSubmitted() && $data->form->rec->id) {

            $retUrl = getRetUrl();
            if($retUrl['Ctr'] == 'planning_Jobs'){
                if($retUrl['Act'] == 'selectTaskAction'){
                    if($data->form->cmd == 'save_pending_new'){
                        $data->retUrl = $retUrl;
                    }
                } elseif($retUrl['Act'] == 'single'){
                    $jobThreadId = planning_Jobs::fetchField($retUrl['id'], 'threadId');
                    if(doc_Threads::haveRightFor('single', $jobThreadId)){
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
        foreach ($labelTemplateRecs as $templateRec){
            $options[$templateRec->id] = $templateRec->title;
        }

        if(isset($exTemplateId)){
            if(!array_key_exists($exTemplateId, $options)){
                $options[$exTemplateId] = label_Templates::fetchField($exTemplateId, 'title');
            }
        }

        return $options;
    }


    /**
     * Параметрите на бутона за етикетиране
     */
    protected static function on_AfterGetLabelTemplates($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        if(isset($rec->labelTemplate) && !array_key_exists($rec->labelTemplate, $res)){
            $res[$rec->labelTemplate] = label_Templates::fetch($rec->labelTemplate);
        }
    }


    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $rows = &$data->rows;
        if (!countR($rows)) return;

        if(Mode::is('printing')){
            $uniqueFolders = arr::extractValuesFromArray($data->recs, 'folderId');
            if(countR($uniqueFolders) == 1){
                unset($data->listFields['folderId']);
            }
        }

        // Ако е филтрирано по център на дейност
        if ($data->listFilter->rec->folder) {
            $Cover = doc_Folders::getCover($data->listFilter->rec->folder);
            if($Cover->isInstanceOf('planning_Centers')){
                $plannedParams = keylist::toArray($Cover->fetchField('planningParams'));
                $data->listFieldsParams = cat_Params::getOrderedArr($plannedParams, 'desc');

                // и той има избрани параметри за планиране, добавят се в таблицата
                $paramFields = array();
                foreach ($data->listFieldsParams as $paramId) {
                    $fullName = cat_Params::getVerbal($paramId, 'typeExt');
                    $paramExt = explode(' » ', $fullName);
                    if(countR($paramExt) == 1){
                        $paramExt[1] = $paramExt[0];
                        $paramExt[0] = " ";
                    }
                    if($fullName != $paramExt[1]){
                        $paramExt[1] = ht::createHint($paramExt[1], $fullName);
                    }
                    $paramFields["param_{$paramId}"] = "|*<small>{$paramExt[1]}</small>";
                    $data->listTableMvc->FNC("param_{$paramId}", 'varchar', 'tdClass=taskParamCol');
                }
                arr::placeInAssocArray($data->listFields, $paramFields, null, 'dependantProgress');
            }
        }

        $displayPlanningParamsCount = countR($data->listFieldsParams);
        $enableReorder = isset($data->listFilter->rec->assetId) &&  in_array($data->listFilter->rec->state, array('activeAndPending', 'pending', 'active', 'wakeup')) && countR($data->recs) > 1;

        // Еднократно извличане на специфичните параметри за показваните операции
        $taskSpecificParams = array();
        if($displayPlanningParamsCount){

            // Ако в операцията има конкретно избрани параметри - ще се използват те с приоритет
            $taskParamQuery = cat_products_Params::getQuery();
            $taskParamQuery->where("#classId = {$mvc->getClassId()}");
            $taskParamQuery->in('productId', array_keys($data->recs));
            while($taskParamRec = $taskParamQuery->fetch()){
                $taskParamVal = cat_Params::toVerbal($taskParamRec->paramId, $mvc->getClassId(), $taskParamRec->productId, $taskParamRec->paramValue);
                $taskSpecificParams[$taskParamRec->productId][$taskParamRec->paramId] = $taskParamVal;
            }
        }

        // Еднократно извличане на зависимите предходни операции
        $dependentTasks = planning_StepConditions::getDependantTasksProgress($data->recs, true);

        foreach ($rows as $id => $row) {
            $rec = $data->recs[$id];

            // Ако има планирани предходни операции - да се показват с техните прогреси
            if(isset($dependentTasks[$rec->id])){
                if(is_array($dependentTasks[$rec->id])){
                    $row->dependantProgress = implode("", $dependentTasks[$rec->id]);
                }
            }

            // Добавяне на дата атрибут за да може с драг и дроп да се преподреждат ПО в списъка
            $row->ROW_ATTR['data-id'] = $rec->id;
            if($enableReorder){
                if($mvc->haveRightFor('reordertask', $rec)){
                    $reorderUrl = toUrl(array($mvc, 'reordertask', 'tId' => $rec->id, 'ret_url' => true), 'local');
                    $row->title = ht::createElement('span', array('data-currentId' => $rec->id, 'data-url' => $reorderUrl, 'class' => 'draggable', 'title' => 'Може да преместите задачата след друга|*!'), $row->title);
                }
            }

            if($displayPlanningParamsCount){

                // Кои са параметрите от артикула на заданието за операцията
                $origin = doc_Containers::getDocument($rec->originId);
                $jobProductId = $origin->fetchField('productId');

                // Взимане с приоритет от кеша на параметрите на артикула от заданието
                $jobParams = core_Permanent::get("taskListJobParams{$jobProductId}");
                if(!is_array($jobParams)){
                    $jobParams = cat_Products::getParams($jobProductId, null, true);
                    core_Permanent::set("taskListJobParams{$jobProductId}", $jobParams, 5*60);
                }

                foreach ($data->listFieldsParams as $paramId){
                    $live = true;
                    $pValue = array_key_exists($paramId, $jobParams) ? $jobParams[$paramId] : null;
                    if(is_array($taskSpecificParams[$rec->id]) && array_key_exists($paramId, $taskSpecificParams[$rec->id])){
                        $pValue = $taskSpecificParams[$rec->id][$paramId];
                        $live = false;
                    }

                    if(isset($pValue)){
                        $pSuffix = cat_Params::getVerbal($paramId, 'suffix');
                        $row->{"param_{$paramId}"} = $pValue;
                        if(!empty($pSuffix)){
                            $row->{"param_{$paramId}"} .= " {$pSuffix}";
                        }
                        if($live){
                            $row->{"param_{$paramId}"} = "<span style='color:blue'>{$row->{"param_{$paramId}"}}</span>";
                        }
                    }
                }
            }
        }
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

        $rememberedTask = Mode::get('rememberedTask');
        $rememberedTask = is_object($rememberedTask) ? $rememberedTask->id : '';
        $res = md5(trim($mQuery->fetch()->modifiedOn) . $rememberedTask);
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
            if(!$rec->_fromForm && !$rec->_isDragAndDrop){

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
    }


    /**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    public static function on_AfterSessionClose($mvc)
    {
        // Задачите към заопашените оборудвания се преподреждат
        if (countR($mvc->reorderTasksInAssetId)) {
            foreach ($mvc->reorderTasksInAssetId as $assetId) {
                if(isset($assetId)){
                    planning_AssetResources::reOrderTasks($assetId);
                }
            }
        }

        if (countR($mvc->recalcProducedDetailIndTime)) {
            foreach ($mvc->recalcProducedDetailIndTime as $rec) {
                planning_ProductionTaskDetails::recalcIndTime($rec->id, 'production', $rec->productId);
                core_Statuses::newStatus('Нормата е променена. Преизчислени са заработките на прогреса|*!');
            }
        }
    }


    /**
     * След рендиране на лист таблицата
     */
    protected static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
        // Включване на драг и дроп ако има избрано оборудване
        $tpl->push('planning/js/TaskCommon.js', 'JS');
        jquery_Jquery::run($tpl, 'enableCopy2Clipboard();');
        jquery_Jquery::runAfterAjax($tpl, 'enableCopy2Clipboard');
        jquery_Jquery::runAfterAjax($tpl, 'makeTooltipFromTitle');

        if(isset($data->listFilter->rec->assetId)){
            if (!Request::get('ajax_mode')) {
                jqueryui_Ui::enable($tpl);
                $tpl->push('planning/js/Tasks.js', 'JS');
            }
            jquery_Jquery::run($tpl, 'listTasks();');
            jquery_Jquery::runAfterAjax($tpl, 'listTasks');
        }
    }


    /**
     * Екшън за преподреждане на ПО към машината
     */
    public function act_reordertask()
    {
        // Проверка за права
        $errorMsg = null;
        if(!$this->haveRightFor('reordertask')){
            $errorMsg = '|Нямате права|*!';
        }
        $id = Request::get('tId', 'int');
        if(!$id){
            $errorMsg = '|Невалиден запис|*!';
        }

        // Задаване след коя ПО да започне тази
        $rec = static::fetch($id);
        $rec->startAfter = Request::get('startAfter', 'int');
        $rec->modifiedOn = dt::now();
        $rec->modifiedBy = core_Users::getCurrent();

        if(!$this->haveRightFor('reordertask', $rec)){
            $errorMsg = '|Нямате права|*!';
        }

        // Ако има грешка се показва
        if(!empty($errorMsg)){
            core_Statuses::newStatus($errorMsg, 'error');
            return status_Messages::returnStatusesArray();
        }

        // Обновяване на записа и преподреждане на ПО
        $rec->_isDragAndDrop = true;
        $this->save($rec, 'orderByAssetId,modifiedOn,modifiedBy');

        planning_AssetResources::reOrderTasks($rec->assetId);
        unset($this->reorderTasksInAssetId[$rec->assetId]);

        $res = array();
        $res = $this->returnAjaxSuccessResponse($res);

        return $res;
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
            $rec->state == 'pending';
            if((empty($rec->timeDuration) && empty($rec->assetId))){
                $rec->state = 'waiting';
                core_Statuses::newStatus('Операцията няма избрано оборудване или продължителност. Преминава в чакащо състояние докато не се уточнят|*!');
            }
            $rec->state =  (empty($rec->timeDuration) && empty($rec->assetId)) ? 'waiting' : 'pending';
        }

        $rec->freeTimeAfter = 'no';

        // Запомняне на предишните стойностти на определени полета
        if(isset($rec->id)){
            $exRec = $mvc->fetch($rec->id, 'orderByAssetId,assetId,indTime', false);
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
        $saveRecs = array();

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
            $saveRecs[] = $wasteRec;
        }

        if($Driver = cat_Products::getDriver($rec->productId)){
            $pData = $Driver->getProductionData($rec->productId);

            // Ако има планиращи действия
            if(is_array($pData['actions'])){
                foreach ($pData['actions'] as $actionId){
                    if(planning_ProductionTaskProducts::fetchField("#taskId = {$rec->id} AND #type = 'input' AND #productId = {$actionId}")) continue;

                    // Ще се създава запис за планираното действие за влагане
                    $inputRec = (object)array('taskId' => $rec->id, 'productId' => $actionId, 'type' => 'input', 'quantityInPack' => 1, 'plannedQuantity' => 1, 'packagingId' => cat_Products::fetchField($actionId, 'measureId'), 'createdOn' => core_Users::SYSTEM_USER, 'modifiedBy' => core_Users::SYSTEM_USER, 'modifiedOn' => $now, 'createdOn' => $now);
                    if($normRec = planning_AssetResources::getNormRec($rec->assetId, $actionId)){
                        $inputRec->indTime = $normRec->indTime;
                    }
                    $saveRecs[] = $inputRec;
                }

                core_Statuses::newStatus('Добавени са планираните действия за операцията|*!');
            }
        }

        if(countR($saveRecs)){
            cls::get('planning_ProductionTaskProducts')->saveArray($saveRecs);
        }
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $assetId = Request::get('assetId', 'int');
        if(isset($assetId)){
            if(planning_AssetResources::haveRightFor('recalctime', (object)array('id' => $assetId))){
                $data->toolbar->addBtn('Преизчисляване', array('planning_AssetResources', 'recalcTimes', $assetId, 'ret_url' => true), 'ef_icon=img/16/arrow_refresh.png, title=Преизчисляване на времената на операциите към оборудването');
            }
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

        followRetUrl(null, 'Заработките са преизчислени успешно|*!');
    }


    /**
     * Необходим метод за подреждането
     * @see plg_StructureAndOrder
     */
    public static function getSaoItems($rec)
    {
        $res = array();
        $query = self::getQuery();
        $query->where("#originId = '{$rec->originId}' AND #state != 'rejected'");
        while ($rec = $query->fetch()) {
            $res[$rec->id] = $rec;
        }

        return $res;
    }


    /**
     * Преди рендиране на тулбара в лист таблицата
     * @see plg_RowTools2
     */
    protected static function on_BeforeRenderListTableRowToolbar($mvc, &$tools, $data)
    {
        if(isset($data->masterMvc)) return;

        if(is_array($data->recs) && countR($data->recs)){
            foreach ($data->recs as $rec){
                $tools->removeBtn("saoup{$rec->id}");
                $tools->removeBtn("saodown{$rec->id}");
            }
        }
    }


    /**
     * Екшън за поставяне на операция от клипборда
     */
    function act_pastefromclipboard()
    {
        $errorMsg = null;
        if(!$this->haveRightFor('pastefromclipboard')){
            $errorMsg = '|Нямате права|*!';
        }
        $refTaskId = Request::get('refTaskId', 'int');
        $place = Request::get('place', 'enum(after,before)');
        if(!$refTaskId){
            $errorMsg = '|Невалиден запис|*!';
        }

        $rememberedTaskRec = Mode::get('rememberedTask');
        $refTaskRec = $this->fetch($refTaskId);
        if(!$this->haveRightFor('pastefromclipboard', (object)array('refTaskId' => $refTaskRec->id, 'place' => $place))){
            $errorMsg = '|Нямате права|*!';
        }

        if(!empty($errorMsg)){
            core_Statuses::newStatus($errorMsg, 'error');
        } else {
            $updateFields = arr::make('orderByAssetId,modifiedOn,modifiedBy');

            if($place == 'after'){
                $startAfterId = $refTaskRec->id;
            } else {
                $startAfterId = $this->getPrevOrNextTask($refTaskRec);
            }

            $msgPart = "|е преместена след|* #{$this->getHandle($startAfterId)}";
            if(empty($startAfterId)){
                $msgPart = "|е преместена преди|* #{$this->getHandle($refTaskRec->id)}";
            }

            // Ако оборудването е различно - подменя се
            $assetIsChanged = false;
            if($rememberedTaskRec->assetId != $refTaskRec->assetId){
                $assetIsChanged = true;
                $rememberedTaskRec->prevAssetId = $rememberedTaskRec->assetId;
                $rememberedTaskRec->assetId = $refTaskRec->assetId;
                $updateFields[] = 'assetId';
                $updateFields[] = 'prevAssetId';
            }

            // След коя операция ще започне тази
            $rememberedTaskRec->startAfter = $startAfterId;
            $rememberedTaskRec->modifiedOn = dt::now();
            $rememberedTaskRec->modifiedBy = core_Users::getCurrent();
            $rememberedTaskRec->_isDragAndDrop = true;
            $this->save($rememberedTaskRec, $updateFields);

            // Ако е сменено оборудването се прави преподреждане на операциите от старото
            if($assetIsChanged){
                planning_AssetResources::reOrderTasks($rememberedTaskRec->prevAssetId);
                unset($this->reorderTasksInAssetId[$rememberedTaskRec->prevAssetId]);
                $this->logWrite("Сменено оборудване при поставяне от клипборда", $rememberedTaskRec->id);
            }

            // Преподреждане на операциите на новото оборудване
            planning_AssetResources::reOrderTasks($rememberedTaskRec->assetId);
            unset($this->reorderTasksInAssetId[$rememberedTaskRec->assetId]);
            $this->logWrite("Операцията е поставена от клипборда", $rememberedTaskRec->id);
            core_Statuses::newStatus("|*#{$this->getHandle($rememberedTaskRec->id)} {$msgPart}", 'notice', null, 180);

            Mode::setPermanent('rememberedTask', null);
        }

        $res = array();
        $res = $this->returnAjaxSuccessResponse($res);

        return $res;
    }


    /**
     * Запомняне на операцията в сесията
     */
    public function act_copy2clipboard()
    {
        // Проверка за права
        $errorMsg = null;
        if(!$this->haveRightFor('copy2clipboard')){
            $errorMsg = '|Нямате права|*!';
        }
        $id = Request::get('id', 'int');
        if(!$id){
            $errorMsg = '|Невалиден запис|*!';
        }

        $rec = $this->fetch($id);
        if(!$this->haveRightFor('copy2clipboard', $rec)){
            $errorMsg = '|Нямате права|*!';
        }

        if(!empty($errorMsg)){
            // Ако е имало грешки се показват
            core_Statuses::newStatus($errorMsg, 'error');
        } else {
            // Кеш на допустимите за избор оборудвания
            $rec->_allowableAssets = array();
            if($Driver = cat_Products::getDriver($rec->productId)) {
                $productionData = $Driver->getProductionData($rec->productId);
                if(is_array($productionData['fixedAssets'])){
                    $rec->_allowableAssets = $productionData['fixedAssets'];
                }
            }
            $rec->_allowableAssets = countR($rec->allowableAssets) ? $rec->allowableAssets : array_keys(planning_AssetResources::getByFolderId($rec->folderId, $rec->assetId, 'planning_Tasks', true));

            // Ако е нямало избраната операция се записва в сесията
            core_Statuses::newStatus("|*#{$this->getHandle($id)} |е запомнена в сесията", 'notice');
            Mode::setPermanent('rememberedTask', $rec);
        }

        $res = array();
        $res = $this->returnAjaxSuccessResponse($res);

        return $res;
    }


    /**
     * Какъв резултат да се върне при успешен ajax екшън
     *
     * @param array $res
     * @param boolean $refreshTable
     * @return mixed
     */
    private function returnAjaxSuccessResponse($res, $refreshTable = true)
    {
        // Затваря се контектстното меню ако е отворено
        $resObj = new stdClass();
        $resObj->func = 'closeContextMenu';
        $res[] = $resObj;

        // Форсиране на опресняване на лист таблицата
        $forwardRes = array();
        if($refreshTable){
            $divId = Request::get('divId');
            Request::push(array('id' => false));
            $refreshUrl = array('Ctr' => 'planning_Tasks', 'Act' => 'ajaxrefreshrows', 'divId' => $divId, 'refreshUrl' => toUrl(getCurrentUrl(), 'local'));
            $forwardRes = Request::forward($refreshUrl);
        }

        // Показване на статусите веднага
        $hitTime = Request::get('hitTime', 'int');
        $idleTime = Request::get('idleTime', 'int');
        $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
        $res = array_merge($res, $forwardRes, (array) $statusData);

        return $res;
    }
}
