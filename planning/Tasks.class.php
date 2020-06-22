<?php


/**
 * Мениджър на Производствени операции
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
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
    public $searchFields = 'title,fixedAssets,description,productId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'doc_plg_Prototype, doc_DocumentPlg, planning_plg_StateManager, planning_Wrapper, acc_plg_DocumentSummary, plg_Search, plg_Clone, plg_Printing, plg_RowTools2, plg_LastUsedKeys, bgerp_plg_Blank';
    
    
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
     * Поле за начало на търсенето
     */
    public $filterFieldDateFrom = 'timeStart';
    
    
    /**
     * Поле за крайна дата на търсене
     */
    public $filterFieldDateTo = 'timeEnd';
    
    
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
    public $listFields = 'title, progress, folderId, state, modifiedOn, modifiedBy';
    
    
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
    public $filterDateField = 'expectedTimeStart,timeStart,createdOn';
    
    
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
    public $fieldsNotToClone = 'progress,totalWeight,scrappedQuantity,producedQuantity,inputInTask,totalQuantity,plannedQuantity';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'fixedAssets';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'expectedTimeStart';
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'barcode_SearchIntf';
    
    
    /**
     * Да се показват ли във филтъра по дата и NULL записите
     * 
     * @see acc_plg_DocumentSummary
     */
    public $showNullDateFields = true;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие,width=100%,silent,input=hidden');
        $this->FLD('totalWeight', 'cat_type_Weight', 'caption=Общо тегло,input=none');
        
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'mandatory,caption=Производство->Артикул,removeAndRefreshForm=packagingId|measureId|quantityInPack|inputInTask|paramcat|plannedQuantity|indPackagingId,silent');
        $this->FLD('measureId', 'key(mvc=cat_UoM,select=name,select=shortName)', 'mandatory,caption=Производство->Мярка,removeAndRefreshForm=quantityInPack|plannedQuantity|packagingId|indPackagingId,silent');
        $this->FLD('plannedQuantity', 'double(smartRound,Min=0)', 'mandatory,caption=Производство->Планирано');
        $this->FLD('quantityInPack', 'double', 'mandatory,caption=Производство->К-во в мярка,input=none');
        
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Производство->Склад,input=none');
        $this->FLD('fixedAssets', 'keylist(mvc=planning_AssetResources,select=name,makeLinks=hyperlink)', 'caption=Производство->Оборудване');
        $this->FLD('employees', 'keylist(mvc=crm_Persons,select=id,makeLinks)', 'caption=Производство->Оператори');
        
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=name)', 'caption=Етикиране->Опаковка,input=none,tdClass=small-field nowrap,placeholder=Няма');
        $this->FLD('labelType', 'enum(print=Отпечатване,scan=Сканиране,both=Сканиране и отпечатване)', 'caption=Етикиране->Етикет,tdClass=small-field nowrap,notNull,value=both');
        
        $this->FLD('indTime', 'time(noSmart,decimals=2)', 'caption=Време за производство->Норма,smartCenter');
        $this->FLD('indPackagingId', 'key(mvc=cat_UoM,select=name)', 'caption=Време за производство->Опаковка,input=hidden,tdClass=small-field nowrap');
        $this->FLD('indTimeAllocation', 'enum(common=Общо,individual=Поотделно)', 'caption=Време за производство->Разпределяне,smartCenter,notNull,value=common');
        
        $this->FLD('showadditionalUom', 'enum(no=Изключено,yes=Включено,mandatory=Задължително)', 'caption=Отчитане на теглото->Режим,notNull,value=yes');
        $this->FLD('weightDeviationNotice', 'percent(suggestions=1 %|2 %|3 %)', 'caption=Отчитане на теглото->Отбелязване,unit=+/-');
        $this->FLD('weightDeviationWarning', 'percent(suggestions=1 %|2 %|3 %)', 'caption=Отчитане на теглото->Предупреждение,unit=+/-');
        $this->FLD('weightDeviationAverageWarning', 'percent(suggestions=1 %|2 %|3 %)', 'caption=Отчитане на теглото->Отклонение,unit=от средното +/-');
        
        $this->FLD('timeStart', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00,format=smartTime)', 'caption=Времена за планиране->Начало, changable, tdClass=leftColImportant');
        $this->FLD('timeDuration', 'time', 'caption=Времена за планиране->Продължителност,changable');
        $this->FLD('timeEnd', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00,format=smartTime)', 'caption=Времена за планиране->Край,changable, tdClass=leftColImportant,formOrder=103');
        
        $this->FLD('totalQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Количество,after=packagingId,input=none');
        $this->FLD('scrappedQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Брак,input=none');
        $this->FLD('producedQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Заскладено,input=none');
        
        $this->FLD('progress', 'percent', 'caption=Прогрес,input=none,notNull,value=0');
        $this->FNC('systemId', 'int', 'silent,input=hidden');
        $this->FLD('expectedTimeStart', 'datetime(format=smartTime)', 'input=hidden,caption=Очаквано начало');
        $this->FLD('inputInTask', 'int', 'caption=Произвеждане->Влагане в,input=none,after=indTime');
        $this->FLD('description', 'richtext(rows=2,bucket=Notes)', 'caption=Допълнително->Описание,autoHide');
        
        $this->setDbIndex('inputInTask');
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
        $data->paramData = self::prepareTaskParams($data->rec);
        
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
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
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
        $row->title = self::getHyperlink($rec->id, (isset($fields['-list']) ? true : false));
        
        $red = new color_Object('#FF0000');
        $blue = new color_Object('green');
        $grey = new color_Object('#bbb');
        
        $progressPx = min(200, round(200 * $rec->progress));
        $progressRemainPx = 200 - $progressPx;
        
        $color = ($rec->progress <= 1) ? $blue : $red;
        $row->progressBar = "<div style='white-space: nowrap; display: inline-block;'><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$color}; width:{$progressPx}px;'> </div><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$grey};width:{$progressRemainPx}px;'></div></div>";
        
        $grey->setGradient($color, $rec->progress);
        $row->progress = "<span style='color:{$grey};'>{$row->progress}</span>";
        
        if ($rec->timeEnd && ($rec->state != 'closed' && $rec->state != 'rejected')) {
            $remainingTime = dt::mysql2timestamp($rec->timeEnd) - time();
            $rec->remainingTime = cal_Tasks::roundTime($remainingTime);
            
            $typeTime = cls::get('type_Time');
            if ($rec->remainingTime > 0) {
                $row->remainingTime = ' (' . tr('остават') . ' ' . $typeTime->toVerbal($rec->remainingTime) . ')';
            } else {
                $row->remainingTime = ' (' . tr('просрочване с') . ' ' . $typeTime->toVerbal(-$rec->remainingTime) . ')';
            }
        }
        
        // Ако е изчислено очакваното начало и има продължителност, изчисляваме очаквания край
        if (isset($rec->expectedTimeStart, $rec->timeDuration)) {
            $rec->expectedTimeEnd = dt::addSecs($rec->timeDuration, $rec->expectedTimeStart);
            $row->expectedTimeEnd = $mvc->getFieldType('expectedTimeStart')->toVerbal($rec->expectedTimeEnd);
        }
        
        $origin = doc_Containers::getDocument($rec->originId);
        $row->originId = $origin->getLink();
        $row->originShortLink = $origin->getShortHyperlink();
        
        if (isset($rec->inputInTask)) {
            $row->inputInTask = planning_Tasks::getLink($rec->inputInTask);
        }
        
        $row->folderId = doc_Folders::getFolderTitle($rec->folderId);
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        
        foreach (array('plannedQuantity', 'totalQuantity', 'scrappedQuantity', 'producedQuantity') as $quantityFld) {
            $row->{$quantityFld} = ($rec->{$quantityFld}) ? $row->{$quantityFld} : 0;
            $row->{$quantityFld} = ht::styleNumber($row->{$quantityFld}, $rec->{$quantityFld});
        }
        
        if (isset($rec->storeId)) {
            $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        }
        
        // Ако няма зададено очаквано начало и край, се приема, че са стандартните
        $rec->expectedTimeStart = ($rec->expectedTimeStart) ? $rec->expectedTimeStart : ((isset($rec->timeStart)) ? $rec->timeStart : null);
        $rec->expectedTimeEnd = ($rec->expectedTimeEnd) ? $rec->expectedTimeEnd : ((isset($rec->timeEnd)) ? $rec->timeEnd : null);
        
        // Проверяване на времената
        foreach (array('expectedTimeStart' => 'timeStart', 'expectedTimeEnd' => 'timeEnd') as $eTimeField => $timeField) {
            
            // Вербализиране на времената
            $DateTime = core_Type::getByName('datetime(format=d.m H:i)');
            $row->{$timeField} = $DateTime->toVerbal($rec->{$timeField});
            $row->{$eTimeField} = $DateTime->toVerbal($rec->{$eTimeField});
            
            // Ако има очаквано и оригинално време
            if (isset($rec->{$eTimeField}, $rec->{$timeField})) {
                
                // Колко е разликата в минути между тях?
                $diffVerbal = null;
                $diff = dt::secsBetween($rec->{$eTimeField}, $rec->{$timeField});
                $diff = ceil($diff / 60);
                if ($diff != 0) {
                    $diffVerbal = cls::get('type_Int')->toVerbal($diff);
                    $diffVerbal = ($diff > 0) ? "<span class='red'>+{$diffVerbal}</span>" : "<span class='green'>{$diffVerbal}</span>";
                }
                
                // Ако има разлика
                if (isset($diffVerbal)) {
                    
                    // Показва се след очакваното време в скоби, с хинт оригиналната дата
                    $hint = 'Зададено|*: ' . $row->{$timeField};
                    $diffVerbal = ht::createHint($diffVerbal, $hint, 'notice', true, array('height' => '12', 'width' => '12'));
                    $row->{$eTimeField} .= " <span style='font-weight:normal'>({$diffVerbal})</span>";
                }
            }
        }
        
        if (isset($fields['-list']) && !isset($fields['-detail'])) {
            $row->title .= "<br><small>{$row->originShortLink}</small>";
        }
        
        
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
        }
        
        if (!empty($rec->employees)) {
            $row->employees = planning_Hr::getPersonsCodesArr($rec->employees, true);
            $row->employees = implode(', ', $row->employees);
        }
        
        if(empty($rec->indTime)){
            $row->indTime = "<span class='quiet'>N/A</span>";
        }
        
        if(empty($rec->packagingId)){
            $row->packagingId = "<span class='quiet'>N/A</span>";
        }
        
        $canStore = cat_products::fetchField($rec->productId, 'canStore');
        $row->producedCaption = ($canStore == 'yes') ? tr('Заскладено') : tr('Изпълнено');
        
        return $row;
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow($id)
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
        $title = cat_Products::getVerbal($rec->productId, 'name');
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
            if ($rec->timeStart && $rec->timeEnd && ($rec->timeStart > $rec->timeEnd)) {
                $form->setError('timeEnd', 'Крайният срок трябва да е след началото на операцията');
            }
            if (!empty($rec->timeStart) && !empty($rec->timeDuration) && !empty($rec->timeEnd)) {
                if (strtotime(dt::addSecs($rec->timeDuration, $rec->timeStart)) != strtotime($rec->timeEnd)) {
                    $form->setWarning('timeStart,timeDuration,timeEnd', 'Въведеното начало плюс продължителността не отговарят на въведената крайната дата');
                }
            }
            
            // Може да се избират само оборудвания от една група
            if (isset($rec->fixedAssets)) {
                if (!planning_AssetGroups::haveSameGroup($rec->fixedAssets)) {
                    $form->setError('fixedAssets', 'Оборудванията са от различни групи');
                }
            }
            
            $packRec =cat_products_Packagings::getPack($rec->productId, $rec->measureId);
            $rec->quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
            $rec->title = cat_Products::getTitleById($rec->productId);
            
            if (empty($rec->id)) {
                $description = cat_Products::fetchField($form->rec->productId, 'info');
                if (!empty($description)) {
                    $rec->description = $description;
                }
            }
        }
    }
    
    
    /**
     * Добавя допълнителни полетата в антетката
     *
     * @param core_Master $mvc
     * @param NULL|array  $res
     * @param object      $rec
     * @param object      $row
     */
    protected static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
       if(!empty($rec->expectedTimeStart) || !empty($rec->timeDuration) || !empty($rec->expectedTimeEnd)){
            $resArr['times'] = array('name' => tr('Времена'), 'val' => tr("|*<table>
                <!--ET_BEGIN expectedTimeStart--><tr><td style='font-weight:normal'>|Очаквано начало|*:</td><td>[#expectedTimeStart#]</td></tr><!--ET_END expectedTimeStart-->
                <!--ET_BEGIN timeDuration--><tr><td style='font-weight:normal'>|Прод-ност|*:</td><td>[#timeDuration#]</td></tr><!--ET_END timeDuration-->
                <!--ET_BEGIN expectedTimeEnd--><tr><td style='font-weight:normal'>|Очакван край|*:</td><td>[#expectedTimeEnd#] <!--ET_BEGIN remainingTime--><div>[#remainingTime#]</div><!--ET_END remainingTime--></td></tr><!--ET_END expectedTimeEnd-->
                </table>"));
        }
        
        if($rec->showadditionalUom != 'yes'){
            unset($row->totalWeight);
        } elseif(empty($rec->totalWeight)) {
            $row->totalWeight = "<span class='quiet'>N/A</span>";
        }
        
        $resArr['additional'] = array('name' => tr('Изчисляване на тегло'), 'val' => tr("|*<table>
                <!--ET_BEGIN totalWeight--><tr><td style='font-weight:normal'>|Общо тегло|*:</td><td>[#totalWeight#]</td></tr><!--ET_END totalWeight-->
                <tr><td style='font-weight:normal'>|Режим|*:</td><td>[#showadditionalUom#]</td></tr>
                <!--ET_BEGIN weightDeviationNotice--><tr><td style='font-weight:normal'>|Отбелязване|*:</td><td>+/- [#weightDeviationNotice#]</td></tr><!--ET_END weightDeviationNotice-->
                <tr><td style='font-weight:normal'>|Предупреждение|*:</td><td>+/- [#weightDeviationWarning#]</td></tr>
                <!--ET_BEGIN weightDeviationAverageWarning--><tr><td style='font-weight:normal'>|Спрямо средното|*:</td><td>+/- [#weightDeviationAverageWarning#]</td></tr><!--ET_END weightDeviationAverageWarning-->
                </table>"));
        
        $resArr['labels'] = array('name' => tr('Етикетиране'), 'val' => tr("|*<table>
                <tr><td style='font-weight:normal'>|Етикет|*:</td><td>[#labelType#]</td></tr>
                <tr><td style='font-weight:normal'>|Опаковка|*:</td><td>[#packagingId#]</td></tr>
                </table>"));
        
        $resArr['indTimes'] = array('name' => tr('Заработка'), 'val' => tr("|*<table>
                <tr><td style='font-weight:normal'>|Норма|*:</td><td>[#indTime#]</td></tr>
                <tr><td style='font-weight:normal'>|Опаковка|*:</td><td>[#indPackagingId#]</td></tr>
                <tr><td style='font-weight:normal'>|Разпределяне|*:</td><td>[#indTimeAllocation#]</td></tr>
                </table>"));
        
        if(empty($rec->weightDeviationWarning)){
            $row->weightDeviationWarning = core_Type::getByName('percent')->toVerbal(planning_Setup::get('TASK_WEIGHT_TOLERANCE_WARNING'));
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
        $dQuery->XPR('sumQuantity', 'double', "SUM(#quantity)");
        $dQuery->XPR('sumWeight', 'double', 'SUM(#weight)');
        $dQuery->XPR('sumScrappedQuantity', 'double', "SUM(#scrappedQuantity)");
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
        return true;
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
                if ($state == 'closed' || $state == 'draft' || $state == 'rejected') {
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
        
        if ($action == 'close' && $rec) {
            if ($rec->state != 'active' && $rec->state != 'wakeup' && $rec->state != 'stopped') {
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
        if (!is_array($rec->params)) {
            
            return;
        }
        
        $tasksClassId = planning_Tasks::getClassId();
        foreach ($rec->params as $k => $o) {
            if (!isset($rec->{$k})) {
                continue;
            }
            
            $nRec = (object) array('paramId' => $o->paramId, 'paramValue' => $rec->{$k}, 'classId' => $tasksClassId, 'productId' => $rec->id);
            if ($id = cat_products_Params::fetchField("#classId = {$tasksClassId} AND #productId = {$rec->id} AND #paramId = {$o->paramId}", 'id')) {
                $nRec->id = $id;
            }
            
            cat_products_Params::save($nRec, null, 'REPLACE');
        }
    }
    
    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        
        $form->setField('weightDeviationWarning', "placeholder=" . core_Type::getByName('percent')->toVerbal(planning_Setup::get('TASK_WEIGHT_TOLERANCE_WARNING')));
        $form->setDefault('showadditionalUom', planning_Setup::get('TASK_WEIGHT_MODE'));
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
        
        // Добавяме допустимите опции
        $options = planning_Centers::getManifacturableOptions($rec->folderId);
        if(!array_key_exists($originRec->productId, $options)){
            $options = array("{$originRec->productId}" => cat_Products::getTitleById($originRec->productId, false)) + $options;
        }
        if(isset($rec->productId) && !array_key_exists($rec->productId, $options)){
            $options = array("{$rec->productId}" => cat_Products::getTitleById($rec->productId, false)) + $options;
        }
       
        $form->setOptions('productId', $options);
        $tasks = cat_Products::getDefaultProductionTasks($originRec, $originRec->quantity);
        
        if (isset($rec->systemId, $tasks[$rec->systemId])) {
            $fields = array_keys($form->selectFields("#input != 'none' AND #input != 'hidden'"));
            foreach ($fields as $fieldName) {
                $form->setDefault($fieldName, $tasks[$rec->systemId]->{$fieldName});
            }
            $form->setReadOnly('productId');
        }
        
        // Ако не е указано друго, е артикула от заданието
        $form->setDefault('productId', $originRec->productId);
        
        if (isset($rec->productId)) {
            $productRec = cat_Products::fetch($rec->productId, 'canConvert,canStore,measureId');
            
            // Ако артикула е различен от този от заданието и има други основни мерки, само тогава се показват за избор
            if($rec->productId != $originRec->productId){
                $measureOptions = cat_Products::getPacks($rec->productId, true);
                $measuresCount = countR($measureOptions);
                $form->setOptions('measureId', $measureOptions);
                $form->setDefault('measureId', key($measureOptions));
                if($measuresCount == 1){
                    $form->setField('measureId', 'input=hidden');
                }
            } else {
                $measuresCount = 1;
                $form->setDefault('measureId', $productRec->measureId);
                $form->setField('measureId', 'input=hidden');
            }
           
            if (empty($rec->id)) {
                
                // Показване на параметрите за задача във формата, като задължителни полета
                $params = cat_Products::getParams($rec->productId);
                $taskParams = cat_Params::getTaskParamIds();
                $diff = array_intersect_key($params, $taskParams);
                foreach ($diff as $pId => $v) {
                    $paramRec = cat_Params::fetch($pId);
                    $name = cat_Params::getVerbal($paramRec, 'name');
                    $form->FLD("paramcat{$pId}", 'double', "caption=Параметри на задачата->{$name},mandatory,before=description");
                    $ParamType = cat_Params::getTypeInstance($pId, $mvc, $rec->id);
                    $form->setFieldType("paramcat{$pId}", $ParamType);
                    
                    // Дефолта е параметъра от дефолтната задача за този артикул, ако има такава
                    if (isset($rec->systemId, $tasks[$rec->systemId])) {
                        $form->setDefault("paramcat{$pId}", $tasks[$rec->systemId]->params[$pId]);
                    }
                    if (!empty($paramRec->suffix)) {
                        $suffix = cat_Params::getVerbal($paramRec, 'suffix');
                        $form->setField("paramcat{$pId}", "unit={$suffix}");
                    }
                    
                    if (isset($v)) {
                        if ($ParamType instanceof fileman_FileType) {
                            $form->setDefault("paramcat{$pId}", $v);
                        } else {
                            $form->setSuggestions("paramcat{$pId}", array('' => '', "{$v}" => "{$v}"));
                        }
                    }
                    
                    $rec->params["paramcat{$pId}"] = (object) array('paramId' => $pId);
                }
                
                if ($productRec->canStore == 'yes') {
                    if($originRec->packagingId != $rec->measureId){
                        $form->setDefault('packagingId', $rec->measureId);
                    }
                    
                    if(isset($rec->packagingId)){
                        $form->setDefault('indPackagingId', $rec->packagingId);
                    }
                } else {
                    $form->setDefault('indPackagingId', $rec->measureId);
                }
            }
           
            if ($productRec->canStore == 'yes') {
                $packs = cat_Products::getPacks($rec->productId);
                $form->setOptions('packagingId', array('' => '') + $packs);
                $form->setOptions('indPackagingId', $packs);
            }
            
            // Ако артикула е вложим, може да се влага по друга операция
            if ($productRec->canConvert == 'yes') {
                $tasks = self::getTasksByJob($origin->that, true);
                unset($tasks[$rec->id]);
                if (countR($tasks)) {
                    $form->setField('inputInTask', 'input');
                    $form->setOptions('inputInTask', array('' => '') + $tasks);
                }
            }
            
            if($measuresCount == 1){
                $measureShort = cat_UoM::getShortName($rec->measureId);
                $form->setField('plannedQuantity', "unit={$measureShort}");
            }
            
            if ($productRec->canStore == 'yes') {
                $form->setField('storeId', 'input');
                $form->setField('packagingId', 'input');
                $form->setField('indPackagingId', 'input');
            } else {
                $form->setField('labelType', 'input=hidden');
                $form->setField('labelType', 'print');
                $form->setDefault('indPackagingId', $rec->measureId);
                $form->setField('indTime', "unit=за|* 1 |{$measureShort}|*");
            }
            
            if ($rec->productId == $originRec->productId) {
                $toProduce = ($originRec->quantity - $originRec->quantityProduced);
                if ($toProduce > 0) {
                    $packRec = cat_products_Packagings::getPack($rec->productId, $rec->measureId);
                    $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
                    $form->setDefault('plannedQuantity', $toProduce / $quantityInPack);
                }
            }
        }
        
        foreach (array('fixedAssets' => 'planning_AssetResources', 'employees' => 'planning_Hr') as $field => $Det) {
            $arr = $Det::getByFolderId($rec->folderId);
            if (!empty($rec->{$field})) {
                $alreadyIn = keylist::toArray($rec->{$field});
                foreach ($alreadyIn as $fId) {
                    if (!array_key_exists($fId, $arr)) {
                        $arr[$fId] = $Det::getTitleById($fId, false);
                    }
                }
            }
            
            if (countR($arr)) {
                $form->setSuggestions($field, $arr);
            } else {
                $form->setField($field, 'input=none');
            }
        }
        
        if (isset($rec->id)) {
            $form->setReadOnly('productId');
            if(planning_ProductionTaskDetails::fetchField("#taskId = {$rec->id}")){
                $form->setReadOnly('packagingId');
            }
        }
    }
    
    
    /**
     * Връща масив със съществуващите задачи
     *
     * @param int      $containerId
     * @param stdClass $data
     *
     * @return void
     */
    protected function prepareExistingTaskRows($containerId, &$data)
    {
        // Всички създадени задачи към заданието
        $query = $this->getQuery();
        $query->where("#state != 'rejected'");
        $query->where("#originId = {$containerId}");
        $query->XPR('orderByState', 'int', "(CASE #state WHEN 'wakeup' THEN 1 WHEN 'active' THEN 2 WHEN 'stopped' THEN 3 WHEN 'closed' THEN 4 WHEN 'waiting' THEN 5 ELSE 6 END)");
        $query->orderBy('#orderByState=ASC,#id=DESC');
        $fields = $this->selectFields();
        $fields['-list'] = $fields['-detail'] = true;
        
        // Подготвяме данните
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $row = planning_Tasks::recToVerbal($rec, $fields);
            $row->plannedQuantity .= " " . $row->measureId;
            $row->totalQuantity .= " " . $row->measureId;
            $row->producedQuantity .= " " . $row->measureId;
            
            $subArr = array();
            if (!empty($row->fixedAssets)) {
                $subArr[] = tr('Оборудване:|* ') . $row->fixedAssets;
            }
            if (!empty($row->employees)) {
                $subArr[] = tr('Оператори:|* ') . $row->employees;
            }
            if (countR($subArr)) {
                $row->info = '<div><small>' . implode(' &nbsp; ', $subArr) . '</small></div>';
            }
            
            // Показване на протоколите за производство
            $notes = array();
            $nQuery = planning_DirectProductionNote::getQuery();
            $nQuery->where("#originId = {$rec->containerId} AND #state != 'rejected'");
            $nQuery->show('id');
            while($nRec = $nQuery->fetch()){
                $notes[] = planning_DirectProductionNote::getLink($nRec->id, 0);
            }
            if (countR($notes)) {
                $row->info .= "<div style='padding-bottom:7px'>" . implode(' | ', $notes) . "</div>";
            }
            
            $row->modified = $row->modifiedOn . ' ' . tr('от||by') . ' ' . $row->modifiedBy;
            $row->modified = "<div style='text-align:center'> {$row->modified} </div>";
            $data->rows[$rec->id] = $row;
        }
    }
    
    
    /**
     * Подготвя задачите към заданията
     */
    public function prepareTasks($data)
    {
        $containerId = $data->masterData->rec->containerId;
        
        $data->recs = $data->rows = array();
        $this->prepareExistingTaskRows($containerId, $data);
        
        // Ако потребителя може да добавя операция от съответния тип, ще показваме бутон за добавяне
        if ($this->haveRightFor('add', (object) array('originId' => $containerId))) {
            if (!Mode::isReadOnly()) {
                $data->addUrlArray = array('planning_Jobs', 'selectTaskAction', 'originId' => $containerId, 'ret_url' => true);
            }
        }
    }
    
    
    /**
     * Рендира задачите на заданията
     */
    public function renderTasks($data)
    {
        $tpl = new ET('');
        
        // Ако няма намерени записи, не се рендира нищо
        // Рендираме таблицата с намерените задачи
        $table = cls::get('core_TableView', array('mvc' => $this));
        $fields = 'title=Операция,progress=Прогрес,plannedQuantity=Планирано,totalQuantity=Произведено,producedQuantity=Заскладено,expectedTimeStart=Времена->Начало, timeDuration=Времена->Прод-ст, timeEnd=Времена->Край, modified=Модифицирано,info=@info';
        $data->listFields = core_TableView::filterEmptyColumns($data->rows, $fields, 'timeStart,timeDuration,timeEnd,expectedTimeStart');
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        
        $tpl = $table->get($data->rows, $data->listFields);
        
        // Имали бутони за добавяне
        if (isset($data->addUrlArray)) {
            $btn = ht::createBtn('Нова операция', $data->addUrlArray, false, false, "title=Създаване на производствена операция към задание,ef_icon={$this->singleIcon}");
            $tpl->append($btn, 'btnTasks');
        }
        
        // Връщаме шаблона
        return $tpl;
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->FLD('assetId', 'key(mvc=planning_AssetResources,select=name,allowEmpty)', 'caption=Оборудване');
        $data->listFilter->showFields .= ',assetId';
        $data->listFilter->input('assetId');
        
        if ($assetId = $data->listFilter->rec->assetId) {
            $data->query->where("LOCATE('|{$assetId}|', #fixedAssets)");
        }
        
        // Показване на полето за филтриране
        if ($filterDateField = $data->listFilter->rec->filterDateField) {
            $filterFieldArr = array($filterDateField => ($filterDateField == 'expectedTimeStart') ? 'Очаквано начало' : ($filterDateField == 'timeStart' ? 'Начало' : 'Създаване'));
            arr::placeInAssocArray($data->listFields, $filterFieldArr, 'title');
        }
        
        if (!Request::get('Rejected', 'int')) {
            $data->listFilter->setOptions('state', array('' => '') + arr::make('draft=Чернова, active=Активен, pendingandactive=Активни+Чакащи,closed=Приключен, stopped=Спрян, wakeup=Събуден,waiting=Чакащо', true));
            $data->listFilter->setField('state', 'placeholder=Всички,formOrder=1000');
            $data->listFilter->showFields .= ',state';
            $data->listFilter->input('state');
            
            if ($state = $data->listFilter->rec->state) {
                if ($state != 'pendingandactive') {
                    $data->query->where("#state = '{$state}'");
                } else {
                    $data->query->where("#state = 'active' OR #state = 'waiting'");
                }
            }
        }
    }
    
    
    /**
     * Връща масив от задачи към дадено задание
     *
     * @param int  $jobId      - ид на задание
     * @param bool $onlyActive - Не оттеглените или само активните/събудени/спрени
     *
     * @return array $res         - масив с намерените задачи
     */
    public static function getTasksByJob($jobId, $onlyActive = false)
    {
        $res = array();
        $oldContainerId = planning_Jobs::fetchField($jobId, 'containerId');
        $query = static::getQuery();
        $query->where("#originId = {$oldContainerId}");
        
        if ($onlyActive === true) {
            $query->where("#state = 'active' || #state = 'wakeup' || #state = 'stopped'");
        } else {
            $query->where("#state != 'rejected'");
        }
        
        while ($rec = $query->fetch()) {
            $res[$rec->id] = self::getRecTitle($rec, false);
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
     * @param mixed                     $jobId
     * @param string $type
     *
     * @return float $quantity
     */
    public static function getProducedQuantityForJob($jobId)
    {
        expect($jobRec = planning_Jobs::fetchRec($jobId));
        
        $query = planning_Tasks::getQuery();
        $query->XPR('sum', 'double', 'SUM((COALESCE(#totalQuantity, 0) - COALESCE(#scrappedQuantity, 0)) * #quantityInPack)');
        $query->where("#originId = {$jobRec->containerId} AND #productId = {$jobRec->productId}");
        $query->where("#state != 'rejected' AND #state != 'pending'");
        $query->show('totalQuantity,sum');
        
        $sum = $query->fetch()->sum;
        $quantity = (!empty($sum)) ? $sum : 0;
        
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
        
        $taskDetilQuery = planning_ProductionTaskDetails::getQuery();
        $taskDetilQuery->where(array("#serial = '[#1#]'", $str));
        
        while($dRec = $taskDetilQuery->fetch()) {
            
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
        
        if ($mvc->haveRightFor('single', $rec)) {
            $data->toolbar->addBtn('Работна карта', array($mvc, 'single', $rec->id, 'ret_url' => true, 'Printing' => true, 'printworkcard' => true), null, 'target=_blank,ef_icon=img/16/print_go.png,title=Печат на работна карта за производствената операция');
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
}
