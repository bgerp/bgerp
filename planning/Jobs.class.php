<?php


/**
 * Мениджър на Задания за производство
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
 * @title     Задания за производство
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
    public $title = 'Задания за производство';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Задание за производство';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Job';
    
    
    /**
     * За кои действия да се изисква основание
     *
     * @see planning_plg_StateManager
     */
    public $demandReasonChangeState = 'stop,wakeup';

    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StockPlanning, doc_DocumentPlg, planning_plg_StateManager, doc_SharablePlg, planning_Wrapper, plg_Sorting, acc_plg_DocumentSummary, plg_Search, change_Plugin, plg_Clone, plg_Printing, doc_plg_SelectFolder, cat_plg_AddSearchKeywords, plg_SaveAndNew';
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'storeId,dueDate,packQuantity,notes,tolerance,sharedUsers,allowSecondMeasure';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, job';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, job';
    
    
    /**
     * Кой може да променя състоянието?
     */
    public $canChangestate = 'ceo, job';
    
    
    /**
     * Кой може да променя активирани записи
     *
     * @see change_Plugin
     */
    public $canChangerec = 'ceo, job';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, planning, job';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, planning, job';
    
    
    /**
     * Полета за търсене
     */
    public $searchFields = 'productId, notes, saleId, deliveryPlace, deliveryDate, deliveryTermId, deliveryPlace';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/clipboard_text.png';
    
    
    /**
     * Кой може да клонира
     */
    public $canClonerec = 'ceo, job';
    
    
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
    public $filterDateField = 'createdOn,dueDate,deliveryDate,modifiedOn,activatedOn';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'Tasks=planning_Tasks';
    
    
    /**
     * Клас за отделния ред в листовия изглед
     */
    public $commonRowClass = 'separateRowTable';
    
    
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
    public $fieldsNotToClone = 'dueDate,quantityProduced,history,oldJobId,secondMeasureQuantity';


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
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=canManifacture,hasnotProperties=generic,maxSuggestions=100,forceAjax)', 'class=w100,silent,mandatory,caption=Артикул,removeAndRefreshForm=packagingId|packQuantity|quantityInPack|tolerance|quantity|oldJobId');
        $this->FLD('oldJobId', 'int', 'silent,after=productId,caption=Предходно задание,removeAndRefreshForm=notes|department|packagingId|quantityInPack|storeId,input=none');
        $this->FLD('dueDate', 'date(smartTime)', 'caption=Падеж,mandatory,remember');
        
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'smartCenter,mandatory,input=hidden,before=packQuantity,silent,removeAndRefreshForm');
        $this->FNC('packQuantity', 'double(Min=0,smartRound)', 'caption=Количество,input,mandatory,after=jobQuantity');
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
        $this->FLD('quantity', 'double(decimals=2)', 'caption=Количество->Планирано,input=none');

        $this->FLD('secondMeasureId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Втора мярка->Мярка', 'input=hidden');
        $this->FLD('secondMeasureQuantity', 'double(decimals=2)', 'caption=Втора мярка->К-во,input=none');

        $this->FLD('quantityFromTasks', 'double(decimals=2)', 'input=none,caption=Количество->Произведено,notNull,value=0');
        $this->FLD('quantityProduced', 'double(decimals=2)', 'input=none,caption=Количество->Заскладено,notNull,value=0');
        $this->FLD('tolerance', 'percent(suggestions=5 %|10 %|15 %|20 %|25 %|30 %,warningMax=0.1)', 'caption=Толеранс,silent');
        $this->FLD('allowSecondMeasure', 'enum(no=Без,yes=Задължителна)', 'caption=Втора мярка,notNull,value=no,silent,removeAndRefreshForm=secondMeasureId');
        $this->FLD('department', 'key(mvc=planning_Centers,select=name,allowEmpty)', 'caption=Ц-р дейност,remember');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Произвеждане в,remember');
        $this->FLD('inputStores', 'keylist(mvc=store_Stores,select=name,allowEmpty,makeLinks)', 'caption=Влагане от,after=storeId,remember');
        $this->FLD('notes', 'richtext(rows=2,bucket=Notes,passage)', 'caption=Забележки,remember');

        $this->FLD('deliveryDate', 'date(smartTime)', 'caption=Данни от договора->Срок');
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Данни от договора->Условие');
        $this->FLD('deliveryPlace', 'key(mvc=crm_Locations,select=title,allowEmpty)', 'caption=Данни от договора->Място');

        $this->FLD('weight', 'cat_type_Weight', 'caption=Тегло,input=none');
        $this->FLD('brutoWeight', 'cat_type_Weight', 'caption=Бруто,input=none');
        $this->FLD(
            'state',
                'enum(draft=Чернова, active=Активиран, rejected=Оттеглен, closed=Приключен, stopped=Спрян, wakeup=Събуден)',
                'caption=Състояние, input=none'
        );

        $this->FLD('saleId', 'key(mvc=sales_Sales)', 'input=hidden,silent,caption=Продажба');
        $this->FLD('sharedUsers', 'userList(roles=planning|ceo)', 'caption=Споделяне->Потребители,autohide');
        $this->FLD('history', 'blob(serialize, compress)', 'caption=Данни,input=none');

        $this->setDbIndex('productId');
        $this->setDbIndex('oldJobId');
        $this->setDbIndex('saleId');
        $this->setDbIndex('createdOn');
    }


    /**
     * Връща последните валидни задания
     *
     * @param int $productId   - ид на артикул
     * @param int|null $saleId - ид на продажба, ако има
     * @return array $res      - масив с предишните задания
     */
    private static function getPreviousJob($productId, $saleId = null)
    {
        $options = $jobArr2 = array();
        $jQuery = planning_Jobs::getQuery();
        $jQuery->where("#state = 'active' OR #state = 'wakeup' OR #state = 'stopped' OR #state = 'closed'");
        $jQuery->orderBy('id', 'DESC');
        $jQuery->show('id,productId,state');

        if(isset($saleId)){
            $saleFolderId = sales_Sales::fetchField($saleId, 'folderId');
            $saleQuery = sales_Sales::getQuery();
            $saleQuery->where("#folderId = {$saleFolderId} AND (#state = 'active' OR #state = 'closed')");
            $saleQuery->show('id');
            $otherSaleIds = arr::extractValuesFromArray($saleQuery->fetchAll(), 'id');

            if(countR($otherSaleIds)){
                $jQuery2 = clone $jQuery;
                $jQuery2->in('saleId', $otherSaleIds);
                $jobArr2 = $jQuery2->fetchAll();
            }
        }

        $jQuery->where("#productId = {$productId}");
        $jobArr1 = $jQuery->fetchAll();
        foreach (array('jobArr1' => tr('Предходни'), 'jobArr2' => tr('Подобни')) as $varName => $caption){
            $state = ($varName == 'jobArr1') ? 'state-waiting' : 'state-template';
            $var = ${"{$varName}"};
            if(countR($var)){
                $options += array("{$varName}" => (object) array('group' => true, 'title' => $caption));
                foreach ($var as $jobRec){
                    if(!array_key_exists($jobRec->id, $options)){
                        $options[$jobRec->id] = (object)array('title' => self::getRecTitle($jobRec), 'attr' => array('class' => $state));
                    }
                }
            }
        }

        return $options;
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

        if(isset($rec->id) && $rec->state != 'draft'){
            $form->setReadOnly('productId');
        }

        if (isset($rec->saleId)) {

            // Ако заданието е към продажба, може да се избират само измежду артикулите в нея
            $products = sales_Sales::getManifacturableProducts($rec->saleId, true);
            $form->setFieldType('productId', 'key(mvc=cat_Products)');

            // Дефолтния артикул е първия без задание към продажбата
            $defaultProductId = null;
            foreach ($products as $pId => $pName){
                if(!static::fetchField("#productId = {$pId} AND #saleId = {$rec->saleId} AND #state != 'rejected'")){
                    $defaultProductId = $pId;
                    break;
                }
            }
            $form->setDefault('productId', $defaultProductId);
            $form->rec->_allowedProductsCnt = countR($products);
            if($form->rec->_allowedProductsCnt == 1){
                $form->setDefault('productId', key($products));
                $form->setOptions('productId', $products);
            } else {
                $form->setOptions('productId', array('' => '') + $products);
            }
        }

        // Ако има предишни задания зареждат се за избор
        if(isset($rec->productId)){
            $previousJobs = self::getPreviousJob($rec->productId, $rec->saleId);
            if (countR($previousJobs)) {
                $form->setField('oldJobId', 'input');
                $form->setOptions('oldJobId', array('' => '') + $previousJobs);
            }

            $packs = cat_Products::getPacks($rec->productId, false, $rec->secondMeasureId);
            $form->setOptions('packagingId', $packs);

            // Ако артикула не е складируем, скриваме полето за мярка
            $productRec = cat_Products::fetch($rec->productId, 'canStore,isPublic,innerClass');

            if ($productRec->canStore == 'no') {
                $form->setDefault('packagingId', key($packs));
                $measureShort = cat_UoM::getShortName($rec->packagingId);
                $form->setField('packQuantity', "unit={$measureShort}");
            } else {
                $form->setField('packagingId', 'input');
            }

            if ($tolerance = cat_Products::getParams($rec->productId, 'tolerance')) {
                $form->setDefault('tolerance', $tolerance);
            }

            if (isset($rec->saleId)) {
                $deliveryDate = null;
                $form->setDefault('dueDate', $mvc->getDefaultDueDate($rec->productId, $rec->saleId, $deliveryDate));

                $saleRec = sales_Sales::fetch($rec->saleId);
                $dRec = sales_SalesDetails::fetch("#saleId = {$rec->saleId} AND #productId = {$rec->productId}");
                $form->setDefault('packagingId', $dRec->packagingId);
                $form->setDefault('packQuantity', $dRec->packQuantity);

                // Ако има данни от продажба, попълваме ги
                $form->setDefault('storeId', $saleRec->shipmentStoreId);
                $form->setDefault('deliveryTermId', $saleRec->deliveryTermId);
                $form->setDefault('deliveryDate', $deliveryDate);
                $form->setDefault('deliveryPlace', $saleRec->deliveryLocationId);
                $locations = crm_Locations::getContragentOptions($saleRec->contragentClassId, $saleRec->contragentId);
                $form->setOptions('deliveryPlace', $locations);
                $caption = '|Данни от|* <b>' . sales_Sales::getRecTitle($rec->saleId) . '</b>';
                $caption = str_replace(',', ' ', str_replace(', ', ' ', $caption));

                $form->setField('deliveryTermId', "caption={$caption}->Условие,changable");
                $form->setField('deliveryDate', "caption={$caption}->Срок,changable");
                $form->setField('deliveryPlace', "caption={$caption}->Място,changable");
            } else {

                // Ако заданието не е към продажба, скриваме полетата от продажбата
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

            if ($Driver = cat_Products::getDriver($rec->productId)) {

                // Коя е втората мярка, ако не идва от драйвера се търси в опаковките
                $secondMeasureId = (isset($rec->id) && $rec->secondMeasureId) ? $rec->secondMeasureId : cat_Products:: getSecondMeasureId($rec->productId);

                if(empty($secondMeasureId)){
                    $form->setField('allowSecondMeasure', 'input=none');
                } else {
                    $derivitiveMeasures = cat_UoM::getSameTypeMeasures($secondMeasureId);
                    $form->setDefault('allowSecondMeasure', 'yes');
                    if(array_key_exists($rec->packagingId, $derivitiveMeasures)){
                        $mandatoryMeasure = cat_Products::fetchField($rec->productId, 'measureId');
                    } else {
                        $mandatoryMeasure = $secondMeasureId;
                    }
                    $mandatoryMeasureName = tr(cat_UoM::getVerbal($mandatoryMeasure, 'name'));
                    $form->setFieldType('allowSecondMeasure', "enum(no=Без,yes=Задължително ({$mandatoryMeasureName}))");
                    $form->setDefault('secondMeasureId', $secondMeasureId);
                }
            }
        }

        if($data->action == 'clone'){
            $form->setReadOnly('department');
        }
    }


    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        // Преименуване на бутона за запис и нов
        if(isset($data->form->rec->saleId) && $data->form->rec->_allowedProductsCnt > 1){
            if (!empty($data->form->toolbar->buttons['saveAndNew'])) {
                $data->form->toolbar->renameBtn('saveAndNew', 'Активиране и нов');
            }
        } else {
            $data->form->toolbar->removeBtn('saveAndNew');
        }
    }


    /**
     * Дефолтна дата на падеж
     *
     * @param int $productId - ид на артикул
     * @param int $saleId    - ид на сделка
     *
     * @return NULL|datetime - дефолтния падеж
     */
    private static function getDefaultDueDate($productId, $saleId, &$deliveryDate)
    {
        $saleRec = sales_Sales::fetch($saleId);
        if (empty($saleId)) {
            
            return;
        }
        
        if (!empty($saleRec->deliveryTime)) {
            $deliveryDate = $saleRec->deliveryTime;
        } elseif (!empty($saleRec->deliveryTermTime)) {
            $deliveryDate = dt::addSecs($saleRec->deliveryTermTime, $saleRec->activatedOn);
        }
        
        if (empty($deliveryDate)) {
            
            return;
        }
        
        $deliveryDate = dt::verbal2mysql($deliveryDate, false);
        $saleClassId = sales_Sales::getClassId();
        $transRec = sales_TransportValues::fetch("#docClassId = {$saleClassId} AND #docId = {$saleId}", 'deliveryTime');
        $subtractTime = 3 * 24 * 60 * 60 + $transRec->deliveryTime;
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
        if (!Request::get('Rejected', 'int')) {
            $data->listFilter->FNC('view', 'enum(all=Всички,progress=Според изпълнението,draft=Черновите,active=Активните,activenotasks=Активните без задачи,stopped=Спрените,closed=Приключените,wakeup=Събудените)', 'caption=Изглед,input,silent');
            $data->listFilter->input('view', 'silent');
            $data->listFilter->setDefault('view', 'all');
            $data->listFilter->showFields .= ',view';
        }
        
        $data->listFilter->setField('selectPeriod', 'caption=Падеж');
        $data->listFilter->FLD('contragentFolderId', 'key2(mvc=doc_Folders,allowEmpty,coverInterface=crm_ContragentAccRegIntf)', 'caption=Контрагент,silent,after=view');
        $data->listFilter->input('contragentFolderId', 'silent');
        $data->listFilter->input();
        $data->listFilter->showFields .= ',contragentFolderId';
        
        if ($filter = $data->listFilter->rec) {
            if (isset($filter->contragentFolderId)) {

                // Намиране на ид-та на всички продажби в избраната папка на контрагента
                $sQuery = sales_Sales::getQuery();
                $sQuery->where("#folderId = {$filter->contragentFolderId} AND #state NOT IN ('draft', 'pending', 'rejected')");
                $sQuery->show('id');
                $sales = arr::extractValuesFromArray($sQuery->fetchAll(), 'id');
                if(countR($sales)){
                    $data->query->where('#saleId IS NOT NULL');
                    $data->query->in('saleId', $sales);
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
        
        $data->packagingData->listFields['packagingId'] = 'Опаковка';
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
        
        if (cat_Boms::haveRightFor('add', (object) array('productId' => $rec->productId, 'type' => 'production', 'originId' => $rec->containerId))) {
            $data->toolbar->addBtn('Рецепта', array('cat_Boms', 'add', 'productId' => $rec->productId, 'originId' => $rec->containerId, 'quantityForPrice' => $rec->quantity, 'ret_url' => true, 'type' => 'production'), 'ef_icon = img/16/add.png,title=Създаване на нова работна рецепта,row=2');
        }
        
        // Бутон за добавяне на документ за производство
        if (planning_DirectProductionNote::haveRightFor('add', (object) array('originId' => $rec->containerId))) {
            $pUrl = array('planning_DirectProductionNote', 'add', 'originId' => $rec->containerId, 'ret_url' => true);
            $data->toolbar->addBtn('Произвеждане', $pUrl, 'ef_icon = img/16/page_paste.png,title=Създаване на протокол за производство от заданието');
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
            if (isset($rec->deliveryDate) && $rec->deliveryDate < $rec->dueDate) {
                $form->setWarning('deliveryDate', 'Срокът за доставка не може да е преди падежа');
            }
            
            if (empty($rec->department)) {
                $form->setWarning('department', 'В Заданието липсва избран ц-р на дейност и ще бъде записано в нишката');
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
        // Ако заданието е към сделка и е избран департамент, да се рутира към него
        if (empty($rec->id) && isset($rec->saleId) && isset($rec->department)) {

            // Ако заданието е до продажба и има избран център, рутира се до него
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
                $hint = " 1 {$measureName} " . tr('е') . " {$coefficientVerbal} {$secondMeasureNameHint}";
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
        $rec->quantityToProduce = $rec->packQuantity - (($rec->quantityFromTasks) ? $rec->quantityFromTasks : $quantityProduced);
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
            }
            
            $row->quantityNotStored = "<div class='fright'>{$row->quantityNotStored}</div>";
        }
        
        if (isset($rec->saleId)) {
            $row->saleId = ($fields['__isDetail']) ? sales_Sales::getLink($rec->saleId, 0) : sales_Sales::getLink($rec->saleId);
            $saleRec = sales_Sales::fetch($rec->saleId, 'folderId,deliveryAdress,state');
            $row->saleFolderId = doc_Folders::recToVerbal(doc_Folders::fetch($saleRec->folderId))->title;
            if (!empty($saleRec->deliveryAdress)) {
                $row->saleDeliveryAddress = core_Type::getByName('varchar')->toVerbal($saleRec->deliveryAdress);
            }
            $row->saleId = "<span class='state-{$saleRec->state} document-handler'>{$row->saleId}</span>";
        }
        
        $row->measureId = cat_UoM::getShortName($rec->packagingId);
        $tolerance = ($rec->tolerance) ? $rec->tolerance : 0;
        $diff = $rec->packQuantity * $tolerance;

        foreach (array('quantityFromTasks', 'quantityProduced') as $fld) {
            $quantityValue = ($fld == 'quantityFromTasks') ? $rec->quantityFromTasks : $quantityProduced;
            if ($quantityValue < ($rec->packQuantity - $diff)) {
                $color = 'black';
            } elseif ($quantityValue >= ($rec->packQuantity - $diff) && $quantityValue <= ($rec->packQuantity + $diff)) {
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
        
        if (isset($fields['-single'])) {
            $canStore = cat_Products::fetchField($rec->productId, 'canStore');
            $row->captionProduced = ($canStore == 'yes') ? tr('Заскладено') : tr('Изпълнено');
            $row->captionNotStored = ($canStore == 'yes') ? tr('Незаскладено') : tr('Неизпълнено');
            
            if (isset($rec->deliveryPlace)) {
                $row->deliveryPlace = crm_Locations::getHyperlink($rec->deliveryPlace, true);
            }
            
            if (isset($rec->oldJobId)) {
                $row->oldJobId = planning_Jobs::getLink($rec->oldJobId, 0);
            }
            
            if ($sBomId = cat_Products::getLastActiveBom($rec->productId, 'sales')->id) {
                $row->sBomId = cat_Boms::getLink($sBomId, 0);
            }
            
            if ($sBomId = cat_Products::getLastActiveBom($rec->productId, 'instant')->id) {
                $row->iBomId = cat_Boms::getLink($sBomId, 0);
            }
            
            if ($pBomId = cat_Products::getLastActiveBom($rec->productId, 'production')->id) {
                $row->pBomId = cat_Boms::getLink($pBomId, 0);
            }
            
            $date = ($rec->state == 'draft') ? null : $rec->modifiedOn;
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
        $pTitle = cat_Products::getTitleById($rec->productId);
        
        return "Job{$rec->id} - {$pTitle}";
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
        
        return $row;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if (($action == 'write' || $action == 'add' || $action == 'edit') && isset($rec)){

            if(isset($rec->productId)) {
                $productRec = cat_Products::fetch($rec->productId, 'state,canManifacture,generic,innerClass');

                // Трябва да е активиран и да не е производствен етап
                if ($productRec->state != 'active' || $productRec->innerClass == planning_interface_StepProductDriver::getClassId()) {
                    $res = 'no_one';
                }

                // Трябва и да е производим
                if ($res != 'no_one') {
                    if ($productRec->canManifacture == 'no' || $productRec->generic == 'yes') {
                        $res = 'no_one';
                    }
                }
            }
                
            // Ако се създава към продажба, тя трябва да е активна
            if (!empty($rec->saleId)) {
                $saleState = sales_Sales::fetchField($rec->saleId, 'state');
                if ($saleState != 'active' && $saleState != 'closed') {
                    $res = 'no_one';
                } else {
                    $products = sales_Sales::getManifacturableProducts($rec->saleId, true);
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
            if ($rec->state != 'stopped') {
                $res = 'no_one';
            }
        }
        
        if ($action == 'close' && $rec) {
            if ($rec->state != 'active' && $rec->state != 'wakeup' && $rec->state != 'stopped') {
                $res = 'no_one';
            }
        }
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
                if (!acc_Items::isItemInList('sales_Sales', $rec->saleId, 'costObjects')) {
                    $listId = acc_Lists::fetchBySystemId('costObjects')->id;
                    acc_Items::force('sales_Sales', $rec->saleId, $listId);

                    $costObj = (object) array('containerId' => sales_Sales::fetchField($rec->saleId, 'containerId'));
                    doc_ExpensesSummary::save($costObj);
                }
            }
        }
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        // Подготвяме данните на историята за показване
        $data->row->history = array();
        if (is_array($data->rec->history)) {
            foreach ($data->rec->history as $historyRec) {
                $historyRec['action'] = tr($historyRec['action']);
                
                $historyRow = (object) array('date' => cls::get('type_DateTime')->toVerbal($historyRec['date']),
                    'user' => crm_Profiles::createLink($historyRec['user']),
                    'action' => "<span>{$historyRec['action']}</span>",
                    'stateclass' => "state-{$historyRec['engaction']}");
                
                if (isset($historyRec['reason'])) {
                    $historyRow->reason = cls::get('type_Text')->toVerbal($historyRec['reason']);
                }
                
                $data->row->history[] = $historyRow;
            }
        }
        
        $data->row->history = array_reverse($data->row->history, true);
        $data->packagingData = new stdClass();
        $data->packagingData->masterMvc = cls::get('cat_Products');
        $data->packagingData->masterId = $data->rec->productId;
        $data->packagingData->tpl = new core_ET('[#CONTENT#]');
        $data->packagingData->retUrl = planning_Jobs::getSingleUrlArray($data->rec->id);
        if ($data->rec->state == 'rejected') {
            $data->packagingData->rejected = true;
        }
        cls::get('cat_products_Packagings')->preparePackagings($data->packagingData);
        
        $data->components = array();
        cat_Products::prepareComponents($data->rec->productId, $data->components, 'job', $data->rec->quantity);
    }
    
    
    /**
     * След промяна на състоянието
     */
    protected static function on_AfterChangeState($mvc, &$rec, $action)
    {
        // Записваме в историята действието
        self::addToHistory($rec->history, $action, $rec->modifiedOn, $rec->modifiedBy, $rec->_reason);
        $mvc->save_($rec, 'history');
        
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
     * Преизчисляваме какво количество е произведено по заданието
     *
     * @param int $containerId - ид на запис
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

            if(planning_Tasks::haveRightFor('createjobtasks', (object)array('jobId' => $jobRec->id, 'type' => 'all'))){
                $title = tr('Несъздадените шаблонни операции');
                $createAllUrl = array('planning_Tasks', 'createjobtasks', 'type' => 'all', 'jobId' => $jobRec->id, 'ret_url' => true);
                $urlLink = ht::createBtn('Създаване', $createAllUrl, 'Наистина ли желаете да създадете наведнъж всички останали шаблонни операции|*?', false, 'title=Създаване на всички шаблонни операции за артикула,ef_icon=img/16/add.png');
                $options[] = (object)array('DEFAULT_TASK_CAPTION' => $title, 'DEFAULT_TASK_LINK' => $urlLink, 'DEFAULT_TASK_TR_CLASS' => 'createAllTasksForJob', 'DEFAULT_TASK_CAPTION_COLSPAN' => 1);
            }

            // Показване на наличните дефолтни операции
            foreach ($defaultTasks as $sysId => $defTask){
                $title = $defTask->title;
                $warning = false;
                if($taskId = planning_Tasks::fetchField("#originId = {$jobRec->containerId} AND #systemId = {$sysId} AND #state != 'rejected'")){
                    $warning = 'Наистина ли желаете да създадете отново шаблонна операция|*?';
                    if(planning_Tasks::haveRightFor('single', $taskId)){
                        $title = ht::createLinkRef($title, planning_Tasks::getSingleUrlArray($taskId), false, 'title=Преглед на производствената операция');
                    }
                    $title = "<span class='quiet'>{$title}</span>";
                }

                $folderId = isset($defTask->centerId) ? planning_Centers::fetchField($defTask->centerId, 'folderId') : $folderId;
                $urlAdd = array();
                if(planning_Tasks::haveRightFor('add', (object)array('originId' => $jobRec->containerId, 'productId' => $defTask->productId, 'folderId' => $folderId))){
                    $urlAdd = array('planning_Tasks', 'add', 'folderId' => $folderId, 'originId' => $jobRec->containerId, 'title' => $defTask->title, 'ret_url' => true, 'systemId' => $sysId);
                }

                $urlLink = ht::createBtn('Създаване', $urlAdd, $warning, false, 'title=Създаване на производствена операция,ef_icon=img/16/add.png');
                $options[] = (object)array('DEFAULT_TASK_CAPTION' => $title, 'DEFAULT_TASK_LINK' => $urlLink, 'DEFAULT_TASK_CAPTION_COLSPAN' => 1);
            }
        }

        // Показване на наличните опции за клониране на операция от предходно задание
        if (isset($jobRec->oldJobId)) {
            $oldTasks = planning_Tasks::getTasksByJob($jobRec->oldJobId, array('draft', 'waiting', 'active', 'wakeup', 'stopped', 'closed'));

            if (countR($oldTasks)) {
                $options[] = (object)array('DEFAULT_TASK_CAPTION' => tr('От предишно задание') . planning_Jobs::getLink($jobRec->oldJobId, 0), 'DEFAULT_TASK_LINK' => null, 'DEFAULT_TASK_TR_CLASS' => 'selectTaskFromJobRow', 'DEFAULT_TASK_CAPTION_COLSPAN' => 3);
                foreach ($oldTasks as $k1 => $link) {
                    $oldTitle = $link;
                    $warning = false;
                    if($taskId = planning_Tasks::fetchField("#originId = {$jobRec->containerId} AND #clonedFromId = {$k1} AND #state != 'rejected'")){
                        if(planning_Tasks::haveRightFor('single', $taskId)){
                            $oldTitle = ht::createLinkRef($oldTitle, planning_Tasks::getSingleUrlArray($taskId), false, 'title=Преглед на производствената операция');
                        }
                        $oldTitle = "<span class='quiet'>{$oldTitle}</span>";
                        $warning = 'Наистина ли желаете да клониране отново операцията от предходното задание|*?';
                    }

                    $urlClone = array();
                    if(planning_Tasks::requireRightFor('createjobtasks', (object)array('jobId' => $jobRec->id, 'cloneId' => $k1, 'type' => 'clone'))){
                        $urlClone = array('planning_Tasks', 'createjobtasks', 'type' => 'clone', 'cloneId' => $k1, 'jobId' => $jobRec->id, 'ret_url' => true);
                    }

                    $urlLink = ht::createBtn('Клониране', $urlClone, $warning, false, 'title=Създаване на производствена операция,ef_icon=img/16/clone.png');
                    $options[] = (object)array('DEFAULT_TASK_CAPTION' => $oldTitle, 'DEFAULT_TASK_LINK' => $urlLink, 'DEFAULT_TASK_CAPTION_COLSPAN' => 1);
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
                $options[] = (object)array('DEFAULT_TASK_CAPTION' => $dName, 'DEFAULT_TASK_LINK' => $urlLink, 'DEFAULT_TASK_CAPTION_COLSPAN' => 1, 'DEFAULT_TASK_TR_CLASS' => $trClass);
            }
        } else {
            $options[] = (object)array('DEFAULT_TASK_CAPTION' => tr('Няма налични центрове'), 'DEFAULT_TASK_LINK' => null, 'DEFAULT_TASK_TR_CLASS' => null, 'DEFAULT_TASK_CAPTION_COLSPAN' => 1);
        }

        // Всяка опция се добавя във формата
        foreach ($options as $obj){
            $dTaskBlock = clone $form->info->getBlock('DEFAULT_TASK_BLOCK');
            $dTaskBlock->placeObject($obj);
            $dTaskBlock->removeBlocksAndPlaces();
            $form->info->append($dTaskBlock, 'TASK_ROWS');
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
        
        $query = self::getQuery();
        $query->where("#state = 'active' || #state = 'closed' || #state = 'wakeup' || (#state = 'rejected' && (#brState = 'active' || #brState = 'closed'))");
        $query->where("#modifiedOn >= '{$timeline}'");
        $query->show('activatedBy,activatedOn,modifiedOn,state,createdBy,productId');
        
        while ($rec = $query->fetch()) {
            $activatedBy = isset($rec->activatedBy) ? $rec->activatedBy : $rec->createdBy;
            if (empty($activatedBy) || $activatedBy == core_Users::SYSTEM_USER) continue;

            $personId = crm_Profiles::fetchField("#userId = {$activatedBy}", 'personId');
            $classId = planning_Jobs::getClassId();
            
            setIfNot($rec->activatedOn, $rec->modifiedOn);
            $date = dt::verbal2mysql($rec->activatedOn, false);
            
            $isRejected = ($rec->state == 'rejected');
            hr_Indicators::addIndicatorToArray($result, $date, $personId, $rec->id, $classId, $iRec->id, 1, $isRejected);
            
            if ($Driver = cat_Products::getDriver($rec->productId)) {
                $difficulty = $Driver->getDifficulty($rec->productId);
                if (isset($difficulty)) {
                    hr_Indicators::addIndicatorToArray($result, $date, $personId, $rec->id, $classId, $iRec2->id, $difficulty, $isRejected);
                }
            }
        }
        
        return $result;
    }
    
    
    /**
     * След намиране на текста за грешка на бутона за 'Приключване'
     */
    public function getCloseBtnError($rec)
    {
        if (doc_Containers::fetchField("#threadId = {$rec->threadId} AND #state = 'pending'")) {
            
            return 'Заданието не може да се приключи, защото има документи в състояние "Заявка"';
        }

        $notClosedTasks = array();
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#originId = {$rec->containerId} AND #state IN ('active', 'wakeup', 'stopped', 'pending', 'draft')");
        $tQuery->show('id');
        while($tRec = $tQuery->fetch()){
            $notClosedTasks[] = "#" . planning_Tasks::getHandle($tRec->id);
        }

        if(countR($notClosedTasks)){

            return "Заданието не може да бъде приключено докато не са приключени операциите към него|*: " . implode(', ', $notClosedTasks);
        }
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
     * @param mixed $id
     * @return array $threadsArr
     */
    public static function getJobLinkedThreads($id)
    {
        $rec = static::fetchRec($id);

        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#originId = {$rec->containerId}");
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
                // Записване на очакваното количество за производство
                $res[] = (object)array('storeId'          => $rec->storeId,
                                       'productId'        => $rec->productId,
                                       'date'             => $date,
                                       'quantityIn'       => $quantityToProduce,
                                       'quantityOut'      => null,
                                       'genericProductId' => $genericProductId);
            }

            // Ако има активна рецепта
            if($lastReceipt = cat_Products::getLastActiveBom($rec->productId, 'production,instant,sales')){

                // Кои са материалите и
                $receiptClassId = cat_Boms::getClassId();
                $materialArr = cat_Boms::getBomMaterials($lastReceipt, $rec->quantity);
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

        return $res;
    }


    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     */
    public static function canAddToThread($threadId)
    {
        $saleId = Request::get('saleId', 'int');

        return isset($saleId);
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

        static::closeActiveJobs(planning_Setup::get('JOB_AUTO_COMPLETION_PERCENT'), null, $delay);

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
     * @param $id
     * @return array
     */
    public static function getAllocatedServices($id)
    {
        $jobRec = planning_Jobs::fetchRec($id);

        $res = array();
        $taskExpenseItemIds = static::getTaskCostObjectItems($jobRec);
        if(!countR($taskExpenseItemIds)) return $res;

        $createdOn = dt::verbal2mysql($jobRec->createdOn, false);
        $Balance = new acc_ActiveShortBalance(array('from' => $createdOn, 'to' => dt::today(), 'accs' => '60201', 'item1' => $taskExpenseItemIds, 'keepUnique' => true));
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
     * @return array $convertedArr
     */
    public static function getDefaultProductionDetailsFromConvertedByNow($rec)
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
        $pNoteClassId = planning_DirectProductionNote::getClassId();

        while($pRec = $pQuery1->fetch()){

            $aArray = array('' => $pRec->quantity);

            if(core_Packs::isInstalled('batch')){
                $bQuery = batch_BatchesInDocuments::getQuery();
                $bQuery->where("#detailClassId = {$pNoteClassId} AND #detailRecId = {$pRec->id}");
                while($dRec1 = $bQuery->fetch()){
                    $aArray["{$dRec1->batch}"] = $dRec1->quantity;
                    $aArray[""] -= $dRec1->quantity;
                }
                if(empty($aArray[""])){
                    unset($aArray[""]);
                }
            }

            $fromAccId = ($pRec->canStore != 'yes') ? '61102' : '';
            foreach ($aArray as $batch => $q){
                // Те ще се влагат от склада в който са произведени
                $key = "{$pRec->productId}|{$pRec->packagingId}||{$fromAccId}|{$pRec->storeId}|{$batch}";
                if(!array_key_exists($key, $convertedArr)){
                    $batch = !empty($batch) ? $batch : null;
                    $measureId = cat_Products::fetchField($pRec->productId, 'measureId');
                    $convertedArr[$key] = (object)array('productId' => $pRec->productId, 'packagingId' => $pRec->packagingId, 'quantityInPack' => $pRec->quantityInPack, 'measureId' => $measureId, 'quantityExpected' => 0, 'expenseItemId' => null, 'fromAccId' => null, 'type' => 'input', 'storeId' => $pRec->storeId, 'batch' => $batch, 'fromAccId' => $fromAccId);
                    $consumable[$pRec->productId] = $pRec->productId;
                }

                $convertedArr[$key]->quantityExpected += $q;
            }
        }

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
                $key = "{$dRec->productId}|{$dRec->packagingId}||||";
                $cSign = $sign;

                // Ако артикула е заготовка, но вече е заскладен в посочения склад ще се приспада к-то от него
                if(array_key_exists($dRec->productId, $consumable) && $cSign > 0){
                    $fromAccId = ($dRec->canStore == 'yes') ? null : '61102';
                    $storeId = ($dRec->canStore == 'yes') ?  $dRec->storeId : null;

                    $aArray = array('' => $dRec->quantity);

                    if(core_Packs::isInstalled('batch')){
                        $bQuery = batch_BatchesInDocuments::getQuery();
                        $bQuery->where("#detailClassId = {$DetailMvc->getClassId()} AND #detailRecId = {$dRec->id}");
                        while($bRec = $bQuery->fetch()){
                            $aArray["{$bRec->batch}"] = $bRec->quantity;
                            $aArray[""] -= $bRec->quantity;
                        }
                        if(empty($aArray[""])){
                            unset($aArray[""]);
                        }
                    }

                    foreach ($aArray as $batch => $q){
                        $key1 = "{$dRec->productId}|{$dRec->packagingId}||{$fromAccId}|{$storeId}|{$batch}";

                        if(array_key_exists($key1, $convertedArr)){
                            $convertedArr[$key1]->quantityExpected -= $q;
                        } else {
                            $key1 = "{$dRec->productId}|{$dRec->packagingId}||||";
                            if(!array_key_exists($key1, $convertedArr)){
                                $convertedArr[$key1] = (object)array('productId' => $dRec->productId, 'packagingId' => $dRec->packagingId, 'quantityInPack' => $dRec->quantityInPack, 'measureId' => $dRec->measureId, 'quantityExpected' => 0, 'expenseItemId' => null, 'fromAccId' => null, 'type' => 'input', 'batch' => null);
                            }
                            $convertedArr[$key1]->quantityExpected += $sign * $q;
                        }
                    }
                } else {
                    if(!array_key_exists($key, $convertedArr)){
                        $convertedArr[$key] = (object)array('productId' => $dRec->productId, 'packagingId' => $dRec->packagingId, 'quantityInPack' => $dRec->quantityInPack, 'measureId' => $dRec->measureId, 'quantityExpected' => 0, 'expenseItemId' => null, 'fromAccId' => null, 'type' => 'input', 'batch' => null);
                    }

                    $convertedArr[$key]->quantityExpected += $sign * $dRec->quantity;
                }
            }
        }

        // Кои са всички разпределни услуги по ПО, които са разходни обекти
        $allocatedProducts = planning_Jobs::getAllocatedServices($rec);
        if(countR($allocatedProducts)){
            foreach ($allocatedProducts as $aRec){
                $key = "{$aRec->productId}|{$aRec->measureId}|{$aRec->expenseItemId}|61102||";
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
            if(!isset($dRec->storeId) || array_key_exists($dRec->productId, $consumable)){
                if(!isset($dRec->storeId)){
                    // Приспада се произведеното до сега
                    $key = "{$dRec->productId}|{$dRec->packagingId}|{$dRec->expenseItemId}|{$dRec->fromAccId}||";

                    if(array_key_exists($key, $convertedArr)){
                        $convertedArr[$key]->quantityExpected -= $dRec->quantity;
                    }

                } else {
                    $aArray = array("" => $dRec->quantity);

                    if(core_Packs::isInstalled('batch')){
                        $bQuery = batch_BatchesInDocuments::getQuery();
                        $bQuery->where("#detailClassId = {$detailClassId} AND #detailRecId = {$dRec->id}");
                        while($bRec = $bQuery->fetch()){
                            $aArray["{$bRec->batch}"] = $bRec->quantity;
                            $aArray[""] -= $bRec->quantity;
                        }
                        if(empty($aArray[""])){
                            unset($aArray[""]);
                        }

                        foreach ($aArray as $batch => $q) {
                            $key1 = "{$dRec->productId}|{$dRec->packagingId}|||{$dRec->storeId}|{$batch}";
                            if(array_key_exists($key1, $convertedArr)){
                                $convertedArr[$key1]->quantityExpected -= $q;
                            }
                        }
                    }
                }
            }
        }

        // Остават само положителните количества
        foreach ($convertedArr as $cId => $cObj){
            if($cObj->quantityExpected <= 0){
                unset($convertedArr[$cId]);
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
     * @param int|null $productId        - ид на артикул
     * @param int|null $noNewDocumentsIn - за колко време назад да се гледа да няма нови контиращи документи в нишката
     * @return int $count                - колко са приключените задания
     */
    public static function closeActiveJobs($tolerance, $productId = null, $noNewDocumentsIn = null)
    {
        $thresholdDate = ($noNewDocumentsIn) ? dt::addSecs(-1 * $noNewDocumentsIn, dt::now()) : null;
        $me = cls::get(get_called_class());
        $query = static::getQuery();
        $query->where("#state IN ('active', 'wakeup')");
        $query->XPR('completed', 'percent', 'round(#quantityProduced / #quantity, 2)');
        $query->where("#completed >= {$tolerance}");

        // Ако ще се гледат само за един артикул - за него, иначе за всички задания към затворени артикули
        if(isset($productId)){
            $query->where("#productId = {$productId}");
        }

        $count = 0;
        while($rec = $query->fetch()){

            // Ако има документ на заявка в нишката, няма да се приключва заданието
            if(doc_Containers::fetchField("#threadId = {$rec->threadId} AND #state = 'pending'")) continue;

            // Ако е указано да няма нови документи в нишката в последните X време да се пропуска
            if(isset($thresholdDate)){
                $lastCreatedOn = doc_Threads::getLastCreatedOnInThread($rec->threadId, 'acc_TransactionSourceIntf');
                if($lastCreatedOn >= $thresholdDate) continue;
            }

            // Затваряне на артикула
            $rec->brState = $rec->state;
            $rec->state = 'closed';
            $rec->timeClosed = dt::now();
            $count++;
            if ($me->save($rec, 'brState,state,timeClosed')) {
                $me->logWrite("Автоматично приключване", $rec->id);
                $me->invoke('AfterChangeState', array(&$rec, $rec->state));
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
        $folderId = $query->fetch()->folderId;
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
        $taskRecs = planning_Tasks::getTasksByJob($rec->id, array('draft', 'waiting', 'active', 'wakeup', 'stopped'));
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
        if ($data->form && $data->form->isSubmitted() && $data->form->cmd == 'save_n_new') {

            // и заданието е към продажба
            if(isset($data->form->rec->saleId)){

                // Редиректва се към същата форма за пускане на задание за следващия артикул
                $saleRec = sales_Sales::fetch($data->form->rec->saleId, 'id,threadId,containerId');
                $data->retUrl = $data->addJobUrl = array('planning_Jobs', 'add', 'saleId' => $saleRec->id, 'threadId' => $saleRec->threadId, 'foreignId' => $saleRec->containerId, 'ret_url' => getRetUrl());
            }
        }
    }
}
