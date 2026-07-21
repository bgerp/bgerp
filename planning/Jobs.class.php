<?php


/**
 * Мениджър на Задание за производство/разпад
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Задание за производство/разпад
 */
class planning_Jobs extends core_Master
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'doc_DocumentIntf,hr_IndicatorsSourceIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Задания за производство/разпад';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Задание за производство/разпад';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Job';
    
    
    /**
     * За кои действия да се изисква основание
     *
     * @see planning_plg_StateManager
     */
    public $demandReasonChangeState = 'stop,wakeup,activateAgain';

    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StockPlanning, cat_plg_LogPackUsage, doc_DocumentPlg, doc_plg_Tabs, planning_plg_StateManager, doc_SharablePlg, planning_Wrapper, support_plg_IssueSource, plg_Sorting, acc_plg_DocumentSummary, plg_Search, change_Plugin, plg_Clone, plg_Printing, doc_plg_SelectFolder, cat_plg_AddSearchKeywords, plg_SaveAndNew, bgerp_plg_Blank';
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'oldJobId,storeId,dueDate,packQuantity,notes,tolerance,productionScrap,inputStores,sharedUsers,allowSecondMeasure';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, job, jobDisassembly';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, job, jobDisassembly';
    
    
    /**
     * Кой може да променя състоянието?
     */
    public $canChangestate = 'ceo, job, jobDisassembly, production';
    
    
    /**
     * Кой може да променя активирани записи
     *
     * @see change_Plugin
     */
    public $canChangerec = 'ceo, job, jobDisassembly';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, jobSee, planningAll';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, jobSee';
    
    
    /**
     * Полета за търсене
     */
    public $searchFields = 'productId, notes, saleId, purchaseId, deliveryPlace, deliveryDate, deliveryTermId';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/clipboard_text.png';
    
    
    /**
     * Кой може да клонира
     */
    public $canClonerec = 'ceo, job, jobDisassembly';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title=Документ, dueDate, packQuantity=Количество->|*<small>|Планирано|*</small>,quantityFromTasks=Количество->|*<small>|Произведено|*</small>, quantityProduced=Количество->|*<small>|Заскладено|*</small>, quantityNotStored=Количество->|*<small>|Незаскладено|*</small>, packagingId,folderId, state, modifiedOn,modifiedBy';
    
    
    /**
     * Името на полето, което ще е на втори ред
     */
    public $listFieldsExtraLine = 'title';
    
    
    /**
     * Шаблон за единичен изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutJob.shtml';
    
    
    /**
     * Поле за дата по което ще филтрираме
     */
    public $filterDateField = 'createdOn,dueDate,expectedDueDate,deliveryDate,modifiedOn,lastChangeStateOn,activatedOn';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'Tasks=planning_Tasks';
    
    
    /**
     * Клас за отделния ред в листовия изглед
     */
    public $commonRowClass = 'separateRowTable';


    /**
     * Допълнителен CSS клас на listTopContainer
     */
    public $listTopContainerHtmlClass = 'twoColsFilter';


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'quantityFromTasks,quantityNotStored';


    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.52|Производство';


    /**
     * Вербални наименования на състоянията
     */
    private static $actionNames = array('created' => 'Създаване',
        'active' => 'Активиране',
        'edited' => 'Редактиране',
        'stopped' => 'Спиране',
        'closed' => 'Приключване',
        'rejected' => 'Оттегляне',
        'restore' => 'Възстановяване',
        'wakeup' => 'Събуждане');
    
    
    /**
     * Масив със състояниет, за които да се праща нотификация
     *
     * @see planning_plg_StateManager
     */
    public $notifyActionNamesArr = array('active' => 'Активиране',
                                         'closed' => 'Приключване',
                                         'wakeup' => 'Събуждане',
                                         'stopped' => 'Спиране',
                                         'rejected' => 'Оттегляне');
    
    
    /**
     * Дали ключа на нотификацията да сочи към нишката или документа - за уникалност на нотификацията
     *
     * @see planning_plg_StateManager
     */
    public $notifyToThread = false;
    
    
    /**
     * Да се забрани ли кеширането на документа
     */
    public $preventCache = true;
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'dueDate,quantityProduced,quantityDisassembled,history,oldJobId,secondMeasureQuantity,productViewCacheDate,salesBomIdOnActivation,instantBomIdOnActivation,productionBomIdOnActivation';


    /**
     *  При преминаването в кои състояния ще се обновяват планираните складови наличностти
     */
    public $updatePlannedStockOnChangeStates = array('stopped', 'wakeup', 'active');


    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'planning_Centers';


    /**
     * Дали в лист изгледа да се показва полето за филтър по състояние
     * @param bool
     * @see acc_plg_DocumentSummary
     */
    public $filterAllowState = false;


    /**
     * Дали при печат да може да се избира да се печата С/без фирмена бланка
     */
    public $allowPrintingWithoutBlank = true;


    /**
     * Какъв да е дефолтния избор за печат на фирмена бланка
     */
    public $defaultPrintingBlankMode = 'no';


    /**
     * Какъв да е дефолтния избор за печат на фирмена бланка
     */
    public $routeDocumentAfterCreation = true;


    /**
     * Описание на модела (таблицата)
     */
    public function  description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax)', 'class=w100,silent,mandatory,caption=Артикул,removeAndRefreshForm=packagingId|packQuantity|quantityInPack|tolerance|productionScrap|quantity|oldJobId,placeholder=Търсете артикул');
        $this->FLD('type', 'enum(manifacture=Производство,disassembly=Разпад)', 'notNull,value=manifacture,caption=Вид,mandatory,after=productId,input=hidden,silent');
        $this->FLD('oldJobId', 'key2(mvc=planning_Jobs,selectSourceArr=planning_Jobs::getPreviousJobs,allowEmpty,forceAjax,maxSuggestions=100)', 'silent,after=productId,caption=Предходно задание,removeAndRefreshForm=notes|department|packagingId|quantityInPack|storeId,input=none,class=w100');
        $this->FLD('dueDate', 'date(smartTime)', 'caption=Падеж,mandatory,remember');
        $this->FLD('expectedDueDate', 'date(smartTime)', 'caption=Очакван падеж,input=none');

        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'smartCenter,mandatory,input=hidden,before=packQuantity,silent,removeAndRefreshForm');
        $this->FNC('packQuantity', 'double(Min=0,smartRound)', 'caption=Количество,input,mandatory,after=jobQuantity');
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
        $this->FLD('quantity', 'double(decimals=2)', 'caption=Количество->Планирано,input=none');

        $this->FLD('secondMeasureId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Втора мярка->Мярка', 'input=hidden');
        $this->FLD('secondMeasureQuantity', 'double(decimals=2)', 'caption=Втора мярка->К-во,input=none');

        $this->FLD('quantityFromTasks', 'double(decimals=2)', 'input=none,caption=Количество->Произведено,notNull,value=0');
        $this->FLD('quantityProduced', 'double(decimals=2)', 'input=none,caption=Количество->Заскладено,notNull,value=0');
        $this->FLD('quantityDisassembled', 'double(decimals=2)', 'input=none,caption=Количество->Разпаднато,notNull,value=0');
        $this->FLD('productionScrap', 'percent(suggestions=5 %|10 %|15 %|20 %|25 %|30 %,warningMax=0.1)', 'caption=Технолог.брак');
        $this->FLD('tolerance', 'percent(suggestions=5 %|10 %|15 %|20 %|25 %|30 %,warningMax=0.1)', 'caption=Толеранс,silent');
        $this->FLD('allowSecondMeasure', 'enum(no=Без,yes=Задължителна)', 'caption=Втора мярка,notNull,value=no,silent,removeAndRefreshForm=secondMeasureId');
        $this->FLD('department', 'key(mvc=planning_Centers,select=name,allowEmpty)', 'caption=Ц-р дейност,remember,mandatory');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Произвеждане в,remember');
        $this->FLD('inputStores', 'keylist(mvc=store_Stores,select=name,allowEmpty,makeLinks)', 'caption=Влагане от,after=storeId,remember');
        $this->FLD('notes', 'richtext(rows=2,bucket=Notes,passage)', 'caption=Забележки,remember');

        $this->FLD('deliveryDate', 'date(smartTime)', 'caption=Данни от договора->Срок,input=hidden');
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Данни от договора->Условие,input=hidden');
        $this->FLD('deliveryPlace', 'key(mvc=crm_Locations,select=title,allowEmpty)', 'caption=Данни от договора->Място,input=hidden');

        $this->FLD('weight', 'cat_type_Weight', 'caption=Тегло,input=none');
        $this->FLD('brutoWeight', 'cat_type_Weight', 'caption=Бруто,input=none');
        $this->FLD('state','enum(draft=Чернова, active=Активиран, rejected=Оттеглен, closed=Приключен, stopped=Спрян, wakeup=Събуден)', 'caption=Състояние, input=none');

        $this->FLD('saleId', 'key(mvc=sales_Sales)', 'input=hidden,silent,caption=Продажба');
        $this->FLD('purchaseId', 'key(mvc=purchase_Purchases)', 'input=hidden,silent,caption=Покупка');
        $this->FLD('sharedUsers', 'userList(roles=planning|ceo,showClosedUsers=no)', 'caption=Споделяне->Потребители,autohide');
        $this->FLD('history', 'blob(serialize, compress)', 'caption=Данни,input=none');
        $this->FLD('productViewCacheDate', 'datetime(format=smartTime)', 'caption=Към коя дата е кеширан изгледа на артикула,input=none');
        $this->FLD('salesBomIdOnActivation', 'key(mvc=cat_Boms,select=title)', 'caption=Търговска рецепта при активиране,input=none');
        $this->FLD('instantBomIdOnActivation', 'key(mvc=cat_Boms,select=title)', 'caption=Моментна рецепта при активиране,input=none');
        $this->FLD('productionBomIdOnActivation', 'key(mvc=cat_Boms,select=title)', 'caption=Работна рецепта при активиране,input=none');

        $this->setDbIndex('state');
        $this->setDbIndex('productId');
        $this->setDbIndex('oldJobId');
        $this->setDbIndex('saleId');
        $this->setDbIndex('purchaseId');
        $this->setDbIndex('createdOn');
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

        if(isset($rec->id)){
            $form->setReadOnly('productId');
        }

        $data->singleTitle = ($rec->type == 'disassembly') ? tr('Задание за разпад') : tr('Задание за производство');

        if($rec->type == 'disassembly'){
            $form->setField('productId', "unit=|*(|за РАЗПАД|*)");
        }

        $defaultProductId = $defaultProductPack = $defaultQuantity = null;
        if($data->action == 'changefields' && isset($rec->id)){

            $connectedThreads = planning_Jobs::getJobLinkedThreads($rec->id, true);
            $connectedThreadsStr = implode(',', $connectedThreads);
            if(planning_DirectProductionNote::count("#threadId IN ({$connectedThreadsStr}) AND #state IN ('active', 'pending')")){
                $form->info = "<div class='formNotice'>" . tr("Не може да се променя дали има/няма втора мярка, защото вече има пуснати протоколи за производство към заданието и/или финалните операции към него|*!") . '</div>';

                $form->setField('allowSecondMeasure', 'notChangeableIfHidden,input=none');
            }

            $exRec = $mvc->fetch($rec->id, '*', false);
            list($productId, $packagingId, $secondMeasureId) = array($exRec->productId, $exRec->packagingId, $exRec->secondMeasureId);

            if(isset($secondMeasureId)){
                $tQuery = planning_Tasks::getQuery();
                $tQuery->in('threadId', $connectedThreads);
                $tQuery->where("#state != 'rejected'");
                $derivitiveMeasures = cat_UoM::getSameTypeMeasures($secondMeasureId);
                if(array_key_exists($packagingId, $derivitiveMeasures)){
                    $derivitiveMeasures = cat_UoM::getSameTypeMeasures(cat_Products::fetchField($productId, 'measureId'));
                }
                unset($derivitiveMeasures['']);
                $tQuery->in("measureId", array_keys($derivitiveMeasures));

                if($tQuery->count()){
                    $form->info .= "<div class='formNotice'>" . tr("Не може да се променя дали има/няма втора мярка, защото вече има пуснати ПО с втората мярка|*!") . '</div>';
                    $form->setField('allowSecondMeasure', 'notChangeableIfHidden,input=none');
                }
            }
        } else {
            list($productId, $packagingId, $secondMeasureId) = array($rec->productId, $rec->packagingId, $rec->secondMeasureId);
        }

        // Резолвваме източника (продажба/покупка), ако Заданието е обвързано с такъв
        list($sourceClass, $sourceId, $jobField) = self::getSourceInfo($rec);

        if (isset($sourceClass)) {
            $form->setField('deliveryDate', 'input');
            $form->setField('deliveryTermId', 'input');
            $form->setField('deliveryPlace', 'input');

            // Ако заданието е към продажба/покупка, може да се избират само измежду артикулите в нея
            $products = $sourceClass::getProducts4Job($sourceId, true, $rec->type);
            $form->setFieldType('productId', 'key(mvc=cat_Products)');

            // Дефолтния артикул е първия без задание към продажбата/покупката
            $packsInDeal = $packsInDealOrdered = $sourceClass::getProductPacksInDeal($sourceId, array_keys($products));

            // Подредба в реда на производимите
            $pKeys = array_keys($products);
            foreach ($pKeys as $pKey){
                $packsInDealOrdered[$pKey] = $packsInDeal[$pKey];
            }
            $packsInDeal = $packsInDealOrdered;

            $found = false;
            $availableJobsCnt = 0;
            foreach ($products as $pId => $pName){
                foreach ($packsInDeal[$pId] as $packId => $packQuantity){
                    $exRec = static::fetchField("#productId = {$pId} AND #{$jobField} = {$sourceId} AND #packagingId = {$packId} AND #state != 'rejected'");
                    if(!$exRec){
                        $availableJobsCnt++;
                        if (!$found && (!isset($rec->productId) || $rec->productId == $pId)) {
                            $defaultProductId = $pId;
                            $defaultProductPack = $packId;
                            $defaultQuantity = $packQuantity;
                            $found = true;
                        }
                    }
                }
            }

            $form->setDefault('productId', $defaultProductId);
            $form->rec->_allowedProductsCnt = $availableJobsCnt;
            if(countR($products) == 1){
                $form->setDefault('productId', key($products));
                $form->setOptions('productId', $products);
            } else {
                $form->setOptions('productId', array('' => '') + $products);
            }
        } else {

            // Артикули, годни за Задание: производими ИЛИ (вложими И складируеми), без генеричните
            $form->setFieldTypeParams('productId', array(
                'notDriverId'      => planning_interface_StepProductDriver::getClassId(),
                'allowedForJobs'   => true,
                'jobType'          => $rec->type ?? 'manifacture',
                'hasnotProperties' => 'generic',
            ));
        }

        // Ако има предишни задания зареждат се за избор
        $productId = $productId ?? $rec->productId;
        if(isset($productId)){

            $packs = cat_Products::getPacks($productId, $packagingId, false, $secondMeasureId);
            $form->setOptions('packagingId', $packs);
            $form->setField('packagingId', 'input');
            $form->setField('oldJobId', 'input');

            if ($tolerance = cat_Products::getParams($productId, 'tolerance')) {
                $form->setDefault('tolerance', $tolerance);
            }

            // Видът на заданието идва от бутона/URL и остава скрит във формата
            $productTypeRec = cat_Products::fetch($productId, 'canManifacture,canConvert,canStore');
            $jobType = $rec->type ?? 'manifacture';

            $oldJobParams = array('productId' => $productId, 'type' => $jobType);

            if (isset($sourceClass)) {
                $oldJobParams[$jobField] = $sourceId;
                $deliveryDate = null;
                $form->setDefault('dueDate', $mvc->getDefaultDueDate($productId, $sourceClass, $sourceId, $deliveryDate));

                $sourceRec = $sourceClass::fetch($sourceId);
                $form->setDefault('packagingId', $defaultProductPack);
                $form->setDefault('packQuantity', $defaultQuantity);

                // Ако има данни от продажбата/покупката, попълваме ги
                $form->setDefault('storeId', $sourceRec->shipmentStoreId);
                $form->setDefault('deliveryTermId', $sourceRec->deliveryTermId);
                $form->setDefault('deliveryDate', $deliveryDate);
                $form->setDefault('deliveryPlace', $sourceRec->deliveryLocationId);
                $locations = crm_Locations::getContragentOptions($sourceRec->contragentClassId, $sourceRec->contragentId);
                $form->setOptions('deliveryPlace', $locations);
                $caption = '|Данни от|* <b>' . $sourceClass::getRecTitle($sourceId) . '</b>';
                $caption = str_replace(',', ' ', str_replace(', ', ' ', $caption));

                $form->setField('deliveryTermId', "caption={$caption}->Условие,changable");
                $form->setField('deliveryDate', "caption={$caption}->Срок,changable");
                $form->setField('deliveryPlace', "caption={$caption}->Място,changable");
            } else {

                // Ако заданието не е към продажба/покупка, скриваме полетата от сделката
                $form->setField('deliveryTermId', 'input=none');
                $form->setField('deliveryDate', 'input=none');
                $form->setField('deliveryPlace', 'input=none');
                $form->setField('department', 'mandatory');
            }

            if(isset($rec->folderId)){
                $Cover = doc_Folders::getCover($rec->folderId);
                if($Cover->isInstanceOf('planning_Centers')){
                    $form->setDefault('department', $Cover->that);
                }
            }

            // Ако е избрано предишно задание зареждат се данните от него
            if (isset($rec->oldJobId)) {
                $oRec = self::fetch($rec->oldJobId, 'notes,department,packagingId,storeId');
                $form->setDefault('notes', $oRec->notes);
                $form->setDefault('packagingId', $oRec->packagingId);
                $form->setDefault('storeId', $oRec->storeId);
            }

            $form->setDefault('packagingId', key($packs));
            $form->setFieldTypeParams('oldJobId', $oldJobParams);

            // При Разпад Задачи/Протоколи за производство изобщо не могат да се
            // създават към заданието (виж planning_Tasks::prepareTasks() и
            // planning_DirectProductionNote::on_BeforeGetRequiredRoles()) - а те са
            // единствените, които пишат secondMeasureQuantity. Затова втора мярка
            // няма смисъл и не се предлага за disassembly задания.
            if ($jobType == 'disassembly') {
                $form->setField('allowSecondMeasure', 'input=none');
            } elseif ($Driver = cat_Products::getDriver($productId)) {

                // Коя е втората мярка, ако не идва от драйвера се търси в опаковките
                $secondMeasureId = (isset($rec->id) && $secondMeasureId) ? $secondMeasureId : cat_Products:: getSecondMeasureId($productId);
                $packagingId = $packagingId ?? $rec->packagingId;
                if(empty($secondMeasureId)){
                    $form->setField('allowSecondMeasure', 'input=none');
                } else {
                    $derivitiveMeasures = cat_UoM::getSameTypeMeasures($secondMeasureId);
                    $form->setDefault('allowSecondMeasure', 'yes');
                    if(array_key_exists($packagingId, $derivitiveMeasures)){
                        $mandatoryMeasure = cat_Products::fetchField($productId, 'measureId');
                    } else {
                        $mandatoryMeasure = $secondMeasureId;
                    }
                    $mandatoryMeasureName = tr(cat_UoM::getVerbal($mandatoryMeasure, 'name'));

                    $form->setFieldType('allowSecondMeasure', "enum(no=Без,yes=Задължително ({$mandatoryMeasureName}))");
                    $form->setDefault('secondMeasureId', $secondMeasureId);
                }
            }

            // При Разпад "Технологичен брак" и "Толеранс" не се прилагат - скриваме ги и от формата
            if ($jobType == 'disassembly') {
                $form->setField('tolerance', 'input=none');
                $form->setField('productionScrap', 'input=none');
            }

            if ($productionScrap = cat_Products::getParams($productId, 'productionScrap')) {
                $form->setDefault('productionScrap', $productionScrap);
            }

            $roundPackagingId = cat_UoM::fetchField($rec->packagingId, 'round');
            $form->setField('packQuantity', array('unit' => "|*<span class='scrapHint' style='display:none;'><span class='quiet'>|включен техн. брак|*:</span> <span class='withProductionScrap' data-packaging-round='{$roundPackagingId}'></span></span>"));
        }

        if($data->action == 'clone'){
            $form->setReadOnly('department');
        }

        $form->layout = $form->renderLayout();
        jquery_Jquery::run( $form->layout,'scrapCalculation();');
    }


    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        list($sourceClass) = self::getSourceInfo($data->form->rec);

        // Преименуване на бутона за запис и нов
        if (isset($sourceClass) && ($data->form->rec->_allowedProductsCnt ?? 0) > 1) {
            if (!empty($data->form->toolbar->buttons['saveAndNew'])) {
                $data->form->toolbar->renameBtn('saveAndNew', 'Активиране и нов');
            }
        } else {
            $data->form->toolbar->removeBtn('saveAndNew');
        }
    }


    /**
     * Връща класа, id-то и полето на planning_Jobs, свързващо Заданието с
     * източника (продажба/покупка), от който е създадено. Централизирано тук,
     * за да не се дублира if(saleId)/elseif(purchaseId) логиката на много места.
     *
     * @param stdClass $rec  - трябва да има (евентуално празни) saleId/purchaseId
     *
     * @return array [string|null $sourceClass, int|null $sourceId, string|null $jobField]
     */
    public static function getSourceInfo($rec)
    {
        if (!empty($rec->saleId)) {
            $sourceClass = 'sales_Sales';
        } elseif (!empty($rec->purchaseId)) {
            $sourceClass = 'purchase_Purchases';
        } else {

            return array(null, null, null);
        }

        $jobField = cls::get($sourceClass)->jobSourceField;

        return array($sourceClass, $rec->{$jobField}, $jobField);
    }


    /**
     * Дефолтна дата на падеж
     *
     * @param int         $productId   - ид на артикул
     * @param string|null $sourceClass - sales_Sales|purchase_Purchases
     * @param int|null    $sourceId    - ид на продажбата/покупката
     *
     * @return NULL|datetime - дефолтния падеж
     */
    private static function getDefaultDueDate($productId, $sourceClass, $sourceId, &$deliveryDate)
    {
        if (empty($sourceClass) || empty($sourceId)) {

            return;
        }

        $sourceRec = $sourceClass::fetch($sourceId);

        if (!empty($sourceRec->deliveryTime)) {
            $deliveryDate = $sourceRec->deliveryTime;
        } elseif (!empty($sourceRec->deliveryTermTime)) {
            $deliveryDate = dt::addSecs($sourceRec->deliveryTermTime, $sourceRec->activatedOn);
        }

        if (empty($deliveryDate)) {

            return;
        }

        $deliveryDate = dt::verbal2mysql($deliveryDate, false);

        // Транспортни стойности (sales_TransportValues) се пазят само за продажби -
        // при покупка няма запис за подрязване на срока с времето за доставка до клиента
        $transportTime = 0;
        if ($sourceClass == 'sales_Sales') {
            $sourceClassId = $sourceClass::getClassId();
            $transRec = sales_TransportValues::fetch("#docClassId = {$sourceClassId} AND #docId = {$sourceId}", 'deliveryTime');
            $transportTime = $transRec->deliveryTime ?? 0;
        }
        $subtractTime = 3 * 24 * 60 * 60 + $transportTime;
        $dueDate = dt::addSecs(-1 * $subtractTime, $deliveryDate);
        $dueDate = cal_Calendar::nextWorkingDay($dueDate, null, -1);
        $dueDate = dt::verbal2mysql($dueDate, false);

        $today = dt::today();
        if($dueDate > $today){

            return $dueDate;
        }

        return $today;
    }

    
    /**
     *  Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $filterType = Request::get('type', 'enum(manifacture,disassembly)');
        $data->title = $filterType == 'disassembly' ? 'Задания за разпад' : 'Задания за производство';

        if (!Request::get('Rejected', 'int')) {
            $data->listFilter->FNC('view', 'enum(all=Всички,progress=Според изпълнението,overdue=Просрочен падеж,draft=Черновите,active=Активните,activenotasks=Активните без задачи,stopped=Спрените,closed=Приключените,wakeup=Събудените)', 'caption=Изглед,input,silent');
            $data->listFilter->input('view', 'silent');
            $data->listFilter->setDefault('view', 'all');
            $data->listFilter->showFields .= ',view';
        }
        
        // type вече е реално поле в модела - само разширяваме опциите му с "Всички"
        $data->listFilter->setField('type', 'input=hidden,silent');
        $data->listFilter->input('type', 'silent');
        $data->listFilter->showFields .= ',type';

        $data->listFilter->setField('selectPeriod', 'caption=Период');
        $data->listFilter->FLD('contragentFolderId', 'key2(mvc=doc_Folders,allowEmpty,coverInterface=crm_ContragentAccRegIntf)', 'caption=Контрагент,silent,after=view');
        $data->listFilter->input('contragentFolderId', 'silent');
        $data->listFilter->input();
        $data->listFilter->showFields .= ',contragentFolderId';

        if ($filter = $data->listFilter->rec) {
            if (isset($filter->type) && $filter->type != 'all') {
                $data->query->where("#type = '{$filter->type}'");
            }

            if (isset($filter->contragentFolderId)) {

                // Намиране на заданията към всички продажби и покупки в избраната папка на контрагента
                $sourceConditions = array();
                foreach (array('sales_Sales', 'purchase_Purchases') as $sourceClass) {
                    $sourceQuery = $sourceClass::getQuery();
                    $sourceQuery->where("#folderId = {$filter->contragentFolderId} AND #state NOT IN ('draft', 'pending', 'rejected')");
                    $sourceQuery->show('id');
                    $sourceIds = arr::extractValuesFromArray($sourceQuery->fetchAll(), 'id');
                    if (countR($sourceIds)) {
                        $jobField = cls::get($sourceClass)->jobSourceField;
                        $sourceConditions[] = "#{$jobField} IN (" . implode(',', $sourceIds) . ')';
                    }
                }

                if (countR($sourceConditions)) {
                    $data->query->where('(' . implode(' OR ', $sourceConditions) . ')');
                } else {
                    $data->query->where("1=2");
                }
            }

            if (isset($filter->filterDateField)) {
                switch ($filter->filterDateField) {
                    case 'createdOn':
                        unset($data->listFields['modifiedOn']);
                        unset($data->listFields['modifiedBy']);
                        $data->listFields['createdOn'] = 'Създаване||Created->На';
                        $data->listFields['createdBy'] = 'Създаване||Created->От||By';
                        $data->query->orderBy('createdOn', 'DESC');
                        break;
                    case 'dueDate':
                        $data->query->orderBy('dueDate', 'ASC');
                        $data->query->where("#state = 'active'");
                        break;
                    case 'lastChangeStateOn':
                        $data->query->orderBy('lastChangeStateOn', 'DESC');
                        $data->listFields['lastChangeStateOn'] = 'Промяна състояние||State change->На';
                        $data->listFields['lastChangeStateBy'] = 'Промяна състояние||State change->От||By';
                        unset($data->listFields['modifiedOn']);
                        unset($data->listFields['modifiedBy']);
                        break;
                    case 'activatedOn':
                        unset($data->listFields['activatedOn']);
                        unset($data->listFields['activatedBy']);
                        $data->listFields['activatedOn'] = 'Активиране||Activated->На';
                        $data->listFields['activatedBy'] = 'Активиране||Activated->От||By';
                        $data->query->orderBy('activatedOn', 'DESC');
                        break;
                    case 'deliveryDate':
                        arr::placeInAssocArray($data->listFields, array('deliveryDate' => 'Дата за доставка'), 'modifiedOn');
                        $data->query->orderBy('deliveryDate', 'ASC');
                        break;
                    case 'expectedDueDate':
                        arr::placeInAssocArray($data->listFields, array('expectedDueDate' => 'Очак. падеж'), null, 'dueDate');
                        $data->query->orderBy('expectedDueDate', 'ASC');
                        break;
                }
            }

            // Филтър по изглед
            if (isset($filter->view)) {
                switch ($filter->view) {
                    case 'draft':
                    case 'active':
                    case 'stopped':
                    case 'closed':
                    case 'wakeup':
                        $data->query->where("#state = '{$filter->view}'");
                        break;
                    case 'all':
                        break;
                    case 'overdue':
                        $data->query->where("#dueDate < #expectedDueDate");
                    case 'progress':
                        $data->query->XPR('progress', 'double', 'ROUND(#quantity / COALESCE(#quantityProduced, 0), 2)');
                        $data->query->where("#state = 'active'");
                        $data->query->orderBy('progress', 'DESC');
                        break;
                    case 'activenotasks':
                        $tQuery = planning_Tasks::getQuery();
                        $tQuery->where('#originId IS NOT NULL');
                        $tQuery->EXT('docClass', 'doc_Containers', 'externalName=docClass,externalKey=originId');
                        $tQuery->EXT('docId', 'doc_Containers', 'externalName=docId,externalKey=originId');
                        $tQuery->where('#originId IS NOT NULL');
                        $tQuery->where("#docClass = {$mvc->getClassId()}");
                        $tQuery->show('docId');
                        $jobIdsWithTasks = arr::extractValuesFromArray($tQuery->fetchAll(), 'docId');
                        $data->query->where("#state = 'active'");

                        if (countR($jobIdsWithTasks)) {
                            $data->query->notIn('id', $jobIdsWithTasks);
                        }

                        break;
                }
            }
        }
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    public function renderSingle_($data)
    {
        $tpl = parent::renderSingle_($data);
        $tpl->push('planning/tpl/styles.css', 'CSS');
        
        // Рендираме историята на действията със заданието
        if (countR($data->row->history)) {
            foreach ($data->row->history as $hRow) {
                $clone = clone $tpl->getBlock('HISTORY_ROW');
                $clone->placeObject($hRow);
                $clone->removeBlocks();
                $clone->append2master();
            }
        }

        $packagingTpl = cls::get('cat_products_Packagings')->renderPackagings($data->packagingData);
        $tpl->replace($packagingTpl, 'PACKAGINGS');
        
        if (countR($data->components)) {
            $componentTpl = cat_Products::renderComponents($data->components);
            $tpl->append($componentTpl, 'JOB_COMPONENTS');
        }
        
        return $tpl;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = &$data->rec;
        $issueBtnRow = $rec->state == 'closed' ? 1 : 2;
        $data->toolbar->setBtnAttr("issueBtn{$rec->containerId}", 'row', $issueBtnRow);
        if (cat_Boms::haveRightFor('add', (object) array('productId' => $rec->productId, 'type' => 'production', 'originId' => $rec->containerId))) {
            $data->toolbar->addBtn('Рецепта', array('cat_Boms', 'add', 'productId' => $rec->productId, 'originId' => $rec->containerId, 'quantityForPrice' => $rec->quantity, 'ret_url' => true, 'type' => 'production'), 'ef_icon = img/16/add.png,title=Създаване на нова работна рецепта,row=2');
        }
        
        // Бутон за добавяне на документ за производство
        if (planning_DirectProductionNote::haveRightFor('add', (object) array('originId' => $rec->containerId))) {
            $pUrl = array('planning_DirectProductionNote', 'add', 'originId' => $rec->containerId, 'ret_url' => true);
            $data->toolbar->addBtn('Произвеждане', $pUrl, 'ef_icon = img/16/page_paste.png,title=Създаване на протокол за производство от заданието');
        }

        // Бутон за добавяне на протокол за разпад
        if (planning_DisassemblyNote::haveRightFor('add', (object) array('originId' => $rec->containerId))) {
            $pUrl = array('planning_DisassemblyNote', 'add', 'originId' => $rec->containerId, 'ret_url' => true);
            $data->toolbar->addBtn('Разпад', $pUrl, 'ef_icon=img/16/protocol_decay.png,title=Създаване на протокол за разпад от заданието');
        }
        
        // Бутон за добавяне на документ за влагане
        if (planning_ConsumptionNotes::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
            $pUrl = array('planning_ConsumptionNotes', 'add', 'threadId' => $rec->threadId, 'ret_url' => true);
            $data->toolbar->addBtn('Влагане', $pUrl, 'ef_icon = img/16/produce_in.png,title=Създаване на протокол за влагане към заданието');
        }
        
        // Бутон за добавяне на документ за влагане
        if (planning_ConsumptionNotes::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
            $pUrl = array('planning_ReturnNotes', 'add', 'threadId' => $rec->threadId, 'ret_url' => true);
            $data->toolbar->addBtn('Връщане', $pUrl, 'ef_icon = img/16/produce_out.png,title=Създаване на протокол за връщане към заданието');
        }

        if (haveRole('debug')) {
            $data->toolbar->addBtn('Преподреди', array($mvc, 'manualreorder', $rec->id), 'ef_icon = img/16/bug.png,title=Преподреждане на операциите в заданието,row=2');
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

            // Подаденият вид (Производство/Разпад) трябва да отговаря на свойствата на артикула
            if (isset($rec->productId) && isset($rec->type)) {
                $productTypeRec = cat_Products::fetch($rec->productId, 'canManifacture,canConvert,canStore');
                $canManifacture = ($productTypeRec->canManifacture == 'yes');
                $canDisassemble = ($productTypeRec->canConvert == 'yes' && $productTypeRec->canStore == 'yes');
                if (($rec->type == 'manifacture' && !$canManifacture) || ($rec->type == 'disassembly' && !$canDisassemble)) {
                    $form->setError('type', 'Избраният вид не отговаря на свойствата на артикула');
                }
            }

            if (isset($rec->deliveryDate) && $rec->deliveryDate < $rec->dueDate) {
                $form->setWarning('deliveryDate', 'Срокът за доставка не може да е преди падежа');
            }
            
            if ($rec->dueDate < dt::today()) {
                $form->setWarning('dueDate', 'Падежът е в миналото');
            }

            //  Проверка има ли други активни задания
            $id = isset($rec->clonedFromId) ? null : $rec->id;
            if(!haveRole('debug')){
                if ($aCount = self::count("#productId = {$rec->productId} AND (#state = 'active' OR #state = 'stopped' OR #state = 'wakeup') AND #id != '{$id}'")) {
                    $aCount = core_Type::getByName('int')->toVerbal($aCount);
                    $msg = ($aCount == 1) ? 'активно задание' : 'активни задания';
                    $form->setWarning('productId', "В момента артикулът има още|* <b>{$aCount}</b> |{$msg}|*. |Желаете ли да създадете още едно|*?");
                }
            }

            $productInfo = cat_Products::getProductInfo($form->rec->productId);
            $rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
            $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
            $rec->isEdited = true;
            
            $brutoWeight = cat_Products::getTransportWeight($rec->productId, $rec->quantity);
            $rec->brutoWeight = (!empty($brutoWeight)) ? $brutoWeight : null;
            
            $nettoWeight = cat_Products::convertToUom($rec->productId, 'kg');
            $rec->weight = (!empty($nettoWeight)) ? $nettoWeight : null;

            if($rec->allowSecondMeasure == 'no'){
                unset($rec->secondMeasureId);
            }

            if($form->cmd == 'save_n_new'){
                $rec->_activateAfterCreation = true;
            }
        }
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    protected static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (empty($rec->quantity) || empty($rec->quantityInPack)) {
            
            return;
        }
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Преди запис на документ
     *
     * @param core_Mvc $mvc
     * @param stdClass     $rec
     */
    protected static function on_BeforeSave($mvc, &$id, $rec, $fields = null, $mode = null)
    {
        list($sourceClass) = self::getSourceInfo($rec);

        // Ако заданието е към сделка и е избран департамент, да се рутира към него
        if (empty($rec->id) && isset($sourceClass) && isset($rec->department)) {

            // Ако заданието е към сделка и има избран център, рутира се към него
            $oldThreadId = $rec->threadId;
            $rec->folderId = planning_Centers::forceCoverAndFolder($rec->department);
            $rec->threadId = doc_Threads::create($rec->folderId, $rec->createdOn, $rec->createdBy);

            // Обновяване на информацията за контейнера и старата нишка, че документ се е преместил оттам
            $cRec = doc_Containers::fetch($rec->containerId);
            $cRec->threadId = $rec->threadId;
            doc_Containers::save($cRec, 'threadId, modifiedOn, modifiedBy');
            doc_Threads::updateThread($oldThreadId);
        }

        if ($rec->isEdited === true && isset($rec->id) && $rec->_isClone !== true && empty($rec->_activateAfterCreation)) {
            self::addToHistory($rec->history, 'edited', $rec->modifiedOn, $rec->modifiedBy);
        }

        if(isset($rec->id)){
            if($rec->isEdited){
                $exDueDate = $mvc->fetchField($rec->id, 'dueDate', false);
                if($exDueDate != $rec->dueDate){
                    $rec->_dueDateChanged = true;
                }
            }
        } else {
            $rec->_dueDateChanged = true;
        }
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        // Записваме в историята на действията, че кога и от кого е създаден документа
        self::addToHistory($rec->history, 'created', $rec->createdOn, $rec->createdBy);
        $mvc->save_($rec, 'history');

        if(isset($rec->_activateAfterCreation)){
            planning_plg_StateManager::changeState($mvc, $rec, 'activate');
       }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->title = ($fields['-single']) ? $mvc->getRecTitle($rec) : $mvc->getLink($rec->id);
        if ($rec->type == 'disassembly') {
            $row->disassemblyLabel = 'РАЗПАД';

            // За Разпад "Заскладено" показва quantityDisassembled, не quantityProduced
            // (последното никога не се пълни за Разпад - виж on_BeforeGetRequiredRoles).
            $rec->quantityProduced = $rec->quantityDisassembled;
        }
        $row->quantity = $mvc->getFieldType('quantity')->toVerbal($rec->quantityFromTasks);
        $Double = core_Type::getByName('double(smartRound)');
        $quantityProduced = $rec->quantityProduced;
        $originalQuantity = $quantityProduced;

        $measureId = cat_Products::fetchField($rec->productId, 'measureId');
        $measureName = tr(cat_UoM::getShortName($measureId));
        if (isset($rec->productId) && empty($fields['__isDetail'])) {
            $rec->quantityFromTasks = planning_Tasks::getProducedQuantityForJob($rec);
            $rec->quantityFromTasks = round($rec->quantityFromTasks, 5);
            $row->quantityFromTasks = $Double->toVerbal($rec->quantityFromTasks);
        }

        // Ако има втора мярка
        $coefficient = null;
        if(isset($rec->secondMeasureId)){
            $derivativeMeasures = cat_UoM::getSameTypeMeasures($rec->secondMeasureId);
            $secondMeasureQuantity = isset($rec->secondMeasureQuantity) ? $rec->secondMeasureQuantity : 0;

            // Ако заданието е в нея, ще се показват разменени местата на количествата
            if(array_key_exists($rec->packagingId, $derivativeMeasures)){
                $secondMeasureQuantity = cat_UoM::convertValue($secondMeasureQuantity, $rec->secondMeasureId, $rec->packagingId);
                $quantityProduced = $secondMeasureQuantity;
                $row->quantityProduced = $Double->toVerbal($secondMeasureQuantity);
                $secondMeasureNameHint = $secondMeasureName = $measureName;
                $secondMeasureQuantityVerbal = $Double->toVerbal($rec->quantityProduced);
                $measureName = tr(cat_UoM::getShortName($rec->secondMeasureId));

                // Ако има коефициент показва се колко е той
                if($rec->secondMeasureQuantity){
                    $coefficient = $originalQuantity / $rec->secondMeasureQuantity;
                }
            } else {
                $rec->quantityProduced /= $rec->quantityInPack;
                $row->quantityProduced = $Double->toVerbal($rec->quantityProduced);
                $quantityProduced = $rec->quantityProduced;
                $secondMeasureQuantityVerbal = $Double->toVerbal($secondMeasureQuantity);

                $secondMeasureNameHint = $measureName;
                $measureName = tr(cat_UoM::getShortName($rec->secondMeasureId));
                $secondMeasureName = $measureName;

                // Ако има коефициент показва се колко е той
                if($rec->secondMeasureQuantity){
                    $coefficient =  $originalQuantity / $rec->secondMeasureQuantity;
                }
            }

            // Ако има сметнат коефициент, показва се колко е той
            if(isset($coefficient)){
                $coefficientVerbal = core_Type::getByName('double(smartRound)')->toVerbal($coefficient);
                $hint = "|* 1 {$measureName} |е|* {$coefficientVerbal} {$secondMeasureNameHint}";
                $secondMeasureName = ht::createHint($secondMeasureName, $hint);
            }
            $row->quantityProduced = "{$row->quantityProduced} <span style='font-weight:normal;color:darkblue;font-style:italic;' class='secondMeasure'>({$secondMeasureQuantityVerbal} {$secondMeasureName}) </span>";
        } else {

            // Ако няма втора мярка, всичко се конвертира в опаковката
            $rec->quantityProduced /= $rec->quantityInPack;
            $quantityProduced = $rec->quantityProduced;
            $row->quantityProduced = $Double->toVerbal($rec->quantityProduced);
        }

        // Ако има втора мярка
        if(!empty($rec->secondMeasureId)){

            // Ако заданието е във втората мярка, то ще се показва, че ще се отчита в основната
            $derivativeMeasures = cat_UoM::getSameTypeMeasures($rec->secondMeasureId);
            if(array_key_exists($rec->packagingId, $derivativeMeasures)){
                $mandatoryMeasure = cat_Products::fetchField($rec->productId, 'measureId');
            } else {
                $mandatoryMeasure = $rec->secondMeasureId;
            }
            $row->secondMeasureId = cat_UoM::getVerbal($mandatoryMeasure, 'name');
        }

        $rec->quantityNotStored = $rec->quantityFromTasks - $quantityProduced;
        $row->quantityNotStored = $Double->toVerbal($rec->quantityNotStored);

        // При Разпад "Очаквано още" се смята спрямо разпаднатото (quantityDisassembled),
        // а не спрямо quantityFromTasks (винаги 0 за Разпад - няма Задачи/протоколи за производство)
        if ($rec->type == 'disassembly') {
            $rec->quantityToProduce = $rec->packQuantity - $quantityProduced;
        } else {
            $rec->quantityToProduce = $rec->packQuantity - (($rec->quantityFromTasks) ? $rec->quantityFromTasks : $quantityProduced);
        }
        $row->quantityToProduce = $Double->toVerbal($rec->quantityToProduce);
        
        foreach (array('quantityNotStored', 'quantityToProduce') as $fld) {
            if ($rec->{$fld} < 0) {
                $row->{$fld} = "<span class='red'>{$row->{$fld}}</span>";
            }
        }
        
        if (cat_Boms::haveRightFor('add', (object) array('productId' => $rec->productId, 'type' => 'production', 'originId' => $rec->containerId))) {
            core_RowToolbar::createIfNotExists($row->_rowTools);
            $row->_rowTools->addLink('Работна рецепта', array('cat_Boms', 'add', 'productId' => $rec->productId, 'originId' => $rec->containerId, 'quantityForPrice' => $rec->quantity, 'ret_url' => true, 'type' => 'production'), "ef_icon=img/16/article.png,title=Създаване на нова работна рецепта");
        }
        
        if (isset($fields['-list'])) {
            $row->productId = ($fields['__isDetail']) ? cat_Products::getLink($rec->productId, 0) : cat_Products::getHyperlink($rec->productId, true);
            if ($rec->quantityNotStored > 0) {
                if (planning_DirectProductionNote::haveRightFor('add', (object) array('originId' => $rec->containerId))) {
                    core_RowToolbar::createIfNotExists($row->_rowTools);
                    $row->_rowTools->addLink('Произвеждане', array('planning_DirectProductionNote', 'add', 'originId' => $rec->containerId, 'ret_url' => true), array('order' => 19, 'ef_icon' => 'img/16/page_paste.png', 'title' => 'Създаване на протокол за производство'));
                    $row->quantityNotStored = ht::createHint($row->quantityNotStored, 'Заданието очаква да се създаде протокол за производство', 'warning', false);
                }

                if (planning_DisassemblyNote::haveRightFor('add', (object) array('originId' => $rec->containerId))) {
                    core_RowToolbar::createIfNotExists($row->_rowTools);
                    $row->_rowTools->addLink('Разпад', array('planning_DisassemblyNote', 'add', 'originId' => $rec->containerId, 'ret_url' => true), array('order' => 19, 'ef_icon' => 'img/16/protocol_decay.png', 'title' => 'Създаване на протокол за разпад'));
                }
            }
            
            $row->quantityNotStored = "<div class='fright'>{$row->quantityNotStored}</div>";
        }
        
        list($sourceClass, $sourceId) = self::getSourceInfo($rec);
        if (isset($sourceClass)) {
            $row->sourceId = ($fields['__isDetail']) ? $sourceClass::getLink($sourceId, 0) : $sourceClass::getLink($sourceId);
            $sourceRec = $sourceClass::fetch($sourceId, 'folderId,deliveryAdress,state');
            $row->sourceFolderId = doc_Folders::recToVerbal(doc_Folders::fetch($sourceRec->folderId))->title;
            if (!empty($sourceRec->deliveryAdress)) {
                $row->sourceDeliveryAddress = core_Type::getByName('varchar')->toVerbal($sourceRec->deliveryAdress);
            }
            $row->sourceId = "<span class='state-{$sourceRec->state} document-handler'>{$row->sourceId}</span>";
        }
        
        $row->measureId = cat_UoM::getShortName($rec->packagingId);
        $tolerance = ($rec->tolerance) ? $rec->tolerance : 0;
        $diff = $rec->packQuantity * $tolerance;

        foreach (array('quantityFromTasks', 'quantityProduced') as $fld) {
            $quantityValue = ($fld == 'quantityFromTasks') ? $rec->quantityFromTasks : $quantityProduced;
            if ($quantityValue < ($rec->packQuantity - $diff)) {
                $color = 'black';
            } elseif (round($quantityValue, 3) >= round(($rec->packQuantity - $diff), 3) && round($quantityValue, 3) <= round(($rec->packQuantity + $diff), 3)) {
                $color = 'green';
            } else {
                $row->{$fld} = ht::createHint($row->{$fld}, 'Произведено е повече от планираното', 'warning', false);
                $color = 'red';
            }
            
            if ($quantityValue != 0) {
                $quantityRow = new core_ET("<span style='color:[#color#]'>[#quantity#]</span>");
                $quantityRow->placeArray(array('color' => $color, 'quantity' => $row->{$fld}));
                $row->{$fld} = $quantityRow;
            }
        }

        foreach (array('quantityProduced', 'quantityToProduce', 'quantityFromTasks', 'quantityNotStored') as $fld) {
            if (empty($rec->{$fld})) {
                $row->{$fld} = "<b class='quiet'>{$row->{$fld}}</b>";
            }
        }

        if($rec->expectedDueDate > $rec->dueDate){
            $row->dueDate = "<b style='color:#cc0000'>{$row->dueDate}</b>";
            $expectedDueDateVerbal = dt::mysql2verbal($rec->expectedDueDate, 'd.m.Y');
            $row->dueDate = ht::createHint($row->dueDate, "Зададеният падеж е преди очаквания|*: {$expectedDueDateVerbal}", 'warning');
        }

        if (isset($fields['-single'])) {
            if ($rec->type == 'disassembly') {
                $row->captionProduced = tr('Разпаднато');
                $row->captionNotStored = tr('Неразпаднато');
            } else {
                $canStore = cat_Products::fetchField($rec->productId, 'canStore');
                $row->captionProduced = ($canStore == 'yes') ? tr('Заскладено') : tr('Изпълнено');
                $row->captionNotStored = ($canStore == 'yes') ? tr('Незаскладено') : tr('Неизпълнено');
            }
            
            if (isset($rec->deliveryPlace)) {
                $row->deliveryPlace = crm_Locations::getHyperlink($rec->deliveryPlace, true);
            }

            if (isset($rec->oldJobId)) {
                $row->oldJobId = planning_Jobs::getLink($rec->oldJobId, 0);

                $oldJobProductId = planning_Jobs::fetchField($rec->oldJobId, 'productId');
                $row->oldJobCaption = ($rec->productId != $oldJobProductId) ? tr('Подобно задание') : tr('Предходно задание');
            }

            foreach (array('sBomId' => array('salesBomIdOnActivation', 'sales'), 'iBomId' => array('instantBomIdOnActivation', 'instant'), 'pBomId' =>  array('productionBomIdOnActivation', 'production')) as $bomFld => $activationBomArr) {
                if ($bomId = cat_Products::getLastActiveBom($rec->productId, $activationBomArr[1])->id) {
                    $row->{$bomFld} = cat_Boms::getLink($bomId, 0);
                    if(isset($rec->{$activationBomArr[0]}) && $bomId != $rec->{$activationBomArr[0]}){
                        $oldBomHandle = cat_Boms::getHandle($rec->{$activationBomArr[0]});
                        $row->{$bomFld} = ht::createHint($row->{$bomFld}, "Рецептата при активиране е била|*: #{$oldBomHandle}", 'warning');
                    }
                }
            }

            $date = ($rec->state == 'draft') ? null : (!empty($rec->productViewCacheDate) ? $rec->productViewCacheDate : $rec->modifiedOn);
            if(!empty($date)){
                $row->title = ht::createHint($row->title, "Описанието на артикула е към дата|*: {$mvc->getFieldType('productViewCacheDate')->toVerbal($date)}");
            }
            $lg = core_Lg::getCurrent();
            $row->origin = cat_Products::getAutoProductDesc($rec->productId, $date, 'detailed', 'job', $lg);

            if (isset($rec->department)) {
                $row->department = planning_Centers::getHyperlink($rec->department, true);
            }
            
            if (isset($rec->storeId)) {
                $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
            }

            if(!empty($rec->deliveryTermId)){
                $row->deliveryTermId = cond_DeliveryTerms::getHyperlink($rec->deliveryTermId, true);
            }

            // Показване и на к-то с очаквания произв. брак
            if(isset($rec->productionScrap) && $rec->type != 'disassembly'){
                $packQuantityWithScrap = $rec->packQuantity * (1 + $rec->productionScrap);
                $packQuantityWithScrapVerbal = core_Type::getByName('double(smartRound)')->toVerbal($packQuantityWithScrap);
                $row->packQuantityWithScrap = ht::createHint($packQuantityWithScrapVerbal, "Техн. брак|*: {$row->productionScrap}");
            }

            // При Разпад "Технологичен брак" и "Толеранс" нямат смисъл - скриваме ги
            if ($rec->type == 'disassembly') {
                unset($row->tolerance);
                unset($row->productionScrap);
            }

            $row->singleTitle = ($rec->type == 'disassembly') ? tr('Задание за разпад') : tr('Задание за производство');
        }
        
        if(!empty($rec->quantityFromTasks)){
            $row->measureId2 = $row->measureId;
            $row->quantityFromTasksCaption = tr('Произведено');
        } else {
            unset($row->quantityFromTasks);
            unset($row->captionNotStored);
            unset($row->quantityNotStored);
        }
    }


    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $rec = static::fetchRec($rec);
        $pTitle = cat_Products::getTitleById($rec->productId, $escaped);
        $title = "Job{$rec->id} - {$pTitle}";

        return $title;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $this->getRecTitle($rec);
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;

        if($rec->type == 'disassembly'){
            $row->subTitle = tr('Разпад');
        }

        return $row;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if (($action == 'write' || $action == 'add' || $action == 'edit') && isset($rec)){

            $jobType = $rec->type ?? 'manifacture';
            $requiredJobRole = ($jobType == 'disassembly') ? 'jobDisassembly' : 'job';

            if (!haveRole("ceo,{$requiredJobRole}", $userId)) {
                $res = 'no_one';
            }

            if(isset($rec->productId)) {
                $productRec = cat_Products::fetch($rec->productId, 'state,canManifacture,canConvert,canStore,generic,innerClass');

                // Трябва да е активиран и да не е производствен етап
                if ($productRec->state != 'active' || $productRec->innerClass == planning_interface_StepProductDriver::getClassId()) {
                    $res = 'no_one';
                }

                // Свойствата на артикула трябва да отговарят на вида на заданието
                if ($res != 'no_one') {
                    $canManifacture = ($productRec->canManifacture == 'yes');
                    $canDisassemble = ($productRec->canConvert == 'yes' && $productRec->canStore == 'yes');
                    $canUseForJob = ($jobType == 'disassembly') ? $canDisassemble : $canManifacture;
                    if (!$canUseForJob || $productRec->generic == 'yes') {
                        $res = 'no_one';
                    }
                }
            }

            // Ако се създава към продажба/покупка, тя трябва да е активна
            list($sourceClass, $sourceId) = self::getSourceInfo($rec);
            if (isset($sourceClass)) {
                $sourceState = $sourceClass::fetchField($sourceId, 'state');
                if (!in_array($sourceState, array('active', 'closed', 'pending'))) {
                    $res = 'no_one';
                } else {
                    $products = $sourceClass::getProducts4Job($sourceId, true, $jobType);
                    if (!countR($products)) {
                        $res = 'no_one';
                    }
                }
            }
        }

        // Ако няма ид, не може да се активира
        if ($action == 'activate' && empty($rec->id)) {
            $res = 'no_one';
        }
        
        // Само спрените могат да се променят
        if ($action == 'changerec' && isset($rec)) {
            $state = isset($rec->id) ? $mvc->fetchField($rec->id, 'state', false) : $rec->state;
            if ($state != 'stopped') {
                $res = 'no_one';
            }
        }
        
        if ($action == 'close' && $rec) {
            if ($rec->state != 'active' && $rec->state != 'wakeup' && $rec->state != 'stopped') {
                $res = 'no_one';
            }
        }

        if($action == 'reject' && isset($rec)){
            if(!haveRole('job,ceo', $userId)){
                $res = 'no_one';
            }
        }
    }


    /**
     * 2 бутона "Нов" - за производство и за разпад, вместо общ бутон за класа
     *
     * @see doc_Containers::getNewDocMenu()
     * @see doc_DocumentPlg::on_AfterGetNewBtnVariants()
     */
    public function getNewBtnVariants_($rec)
    {
        return array(
            array('title' => 'Задание за производство', 'icon' => $this->singleIcon, 'params' => array('type' => 'manifacture')),
            array('title' => 'Задание за разпад', 'icon' => $this->singleIcon, 'params' => array('type' => 'disassembly')),
        );
    }


    /**
     * Добавя действие към историята
     *
     * @param array    $history - масив с историята
     * @param string   $action  - действие
     * @param datetime $date    - кога
     * @param int      $userId  - кой
     *
     * @return void
     */
    private static function addToHistory(&$history, $action, $date, $userId, $reason = null)
    {
        if (!is_array($history)) {
            $history = array();
        }
        
        $arr = array('action' => self::$actionNames[$action], 'date' => $date, 'user' => $userId, 'engaction' => $action);
        if (isset($reason)) {
            $arr['reason'] = $reason;
        }
        
        $history[] = $arr;
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    protected static function on_AfterActivation($mvc, &$rec)
    {
        // След активиране на заданието, добавяме артикула като перо
        $listId = acc_Lists::fetchBySystemId('catProducts')->id;
        acc_Items::force('cat_Products', $rec->productId, $listId);
        
        // След активиране на заданието, ако е към продажба, форсираме я като разходно перо
        if (isset($rec->saleId)) {
            if (cat_Products::fetchField($rec->productId, 'canStore') == 'no') {

                $saleRec = sales_Sales::fetch($rec->saleId, 'id,containerId,state');
                if(in_array($saleRec->state, array('active', 'closed'))) {
                    if (!acc_Items::isItemInList('sales_Sales', $saleRec->id, 'costObjects')) {
                        $listId = acc_Lists::fetchBySystemId('costObjects')->id;
                        acc_Items::force('sales_Sales', $saleRec->id, $listId);
                        sales_Sales::logWrite('Става разходно перо, след активиране на задание', $saleRec->id);

                        $costObj = (object) array('containerId' => $saleRec->containerId);
                        doc_ExpensesSummary::save($costObj);
                    }
                }
            }
        }

        // Кеширане на актуалните рецепти към момента на активиране
        foreach (array('salesBomIdOnActivation' => 'sales', 'instantBomIdOnActivation' => 'instant', 'productionBomIdOnActivation' => 'production') as $bomFld => $bomType){
            if ($bId = cat_Products::getLastActiveBom($rec->productId, $bomType)->id) {
                $rec->{$bomFld} = $bId;
            }
        }

        $rec->productViewCacheDate = dt::now();
        $mvc->save_($rec, 'productViewCacheDate,salesBomIdOnActivation,instantBomIdOnActivation,productionBomIdOnActivation');
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $rec = &$data->rec;
        $row = &$data->row;

        // Подготвяме данните на историята за показване
        $row->history = array();
        if (is_array($rec->history)) {
            foreach ($rec->history as $historyRec) {
                $historyRec['action'] = tr($historyRec['action']);
                
                $historyRow = (object) array('date' => cls::get('type_DateTime')->toVerbal($historyRec['date']),
                    'user' => crm_Profiles::createLink($historyRec['user']),
                    'action' => "<span>{$historyRec['action']}</span>",
                    'stateclass' => "state-{$historyRec['engaction']}");
                
                if (isset($historyRec['reason'])) {
                    $historyRow->reason = cls::get('type_Text')->toVerbal($historyRec['reason']);
                }

                $row->history[] = $historyRow;
            }
        }

        $row->history = array_reverse($row->history, true);
        $data->packagingData = static::getJobProductPackagingData($rec);
        $data->components = array();
        cat_Products::prepareComponents($rec->productId, $data->components, 'job', $rec->quantity);

        if(isset($rec->oldJobId)) {
            $oldJobRec = $mvc->fetch($rec->oldJobId);
            $oldJobCacheDate = !empty($oldJobRec->productViewCacheDate) ? $oldJobRec->productViewCacheDate : $oldJobRec->modifiedOn;
            $oldJobOrigin = cat_Products::getAutoProductDesc($oldJobRec->productId, $oldJobCacheDate, 'detailed', 'job', core_Lg::getCurrent());

            $newOriginHtml = str_replace('&nbsp;', '', strip_tags(str::removeWhiteSpace($oldJobOrigin->getContent())));
            $oldOriginHtml = str_replace('&nbsp;', '', strip_tags(str::removeWhiteSpace($row->origin)));


            if(md5($newOriginHtml) != md5($oldOriginHtml)){
                $cUrl = getCurrentUrl();
                if(Request::get('showDiff')){
                    unset($cUrl['showDiff']);
                    $changeIcon = "img/16/checked-red.png";
                    $changeTitle = 'Скриване на разликите';
                } else {
                    $cUrl['showDiff'] = true;
                    $changeIcon = "img/16/checked-not-green.png";
                    $changeTitle = 'Показване на разликите';
                }
                $row->showDiffBtn = ht::createLink('', $cUrl, false, "title={$changeTitle},ef_icon={$changeIcon}");
            }

            if(Request::get('showDiff')){
                $row->origin = lib_Diff::getDiff(str_replace('&nbsp;', ' ', $oldJobOrigin->getContent()), str_replace('&nbsp;', ' ', $row->origin));
            }
        }
    }


    /**
     * Подготвя информацията за опаковките на заданието
     *
     * @param stdClass $rec
     * @return stdClass $packagingData
     */
    public static function getJobProductPackagingData($rec)
    {
        $packagingData = new stdClass();
        $packagingData->masterMvc = cls::get('cat_Products');
        $packagingData->masterId = $rec->productId;
        $packagingData->tpl = new core_ET('[#CONTENT#]');
        $packagingData->retUrl = planning_Jobs::getSingleUrlArray($rec->id);
        if ($rec->state == 'rejected') {
            $packagingData->rejected = true;
        }
        cls::get('cat_products_Packagings')->preparePackagings($packagingData);
        $packagingData->listFields['packagingId'] = 'Опаковка';

        return $packagingData;
    }


    /**
     * След промяна на състоянието
     */
    protected static function on_AfterChangeState($mvc, &$rec, $action)
    {
        $updateFields = array('history');
        if(($rec->_updateProductParams ?? 'no') == 'yes'){
            $rec->productViewCacheDate = dt::now();
            $updateFields[] = 'productViewCacheDate';
        }

        // Записваме в историята действието
        self::addToHistory($rec->history, $action, $rec->modifiedOn, $rec->modifiedBy, $rec->_reason);
        $mvc->save_($rec, $updateFields);
        
        // Ако заданието е затворено, затваряме и задачите към него
        if($rec->state == 'stopped' || ($rec->brState == 'stopped' && $rec->state == 'active')){
            $Tasks = cls::get('planning_Tasks');

            $inStates = ($rec->state == 'stopped') ? array('active', 'wakeup') : array('stopped');
            $action = ($rec->state == 'stopped') ? 'stop' : 'activateAgain';
            $msg = ($rec->state == 'stopped') ? 'Спрени' : 'Пуснати';
            $taskRecs = planning_Tasks::getTasksByJob($rec->id, $inStates, false);
            $syncedCount = countR($taskRecs);
            foreach ($taskRecs as $tRec){
                planning_plg_StateManager::changeState($Tasks, $tRec, $action);
            }

            if($syncedCount){
                $syncedCountVerbal = core_Type::getByName('int')->toVerbal($syncedCount);
                core_Statuses::newStatus("{$msg} са|* {$syncedCountVerbal} |операции|*!");
            }
        }

        doc_Containers::touchDocumentsByOrigin($rec->containerId);
    }
    
    
    /**
     * Връща заявка към протоколите за производство на произведения артикул
     * 
     * @param stdClass $rec
     * @return core_Query $noteQuery
     */
    private static function getJobProductionNotesQuery($rec)
    {
        $rec = static::fetchRec($rec);
        
        // Всички задачи за производството на артикула от заданието
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#originId = {$rec->containerId} AND #state != 'draft' AND #state != 'rejected'");
        $tQuery->show('containerId');

        $containerIds = arr::extractValuesFromArray($tQuery->fetchAll(), 'containerId');
        $containerIds[$rec->containerId] = $rec->containerId;
        
        $noteQuery = planning_DirectProductionNote::getQuery();
        $noteQuery->in("originId", $containerIds);
        $noteQuery->where("#state = 'active' AND #productId = {$rec->productId}");

        return $noteQuery;
    }


    /**
     * Преизчисляваме какво количество е произведено (или разпаднато) по заданието
     *
     * @param int    $containerId - ид на запис
     * @return void
     */
    public static function updateProducedQuantity($containerId)
    {
        $me = cls::get(get_called_class());
        $rec = static::fetch("#containerId = {$containerId}");
        $noteQuery = self::getJobProductionNotesQuery($rec);
        $allRecs = $noteQuery->fetchAll();

        $totalQuantity = arr::sumValuesArray($allRecs, 'quantity');
        $rec->quantityProduced = empty($totalQuantity) ? 0 : $totalQuantity;

        // Ако артикулът поддържа втора мярка
        $saveFields = 'quantityProduced';

        if($rec->secondMeasureId) {
            $secondMeasureDerivities = cat_UoM::getSameTypeMeasures($rec->secondMeasureId);
            unset($secondMeasureDerivities['']);

            // Сумиране на произведеното във втора мярка
            $rec->secondMeasureQuantity = 0;
            foreach($allRecs as $noteRec){
                if(array_key_exists($noteRec->packagingId, $secondMeasureDerivities)){
                    $rec->secondMeasureQuantity += cat_UoM::convertValue($noteRec->packQuantity, $noteRec->packagingId, $rec->secondMeasureId);
                } elseif(array_key_exists($noteRec->additionalMeasureId, $secondMeasureDerivities)){
                    $rec->secondMeasureQuantity += cat_UoM::convertValue($noteRec->additionalMeasureQuantity, $noteRec->additionalMeasureId, $rec->secondMeasureId);
                }
            }

            $rec->secondMeasureQuantity = round($rec->secondMeasureQuantity, 5);
            $saveFields .= ',secondMeasureQuantity';
        }

        $me->save_($rec, $saveFields);
        $me->touchRec($rec);
    }


    /**
     * Преизчислява какво количество от заданието е разпаднато (за type=disassembly) -
     * сумата от количествата на редовете за влагане (type=input) на активните
     * протоколи за разпад по заданието (@see planning_DisassemblyNote)
     *
     * @param int $containerId - ид на запис
     * @return void
     */
    public static function updateDisassembledQuantity($containerId)
    {
        $me = cls::get(get_called_class());
        $rec = static::fetch("#containerId = {$containerId}");

        $dQuery = planning_DisassemblyNoteDetails::getQuery();
        $dQuery->EXT('noteState', 'planning_DisassemblyNote', 'externalName=state,externalKey=noteId');
        $dQuery->EXT('noteOriginId', 'planning_DisassemblyNote', 'externalName=originId,externalKey=noteId');
        $dQuery->where("#type = 'input' AND #noteState = 'active' AND #noteOriginId = {$rec->containerId}");

        $totalQuantity = arr::sumValuesArray($dQuery->fetchAll(), 'quantity');
        $rec->quantityDisassembled = empty($totalQuantity) ? 0 : $totalQuantity;

        $me->save_($rec, 'quantityDisassembled');
        $me->touchRec($rec);
    }


    /**
     * Селектиране на действие при създаване на нова задача
     */
    public function act_selectTaskAction()
    {
        planning_Tasks::requireRightFor('add');
        expect($originId = Request::get('originId', 'int'));
        planning_Tasks::requireRightFor('add', (object) array('originId' => $originId));
        $jobRec = doc_Containers::getDocument($originId)->fetch();
        $folderId = (!empty($jobRec->department)) ? planning_Centers::fetchField($jobRec->department, 'folderId') : $jobRec->folderId;
        if(!planning_Tasks::canAddToFolder($folderId)){
            $folderId = planning_Centers::getUndefinedFolderId();
        }

        $form = cls::get('core_Form');
        $form->title = 'Създаване на производствени операция към|* <b>' . self::getHyperlink($jobRec->id, true) . '</b>';
        $form->info = getTplFromFile('planning/tpl/SelectTaskInJobForm.shtml');
        $options = array();

        $defaultTasks = cat_Products::getDefaultProductionTasks($jobRec, $jobRec->quantity);

        if(countR($defaultTasks)){
            $options[] = (object)array('DEFAULT_TASK_CAPTION' => tr('Шаблонни операции за артикула'), 'DEFAULT_TASK_LINK' => null, 'DEFAULT_TASK_TR_CLASS' => 'selectTaskFromJobRow', 'DEFAULT_TASK_CAPTION_COLSPAN' => 2);
            $createAllUrl = array();
            if(planning_Tasks::haveRightFor('createjobtasks', (object)array('jobId' => $jobRec->id, 'type' => 'all'))){
                $title = tr('Автоматично на всички избрани / маркирани операции:');
                $createAllUrl = array('planning_Tasks', 'createjobtasks', 'type' => 'all', 'jobId' => $jobRec->id, 'ret_url' => true);
                $createAllUrlString = toUrl($createAllUrl);
                $urlLinkBtn = ht::createFnBtn('Създаване', null, 'Ще бъдат създадени всички маркирани шаблонни операции|*?', array('title' => 'Автоматично / едновременно създаване на всички маркирани от шаблонните операции за артикула', 'ef_icon' => 'img/16/add.png', 'data-url' => $createAllUrlString, 'class' => 'createAllCheckedTasks'));

                $urlLink = "<input type='checkbox' name='checkAllDefaultTasks' checked class='inline-checkbox'>" . $urlLinkBtn->getContent();
                $options[] = (object)array('DEFAULT_TASK_CAPTION' => $title, 'DEFAULT_TASK_LINK' => $urlLink, 'DEFAULT_TASK_TR_CLASS' => 'createAllTasksForJob', 'DEFAULT_TASK_CAPTION_COLSPAN' => 1);
            }

            // Показване на наличните дефолтни операции
            foreach ($defaultTasks as $sysId => $defTask){
                $title = $defTask->title;
                if(!empty($defTask->subTitle)){
                    $title .= " <i>{$defTask->subTitle}</i>";
                }

                $warning = false;
                $exLinkArray = array();
                $exQuery = planning_Tasks::getQuery();
                $exQuery->where("#originId = {$jobRec->containerId} AND (#systemId = {$sysId} OR (#productId = {$defTask->productId} AND #subTitle = '{$defTask->subTitle}'))AND #state != 'rejected'");
                $exQuery->show('id');
                while($exRec = $exQuery->fetch()) {
                    if (planning_Tasks::haveRightFor('single', $exRec->id)) {
                        $exLinkArray[] = ht::createLinkRef('', planning_Tasks::getSingleUrlArray($exRec->id), false, "title=Към операция|* #" . planning_Tasks::getHandle($exRec->id));
                    }
                }
                if(countR($exLinkArray)){
                    $oldTitleContent = implode('', $exLinkArray);
                    $title = "<span class='quiet'>{$title}</span>: <small>{$oldTitleContent}</small>";
                    $warning = 'Наистина ли желаете да създадете отново шаблонна операция|*?';
                }

                $folderId = isset($defTask->centerId) ? planning_Centers::fetchField($defTask->centerId, 'folderId') : $folderId;
                $urlAdd = array();
                if(planning_Tasks::haveRightFor('add', (object)array('originId' => $jobRec->containerId, 'productId' => $defTask->productId, 'folderId' => $folderId))){
                    $urlAdd = array('planning_Tasks', 'add', 'folderId' => $folderId, 'originId' => $jobRec->containerId, 'title' => $defTask->title, 'ret_url' => true, 'systemId' => $sysId);
                }

                $urlLinkBtn = ht::createBtn('Създаване', $urlAdd, $warning, false, 'title=Създаване на производствена операция,ef_icon=img/16/add.png');
                $urlLink = $urlLinkBtn->getContent();
                if(countR($createAllUrl)){
                    $checked = empty($warning) ? 'checked' : '';
                    $urlLink = "<input type='checkbox' name='R[{$sysId}]' id='cb_{$sysId}' class='inline-checkbox defaultTaskCheckbox' data-sysId='{$sysId}' {$checked}>{$urlLink}";
                }
                $options[] = (object)array('DEFAULT_TASK_CAPTION' => $title, 'DEFAULT_TASK_LINK' => $urlLink, 'DEFAULT_TASK_CAPTION_COLSPAN' => 1);
            }
        }

        $this->invoke('AfterGetCreateTaskFormDetails', array($jobRec, &$options));

        // Показване на наличните опции за клониране на операция от предходно задание
        if (isset($jobRec->oldJobId)) {
            $oldTasks = planning_Tasks::getTasksByJob($jobRec->oldJobId, array('draft', 'waiting', 'active', 'wakeup', 'stopped', 'closed', 'pending'), true, true, null, true);
            $urlCloneAll = null;

            if (countR($oldTasks)) {
                $options[] = (object)array('DEFAULT_TASK_CAPTION' => tr('От предишно задание') . tr(' ') . planning_Jobs::getLink($jobRec->oldJobId, 0), 'DEFAULT_TASK_LINK' => null, 'DEFAULT_TASK_TR_CLASS' => 'selectTaskFromJobRow', 'DEFAULT_TASK_CAPTION_COLSPAN' => 3);
                if(planning_Tasks::haveRightFor('createjobtasks', (object)array('jobId' => $jobRec->id, 'jobsToCloneTasksFrom' => $jobRec->oldJobId, 'type' => 'cloneAll'))){
                    $title = tr('Избраните от предишно задание');
                    $urlCloneAll = array('planning_Tasks', 'createjobtasks', 'type' => 'cloneAll', 'jobId' => $jobRec->id, 'jobsToCloneTasksFrom' => $jobRec->oldJobId, 'ret_url' => true);
                    $cloneAllUrlString = toUrl($urlCloneAll);

                    $urlAllBtnTpl = new core_ET("");
                    $urlAllBtnTpl->append(ht::createFnBtn('Клониране', null, 'Наистина ли желаете да клонирате наведнъж избраните операции|*?', array('title' => 'Клониране на всички предходни операции', 'data-url' => $cloneAllUrlString, 'class' => 'cloneAllCheckedTasks')));

                    $tQuery = planning_Tasks::getQuery();
                    $tQuery->in('id', array_keys($oldTasks));
                    $threads = arr::extractValuesFromArray($tQuery->fetchAll(), 'threadId');

                    $threadsString = implode(',', $threads);
                    if(planning_ConsumptionNotes::count("#state != 'rejected' AND #threadId IN ({$threadsString})")){
                        $urlCloneAll = array('planning_Tasks', 'createjobtasks', 'type' => 'cloneAll', 'jobId' => $jobRec->id, 'jobsToCloneTasksFrom' => $jobRec->oldJobId, 'cloneNotes' => true, 'ret_url' => true);
                        $cloneAllUrlString = toUrl($urlCloneAll);
                        $urlAllBtnTpl->append(ht::createFnBtn('|Кл|*+|ПВ|*', null, 'Наистина ли желаете да клонирате наведнъж избраните операции заедно с протоколите за влагане в тях|*?', array('title' => 'Клониране на всички предходни операции и на протоколите за влагане в тях', 'data-url' => $cloneAllUrlString, 'class' => 'cloneAllCheckedTasks')));
                    }

                    $urlAllBtnTpl->prepend("<input type='checkbox' name='checkAllClonedTasks' checked class='inline-checkbox'>");
                    $options[] = (object)array('DEFAULT_TASK_CAPTION' => $title, 'DEFAULT_TASK_LINK' => $urlAllBtnTpl, 'DEFAULT_TASK_TR_CLASS' => 'createAllTasksForJob', 'DEFAULT_TASK_CAPTION_COLSPAN' => 1);
                }

                foreach ($oldTasks as $k1 => $link) {
                    $taskRec = planning_Tasks::fetch($k1);
                    $taskStateVerbal = planning_Tasks::getVerbal($taskRec, 'state');
                    $oldTitle = $link . " <span class= 'state-{$taskRec->state} document-handler'>{$taskStateVerbal}</span>";
                    $warning = false;

                    $alreadyCreatedTasksArr = planning_Tasks::getTasksClonedFromOtherTasks($k1, $jobRec->containerId);
                    if(countR($alreadyCreatedTasksArr)){
                        $oldTitleContent = implode('', $alreadyCreatedTasksArr);
                        $oldTitle = "<span class='quiet'>{$link}</span>: <small>{$oldTitleContent}</small>";
                        $warning = 'Наистина ли желаете да клониране отново операцията от предходното задание|*?';
                    }

                    $urlClone = array();
                    if(planning_Tasks::requireRightFor('createjobtasks', (object)array('jobId' => $jobRec->id, 'cloneId' => $k1, 'type' => 'clone'))){
                        $urlClone = array('planning_Tasks', 'createjobtasks', 'type' => 'clone', 'cloneId' => $k1, 'jobId' => $jobRec->id, 'ret_url' => true);
                    }

                    $urlTplBlock = new core_ET("");
                    $urlTplBlock->append(ht::createBtn('Клониране', $urlClone, $warning, false, 'title=Създаване на производствена операция'));
                    if(countR($urlCloneAll)){
                        $checked = empty($warning) ? 'checked' : '';
                        $urlTplBlock->prepend("<input type='checkbox' name='R[{$k1}]' id='cb_{$k1}' class='previousTaskCheckbox inline-checkbox' data-cloneId='{$k1}' {$checked}>");
                    }
                    if(planning_ConsumptionNotes::count("#state != 'rejected' AND #threadId = {$taskRec->threadId}")){
                        $urlClone['cloneNotes'] = true;
                        $urlTplBlock->append(ht::createBtn('|Кл|*+|ПВ|*', $urlClone, $warning, false, 'title=Създаване на производствена операция с протоколите за влагане от оригинала'));
                    }

                    $options[] = (object)array('DEFAULT_TASK_CAPTION' => $oldTitle, 'DEFAULT_TASK_LINK' => $urlTplBlock, 'DEFAULT_TASK_CAPTION_COLSPAN' => 1);
                }
            }
        }

        // Създаване на нови ПО към наличните департаменти за избор
        $readyOptions = countR($options);
        $departments = planning_Centers::getCentersForTasks($jobRec->id);
        $caption = tr('Нова операция в център на дейност');
        if($readyOptions){
            $caption .= "&nbsp; <a id= 'btnShowTasks' href=\"javascript:toggleDisplayByClass('btnShowTasks', 'newTaskBtn')\"  style=\"background-image:url(" . sbf('img/16/toggle1.png', "'") . ');" class=" plus-icon more-btn", title="' . tr('Списък за избор на Център на дейност за създаване на нова Операция') . "\"</a>";
        }
        $options[] = (object)array('DEFAULT_TASK_CAPTION' => $caption, 'DEFAULT_TASK_LINK' => null, 'DEFAULT_TASK_TR_CLASS' => 'selectTaskFromJobRow', 'DEFAULT_TASK_CAPTION_COLSPAN' => 2);

        if(countR($departments)){
            foreach ($departments as $depFolderId => $dName) {
                $urlNewTask = array();
                if(planning_Tasks::haveRightFor('add', (object)array('originId' => $jobRec->containerId, 'folderId' => $depFolderId))){
                    $urlNewTask = array('planning_Tasks', 'add', 'originId' => $jobRec->containerId, 'folderId' => $depFolderId, 'ret_url' => true);
                }

                $productionStepCount = planning_Steps::getCountByCenterFolderId($depFolderId);
                if(!$productionStepCount) continue;

                $urlLink = ht::createBtn('Създаване', $urlNewTask, false, false, "title=Създаване на нова производствена операция в избрания център,ef_icon=img/16/add.png");
                $dName = doc_Folders::recToVerbal($depFolderId)->title;
                $trClass = ($readyOptions) ? 'newTaskBtn' : null;
                $options[] = (object)array('DEFAULT_TASK_CAPTION' => tr('в') . " " . $dName, 'DEFAULT_TASK_LINK' => $urlLink, 'DEFAULT_TASK_CAPTION_COLSPAN' => 1, 'DEFAULT_TASK_TR_CLASS' => $trClass);
            }
        } else {
            $options[] = (object)array('DEFAULT_TASK_CAPTION' => tr('Няма налични центрове'), 'DEFAULT_TASK_LINK' => null, 'DEFAULT_TASK_TR_CLASS' => null, 'DEFAULT_TASK_CAPTION_COLSPAN' => 1);
        }

        // Всяка опция се добавя във формата
        if(countR($options)){
            foreach ($options as $obj){
                $dTaskBlock = clone $form->info->getBlock('DEFAULT_TASK_BLOCK');
                $dTaskBlock->placeObject($obj);
                $dTaskBlock->removeBlocksAndPlaces();
                $form->info->append($dTaskBlock, 'TASK_ROWS');
            }
        } else {
            $noOptionMsg = tr("За да създавате операции, трябва да има създадени производствени етапи към центрове на дейност|*!");
            $form->info->append("<div class='richtext-message richtext-warning'>{$noOptionMsg}</div>", 'TASK_ROWS');
        }

        $form->toolbar->addBtn('Назад', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Назад към заданието');
        $form->layout = $form->renderLayout();

        // Рендиране на заданието под формата
        if(planning_Jobs::haveRightFor('single', $jobRec->id)){
            $tpl = new ET("<div class='preview-holder planning_Jobs'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr('Оригинален документ') . "</b></div><div class='scrolling-holder'>[#DOCUMENT#]</div></div><div class='clearfix21'></div>");
            $docHtml = $this->getInlineDocumentBody($jobRec);
            $tpl->append($docHtml, 'DOCUMENT');
            $form->layout->append($tpl);
        }

        $tpl = $this->renderWrapping($form->renderHtml());

        $tpl->push('planning/js/TaskSelection.js', 'JS');
        jquery_Jquery::run($tpl, 'taskSelect();');

        return $tpl;
    }


    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     *
     * @return array $result
     */
    public static function getIndicatorNames()
    {
        $result = array();
        $rec = hr_IndicatorNames::force('Активирани_задания', __CLASS__, 1);
        $result[$rec->id] = $rec->name;

        $rec = hr_IndicatorNames::force('Сложност_на_задания', __CLASS__, 2);
        $result[$rec->id] = $rec->name;

        $rec = hr_IndicatorNames::force('Приключени_задания', __CLASS__, 3);
        $result[$rec->id] = $rec->name;

        return $result;
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
        $iRec = hr_IndicatorNames::force('Активирани_задания', __CLASS__, 1);
        $iRec2 = hr_IndicatorNames::force('Сложност_на_задания', __CLASS__, 2);
        $iRec3 = hr_IndicatorNames::force('Приключени_задания', __CLASS__, 3);

        $query = self::getQuery();
        $query->where("#state IN ('active', 'closed', 'wakeup') || (#state = 'rejected' && (#brState = 'active' || #brState = 'closed'))");
        $query->where("#modifiedOn >= '{$timeline}'");
        $query->show('activatedBy,activatedOn,modifiedOn,state,createdBy,productId,lastChangeStateBy');

        while ($rec = $query->fetch()) {
            $activatedBy = isset($rec->activatedBy) ? $rec->activatedBy : $rec->createdBy;
            if (empty($activatedBy) || $activatedBy == core_Users::SYSTEM_USER) continue;

            $personId = crm_Profiles::fetchField("#userId = {$activatedBy}", 'personId');
            $classId = planning_Jobs::getClassId();

            $rec->activatedOn = $rec->activatedOn ?? $rec->modifiedOn;
            $date = dt::verbal2mysql($rec->activatedOn, false);
            
            $isRejected = ($rec->state == 'rejected');
            hr_Indicators::addIndicatorToArray($result, $date, $personId, $rec->id, $classId, $iRec->id, 1, $isRejected);

            if ($Driver = cat_Products::getDriver($rec->productId)) {
                $difficulty = $Driver->getDifficulty($rec->productId);
                if (isset($difficulty)) {
                    hr_Indicators::addIndicatorToArray($result, $date, $personId, $rec->id, $classId, $iRec2->id, $difficulty, $isRejected);
                }
            }

            if($rec->state == 'closed' || ($rec->brState == 'closed' && $isRejected)) {
                if ($rec->lastChangeStateBy == core_Users::SYSTEM_USER) continue;
                $lastChangeStateBy = $rec->lastChangeStateBy ?? $rec->modifiedBy;
                $lastChangeStateOn = $rec->lastChangeStateOn ?? $rec->modifiedOn;
                $lastChangeStateOn = dt::verbal2mysql($lastChangeStateOn, false);

                $personId = crm_Profiles::fetchField("#userId = {$lastChangeStateBy}", 'personId');
                hr_Indicators::addIndicatorToArray($result, $lastChangeStateOn, $personId, $rec->id, $classId, $iRec3->id, 1, $isRejected);
            }
        }
        
        return $result;
    }
    
    
    /**
     * След намиране на текста за грешка на бутона за 'Приключване'
     */
    public function getCloseBtnError($rec)
    {
        if (doc_Containers::fetchField("#threadId = {$rec->threadId} AND #state IN ('pending', 'draft')")) {
            
            return 'Заданието не може да се приключи, защото има документи в състояние "Заявка/Чернова"';
        }

        // Всички ПО към заданието
        $errorMsgArr = $notClosedTasks = $threadsWithPendings = array();
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#originId = {$rec->containerId}");
        $tQuery->show('id,threadId,state');
        while($tRec = $tQuery->fetch()){

            // Ако има неприключени - няма да може да се приключи
            if(in_array($tRec->state, array('active', 'wakeup', 'stopped', 'pending', 'draft'))){
                $notClosedTasks[] = "#" . planning_Tasks::getHandle($tRec->id);
            }

            // Ако има в тях документи на заявка - няма да може да се приключи
            if(doc_Containers::fetchField("#threadId = {$tRec->threadId} AND #state IN ('pending', 'draft')")){
                $threadsWithPendings[] = "#" . planning_Tasks::getHandle($tRec->id);
            }
        }

        if(countR($notClosedTasks)){
            $errorMsgArr[] = "|Заданието не може да бъде приключено докато не са приключени операциите към него|*: " . implode(', ', $notClosedTasks);
        }

        if(countR($threadsWithPendings)){
            $errorMsgArr[] = "|Заданието не може да бъде приключено докато в следните операции има документ/и на заявка/чернова|*: " . implode(', ', $threadsWithPendings);
        }

        if(countR($errorMsgArr)) return implode('. ', $errorMsgArr);
    }
    
    
    /**
     * Намира количеството от последното (активно или приключено) задание за артикула
     * 
     * @param int $productId
     * 
     * @return double|null
     */
    public static function getLastQuantity($productId)
    {
        $query = self::getQuery();
        $query->where("#productId = {$productId} AND #state IN ('active', 'closed')");
        $query->orderBy('activatedOn', 'DESC');
        $query->limit(1);
        $query->show('quantity');
        
        return  $query->fetch()->quantity;
    }


    /**
     * Връща свързаните нишки към заданието (неговата и тази на неговите операции)
     *
     * @param mixed $id                - ид на задание
     * @param  bool $skipNotFinalTasks - да се игнорират ли нишките на междинните ПО
     * @return array $threadsArr       - масив от нишката на заданието + нишките на ПО към него
     */
    public static function getJobLinkedThreads($id, $skipNotFinalTasks = false)
    {
        $rec = static::fetchRec($id);

        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#originId = {$rec->containerId} AND #state != 'rejected'");
        if($skipNotFinalTasks){
            $tQuery->where("#isFinal = 'yes'");
        }
        $tQuery->show("threadId");

        $threadsArr = array($rec->threadId => $rec->threadId) + arr::extractValuesFromArray($tQuery->fetchAll(), 'threadId');

        return $threadsArr;
    }


    /**
     * Връща планираните наличности
     *
     * @param stdClass $rec
     * @return array
     *       ['productId']        - ид на артикул
     *       ['storeId']          - ид на склад, или null, ако няма
     *       ['date']             - на коя дата
     *       ['quantityIn']       - к-во очаквано
     *       ['quantityOut']      - к-во за експедиране
     *       ['genericProductId'] - ид на генеричния артикул, ако има
     *       ['reffClassId']      - клас на обект (различен от този на източника)
     *       ['reffId']           - ид на обект (различен от този на източника)
     */
    public function getPlannedStocks($rec)
    {
        $res = array();
        $id = is_object($rec) ? $rec->id : $rec;
        $rec = $this->fetch($id, '*', false);
        $date = $rec->dueDate;

        if(!in_array($rec->state, array('active', 'wakeup', 'stopped'))) {

            return $res;
        }

        $productRec = cat_Products::fetch($rec->productId, 'canStore,canConvert');
        $quantityToProduce = round($rec->quantity - $rec->quantityProduced, 4);

        // В кои нишки има документи отнасящи се за заданието
        $threadsArr = static::getJobLinkedThreads($rec);

        // Ако има протокол за производство на заявка с по-голяма ефективна дата от заданието, ще се използва тя
        $dnQuery = planning_DirectProductionNote::getQuery();
        $dnQuery->XPR('date', 'date', 'DATE(COALESCE(#deadline, #valior, #createdOn))');
        $dnQuery->where("#state = 'pending' AND #date > '{$date}'");
        $dnQuery->in('threadId', $threadsArr);
        $dnQuery->show('date');
        $dnQuery->orderBy('date', 'ASC');
        $dnQuery->limit(1);

        if($dRec = $dnQuery->fetch()){
            $date = $dRec->date;
        }

        // Какви количества има вече запазени по заданието
        $products = $productsIn = array();
        $sQuery = store_StockPlanning::getQuery();
        $sQuery->in("threadId", $threadsArr);
        $sQuery->where("#sourceClassId != {$this->getClassId()}");
        while($sRec = $sQuery->fetch()){
            $productsIn[$sRec->productId] += $sRec->quantityIn;
            $products[$sRec->productId] += $sRec->quantityOut;
        }
        $quantityToProduce -= $productsIn[$rec->productId];

        if($quantityToProduce > 0){
            $genericProductId = null;
            if($productRec->canConvert == 'yes'){
                $genericProductId = planning_GenericMapper::fetchField("#productId = {$rec->productId}", 'genericProductId');
            }

            if($productRec->canStore == 'yes') {
                $quantityIn = $rec->type == 'manifacture' ? $quantityToProduce : null;
                $quantityOut = $rec->type == 'manifacture' ? null : $quantityToProduce;

                // Записване на очакваното количество за производство
                $res[] = (object)array('storeId'          => $rec->storeId,
                                       'productId'        => $rec->productId,
                                       'date'             => $date,
                                       'quantityIn'       => $quantityIn,
                                       'quantityOut'      => $quantityOut,
                                       'genericProductId' => $genericProductId);
            }

            // Ако има активна рецепта
            if($rec->type == 'manifacture'){
                if($lastReceipt = cat_Products::getLastActiveBom($rec->productId, 'production,instant,sales')){

                    // Кои са материалите и
                    $receiptClassId = cat_Boms::getClassId();
                    $materialArr = cat_Boms::getBomMaterials($lastReceipt, $rec->quantity, null, true, array(), $rec->quantity);

                    if(countR($materialArr)){

                        // Какви количества има вложени по заданието
                        foreach (array('planning_ConsumptionNoteDetails' => 'planning_ConsumptionNotes', 'planning_DirectProductNoteDetails' => 'planning_DirectProductionNote') as $detail => $master){
                            $Detail = cls::get($detail);
                            $Master = cls::get($master);

                            $dQuery = $Detail::getQuery();
                            $dQuery->EXT('state', "{$Master->className}", "externalName=state,externalKey={$Detail->masterKey}");
                            $dQuery->EXT('threadId', "{$Master->className}", "externalName=threadId,externalKey={$Detail->masterKey}");
                            $dQuery->EXT('canStore', 'cat_Products', "externalName=canStore,externalKey=productId");
                            $dQuery->XPR('totalQuantity', 'double', "SUM(#quantity)");
                            $dQuery->where("#state = 'active' AND #canStore = 'yes'");
                            $dQuery->in("threadId", $threadsArr);
                            if($Detail instanceof planning_DirectProductNoteDetails){
                                $dQuery->where("#storeId IS NOT NULL");
                            }
                            $dQuery->show('productId,totalQuantity');
                            $dQuery->groupBy('productId');
                            while($dRec = $dQuery->fetch()){
                                $products[$dRec->productId] += $dRec->totalQuantity;
                            }
                        }

                        // За всеки материал от рецептата, ще се проверява, колко остава да се запази
                        foreach($materialArr as $materialRec){
                            if($materialRec->quantity == cat_BomDetails::CALC_ERROR) continue;
                            $materialRec->quantity *= $materialRec->quantityInPack;
                            $materialProductRec = cat_Products::fetch($materialRec->productId, 'generic,canConvert');

                            // Ако материала е генеричен
                            if($materialProductRec->generic == 'yes'){
                                $genericProductId = $materialRec->productId;

                                // и има вложени, негови заместители те ще се приспаднат от него
                                $equivalent = array_keys(planning_GenericMapper::getEquivalentProducts($materialRec->productId));
                                $equivalent[] = $materialRec->productId;
                                array_walk($products, function($quantity, $productId) use (&$removeQuantity, $equivalent) {
                                    if(in_array($productId, $equivalent)){
                                        $removeQuantity += $quantity;
                                    }
                                });
                            } else {

                                // Ако материала не е генеричен, гледа се колко конкретно има вложено по него
                                $removeQuantity = $products[$materialRec->productId];
                                $genericProductId = planning_GenericMapper::fetchField("#productId = {$materialRec->productId}", 'genericProductId');
                            }

                            // Ако има оставащо количество за запазване ще се запазва
                            $remainingQuantity = 0;
                            if($materialRec->quantity != cat_BomDetails::CALC_ERROR){
                                $remainingQuantity = round($materialRec->quantity - $removeQuantity, 4);
                            }

                            if($remainingQuantity > 0){
                                $inputStoreId = null;
                                if(isset($rec->inputStores)){
                                    $quantities = store_Products::getQuantitiesByStore($materialRec->productId, null, $rec->inputStores);
                                    arsort($quantities);
                                    $inputStoreId = key($quantities);
                                }

                                $res[] = (object)array('storeId'          => $inputStoreId,
                                    'productId'        => $materialRec->productId,
                                    'date'             => $date,
                                    'quantityIn'       => null,
                                    'quantityOut'      => $remainingQuantity,
                                    'genericProductId' => $genericProductId,
                                    'reffClassId'      => $receiptClassId,
                                    'reffId'           => $lastReceipt->id,
                                );
                            }
                        }
                    }
                }
            }
        }

        return $res;
    }


    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     */
    public static function canAddToThread($threadId)
    {
        $saleId = Request::get('saleId', 'int');
        $purchaseId = Request::get('purchaseId', 'int');

        return isset($saleId) || isset($purchaseId);
    }


    /**
     * Затваряне на приключени задания
     */
    public function cron_CloseOldJobs()
    {
        // Задания готови на колко процента, да се приключват
        $percent = planning_Setup::get('JOB_AUTO_COMPLETION_PERCENT');
        if(empty($percent)) return;

        // Да не са променяни от
        $delay = planning_Setup::get('JOB_AUTO_COMPLETION_DELAY');
        $isSystemUser = core_Users::isSystemUser();
        if(!$isSystemUser){
            core_Users::forceSystemUser();
        }

        static::closeActiveJobs(planning_Setup::get('JOB_AUTO_COMPLETION_PERCENT'), null, null, $delay, "Автоматично приключване по разписание");

        if(!$isSystemUser){
            core_Users::cancelSystemUser();
        }
    }


    /**
     * Кои са перата на ПО на заданието
     *
     * @param $id
     * @return array $taskExpenseItemIds
     */
    public static function getTaskCostObjectItems($id)
    {
        $jobRec = planning_Jobs::fetchRec($id);
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#originId = {$jobRec->containerId} AND #state != 'draft' AND #state != 'rejected'");

        $taskExpenseItemIds = array();
        while($tRec = $tQuery->fetch()){
            if($listItemId = acc_Items::fetchItem('planning_Tasks', $tRec->id)->id){
                $taskExpenseItemIds[$listItemId] = $listItemId;
            }
        }

        return $taskExpenseItemIds;
    }


    /**
     * Кои са разпределените разходи към заданието
     *
     * @param int $id
     * @param null|string $valior - вальор
     * @return array
     */
    public static function getAllocatedServices($id, $valior = null)
    {
        $jobRec = planning_Jobs::fetchRec($id);

        $res = array();
        $taskExpenseItemIds = static::getTaskCostObjectItems($jobRec);
        if($jobItemId = acc_Items::fetchItem('planning_Jobs', $jobRec->id)->id){
            $taskExpenseItemIds[$jobItemId] = $jobItemId;
        }
        if(!countR($taskExpenseItemIds)) return $res;

        $to = $valior ?? dt::verbal2mysql($jobRec->createdOn, false);
        $from = $to;

        $Balance = new acc_ActiveShortBalance(array('from' => $from, 'to' => $to, 'accs' => '60201', 'item1' => $taskExpenseItemIds, 'keepUnique' => true));
        $bRecs = $Balance->getBalance('60201');

        if(is_array($bRecs)) {
            foreach ($bRecs as $bRec) {
                    $itemRec = acc_Items::fetch($bRec->ent2Id, 'classId,objectId');
                    $measureId = cat_Products::fetchField($itemRec->objectId, 'measureId');
                    $key = "{$itemRec->objectId}|{$bRec->ent1Id}";
                    if (!array_key_exists($key, $res)) {
                        $res[$key] = (object)array('productId' => $itemRec->objectId, 'measureId' => $measureId, 'expenseItemId' => $bRec->ent1Id);
                    }

                    $res[$key]->quantity += $bRec->blQuantity;
            }
        }

        return $res;
    }


    /**
     * Връща детайли отговарящи на вложеното до сега по заданието на протокола за крайния артикул
     *
     * @param mixed $rec
     * @param null|string $valior
     * @return array $convertedArr
     */
    public static function getDefaultProductionDetailsFromConvertedByNow($rec, $valior = null)
    {
        $rec = planning_Jobs::fetchRec($rec);

        // Кои са свързаните нишки към заданието
        $threadsArr = planning_Jobs::getJobLinkedThreads($rec);
        $consumable = $convertedArr = array();

        // Намират се всички произведени Заготовки: артикул по заданието различни от артикула му
        $pQuery1 = planning_DirectProductionNote::getQuery();
        $pQuery1->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $pQuery1->where("#state = 'active' AND #productId != {$rec->productId}");
        $pQuery1->in('threadId', $threadsArr);
        $workInProgressId = batch_Items::WORK_IN_PROGRESS_ID;

        // Всички протоколи за влагане/връщане на услуги се реконтират - за да се смени правилно разходния обект
        foreach (array('planning_ConsumptionNoteDetails' => 'planning_ConsumptionNotes', 'planning_ReturnNoteDetails' => 'planning_ReturnNotes') as $detailName => $masterName){
            $sign = ($detailName == 'planning_ConsumptionNoteDetails') ? 1 : -1;
            $DetailMvc = cls::get($detailName);
            $MasterMvc = cls::get($masterName);
            $dQuery = $DetailMvc->getQuery();
            $dQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
            $dQuery->EXT('useResourceAccounts', $MasterMvc, 'externalKey=noteId');
            $dQuery->EXT('storeId', $MasterMvc, 'externalKey=noteId');
            $dQuery->EXT('state', $MasterMvc, 'externalKey=noteId');
            $dQuery->EXT('threadId', $MasterMvc, 'externalKey=noteId');
            $dQuery->where("#state = 'active' AND #useResourceAccounts = 'yes'");
            $dQuery->in('threadId', $threadsArr);

            while($dRec = $dQuery->fetch()){
                $fromAccId = ($dRec->canStore == 'yes') ? null : '61102';

                $key = "{$dRec->productId}|{$dRec->packagingId}||{$fromAccId}|{$workInProgressId}";
                if(!array_key_exists($key, $convertedArr)){
                    $convertedArr[$key] = (object)array('productId' => $dRec->productId, 'packagingId' => $dRec->packagingId, 'quantityInPack' => $dRec->quantityInPack, 'measureId' => $dRec->measureId, 'quantityExpected' => 0, 'expenseItemId' => null, 'fromAccId' => $fromAccId, 'type' => 'input', 'batches' => array());
                }

                $convertedArr[$key]->quantityExpected += $sign * $dRec->quantity;

                if(core_Packs::isInstalled('batch')){
                    $bQuery = batch_BatchesInDocuments::getQuery();
                    $bQuery->where("#detailClassId = {$DetailMvc->getClassId()} AND #detailRecId = {$dRec->id}");
                    $bQuery->where("#storeId = {$workInProgressId}");
                    while($bRec = $bQuery->fetch()){
                        $convertedArr[$key]->batches[$bRec->batch] += $sign * $bRec->quantity;
                    }
                }
            }
        }

        // Кои са всички разпределни услуги по ПО, които са разходни обекти
        $allocatedProducts = planning_Jobs::getAllocatedServices($rec, $valior);
        if(countR($allocatedProducts)){
            foreach ($allocatedProducts as $aRec){
                $key = "{$aRec->productId}|{$aRec->measureId}|{$aRec->expenseItemId}|61102|";
                if(!array_key_exists($key, $convertedArr)){
                    $convertedArr[$key] = (object)array('productId' => $aRec->productId, 'packagingId' => $aRec->measureId, 'quantityInPack' => 1, 'measureId' => $aRec->measureId, 'quantityExpected' => 0, 'expenseItemId' => $aRec->expenseItemId, 'fromAccId' => '61102', 'type' => 'allocated');
                }

                $convertedArr[$key]->quantityExpected += $aRec->quantity;
            }
        }

        // Обикалят се всички протоколи за произовдство за крайния артикул
        $pQuery = planning_DirectProductNoteDetails::getQuery();
        $pQuery->EXT('pProductId', 'planning_DirectProductionNote', 'externalName=productId,externalKey=noteId');
        $pQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $pQuery->EXT('state', 'planning_DirectProductionNote', 'externalName=state,externalKey=noteId');
        $pQuery->EXT('threadId', 'planning_DirectProductionNote', 'externalName=threadId,externalKey=noteId');
        $pQuery->where("#state = 'active' AND #pProductId = {$rec->productId}");
        $pQuery->in('threadId', $threadsArr);

        $detailClassId = planning_DirectProductNoteDetails::getClassId();
        while($dRec = $pQuery->fetch()){
            $key = "{$dRec->productId}|{$dRec->packagingId}|{$dRec->expenseItemId}|{$dRec->fromAccId}|{$workInProgressId}";
            if(array_key_exists($key, $convertedArr)){
                $convertedArr[$key]->quantityExpected -= $dRec->quantity;

                if(core_Packs::isInstalled('batch')){
                    $bQuery = batch_BatchesInDocuments::getQuery();
                    $bQuery->where("#detailClassId = {$detailClassId} AND #detailRecId = {$dRec->id}");
                    $bQuery->where("#storeId = {$workInProgressId}");
                    while($bRec = $bQuery->fetch()){
                        $convertedArr[$key]->batches[$bRec->batch] -= $bRec->quantity;
                    }
                }
            }
        }

        // Остават само положителните количества
        foreach ($convertedArr as $cId => $cObj){
            if($cObj->quantityExpected <= 0){
                unset($convertedArr[$cId]);
            } elseif(is_array($cObj->batches)) {
                foreach ($cObj->batches as $bId => $bQuantity){
                    if($bQuantity <= 0){
                        unset($cObj->batches[$bId]);
                    }
                }
            }
        }

        return $convertedArr;
    }


    /**
     * Връща масив от използваните нестандартни артикули в протокола
     *
     * @param int $id - ид на протокола
     *
     * @return array $res - масив с използваните документи
     *               ['class'] - инстанция на документа
     *               ['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
        $rec = $this->fetchRec($id);
        $usedDocs = array();
        $usedDocs[$rec->productId] = cat_Products::fetchField($rec->productId, 'containerId');

        return $usedDocs;
    }


    /**
     * Затваря активните + събудените задания само ако произведеното е над $tolerance и няма нови контиращи документи
     * в последните $noNewDocumentsInMonths месеца
     *
     * @param double $tolerance          - над колко % произведено (включително)
     * @param int|array|null $productIds - ид/масив от ид-та на артикули
     * @param int|array|null $sourceIds  - ид/масив от ид-та от сделки (продажби/покупки)
     * @param int|null $noNewDocumentsIn - за колко време назад да се гледа да няма нови контиращи документи в нишката
     * @param string $logMsg             - лог при приключване
     * @param string $jobSourceField     - кое поле на planning_Jobs да се филтрира с $sourceIds
     *                                     ('saleId' или 'purchaseId' - виж deals_DealMaster::$jobSourceField)
     *
     * @return int $count                - колко са приключените задания
     */
    public static function closeActiveJobs($tolerance, $productIds = null, $sourceIds = null, $noNewDocumentsIn = null, $logMsg = 'Автоматично приключване', $jobSourceField = 'saleId')
    {
        $me = cls::get(get_called_class());
        $thresholdDate = ($noNewDocumentsIn) ? dt::addSecs(-1 * $noNewDocumentsIn, dt::now()) : null;
        $query = static::getQuery();
        $query->where("#state IN ('active', 'wakeup')");
        $query->XPR('completed', 'percent', 'round(#quantityProduced / #quantity, 2)');
        $query->where("#completed >= {$tolerance}");

        // Ако ще се гледат само за един артикул - за него, иначе за всички задания към затворени артикули
        if(isset($productIds)){
            $productIds = arr::make($productIds, true);
            $query->in("productId", $productIds);
        }

        // Ако има сделка (продажба/покупка), само заданията към нея - през вярното
        // поле ('saleId' или 'purchaseId'), защото ClosedDeals обработва и двата вида сделки
        if(isset($sourceIds)){
            $sourceIdArr = arr::make($sourceIds, true);
            $query->in($jobSourceField, $sourceIdArr);
        }

        $count = 0;
        while($rec = $query->fetch()){
            // Ако е събудено в посочения интервал да не се приключва
            if($rec->state == 'wakeup' && $rec->lastChangeStateOn >= $thresholdDate) continue;

            // Ако има документ на заявка в нишката, няма да се приключва заданието
            $cQuery = doc_Containers::getQuery();
            $cQuery->where("#threadId = {$rec->threadId} AND #state IN ('pending', 'draft')");
            $draftAndPendingRecs = $cQuery->fetchAll();
            foreach($draftAndPendingRecs as $cRec){
                $notificationUrl = array('doc_Containers', 'list', "threadId" => $rec->threadId);
                bgerp_Notifications::add("|*#{$me->getHandle($rec->id)} |не може да бъде автоматично приключено, защото има документи на заявка/чернова", $notificationUrl, $cRec->createdBy);
            }

            if(countR($draftAndPendingRecs)) continue;

            // Ако има неприключени ПО към заданието, да не се приключва и да се бие нотификация
            $tQuery = planning_Tasks::getQuery();
            $tQuery->where("#originId = {$rec->containerId} AND #state != 'rejected' AND #state != 'closed'");
            $notRejectedTasks = $tQuery->fetchAll();
            foreach($notRejectedTasks as $taskRec){
                $notificationUrl = array('doc_Containers', 'list', "threadId" => $taskRec->threadId);
                $taskHandle = cal_Tasks::getHandle($taskRec->id);
                bgerp_Notifications::add("|*#{$me->getHandle($rec->id)} |не може да бъде автоматично приключено, защото|* {$taskHandle} |не е приключена|*", $notificationUrl, $taskRec->createdBy);
            }
            if(countR($notRejectedTasks)) continue;

            // Ако е указано да няма нови документи в нишката в последните X време да се пропуска
            if(isset($thresholdDate)){
                $lastCreatedOn = doc_Threads::getLastCreatedOnInThread($rec->threadId, 'acc_TransactionSourceIntf');
                if($lastCreatedOn >= $thresholdDate) continue;
            }

            $isSystemUser = core_Users::isSystemUser();
            if(!$isSystemUser){
                core_Users::forceSystemUser();
            }
            planning_plg_StateManager::changeState($me, $rec, 'close', $logMsg);
            if(!$isSystemUser){
                core_Users::cancelSystemUser();
            }
        }

        return $count;
    }


    /**
     * Коя е дефолт папката за нови записи
     */
    public function getDefaultFolder()
    {
        // Първо се търси последната папка на ЦД, където потребителя е създавал задания
        $centerClassId = planning_Centers::getClassId();
        $cu = core_Users::getCurrent();
        $query = $this->getQuery();
        $query->EXT('coverClass', 'doc_Folders', 'externalName=coverClass,externalKey=folderId');
        $query->where("#state != 'rejected' AND #createdBy = '{$cu}' AND #coverClass = {$centerClassId}");
        doc_Folders::restrictAccess($query, $cu);
        $query->show('folderId');
        $query->orderBy('createdOn', 'DESC');
        $query->limit(1);

        // Ако има връща се тя
        $folderId = $query->fetch()->folderId ?? null;
        if(!empty($folderId))  return $folderId;

        // Ако потребителя не е създавал, гледам папката в чиято нишка на задание, потребителя е променял документи
        $cQuery = doc_Containers::getQuery();
        doc_Folders::restrictAccess($cQuery, $cu);
        $cQuery->EXT('coverClass', 'doc_Folders', 'externalName=coverClass,externalKey=folderId');
        $cQuery->EXT('firstDocClass', 'doc_Threads', 'externalName=firstDocClass,externalKey=threadId');
        $cQuery->where("(#createdBy = {$cu} OR #modifiedBy = {$cu}) AND #coverClass = {$centerClassId} AND #firstDocClass=" . $this->getClassId());
        $cQuery->show('folderId');
        $cQuery->orderBy('modifiedOn', 'DESC');
        $cQuery->limit(1);

        return $cQuery->fetch()->folderId;
    }


    /**
     * Изпълнява се преди оттеглянето на документа
     */
    protected static function on_BeforeReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        $taskRecs = planning_Tasks::getTasksByJob($rec->id, array('draft', 'waiting', 'active', 'wakeup', 'stopped', 'pending'));
        if(countR($taskRecs)){
            core_Statuses::newStatus("Не може да се оттегли, докато следните операции не са оттеглени/приключени|*: " . implode(', ', $taskRecs), 'warning');
            return false;
        }
    }


    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    protected static function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        // Ако има форма, и тя е събмитната и действието е 'запис и нов'
        if ($data->form && $data->form->isSubmitted() && $data->form->cmd == 'save_n_new' && ($data->form->rec->_allowedProductsCnt ?? 0) > 1) {

            list($sourceClass, $sourceId, $jobField) = self::getSourceInfo($data->form->rec);
            if (isset($sourceClass)) {

                // Редиректва се към същата форма за пускане на задание за следващия артикул
                $sourceRec = $sourceClass::fetch($sourceId, 'id,threadId,containerId');
                $url = array('planning_Jobs', 'add', $jobField => $sourceRec->id, 'type' => $data->form->rec->type, 'foreignId' => $sourceRec->containerId, 'ret_url' => getRetUrl());
                if(doc_Threads::haveRightFor('single', $sourceRec->threadId)){
                    $url['threadId'] = $sourceRec->threadId;
                } else {
                    $url['folderId'] = $data->form->rec->folderId;
                }

                $data->retUrl = $data->addJobUrl = $url;
            }
        }
    }


    /**
     * След подготовка на формата за изискване на основание за промяна на състоянието
     */
    protected static function on_AfterGetDemandReasonFormForChange($mvc, &$form, $action, $rec)
    {
        // При събуждане и пускане да се изисква потребителя да посочи да се опреснят ли параметрите на артикула
        if(in_array($action, array('wakeup', 'activateAgain'))){
            $form->FLD('updateProductParams', 'enum(yes=Да,no=Не)', 'caption=Обновяване на параметрите на артикула в заданието->Избор,maxRadio=2,mandatory');
            $defaultUpdateProductParams = planning_Setup::get('JOB_DEFAULT_INVALIDATE_PRODUCT_CACHE_ON_CHANGE');
            $form->setDefault('updateProductParams', $defaultUpdateProductParams);
        }
    }


    /**
     * Екшън за ръчно преподреждане
     */
    public function act_manualreorder()
    {
        requireRole('debug');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetchRec($id));
        $Tasks = cls::get('planning_Tasks');

        $res = $Tasks->reorderTasksInJob($rec->containerId);
        $this->logWrite('Ръчно преподреждане на ПО', $rec->id);

        bp($res['debug'], $res['updated']);
    }


    /**
     * Помощен екшън връщащ произведеното по партиди от операции за този артикул по това задание
     *
     * @param mixed $jobRec
     * @param int $productId
     * @return array $res
     */
    public static function getProducedBatchesByProductId($jobRec, $productId)
    {
        $res = array();
        $jobRec = static::fetchRec($jobRec);
        if(core_Packs::isInstalled('batch')){
            $taskDetailQuery = planning_ProductionTaskDetails::getQuery();
            $taskDetailQuery->EXT('originId', 'planning_Tasks', 'externalName=originId,externalKey=taskId');
            $taskDetailQuery->EXT('tState', 'planning_Tasks', 'externalName=state,externalKey=taskId');
            $taskDetailQuery->where("#originId = {$jobRec->containerId} AND #productId = {$productId} AND #state != 'rejected' AND #tState IN ('active', 'wakeup', 'closed') AND #type IN ('scrap', 'production')");
            $taskDetailQuery->where("#batch != ''");
            while($dRec = $taskDetailQuery->fetch()){
                $sign = ($dRec->type == 'scrap') ? -1 : 1;
                $res["{$dRec->batch}"] += $dRec->quantity * $sign;
            }

            if(!countR($res)){
                $me = cls::get(get_called_class());
                $bQuery = batch_BatchesInDocuments::getQuery();
                $bQuery->where("#detailClassId={$me->getClassId()} AND #detailRecId = {$jobRec->id}");
                while($bRec = $bQuery->fetch()){
                    $res["{$bRec->batch}"] += $bRec->quantity;
                }
            }
        }

        return $res;
    }


    /**
     * Вкарваме css файл за единичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if (!$data->selectedTab || $data->selectedTab == 'Statistic') {

            // Показване на обобщението на отпадъка в статистиката
            $tasksInJob = planning_Tasks::getTasksByJob($data->rec->id, 'active,wakeup,closed,stopped', false, false, 'yes');
            $totalFinalNetWeight = countR($tasksInJob) ? arr::sumValuesArray($tasksInJob, 'totalNetWeight', true) : 0;
            $wasteArr = planning_ProductionTaskProducts::getTotalWasteArr($data->rec->threadId, $totalFinalNetWeight);

            $subProductArr = planning_ProductionTaskProducts::getSubProductsArr($data->rec->threadId);
            if(!Mode::is('printBlank')){
                if(countR($wasteArr) || countR($subProductArr)){
                    foreach ($wasteArr as $wasteRow){
                        $cloneTpl = clone $tpl->getBlock('WASTE_BLOCK_ROW');
                        $cloneTpl->replace($wasteRow->productLink, 'wasteProducedProductId');
                        $cloneTpl->replace($wasteRow->class, 'wasteClass');
                        $cloneTpl->replace($wasteRow->quantityVerbal, 'wasteQuantity');
                        $cloneTpl->removeBlocksAndPlaces();
                        $tpl->append($cloneTpl, 'WASTE_BLOCK_TABLE_ROW');
                    }

                    foreach ($subProductArr as $subRow){
                        $cloneTpl = clone $tpl->getBlock('SUB_PRODUCT_BLOCK_ROW');
                        $cloneTpl->replace($subRow->productLink, 'subProductId');
                        $cloneTpl->replace($subRow->quantityVerbal, 'subProductQuantity');
                        $cloneTpl->removeBlocksAndPlaces();
                        $tpl->append($cloneTpl, 'SUB_BLOCK_TABLE_ROW');
                    }
                }
            }
        }
    }


    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        // При местене на заданието да се подменя и центъра на дейност
        if(($rec->_isBeingMoved ?? false) && isset($rec->department)){
            $folderCover = doc_Folders::getCover($rec->folderId);
            if($folderCover->isInstanceOf('planning_Centers')){
                if($rec->department != $folderCover->that){
                    $rec->department = $folderCover->that;
                    $mvc->save_($rec, 'department');
                }
            }
        }

        if(($rec->__isBeingChanged ?? null) && $rec->allowSecondMeasure == 'no'){
            $rec->secondMeasureId = null;
            $mvc->save_($rec, 'secondMeasureId');
        }

        if($rec->_dueDateChanged ?? null){
            static::recalcExpectedDueDates($rec->containerId);

            $taskQuery = planning_Tasks::getQuery();
            $taskQuery->where("#originId = {$rec->containerId} AND #assetId IS NOT NULL");
            $taskQuery->in('state', array('active', 'pending', 'wakeup', 'stopped'));
            $taskQuery->show('assetId');
            $assetIds = arr::extractValuesFromArray($taskQuery->fetchAll(), 'assetId');
            if (countR($assetIds)) {
                cls::get('planning_Tasks')->requestAssetOptimization($assetIds);
            }
        }
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
        $systemFolderId = planning_Centers::fetchField("#folderId = {$rec->folderId}", 'supportSystemFolderId');

        return isset($systemFolderId) ? array($systemFolderId) : array();
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
        return (object)array('title' => tr('Към|*: ') . $this->getTitleById($id));
    }


    /**
     * Рекалкулиране на очаквания падеж
     *
     * @param mixed $containerIds - масив от контейнери или null ако ще е само за активните
     * @return void
     */
    public static function recalcExpectedDueDates($containerIds = null)
    {
        // Всички задания отговарящи на условията
        $containerIds = isset($containerIds) ? arr::make($containerIds, true) : array();

        $jobArr = array();
        $jQuery = planning_Jobs::getQuery();
        if(countR($containerIds)){
            $jQuery->in('containerId', $containerIds);
        } else {
            $jQuery->in('state', array('active', 'stopped', 'wakeup'));
        }

        $jQuery->show('id,containerId,dueDate');
        while ($jRec = $jQuery->fetch()){
            $jobArr[$jRec->containerId] = (object)array('id' => $jRec->id, 'expectedDueDate' => $jRec->dueDate, 'dueDate' => $jRec->dueDate);
        }

        if(!countR($jobArr)) return;

        // Взима се най-голямото от очаквания край на техните операции или тяхното задание
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#state IN ('active', 'wakeup', 'pending', 'stopped', 'closed')");
        $tQuery->XPR('maxTimeEnd', 'date', 'DATE(MAX(#expectedTimeEnd))');
        $tQuery->in("originId", array_keys($jobArr));
        $tQuery->groupBy('originId');
        $tQuery->show('originId,maxTimeEnd');
        while ($tRec = $tQuery->fetch()){
            $jobArr[$tRec->originId]->expectedDueDate = max($tRec->maxTimeEnd, $jobArr[$tRec->originId]->expectedDueDate);
        }

        cls::get('planning_Jobs')->saveArray($jobArr, 'id,expectedDueDate');
    }


    /**
     * След подготовка на табовете на документа
     * @see doc_plg_Tabs
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     * @return void
     */
    protected static function on_AfterPrepareDocumentTabs($mvc, &$res, $data)
    {
        if (!in_array($data->rec->state, array('draft', 'rejected'))) {
            $url = getCurrentUrl();
            $url["docTab{$data->rec->containerId}"] = 'WorkInProgress';
            $data->tabs->TAB('WorkInProgress', 'Незавършено производство', $url, null, 2);
        }
    }


    /**
     * Подготовка на статистиката на данните от НП за заданието
     *
     * @param $data
     * @return void
     */
    public static function prepareWorkInProgress(&$data)
    {
        planning_WorkInProgress::prepareJobStatistic($data);
    }


    /**
     * Рендиране на статистиката за НП в заданието
     *
     * @param core_ET $tpl
     * @param stdClass $data
     * @return void
     */
    public static function renderWorkInProgress(&$tpl, &$data)
    {
        planning_WorkInProgress::renderJobStatistic($tpl, $data);
        $tpl->removeBlock("STATISTIC");
    }



    /**
     * Филтрира заданията по подадените параметри
     */
    public static function getPreviousJobs($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $jQuery = planning_Jobs::getQuery();
        $jQuery->orderBy('id', 'DESC');

        if (isset($params['type']) && in_array($params['type'], array('manifacture', 'disassembly'))) {
            $jQuery->where(array("#type = '[#1#]'", $params['type']));
        }

        if (is_array($onlyIds)) {
            if (!countR($onlyIds)) {

                return array();
            }
            $ids = implode(',', $onlyIds);
            $jQuery->where("#id IN ({$ids})");
        } elseif (ctype_digit("{$onlyIds}")) {
            $jQuery->where("#id = {$onlyIds}");
        } else {
            $jQuery->where("#state IN ('active', 'wakeup', 'stopped', 'closed')");

            list($sourceClass, $sourceId, $jobField) = self::getSourceInfo((object) $params);
            if (isset($sourceClass)) {
                $sourceFolderId = $sourceClass::fetchField($sourceId, 'folderId');
                $sourceQuery = $sourceClass::getQuery();
                $sourceQuery->where("#folderId = {$sourceFolderId} AND (#state IN ('active', 'closed'))");
                $sourceQuery->show('id');
                $otherSourceIds = arr::extractValuesFromArray($sourceQuery->fetchAll(), 'id');
                $otherSourceIds = implode(',', $otherSourceIds);
                $jQuery->setUnion("#{$jobField} IN ({$otherSourceIds})");
                $jQuery->setUnion("#productId = {$params['productId']}");
            } elseif(isset($params['productId'])) {
                $jQuery->where("#productId = {$params['productId']}");
            }
        }

        if ($q) {
            $q1 = plg_Search::normalizeText($q);
            if(is_numeric($q1)) {
                $jQuery->where(array("#id = '[#1#]'", $q1));
            } else {
                plg_Search::applySearch($q1, $jQuery, 'searchKeywords');
            }
        }

        $previousArr = $similarArr = array();
        while($jRec = $jQuery->fetch()){
            if($jRec->productId == $params['productId']){
                $previousArr[$jRec->id] = (object)array('title' => self::getRecTitle($jRec), 'attr' => array('class' => 'state-waiting'));
            } else {
                $similarArr[$jRec->id] = (object)array('title' => self::getRecTitle($jRec), 'attr' => array('class' => 'state-template'));
            }
        }

        $options = array();
        if(!isset($onlyIds) && isset($params['productId'])) {
            if(countR($previousArr)) {
                $options += array("prev" => (object) array('group' => true, 'title' => 'Предходни')) + $previousArr;
            }
            if(countR($similarArr)) {
                $options += array("similar" => (object) array('group' => true, 'title' => 'Подобни')) + $similarArr;
            }
        } else {
            $options = $previousArr + $similarArr;
        }

        return $options;
    }


    /**
     * Кои са полетата за опаковките, за които ще се логва
     *
     * @return array
     */
    public static function getPackagingFields_()
    {
        return array('packagingId' => 'packagingId', 'secondMeasureId' => 'secondMeasureId');
    }
}
