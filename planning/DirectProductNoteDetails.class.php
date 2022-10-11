<?php


/**
 * Клас 'planning_DirectProductNoteDetails'
 *
 * Детайли на мениджър на детайлите на протокола за производство
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_DirectProductNoteDetails extends deals_ManifactureDetail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола за производство';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Ресурс';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_SaveAndNew,deals_plg_ImportDealDetailProduct, plg_Created, planning_Wrapper, plg_Sorting,
                        planning_plg_ReplaceEquivalentProducts, plg_PrevAndNext,cat_plg_ShowCodes';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,production';
    
    
    /**
     * Кой има право да променя взаимно заменяемите артикули?
     */
    public $canReplaceproduct = 'ceo,production';


    /**
     * Може ли да се импортират цени
     */
    public $allowPriceImport = false;
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,production';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,production';
    
    /**
     * Кой може да го импортира артикули?
     *
     * @var string|array
     */
    public $canImport = 'user';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=№,productId=Материал, packagingId, packQuantity=Количество->Въведено,quantityFromBom=Количество->Рецепта,quantityExpected=Количество->Очаквано,storeId';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'quantityFromBom,quantityExpected';
    
    
    /**
     * Активен таб
     */
    public $currentTab = 'Протоколи->Производство';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=planning_DirectProductionNote)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('type', 'enum(input=Влагане,pop=Отпадък,allocated=Разходи)', 'caption=Действие,silent,input=hidden');
        parent::setDetailFields($this);
        $this->setField('quantity', 'caption=Количества');
        $this->FLD('quantityFromBom', 'double', 'caption=От рецепта,input=none,smartCenter,tdClass=noteBomCol');
        $this->FLD('quantityExpected', 'double', 'caption=Реално вложено,input=none,smartCenter,tdClass=noteExpectedCol');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Изписване от,input=none,tdClass=small-field nowrap,placeholder=Незавършено производство');
        $this->FLD('fromAccId', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'caption=Изписване от,input=none,tdClass=small-field nowrap,placeholder=Незавършено производство');
        $this->FLD('expenseItemId', 'acc_type_Item(select=titleNum,lists=600)', 'input=none,after=expenses,caption=Разходен обект');
        
        $this->setDbIndex('productId');
        $this->setDbIndex('noteId,type');
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
        $data->singleTitle = ($rec->type == 'pop') ? 'отпадък' : (($rec->type == 'input') ? 'материал' : 'отнесен разход');
        $data->defaultMeta = ($rec->type == 'pop') ? 'canConvert,canStore' : (($rec->type == 'input') ? 'canConvert' : null);
        
        $productOptions = $expenseItemIdOptions = array();
        if($rec->type == 'allocated'){
            $jobRec = planning_DirectProductionNote::getJobRec($rec->noteId);
            $allocatedArr = planning_Jobs::getAllocatedServices($jobRec);
            if(!countR($allocatedArr)){
                $form->setError('productId', 'Няма все още отнесени разходи към производствени операции по заданието');
                $form->setReadOnly('productId');
            } else {
                foreach ($allocatedArr as $aObject){
                    $productOptions[$aObject->productId] = cat_Products::getTitleById($aObject->productId, false);
                    $expenseItemIdOptions[$aObject->expenseItemId] = acc_Items::getVerbal($aObject->expenseItemId, 'title');
                }
                
                if(countR($productOptions) == 1){
                    $form->setDefault('productId', key($productOptions));
                } else {
                    $productOptions = array('' => '') + $productOptions;
                }
                
                $form->setFieldType('productId', 'int');
                $form->setOptions('productId', $productOptions);
            }
        }
        if (isset($rec->productId)) {
            $prodRec = cat_Products::fetch($rec->productId, 'canStore');
            if ($prodRec->canStore == 'yes') {
                $form->setField('storeId', 'input');
                if (empty($rec->id) && isset($data->masterRec->inputStoreId)) {
                    $form->setDefault('storeId', $data->masterRec->inputStoreId);
                }
            } else {
                $options = array('' => '', '61102' => 'Разходи за услуги (всички)');
                $form->setOptions('fromAccId', $options);
                $form->setField('fromAccId', 'input');
                if($rec->type == 'allocated'){
                    $form->setField('expenseItemId', 'input');
                    $form->setDefault('expenseItemId', key($expenseItemIdOptions));
                    $form->setOptions('expenseItemId', array('' => '') + $expenseItemIdOptions);
                    
                    $form->setDefault('fromAccId', '61102');
                    $form->setReadOnly('fromAccId');
                }
            }
        }
        
        if ($rec->type == 'pop') {
            
            // Артикула, по която е ПО да може винаги да се избира, ако е складируем
            $noteProductId = planning_DirectProductionNote::fetchField($rec->noteId, 'productId');
            if(cat_Products::fetchField($noteProductId, 'canStore') == 'yes'){
                $form->setFieldTypeParams('productId', array('alwaysShow' => array($noteProductId)));
            }
            $form->setField('storeId', 'input=none');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        $rec = &$form->rec;
        
        if (isset($rec->productId)) {
            if($rec->type == 'pop'){
                
                // Ако отпадъка ще е произведения артикул, само мярката в която е произведен ще е позволена
                $noteRec = planning_DirectProductionNote::fetch($rec->noteId, 'productId,packagingId');
                if($rec->productId == $noteRec->productId){
                    $form->rec->_onlyAllowedPackId = $noteRec->packagingId;
                }
            }
            
            if ($form->isSubmitted()) {
                // Проверка на к-то
                $warning = null;
                if (!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)) {
                    $form->setWarning('packQuantity', $warning);
                }
                
                // Ако добавяме отпадък, искаме да има себестойност
                if ($rec->type == 'pop') {
                    $selfValue = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $rec->productId);
                    
                    if (!isset($selfValue)) {
                        $form->setError('productId', 'Отпадъкът няма себестойност');
                    }
                }
                
                if(!empty($rec->fromAccId)){
                    $rec->storeId = null;
                }
                
                if(!empty($rec->storeId)){
                    $rec->fromAccId = null;
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, &$data)
    {
        if (!countR($data->recs)) {
            
            return;
        }
        
        foreach ($data->rows as $id => &$row) {
            $rec = &$data->recs[$id];
            $row->ROW_ATTR['class'] = ($rec->type == 'pop') ? 'row-removed' : 'row-added';
            
            if (isset($rec->storeId)) {
                $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
            }
            
            if ($rec->type == 'pop') {
                $row->packQuantity .= " {$row->packagingId}";
            }
            
            if(!empty($rec->expenseItemId)){
                $itemLink = acc_Items::getVerbal($rec->expenseItemId, 'titleLink');
                $row->productId .= "<br><small><span class='quiet'>" . tr('Раз. обект') . "</span>: {$itemLink}</small>";
            }
        }
    }
    
    
    /**
     * След подготовка на детайлите, изчислява се общата цена
     * и данните се групират
     */
    protected static function on_AfterPrepareDetail($mvc, $res, $data)
    {
        $data->inputArr = $data->popArr = array();
        $countInputed = $countPoped = 1;
        $Int = cls::get('type_Int');
        
        // За всеки детайл (ако има)
        if (countR($data->rows)) {
            foreach ($data->rows as $id => $row) {
                $rec = $data->recs[$id];
                if (!is_object($row->tools)) {
                    $row->tools = new ET('[#TOOLS#]');
                }
                
                // Разделяме записите според това дали са вложими или не
                if ($rec->type == 'input' || $rec->type == 'allocated') {
                    $num = $Int->toVerbal($countInputed);
                    $data->inputArr[$id] = $row;
                    $countInputed++;
                } else {
                    $num = $Int->toVerbal($countPoped);
                    $data->popArr[$id] = $row;
                    $countPoped++;
                }
                
                $row->tools->append($num, 'TOOLS');
            }
        }
    }
    
    
    /**
     * Помощна ф-я за модифициране на записите
     */
    private function modifyRows($data)
    {
        if(!countR($data->rows)) return;
        
        $origin = doc_Containers::getDocument($data->masterData->rec->originId);
        if($origin->isInstanceOf('planning_Tasks')){
            $origin = doc_Containers::getDocument($origin->fetchField('originId'));
        }
        
        foreach ($data->rows as $id => &$row) {
            $rec = $data->recs[$id];
            if (empty($rec->storeId)) {
                $emptyPlaceholder = tr('Незавършено производство');
                if(!empty($rec->fromAccId)){
                    $emptyPlaceholder = tr('Разходи за услуги (всички)');
                }
                
                $row->storeId = "<span class='quiet'>{$emptyPlaceholder}</span>";
            } elseif($rec->type != 'pop') {
                $threadId = $origin->fetchField('threadId');
                $deliveryDate = (!empty($data->masterData->rec->deadline)) ? $data->masterData->rec->deadline : $data->masterData->rec->valior;
                deals_Helper::getQuantityHint($row->packQuantity, $this, $rec->productId, $rec->storeId, $rec->quantity, $data->masterData->rec->state, $deliveryDate, $threadId);
            }
            
            if(!empty($rec->quantityFromBom)){
                $rec->quantityFromBom /= $rec->quantityInPack;
                $row->quantityFromBom = $this->getFieldType('quantityFromBom')->fromVerbal($rec->quantityFromBom);
            }
            
            if(!empty($rec->quantityExpected)){
                $rec->quantityExpected /= $rec->quantityInPack;
                $row->quantityExpected = $this->getFieldType('quantityExpected')->fromVerbal($rec->quantityExpected);
            }
        }
    }
    
    
    /**
     * Променяме рендирането на детайлите
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderDetail_($data)
    {
        $tpl = new ET('');
        
        // Ако протокола е само за заготовка и няма детайли няма да се рендират
        if(!countR($data->recs) && !planning_DirectProductionNote::isForJobProductId($data->masterData->rec)) return $tpl;
        
        if (Mode::is('printing')) {
            unset($data->listFields['tools']);
        }
        
        // Рендираме таблицата с вложените материали
        $data->listFields['productId'] = 'Вложени артикули|* ';
        
        $fieldset = clone $this;
        $fieldset->FNC('num', 'int');
        $table = cls::get('core_TableView', array('mvc' => $fieldset));
        
        $iData = clone $data;
        $iData->listTableMvc = clone $this;
        $iData->rows = $data->inputArr;
        $iData->recs = array_intersect_key($iData->recs, $iData->rows);
        
        $this->invoke('BeforeRenderListTable', array(&$tpl, &$iData));
        plg_AlignDecimals2::alignDecimals($this, $iData->recs, $iData->rows);
        
        $iData->listFields = core_TableView::filterEmptyColumns($iData->rows, $iData->listFields, $this->hideListFieldsIfEmpty);
        if(empty($iData->listFields['quantityFromBom']) && empty($iData->listFields['quantityExpected'])){
            $iData->listFields['packQuantity'] = 'Количество';
        }
        
        if(isset($iData->listFields['quantityFromBom'])){
            $iData->listFields['quantityFromBom'] = 'Количество->|*<small>|Рецепта|*</small>';
        }
        
        if(isset($iData->listFields['quantityExpected'])){
            $iData->listFields['quantityExpected'] = 'Количество->|*<small>|Очаквано|*</small>';
        }
        
        $this->modifyRows($iData);
        $detailsInput = $table->get($iData->rows, $iData->listFields);
        $tpl->append($detailsInput, 'planning_DirectProductNoteDetails');
        
        // Добавяне на бутон за нов материал
        if ($this->haveRightFor('add', (object) array('noteId' => $data->masterId, 'type' => 'input'))) {
            $tpl->append(ht::createBtn('Влагане', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'input', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/wooden-box.png', 'title' => 'Добавяне на нов материал')), 'planning_DirectProductNoteDetails');
            $tpl->append(ht::createBtn('Импортиране', array($this, 'import', 'noteId' => $data->masterId, 'type' => 'input', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/import.png', 'title' => 'Добавяне на нов материал')), 'planning_DirectProductNoteDetails');
        }
        
        if ($this->haveRightFor('add', (object) array('noteId' => $data->masterId, 'type' => 'allocated'))) {
            $tpl->append(ht::createBtn('Отнесени разходи', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'allocated', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/wooden-box.png', 'title' => 'Влагане на отнесен разход')), 'planning_DirectProductNoteDetails');
        }
        
        // Рендиране на таблицата с отпадъците
        if (countR($data->popArr) || $data->masterData->rec->state == 'draft') {
            $data->listFields['productId'] = "Отпадъци|* <small style='font-weight:normal'>( |остават в незавършеното производство|* )</small>";
            unset($data->listFields['storeId']);
            
            $pData = clone $data;
            $pData->listTableMvc = clone $this;
            $pData->rows = $data->popArr;
            $pData->recs = array_intersect_key($pData->recs, $pData->rows);
            
            $this->invoke('BeforeRenderListTable', array(&$tpl, &$pData));
            plg_AlignDecimals2::alignDecimals($this, $pData->recs, $pData->rows);
            $pData->listFields = core_TableView::filterEmptyColumns($pData->rows, $pData->listFields, $this->hideListFieldsIfEmpty);
            $this->modifyRows($pData);
            
            if(isset($pData->listFields['quantityFromBom'])){
                $pData->listFields['quantityFromBom'] = 'Количество->|*<small>|Рецепта|*</small>';
            }
            
            if(empty($pData->listFields['quantityFromBom'])){
                $pData->listFields['packQuantity'] = 'Количество';
            }
            
            $popTable = $table->get($pData->rows, $pData->listFields);
            $detailsPop = new core_ET("<span style='margin-top:5px;'>[#1#]</span>", $popTable);
            $tpl->append($detailsPop, 'planning_DirectProductNoteDetails');
        }
        
        // Добавяне на бутон за нов отпадък
        if ($this->haveRightFor('add', (object) array('noteId' => $data->masterId, 'type' => 'pop'))) {
            $tpl->append(ht::createBtn('Отпадък', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'pop', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;;margin-bottom:10px;', 'ef_icon' => 'img/16/recycle.png', 'title' => 'Добавяне на нов отпадък')), 'planning_DirectProductNoteDetails');
        }
        
        // Връщаме шаблона
        return $tpl;
    }
    
    
    /**
     * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
     */
    protected static function on_AfterGetRowInfo($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        if (empty($rec->storeId)) {
            unset($res->operation);
        } else {
            $res->operation[key($res->operation)] = $rec->storeId;
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec)) {
            
            if(!planning_DirectProductionNote::isForJobProductId($rec->noteId)){
                $requiredRoles = 'no_one';
            }
        }
        
        if($action == 'add' && isset($rec->type)){
            if($rec->type == 'allocated'){
                $jobRec = planning_DirectProductionNote::getJobRec($rec->noteId);
                $jobTaskCostObjectArr = planning_Jobs::getTaskCostObjectItems($jobRec);
                if(!countR($jobTaskCostObjectArr)){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
}
