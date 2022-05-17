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
    public $loadList = 'doc_plg_Prototype, doc_DocumentPlg, planning_plg_StateManager, plg_Sorting, planning_Wrapper, acc_plg_DocumentSummary, plg_Search, plg_Clone, plg_Printing, plg_RefreshRows, plg_LastUsedKeys, bgerp_plg_Blank';


    /**
     * На колко време да се рефрешва лист изгледа
     */
    public $refreshRowsTime = 3000;


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
     * Да се скрива ли филтъра по дата от лист изгледа
     */
    public $hidePeriodFilter = true;
    
    
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
    public $listFields = 'expectedTimeStart=Начало,title, progress, folderId, orderByAssetId, state, modifiedOn, modifiedBy, originId=@';
    
    
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
    public $canSingle = 'ceo,taskPlanning';


    /**
     * Кой може да го активира?
     */
    public $canActivate = 'ceo, taskPlanning';


    /**
     * Кой може да го активира?
     */
    public $canChangestate = 'ceo, taskPlanning';
    
    
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
    public $filterDateField = 'expectedTimeStart,activatedOn,createdOn';
    
    
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
    public $fieldsNotToClone = 'progress,totalWeight,scrappedQuantity,producedQuantity,totalQuantity,plannedQuantity,timeStart,timeEnd,timeDuration,systemId,orderByAssetId';
    
    
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
     * Брой записи на страница
     */
    public $listItemsPerPage = 20;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие,width=100%,silent,input=hidden');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'mandatory,caption=Производство->Артикул,removeAndRefreshForm=packagingId|measureId|quantityInPack|paramcat|plannedQuantity|indPackagingId|storeId|assetId|employees|labelPackagingId|labelQuantityInPack|labelType|labelTemplate|indTime,silent');
        $this->FLD('measureId', 'key(mvc=cat_UoM,select=name,select=shortName)', 'mandatory,caption=Производство->Мярка,removeAndRefreshForm=quantityInPack|plannedQuantity|labelPackagingId|indPackagingId,silent');
        $this->FLD('totalWeight', 'cat_type_Weight', 'caption=Общо тегло,input=none');
        $this->FLD('plannedQuantity', 'double(smartRound,Min=0)', 'mandatory,caption=Производство->Планирано');
        $this->FLD('quantityInPack', 'double', 'mandatory,caption=Производство->К-во в мярка,input=none');

        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Производство->Склад,input=none');
        $this->FLD('assetId', 'key(mvc=planning_AssetResources,select=name)', 'caption=Производство->Оборудване,silent,removeAndRefreshForm=orderByAssetId|startAfter|allowedInputProducts');
        $this->FLD('employees', 'keylist(mvc=crm_Persons,select=id,makeLinks,select2MinItems=20)', 'caption=Производство->Оператори');
        $this->FNC('startAfter', 'varchar', 'caption=Производство->Започва след,silent,placeholder=Първа');
        if(core_Packs::isInstalled('batch')){
            $this->FLD('followBatchesForFinalProduct', 'enum(yes=На производство по партида,no=Без отчитане)', 'caption=Производство->Отчитане,input=none');
        }
        $this->FLD('allowedInputProducts', 'enum(yes=Всички за влагане,no=Само посочените в операцията)', 'caption=Производство->Влагане');

        $this->FLD('labelPackagingId', 'key(mvc=cat_UoM,select=name)', 'caption=Етикиране->Опаковка,input=hidden,tdClass=small-field nowrap,placeholder=Няма,silent,removeAndRefreshForm=labelQuantityInPack|labelTemplate|indPackagingId|,oldFieldName=packagingId');
        $this->FLD('labelQuantityInPack', 'double(smartRound,Min=0)', 'caption=Етикиране->В опаковка,tdClass=small-field nowrap,input=hidden,oldFieldName=packagingQuantityInPack');
        $this->FLD('labelType', 'enum(print=Отпечатване,scan=Сканиране,both=Сканиране и отпечатване)', 'caption=Етикиране->Етикет,tdClass=small-field nowrap,notNull,value=both');
        $this->FLD('labelTemplate', 'key(mvc=label_Templates,select=title)', 'caption=Етикиране->Шаблон,tdClass=small-field nowrap,input=hidden');

        $this->FLD('indTime', 'planning_type_ProductionRate', 'caption=Нормиране->Норма,smartCenter');
        $this->FLD('indPackagingId', 'key(mvc=cat_UoM,select=name)', 'silent,class=w25,removeAndRefreshForm,caption=Нормиране->Опаковка,input=hidden,tdClass=small-field nowrap');
        $this->FLD('indTimeAllocation', 'enum(common=Общо,individual=Поотделно)', 'caption=Нормиране->Разпределяне,smartCenter,notNull,value=common');

        $this->FLD('showadditionalUom', 'enum(no=Изключено,yes=Включено,mandatory=Задължително)', 'caption=Отчитане на теглото->Режим,notNull,value=yes');
        $this->FLD('weightDeviationNotice', 'percent(suggestions=1 %|2 %|3 %)', 'caption=Отчитане на теглото->Отбелязване,unit=+/-,autohide');
        $this->FLD('weightDeviationWarning', 'percent(suggestions=1 %|2 %|3 %)', 'caption=Отчитане на теглото->Предупреждение,unit=+/-,autohide');
        $this->FLD('weightDeviationAverageWarning', 'percent(suggestions=1 %|2 %|3 %)', 'caption=Отчитане на теглото->Отклонение,unit=от средното +/-,autohide');

        $this->FLD('timeStart', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00,format=smartTime)', 'caption=Целеви времена->Начало, changable, tdClass=leftColImportant');
        $this->FLD('timeDuration', 'time', 'caption=Целеви времена->Продължителност,changable');
        $this->FLD('timeEnd', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00,format=smartTime)', 'caption=Целеви времена->Край,changable, tdClass=leftColImportant,formOrder=103');

        $this->FLD('expectedTimeStart', 'datetime', 'caption=Планирани времена->Начало,input=none');
        $this->FLD('expectedTimeEnd', 'datetime', 'caption=Планирани времена->Край,input=none');

        $this->FLD('totalQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Количество,after=labelPackagingId,input=none');
        $this->FLD('scrappedQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Брак,input=none');
        $this->FLD('producedQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Заскладено,input=none');

        $this->FLD('progress', 'percent', 'caption=Прогрес,input=none,notNull,value=0');
        $this->FLD('systemId', 'int', 'silent,input=hidden');
        $this->FLD('description', 'richtext(rows=2,bucket=Notes)', 'caption=Допълнително->Описание,autoHide');
        $this->FLD('orderByAssetId', 'double(smartRound)', 'silent,input=hidden,caption=Подредба,smartCenter');

        $this->setDbIndex('productId');
        $this->setDbIndex('assetId,orderByAssetId');
        $this->setDbIndex('assetId');
    }


    /**
     * Подготвя параметрите
     * 
     * @param stdClass $rec
     * @return stdClass
     */
    private static function prepareTaskParams($rec)
    {
        $d = new stdClass();
        $d->masterId = $rec->id;
        $d->masterClassId = planning_Tasks::getClassId();
        if ($rec->state == 'closed' || $rec->state == 'stopped' || $rec->state == 'rejected') {
            $d->noChange = true;
        }
        cat_products_Params::prepareParams($d);
        
        return $d;
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $data->paramData = cat_products_Params::prepareClassObjectParams($mvc, $data->rec);
        
        if(Mode::is('printworkcard')){
            $ownCompanyData = crm_Companies::fetchOwnCompany();
            $data->row->MyCompany = $ownCompanyData->companyVerb;
            
            $absoluteUrl = toUrl(array($mvc, 'single', $data->rec->id), 'absolute');
            $data->row->QR_CODE = barcode_Generator::getLink('qr', $absoluteUrl, array('width' => 87, 'height' => 87));
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        if(Mode::is('printworkcard')){
            $tpl = getTplFromFile('planning/tpl/SingleWorkCard.shtml');
        } else {
            $tpl->prepend(getTplFromFile('planning/tpl/TaskStatistic.shtml'), 'ABOVE_LETTER_HEAD');
        }
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
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    public static function recToVerbal_($rec, &$fields = '*')
    {
        static::fillGapsInRec($rec);
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
        $row->progress = "<span style='color:{$grey};'>{$row->progress}</span>";

        $origin = doc_Containers::getDocument($rec->originId);
        $row->originId = (isset($fields['-list'])) ? "<small>" . $origin->getShortHyperlink() . "</small>" : $origin->getHyperlink(true);
        $row->folderId = doc_Folders::getFolderTitle($rec->folderId);
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        
        foreach (array('plannedQuantity', 'totalQuantity', 'scrappedQuantity', 'producedQuantity') as $quantityFld) {
            $row->{$quantityFld} = ($rec->{$quantityFld}) ? $row->{$quantityFld} : 0;
            $row->{$quantityFld} = ht::styleNumber($row->{$quantityFld}, $rec->{$quantityFld});
        }
        
        if (isset($rec->storeId)) {
            $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        }

        // Проверяване на времената
        foreach (array('expectedTimeStart' => 'timeStart', 'expectedTimeEnd' => 'timeEnd') as $eTimeField => $timeField) {

            // Вербализиране на времената
            $DateTime = core_Type::getByName('datetime(format=d.m.y H:i)');
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

        $expectedDuration = dt::secsBetween($rec->expectedTimeEnd, $rec->expectedTimeStart);
        $row->expectedDuration = empty($expectedDuration) ? '<span class=quiet>N/A</span>' : core_Type::getByName('time(uom=hours)')->toVerbal($expectedDuration);

        // Показване на разширеното описание на артикула
        if (isset($fields['-single'])) {
            $row->toggleBtn = "<a href=\"javascript:toggleDisplay('{$rec->id}inf')\"  style=\"background-image:url(" . sbf('img/16/toggle1.png', "'") . ');" class=" plus-icon more-btn"> </a>';
            $row->productDescription = cat_Products::getAutoProductDesc($rec->productId, null, 'detailed', 'job');
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
                    $quantityInPackDefault = static::getDefaultQuantityInLabelPackagingId($rec->productId, $rec->measureId, $rec->labelPackagingId);
                    $quantityInPackDefault = "<span style='color:blue'>" . core_Type::getByName('double(smartRound)')->toVerbal($quantityInPackDefault) . "</span>";
                    $quantityInPackDefault = ht::createHint($quantityInPackDefault, 'От опаковката/мярката на артикула');
                    $row->labelQuantityInPack = $quantityInPackDefault;
                } else {
                    $row->labelQuantityInPack .= " {$row->measureId}";
                }
            }

            if(isset($rec->labelTemplate)){
                $row->labelTemplate = label_Templates::getHyperlink($rec->labelTemplate);
            } else {
                $row->labelTemplate = "<span class='quiet'>N/A</span>";
            }

            // Линк към отпечаванията ако има
            if(label_Prints::haveRightFor('list')){
                if($printCount = label_Prints::count("#classId = {$mvc->getClassId()} AND #objectId = {$rec->id}")){
                    $row->printCount = core_Type::getByName('int')->toVerbal($printCount);
                    $row->printCount = ht::createLink($row->printCount, array('label_Prints', 'list', 'classId' => $mvc->getClassId(), 'objectId' => $rec->id, 'ret_url' => true));
                }
            }

            $row->activatedOn = !empty($rec->activatedOn) ? $row->activatedOn : "<span class='quiet'>N/A</span>";
            $row->timeClosed = !empty($rec->timeClosed) ? $row->timeClosed : "<span class='quiet'>N/A</span>";
        }
        
        if (!empty($rec->employees)) {
            $row->employees = planning_Hr::getPersonsCodesArr($rec->employees, true);
            $row->employees = implode(', ', $row->employees);
        }
        
        if(empty($rec->indTime)){
            $row->indTime = "<span class='quiet'>N/A</span>";
        }

        // Ако има избрано оборудване
        if(isset($rec->assetId)){
            $row->assetId = planning_AssetResources::getHyperlink($rec->assetId, true);
            if(haveRole('debug')){
                $row->assetId = ht::createHint($row->assetId, "Подредба|*: {$row->orderByAssetId}", 'img/16/bug.png');
            }

            if(!in_array($rec->state, array('closed', 'rejected'))){

                // Показва се след коя ще започне
                $startAfter = $mvc->getStartAfter($rec);
                if(isset($startAfter)){
                    $row->startAfter = $mvc->getHyperlink($startAfter, true);
                } else {
                    $row->startAfter = tr('Първа за оборудването');
                }
            }
        }

        $canStore = cat_products::fetchField($rec->productId, 'canStore');
        $row->producedCaption = ($canStore == 'yes') ? tr('Заскладено') : tr('Изпълнено');

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
        $title = cat_Products::getTitleById($rec->productId, $escaped);
        $title = "Opr{$rec->id} - " . $title;
        
        return $title;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        
        if ($form->isSubmitted()) {

            $packRec = cat_products_Packagings::getPack($rec->productId, $rec->measureId);
            $rec->quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
            $rec->title = cat_Products::getTitleById($rec->productId);
            
            if (empty($rec->id)) {
                $description = cat_Products::fetchField($form->rec->productId, 'info');
                if (!empty($description)) {
                    $rec->description = $description;
                }
            }

            if($form->cmd == 'save_pending'){
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

            if(!$form->gotErrors()){
                $rec->_fromForm = true;
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
        if($rec->showadditionalUom != 'yes'){
            unset($row->totalWeight);
        } elseif(empty($rec->totalWeight)) {
            $row->totalWeight = "<span class='quiet'>N/A</span>";
        }

        $canStore = cat_Products::fetchField($rec->productId, 'canStore');
        if($canStore == 'yes'){
            $resArr['additional'] = array('name' => tr('Изчисляване на тегло'), 'val' => tr("|*<table>
                <!--ET_BEGIN totalWeight--><tr><td style='font-weight:normal'>|Общо тегло|*:</td><td>[#totalWeight#]</td></tr><!--ET_END totalWeight-->
                <tr><td style='font-weight:normal'>|Режим|*:</td><td>[#showadditionalUom#]</td></tr>
                <!--ET_BEGIN weightDeviationNotice--><tr><td style='font-weight:normal'>|Отбелязване|*:</td><td>+/- [#weightDeviationNotice#]</td></tr><!--ET_END weightDeviationNotice-->
                <tr><td style='font-weight:normal'>|Предупреждение|*:</td><td>+/- [#weightDeviationWarning#]</td></tr>
                <!--ET_BEGIN weightDeviationAverageWarning--><tr><td style='font-weight:normal'>|Спрямо средното|*:</td><td>+/- [#weightDeviationAverageWarning#]</td></tr><!--ET_END weightDeviationAverageWarning-->
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
                <tr><td style='font-weight:normal'>|Опаковка|*:</td><td>[#indPackagingId#]</td></tr>
                <tr><td style='font-weight:normal'>|Разпределяне|*:</td><td>[#indTimeAllocation#]</td></tr>
                </table>"));

        if(empty($rec->weightDeviationWarning)){
            $row->weightDeviationWarning = core_Type::getByName('percent')->toVerbal(planning_Setup::get('TASK_WEIGHT_TOLERANCE_WARNING'));
        }

        if(isset($rec->indPackagingId) && !empty($rec->indTime)){
            $row->indTime = core_Type::getByName("planning_type_ProductionRate(measureId={$rec->indPackagingId})")->toVerbal($rec->indTime);
        }

        if($Driver = cat_Products::getDriver($rec->productId)){

            // Има ли параметри за планиране
            $productionData = $Driver->getProductionData($rec->productId);
            if(is_array($productionData['planningParams'])){
                $jobRec = doc_Containers::getDocument($rec->originId);
                $jobProductId = $jobRec->fetchField('productId');
                $productParams = cat_Products::getParams($jobProductId, null, true);
                $displayParams = array_intersect_key($productParams, $productionData['planningParams']);

                // Има ли от параметрите на артикула за задание, такива които да се покажат
                if(countR($displayParams)){
                    $resArr['params'] = array('name' => tr('От') . ": " . cat_Products::getHyperlink($jobProductId));
                    $displayParamsHtml = "<table>";

                    // Ако има показват се
                    foreach ($displayParams as $pId => $pVal){
                        $pName = tr(cat_Params::getTitleById($pId));
                        $pSuffix = tr(cat_Params::getVerbal($pId, 'suffix'));
                        if(!empty($pSuffix)){
                            $pVal = "{$pVal} {$pSuffix}";
                        }
                        $displayParamsHtml .= "<tr><td style='font-weight:normal'>{$pName}:</td><td>{$pVal}</td></tr>";
                    }
                    $displayParamsHtml .= "</table>";
                    $resArr['params']['val'] = $displayParamsHtml;
                }
            }
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
        $updateFields = 'totalQuantity,totalWeight,scrappedQuantity,producedQuantity,progress,modifiedOn,modifiedBy';
        
        // Колко е общото к-во досега
        $dQuery = planning_ProductionTaskDetails::getQuery();
        $dQuery->where("#taskId = {$rec->id} AND #productId = {$rec->productId} AND #type = 'production' AND #state != 'rejected'");
        $dQuery->XPR('sumQuantity', 'double', "ROUND(SUM(#quantity), 5)");
        $dQuery->XPR('sumWeight', 'double', 'SUM(#weight)');
        $dQuery->XPR('sumScrappedQuantity', 'double', "ROUND(SUM(#scrappedQuantity), 5)");
        $dQuery->show('sumQuantity,sumWeight,sumScrappedQuantity');

        // Преизчисляваме общото тегло
        $res = $dQuery->fetch();
        $rec->totalWeight = $res->sumWeight;
        $rec->totalQuantity = $res->sumQuantity;
        $rec->scrappedQuantity = $res->sumScrappedQuantity;
        
        // Изчисляваме колко % от зададеното количество е направено
        if (!empty($rec->plannedQuantity)) {
            $percent = ($rec->totalQuantity - $rec->scrappedQuantity) / $rec->plannedQuantity;
            $rec->progress = round($percent, 2);
        }
        
        $rec->progress = max(array($rec->progress, 0));
        
        $noteQuery = planning_DirectProductionNote::getQuery();
        $noteQuery->where("#productId = {$rec->productId} AND #state = 'active' AND #originId = {$rec->containerId}");
        $noteQuery->XPR('totalQuantity', 'double', 'SUM(#quantity)');
        $noteQuery->show('totalQuantity');
        $producedQuantity = $noteQuery->fetch()->totalQuantity;
       
        // Обновяване на произведеното по заданието
        if($producedQuantity != $rec->producedQuantity){
            planning_Jobs::updateProducedQuantity($rec->originId);
        }
        
        $rec->producedQuantity = $producedQuantity;
        
        // Ако няма зададено начало, тогава се записва времето на първо добавения запис
        if(empty($rec->timeStart) && !isset($rec->timeDuration, $rec->timeEnd) && planning_ProductionTaskDetails::count("#taskId = {$rec->id}")){
            $rec->timeStart = dt::now();
            $updateFields .= ',timeStart';
        }

        // При първо добавяне на прогрес, ако е в заявка - се активира автоматично
        if($rec->state == 'pending' && planning_ProductionTaskDetails::count("#taskId = {$rec->id}")){
            planning_plg_StateManager::changeState($this, $rec, 'activate');
            $this->logWrite('Активиране при прогрес', $rec->id);
            core_Statuses::newStatus('Операцията е активирана след добавяне на прогрес|*!');
        }

        return $this->save($rec, $updateFields);
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
                if (in_array($state, array('closed', 'rejected', 'draft', 'stopped'))) {
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
            if (!in_array($rec->state, array('pending'))) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * След успешен запис
     */
    protected static function on_AfterCreate($mvc, &$rec)
    {
        // Ако записа е създаден с клониране не се прави нищо
        if ($rec->_isClone === true) {
            
            return;
        }
        
        if (isset($rec->originId)) {
            $originDoc = doc_Containers::getDocument($rec->originId);
            $originRec = $originDoc->fetch();
            
            // Ако е по източник
            if (isset($rec->systemId)) {
                $tasks = cat_Products::getDefaultProductionTasks($originRec, $originRec->quantity);
                if (isset($tasks[$rec->systemId])) {
                    $def = $tasks[$rec->systemId];
                    
                    // Намираме на коя дефолтна операция отговаря и извличаме продуктите от нея
                    foreach (array('production' => 'product', 'input' => 'input', 'waste' => 'waste') as $var => $type) {
                        if (is_array($def->products[$var])) {
                            foreach ($def->products[$var] as $p) {
                                $p = (object) $p;
                                $nRec = new stdClass();
                                $nRec->taskId = $rec->id;
                                $nRec->packagingId = $p->packagingId;
                                $nRec->quantityInPack = $p->quantityInPack;
                                $nRec->plannedQuantity = $p->packQuantity * $rec->plannedQuantity;
                                $nRec->productId = $p->productId;
                                $nRec->type = $type;
                                $nRec->storeId = $rec->storeId;
                                
                                planning_ProductionTaskProducts::save($nRec);
                            }
                        }
                    }
                }
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

        if($rec->showadditionalUom)
        if (isset($rec->systemId)) {
            $form->setField('prototypeId', 'input=none');
        }
        if (empty($rec->id)) {
            if ($folderId = Request::get('folderId', 'key(mvc=doc_Folders)')) {
                unset($rec->threadId);
                $rec->folderId = $folderId;
            }
        }
        
        // За произвеждане може да се избере само артикула от заданието
        $origin = doc_Containers::getDocument($rec->originId);
        $originRec = $origin->fetch();
        
        // Добавяне на допустимите опции
        $options = planning_Centers::getManifacturableOptions($rec->folderId);
        if(!array_key_exists($originRec->productId, $options)){
            $options = array("{$originRec->productId}" => cat_Products::getTitleById($originRec->productId, false)) + $options;
        }
        if(isset($rec->productId) && !array_key_exists($rec->productId, $options)){
            $options = array("{$rec->productId}" => cat_Products::getTitleById($rec->productId, false)) + $options;
        }

        $form->setOptions('productId', $options);
        $tasks = cat_Products::getDefaultProductionTasks($originRec, $originRec->quantity);
        $form->setDefault('labelType', 'both');

        if (isset($rec->systemId, $tasks[$rec->systemId]) && empty($rec->id)) {
            $taskData = (array)$tasks[$rec->systemId];

            unset($taskData['products']);
            foreach ($taskData as $fieldName => $defaultValue) {
                $form->setDefault($fieldName, $defaultValue);
            }
            if(!empty($taskData['fixedAssets'])){
                $fixedAssetOptions = keylist::toArray($taskData['fixedAssets']);
            }
            $form->setReadOnly('productId');
        }

        // Ако не е указано друго, е артикула от заданието
        $form->setDefault('productId', $originRec->productId);
        
        if (isset($rec->productId)) {
            $productRec = cat_Products::fetch($rec->productId, 'canConvert,canStore,measureId');
            if(core_Packs::isInstalled('batch')){
                if(batch_Defs::getBatchDef($rec->productId)){
                    $form->setField('followBatchesForFinalProduct', 'input');
                }
            }

            if($rec->productId == $originRec->productId){

                // Ако артикула е този от заданието то допустимите мерки са тази от заданието и втората му мярка ако има
                if(cat_UoM::fetchField($originRec->packagingId, 'type') == 'uom'){
                    $measureOptions[$originRec->packagingId] = cat_UoM::getTitleById($originRec->packagingId, false);
                } else {
                    $measureOptions[$productRec->measureId] = cat_UoM::getTitleById($productRec->measureId, false);
                }
                if(isset($originRec->secondMeasureId)){
                    $secondMeasureId = ($originRec->secondMeasureId == $originRec->packagingId) ? $productRec->measureId : $originRec->secondMeasureId;
                    $measureOptions[$secondMeasureId] = cat_UoM::getTitleById($secondMeasureId, false);
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

            // Ако не е системна, взима се дефолта от драйвера
            if(empty($rec->systemId) && empty($rec->id)){
                if($Driver = cat_Products::getDriver($rec->productId)){
                    $productionData = $Driver->getProductionData($rec->productId);
                    $defFields = arr::make(array('employees', 'labelPackagingId', 'labelQuantityInPack', 'labelType', 'labelTemplate'), true);
                    $defFields['storeId'] = 'storeIn';
                    $defFields['indTime'] = 'norm';
                    foreach ($defFields as $fld => $val){
                        $form->setDefault($fld, $productionData[$val]);
                    }
                    if(isset($productionData['fixedAssets'])){
                        $fixedAssetOptions = $productionData['fixedAssets'];
                    }
                }
            }

            if(countR($fixedAssetOptions)){
                $cloneArr = $fixedAssetOptions;
                $fixedAssetOptions = array();
                array_walk($cloneArr, function($a) use (&$fixedAssetOptions){$fixedAssetOptions[$a] = planning_AssetResources::getTitleById($a, false);});
            }

            if (empty($rec->id)) {
                cat_products_Params::addProductParamsToForm($mvc, $rec->id, $rec->productId, $form);
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
                $packs = array($rec->measureId => cat_UoM::getTitleById($rec->measureId, false)) + cat_products_Packagings::getOnlyPacks($rec->productId);
                $form->setOptions('labelPackagingId', array('' => '') + $packs);
                $form->setOptions('indPackagingId', $packs);

                $form->setField('storeId', 'input');
                $form->setField('labelPackagingId', 'input');
                $form->setField('indPackagingId', 'input');

                $defaultShowAdditionalUom = planning_Setup::get('TASK_WEIGHT_MODE');
                $form->setField('weightDeviationWarning', "placeholder=" . core_Type::getByName('percent')->toVerbal(planning_Setup::get('TASK_WEIGHT_TOLERANCE_WARNING')));
                $form->setDefault('showadditionalUom', $defaultShowAdditionalUom);

                if($defaultShowAdditionalUom == $rec->showadditionalUom){
                    $form->setField('showadditionalUom', 'autohide=any');
                }
            } else {
                $form->setField('showadditionalUom', 'input=none');
                $form->setField('weightDeviationNotice', 'input=none');
                $form->setField('weightDeviationWarning', 'input=none');
                $form->setField('weightDeviationAverageWarning', 'input=none');
                $form->setDefault('indPackagingId', $rec->measureId);
            }
            
            if($measuresCount == 1){
                $measureShort = cat_UoM::getShortName($rec->measureId);
                $form->setField('plannedQuantity', "unit={$measureShort}");
            }

            if(isset($rec->labelPackagingId)){
                $form->setField('labelQuantityInPack', 'input');
                $form->setDefault('indPackagingId', $rec->labelPackagingId);
                $quantityInPackDefault = static::getDefaultQuantityInLabelPackagingId($rec->productId, $rec->measureId, $rec->labelPackagingId);
                $form->setField('labelQuantityInPack', "placeholder={$quantityInPackDefault}");

                $templateOptions = static::getAllAvailableLabelTemplates($rec->labelTemplate);
                $form->setField('labelTemplate', 'input,mandatory');
                $form->setOptions('labelTemplate', array('' => '') + $templateOptions);
                $defaultIndPackagingId = $rec->labelPackagingId;
            } else {
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
        }

        // Добавяне на наличните за избор оборудвания
        $fixedAssetOptions = countR($fixedAssetOptions) ? $fixedAssetOptions : planning_AssetResources::getByFolderId($rec->folderId, $rec->assetId, 'planning_Tasks', true);
        $countAssets = countR($fixedAssetOptions);

        if($countAssets){
            $form->setField('assetId', 'input');
            $form->setOptions('assetId', array('' => '') + $fixedAssetOptions);
            if($countAssets == 1 && $form->cmd != 'refresh' && empty($rec->id)){
                $form->setDefault('assetId', key($fixedAssetOptions));
            }
        } else {
            $form->setField('assetId', 'input=none');
        }

        // Добавяне на достъпните за избор оператори
        $employees = planning_Hr::getByFolderId($rec->folderId, $rec->employees);
        if(countR($employees)){
            $form->setField('employees', 'input');
            $form->setSuggestions('employees', $employees);
        } else {
            $form->setField('employees', 'input=none');
        }

        // Ако има избрано оборудване се добавя след края на коя операция да започне тази
        $form->input('assetId', 'silent');
        if(isset($rec->assetId)){
            if($data->action != 'clone'){
                $assetTasks = planning_AssetResources::getAssetTaskOptions($rec->assetId, true);
                unset($assetTasks[$rec->id]);
                $taskOptions = array();
                foreach ($assetTasks as $tRec){
                    $job = doc_Containers::getDocument($tRec->originId);
                    $jobTitle = str::limitLen($job->getRecTitle(), 42);
                    $productTitle = str::limitLen($mvc->getVerbal($tRec->id, 'productId'), 42);
                    $title = "#Opr{$tRec->id}/{$jobTitle}/{$productTitle}";
                    $taskOptions[$tRec->id] = $title;
                }

                $form->setField('startAfter', 'input');
                if(countR($taskOptions)){
                    $form->setOptions('startAfter', array('' => '') + $taskOptions);
                    $form->setDefault('startAfter', $mvc->getStartAfter($rec));
                } else {
                    $form->setReadOnly('startAfter');
                }
            }
            $form->setDefault('allowedInputProducts', 'yes');
        } else {
            $form->setDefault('allowedInputProducts', 'no');
            $form->setField('allowedInputProducts', 'input=hidden');
            $form->setField('startAfter', 'input=none');
        }

        if (isset($rec->id)) {
            $form->setReadOnly('productId');
            if(planning_ProductionTaskDetails::fetchField("#taskId = {$rec->id}")){
                $form->setReadOnly('labelPackagingId');
                $form->setReadOnly('labelQuantityInPack', $rec->labelQuantityInPack);
                $form->setReadOnly('measureId');
            }

            if(planning_ProductionTaskDetails::fetchField("#taskId = {$rec->id} AND #type = 'input'")){
                $form->setReadOnly('allowedInputProducts');
            }
        }
    }


    /**
     * Изчисляване след коя задача ще се изпълни тази
     *
     * @param stdClass $rec
     * @return null|int
     */
    private function getStartAfter($rec)
    {
        if(empty($rec->assetId)) return null;

        $query = planning_Tasks::getQuery();
        $query->where("#assetId = {$rec->assetId} AND #orderByAssetId IS NOT NULL");
        $query->orderBy('orderByAssetId', 'DESC');
        $query->show('id');
        $query->limit(1);

        if(isset($rec->id) && isset($rec->orderByAssetId)){
            $query->where("#orderByAssetId < {$rec->orderByAssetId}");
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
        $query->orderBy('orderByDate', 'ASC');
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
        $fields = arr::make('expectedTimeStart=Начало,title=Операция,progress=Прогрес,plannedQuantity=Планирано,totalQuantity=Произведено,producedQuantity=Заскладено,costsCount=Разходи, assetId=Оборудване,info=@info');
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
        $data->query->XPR('orderByDate', 'datetime', "COALESCE(#expectedTimeStart, 9999999999999)");
        $orderByField = 'orderByDate';

        // Добавят се за избор само използваните в ПО оборудвания
        $assetInTasks = planning_AssetResources::getUsedAssetsInTasks($data->listFilter->rec->folder);
        if(countR($assetInTasks)){
            $data->listFilter->setField('assetId', 'caption=Оборудване,autoFilter');
            $data->listFilter->setOptions('assetId', array('' => '') + $assetInTasks);
            $data->listFilter->showFields .= ',assetId';
            $data->listFilter->input('assetId');
        }

        if($filter = $data->listFilter->rec){
            if (isset($filter->assetId)) {
                $mvc->listItemsPerPage = 200;
                $data->query->where("#assetId = {$filter->assetId}");
                $data->query->orderBy('orderByDate', 'ASC');
                $orderByField = 'orderByAssetId';
            } else {
                unset($data->listFields['orderByAssetId']);
            }
        }
        
        if (!Request::get('Rejected', 'int')) {
            $data->listFilter->setOptions('state', arr::make('activeAndPending=Заявки+Активни+Събудени+Спрени,draft=Чернова,active=Активен,closed=Приключен, stopped=Спрян, wakeup=Събуден,waiting=Чакащо,pending=Заявка,all=Всички', true));
            $data->listFilter->showFields .= ',state';
            $data->listFilter->input('state');
            $data->listFilter->setDefault('state', 'activeAndPending');

            if ($state = $data->listFilter->rec->state) {
                if ($state == 'activeAndPending') {
                    $data->query->where("#state IN ('active', 'pending', 'wakeup', 'stopped')");
                } elseif($state != 'all') {
                    $data->query->where("#state = '{$state}'");
                }
            }
        }

        $data->query->orderBy($orderByField, 'ASC');
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
     * Ако са въведени две от времената (начало, продължителност, край) а третото е празно, изчисляваме го.
     * ако е въведено само едно време или всички не правим нищо
     *
     * @param stdClass $rec - записа който ще попълним
     *
     * @return void
     */
    protected static function fillGapsInRec(&$rec)
    {
        if (isset($rec->timeStart, $rec->timeDuration) && empty($rec->timeEnd)) {
            
            // Ако има начало и продължителност, изчисляваме края
            $rec->timeEnd = dt::addSecs($rec->timeDuration, $rec->timeStart);
        } elseif (isset($rec->timeStart, $rec->timeEnd) && empty($rec->timeDuration)) {
            
            // Ако има начало и край, изчисляваме продължителността
            $rec->timeDuration = strtotime($rec->timeEnd) - strtotime($rec->timeStart);
        } elseif (isset($rec->timeDuration, $rec->timeEnd) && empty($rec->timeStart)) {
            
            // Ако има продължителност и край, изчисляваме началото
            $rec->timeStart = dt::addSecs(-1 * $rec->timeDuration, $rec->timeEnd);
        }
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        if (empty($rec->id)) {
            
            return;
        }
        
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
        expect($jobRec = planning_Jobs::fetchRec($jobId));
        
        $query = planning_Tasks::getQuery();
        $query->XPR('sum', 'double', 'SUM((COALESCE(#totalQuantity, 0) - COALESCE(#scrappedQuantity, 0)) * #quantityInPack)');
        $query->where("#originId = {$jobRec->containerId} AND #productId = {$jobRec->productId}");
        $query->where("#state != 'rejected' AND #state != 'pending'");
        $query->show('sum');

        $sum = $query->fetch()->sum;
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
     * Преди подготовка на сингъла
     */
    protected static function on_BeforePrepareSingle(core_Mvc $mvc, &$res, $data)
    {
        if (Request::get('printworkcard', 'int')) {
            Mode::set('printworkcard', true);
        }
    }
    
    
    /**
     * Поставя бутони за генериране на други банкови документи възоснова
     * на този, само ако документа е "чернова"
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;

        if ($mvc->haveRightFor('single', $rec) && $rec->state != 'rejected') {
            $data->toolbar->addBtn('Р. карта', array($mvc, 'single', $rec->id, 'ret_url' => true, 'Printing' => true, 'printworkcard' => true), null, 'target=_blank,ef_icon=img/16/print_go.png,title=Печат на работна карта за производствената операция,row=2');
        }

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
            if($retUrl['Ctr'] == 'planning_Jobs' && $retUrl['Act'] == 'selectTaskAction'){
                $data->retUrl = $retUrl;
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

        // Ако е филтрирано по център на дейност
        if ($data->listFilter->rec->folder) {
            $Cover = doc_Folders::getCover($data->listFilter->rec->folder);
            if($Cover->isInstanceOf('planning_Centers')){
                $data->listFieldsParams = keylist::toArray($Cover->fetchField('planningParams'));

                // и той има избрани параметри за планиране, добавят се в таблицата
                $paramFields = array();
                foreach ($data->listFieldsParams as $paramId) {
                    $paramFields["param_{$paramId}"] = "|Параметри за планиране|*->|*<small>" . cat_Params::getVerbal($paramId, 'typeExt') . "</small>";
                    $data->listTableMvc->FNC("param_{$paramId}", 'varchar', 'smartCenter');
                }
                arr::placeInAssocArray($data->listFields, $paramFields, null, 'progress');
            }
        }

        $enableReorder = isset($data->listFilter->rec->assetId) &&  in_array($data->listFilter->rec->state, array('activeAndPending', 'pending', 'active', 'wakeup')) && countR($data->recs) > 1;
        foreach ($rows as $id => $row) {
            $rec = $data->recs[$id];

            // Добавяне на дата атрибуто за да може с драг и дроп да се преподреждат ПО в списъка
            $row->ROW_ATTR['data-id'] = $rec->id;
            if($enableReorder){
                if($mvc->haveRightFor('reordertask', $rec)){
                    $reorderUrl = toUrl(array($mvc, 'reordertask', 'tId' => $rec->id, 'ret_url' => true), 'local');
                    $row->title = ht::createElement('span', array('data-currentId' => $rec->id, 'data-url' => $reorderUrl, 'class' => 'draggable', 'title' => 'Може да преместите задачата след друга|*!'), $row->title);
                }
            }

            if(countR($data->listFieldsParams)){

                // От ПО се намира артикула от заданието ѝ, извличат се неговите параметри
                // които са посочени за филтриране от центъра и се показват в таблицата
                $origin = doc_Containers::getDocument($rec->originId);
                $jobProductId = $origin->fetchField('productId');
                $jobParams = cat_Products::getParams($jobProductId, null, true);
                $displayParams = array_intersect_key($jobParams, $data->listFieldsParams);
                foreach ($displayParams as $pId => $pValue){
                    $pSuffix = cat_Params::getVerbal($pId, 'suffix');
                    $row->{"param_{$pId}"} = $pValue;
                    if(!empty($pSuffix)){
                        $row->{"param_{$pId}"} .= " {$pSuffix}";
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

            // Ако не е минато през формата
            if(!$rec->_fromForm && !$rec->_isDragAndDrop){

                // Ако няма начало изчислява се да започне след последната
                if($rec->state == 'active' && $rec->brState == 'pending'){
                    // При активиране от чернова - намърдва се най-накрая
                    $rec->startAfter = $mvc->getStartAfter($rec);
                } elseif($rec->state == 'rejected' || ($rec->state == 'closed' && in_array($rec->brState, array('stopped', 'active', 'wakeup')))){

                    // При оттегляне изчезва от номерацията
                    $rec->orderByAssetId = $rec->startAfter = null;
                } elseif(in_array($rec->state, array('pending', 'active', 'wakeup')) && in_array($rec->brState, array('rejected', 'closed'))){

                    // При възстановяване в намърдва се най-накрая
                    $rec->startAfter = $mvc->getStartAfter($rec);
                } elseif($rec->state == 'pending' && in_array($rec->brState, array('draft', 'waiting'))) {

                    // Ако става на заявка от чакащо/чернова
                    $rec->startAfter = $mvc->getStartAfter($rec);
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

            $exRec = $mvc->fetch($rec->id, 'orderByAssetId,assetId', false);
            if($rec->orderByAssetId != $exRec->orderByAssetId){
                $mvc->save_($rec, 'orderByAssetId');
                $mvc->reorderTasksInAssetId[$rec->assetId] = $rec->assetId;
            }

            if(isset($exRec->assetId) && $rec->assetId != $exRec->assetId){
                $mvc->reorderTasksInAssetId[$exRec->assetId] = $exRec->assetId;
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
                planning_AssetResources::reOrderTasks($assetId);
            }
        }
    }


    /**
     * След рендиране на лист таблицата
     */
    protected static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
        // Включване на драг и дроп ако има избрано оборудване
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

        // Форсиране на опресняване на лист таблицата
        $divId = Request::get('divId');
        Request::push(array('id' => false));
        $test = array('Ctr' => 'planning_Tasks', 'Act' => 'ajaxrefreshrows', 'divId' => $divId, 'refreshUrl' => toUrl(getCurrentUrl(), 'local'));
        $forwardRes = Request::forward($test);

        // Моментно показване на статусите
        $hitTime = Request::get('hitTime', 'int');
        $idleTime = Request::get('idleTime', 'int');
        $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
        $res = array_merge($forwardRes, (array) $statusData);

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
            $form->toolbar->renameBtn('btnPending', 'Запис');
            $form->toolbar->setBtnOrder('btnPending', '1');
            $form->toolbar->setBtnOrder('save', '2');
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
    }
}
