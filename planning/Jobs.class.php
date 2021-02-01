<?php


/**
 * Мениджър на Задания за производство
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
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
    public $loadList = 'plg_RowTools2, store_plg_StockPlanning, doc_DocumentPlg, planning_plg_StateManager, doc_SharablePlg, planning_Wrapper, plg_Sorting, acc_plg_DocumentSummary, plg_Search, change_Plugin, plg_Clone, plg_Printing, doc_plg_SelectFolder, cat_plg_AddSearchKeywords';
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'dueDate,packQuantity,notes,tolerance,sharedUsers';
    
    
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
    public $filterDateField = 'createdOn,dueDate,deliveryDate,modifiedOn';
    
    
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
    public $fieldsNotToClone = 'dueDate,quantityProduced,history,oldJobId,secondMeasureId,secondMeasureQuantity';


    /**
     *  При преминаването в кои състояния ще се обновяват планираните складови наличностти
     */
    public $updatePlannedStockOnChangeStates = array('stopped', 'wakeup', 'active');


    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'planning_Centers';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=canManifacture,hasnotProperties=generic,maxSuggestions=100,forceAjax)', 'silent,mandatory,caption=Артикул,removeAndRefreshForm=packagingId|packQuantity|quantityInPack|tolerance|quantity|oldJobId');
        $this->FLD('oldJobId', 'int', 'silent,after=productId,caption=Предходно задание,removeAndRefreshForm=notes|department|packagingId|quantityInPack|storeId,input=none');
        $this->FLD('dueDate', 'date(smartTime)', 'caption=Падеж,mandatory');
        
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'smartCenter,mandatory,input=hidden,before=packQuantity');
        $this->FNC('packQuantity', 'double(Min=0,smartRound)', 'caption=Количество,input,mandatory,after=jobQuantity');
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
        $this->FLD('quantity', 'double(decimals=2)', 'caption=Количество->Планирано,input=none');

        $this->FLD('secondMeasureId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Втора мярка->Мярка', 'input=none');
        $this->FLD('secondMeasureQuantity', 'double(decimals=2)', 'caption=Втора мярка->К-во,input=none');

        $this->FLD('quantityFromTasks', 'double(decimals=2)', 'input=none,caption=Количество->Произведено,notNull,value=0');
        $this->FLD('quantityProduced', 'double(decimals=2)', 'input=none,caption=Количество->Заскладено,notNull,value=0');
        $this->FLD('tolerance', 'percent(suggestions=5 %|10 %|15 %|20 %|25 %|30 %,warningMax=0.1)', 'caption=Толеранс,silent');
        $this->FLD('department', 'key(mvc=planning_Centers,select=name,allowEmpty)', 'caption=Ц-р дейност');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад');
        $this->FLD('notes', 'richtext(rows=2,bucket=Notes)', 'caption=Забележки');

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
    }
    
    
    /**
     * Връща последните валидни задания за артикула
     *
     * @param int $productId - ид на артикул
     * @param int $id        - ид на текущото задание
     *
     * @return array $res    - масив с предишните задания
     */
    private static function getOldJobs($productId, $id, $folderId)
    {
        $res = array();
        
        // Старите задания към артикула или към артикулите в неговата папка
        $pQuery = cat_Products::getQuery();
        $pQuery->where("#folderId = {$folderId}");
        $pQuery->show('id');
        $products = arr::extractValuesFromArray($pQuery->fetchAll(), 'id');
        $products[$productId] = $productId;
        
        $query = self::getQuery();
        $query->in('productId', $products);
        $query->where("#id != '{$id}' AND (#state = 'active' OR #state = 'wakeup' OR #state = 'stopped' OR #state = 'closed')");
        
        $query->orderBy('id', 'DESC');
        $query->show('id,productId,state');
        
        while ($rec = $query->fetch()) {
            $res[$rec->id] = self::getRecTitle($rec);
        }
        
        return $res;
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
            $products = sales_Sales::getManifacurableProducts($rec->saleId, true);
            $form->setFieldType('productId', 'key(mvc=cat_Products)');
            if(countR($products) == 1){
                $form->setDefault('productId', key($products));
                $form->setOptions('productId', $products);
            } else {
                $form->setOptions('productId', array('' => '') + $products);
            }
        }

        // Ако има предишни задания зареждат се за избор
        if(isset($rec->productId)){
            $oldJobs = self::getOldJobs($rec->productId, $rec->id, $rec->folderId);
            if (countR($oldJobs)) {
                $form->setField('oldJobId', 'input');
                $form->setOptions('oldJobId', array('' => '') + $oldJobs);
            }

            $packs = cat_Products::getPacks($rec->productId);
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
            $data->listFilter->FNC('view', 'enum(createdOn=По дата на създаване,dueDate=По дата на падеж,deliveryDate=По дата за доставка,progress=Според изпълнението,all=Всички,draft=Черновите,active=Активните,activenotasks=Активните без задачи,stopped=Спрените,closed=Приключените,wakeup=Събудените)', 'caption=Изглед,input,silent');
            $data->listFilter->input('view', 'silent');
            $data->listFilter->setDefault('view', 'createdOn');
            $data->listFilter->showFields .= ',view';
        }
        
        $data->listFilter->setField('selectPeriod', 'caption=Падеж');
        $contragentsWithJobs = self::getContragentsWithJobs();
        if (countR($contragentsWithJobs)) {
            $data->listFilter->FLD('contragent', 'int', 'caption=Контрагенти,input,silent');
            $data->listFilter->setOptions('contragent', array('' => '') + $contragentsWithJobs);
            $data->listFilter->input('contragent', 'silent');
        }
        
        $data->listFilter->input();
        $data->listFilter->showFields .= ',contragent';
        
        if ($filter = $data->listFilter->rec) {
            if (isset($filter->contragent)) {
                
                // Намиране на ид-та на всички продажби в избраната папка на контрагента
                $sQuery = sales_Sales::getQuery();
                $sQuery->where("#folderId = {$filter->contragent}");
                $sQuery->show('id');
                $sales = arr::extractValuesFromArray($sQuery->fetchAll(), 'id');
                
                // Филтрират се само тези задания към посочените продажби
                $data->query->where('#saleId IS NOT NULL');
                $data->query->in('saleId', $sales);
            }
            
            // Филтър по изглед
            if (isset($filter->view)) {
                switch ($filter->view) {
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
                    case 'deliveryDate':
                        arr::placeInAssocArray($data->listFields, array('deliveryDate' => 'Дата за доставка'), 'modifiedOn');
                        $data->query->orderBy('deliveryDate', 'ASC');
                        break;
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
     * Извличане с кеширане на списъка на контрагентите със задания
     *
     * @return array $options
     */
    private static function getContragentsWithJobs()
    {
        $options = core_Cache::get('planning_Jobs', 'contragentsWithJobs', 120, array('planning_Jobs'));
        
        if(!is_array($options) || !countR($options)) {
            $options = array();
            $query = self::getQuery();
            $query->EXT('sFolderId', 'sales_Sales', 'externalName=folderId,externalKey=saleId');
            $query->groupBy('sFolderId');
            $query->where('#saleId IS NOT NULL');
            $query->show('sFolderId');
            
            while ($jRec = $query->fetch()) {
                $options[$jRec->sFolderId] = doc_Folders::getTitleById($jRec->sFolderId);
            }
            
            core_Cache::set('planning_Jobs', 'contragentsWithJobs', $options, 120, array('planning_Jobs'));
        }
        
        return $options;
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

            if ($aCount = self::count("#productId = {$rec->productId} AND (#state = 'active' OR #state = 'stopped' OR #state = 'wakeup') AND #id != '{$rec->id}'")) {
                $aCount = core_Type::getByName('int')->toVerbal($aCount);
                $msg = ($aCount == 1) ? 'активно задание' : 'активни задания';
                $form->setWarning('productId', "В момента артикулът има още|* <b>{$aCount}</b> |{$msg}|*. |Желаете ли да създадете още едно|*?");
            }

            $productInfo = cat_Products::getProductInfo($form->rec->productId);
            $rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
            $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
            $rec->isEdited = true;
            
            $brutoWeight = cat_Products::getTransportWeight($rec->productId, $rec->quantity);
            $rec->brutoWeight = (!empty($brutoWeight)) ? $brutoWeight : null;
            
            $nettoWeight = cat_Products::convertToUom($rec->productId, 'kg');
            $rec->weight = (!empty($nettoWeight)) ? $nettoWeight : null;
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

        if ($rec->isEdited === true && isset($rec->id) && $rec->_isClone !== true) {
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
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->title = ($fields['-single']) ? $mvc->getRecTitle($rec) : $mvc->getLink($rec->id);
        $row->quantity = $mvc->getFieldType('quantity')->toVerbal($rec->quantityFromTasks);
        $Double = core_Type::getByName('double(smartRound)');
        $row->quantityProduced = $Double->toVerbal($rec->quantityProduced);
        $measureId = cat_Products::fetchField($rec->productId, 'measureId');

        if (isset($rec->productId) && empty($fields['__isDetail'])) {
            $rec->quantityFromTasks = planning_Tasks::getProducedQuantityForJob($rec);
            $rec->quantityFromTasks /= $rec->quantityInPack;
            $row->quantityFromTasks = $Double->toVerbal($rec->quantityFromTasks);
        }

        $packType = cat_UoM::fetchField($rec->packagingId, 'type');
        if($packType != 'uom'){
            $rec->quantityProduced /= $rec->quantityInPack;
        } else {
            $converted = cat_UoM::convertValue($rec->quantityProduced, $measureId,  $rec->packagingId);
            if($converted !== false){
                $rec->quantityProduced = $converted;
            }
        }

        $row->quantityProduced = $Double->toVerbal($rec->quantityProduced);
        $packQuantity = $rec->packQuantity;
        $originalQuantityProduced = $rec->quantityProduced;

        // Ако има втора мярка
        if(!empty($rec->secondMeasureId)){
            $derivitiveMeasures = cat_UoM::getSameTypeMeasures($rec->secondMeasureId);

            $coefficient = $originalQuantityProduced / $rec->secondMeasureQuantity;
            $coefficientVerbal = core_Type::getByName('double(smartRound)')->toVerbal($coefficient);

            $additionalQuantityVerbal  = $Double->toVerbal($rec->secondMeasureQuantity);
            $additionalMeasureName = tr(cat_UoM::getShortName($rec->secondMeasureId));
            $measureName = tr(cat_UoM::getShortName($measureId));
            $originalMeasureName = $measureName;
            $originalSecondMeasureName = $additionalMeasureName;
            $hint = " 1 {$additionalMeasureName} " . tr('е') . " {$coefficientVerbal} {$measureName}";

            // Ако втората мярка е опаковката подменям ги
            if(array_key_exists($rec->packagingId, $derivitiveMeasures)){
                $secondMeasureQuantity = cat_UoM::convertValue($rec->secondMeasureQuantity, $rec->secondMeasureId, $rec->packagingId);
                $additionalQuantityVerbal  = $Double->toVerbal($secondMeasureQuantity);

                $row->quantityProduced = $additionalQuantityVerbal;
                $additionalQuantityVerbal = $Double->toVerbal($originalQuantityProduced);
                $additionalMeasureName = $originalMeasureName;
                $measureName = $originalSecondMeasureName;
                $originalQuantityProduced = $rec->secondMeasureQuantity;
                $hint = " 1 {$measureName} " . tr('е') . " {$coefficientVerbal} {$additionalMeasureName}";
            }

            // Показване на съотвествието с втората мярка
            if(isset($fields['-single'])){
                $additionalMeasureName = ht::createHint($additionalMeasureName, $hint);
                $row->quantityProduced = "{$row->quantityProduced} <span style='font-weight:normal;color:darkblue;font-size:15px;font-style:italic;'>({$additionalQuantityVerbal} {$additionalMeasureName}) </span>";
            }
        }

        $rec->quantityNotStored = $rec->quantityFromTasks - $originalQuantityProduced;
        $row->quantityNotStored = $Double->toVerbal($rec->quantityNotStored);
        $rec->quantityToProduce = $packQuantity - (($rec->quantityFromTasks) ? $rec->quantityFromTasks : $originalQuantityProduced);
        $row->quantityToProduce = $Double->toVerbal($rec->quantityToProduce);
        
        foreach (array('quantityNotStored', 'quantityToProduce') as $fld) {
            if ($rec->{$fld} < 0) {
                $row->{$fld} = "<span class='red'>{$row->{$fld}}</span>";
            }
        }
        
        if (cat_Boms::haveRightFor('add', (object) array('productId' => $rec->productId, 'type' => 'production', 'originId' => $rec->containerId))) {
            core_RowToolbar::createIfNotExists($row->_rowTools);
            $row->_rowTools->addLink('Нова работна рецепта', array('cat_Boms', 'add', 'productId' => $rec->productId, 'originId' => $rec->containerId, 'quantityForPrice' => $rec->quantity, 'ret_url' => true, 'type' => 'production'), "ef_icon=img/16/article.png,title=Създаване на нова работна рецепта");
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
            if ($rec->{$fld} < ($rec->packQuantity - $diff)) {
                $color = 'black';
            } elseif ($rec->{$fld} >= ($rec->packQuantity - $diff) && $rec->{$fld} <= ($rec->packQuantity + $diff)) {
                $color = 'green';
            } else {
                $row->{$fld} = ht::createHint($row->{$fld}, 'Произведено е повече от планираното', 'warning', false);
                $color = 'red';
            }
            
            if ($rec->{$fld} != 0) {
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
            
            // Ако има сделка и пакета за партиди е инсталиран показваме ги
            if (isset($rec->saleId) && core_Packs::isInstalled('batch')) {
                $query = batch_BatchesInDocuments::getQuery();
                $saleContainerId = sales_Sales::fetchField($rec->saleId, 'containerId');
                $query->where("#containerId = {$saleContainerId} AND #productId = {$rec->productId}");
                $query->show('batch,productId');
                
                $batchArr = array();
                while ($bRec = $query->fetch()) {
                    $batchArr = $batchArr + batch_Movements::getLinkArr($bRec->productId, $bRec->batch);
                }
                $row->batches = implode(', ', $batchArr);
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
    public function getDocumentRow($id)
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
                $productRec = cat_Products::fetch($rec->productId, 'state,canManifacture,generic');

                // Трябва да е активиран
                if ($productRec->state != 'active') {
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
                    $products = sales_Sales::getManifacurableProducts($rec->saleId, true);
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
        if ($rec->state == 'closed') {
            $count = 0;
            $tQuery = planning_Tasks::getQuery();
            $tQuery->where("#originId = '{$rec->containerId}' AND #state != 'draft' AND #state != 'rejected' AND #state != 'stopped'");
            while ($tRec = $tQuery->fetch()) {
                $tRec->state = 'closed';
                cls::get('planning_Tasks')->save_($tRec, 'state');
                $count++;
            }
            
            if (!empty($count)) {
                core_Statuses::newStatus(tr("|Затворени са|* {$count} |задачи по заданието|*"));
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
     *
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
        if($secondMeasureId = cat_products_Packagings::getSecondMeasureId($rec->productId)) {
            $secondMeasureDerivities = cat_UoM::getSameTypeMeasures($secondMeasureId);
            unset($secondMeasureDerivities['']);
            $quantityProduced = 0;

            // Първо се обхождат тези протоколи, в които има въведени и двете мерки
            $secondMeasureArr = array();
            foreach($allRecs as $noteRec){
                $quantityProduced += $noteRec->quantity;

                if(empty($noteRec->additionalMeasureId)) continue;

                // Сумиране на общо какви к-ва от двете мерки има
                if(array_key_exists($noteRec->packagingId, $secondMeasureDerivities)){
                    $secondMeasureArr[$secondMeasureId]['quantity'] += cat_UoM::convertToBaseUnit($noteRec->packQuantity, $noteRec->packagingId);
                    $secondMeasureArr[$secondMeasureId]['baseQuantity'] += $noteRec->quantity;
                } elseif(array_key_exists($noteRec->additionalMeasureId, $secondMeasureDerivities)){
                    $secondMeasureArr[$secondMeasureId]['quantity'] += cat_UoM::convertToBaseUnit($noteRec->additionalMeasureQuantity, $noteRec->additionalMeasureId);
                    $secondMeasureArr[$secondMeasureId]['baseQuantity'] += $noteRec->quantity;
                }
            }

            // Смятане на коефициента
            if(countR($secondMeasureArr)){

                // Ако има въведени съотвествия коефициента, ще е реалния
                $coefficient = round($secondMeasureArr[$secondMeasureId]['baseQuantity'] / $secondMeasureArr[$secondMeasureId]['quantity'], 5);
                $secondMeasureQuantity = $secondMeasureArr[$secondMeasureId]['quantity'];
            } else {

                // Колко е коефициента
                $packRec = cat_products_Packagings::getPack($rec->productId, $secondMeasureId);

                // Ако няма проверява се някоя от нейните производни
                if(!is_object($packRec)){
                    $sameTypeMeasureIds = cat_UoM::getSameTypeMeasures($secondMeasureId);
                    unset($sameTypeMeasureIds['']);
                    unset($sameTypeMeasureIds[$secondMeasureId]);
                    $sameTypeMeasureIds = array_keys($sameTypeMeasureIds);

                    // Ако има да се конвертира
                    foreach ($sameTypeMeasureIds as $sId){
                        if($packRec = cat_products_Packagings::getPack($rec->productId, $sId)){
                            if(!in_array($rec->packagingId, $sameTypeMeasureIds)){
                                $coefficient = cat_UoM::convertValue($packRec->quantity, $sId, $secondMeasureId);
                            } else {
                                $coefficient = $packRec->quantity;
                            }
                            break;
                        }
                    }
                } else {
                    $coefficient = $packRec->quantity;
                }

                // Ако няма ще е теоретичния
                $secondMeasureQuantity = 0;
            }

            // След това се търсят протоколите където се произвежда само в една мярка
            foreach($allRecs as $noteRec1) {
                if (!empty($noteRec1->additionalMeasureId)) continue;

                if(array_key_exists($noteRec1->packagingId, $secondMeasureDerivities)){

                    // Ако тази мярка е втората няма да се конвертира
                    $secondMeasureQuantity += cat_UoM::convertToBaseUnit($noteRec1->packQuantity, $noteRec1->packagingId);
                } else {

                    // Обръщане във втората мярка според реалния коефициент
                    $secondMeasureQuantity += round($noteRec1->quantity / $coefficient, 5);
                }
            }

            // Записване на информацията за вторите мерки
            $rec->quantityProduced = empty($quantityProduced) ? 0 : $quantityProduced;
            $rec->secondMeasureId = empty($quantityProduced) ? null : $secondMeasureId;
            $rec->secondMeasureQuantity = $secondMeasureQuantity;
            $saveFields .= ',secondMeasureId,secondMeasureQuantity';
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
        $folderId = (!empty($jobRec->department)) ? planning_Centers::fetchField($jobRec->department, 'folderId') : null;
        
        $form = cls::get('core_Form');
        $form->title = 'Създаване на производствена операция към|* <b>' . self::getHyperlink($jobRec->id, true) . '</b>';
        $form->FLD('select', 'varchar', 'caption=Избор,mandatory');
        
        $options = $this->getTaskOptions($jobRec);
        if(countR($options)){
            $form->setOptions('select', $options);
            $form->setDefault('select', 'new');
        } else {
            $form->setReadOnly('select');
        }
        
        $form->input();
        if ($form->isSubmitted()) {
            $action = $form->rec->select;
            $actionArr = explode('|', $action);
            if ($actionArr[0] == 'sys') {
                
                // Създаване на шаблонна операция
                $defaultTasks = cat_Products::getDefaultProductionTasks($jobRec, $jobRec->quantity);
                $draft = $defaultTasks[$actionArr[1]];
                $url = array('planning_Tasks', 'add', 'folderId' => $folderId, 'originId' => $jobRec->containerId, 'title' => $draft->title, 'ret_url' => true, 'systemId' => $actionArr[1]);
                redirect($url);
            } elseif ($actionArr[0] == 'c') {
                
                // Клониране на стара операция
                $Tasks = cls::get('planning_Tasks');
                $taskRec = planning_Tasks::fetch($actionArr[1]);
                
                $newTask = clone $taskRec;
                plg_Clone::unsetFieldsNotToClone($Tasks, $newTask, $taskRec);
                $newTask->_isClone = true;
                $newTask->originId = $jobRec->containerId;
                $newTask->state = 'draft';
                unset($newTask->id);
                unset($newTask->threadId);
                unset($newTask->containerId);
                if ($Tasks->save($newTask)) {
                    $Tasks->invoke('AfterSaveCloneRec', array($taskRec, &$newTask));
                }
                
                redirect(array('planning_Tasks', 'single', $newTask->id), false, 'Операцията е клонирана успешно');
            } elseif ($actionArr[0] == 'new') {
                redirect(array('planning_Tasks', 'add', 'originId' => $jobRec->containerId, 'folderId' => $actionArr[1], 'ret_url' => true));
            }
        }
        
        $form->toolbar->addSbBtn('Напред', 'default', 'ef_icon = img/16/move.png, title=Създаване на нова операция');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);
        
        return $tpl;
    }
    
    
    /**
     * Помощен метод подготвящ опциите за създаване на задача към задание
     *
     * @param stdRec $rec - към кое задание
     *
     * @return array $options - масив с опции при създаване на задача
     */
    private function getTaskOptions($rec)
    {
        $options = array();
        
        // Има ли дефолтни задачи от артикула
        $defaultTasks = cat_Products::getDefaultProductionTasks($rec, $rec->quantity);
        if (countR($defaultTasks)) {
            foreach ($defaultTasks as $k => $defRec) {
                $options["sys|{$k}"] = $defRec->title;
            }
            
            $options = array('d' => (object) array('group' => true, 'title' => tr('Шаблонни операции от артикула'))) + $options;
        }
        
        // Имали задачи за клониране
        if (isset($rec->oldJobId)) {
            $oldTasks = planning_Tasks::getTasksByJob($rec->oldJobId);
            
            if (countR($oldTasks)) {
                $options1 = array();
                foreach ($oldTasks as $k1 => $oldTitle) {
                    $options1["c|{$k1}"] = $oldTitle;
                }
                
                $options += array('c' => (object) array('group' => true, 'title' => tr('Клониране от предходни операции'))) + $options1;
            }
        }
        
        // За всички цехове, добавя се опция за добавяне
        $options2 = array();
        $departments = planning_Centers::getCentersForTasks($rec->id);
        foreach ($departments as $depFolderId => $dName) {
            $options2["new|{$depFolderId}"] = tr("В|* {$dName}");
        }
        
        if(countR($options2)){
            $options += array('new' => (object) array('group' => true, 'title' => tr('Нови операции'))) + $options2;
        }
        
        // Връщане на опциите за избор
        return $options;
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
            if (empty($activatedBy) || $activatedBy == core_Users::SYSTEM_USER) {
                continue;
            }
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

        $productRec = cat_Products::fetch($rec->productId, 'canStore,canConvert');
        $quantityToProduce = round($rec->quantity - $rec->quantityProduced, 4);

        // В кои нишки има документи отнасящи се за заданието
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#originId = {$rec->containerId}");
        $tQuery->show("threadId");
        $threadsArr = array($rec->threadId => $rec->threadId) + arr::extractValuesFromArray($tQuery->fetchAll(), 'threadId');

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
                            $res[] = (object)array('storeId'          => $rec->storeId,
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
}
