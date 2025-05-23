<?php


/**
 * Мениджър за технологични рецепти на артикули
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_Boms extends core_Master
{
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Технологични рецепти';
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, cat_Wrapper, doc_DocumentPlg, plg_Printing, doc_plg_Close, doc_plg_Prototype, acc_plg_DocumentSummary, doc_ActivatePlg, plg_Clone, cat_plg_AddSearchKeywords, plg_Search, change_Plugin, plg_Sorting,plg_Select';
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'title,showInProduct,expenses,isComplete';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title=Документ,productId=За артикул,state,expenses=Реж.,createdOn,createdBy,modifiedOn,modifiedBy';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'productId,notes';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'cat_BomDetails';


    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsSingleField = 'name';

    
    /**
     * Кой е основния детайл
     */
    public $mainDetail = 'cat_BomDetails';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'cat_BomDetails';


    /**
     * Кои полета да не бъдат презаписвани от шаблона
     */
    public $fieldsNotToCopyFromTemplate = 'type,productId,regeneratedFromId';


    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Технологична рецепта';
    
    
    /**
     * Икона на единичния изглед на търговската рецепта
     */
    public $singleIcon = 'img/16/article2.png';
    
    
    /**
     * Икона на единичния изглед на работната рецепта
     */
    public $singleProductionBomIcon = 'img/16/article.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Bom';
    
    
    /**
     * Кой може да пише?
     */
    public $canEdit = 'cat,ceo,sales,planning';


    /**
     * Кой може да променя активирани записи
     *
     * @see change_Plugin
     */
    public $canChangerec = 'cat,ceo,sales,planning';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'cat,ceo,sales,planning';
    
    
    /**
     * Кой може да преизчислява себестойността?
     */
    public $canRecalcselfvalue = 'ceo, acc, cat, price';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'cat,ceo,sales,purchase,planning';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'cat,ceo,sales,purchase,planning';


    /**
     * Кой може да го регенерира рецептата?
     */
    public $canRegenerate = 'cat,ceo,sales,planning';


    /**
     * Кой може да затваря?
     */
    public $canClose = 'cat,ceo,sales,planning';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'cat/tpl/SingleLayoutBom.shtml';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn,lastUpdatedDetailOn,modifiedOn';
    
    
    /**
     * Искаме ли в листовия филтър да е попълнен филтъра по дата
     *
     * @see acc_plg_DocumentSummary
     */
    public $filterAutoDate = false;
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'cat,ceo';
    
    
    /**
     * Коефициент за изчисляване на минималния и максималния тираж
     */
    const PRICE_COEFFICIENT = 0.5;
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Опашка от активираните рецепти
     */
    private static $activatedBoms = array();
    
    
    /**
     * Опашка от спрените рецепти
     */
    private static $stoppedActiveBoms = array();


    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'title,hash,regeneratedFromId,lastUpdatedDetailOn,lastUpdatedDetailBy';


    /**
     * Брояч
     */
    public static $calcPriceCounter = array();


    /**
     * Да се показва ли антетката
     */
    public $showLetterHead = true;


    /**
     * Дали да се показват последно видяните документи при избора на шаблонен
     */
    public $showInPrototypesLastVisited = true;


    /**
     * Кой може да синхронизира параметрите?
     */
    public $canSyncparams = 'cat,ceo';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('title', 'varchar(124,nullIfEmpty)', 'caption=Заглавие,tdClass=nameCell');
        $this->FLD('quantity', 'double(smartRound,Min=0)', 'caption=За,silent,mandatory');
        $this->FLD('type', 'enum(sales=Търговска,production=Работна,instant=Моментна)', 'caption=Вид,input=hidden,silent');

        $this->FLD('expenses', 'percent(min=0)', 'caption=Общи режийни,changeable,placeholder=Автоматично');
        $this->FLD('isComplete', 'enum(auto=Автоматично,yes=Без допълване (рецептата е Пълна),no=Допълване до "Себестойност" (рецептата е Непълна))', 'caption=Себестойност,notNull,value=auto,mandatory,width=100%');
        $this->FLD('state', 'enum(draft=Чернова, active=Активиран, rejected=Оттеглен, closed=Затворен,template=Шаблон)', 'caption=Статус, input=none');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden,silent');
        $this->FLD('showInProduct', 'enum(,auto=Автоматично,product=В артикула,job=В заданието,yes=Навсякъде,no=Никъде)', 'caption=Показване в артикула,changeable');
        $this->FLD('notes', 'richtext(rows=4,bucket=Notes)', 'caption=Забележки');
        $this->FLD('quantityForPrice', 'double(smartRound,min=0)', 'caption=Изчисляване на себестойност->При тираж,silent');
        $this->FLD('hash', 'varchar', 'input=none');
        $this->FLD('regeneratedFromId', 'key(mvc=cat_Boms,select=id)', 'input=none');
        $this->FLD('lastUpdatedDetailOn', 'datetime(format=smartTime)', 'caption=Промяна на детайла->На,silent,input=none');
        $this->FLD('lastUpdatedDetailBy', 'key(mvc=core_Users,select=nick)', 'caption=Промяна на детайла->От,input=none');

        $this->setDbIndex('productId');
        $this->setDbIndex('productId,state,type');
        $this->setDbUnique('productId,title');
    }
    
    
    /**
     * Показване на рецептата в артикула
     *
     * @param int      $id
     * @param core_Mvc $className
     *
     * @return bool
     */
    public static function showIn($id, $className)
    {
        $rec = self::fetchRec($id);
        $showInProduct = !empty($rec->showInProduct) ? $rec->showInProduct : cat_Setup::get('SHOW_BOM_IN_PRODUCT');
        
        switch ($showInProduct) {
            case 'auto':
                $res = (cat_Products::fetchField($rec->productId, 'fixedAsset') == 'yes');
                break;
            case 'yes':
                $res = true;
                break;
            case 'product':
                $res = ($className == 'cat_Products');
                break;
            case 'job':
                $res = ($className == 'planning_Jobs');
                break;
            default:
                $res = false;
        }
        
        return $res;
    }
    
    
    /**
     * Преди подготвяне на едит формата
     */
    protected static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
        $type = Request::get('type');
        if (!$type) return;
        
        $mvc->singleTitle = ($type == 'sales') ? 'Търговска рецепта' : (($type == 'instant') ? 'Моментна рецепта' : 'Работна рецепта');
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

        $productInfo = cat_Products::getProductInfo($rec->productId);
        $shortUom = cat_UoM::getShortName($productInfo->productRec->measureId);
        $form->setField('quantity', "unit={$shortUom}");
        $form->setField('quantityForPrice', "unit={$shortUom}");
        
        // К-то е дефолтното от заданието
        if (isset($rec->originId)) {
            $origin = doc_Containers::getDocument($rec->originId);
            if ($origin->isInstanceOf('planning_Jobs')) {
                $form->setDefault('quantity', $origin->fetchField('quantity'));
            }
        }
        $form->setDefault('quantity', 1);
        $defaultOverheadCost = cat_Products::getDefaultOverheadCost($rec->productId);
        if(!empty($defaultOverheadCost)){
            $defaultOverheadCostPlaceholder = $mvc->getFieldType('expenses')->toVerbal($defaultOverheadCost['overheadCost']);
            $form->setField('expenses', "placeholder={$defaultOverheadCostPlaceholder}");
        }
    }
    
    
    /**
     * Преди запис
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if (isset($rec->threadId)) {
            if(empty($rec->type)){
                $rec->type = 'sales';
                
                $firstDocument = doc_Containers::getDocument($rec->originId);
                if ($firstDocument->isInstanceOf('planning_Jobs')) {
                    $rec->type = 'production';
                }
            }
        }
    }
    
    
    /**
     * Преди запис на клониран запис
     */
    protected static function on_BeforeSaveCloneRec($mvc, $rec, &$nRec)
    {
        $nRec->cloneDetails = true;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        if ($rec->cloneDetails === true || !empty($rec->prototypeId) || $rec->_regenerate === true) return;

        $activeBom = null;
        cat_BomDetails::addProductComponents($rec->productId, $rec->id, null, $activeBom, true);
    }
    
    
    /**
     * Активира последната затворена рецепта за артикула
     *
     * @param mixed $id
     *
     * @return FALSE|int
     */
    private function activateLastBefore($id)
    {
        $rec = $this->fetchRec($id);
        if ($rec->state != 'closed' && $rec->state != 'rejected') {
            
            return false;
        }
        
        // Намираме последната приключена рецепта (различна от текущата за артикула)
        $query = $this->getQuery();
        $query->where("#state = 'closed' AND #id != {$rec->id} AND #productId = {$rec->productId} AND #type = '{$rec->type}'");
        $query->orderBy('id', 'DESC');

        while ($nextActiveBomRec = $query->fetch()) {

            // Ако предишната активна рецепта е ОК - активира се
            if($this->isOk($nextActiveBomRec)){
                $nextActiveBomRec->state = 'active';
                $nextActiveBomRec->brState = 'closed';
                $nextActiveBomRec->modifiedOn = dt::now();

                $id = $this->save_($nextActiveBomRec, 'state,brState,modifiedOn');
                $this->logWrite("Активиране на последна '" . $this->getVerbal($rec, 'type') . "' рецепта", $id);
                doc_DocumentCache::cacheInvalidation($nextActiveBomRec->containerId);

                return $id;
            }
        }

        return false;
    }
    
    
    /**
     * Реакция в счетоводния журнал при оттегляне на счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    protected static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        if($rec->brState == 'active'){
            static::$stoppedActiveBoms[$rec->id] = $rec;
        }
    }
    
    
    /**
     * След промяна на състоянието
     */
    protected function on_AfterChangeState($mvc, $rec, $state)
    {
        $rec = $mvc->fetchRec($rec);
        if ($state == 'closed' && $rec->brState == 'active') {
            static::$stoppedActiveBoms[$rec->id] = $rec;
        } elseif($state == 'active' && $rec->brState == 'closed'){
            static::$activatedBoms[$rec->id] = $rec;
        }
    }
    
    
    /**
     * Реакция в счетоводния журнал при възстановяване на оттеглен счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    protected static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        if($rec->state == 'active'){
            static::$activatedBoms[$rec->id] = $rec;
        }
    }
    
    
    /**
     * Функция, която прихваща след активирането на документа
     */
    protected static function on_AfterActivation($mvc, &$rec)
    {
        $rec = $mvc->fetchRec($rec);
        static::$activatedBoms[$rec->id] = $rec;
    }
    
    
    /**
     * Обновява списъците със свойства на номенклатурите от които е имало засегнати пера
     *
     * @param acc_Items $mvc
     */
    public static function on_Shutdown($mvc)
    {
        if(countR(static::$activatedBoms)){
            foreach (static::$activatedBoms as $rec){

                // Намираме всички останали активни рецепти
                $query = static::getQuery();
                $query->where("#state = 'active' AND #id != {$rec->id} AND #productId = {$rec->productId} AND #type = '{$rec->type}'");
                
                // Затваряме ги
                $idCount = 0;
                while ($bomRec = $query->fetch()) {
                    $bomRec->state = 'closed';
                    $bomRec->brState = 'active';
                    $bomRec->modifiedOn = dt::now();
                    $mvc->save_($bomRec, 'state,brState,modifiedOn');
                    $mvc->logWrite("Затваряне при активиране на нова '" . $mvc->getVerbal($rec, 'type') . "' рецепта", $bomRec->id);
                    
                    doc_DocumentCache::cacheInvalidation($bomRec->containerId);
                    $idCount++;
                }
                
                if ($idCount) {
                    core_Statuses::newStatus("|Затворени рецепти|*: {$idCount}");
                }

                // Ако има задания към артикула да се обновят запазените им количества
                $jQuery = planning_Jobs::getQuery();
                $jQuery->where("#productId = {$rec->productId} AND #state IN ('active', 'stopped', 'wakeup')");
                $jQuery->show('id');
                while($jRec = $jQuery->fetch()){
                    store_StockPlanning::updateByDocument('planning_Jobs', $jRec->id);
                }
            }
        }

        // Ако по изключените е имало запазени количества, рекалкулират се запазените по заданията
        if(countR(static::$stoppedActiveBoms)){
            foreach (static::$stoppedActiveBoms as $rec){
                store_StockPlanning::recalcByReff($mvc, $rec->id);
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        //  При активацията на рецептата променяме датата на модифициране на артикула
        $type = (isset($rec->type)) ? $rec->type : $mvc->fetchField($rec->id, 'type');
        if ($type == 'sales' && $rec->state != 'draft') {
            $productId = (isset($rec->productId)) ? $rec->productId : $mvc->fetchField($rec->id, 'productId');
            cat_Products::touchRec($productId);
        }
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);
        
        // Обновяваме датата на модифициране на артикула след промяна по рецептата
        if ($rec->productId) {
            $bRec = cat_Products::getLastActiveBom($rec->productId, 'sales');
            if (($rec->type == 'sales' && !$bRec) || $bRec->id == $rec->id) {
                cat_Products::touchRec($rec->productId);
            }
        }
        
        doc_DocumentCache::cacheInvalidation($rec->containerId);
        $rec->lastUpdatedDetailOn = dt::now();
        $rec->lastUpdatedDetailBy = core_Users::getCurrent();

        return $this->save_($rec, 'lastUpdatedDetailOn,lastUpdatedDetailBy,modifiedOn,modifiedBy,searchKeywords');
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        // Документа не може да се създава  в нова нишка, ако е възоснова на друг
        if (!empty($data->form->toolbar->buttons['btnNewThread'])) {
            $data->form->toolbar->removeBtn('btnNewThread');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if (in_array($action, array('add', 'edit', 'regenerate')) && isset($rec)) {
            
            // Може да се добавя само ако има ориджин
            if (empty($rec->productId)) {
                $res = 'no_one';
            } else {
                $productRec = cat_Products::fetch($rec->productId, 'state,canManifacture,threadId');
                if ($rec->type != 'production' && !doc_Threads::haveRightFor('single', $productRec->threadId)) {
                    $res = 'no_one';
                } else {
                    
                    // Трябва да е активиран
                    if ($productRec->state != 'active' && $productRec->state != 'template') {
                        $res = 'no_one';
                    } else {
                        if ($productRec->canManifacture == 'no') {
                            $res = 'no_one';
                        }
                    }
                }
            }
        }
        
        if ($action == 'add' && isset($rec->originId)) {
            $origin = doc_Containers::getDocument($rec->originId);
            $threadId = $origin->fetchField('threadId');

            if($origin->isInstanceOf('planning_Tasks')){
                $res = 'no_one';
            } elseif(in_array($origin->fetchField('state'), array('draft', 'rejected'))) {
                $res = 'no_one';
            } elseif(!doc_Threads::haveRightFor('single', $threadId)){
                $res = 'no_one';
            }
        }
        
        if (($action == 'add' || $action == 'edit' || $action == 'reject' || $action == 'restore' || $action == 'changerec') && isset($rec)) {
            if ($rec->type == 'production') {
                if (!haveRole('cat,planning,ceo', $userId)) {
                    $res = 'no_one';
                }
            }
        }
        
        // Ако няма ид, не може да се активира
        if ($action == 'activate' && empty($rec->id)) {
            $res = 'no_one';
        } elseif ($action == 'activate' && isset($rec->id)) {
            if (!cat_BomDetails::fetchField("#bomId = {$rec->id}", 'id')) {
                $res = 'no_one';
            }
        }
        
        // Кой може да оттегля и възстановява
        if (($action == 'reject' || $action == 'restore') && isset($rec)) {
            
            // Ако не можеш да редактираш записа, не можеш да оттегляш/възстановяваш
            if (!haveRole($mvc->getRequiredRoles('edit'))) {
                $res = 'no_one';
            }
        }
        
        if($action == 'recalcselfvalue' && isset($rec)){
            if(Mode::isReadOnly() || $rec->state == 'rejected'){
                $res = 'no_one';
            }
        }

        if ($action == 'close' && isset($rec)) {
            if(!in_array($rec->state, array('active', 'closed'))){
                $res = 'no_one';
            }
        }

        if ($action == 'regenerate' && isset($rec)) {
            if($rec->state != 'active'){
                $res = 'no_one';
            } elseif(!cat_BomDetails::count("#bomId={$rec->id} AND #type = 'stage'")) {
                $res = 'no_one';
            }
        }

        // Само на активните производими артикули с универсален драйвер, може да им се агрегират параметрите
        if($action == 'syncparams' && isset($rec)){
            if(!cat_Products::haveDriver($rec->productId, 'cat_GeneralProductDriver')){
                $res = 'no_one';
            } else{
                $productRec = cat_Products::fetch($rec->productId, 'state,canManifacture');
                if($productRec->canManifacture != 'yes' || $productRec->state != 'active'){
                    $res = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetchRec($id);
        
        $row = new stdClass();
        $row->title = $this->getRecTitle($rec);
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $rec->title;
        
        return $row;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (empty($rec->showInProduct)) {
            $showInProduct = cat_Setup::get('SHOW_BOM_IN_PRODUCT');
            $row->showInProduct = $mvc->getFieldType('showInProduct')->toVerbal($showInProduct);
            $row->showInProduct = ht::createHint($row->showInProduct, 'По подразбиране');
        }
        
        $row->productId = cat_Products::getShortHyperlink($rec->productId);
        $row->title = $mvc->getLink($rec->id, 0);
        $row->singleTitle = ($rec->type == 'sales') ? tr('Търговска рецепта') : (($rec->type == 'instant') ? tr('Моментна рецепта') : ('Работна рецепта'));
        $measureId = cat_Products::fetchField($rec->productId, 'measureId');

        $shortUom = cat_UoM::getShortName($measureId);
        $row->quantity .= ' ' . $shortUom;

        $row->title = $mvc->getHyperlink($rec, true);
        if ($fields['-single']) {
            if(!doc_HiddenContainers::isHidden($rec->containerId)) {
                $row->title = empty($rec->title) ? null : $mvc->getVerbal($rec, 'title');
                $rec->quantityForPrice = isset($rec->quantityForPrice) ? $rec->quantityForPrice : $rec->quantity;

                try {
                    $price = cat_Boms::getBomPrice($rec->id, $rec->quantityForPrice, 0, 0, dt::now(), price_ListRules::PRICE_LIST_COST);
                } catch (core_exception_Expect $e) {
                    core_Statuses::newStatus($e->getMessage(), 'error');
                    reportException($e);
                    $price = 0;
                }

                $overheadCost = $rec->expenses;
                if (!isset($rec->expenses)) {
                    $defaultOverheadCost = cat_Products::getDefaultOverheadCost($rec->productId);
                    if (!empty($defaultOverheadCost)) {
                        $overheadCost = $defaultOverheadCost['overheadCost'];
                        $defaultOverheadCostVerbal = $mvc->getFieldType('expenses')->toVerbal($defaultOverheadCost['overheadCost']);
                        $row->expenses = ht::createHint("<span style='color:blue'>{$defaultOverheadCostVerbal}</span>", "Автоматично|* {$defaultOverheadCost['hint']}");
                    } else {
                        $row->expenses = ht::createHint("<span style='color:blue'>n/a</span>", "Не може да се определи автоматично|*!");
                    }
                }

                if (haveRole('ceo, acc, cat, price')) {
                    $row->quantityForPrice = $mvc->getFieldType('quantity')->toVerbal($rec->quantityForPrice);
                    $rec->primeCost = ($price) ? $price : 0;

                    $baseCurrencyCode = acc_Periods::getBaseCurrencyCode($rec->modifiedOn);
                    $Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
                    $row->primeCost = $Double->toVerbal($rec->primeCost);

                    if ($rec->primeCost === 0 && cat_BomDetails::fetchField("#bomId = {$rec->id}", 'id')) {
                        $row->primeCost = "<span class='red'>???</span>";
                    } else {
                        $row->primeCost = ht::styleNumber($row->primeCost, $rec->primeCost);
                        $row->primeCost = "<b>{$row->primeCost}</b>";

                        if(isset($overheadCost) && !empty($rec->primeCost)){
                            $rec->primeCostWithOverheadCost = $rec->primeCost * (1 + $overheadCost);
                            $row->primeCostWithOverheadCost = $Double->toVerbal($rec->primeCostWithOverheadCost);
                            $row->primeCostWithOverheadCost = ht::styleNumber($row->primeCostWithOverheadCost, $rec->primeCostWithOverheadCost);
                        }
                    }

                    $row->primeCost = currency_Currencies::decorate($row->primeCost, $baseCurrencyCode);
                    $row->primeCost = ($rec->primeCost === 0 && cat_BomDetails::fetchField("#bomId = {$rec->id}", 'id')) ? "<b class='red'>???</b>" : "<b>{$row->primeCost}</b>";
                    $row->primeCost .= tr("|*, <i>|при тираж|* {$row->quantityForPrice} {$shortUom}</i>");
                    if(!empty($row->primeCostWithOverheadCost)){
                        $row->primeCostWithOverheadCost = currency_Currencies::decorate($row->primeCostWithOverheadCost, $baseCurrencyCode);
                    }
                }

                if ($mvc->haveRightFor('recalcselfvalue', $rec)) {
                    $row->primeCost .= ht::createLink('', array($mvc, 'RecalcSelfValue', $rec->id), false, 'ef_icon=img/16/arrow_refresh.png,title=Преизчисляване на себестойността');
                }

                if ($rec->isComplete == 'auto') {
                    $autoValue = cat_Setup::get('DEFAULT_BOM_IS_COMPLETE');
                    $row->isComplete = $mvc->getFieldType('isComplete')->toVerbal($autoValue);
                    $row->isComplete = ht::createHint($row->isComplete, 'Стойността е автоматично определена');
                }

                if(isset($rec->regeneratedFromId)){
                    $row->regeneratedFromId = cat_Boms::getLink($rec->regeneratedFromId, 0);
                }

                if(isset($rec->clonedFromId) && empty($rec->prototypeId)){
                    $row->clonedFromId = cat_Boms::getLink($rec->clonedFromId, 0);
                }

                if(isset($rec->prototypeId)){
                    $row->prototypeId = cat_Boms::getLink($rec->prototypeId, 0);
                    unset($row->clonedFromId);
                }
            }
        }
    }
    
    
    /**
     * Връща информация с ресурсите използвани в технологичната рецепта
     *
     * @param mixed $id - ид или запис
     * @param double $quantity - ид или запис
     * @param datetime $date - ид или запис
     *
     * @return array $res - Информация за рецептата
     *               ->quantity - к-во
     *               ->resources
     *               o $res->productId      - ид на материала
     *               o $res->type           - вложим или отпаден материал
     *               o $res->baseQuantity   - начално количество наматериала (к-во в опаковка по брой опаковки)
     *               o $res->propQuantity   - пропорционално количество на ресурса (к-во в опаковка по брой опаковки)
     */
    public static function getResourceInfo($id, $quantity, $date)
    {
        $resources = $materials = array();
        
        expect($rec = static::fetchRec($id));
        $resources['quantity'] = ($rec->quantity) ? $rec->quantity : 1;
        $resources['expenses'] = null;

        try{
            $resources['primeCost'] = static::getBomPrice($id, $quantity, 0, 0, $date, price_ListRules::PRICE_LIST_COST, $materials);
        } catch(core_exception_Expect $e){
            reportException($e);
            $resources['primeCost'] = null;
        }

        $resources['resources'] = array_values($materials);

        if (is_array($materials)) {
            foreach ($materials as &$m) {
                if ($m->propQuantity != cat_BomDetails::CALC_ERROR) {
                    $m->propQuantity /= $m->quantityInPack;
                }
            }
        }

        $expenses = $rec->expenses;
        if(!$expenses){
            $defaultOverheadCost = cat_Products::getDefaultOverheadCost($rec->productId);
            $expenses = $defaultOverheadCost['overheadCost'];
        }

        if ($expenses) {
            $resources['expenses'] = $expenses;
        }
        
        // Връщаме намерените ресурси
        return $resources;
    }
    
    
    /**
     * Функция, която се извиква преди активирането на документа
     */
    protected static function on_BeforeActivation($mvc, $res)
    {
        if ($res->id) {
            if (!cat_BomDetails::count("#bomId = {$res->id} AND (#type = 'input' || #type = 'stage')")) {
                core_Statuses::newStatus('Рецептатата не може да се активира, докато няма поне един вложим ресурс или етап', 'warning');
                
                return false;
            }
        }
    }
    
    
    /**
     * Ф-я за добавяне на нова рецепта към артикул
     *
     * @param mixed   $productId - ид или запис на производим артикул
     * @param int   $quantity    - количество за което е рецептата
     * @param array $details     - масив с обекти за детайли
     *                        ->resourceId   - ид на ресурс
     *                        ->type         - действие с ресурса: влагане/отпадък, ако не е подаден значи е влагане
     *                        ->stageId      - опционално, към кой производствен етап е детайла
     *                        ->baseQuantity - начално количество на ресурса
     *                        ->propQuantity - пропорционално количество на ресурса
     * @param string  $notes   - забележки
     * @param float $expenses  - процент режийни разходи
     *
     * @return int $id         - ид на новосъздадената рецепта
     */
    public static function createNewDraft($productId, $quantity, $originId, $details = array(), $notes = null, $expenses = null)
    {
        // Проверка на подадените данни
        expect($pRec = cat_Products::fetchRec($productId));
        expect($pRec->canManifacture == 'yes', $pRec);
        $origin = doc_Containers::getDocument($originId);
        $type = ($origin->isInstanceOf('planning_Jobs')) ? 'production' : 'sales';
        
        $Double = cls::get('type_Double');
        $Richtext = cls::get('type_Richtext');
        
        $rec = (object) array('productId' => $productId,
            'type' => $type,
            'originId' => $originId,
            'folderId' => $origin->rec()->folderId,
            'threadId' => $origin->rec()->threadId,
            'quantity' => $Double->fromVerbal($quantity),
            'expenses' => $expenses);
        if ($notes) {
            $rec->notes = $Richtext->fromVerbal($notes);
        }
        
        // Ако има данни за детайли, проверяваме дали са валидни
        if (countR($details)) {
            foreach ($details as &$d) {
                expect($d->resourceId);
                expect(cat_Products::fetch($d->resourceId));
                $d->type = ($d->type) ? $d->type : 'input';
                expect(in_array($d->type, array('input', 'pop', 'subProduct')));
                
                $d->baseQuantity = $Double->fromVerbal($d->baseQuantity);
                $d->propQuantity = $Double->fromVerbal($d->propQuantity);
                $d->quantityInPack = $Double->fromVerbal($d->quantityInPack);
                expect($d->baseQuantity || $d->propQuantity);
            }
        }
        
        // Ако всичко е наред, записваме мастъра на рецептата
        $rec->cloneDetails = true;
        $id = self::save($rec);
        
        // За всеки детайл, добавяме го към рецептата
        if (countR($details)) {
            foreach ($details as $d1) {
                $d1->bomId = $id;
                $fields = array();
                if (cls::get('cat_BomDetails')->isUnique($d1, $fields)) {
                    cat_BomDetails::save($d1);
                }
            }
        }
        
        // Връщаме ид-то на новосъздадената рецепта
        return $id;
    }
    
    
    /**
     * Форсира изчисляването на себестойността по рецептата
     */
    public function act_RecalcSelfValue()
    {
        $this->requireRightFor('recalcselfvalue');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('recalcselfvalue');
        
        $rec->modifiedOn = dt::now();
        $this->save_($rec, 'modifiedOn');
        
        return new Redirect(array($this, 'single', $id), '|Себестойността е преизчислена|*!');
    }
    
    
    /**
     * Създава дефолтната рецепта за артикула.
     * Проверява за артикула може ли да се създаде дефолтна рецепта,
     * ако може затваря предишната дефолтна рецепта (ако е различна) и създава нова
     * активна рецепта с подадените данни.
     *
     * @param mixed $productId - ид на артикул
     *
     * @return null|int
     */
    public static function createDefault($productId)
    {
        $pRec = cat_Products::fetchRec($productId);
        $Driver = cat_Products::getDriver($productId);
        $bomInfo = $Driver->getDefaultBom($pRec);
       
        // Ако има информация за дефолтна рецепта
        if ($bomInfo) {
            $hash = md5(serialize($bomInfo));
            $details = array();
            $error = array();
            $hasInputMats = false;
            
            // И има материали
            if (is_array($bomInfo['materials'])) {
                foreach ($bomInfo['materials'] as $matRec) {
                    
                    // Имали артикул с такъв код
                    if (!$prod = cat_Products::getByCode($matRec->code)) {
                        $error[$matRec->code] = $matRec->code;
                        continue;
                    }
                    
                    // Подготвяме детайлите на рецептата
                    $nRec = new stdClass();
                    $nRec->resourceId = $prod->productId;
                    $nRec->baseQuantity = $matRec->baseQuantity;
                    $nRec->propQuantity = $matRec->propQuantity;
                    $nRec->quantityInPack = 1;
                    $nRec->type = ($matRec->waste) ? 'pop' : (($matRec->isSubProduct) ? 'subProduct' : 'input');
                    $nRec->packagingId = cat_Products::fetchField($prod->productId, 'measureId');
                    if (isset($prod->packagingId)) {
                        $nRec->packagingId = $prod->packagingId;
                        if ($pRec = cat_products_Packagings::getPack($prod->productId, $prod->packagingId)) {
                            $nRec->quantityInPack = $pRec->quantity;
                        }
                    }
                    
                    // Форсираме производствения етап
                    $details[] = $nRec;
                    
                    if ($nRec->type == 'input') {
                        $hasInputMats = true;
                    }
                }
            }
            
            // Ако някой от артикулите липсва, не създаваме нищо
            if (countR($error)) {
                $string = implode(',', $error);
                $error = "Базовата рецепта не може да бъде създадена|*, |защото материалите с кодове|*: <b>{$string}</b> |не са въведени в системата|*";
                expect(false, $error);
                
                return;
            }
            
            // Ако няма вложими материали, не създаваме рецепта
            if ($hasInputMats === false) {
                $error = 'Базовата рецепта не може да бъде създадена|*, |защото не са подадени вложими материали|*, |а само отпадаци|*';
                expect(false, $error);
                
                return;
            }
            
            // Ако има стара активна дефолтна рецепта със същите данни не правим нищо
            if ($oldRec = static::fetch("#productId = {$pRec->id} AND #state = 'active'  AND #hash IS NOT NULL")) {
                
                // Ако дефолтната рецепта е различна от текущата дефолтна затваряме я
                if ($oldRec->hash != $hash) {
                    $oldRec->state = 'closed';
                    static::save($oldRec);
                }
            }
            
            // Създаваме нова дефолтна рецепта от системния потребител
            core_Users::forceSystemUser();
            $bomId = static::createNewDraft($pRec->id, $bomInfo['quantity'], $pRec->containerId, $details, 'Автоматична рецепта', $bomInfo['expenses']);
            $bomRec = static::fetchRec($bomId);
            $bomRec->state = 'active';
            $bomRec->hash = $hash;
            static::save($bomRec);
            core_Users::cancelSystemUser();
            doc_Threads::doUpdateThread($bomRec->threadId);
            
            return $bomRec->id;
            
        }
        
        return null;
    }
    
    
    /**
     * Подготвяне на рецептите за един артикул
     *
     * @param stdClass $data
     *
     * @return void
     */
    public function prepareBoms(&$data)
    {
        $data->rows = array();

        // Намираме неоттеглените задания
        $query = cat_Boms::getQuery();
        $query->XPR('orderByState', 'int', "(CASE #state WHEN 'active' THEN 1 WHEN 'closed' THEN 2 ELSE 3 END)");
        
        $query->where("#productId = {$data->masterId}");
        $query->where("#state != 'rejected'");
        $query->orderBy('orderByState', 'ASC');

        $data->Pager = cls::get('core_Pager', array('itemsPerPage' => 20));
        $data->Pager->setPageVar('cat_Products', $data->masterId, 'cat_Boms');
        $data->Pager->setLimit($query);

        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = $this->recToVerbal($rec);
        }
        
        $masterInfo = cat_Products::getProductInfo($data->masterId);
        if (!isset($masterInfo->meta['canManifacture'])) {
            $data->notManifacturable = true;
        }
        
        if (!haveRole('ceo,sales,cat,planning') || ($data->notManifacturable === true && !countR($data->rows))) {
            $data->hide = true;
            
            return;
        }
        
        $data->TabCaption = 'Рецепти';
        $data->Tab = 'top';
        
        // Проверяваме можем ли да добавяме нови рецепти
        if ($this->haveRightFor('add', (object) array('productId' => $data->masterId, 'originId' => $data->masterData->rec->containerId))) {
            $data->addUrl1 = array('cat_Boms', 'add', 'productId' => $data->masterData->rec->id, 'originId' => $data->masterData->rec->containerId, 'type' => 'sales', 'ret_url' => true);
            $data->addUrl2 = array('cat_Boms', 'add', 'productId' => $data->masterData->rec->id, 'originId' => $data->masterData->rec->containerId, 'type' => 'instant', 'ret_url' => true);
        }
    }
    
    
    /**
     * Рендиране на рецептите на един артикул
     *
     * @param stdClass $data
     * @return core_ET
     */
    public function renderBoms($data)
    {
        if ($data->hide === true) {
            
            return;
        }
        
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        if(!$data->fromConvertable){
            $title = tr('Технологични рецепти');
            $tpl->append($title, 'title');
        }
        
        $data->listFields = arr::make('title=Рецепта,type=Вид,quantity=Количество,createdBy=От||By,createdOn=На');
        $table = cls::get('core_TableView', array('mvc' => $this));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $details = $table->get($data->rows, $data->listFields);
        if ($data->Pager) {
            $details->append($data->Pager->getHtml());
        }

        // Ако артикула не е производим, показваме в детайла
        if ($data->notManifacturable === true) {
            $tpl->append(" <span class='red small'>(" . tr('Артикулът не е производим') . ')</span>', 'title');
            $tpl->append('state-rejected', 'TAB_STATE');
        } elseif($data->fromConvertable && $data->masterData->rec->canConvert != 'yes'){
            $tpl->replace(" <span class='red small'>(" . tr('Артикулът не е вложим') . ')</span>', 'title');
            $tpl->append('state-rejected', 'TAB_STATE');
        }
        $tpl->append($details, 'content');
        
        if(!Mode::isReadOnly()){
            if (isset($data->addUrl1)) {
                $addBtn = ht::createBtn('Търговска', $data->addUrl1, false, false, "ef_icon={$this->singleIcon},title=Добавяне на нова търговска технологична рецепта");
                $tpl->append($addBtn, 'toolbar');
            }
            
            if (isset($data->addUrl2)) {
                $addBtn = ht::createBtn('Моментна', $data->addUrl2, false, false, "ef_icon={$this->singleIcon},title=Добавяне на нова моментна технологична рецепта");
                $tpl->append($addBtn, 'toolbar');
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Клонира и разпъва рецептата на един артикул към друг
     *
     * @param int $fromProductId
     * @param int $toProductId
     * 
     * @return int|null
     */
    public static function cloneBom($fromProductId, $toProductId)
    {
        $toProductRec = cat_Products::fetchRec($toProductId);
        $activeBom = cat_Products::getLastActiveBom($fromProductId, 'sales');
        
        // Ако има рецепта за клониране
        if ($activeBom) {
            $nRec = clone $activeBom;
            $nRec->folderId = $toProductRec->folderId;
            $nRec->threadId = $toProductRec->threadId;
            $nRec->productId = $toProductRec->id;
            $nRec->originId = $toProductRec->containerId;
            $nRec->state = 'draft';
            foreach (array('id', 'modifiedOn', 'modifiedBy', 'createdOn', 'createdBy', 'containerId') as $fld) {
                unset($nRec->{$fld});
            }
            
            if (static::save($nRec)) {
                cls::get('cat_Boms')->invoke('AfterSaveCloneRec', array($activeBom, &$nRec));
            } else {
                core_Statuses::newStatus('|Грешка при клониране на запис', 'warning');
            }
            
            return $nRec->id;
        }
        
        return null;
    }
    
    
    /**
     * Връща допустимите параметри за формулите
     *
     * @param int $productId - запис
     * @return array $res    - допустимите параметри с техните стойностти
     */
    public static function getProductParams($productId)
    {
        $params = cat_Products::getParams($productId);
        $params = cond_type_Formula::tryToCalcAllFormulas($params);
        $res = cat_Params::getFormulaParamMap($params);

        if (countR($res)) return array($productId => $res);
        
        return $res;
    }
    
    
    /**
     * Пушва параметри в началото на масива
     *
     * @param array $array
     * @param array $params
     *
     * @return void
     */
    public static function pushParams(&$array, $params)
    {
        if (is_array($params) && countR($params)) {
            $array = $params + $array;
        }
    }
    
    
    /**
     * Маха параметър от масива
     *
     * @param array  $array
     * @param string $key
     *
     * @return void
     */
    public static function popParams(&$array, $key)
    {
        unset($array[$key]);
    }
    
    
    /**
     * Връща контекста на параметрите
     *
     * @param array $params
     * @return array $scope
     */
    public static function getScope($params)
    {
        $scope = array();
        
        if (is_array($params)) {
            foreach ($params as $arr) {
                if (is_array($arr)) {
                    foreach ($arr as $k => $v) {
                        if (!isset($scope[$k])) {
                            $scope[$k] = $v;
                        }
                    }
                }
            }
        }
        
        return $scope;
    }


    /**
     * Връща цената на материала за рецептата
     *
     * @param string       $type        - типа за която рецепта ще проверяваме
     * @param int          $productId   - ид на артикула
     * @param float        $quantity    - количество за което искаме цената
     * @param datetime     $date        - към коя дата
     * @param int          $priceListId - по кой ценоразпис
     *
     * @return float|FALSE $price   - намерената цена или FALSE ако няма
     */
    private static function getPriceForBom($type, $productId, $quantity, $date, $priceListId)
    {
        // Ако търсим цената за търговска рецепта
        if ($type == 'sales') {
            
            // Първо проверяваме имали цена по политиката
            $price = price_ListRules::getPrice($priceListId, $productId, null, $date);
            $pRec = cat_Products::fetch($productId, 'generic,canStore');
            if (!isset($price)) {
                
                // Ако няма, търсим по последната търговска рецепта, ако има
                if ($salesBom = cat_Products::getLastActiveBom($productId, 'sales')) {
                    $price = static::getBomPrice($salesBom, $quantity, 0, 0, $date, $priceListId);
                }
            }
            
            if (!isset($price)) {
                if($pRec->generic == 'yes'){
                    $price = planning_GenericMapper::getAvgPriceEquivalentProducts($productId, $date);
                }
            }
            
            // Ако и по рецепта няма тогава да гледа по складова
            if (!isset($price)) {
                
                // Ако артикула е складируем търсим средната му цена във всички складове, иначе търсим в незавършеното производство
                if ($pRec->canStore == 'yes') {
                    $price = cat_Products::getWacAmountInStore(1, $productId, $date);
                } else {
                    $price = planning_GenericMapper::getWacAmountInProduction(1, $productId, $date);
                }
                
                if (isset($price) && $price < 0) {
                    $price = null;
                }
            }
        } else {
            // Ако артикула е складируем търсим средната му цена във всички складове, иначе търсим в незавършеното производство
            $pRec = cat_Products::fetch($productId, 'generic,canStore');
            if ($pRec->canStore == 'yes') {
                $price = cat_Products::getWacAmountInStore(1, $productId, $date);
            } else {
                $price = planning_GenericMapper::getWacAmountInProduction(1, $productId, $date);
            }
            
            if (!isset($price)) {
                // Ако няма такава, търсим по последната работна рецепта, ако има
                if ($prodBom = cat_Products::getLastActiveBom($productId, 'production,instant,sales')) {
                    $price = static::getBomPrice($prodBom, $quantity, 0, 0, $date, $priceListId);
                }
            }
            
            if (!isset($price)) {
                if ($pRec->generic == 'yes') {
                    $price = planning_GenericMapper::getAvgPriceEquivalentProducts($productId, $date);
                }
            }
            
            // В краен случай взимаме мениджърската себестойност
            if (!isset($price)) {
                $price = price_ListRules::getPrice($priceListId, $productId, null, $date);
            }
        }
        
        // Ако няма цена връщаме FALSE
        if (!isset($price)) return false;
        if (!$quantity) return false;
        
        // Умножаваме цената по количеството
        if($quantity != cat_BomDetails::CALC_ERROR){
            $price *= $quantity;
        } else {
            return false;
        }

        // Връщаме намерената цена
        return $price;
    }
    
    
    /**
     * Изчислява сумата на реда и я записва
     *
     * @param stdClass $rec           - Записа на реда
     * @param array    $params        - Параметрите за реда
     * @param float    $t             - Тиража
     * @param float    $q             - Изчислимото количество
     * @param datetime     $date          - Към коя дата
     * @param int      $priceListId   - ид на ценоразпис
     * @param bool     $savePriceCost - дали да кешираме изчислената цена
     * @param array    $materials     - масив със сумираните вложени материали
     *
     * @return float|FALSE $price   - намерената цена или FALSE ако не можем
     */
    private static function getRowCost($rec, $params, $t, $q, $date, $priceListId, $savePriceCost = false, &$materials = array())
    {
        // Изчисляваме количеството ако можем
        $rowParams = self::getProductParams($rec->resourceId);
        self::pushParams($params, $rowParams);
        $doTouchRec = !(($rec->state == 'rejected'));
        
        $scope = self::getScope($params);
        $rQuantity = cat_BomDetails::calcExpr($rec->propQuantity, $scope);
        if ($rQuantity != cat_BomDetails::CALC_ERROR) {
            
            // Искаме количеството да е за единица, не за опаковка
            $rQuantity *= $rec->quantityInPack;
        }

        // Сумираме какви количества ще вложим към материалите
        if ($rec->type != 'stage') {
            $index = "{$rec->resourceId}|{$rec->type}";
            if (!isset($materials[$index])) {
                $materials[$index] = (object) array('productId' => $rec->resourceId,
                    'packagingId' => $rec->packagingId,
                    'quantityInPack' => $rec->quantityInPack,
                    'type' => $rec->type,
                    'genericProductId' => planning_GenericProductPerDocuments::getRec('cat_BomDetails', $rec->id),
                );

                if ($rQuantity != cat_BomDetails::CALC_ERROR) {
                    $materials[$index]->propQuantity = $t * $rQuantity;
                } else {
                    $materials[$index]->propQuantity = $rQuantity;
                }
            } else {
                $d = &$materials[$index];
                if ($rQuantity != cat_BomDetails::CALC_ERROR) {
                    $d->propQuantity += $t * $rQuantity;
                } else {
                    $d->propQuantity = $rQuantity;
                }
            }
        }
        
        // Какъв е типа на рецептата
        $type = static::fetchField($rec->bomId, 'type');
        
        // Ако реда не е етап а е материал или отпадък
        if ($rec->type != 'stage') {
            if (in_array($rec->type, array('pop', 'subProduct'))) {
                
                // Ако е отпадък или субпродукт търсим твърдо мениджърската себестойност
                $price = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $rec->resourceId, $rec->packagingId, $date);
                if (!isset($price)) {
                    $price = false;
                } else {
                    $price *= $q * $rQuantity;
                }
            } else {
                if ($rQuantity != cat_BomDetails::CALC_ERROR) {
                    $q1 = $q * $rQuantity;
                } else {
                    $q1 = $rQuantity;
                }

                // Ако не е търсим най-подходящата цена за рецептата
                $price = self::getPriceForBom($type, $rec->resourceId, $q1, $date, $priceListId);
            }
            
            // Записваме намерената цена
            if ($savePriceCost === true) {
                $primeCost = ($price === false) ? null : $price;
                $params1 = $scope;
                
                // Ъпдейтваме кешираните стойност и параметри само при промяна
                if (trim($rec->primeCost) != trim($primeCost) || serialize($rec->params) != serialize($params1)) {
                    $rec->primeCost = $primeCost;
                    $rec->params = $params1;
                    
                    Mode::push("touchRec{$rec->bomId}", $doTouchRec);
                    cls::get('cat_BomDetails')->save_($rec, 'primeCost,params');
                    Mode::pop("touchRec{$rec->bomId}");
                }
            }
        } else {
            $price = null;
            if (isset($rec->coefficient)) {
                if ($rQuantity != cat_BomDetails::CALC_ERROR) {
                    $rQuantity /= $rec->coefficient;
                } else {
                    $rQuantity = 0;
                }
            }
            
            // Ако е етап, новите параметри са неговите данни + количестото му по тиража
            $flag = false;
            if (!array_key_exists($rec->resourceId, $params)) {
                $empty = array($rec->resourceId => array());
                self::pushParams($params, $empty);
                $flag = true;
            }
            $params[$rec->resourceId]['$T'] = ($rQuantity == cat_BomDetails::CALC_ERROR) ? $rQuantity : $t * $rQuantity;
            
            // Намираме кои редове са му детайли
            $query = cat_BomDetails::getQuery();
            $query->where("#parentId = {$rec->id} AND #bomId = {$rec->bomId}");
            $query->EXT('state', 'cat_Boms', 'externalName=state,externalKey=bomId');
            
            // За всеки детайл
            while ($dRec = $query->fetch()) {

                // Опитваме се да намерим цената му
                if($rQuantity != cat_BomDetails::CALC_ERROR){
                    $dRec->primeCost = self::getRowCost($dRec, $params, $t * $rQuantity, $q * $rQuantity, $date, $priceListId, $savePriceCost, $materials);
                } else {
                    $dRec->primeCost = null;

                    if($dRec->type != 'stage'){
                        $index = "{$dRec->resourceId}|{$dRec->type}";
                        if (!isset($materials[$index])) {
                            $materials[$index] = (object) array('productId' => $dRec->resourceId,
                                'packagingId' => $dRec->packagingId,
                                'quantityInPack' => $dRec->quantityInPack,
                                'type' => $dRec->type,
                                'genericProductId' => planning_GenericProductPerDocuments::getRec('cat_BomDetails', $dRec->id),
                            );
                            $materials[$index]->propQuantity = 0;
                        } else {
                            $d = &$materials[$index];
                            $d->propQuantity += 0;
                        }
                    }
                }

                // Ако няма цена връщаме FALSE
                if ($dRec->primeCost === false) {
                    $price = false;
                }
                
                // Добавяме цената на реда към цената на етапа
                if ($dRec->primeCost !== false && $price !== false) {
                    $price += $dRec->primeCost;
                }
            }
            
            // Попваме данните, за да кешираме оригиналните
            if ($flag === true) {
                self::popParams($params, $rec->resourceId);
            }
            
            // Кешираме параметрите само при нужда
            if ($savePriceCost === true) {
                $scope = static::getScope($params);
                $params1 = $scope;
                
                if (serialize($rec->params) != serialize($params1)) {
                    $rec->params = $params1;
                    
                    Mode::push("touchRec{$rec->bomId}", $doTouchRec);
                    cls::get('cat_BomDetails')->save_($rec, 'params');
                    Mode::pop("touchRec{$rec->bomId}");
                }
            }
        }
        
        // Ако реда е отпадък то ще извадим цената му от себестойността
        if (in_array($rec->type, array('pop', 'subProduct')) && $price !== false) {
            $price *= -1;
        }
        
        self::popParams($params, $rec->resourceId);

        // Връщаме намерената цена
        return $price;
    }
    
    
    /**
     * Връща цената на артикул по рецепта
     *
     * @param int   $id          - ид на рецепта
     * @param float $quantity    - количеството
     * @param float $minDelta    - минималната търговска отстъпка
     * @param float $maxDelta    - максималната търговска надценка
     * @param datetime  $date        - към коя дата
     * @param int   $priceListId - ид на ценоразпис
     * @param array $materials   - какви материали са вложени
     *
     * @return FALSE|float - намерената цена или FALSE ако няма
     */
    public static function getBomPrice($id, $quantity, $minDelta, $maxDelta, $date, $priceListId, &$materials = array())
    {
        $primeCost1 = $primeCost2 = null;
        
        // Трябва да има такъв запис
        expect($rec = static::fetchRec($id));

        if(!array_key_exists($rec->id, static::$calcPriceCounter)){
            static::$calcPriceCounter[$rec->id] = 1;
        } else {
            static::$calcPriceCounter[$rec->id]++;
        }

        // Ако случайно изчисляването на рецептата е зациклило да се прекъсне да не върти вечно
        if(static::$calcPriceCounter[$rec->id] > 500){

            throw new core_exception_Expect("Има проблем при изчисляването на рецептата:|* #Bom{$rec->id}", 'Несъответствие');
        }

        $savePrimeCost = false;
        $bomQuantity = ($rec->quantityForPrice) ? $rec->quantityForPrice : $rec->quantity;
        
        if ($minDelta === 0 && $maxDelta === 0 && $priceListId == price_ListRules::PRICE_LIST_COST && $bomQuantity == $quantity) {
            $savePrimeCost = true;
        }
        
        if (!$rec->quantity) {
            $rec->quantity = 1;
        }
        
        $quantity /= $rec->quantity;

        // Количеството за което изчисляваме е 1-ца
        $q = 1;
        
        // Изчисляваме двата тиража (минимум и максимум)
        $t1 = $quantity / self::PRICE_COEFFICIENT;
        $t2 = $quantity * self::PRICE_COEFFICIENT;
        
        // Намираме всички детайли от първи етап
        $query = cat_BomDetails::getQuery();
        $query->where("#bomId = {$rec->id}");
        $query->where('#parentId IS NULL');
        $query->EXT('state', 'cat_Boms', 'externalName=state,externalKey=bomId');
        $details = $query->fetchAll();
        
        // Ако изчисляваме цената на рецептата по себестойност, ще кешираме изчислените цени на редовете
        $canCalcPrimeCost = true;
        
        // За всеки от тях
        if (is_array($details)) {
            foreach ($details as $dRec) {
                
                // Параметрите са на продукта на рецептата
                $params = array();
                $pushParams = static::getProductParams($rec->productId);
                $pushParams[$rec->productId]['$T'] = $quantity;
                self::pushParams($params, $pushParams);

                // Опитваме се да намерим себестойността за основното количество
                $rowCost1 = self::getRowCost($dRec, $params, $quantity, $q, $date, $priceListId, $savePrimeCost, $materials);

                // Ако няма връщаме FALSE
                if ($rowCost1 === false) {
                    $canCalcPrimeCost = false;
                }
                
                // Ако мин и макс делта са различни изчисляваме редовете за двата тиража
                if ($minDelta != $maxDelta) {
                    $params[$rec->productId]['$T'] = $t1;
                    $rowCost1 = self::getRowCost($dRec, $params, $t1, $q, $date, $priceListId);
                    
                    if ($rowCost1 === false) {
                        $canCalcPrimeCost = false;
                    }
                    $primeCost1 += $rowCost1;
                    
                    $params[$rec->productId]['$T'] = $t2;
                    $rowCost2 = self::getRowCost($dRec, $params, $t2, $q, $date, $priceListId);
                    if ($rowCost2 === false) {
                        $canCalcPrimeCost = false;
                    }
                    $primeCost2 += $rowCost2;
                } else {
                    if ($rowCost1 === false) {
                        $canCalcPrimeCost = false;
                    }
                    $primeCost1 += $rowCost1;
                }
            }
        }
        
        if ($canCalcPrimeCost === false) {
            
            return;
        }
        
        // Ако са равни връщаме себестойността
        if ($minDelta == $maxDelta) {
            $price = $primeCost1 * (1 + $minDelta);
        } else {
            $primeCost1 *= $t1;
            $primeCost2 *= $t2;
            
            // Изчисляваме началната и пропорционалната сума
            $basePrice = ($primeCost2 * $t1 - $primeCost1 * $t2) / ($t1 - $t2);
            $propPrice = ($primeCost1 - $primeCost2) / ($t1 - $t2);
            
            // Прилагаме и максималната надценка и минималната отстъпка
            $price = $basePrice * (1 + $maxDelta) / $quantity + $propPrice * (1 + $minDelta);
        }
        
        $price /= $rec->quantity;
        
        // Връщаме намерената цена
        return $price;
    }
    
    
    /**
     * Връща иконата за сметката
     */
    public function getIcon($id)
    {
        $rec = $this->fetch($id);
        $icon = ($rec->type == 'sales') ? $this->singleIcon : $this->singleProductionBomIcon;
        
        return $icon;
    }
    
    
    /**
     * След подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter(core_Mvc $mvc, $data)
    {
        $data->listFilter->setField('type', 'input=none');
        $data->listFilter->FLD('typeF', 'enum(all=Всички,sales=Търговски,instant=Моментни,production=Работни)','caption=Вид,silent');
        $data->listFilter->setDefault('typeF', 'all');
        $data->listFilter->showFields .= ',typeF';
        
        $data->listFilter->input();
        if ($filter = $data->listFilter->rec) {
            if ($filter->typeF != 'all') {
                $data->query->where("#type = '{$filter->typeF}'");
            }
        }
    }

    /**
     * След рендиране на еденичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        jquery_Jquery::run($tpl,"toggleDisplayBomStepDetails();", TRUE);
        jquery_Jquery::run($tpl,"openBoomRows();", TRUE);
        jquery_Jquery::runAfterAjax($tpl, 'toggleDisplayBomStepDetails');
        jquery_Jquery::runAfterAjax($tpl, 'openBoomRows');
    }


    /**
     * Опит за връщане на масив със задачи за производство от рецептата
     *
     * @param mixed $id       - ид на рецепта
     * @param float $quantity - количество
     *
     * @return array $tasks - масив със задачи за производство за генерирането на всеки етап
     */
    public static function getTasksFromBom($id, $quantity = 1)
    {
        expect($rec = self::fetchRec($id));
        $pName = cat_Products::getTitleById($rec->productId, false);
        $Details = cls::get('cat_BomDetails');
        $productStepClassId = planning_interface_StepProductDriver::getClassId();

        // Отделяме етапите за всеки етап ще генерираме отделна задача в която той е за произвеждане
        // А неговите подетапи са за влагане/отпадък
        $onlySteps = $allStages = array();
        $query = cat_BomDetails::getQuery();
        $query->EXT('innerClass', 'cat_Products', "externalName=innerClass,externalKey=resourceId");
        $query->where("#bomId = {$rec->id}");
        $query->where("#type = 'stage' AND #innerClass = {$productStepClassId}");
        $query->orderBy('parentId,position', 'ASC');
        while($dRec1 = $query->fetch()){
            $allStages[$dRec1->id] = $dRec1;
            if($dRec1->innerClass == $productStepClassId){
                $onlySteps[$dRec1->id] = $dRec1;
            }
        }

        // За всеки етап намираме подетапите му
        $tasks = array();
        foreach ($allStages as $dRec) {
            $dRec->params['$T'] = $quantity;
            $quantityP = cat_BomDetails::calcExpr($dRec->propQuantity, $dRec->params);
            if ($quantityP == cat_BomDetails::CALC_ERROR) {
                $quantityP = 0;
            }
            
            $parent = $dRec->parentId;
            while ($parent && ($pRec = cat_BomDetails::fetch($parent))) {
                $q = cat_BomDetails::calcExpr($pRec->propQuantity, $pRec->params);
                if ($q == cat_BomDetails::CALC_ERROR) {
                    $q = 0;
                }
                $quantityP *= $q;
                $parent = $pRec->parentId;
            }

            $quantityP = (($quantityP) / $rec->quantity) * $quantity;
            $q1 = round($quantityP * $dRec->quantityInPack, 5);

            $pRec = cat_Products::fetch($dRec->resourceId);
            $obj = (object) array('title' => cat_Products::getTitleById($dRec->resourceId, false),
                                  'plannedQuantity' => $q1,
                                  'measureId' => $pRec->measureId,
                                  'productId' => $dRec->resourceId,
                                  'packagingId' => $dRec->packagingId,
                                  'quantityInPack' => $dRec->quantityInPack,
                                  'storeId' => $dRec->storeIn,
                                  'centerId' => $dRec->centerId,
                                  'fixedAssets' => $dRec->fixedAssets,
                                  'employees' => $dRec->employees,
                                  'indTime' => $dRec->norm,
                                  '_inputPreviousSteps' => (($dRec->inputPreviousSteps == 'auto') ? planning_Setup::get('INPUT_PREVIOUS_BOM_STEP') : $dRec->inputPreviousSteps),
                                  '_dId' => $dRec->id,
                                  '_parentId' => $dRec->parentId,
                                  '_position' => $dRec->position,
								  'subTitle' => $dRec->subTitle,
                                  'description' => $dRec->description,
                                  'labelPackagingId' => $dRec->labelPackagingId,
                                  'labelQuantityInPack' => $dRec->labelQuantityInPack,
                                  'labelType' => $dRec->labelType,
                                  'labelTemplate' => $dRec->labelTemplate,
                                  'showadditionalUom' => ($pRec->planning_Steps_calcWeightMode == 'auto') ? planning_Setup::get('TASK_WEIGHT_MODE') : $pRec->planning_Steps_calcWeightMode,
                                  'params' => array(),
                                  'wasteProductId' => ($dRec->wasteProductId) ? $dRec->wasteProductId : $pRec->planning_Steps_wasteProductId,
                                  'wasteStart' => ($dRec->wasteStart) ? $dRec->wasteStart : $pRec->planning_Steps_wasteStart,
                                  'wastePercent' => ($dRec->wastePercent) ? $dRec->wastePercent : $pRec->planning_Steps_wastePercent,
                                  'products' => array('input' => array(), 'waste' => array(), 'production' => array()));

            $pQuery = cat_products_Params::getQuery();
            $pQuery->where("#classId = '{$Details->getClassId()}' AND #productId = {$dRec->id}");
            $pQuery->show('paramId,paramValue');
            while($pRec = $pQuery->fetch()){
                $obj->params[$pRec->paramId] = $pRec->paramValue;
            }

            // Добавяме директните наследници на етапа като материали за влагане/отпадък
            $query2 = cat_BomDetails::getQuery();
            $query2->where("#parentId = {$dRec->id}");
            $query2->EXT('innerClass', 'cat_Products', "externalName=innerClass,externalKey=resourceId");
            $stageChildren = $query2->fetchAll();

            foreach ($stageChildren as $cRec){
                if($cRec->innerClass != $productStepClassId){
                    $quantityS = cat_BomDetails::calcExpr($cRec->propQuantity, $cRec->params);
                    if ($quantityS == cat_BomDetails::CALC_ERROR) {
                        $quantityS = 0;
                    }

                    $place = ($cRec->type == 'pop') ? 'waste' : ($cRec->type == 'subProduct' ? 'production': 'input');
                    $obj->products[$place][] = array('productName' => cat_Products::getTitleById($cRec->resourceId), 'productId' => $cRec->resourceId, 'packagingId' => $cRec->packagingId, 'packQuantity' => $quantityS, 'quantityInPack' => $cRec->quantityInPack);
                }
            }

            // Събираме задачите
            $tasks[] = $obj;
        }

        foreach ($tasks as $k => $defTask){
            $siblingSteps = array_filter($onlySteps, function($a) use ($defTask) { return $a->parentId == $defTask->_parentId && $a->position < $defTask->_position;});
            $childrenSteps = array_filter($onlySteps, function($a) use ($defTask) { return $a->parentId == $defTask->_dId;});

            foreach (array($siblingSteps, $childrenSteps) as $arr){
                if(countR($arr)){
                    arr::sortObjects($arr, 'position', 'DESC');
                    $foundStepId = key($arr);
                    if($foundStepId){
                        $foundStepArr = array_filter($tasks, function($b) use ($foundStepId) { return $b->_dId == $foundStepId;});
                        $foundStepTask = $foundStepArr[key($foundStepArr)];
                        if($foundStepTask){
                            if($defTask->_inputPreviousSteps == 'yes'){
                                $defTask->products['input'][] = array('productName' => cat_Products::getTitleById($foundStepTask->productId), 'productId' => $foundStepTask->productId, 'packagingId' => $foundStepTask->packagingId, 'packQuantity' => $foundStepTask->plannedQuantity, 'quantityInPack' => $foundStepTask->quantityInPack, 'isPrevStep' => true);
                            }
                        }
                    }
                }
            }
        }

        // Връщаме масива с готовите задачи
        return $tasks;
    }
    
    
    /**
     * Проверка след изпращането на формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = &$form->rec;
        if ($form->isSubmitted()) {
            
            // Проверка на к-то
            $measureId = cat_Products::fetchField($rec->productId, 'measureId');
            $warning = '';
            if (!deals_Helper::checkQuantity($measureId, $rec->quantity, $warning)) {
                $form->setWarning('quantity', $warning);
            }
            
            $firstDocument = doc_Containers::getDocument($rec->originId);
            if (empty($rec->id) && $firstDocument->isInstanceOf('planning_Jobs')) {
                
                // Ако има търговска рецепта за друго количество, при създаване на работната
                // се добавя предупреждение, ако има разминаване в к-та
                $bRec = cat_Products::getLastActiveBom($rec->productId, 'sales');
                if (!empty($bRec)) {
                    if ($bRec->quantity != $rec->quantity) {
                        $q1 = cls::get('type_Double', array('params' => array('smartRound' => true)))->toVerbal($bRec->quantity);
                        $q2 = cls::get('type_Double', array('params' => array('smartRound' => true)))->toVerbal($rec->quantity);
                        $uom = cat_Uom::getShortName(cat_Products::fetchField($rec->productId, 'measureId'));
                        $handle = cat_Boms::getLink($bRec->id, 0);
                        
                        $form->setWarning('quantity', "|Данните от търговската рецепта|* {$handle} |няма да се прехвърлят защото тя е за|* <b>{$q1} {$uom}</b>, |а работната рецепта е за|* <b>{$q2} {$uom}</b>");
                    }
                }
            }
        }
    }
    
    
    /**
     * Връща складируемите материали по-рецепта, ако е подаден склад се
     * отсяват само ненулевите количества
     *
     * @param int        $bomId                   - ид на рецепта
     * @param double     $quantity                - к-во в рецептата
     * @param int|null   $storeId                 - ид на склад (или null) за всички
     * @param bool       $onlyStorable            - дали да са само складируемите
     * @param array|null $ignoreReservedByDocsArr - запазените к-ва от кои документи да се игнорират
     *
     * @return array $res
     *               ['productId']      - ид на артикул
     *               ['packagingId']    - ид на опаковка
     *               ['quantity']       - к-во
     *               ['quantityInPack'] - к-во в опаковка
     */
    public static function getBomMaterials($bomId, $quantity, $storeId = null, $onlyStorable = true, $ignoreReservedByDocsArr = array())
    {
        $res = array();
        $bomInfo = cat_Boms::getResourceInfo($bomId, $quantity, dt::now());

        if (!countR($bomInfo['resources'])) return $res;

        foreach ($bomInfo['resources'] as $pRec) {
            $productRec = cat_Products::fetch($pRec->productId, 'canStore,generic');
            if($pRec->type != 'input') continue;
            if($onlyStorable && $productRec->canStore != 'yes') continue;

            // Ако има склад се отсяват артикулите, които имат нулева наличност
            if (isset($storeId) && $productRec->canStore == 'yes') {

                // Ако артикула или някой от заместителите му са налични в склада остава
                $productArr = array_keys(planning_GenericMapper::getEquivalentProducts($pRec->productId, $pRec->genericProductId, true));
                if(!countR($productArr)){
                    $productArr = array($pRec->productId);
                }

                // Ако има подадени документи от които да се игнорират запазените/очакваните к-ва да се извлекат
                $reservedByJobs = array();
                if(countR($ignoreReservedByDocsArr)){
                    $sQuery = store_StockPlanning::getQuery();
                    $sQuery->where("#storeId = {$storeId}");
                    foreach ($ignoreReservedByDocsArr as $ignoreArr){
                        $sQuery->where("#sourceClassId = {$ignoreArr[0]} AND #sourceId = {$ignoreArr[1]}");
                    }
                    while($sRec = $sQuery->fetch()) {
                        $reservedByJobs[$sRec->productId] = $sRec;
                    }
                }

                // Ако разполагаемото е очакваното - няма да се върне
                $quantity = 0;
                array_walk($productArr, function($pId) use (&$quantity, $storeId, $reservedByJobs) {
                    $quantity += store_Products::getQuantities($pId, $storeId)->free;
                    if(array_key_exists($pId, $reservedByJobs)){
                        $quantity += $reservedByJobs[$pId]->quantityOut;
                        $quantity -= $reservedByJobs[$pId]->quantityIn;
                    }
                });

                $found = round($quantity / $pRec->quantityInPack, 6);

                if ($found < $pRec->propQuantity) continue;
            }

            $r = (object) array('productId' => $pRec->productId,
                                'packagingId' => $pRec->packagingId,
                                'quantity' => $pRec->propQuantity,
                                'canStore' => $productRec->canStore,
                                'quantityInPack' => $pRec->quantityInPack);
            if(isset($pRec->genericProductId)){
                $r->genericProductId = $pRec->genericProductId;
            }
            $res[] = $r;
        }

        return $res;
    }


    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $rec = static::fetchRec($rec);
        $title = static::getHandle($rec);
        if(!empty($rec->title)){
            $title .= "/" . static::getVerbal($rec, 'title');
        }
        $title .= "/" . cat_Products::getTitleById($rec->productId);
        $title = str::limitLen($title, 94);

        return $title;
    }


    /**
     * Дали рецептата е ОК - тоест няма да предизвика рекурсивно зацикляне
     *
     * @param $rec
     * @return bool
     */
    private function isOk($rec)
    {
        $Detail = cls::get('cat_BomDetails');

        // Проверка дали активирането на рецептата ще предизвика зацикляне
        $dQuery = $Detail->getQuery();
        $dQuery->where("#bomId = {$rec->id}");
        while($dRec = $dQuery->fetch()){
            $notAllowed = array();
            $Detail->findNotAllowedProducts($dRec->resourceId, $rec->productId, $notAllowed);

            if (isset($notAllowed[$dRec->resourceId])) return false;
        }

        return true;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    protected static function on_BeforeChangeState($mvc, &$rec, $state)
    {
        if($state == 'active' && !$mvc->isOk($rec)){
            followRetUrl(null, '|Рецептата не може да се активира, защото артикулът се съдържа в рецептата на някой от вложените в него|*!', 'error');
        }
    }


    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if ($mvc->haveRightFor('regenerate', $data->rec)) {
            $data->toolbar->addBtn('Подновяване', array($mvc, 'regenerate', $data->rec->id, 'ret_url' => true), 'title=Създаване на нова подновена рецепта,ef_icon=img/16/arrow_refresh.png,row=2');
        }

        if ($mvc->haveRightFor('syncparams', $data->rec)) {
            $data->toolbar->addBtn('Синх. параметри', array($mvc, 'syncparams', $data->rec->id, 'ret_url' => true), 'title=Обновяване на параметрите на крайния артикул на база материалите в рецептата,ef_icon=img/16/arrow_refresh.png');
        }
    }


    /**
     * Екшън за регенериране на рецептата
     */
    public function act_Regenerate()
    {
        $this->requireRightFor('regenerate');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('regenerate', $rec);

        $form = cls::get('core_Form');
        $form->info = "<div class='richtext-info-no-image'>Ще се създаде нова рецепта, където ще са обновени поднивата на етапите</div>";
        $form->title = "Подновяване на детайлите на|* " . cls::get('cat_Boms')->getFormTitleLink($rec->id);
        $form->FNC('select', 'enum(yes=Да,no=Не)', 'caption=Да се регенерерират само при по-нова рецепта->Избор,input');
        $form->setDefault('select', 'yes');
        $form->input();
        if($form->isSubmitted()){
            $selected = ($form->rec->select == 'yes');

            $clone = clone $rec;
            plg_Clone::unsetFieldsNotToClone($this, $clone, $rec);
            unset($clone->id, $clone->changeModifiedOn, $clone->changeModifiedBy, $clone->containerId, $clone->modifiedOn, $clone->modifiedBy, $clone->activatedOn, $clone->activatedBy, $clone->clonedFromId, $clone->createdBy, $clone->createdOn);
            $clone->state = 'draft';
            $clone->_regenerate = true;
            $clone->regeneratedFromId = $rec->id;

            $newBomId = $this->save($clone);
            $newBomRec = static::fetch($newBomId);

            $dQuery = cat_BomDetails::getQuery();
            $dQuery->where("#bomId = {$rec->id} AND #parentId IS NULL");
            $dQuery->orderBy('position', 'ASC');
            while($dRec = $dQuery->fetch()){
                $this->regenDetailRec($dRec, $newBomRec, $rec, $selected);
            }

            return new Redirect(array($this, 'single', $newBomRec->id), '|Създадена е нова обновена рецепта|*!');
        }

        // Добавяне на бутони
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        $tpl = $form->renderHtml();

        return $tpl;
    }


    /**
     * Регенерира запис на детайла
     *
     * @param stdClass $dRec - запис за регенериране
     * @param stdClass $newBomRec - нова рецепта
     * @param stdClass $oldBomRec - стара рецепта
     * @param bool $cloneIfDetailsAreNewer - дали да се регенерират само ако рецептата е по-нова
     *
     * @return void
     */
    private function regenDetailRec($dRec, $newBomRec, $oldBomRec, $cloneIfDetailsAreNewer)
    {
        if($dRec->type == 'stage'){
            $Driver = cat_Products::getDriver($dRec->resourceId);
            $productionData = $Driver->getProductionData($dRec->resourceId);
            foreach (array('centerId', 'norm', 'storeIn', 'inputStores', 'fixedAssets', 'employees', 'labelPackagingId', 'labelQuantityInPack', 'labelType', 'labelTemplate') as $productionFld) {
                $defaultValue = is_array($productionData[$productionFld]) ? keylist::fromArray($productionData[$productionFld]) : $productionData[$productionFld];
                $dRec->{$productionFld} = $defaultValue;
            }
        }

        $Details = cls::get('cat_BomDetails');
        $dRec->params = $Details->getProductParamScope($dRec, $newBomRec->productId);
        $originRec = clone $dRec;
        unset($dRec->id, $dRec->modifiedOn, $dRec->modifiedBy, $dRec->createdOn, $dRec->createdBy);
        $dRec->bomId = $newBomRec->id;
        $dRec->coefficient = $newBomRec->quantity;

        Mode::push('dontAutoAddStepDetails', true);
        $Details->save($dRec);
        Mode::pop('dontAutoAddStepDetails');

        if($dRec->type == 'stage'){
            cat_BomDetails::addParamsToStepRec($newBomRec->productId, $dRec);
            $bomOrder = (($newBomRec->type == 'production') ? 'production,instant,sales' : (($newBomRec->type == 'instant') ? 'instant,sales' : 'sales'));
            $activeBom = cat_Products::getLastActiveBom($dRec->resourceId, $bomOrder);

            $dRecs = array();
            if($activeBom){
                if(!$cloneIfDetailsAreNewer || $oldBomRec->lastUpdatedDetailOn <= $activeBom->lastUpdatedDetailOn){
                    $bQuery = cat_BomDetails::getQuery();
                    $bQuery->where("#parentId IS NULL AND #bomId = {$activeBom->id}");
                    $dRecs = $bQuery->fetchAll();
                }
            }

            if(!countR($dRecs)){
                $dQuery = cat_BomDetails::getQuery();
                $dQuery->where("#parentId = {$originRec->id}");
                $dRecs = $dQuery->fetchAll();
            }

            foreach ($dRecs as $bRec){
                $bRec->parentId = $dRec->id;
                $bRec->coefficient = $activeBom->quantity;
                $this->regenDetailRec($bRec, $newBomRec, $oldBomRec, $cloneIfDetailsAreNewer);
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
    public static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
        $resArr = arr::make($resArr);

        $resArr['price'] = array('name' => tr('Себестойност'), 'val' => tr("|*<table class='docHeaderVal'>
                <tr><td style='font-weight:normal'>|Себестойност|*:</td><td>[#primeCost#]</td></tr>
                <!--ET_BEGIN primeCostWithOverheadCost--><tr><td style='font-weight:normal'>|С реж. разходи|*:</td><td>[#primeCostWithOverheadCost#]</td></tr><!--ET_END primeCostWithOverheadCost-->
                <!--ET_BEGIN expenses--><tr><td style='font-weight:normal'>|Режийни разходи|*:</td><td>[#expenses#]</td></tr><!--ET_END expenses-->
                <tr><td style='font-weight:normal' colspan='2'><b>[#isComplete#]</b></td></tr>
                </table>"));

        $resArr['info'] = array('name' => tr('Информация'), 'val' => tr("|*<table class='docHeaderVal'>
                <!--ET_BEGIN showInProduct--><tr><td style='font-weight:normal'>|Показване в артикула|*:</td><td>[#showInProduct#]</td></tr><!--ET_END showInProduct-->
                <tr><td style='font-weight:normal'>|Модифициранe|*:</td><td>[#modifiedOn#]</b> |от|* [#modifiedBy#]</td></tr>
                <!--ET_BEGIN lastUpdatedDetailOn--><tr><td style='font-weight:normal'>|Промяна на детайл|*:</td><td>[#lastUpdatedDetailOn#]</td></tr><!--ET_END lastUpdatedDetailOn-->
                <!--ET_BEGIN prototypeId--><tr><td style='font-weight:normal'>|Базирано на|*:</td><td>[#prototypeId#]</td></tr><!--ET_END prototypeId-->
                <!--ET_BEGIN clonedFromId--><tr><td style='font-weight:normal'>|Клонирано от|*:</td><td>[#clonedFromId#]</td></tr><!--ET_END clonedFromId-->
                <!--ET_BEGIN regeneratedFromId--><tr><td style='font-weight:normal'>|Регенерирано от|*:</td><td>[#regeneratedFromId#]</td></tr><!--ET_END regeneratedFromId-->
                </table>"));
    }


    /**
     * Синхронизиране на параметрите на артикула от рецептата
     */
    public function act_Syncparams()
    {
        $this->requireRightFor('syncparams');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetchRec($id));
        $this->requireRightFor('syncparams', $rec);

        $bomRec = cat_Products::getLastActiveBom($rec->productId, 'production,instant,sales');
        if(is_object($bomRec)){

            $params = array();
            $materials = cat_Boms::getBomMaterials($bomRec, 1);
            $classes = core_Classes::getOptionsByInterface('cat_ParamAggregateIntf');

            foreach ($classes as $classId){
                $Interface = cls::getInterface('cat_ParamAggregateIntf', $classId);
                $paramArr = $Interface->getAggregatedParams($rec->productId, $materials);
                foreach($paramArr as $k => $v){
                    $params[$k] = $v;
                }
            }

            if(countR($params)){
                cat_products_Params::syncParams('cat_Products', $rec->productId, $params);
            }
        }

        followRetUrl(null, '|Параметрите на артикула са синхронизирани от рецептата');
    }
}
