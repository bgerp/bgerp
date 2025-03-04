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
 * @copyright 2006 - 2024 Experta OOD
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
                        planning_plg_ReplaceProducts, plg_PrevAndNext,cat_plg_ShowCodes';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,production,store';
    
    
    /**
     * Кой има право да променя взаимно заменяемите артикули?
     */
    public $canReplaceproduct = 'ceo,production,store';


    /**
     * Може ли да се импортират цени
     */
    public $allowPriceImport = false;
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,production,store';
    
    
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
    public $listFields = 'tools=№,productId=Материал, packagingId, packQuantity=К-во->Въведено,quantityFromBom=К-во->Рецепта,quantityExpected=К-во->Очаквано,storeId';
    
    
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
        $this->FLD('type', 'enum(input=Влагане,pop=Отпадък,allocated=Разходи,subProduct=Субпродукт)', 'caption=Действие,silent,input=hidden');
        parent::setDetailFields($this);
        $this->setField('quantity', 'caption=Количества');
        $this->FLD('quantityFromBom', 'double', 'caption=От рецепта,input=none,smartCenter,tdClass=noteBomCol');
        $this->FLD('quantityExpected', 'double', 'caption=Реално вложено,input=none,smartCenter,tdClass=noteExpectedCol');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Изписване от,input=none,tdClass=small-field nowrap,placeholder=Незавършено производство,silent,removeAndRefreshForm');
        $this->FLD('isOutsourced', 'enum(no=Не,yes=Да)', 'caption=Ишлеме|*?,input=hidden,maxRadio=2,notNull,value=no');
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
        $data->singleTitle = ($rec->type == 'pop') ? 'отпадък' : (($rec->type == 'input') ? 'материал' : (($rec->type == 'subProduct') ? 'субпродукт' : 'отнесен разход'));
        $data->defaultMeta = ($rec->type == 'pop') ? 'canConvert,canStore' : (($rec->type == 'input') ? 'canConvert' : ($rec->type == 'subProduct' ? 'canManifacture,canStore' : null));
        if(empty($rec->id)){
            $form->setFieldType('packQuantity', 'double(Min=0)');
        }

        $jobRec = planning_DirectProductionNote::getJobRec($rec->noteId);
        $productOptions = $expenseItemIdOptions = array();
        if($rec->type == 'allocated'){
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
            $prodRec = cat_Products::fetch($rec->productId, 'canStore,folderId');
            if ($prodRec->canStore == 'yes') {
                $form->rec->_isStorable = true;
                $form->setField('storeId', 'input');
                if (empty($rec->id) && isset($data->masterRec->inputStoreId)) {
                    $field = $rec->type == 'subProduct' ? 'storeId' : 'inputStoreId';
                    $form->setDefault('storeId', $data->masterRec->{$field});
                }

                $Cover = doc_Folders::getCover($prodRec->folderId);
                if($Cover->haveInterface('crm_ContragentAccRegIntf')){
                    $consignmentProducts = store_ConsignmentProtocolDetailsReceived::getReceivedOtherProductsFromSale($data->masterRec->threadId, false);
                    if(array_key_exists($rec->productId, $consignmentProducts)){
                        $form->setDefault('isOutsourced', 'yes');
                    } else {
                        $form->setDefault('isOutsourced', 'no');
                    }

                    if(haveRole('ceo,acc,job')){
                        $form->setField('isOutsourced', 'input');
                    }
                }
            } else {
                $options = array('' => '', '61102' => 'Разходи за услуги (без влагане)');
                $form->setOptions('fromAccId', $options);
                $form->setField('fromAccId', 'input');
                if($rec->type == 'allocated'){
                    $form->setField('expenseItemId', 'input');
                    $form->setDefault('expenseItemId', key($expenseItemIdOptions));
                    $form->setOptions('expenseItemId', array('' => '') + $expenseItemIdOptions);
                    
                    $form->setDefault('fromAccId', '61102');
                    $form->setReadOnly('fromAccId');
                } elseif($data->masterRec->inputServicesFrom == 'all'){
                    if(empty($rec->id)){
                        $form->setDefault('fromAccId', '61102');
                    }
                }
            }
        }
        
        if ($rec->type == 'pop') {
            $form->setFieldTypeParams('productId', array('groups' => cat_Groups::getKeylistBySysIds('waste')));
            $form->setField('storeId', 'input=none');
        } elseif($rec->type == 'input'){

            // Ако ПП е в нишка на финална ПО и се произвежда друг артикул - то само този от заданието да може да се избира
            $origin = doc_Threads::getFirstDocument($data->masterRec->threadId);
            if($origin->isInstanceOf('planning_Tasks')){
                $originRec = $origin->fetch('isFinal,productId');
                if($originRec->isFinal == 'yes' && $data->masterRec->productId != $jobRec->productId){
                    $form->setFieldTypeParams('productId', array('onlyIn' => array($jobRec->productId)));
                }
            }
        } elseif ($rec->type == 'subProduct') {
            $form->setDefault('storeId', $data->masterRec->storeId);
            $form->setField('storeId', 'caption=Засклаждане в,mandatory,placeholder=Изберете склад');
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
            $noteRec = planning_DirectProductionNote::fetch($rec->noteId, 'productId,packagingId,threadId');
            if($rec->type == 'pop'){
                
                // Ако отпадъка ще е произведения артикул, само мярката в която е произведен ще е позволена
                if($rec->productId == $noteRec->productId){
                    $form->rec->_onlyAllowedPackId = $noteRec->packagingId;
                }
            }
            
            if ($form->isSubmitted()) {
                if($rec->_isStorable && empty($rec->storeId) && $rec->type != 'pop'){
                    if(!planning_ConsumptionNotes::existActivatedInThread($noteRec->threadId, $rec->productId)){
                        $form->setWarning('storeId', "В Заданието(и операциите към него) няма контиран Протокол за влагане с посочения артикул, а е избрано влагане от Незавършено производство! Ако няма предварително (общо) влагане - изберете склад за изписване на материала!");
                    }
                }

                // Проверка на к-то
                $warning = null;
                if (!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)) {
                    $form->setWarning('packQuantity', $warning);
                }
                
                // Ако добавяме отпадък, искаме да има себестойност
                if (in_array($rec->type, array('pop', 'subProduct'))) {
                    if($noteRec->productId == $rec->productId){
                        $form->setError('productId', "Избраният артикул не може да е същия, като този който се произвежда с протокола");
                    }

                    $selfValue = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $rec->productId);
                    if (!isset($selfValue)) {
                        $error = $rec->type == 'pop' ? 'Отпадъкът няма себестойност' : 'Субпродуктът няма себестойност';
                        $form->setError('productId', $error);
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
        if (!countR($data->recs)) return;

        // Кои са получените чужди артикули
        foreach ($data->rows as $id => &$row) {
            $rec = &$data->recs[$id];
            $row->ROW_ATTR['class'] = ($rec->type == 'pop') ? 'row-removed' : (($rec->type == 'allocated') ? 'state-active' : ($rec->type == 'input' ? 'row-added' : 'row-subProduct'));

            if (isset($rec->storeId)) {
                $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
            }
            
            if (in_array($rec->type, array('pop', 'subProduct'))) {
                $row->packQuantity .= " {$row->packagingId}";
            }

            if($rec->isOutsourced == 'yes'){
                if(!Mode::isReadOnly()) {
                    $row->productId = ht::createHint($row->productId, 'Артикулът е отбелязан да се влага като ишлеме', 'noicon', false);
                }
            }

            if(!empty($rec->expenseItemId)){
                $itemLink = acc_Items::getVerbal($rec->expenseItemId, 'titleLink');
                $row->productId = new core_ET($row->productId);
                $row->productId->append("<br><small><span class='quiet'>" . tr('Раз. обект') . "</span>: {$itemLink}</small>");
            }
        }
    }
    
    
    /**
     * След подготовка на детайлите, изчислява се общата цена
     * и данните се групират
     */
    protected static function on_AfterPrepareDetail($mvc, $res, $data)
    {
        $data->inputArr = $data->popArr = $data->subProductArr = array();
        $countInputed = $countPoped = $countSubProducts = 1;
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
                } elseif($rec->type == 'subProduct') {
                    $num = $Int->toVerbal($countSubProducts);
                    $data->subProductArr[$id] = $row;
                    $countSubProducts++;
                }  else {
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

        $masterRec = $data->masterData->rec;
        $workInProgressRecs = array();
        foreach ($data->rows as $id => &$row) {
            $rec = $data->recs[$id];
            if (empty($rec->storeId)) {
                if($rec->type == 'subProduct'){
                    $row->storeId = ht::createHint("<span class='red'>n/a</span>", 'Изберете склад, за да може да се контира документа!', 'error', false);
                } else {
                    $emptyPlaceholder = tr('Незавършено производство');
                    if(!empty($rec->fromAccId)){
                        $emptyPlaceholder = tr('Разходи за услуги (без влагане)');
                    } elseif($rec->type == 'input') {
                        $workInProgressRecs[$rec->id] = $rec;
                    }

                    $row->storeId = "<span class='quiet'>{$emptyPlaceholder}</span>";
                }
            } elseif(!in_array($rec->type, array('pop', 'subProduct'))) {
                $threadId = $origin->fetchField('threadId');
                $deliveryDate = (!empty($masterRec->deadline)) ? $masterRec->deadline : $masterRec->valior;
                deals_Helper::getQuantityHint($row->packQuantity, $this, $rec->productId, $rec->storeId, $rec->quantity, $masterRec->state, $deliveryDate, $threadId);
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

        // Проверка дали ще има проблем с количествата в незавършеното производство
        if(countR($workInProgressRecs)){
            if(in_array($data->masterData->rec->state, array('pending', 'draft'))){
                planning_WorkInProgress::applyQuantityHintIfNegative($data->rows, $workInProgressRecs);
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

        if (Mode::is('printing')) {
            unset($data->listFields['tools']);
        }
        
        // Рендираме таблицата с вложените материали
        $data->listFields['productId'] = 'Вложени артикули|* ';
        $firstDoc = doc_Threads::getFirstDocument($data->masterData->rec->threadId);
        if($firstDoc->isInstanceOf('planning_Tasks')){
            $firstDocRec = $firstDoc->fetch('isFinal,productId');
            if($firstDocRec->isFinal == 'no') return new $tpl;
        }

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
            $iData->listFields['packQuantity'] = 'К-во';
        }
        
        if(isset($iData->listFields['quantityFromBom'])){
            $iData->listFields['quantityFromBom'] = 'К-во->|*<small>|Рецепта|*</small>';
        }
        
        if(isset($iData->listFields['quantityExpected'])){
            $iData->listFields['quantityExpected'] = 'К-во->|*<small>|Очаквано|*</small>';
        }
        
        $this->modifyRows($iData);
        $detailsInput = $table->get($iData->rows, $iData->listFields);

        $tpl->append($detailsInput, 'INPUTED_PRODUCTS_TABLE');
        
        // Добавяне на бутон за нов материал
        if ($this->haveRightFor('add', (object) array('noteId' => $data->masterId, 'type' => 'input'))) {
            $tpl->append(ht::createBtn('Влагане', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'input', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/wooden-box.png', 'title' => 'Добавяне на нов материал')), 'INPUTED_PRODUCTS_TABLE');
        }
        if ($this->haveRightFor('import', (object) array('noteId' => $data->masterId, 'type' => 'input'))) {
            $tpl->append(ht::createBtn('Импортиране', array($this, 'import', 'noteId' => $data->masterId, 'type' => 'input', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/import.png', 'title' => 'Добавяне на нов материал')), 'INPUTED_PRODUCTS_TABLE');
        }
        
        if ($this->haveRightFor('add', (object) array('noteId' => $data->masterId, 'type' => 'allocated'))) {
            $tpl->append(ht::createBtn('Разходи', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'allocated', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/money.png', 'title' => 'Влагане на отнесен разход')), 'INPUTED_PRODUCTS_TABLE');
        }

        if($this->haveRightFor('selectrowstodelete', (object)array("noteId" => $data->masterId, '_filterFld' => 'type', '_filterFldVal' => 'input'))){
            $tpl->append(ht::createBtn('Изтриване', array($this, 'selectRowsToDelete', "noteId" => $data->masterId, '_filterFld' => 'type', '_filterFldVal' => 'input', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/delete.png', 'title' => 'Форма за избор на редове за изтриване', 'class' => 'selectDeleteRowsBtn')), 'INPUTED_PRODUCTS_TABLE');
        }
        
        if($firstDoc->isInstanceOf('planning_Tasks')) return $tpl;

        if($firstDoc->isInstanceOf('planning_Tasks')) return $tpl;

        // Рендиране на таблицата с отпадъците
        foreach (array('subProduct', 'pop') as $type){
            $arr = $data->{"{$type}Arr"};
            if (countR($arr) || $data->masterData->rec->state == 'draft') {
                $data->listFields['productId'] = ($type == 'subProduct') ? 'Субпродукти' : "Отпадъци|* <small style='font-weight:normal'>( |остават в незавършеното производство|* )</small>";
                if($type == 'pop'){
                    unset($data->listFields['storeId']);
                } else {
                    $data->listFields['storeId'] = 'Засклаждане в';
                }

                $pData = clone $data;
                $pData->listTableMvc = clone $this;
                $pData->rows = $arr;
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
                $placeholder = $type == 'subProduct' ? 'SUB_PRODUCTS_TABLE' : 'WASTE_PRODUCTS_TABLE';
                $tpl->append($detailsPop, $placeholder);
                $btnTitle = ($type == 'pop') ? 'Отпадък' : 'Субпродукт';

                // Добавяне на бутон за нов отпадък
                if ($this->haveRightFor('add', (object) array('noteId' => $data->masterId, 'type' => 'pop'))) {
                    $icon = ($type == 'pop') ? 'recycle.png' : 'door_in.png';
                    $tpl->append(ht::createBtn($btnTitle, array($this, 'add', 'noteId' => $data->masterId, 'type' => $type, 'ret_url' => true), null, null, array('style' => 'margin-top:5px;;margin-bottom:10px;', 'ef_icon' => "img/16/{$icon}", 'title' => "Добавяне на нов " . mb_strtolower($btnTitle))), $placeholder);
                }

                if($this->haveRightFor('selectrowstodelete', (object)array("noteId" => $data->masterId, '_filterFld' => 'type', '_filterFldVal' => $type))){
                    $tpl->append(ht::createBtn("Изтриване", array($this, 'selectRowsToDelete', "noteId" => $data->masterId, '_filterFld' => 'type', '_filterFldVal' => $type, 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/delete.png', 'title' => "Форма групово изтриване на|* " . mb_strtolower($btnTitle), 'class' => 'selectDeleteRowsBtn')), $placeholder);
                }
            }
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
        if($action == 'add' && isset($rec->type)){
            $jobRec = planning_DirectProductionNote::getJobRec($rec->noteId);
            if($rec->type == 'allocated'){
                $jobTaskCostObjectArr = planning_Jobs::getTaskCostObjectItems($jobRec);
                if(!countR($jobTaskCostObjectArr)){
                    $requiredRoles = 'no_one';
                }
            }
        }

        if($action == 'import'){
            // За да импортира някой, трябва да може да добавя
            $requiredRoles = $mvc->getRequiredRoles('add', $rec, $userId);
        }

        if(in_array($action, array('add', 'import')) && isset($rec)){
            if($requiredRoles != 'no_one'){

                // Ако детайла е в ПП в нишка на финална операция
                $jobRec = planning_DirectProductionNote::getJobRec($rec->noteId);
                $masterRec = planning_DirectProductionNote::fetch($rec->noteId);
                $origin = doc_Containers::getDocument($masterRec->originId);
                if($origin->isInstanceOf('planning_Tasks')){
                    $originRec = $origin->fetch('isFinal,productId');
                    if($originRec->isFinal == 'no'){
                        $requiredRoles = 'no_one';
                    } elseif($masterRec->productId != $jobRec->productId && $rec->type != 'input'){
                        $requiredRoles = 'no_one';
                    } elseif($action == 'import'){
                        $requiredRoles = 'no_one';
                    } elseif($rec->type == 'input'){
                        $isConvertable = cat_Products::fetchField($jobRec->productId, 'canConvert');
                        if($isConvertable != 'yes'){
                            $requiredRoles = 'no_one';
                        }
                    }
                }
            }
        }

        if($action == 'delete' && isset($rec)){
            if(!empty($rec->quantityFromBom) || !empty($rec->quantityExpected)){
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Изпълнява се преди клониране
     */
    protected static function on_BeforeSaveClonedDetail($mvc, &$rec, $oldRec)
    {
        // При клониране да се пропуска прогнозния отпадъка посочен в операцията (той ще се запише при активиране)
        $newTaskQuantity = planning_DirectProductionNote::fetchField($rec->noteId, 'quantity');
        $oldTaskQuantity = planning_DirectProductionNote::fetchField($oldRec->noteId, 'quantity');

        $q = $oldRec->quantity / $oldTaskQuantity;
        $measureId = cat_Products::fetchField($rec->productId, 'measureId');
        $round = cat_UoM::fetchField($measureId, 'round');
        $rec->quantity = round($q * $newTaskQuantity, $round);

        if(!empty($rec->quantityFromBom)){
            $q1 = $oldRec->quantityFromBom / $oldTaskQuantity;
            $rec->quantityFromBom = round($q1 * $newTaskQuantity, $round);
        }
    }


    /**
     * След подготовка на заявката за синхронизиране на детайлите със зоните
     * @see rack_plg_Shipments
     *
     * @param core_Mvc $mvc
     * @param stdClass $masterRec
     * @param core_Query $query
     * @return void
     */
    protected static function on_AfterGetZoneSummaryQuery($mvc, $masterRec, &$query)
    {
        $query->where("#storeId = '{$masterRec->inputStoreId}' AND #type = 'input'");
    }


    /**
     * Извиква се преди запис в модела
     */
    protected static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if(isset($rec->id)){
            $rec->_oldStoreId = $mvc->fetchField($rec->id, 'storeId', false);
        }
    }


    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        if(isset($rec->_oldStoreId) && $rec->_oldStoreId != $rec->storeId){
            if(core_Packs::isInstalled('batch')){
                $bQuery = batch_BatchesInDocuments::getQuery();
                $bQuery->where("#detailClassId = {$mvc->getClassId()} AND #detailRecId = {$rec->id} AND #storeId = {$rec->_oldStoreId}");
                if(isset($rec->storeId)){
                    while($bRec = $bQuery->fetch()){
                        $bRec->storeId = $rec->storeId;
                        batch_BatchesInDocuments::save($bRec, 'storeId');
                    }
                } else {
                    $bQuery->delete();
                }
            }
        }
    }


    /**
     * Метод по реализация на определянето на движението генерирано от реда
     *
     * @param stdClass $rec
     *
     * @return string
     */
    public function getBatchMovementDocument($rec)
    {
        return $rec->type == 'subProduct' ? 'in' : 'out';
    }
}
