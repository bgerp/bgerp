<?php


/**
 * Клас 'store_InventoryNoteDetails'
 *
 * Детайли на мениджър на детайлите на протоколите за инвентаризация (@see store_InventoryNotes)
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_InventoryNoteDetails extends doc_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола за инвентаризация';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'артикул за опис';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';


    /**
     * Може ли да се импортират цени
     */
    public $allowPriceImport = false;


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'store_Wrapper, plg_AlignDecimals2, plg_RowTools2, plg_PrevAndNext, plg_SaveAndNew, plg_Modified,deals_plg_ImportDealDetailProduct,plg_Created,plg_Sorting,plg_Search';
    
    
    /**
     * Кой има достъп до листовия изглед?
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, storeMaster,inventory';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo, storeMaster,inventory';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canDeletesysdata = 'ceo, storeMaster,inventory';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, storeMaster,inventory';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, storeMaster,inventory';
    
    
    /**
     * Активен таб
     */
    public $currentTab = 'Документи->Инвентаризация';
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'noteId, productId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'code=Код,productId, packagingId=Мярка,packQuantity=Установено,modifiedOn,modifiedBy';
    
    
    /**
     * По подразбиране колко резултата да показва на страница
     */
    public $listItemsPerPage = 40;
    
    
    /**
     * Работен кеш
     */
    protected $cache = array();
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'productId,packagingId';
    
    
    /**
     * Име на полето за търсене
     */
    public $searchInputField = 'searchDetail';


    /**
     * Позволява ли се възможност за подмяна при импорт
     */
    public $allowImportReplacement = true;


    /**
     * Кои записи да се рекалкулират при шътдаун
     */
    public $recalcOnShutDown = array();


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=store_InventoryNotes)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=canStore,hasnotProperties=generic,maxSuggestions=100,forceAjax,titleFld=name)', 'class=w100,caption=Артикул,mandatory,silent,removeAndRefreshForm=packagingId|quantity|quantityInPack|packQuantity|batch|batchEx|batchEx|batchNew');
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory,tdClass=small-field nowrap,removeAndRefreshForm=quantity|quantityInPack|packQuantity|batch,remember,silent');
        $this->FLD('quantity', 'double', 'caption=Количество,input=none');
        $this->FLD('quantityInPack', 'double(decimals=2)', 'input=hidden,column=none');
        $this->FNC('packQuantity', 'double(decimals=2,min=0)', 'caption=Количество,input');
        $this->FNC('editQuantity', 'double', 'input=hidden,silent');
        $this->FNC('editSummary', 'double', 'input=hidden,silent');
        $this->FNC('editBatch', 'varchar(nullIfEmpty)', 'input=hidden,silent');

        $this->setDbIndex('productId,packagingId');
        $this->setDbIndex('modifiedOn');
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    protected static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->quantity) || !isset($rec->quantityInPack)) {
            return;
        }
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Подготовка на Детайлите
     */
    public function prepareDetail_($data)
    {
        $data->TabCaption = 'Въвеждане';
        $data->Tab = 'top';
        
        $tab = Request::get($data->masterData->tabTopParam, 'varchar');
        if ($tab == get_called_class()) {
            parent::prepareDetail_($data);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)) {
            $state = store_InventoryNotes::fetchField($rec->noteId, 'state');
            if ($state != 'draft') {
                $requiredRoles = 'no_one';
            } else {
                if (!store_InventoryNotes::haveRightFor('edit', $rec->noteId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareEditForm_($data)
    {
        $data = parent::prepareEditForm_($data);
        $form = &$data->form;
        $rec = &$form->rec;
        
        // Кеш на предишния запис
        $lastId = Mode::get("InventoryNoteLastSavedRow{$rec->noteId}");
        if ($lastId && $lastId != $rec->id) {
            if ($lastRec = $this->fetch($lastId)) {
                $row = $this->recToVerbal($lastRec);
                $info = new core_ET(tr("|*<b>|Предишно|*</b>: {$row->productId} {$row->packQuantity} {$row->packagingId} [#tools#]"));
                $info->replace($row->_rowTools->renderHtml(2), 'tools');
                $form->info = $info;
            }
        }
        
        $form->FNC('keepProduct', 'enum(yes=Да,no=Не)', 'caption=Помни артикула,after=packQuantity,input,maxRadio=2,remember');
        $permanentName = cls::getClassName($this) . '_keepProduct';
        $permanentName = (Mode::get($permanentName)) ? Mode::get($permanentName) : 'no';
        $form->setDefault('keepProduct', $permanentName);
        
        $defProduct = Mode::get("InventoryNoteNextProduct{$rec->noteId}");
        if($form->cmd != 'refresh'){
            $form->setDefault('productId', $defProduct);
        }
        
        // Рендиране на опаковките
        if (isset($rec->productId)) {
            $packs = cat_Products::getPacks($rec->productId, $rec->packagingId);
            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', key($packs));
        } else {
            $form->setField('packagingId', 'input=none');
        }


        if(isset($rec->editQuantity)){
            $form->setReadOnly('productId');
            $form->setReadOnly('packagingId');
            $form->setDefault('packQuantity', $rec->editQuantity);
        }

        return $data;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        
        if ($form->notMandatoryQ !== true) {
            $form->setField('packQuantity', 'mandatory');
        }
        
        if ($form->isSubmitted()) {
            
            // Проверка на к-то
            $warning = null;
            if (!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)) {
                $form->setWarning('packQuantity', $warning);
            }
            
            $productInfo = cat_Products::getProductInfo($rec->productId);
            $rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
            $rec->quantity = (isset($rec->packQuantity)) ? $rec->packQuantity * $rec->quantityInPack : null;
        }

        $mvc->invoke('AfterAfterInputEditForm', array($form));
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        // Подсигуряване че запис и нов го има дори и при редакция
        if (isset($data->form->rec->id)) {
            $data->form->toolbar->addSbBtn('Запис и Нов', 'save_n_new', null, array('id' => 'saveAndNew', 'order' => '1', 'ef_icon' => 'img/16/save_and_new.png', 'title' => 'Запиши документа и създай нов'));
        }
    }
    
    
    /**
     * Логика за определяне къде да се пренасочва потребителския интерфейс.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareRetUrl($mvc, $data)
    {
        if (!isset($data->form) || !$data->form->isSubmitted()) {
            return;
        }
        
        if ($data->form->cmd == 'save_n_new') {
            $rec = $data->form->rec;
            
            // Ако не е избрано за пазене на артикула, се цикли в следващия в списъка
            if ($rec->keepProduct != 'yes') {
                $cache = store_InventoryNotes::fetchField($rec->noteId, 'cache');
                $cache = (array) json_decode($cache);
                
                $keys = array_values($cache);
                $i = array_search($rec->productId, $keys);
                $key = $i + 1;
                
                $newProductId = (isset($keys[$key])) ? $keys[$key] : null;
                Mode::setPermanent("InventoryNoteNextProduct{$rec->noteId}", $newProductId);
            } else {
                Mode::setPermanent("InventoryNoteNextProduct{$rec->noteId}", $rec->productId);
            }
            
            unset($data->retUrl['id']);
            unset($data->retUrl['packagingId']);
            unset($data->retUrl['editSummary']);
            unset($data->retUrl['editBatch']);
            unset($data->retUrl['editQuantity']);
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
        // Ако е редакция от съмърите да се изтрият другите записи
        if(isset($rec->editSummary)){
            $deleteWhere = "#noteId = {$rec->noteId} AND #productId = {$rec->productId} AND #id != '{$rec->id}'";
            if(core_Packs::isInstalled('batch')){
                $deleteWhere .= empty($rec->batch) ? " AND (#batch = '' OR #batch IS NULL)" : " AND #batch = '{$rec->batch}'";
                static::delete($deleteWhere);
            } else {
                static::delete($deleteWhere);
            }
        }

        if (is_null($rec->quantity)) {
            $mvc->delete($rec->id);
        }

        $mvc->recalcOnShutDown[$rec->noteId][$rec->productId] = $rec->productId;
        Mode::setPermanent("InventoryNoteLastSavedRow{$rec->noteId}", $rec->id);
    }
    
    
    /**
     * След изтриване на запис
     */
    public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        if(Mode::is("selectRowsOnDelete_store_InventoryNoteSummary")) return;

        foreach ($query->getDeletedRecs() as $rec) {
            $mvc->recalcOnShutDown[$rec->noteId][$rec->productId] = $rec->productId;
            Mode::setPermanent("InventoryNoteLastSavedRow{$rec->noteId}", null);
        }
    }
    
    
    /**
     * Изчиства записите, заопашени за запис
     */
    public static function on_Shutdown($mvc)
    {
        if (countR($mvc->recalcOnShutDown)) {
            core_Debug::startTimer('RECALC_ON_SHUTDOWN');
            foreach ($mvc->recalcOnShutDown as $noteId => $productArr){
                foreach ($productArr as $productId){
                    $summeryId = store_InventoryNoteSummary::force($noteId, $productId);
                    store_InventoryNoteSummary::recalc($summeryId);
                }
                store_InventoryNotes::invalidateCache($noteId);
            }
            core_Debug::stopTimer('RECALC_ON_SHUTDOWN');
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
        $row->packagingId = cat_UoM::getShortName($rec->packagingId);
        
        $pRec = cat_Products::fetch($rec->productId, 'name,code,isPublic,nameEn');
        $row->productId = cat_Products::getVerbal($pRec, 'name');
        $row->productId = ht::createLinkRef($row->productId, cat_Products::getSingleUrlArray($pRec->id));
        $row->code = cat_Products::getVerbal($pRec, 'code');
        
        deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        if ($data->masterData->rec->state == 'rejected') {
            return;
        }
        
        if (!Mode::isReadOnly()) {
            $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png,title=Филтриране на данните');
            $data->listFilter->FLD('threadId', 'key(mvc=doc_Threads)', 'input=hidden');
            $data->listFilter->FLD("{$data->masterData->tabTopParam}", 'varchar', 'input=hidden');
            $data->listFilter->setDefault("{$data->masterData->tabTopParam}", get_called_class());
            $data->listFilter->setDefault('threadId', $data->masterData->rec->threadId);
            $data->listFilter->showFields = $mvc->searchInputField;
            $data->listFilter->view = 'horizontal';
        }
        
        $data->query->orderby('id', 'DESC');
    }
    
    
    /**
     * Екшън за импорт на артикули от група
     *
     * @return core_ET $tpl
     */
    public function act_ImportGroups()
    {
        // Проверки
        $this->requireRightFor('add');
        expect($noteId = Request::get('noteId', 'int'));
        expect($noteRec = store_InventoryNotes::fetch($noteId));
        $this->requireRightFor('add', (object) array('noteId' => $noteId));
        
        // Подготовка на формата
        $form = cls::get('core_Form');
        $form->title = 'Импортиране на артикули в|* <b>' . store_InventoryNotes::getHyperlink($noteId, true) . '</b>';
        $form->FLD('group', 'key(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Група,mandatory,silent,removeAndRefreshForm=selected');
        $form->input(null, 'silent');
        $form->input();
        $rec = $form->rec;
        
        // Ако е избрана група извличат се складируемите артикули от нея, които ги няма в протокола
        if (isset($rec->group)) {
            $inArr = cls::get('store_InventoryNotes')->getCurrentProducts($noteRec);
            $inArr = arr::extractValuesFromArray($inArr, 'productId');
            
            $query = cat_Products::getQuery();
            plg_ExpandInput::applyExtendedInputSearch('cat_Products', $query, $rec->group);
            if (countR($inArr)) {
                $query->notIn('id', $inArr);
            }
            
            $products = array();
            $query->where("#state = 'active'");
            $query->where("#canStore = 'yes'");
            $query->show('isPublic,folderId,meta,id,code,name');
            while ($pRec = $query->fetch()) {
                $products[$pRec->id] = static::getRecTitle($pRec, false);
            }
            
            if (!countR($products)) {
                $form->setError('group', 'Няма нови артикули за импортиране от групата');
            } else {
                $set = cls::get('type_Set', array('suggestions' => $products));
                $form->FLD('selected', 'varchar', 'caption=Артикули,mandatory');
                $form->setFieldType('selected', $set);
                $form->input('selected');
                $form->setDefault('selected', $set->fromVerbal($products));
            }
        }
        
        // Ако формата е събмитната
        if ($form->isSubmitted()) {
            $products = type_Set::toArray($rec->selected);
            if (is_array($products)) {
                $count = 0;
                foreach ($products as $pId) {
                    store_InventoryNoteSummary::force($noteId, $pId);
                    $count++;
                }

                // След импорт се изтрива кеша, да се покажат новите данни
                $key = store_InventoryNotes::getCacheKey($noteId);
                core_Cache::remove("{$this->Master->className}_{$noteId}", $key);
                store_InventoryNotes::logWrite('Импортиране от група', $noteId);

                followRetUrl(null, "Импортирани са|* '{$count}' |артикула|*");
            }
        }
        
        $form->toolbar->addSbBtn('Импорт', 'save', 'ef_icon = img/16/import.png, title = Импорт');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);
        
        return $tpl;
    }


    /**
     * Импортиране на артикул генериран от ред на csv файл
     *
     * @param int   $masterId - ид на мастъра на детайла
     * @param stdClass $row      - Обект представляващ артикула за импортиране
     *                        ->code - код/баркод на артикула
     *                        ->quantity - К-во на опаковката или в основна мярка
     *                        ->price - цената във валутата на мастъра, ако няма се изчислява директно
     *                        ->pack - Опаковката
     *                        ->batch - Партида ако има
     *
     * @return int - резултата от експорта
     */
    public function import($masterId, $row)
    {
        $pRec = cat_Products::getByCode($row->code);
        $pRec->packagingId = (isset($row->pack)) ? $row->pack : $pRec->packagingId;
        $packRec = cat_products_Packagings::getPack($pRec->productId, $pRec->packagingId);
        $quantityInPack  = is_object($packRec) ? $packRec->quantity : 1;

        $dRec = (object) array('noteId' => $masterId, 'productId' => $pRec->productId, 'quantity' => $row->quantity * $quantityInPack, 'quantityInPack' => $quantityInPack, 'packagingId' => $pRec->packagingId, 'batch' => $row->batch);

        // Ако е избрано заместване при дублиране да се заместват
        $onDuplicate = Mode::get('onDuplicate');
        if($onDuplicate){
            $batchCond = isset($row->batch) ? "#batch = '[#1#]'" : "#batch IS NULL";
            $n = self::delete(array("#noteId = {$masterId} AND #productId = {$pRec->productId} AND {$batchCond}", $row->batch));
            core_Statuses::newStatus($n, 'warning');
        }

        return self::save($dRec);
    }
}
