<?php


/**
 * Клас 'store_InventoryNotes'
 *
 * Мениджър за документ за инвентаризация на склад
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_InventoryNotes extends core_Master
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=store_transaction_InventoryNote';
    
    
    /**
     * Заглавие
     */
    public $title = 'Протоколи за инвентаризация';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Ivn';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,store,inventory';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,store,inventory';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,storeMaster,inventory';
    
    
    /**
     * Кой може да създава продажба към отговорника на склада?
     */
    public $canMakesale = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,storeMaster,inventory';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'ceo,store,inventory';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Протокол за инвентаризация';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/invertory.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.8|Логистика';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter, store_Wrapper,plg_Clone,acc_plg_Contable,
                        doc_DocumentPlg,purchase_plg_ExtractPurchasesData,
                        plg_Printing, acc_plg_DocumentSummary, deals_plg_SaveValiorOnActivation, plg_Search,bgerp_plg_Blank';
    
    
    /**
     * Кой има право да клонира?
     */
    public $canClonerec = 'ceo,storeMaster,inventory';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = true;
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'store_InventoryNoteSummary,store_InventoryNoteDetails';
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'store_InventoryNoteSummary';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/InventoryNote/SingleLayout.shtml';
    
    
    /**
     * Файл с шаблон за единичен изглед в мобилен
     */
    public $singleLayoutFileNarrow = 'store/tpl/InventoryNote/SingleLayoutNarrow.shtml';
    
    
    /**
     * Да се забрани ли кеширането на документа
     */
    public $preventCache = true;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior,title=Документ,storeId,folderId,createdOn,createdBy,modifiedOn,modifiedBy';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'storeId,groups,folderId';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'store_InventoryNoteSummary,store_InventoryNoteDetails';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'valior';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, valior,modifiedOn';
    
    
    /**
     * На кой ред в тулбара да се показва бутона за принтиране
     */
    public $printBtnToolbarRow = 1;
    
    
    /**
     * Кой може да занули количествата на всички артикули без установени количества
     */
    public $canFillreport = 'ceo,storeMaster,inventory';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Вальор');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад, mandatory');
        $this->FLD('groups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Групи');
        $this->FLD('expandGroups', 'enum(yes=Да,no=Не)', 'caption=Подгрупи,columns=2,single=none,notNull,value=no');
        $this->FLD('hideOthers', 'enum(yes=Да,no=Не)', 'caption=Показване само на избраните групи->Избор, mandatory, notNull,value=no,maxRadio=2');
        $this->FLD('cache', 'blob', 'input=none');
        $this->FLD('expandByBatches', 'enum(no=Само ако има въведени,yes=Винаги)', 'caption=Разпъване по партиди при показване->Избор');

        // Ако потребителя има роля 'accMaster', може да контира/оотегля/възстановява МО с приключени права
        if (haveRole('accMaster,ceo')) {
            $this->canUseClosedItems = true;
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'makesale' && isset($rec->id)) {
            if ($rec->state != 'active') {
                $requiredRoles = 'no_one';
            } else {
                $responsible = $mvc->getSelectedResponsiblePersons($rec);
                if (!countR($responsible)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if (($action == 'add' || $action == 'edit' || $action == 'fillreport') && isset($rec)) {
            if (isset($rec->threadId)) {
                if (!doc_Threads::haveRightFor('single', $rec->threadId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if (!deals_Helper::canSelectObjectInDocument($action, $rec, 'store_Stores', 'storeId')) {
            $requiredRoles = 'no_one';
        }
        
        if ($action == 'fillreport' && isset($rec)) {
            if ($rec->state != 'draft') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Намира МОЛ-те на които ще начитаме липсите
     *
     * @param stdClass $rec
     *
     * @return array $options
     */
    private static function getSelectedResponsiblePersons($rec)
    {
        $options = array();
        
        $dQuery = store_InventoryNoteSummary::getResponsibleRecsQuery($rec->id);
        $dQuery->show('charge');
        while ($dRec = $dQuery->fetch()) {
            $options[$dRec->charge] = core_Users::getVerbal($dRec->charge, 'nick');
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
        
        $form->setDefault('storeId', doc_Folders::fetchCoverId($form->rec->folderId));
        $form->setReadOnly('storeId');
        $form->setDefault('hideOthers', 'no');
        $form->setDefault('expandGroups', 'no');
        
        if (isset($form->rec->id)) {
            $form->setReadOnly('storeId');
        }

        if(!core_Packs::isInstalled('batch')){
            $form->setField('expandByBatches', 'input=none');
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
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            
            // Проверка имали избрани вложени групи
            if (cat_Groups::checkForNestedGroups($rec->groups)) {
                $form->setError('groups', 'Избрани са вложени групи');
            }
        }
    }
    
    
    /**
     * Можели документа да се добави в посочената папка
     *
     * @param $folderId int ид на папката
     *
     * @return bool
     */
    public static function canAddToFolder($folderId)
    {
        $folderClass = doc_Folders::fetchCoverClassName($folderId);
        
        return ($folderClass == 'store_Stores') ? true : false;
    }
    
    
    /**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow_($id)
    {
        expect($rec = $this->fetch($id));
        $title = $this->getRecTitle($rec);
        
        $row = (object) array(
            'title' => $title,
            'authorId' => $rec->createdBy,
            'author' => $this->getVerbal($rec, 'createdBy'),
            'state' => $rec->state,
            'recTitle' => $title
        );
        
        return $row;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        if ($rec->state != 'rejected') {
            if ($mvc->haveRightFor('single', $rec->id)) {
                $url = array($mvc, 'getBlankForm', $rec->id, 'ret_url' => true);
                $data->toolbar->addBtn('Бланка||Blank', $url, 'ef_icon = img/16/print_go.png,title=Разпечатване на бланка за попълване,target=_blank');
            }
        }
        
        if ($mvc->haveRightFor('makesale', $rec)) {
            $url = array($mvc, 'makeSale', $rec->id, 'ret_url' => true);
            $data->toolbar->addBtn('Начет', $url, 'ef_icon = img/16/cart_go.png,title=Начисляване на излишъците на МОЛ');
        }
        
        if (core_Packs::isInstalled('batch')) {
            if (batch_Movements::haveRightFor('list') && $data->rec->state == 'active') {
                $data->toolbar->addBtn('Партиди', array('batch_Movements', 'list', 'document' => $mvc->getHandle($data->rec->id)), 'ef_icon = img/16/wooden-box.png,title=Добавяне като ресурс,row=2');
            }
        }
        
        if ($mvc->haveRightFor('fillreport', $rec)) {
            $url = array($mvc, 'fillreport', $rec->id, 'ret_url' => true);
            $data->toolbar->addBtn('Нулиране', $url, 'id=fillReport,ef_icon = img/16/cart_go.png,title=Нулиране на всички артикули без въведени количества,row=2');
            $data->toolbar->setWarning('fillReport', 'Наистина ли желаете всички артикули без въведени количества да се нулират|*?');
        }
    }
    
    
    /**
     * Екшън създаващ продажба в папката на избран МОЛ
     */
    public function act_makeSale()
    {
        // Проверка за права
        $this->requireRightFor('makesale');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('makesale', $rec);
        
        // Имали пторебители за начет
        $options = $this->getSelectedResponsiblePersons($rec);
        
        // Подготвяме формата
        $form = cls::get('core_Form');
        $form->title = 'Избор на МОЛ за начет';
        $form->FLD('userId', 'key(mvc=core_Users,select=nick)', 'caption=МОЛ,mandatory');
        
        $form->setOptions('userId', array('' => '') + $options);
        if (countR($options) == 1) {
            $form->setDefault('userId', key($options));
        }
        $form->input();
        
        // Ако е събмитната
        if ($form->isSubmitted()) {
            
            // Кой е избрания потребител?
            $userId = $form->rec->userId;
            $personId = crm_Profiles::fetchField("#userId = {$userId}", 'personId');
            
            // Създаваме продажба в папката му
            $fields = array('shipmentStoreId' => $rec->storeId, 'valior' => $rec->valior, 'originId' => $rec->containerId);
            $saleId = sales_Sales::createNewDraft('crm_Persons', $personId, $fields);
            
            // Добавяме редовете, които са за неговото начисляване
            $dQuery = store_InventoryNoteSummary::getResponsibleRecsQuery($rec->id);
            $dQuery->where("#charge = {$userId}");
            while ($dRec = $dQuery->fetch()) {
                $quantity = abs($dRec->delta);
                sales_Sales::addRow($saleId, $dRec->productId, $quantity);
            }
            
            // Редирект при успех
            redirect(array('sales_Sales', 'single', $saleId));
        }
        
        // Добавяме бутони
        $form->toolbar->addSbBtn('Продажба', 'save', 'id=save, ef_icon = img/16/cart_go.png', 'title=Създаване на продажба');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'id=cancel, ef_icon = img/16/close-red.png', 'title=Прекратяване на действията');
        
        // Рендираме формата
        $tpl = $form->renderHtml();
        $tpl = $this->renderWrapping($tpl);
        
        // Връщаме шаблона
        return $tpl;
    }
    
    
    /**
     * Преди подготовка на сингъла
     */
    protected static function on_BeforePrepareSingle(core_Mvc $mvc, &$res, $data)
    {
        if (Request::get('Blank', 'varchar')) {
            Mode::set('blank');
        }
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $rec = &$data->rec;
        $row = &$data->row;
        
        $headerInfo = deals_Helper::getDocumentHeaderInfo(null, null);
        $row = (object) ((array) $row + (array) $headerInfo);
        $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        
        $toDate = dt::addDays(-1, $rec->valior);
        $toDate = dt::verbal2mysql($toDate, false);
        $row->toDate = $mvc->getFieldType('valior')->toVerbal($toDate);
        
        if ($storeLocationId = store_Stores::fetchField($data->rec->storeId, 'locationId')) {
            $row->storeAddress = crm_Locations::getAddress($storeLocationId);
        }
        
        $row->sales = array();
        
        if (!Mode::is('blank')) {
            $sQuery = sales_Sales::getQuery();
            $sQuery->where("#originId = {$rec->containerId}");
            $sQuery->show('id,contragentClassId,contragentId,state');
            while ($sRec = $sQuery->fetch()) {
                $index = $sRec->contragentClassId . '|' . $sRec->contragentId;
                if (!array_key_exists($index, $row->sales)) {
                    $userId = crm_Profiles::fetchField("#personId = {$sRec->contragentId}", 'userId');
                    $row->sales[$index] = (object) array('sales' => array(), 'link' => crm_Profiles::createLink($userId));
                }
                
                $class = "state-{$sRec->state}";
                $link = sales_Sales::getLink($sRec->id, 0, false);
                $row->sales[$index]->sales[] = "<span class='{$class}'>{$link}</span>";
            }
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        foreach ($data->row->sales as $saleObject) {
            $saleObject->sales = implode(', ', $saleObject->sales);
            $block = clone $tpl->getBlock('link');
            $block->placeObject($saleObject);
            $block->removeBlocks();
            $block->removePlaces();
            $tpl->append($block, 'SALES_BLOCK');
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        $tpl->push('store/js/InventoryNotes.js', 'JS');
        
        jquery_Jquery::run($tpl, 'noteActions();');
        
        if (!Mode::is('printing')) {
            $tpl->removeBlock('COUNTER');
        } else {
            $tpl->removeBlock('ExtState');
        }
    }
    
    
    /**
     * Връща артикулите в протокола
     *
     * @param stdClass $rec - ид или запис
     *
     * @return array $res - масив с артикули
     */
    public function getCurrentProducts($rec)
    {
        $res = array();
        $rec = $this->fetchRec($rec);
        
        $query = store_InventoryNoteSummary::getQuery();
        $query->where("#noteId = {$rec->id}");
        $query->show('noteId,productId,blQuantity,groups,modifiedOn,quantity');
        
        while ($dRec = $query->fetch()) {
            $res[] = $dRec;
        }
        
        return $res;
    }
    
    
    /**
     * Масив с артикулите срещани в счетоводството
     *
     * @param stdClass $rec
     *
     * @return array
     *               o productId      - ид на артикул
     *               o groups         - в кои групи е
     *               o blQuantity     - к-во
     *               o searchKeywords - ключови думи
     *               o modifiedOn     - текуща дата
     */
    private function getProductsFromBalance($rec)
    {
        $res = array();
        $Summary = cls::get('store_InventoryNoteSummary');
        
        // Търсим артикулите от два месеца назад
        $to = dt::addDays(-1, $rec->valior);
        $to = dt::verbal2mysql($to, false);
        
        $from = dt::addMonths(-2, $to);
        $from = dt::verbal2mysql($from, false);
        
        $now = dt::now();
        
        // Изчисляваме баланс за подадения период за склада
        $storeItemId = acc_Items::fetchItem('store_Stores', $rec->storeId)->id;
        $Balance = new acc_ActiveShortBalance(array('from' => $from, 'to' => $to, 'accs' => '321', 'cacheBalance' => false, 'item1' => $storeItemId, 'keepUnique' => true));
        $bRecs = $Balance->getBalance('321');
        
        
        $productPositionId = acc_Lists::getPosition('321', 'cat_ProductAccRegIntf');
        
        // Подготвяме записите в нормален вид
        if (is_array($bRecs)) {
            foreach ($bRecs as $bRec) {
                
                // Записите, които не са от избрания склад ги пропускаме
                if ($bRec->ent1Id != $storeItemId) {
                    continue;
                }
                
                $productId = acc_Items::fetchField($bRec->{"ent{$productPositionId}Id"}, 'objectId');
                $aRec = (object) array('noteId' => $rec->id,
                    'productId' => $productId,
                    'groups' => null,
                    'modifiedOn' => $now,
                    'createdBy' => core_Users::SYSTEM_USER,
                    'blQuantity' => $bRec->blQuantity);
                $aRec->searchKeywords = $Summary->getSearchKeywords($aRec);
                
                $groups = cat_Products::fetchField($productId, 'groups');
                if (!empty($groups)) {
                    $aRec->groups = $groups;
                }
                
                $res[] = $aRec;
            }
        }
        
        // Връщаме намерените артикули
        return $res;
    }
    
    
    /**
     * Синхронизиране на множеството на артикулите идващи от баланса
     * и текущите записи.
     *
     * @param stdClass $rec
     *
     * @return void
     */
    public function sync($id)
    {
        expect($rec = $this->fetchRec($id));
        
        // Дигаме тайм лимита
        core_App::setTimeLimit(800);
        
        // Извличаме артикулите от баланса
        $balanceArr = $this->getProductsFromBalance($rec);
        
        // Извличаме текущите записи
        $currentArr = $this->getCurrentProducts($rec);
        
        // Избраните групи
        $rGroup = cat_Groups::getDescendantArray($rec->groups);
        $rGroup = keylist::toArray($rGroup);
        
        // От наличните артикули, взимат се ид-та на тези с к-во
        $productArr = array();
        array_walk($currentArr, function ($a) use (&$productArr) {
            if (isset($a->quantity)) {
                $productArr[$a->productId] = $a->productId;
            }
        });
        
        // От артикулите от баланса, се махат тези, които нямат избраната група и нямат к-ва.
        // Целта е ако потребителя е въвел артикул, който не е в избраните групи с к-во, неговото очаквано
        // к-во да дойде от баланса
        foreach ($balanceArr as $id => $new) {
            if ($rec->hideOthers == 'yes') {
                if (!keylist::isIn($rGroup, $new->groups) && !in_array($new->productId, $productArr)) {
                    unset($balanceArr[$id]);
                }
            }
        }
        
        // Синхронизираме двата масива
        $syncedArr = arr::syncArrays($balanceArr, $currentArr, 'noteId,productId', 'blQuantity,groups,modifiedOn');
        $Summary = cls::get('store_InventoryNoteSummary');
        
        // Ако има нови артикули, добавяме ги
        if (countR($syncedArr['insert'])) {
            $Summary->saveArray($syncedArr['insert']);
        }
        
        // На останалите им обновяваме определени полета
        if (countR($syncedArr['update'])) {
            $Summary->saveArray($syncedArr['update'], 'id,noteId,productId,blQuantity,groups,modifiedOn,searchKeywords');
        }
        
        $deleted = 0;
        
        // Ако трябва да се трият артикули
        if (countR($syncedArr['delete'])) {
            foreach ($syncedArr['delete'] as $deleteId) {
                
                // Трием само тези, които нямат въведено количество
                $sRec = store_InventoryNoteSummary::fetch($deleteId, 'productId,quantity,createdBy');
                if (!isset($sRec->quantity) && ($sRec->createdBy == core_Users::SYSTEM_USER || empty($sRec->createdBy))) {
                    $deleted++;
                    store_InventoryNoteSummary::delete($deleteId);
                }
            }
        }

        // Ако е инсталиран пакета за партиди
        if(core_Packs::isInstalled('batch')){
            $recalcQuery = store_InventoryNoteSummary::getQuery();
            $recalcQuery->where("#noteId = {$rec->id} AND #quantityHasAddedValues = 'yes'");
            $productsWithBatches = batch_Items::getProductsWithDefs(false);
            if(countR($productsWithBatches)){
                $recalcQuery->in('productId', $productsWithBatches);

                while($sRec = $recalcQuery->fetch()){
                    store_InventoryNoteSummary::recalc($sRec);
                }
            }
        }

        self::logWrite('Синхронизиране на данните', $rec->id);
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
        // Синхронизираме данните само в чернова
        if ($rec->state == 'draft' && $rec->_isClone !== true) {
            $mvc->sync($rec);
        } elseif ($rec->state == 'active' || ($rec->state == 'rejected' && $rec->brState == 'active')) {
            cls::get('store_InventoryNoteDetails')->invoke('AfterContoOrReject', array($rec));
        }
        
        static::invalidateCache($rec);
    }
    
    
    /**
     * Инвалидиране на кеша на документа
     *
     * @param mixed $rec – ид или запис
     *
     * @return void
     */
    public static function invalidateCache($rec)
    {
        $rec = static::fetchRec($rec);
        
        core_Cache::removeByType("store_InventoryNotes_{$rec->id}");
    }
    
    
    /**
     * Връща ключа за кеширане на данните
     *
     * @param stdClass $rec - запис
     *
     * @return string $key  - уникален ключ
     */
    public static function getCacheKey($rec)
    {
        // Подготвяме ключа за кеширане
        $cu = core_Users::getCurrent();
        $lg = core_Lg::getCurrent();
        $isNarrow = (Mode::is('screenMode', 'narrow')) ? true : false;
        $key = "ip{$cu}|{$lg}|{$isNarrow}|";
        
        // Връщаме готовия ключ
        return $key;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row    Това ще се покаже
     * @param stdClass $rec    Това е записа в машинно представяне
     * @param array    $fields
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-list'])) {
            $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
            $row->title = $mvc->getLink($rec->id, 0);
        }
    }
    
    
    /**
     * Документа не може да се активира ако има детайл с количество 0
     */
    protected static function on_AfterCanActivate($mvc, &$res, $rec)
    {
        $res = true;
    }
    
    
    /**
     * Рендиране на формата за избор на настройките на бланката
     *
     * @return core_ET
     */
    public function act_getBlankForm()
    {
        // Проверка за входни данни
        $this->requireRightFor('single');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('single', $rec);
        
        $url = array($this, 'single', $id, 'Printing' => true, 'Blank' => true);
        $groupName = Request::get('groupName', 'varchar');
        if ($groupName) {
            $url['groupName'] = $groupName;
        }
        
        $directRedirect = true;
        
        // Подготовка на формата
        $form = cls::get('core_Form');
        $form->title = 'Настройки за принтиране на бланка от|* <b>' . static::getHyperlink($id, true) . '</b>';
        
        if (haveRole('ceo,storeMaster')) {
            $directRedirect = false;
            $form->FLD('showBlQuantities', 'enum(no=Скриване,yes=Показване)', 'caption=Очаквани количества,mandatory');
            $form->setDefault('showBlQuantities', 'no');
        }
        
        if (core_Packs::isInstalled('batch')) {
            $directRedirect = false;
            $form->FLD('batches', 'enum(no=Скриване,yes=Показване)', 'caption=Партиди,mandatory');
            $form->setDefault('batches', 'yes');
        }
        
        if ($directRedirect === true) {
            
            return new Redirect($url);
        }
        
        // Изпращане на формата
        $form->input();
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            
            if ($rec->batches == 'yes') {
                $url['showBatches'] = true;
            }
            
            if ($rec->showBlQuantities == 'yes') {
                $url['showBlQuantities'] = true;
            }
            
            $this->logWrite('Настройки на бланката', $id);
            
            // Редирект към урл-то за бланката
            return new Redirect($url);
        }
        
        // Добавяне на бутоните на формата
        $form->toolbar->addSbBtn('Бланка', 'save', 'ef_icon = img/16/disk.png, title = Генериране на бланка');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        // Рендиране на обвивката и формата
        return $this->renderWrapping($form->renderHtml());
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
        $rec = $this->fetchRec($id);
        
        if ($rec->isContable != 'yes') {
            $this->save($rec, 'isContable');
        }
    }
    
    
    /**
     * Ре-контиране на счетоводен документ
     */
    protected static function on_AfterReConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        cls::get('store_InventoryNoteDetails')->invoke('AfterContoMaster', array($rec));
    }
    
    
    /**
     * Контиране на счетоводен документ
     */
    protected static function on_AfterConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        cls::get('store_InventoryNoteDetails')->invoke('AfterContoMaster', array($rec));
    }
    
    
    /**
     * Оттегляне на документ
     */
    protected static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        cls::get('store_InventoryNoteDetails')->invoke('AfterRejectMaster', array($rec));
    }
    
    
    /**
     * Метод за създаване на нов протокол за инвентаризация
     *
     * @param int           $storeId     - склад
     * @param datetime|NULL $valior      - вальор
     * @param bool          $loadCurrent - дали да се заредят всички артикули в склада
     *
     * @return int $id             - ид на протокола
     */
    public static function createDraft($storeId, $valior = null, $loadCurrent = false)
    {
        $valior = (isset($valior)) ? $valior : dt::today();
        expect(store_Stores::fetch($storeId), "Няма склад с ид {$storeId}");
        
        $rec = (object) array('storeId' => $storeId,
            'valior' => $valior,
            'hideOthers' => (!$loadCurrent) ? 'yes' : 'no',
            'folderId' => store_Stores::forceCoverAndFolder($storeId));
        
        static::route($rec);
        
        $id = static::save($rec);
        doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, core_Users::getCurrent());
        
        return $id;
    }
    
    
    /**
     * Добавяне на ред към протокол за инвентаризация
     *
     * @param int         $noteId               - ид на протокол
     * @param int         $productId            - ид на артикул
     * @param int         $packagingId          - ид на мярка/опаковка
     * @param float       $quantityInPack       - к-во в опаковката
     * @param float       $foundPackQuantity    - намерено количество опаковки
     * @param float|NULL  $expectedPackQuantity - очаквано количество опаковка, ако не се зададе е 0
     * @param string|NULL $batch                - партиден номер, опционален
     *
     * @return int - ид на записа
     */
    public static function addRow($noteId, $productId, $packagingId, $quantityInPack, $foundPackQuantity, $expectedPackQuantity = null, $batch = null)
    {
        // Проверки на параметрите
        expect($noteRec = store_InventoryNotes::fetch($noteId), "Няма протокол с ид {$noteId}");
        expect($noteRec->state == 'draft', 'Протокола трябва да е чернова');
        expect($productRec = cat_Products::fetch($productId), "Няма артикул с ид {$productId}");
        expect($productRec->canStore == 'yes', 'Артикулът трябва да е складируем');
        expect($packagingId, 'Няма мярка/опаковка');
        expect(cat_UoM::fetch($packagingId), "Няма опаковка/мярка с ид {$packagingId}");
        
        $packs = cat_Products::getPacks($productId);
        expect(isset($packs[$packagingId]), "Артикулът не поддържа мярка/опаковка с ид {$packagingId}");
        
        $Double = cls::get('type_Double');
        expect($quantityInPack = $Double->fromVerbal($quantityInPack));
        expect(($foundPackQuantity = $Double->fromVerbal($foundPackQuantity)) || !$foundPackQuantity);
        $quantity = $quantityInPack * $foundPackQuantity;
        if (isset($expectedPackQuantity)) {
            expect($expectedPackQuantity = $Double->fromVerbal($expectedPackQuantity));
        }
        
        // Подготовка на записа
        $rec = (object) array('noteId' => $noteId,
            'productId' => $productId,
            'packagingId' => $packagingId,
            'quantityInPack' => $quantityInPack,
            'quantity' => $quantity,
        );
        
        // Валидация на партидния номер ако има
        if (!empty($batch)) {
            $msg = null;
            if (core_Packs::isInstalled('batch')) {
                expect($Def = batch_Defs::getBatchDef($productId), 'Опит за задаване на партида на артикул без партида');
                $Def->isValid($batch, $quantity, $msg);
                if ($msg) {
                    expect(false, tr($msg));
                }
                
                $rec->batch = $Def->normalize($batch);
            }
        }
        
        // Запис на реда
        core_Users::forceSystemUser();
        store_InventoryNoteDetails::save($rec);
        
        // Задаване на очакваното количество
        if (isset($expectedPackQuantity)) {
            $sId = store_InventoryNoteSummary::force($noteId, $productId);
            store_InventoryNoteSummary::save((object) array('id' => $sId, 'blQuantity' => $expectedPackQuantity), 'id,blQuantity');
        }
        core_Users::cancelSystemUser();
        
        // Връщане на записа
        return $rec->id;
    }
    
    
    /**
     * Екшън зануляващ к-та в инвентаризацията
     */
    public function act_fillreport()
    {
        $this->requireRightFor('fillreport');
        expect($id = Request::get('id', 'int'));
        expect($rec = self::fetch($id));
        $this->requireRightFor('fillreport', $rec);
        
        $summaryQuery = store_InventoryNoteSummary::getQuery();
        $summaryQuery->where("#noteId = {$rec->id} AND #quantity IS NULL");
        
        while ($summaryRec = $summaryQuery->fetch()) {
            $dRec = (object) array('noteId' => $id, 'productId' => $summaryRec->productId, 'quantityInPack' => 1);
            $dRec->packagingId = cat_Products::fetchField($summaryRec->productId, 'measureId');
            $dRec->quantity = 0;
            store_InventoryNoteDetails::save($dRec);
        }
        
        $this->logInAct('Нулиране на невъведените артикули', $id);
        
        followRetUrl('Всички артикули с невъведени количества са нулирани');
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $mvc = cls::get(get_called_class());
        $rec = static::fetchRec($rec);
        $handle = $mvc->getHandle($rec);
        $title = "{$handle} / " . store_Stores::getTitleById($rec->storeId, false);
         
        return $title;
    }
}
