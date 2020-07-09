<?php


/**
 * Контролер на терминала за пос продажби
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg> 
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pos_Terminal extends peripheral_Terminal
{
    /**
     * Заглавие
     */
    public $title = 'ПОС Терминал';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Printing';
    
    
    /**
     * Име на източника
     */
    protected $clsName = 'pos_Points';
    
    
    /**
     * Полета
     */
    protected $fieldArr = array('payments', 'policyId', 'caseId', 'storeId');
    
    
    /**
     * Кои операции са забранени за нови бележки
     */
    protected static $forbiddenOperationOnEmptyReceipts = array('text', 'quantity');
    
    
    /**
     * Кои операции са забранени за бележки с направено плащане
     */
    protected static $forbiddenOperationOnReceiptsWithPayment = array('quantity', 'add', 'text');
    
    
    
    /**
     * Кои операции са забранени за сторниращите бележки
     */
    protected static $allowedOperationOnNonDraftReceipts = 'receipts=Бележки,revert=Сторно,payment=Плащане';
    
    
    /**
     * Бутони за бърз достъп до терминала
     */
    protected static $operationShortcuts = 'operation-add=Ctrl A,operation-payment=Ctrl Z,operation-quantity=Ctrl S,operation-text=Ctrl E,operation-contragent=Ctrl K,operation-receipts=Ctrl B,enlarge=F2,print=Ctrl P,keyboard=Ctrl M,exit=Ctrl Q,reject=Ctrl O,help=F1,delete=Ctrl I';

    /**
     * Кои са разрешените операции
     */
    protected static $operationsArr = "add=Добавяне на артикул,quantity=Промяна на реда,payment=Плащане по бележката,contragent=Избор на контрагент,text=Текст,receipts=Търсене на бележка";


    /**
     * Икони за операциите
     */
    protected static $operationImgs = array('enlarge' => 'pos/img/search.png', 'print' => 'pos/img/printer.png', 'keyboard' => 'pos/img/keyboard.png', 'operation-add' => 'pos/img/а.png', 'operation-text' =>  'pos/img/comment.png', 'operation-payment' => 'pos/img/dollar.png', 'operation-quantity' => 'pos/img/multiply.png',  'operation-add' => 'pos/img/a.png',  'operation-receipts' => 'pos/img/receipt.png', 'operation-contragent' => 'pos/img/user.png', 'close' => 'pos/img/close.png', 'transfer' => 'pos/img/transfer.png', 'reject' => 'pos/img/cancel.png', 'delete' => 'pos/img/delete.png', 'help' => "pos/img/info.png");

    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('payments', 'keylist(mvc=cond_Payments, select=title)', 'caption=Безналични начини на плащане->Позволени,placeholder=Всички');
        $fieldset->FLD('policyId', 'key(mvc=price_Lists, select=title)', 'caption=Политика, silent, mandotory');
        $fieldset->FLD('caseId', 'key(mvc=cash_Cases, select=name)', 'caption=Каса, mandatory');
        $fieldset->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Склад, mandatory');
    }
    
    
    /**
     * След подготовка на формата за добавяне
     *
     * @param core_Fieldset $fieldset
     */
    protected static function on_AfterPrepareEditForm($Driver, embed_Manager $Embedder, &$data)
    {
        $data->form->setDefault('policyId', cat_Setup::get('DEFAULT_PRICELIST'));
    }
    
    
    /**
     * Редиректва към посочения терминал в посочената точка и за посочения потребител
     *
     * @return Redirect
     *
     * @see peripheral_TerminalIntf
     */
    public function getTerminalUrl($pointId)
    {
        return array('pos_Points', 'openTerminal', $pointId);
    }
    
    
    /**
     * Отваряне на бележка в терминала
     * 
     * @return core_ET
     */
    public function act_Open()
    {
        $Receipts = cls::get('pos_Receipts');
        $Receipts->requireRightFor('terminal');
        expect($id = Request::get('receiptId', 'int'));
        expect($rec = $Receipts->fetch($id));
        
        // Ако се отваря нова бележка нулира се в сесията запомненото
        if(Request::get('opened', 'int')){
            $redirectUrl = getCurrentUrl();
            unset($redirectUrl['opened']);
            $defaultOperation = ($rec->state != 'draft') ? 'receipts' : (empty($rec->paid) ? 'add' : 'payment');
            Mode::setPermanent("currentOperation{$id}", $defaultOperation);
            Mode::setPermanent("currentSearchString{$id}", null);
            redirect($redirectUrl);
        }
        
        // Имаме ли достъп до терминала
        if (!$Receipts->haveRightFor('terminal', $rec)) {
            
            return new Redirect(array($Receipts, 'new'));
        }
        
        // Автоматично избиране на касата на бележката за текуща
        pos_Points::selectCurrent($rec->pointId);
        $tpl = getTplFromFile('pos/tpl/terminal/Layout.shtml');
        $tpl->replace(pos_Points::getTitleById($rec->pointId), 'PAGE_TITLE');
        $tpl->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf('img/16/cash-register.png', '"', true) . '>', 'HEAD');
        $tpl->replace($this->renderHeader($rec), 'HEADER_BAR');
        
        // Добавяме бележката в изгледа
        $receiptTpl = $this->getReceipt($rec);
        $tpl->replace($receiptTpl, 'RECEIPT');
        
        // Ако не сме в принтиране, сменяме обвивквата и рендираме табовете
        if (!Mode::is('printing')) {
            
            // Задаване на празна обвивка
            Mode::set('wrapper', 'page_Empty');
            $defaultOperation = Mode::get("currentOperation{$rec->id}") ? Mode::get("currentOperation{$rec->id}") : (($rec->state == 'draft') ? 'add' : 'receipts');
            Mode::setPermanent("currentOperation{$rec->id}", $defaultOperation);
            
            $defaultSearchString = Mode::get("currentSearchString{$rec->id}");
            if(!Mode::is('printing')){
                
                // Добавяне на табовете под бележката
                $toolsTpl = $this->getCommandPanel($rec);
                $tpl->replace($toolsTpl, 'TAB_TOOLS');
                
                // Добавяне на табовете показващи се в широк изглед отстрани
                $lastRecId = pos_ReceiptDetails::getLastRec($rec->id)->id;
                $resultTabHtml = $this->renderResult($rec, $defaultOperation, $defaultSearchString, $lastRecId);
                $tpl->append($resultTabHtml, 'SEARCH_RESULT');
            }
        } else {
            $tpl->append('не се дължи плащане', 'PAYMENT_NOT_REQUIRED');
        }
        
        $data = (object) array('rec' => $rec);
        $this->invoke('AfterRenderSingle', array(&$tpl, $data));
        
        // Вкарване на css и js файлове
        $this->pushTerminalFiles($tpl, $rec);
        $this->renderWrapping($tpl);
        
        return $tpl;
    }
    
    
    /**
     * Рендиране на горния бар
     *
     * @param stdClass $rec
     *
     * @return core_ET $tpl
     */
    private function renderHeader($rec)
    {
        $Receipts = cls::get('pos_Receipts');
        $rec = $Receipts->fetchRec($rec);
        $tpl = getTplFromFile('pos/tpl/terminal/Header.shtml');
        
        $headerData = (object)array('APP_NAME' => EF_APP_NAME,
                                    'pointId' => pos_Points::getVerbal($rec->pointId, 'name'),
                                    'ID' => pos_Receipts::getVerbal($rec->id, 'id'),
                                    'TIME' => $this->renderCurrentTime(),
                                    'valior' => pos_Receipts::getVerbal($rec->id, 'valior'),
                                    'userId' => core_Users::getVerbal(core_Users::getCurrent(), 'nick'));
        
        $defaultContragentId = pos_Points::defaultContragent($rec->pointId);
        $contragentName = ($rec->contragentClass == crm_Persons::getClassId() && $defaultContragentId == $rec->contragentObjectId) ? null : cls::get($rec->contragentClass)->getTitleById($rec->contragentObjectId);
        $headerData->contragentId = (!empty($rec->transferedIn)) ? sales_Sales::getLink($rec->transferedIn, 0, array('ef_icon' => false)) : $contragentName;
       
        $img = ht::createImg(array('path' => 'img/16/bgerp.png'));
        $logoTpl = new core_ET("[#img#] [#logo#]");
        $logoTpl->replace($img, 'img');
        $logoTpl->replace($Receipts->getTerminalHeaderLogo($rec), 'logo');
        $logoLink = ht::createLink($logoTpl, array('bgerp_Portal', 'show'));
        
        $tpl->append($logoLink, 'OTHER_ELEMENTS');
        $tpl->placeObject($headerData);        
        $Receipts->invoke('AfterRenderTerminalHeader', array(&$tpl, $rec));
        
        return $tpl;
    }
    
    
    /**
     * Рендира текущата дата
     * 
     * @return core_ET 
     */
    private function renderCurrentTime()
    {
        return new core_ET(dt::mysql2verbal(dt::now()));
    }
    
    
    /**
     * Увеличаване на избрания артикул
     * 
     * @return array $res
     */
    public function act_EnlargeElement()
    {
        $enlargeClassId = Request::get('enlargeClassId', 'int');
        $enlargeObjectId = Request::get('enlargeObjectId', 'int');
        $receitpId = Request::get('id', 'int');
        
        if(empty($enlargeClassId) || empty($enlargeObjectId)) {
            
            return array();
        }
        
        $EnlargeClass = cls::get($enlargeClassId);
        $receiptRec = pos_Receipts::fetch($receitpId);
        
        switch ($enlargeClassId){
            case cat_Products::getClassId():
                $productRec = cat_Products::fetch($enlargeObjectId);
                $modalTpl = getTplFromFile('pos/tpl/terminal/SingleLayoutProductModal.shtml');
                if($productRec->canSell != 'yes'){
                    $modalTpl->replace(tr('спрян'), 'STOPPED_PRODUCT');
                }
                
                Mode::push('text', 'xhtml');
                $packData = (object)array('masterMvc' => cls::get('cat_Products'), 'masterId' => $enlargeObjectId);
                cls::get('cat_products_Packagings')->preparePackagings($packData);
                unset($packData->listFields['user']);
               
                $packagingTpl = cls::get('cat_products_Packagings')->renderPackagings($packData);
                $modalTpl->append($packagingTpl, 'Packagings');
                Mode::pop();
                
                $Policy = cls::get('price_ListToCustomers');
                $price = $Policy->getPriceInfo($receiptRec->contragentClass, $receiptRec->contragentObjectId, $productRec->id, $productRec->measureId, 1, $receiptRec->createdOn, 1, 'yes');
                $Double = core_Type::getByName('double(decimals=2)');
                
                $row = new stdClass();
                $row->price = currency_Currencies::decorate($Double->toVerbal($price->price));
                $row->measureId = cat_UoM::getVerbal($productRec->measureId, 'name');
                $row->info = cat_Products::getVerbal($productRec, 'info');
                
                $pRow = cat_Products::recToVerbal($productRec);
                $row->salePrice = $pRow->salePrice;
                $row->maxSaleDiscount = $pRow->maxSaleDiscount;
                $row->deliveryPrice = $pRow->deliveryPrice;
                $row->storePlace = $pRow->storePlace;
                
                if ($productRec->canStore == 'yes') {
                    $stores = pos_Points::getStores($receiptRec->pointId);
                    $row->INSTOCK = '';
                    
                    foreach ($stores as $storeId){
                        $block = clone $modalTpl->getBlock('INSTOCK_BLOCK');
                        $storeRow = (object)array('storeId' => store_Stores::getTitleById($storeId));
                        
                        $quantity = pos_Stocks::fetchField("#storeId = '{$storeId}' AND #productId = '{$productRec->id}'", 'quantity');
                        $quantity = isset($quantity) ? $quantity : 0;
                        $reservedQuantity = store_Products::fetchField("#storeId = {$storeId} AND #productId = {$productRec->id}", 'reservedQuantity');
                        $free = $quantity;
                        
                        $inStockVerbal = core_Type::getByName('double(smartRound)')->toVerbal($quantity);
                        $inStockVerbal = ht::styleIfNegative($inStockVerbal, $quantity);
                        $storeRow->inStock = $inStockVerbal;
                        
                        if(!empty($reservedQuantity) && $quantity){
                            $reservedQuantityVerbal = core_Type::getByName('double(smartRound)')->toVerbal($reservedQuantity);
                            $reservedQuantityVerbal = ht::styleIfNegative($reservedQuantityVerbal, $reservedQuantity);
                            $storeRow->reserved = $reservedQuantityVerbal;
                            $free -= $reservedQuantity;
                        }
                        
                        $freeVerbal = core_Type::getByName('double(smartRound)')->toVerbal($free);
                        $freeVerbal = ht::styleIfNegative($freeVerbal, $free);
                        $storeRow->free = $freeVerbal;
                        
                        $block->placeObject($storeRow);
                        $row->INSTOCK .= $block->getContent();
                    }
                }
                
                $row->preview = $this->getPosProductPreview($productRec->id, 400, 400);
                $name = cat_Products::getTitleById($productRec->id);
                if(mb_strlen($name) > 60) {
                    $row->name = cat_Products::getTitleById($productRec->id);
                }
                
                $modalTpl->placeObject($row);
                $params = cat_Products::getParams($productRec->id, null, true);
                $block = $modalTpl->getBlock('PARAM_BLOCK');
                foreach ($params as $paramId => $paramValue){
                    $suffix = cat_Params::fetchField($paramId, 'suffix');
                    if(!empty($suffix)){
                        $paramValue .= " {$suffix}";
                    }
                    
                    $blockClone = clone $block;
                    $blockClone->append(tr(cat_Params::getTitleById($paramId)), 'paramCaption');
                    $blockClone->append($paramValue, 'paramValue');
                    $blockClone->removeBlocksAndPlaces();
                    $modalTpl->append($blockClone, 'PARAMETERS');
                }
                
                // Добавяне на бутон за спиране/пускане
                $btnTitle = ($productRec->canSell == 'yes') ? 'Спиране' : 'Пускане';
                $className = ($productRec->canSell == 'yes') ? 'offBtn' : 'onBtn';
                Request::setProtected('Selected');
                $changeMetaUrl = (cat_Products::haveRightFor('edit', $productRec->id)) ? array('cat_Products', 'changemeta', 'Selected' => $productRec->id, 'toggle' => 'canSell', 'ret_url' => array('pos_Terminal', 'open', 'receiptId' => $receitpId)) : array();
                $warning = ($productRec->canSell == 'yes') ? 'Наистина ли желаете да спрете артикула от продажба|*?' : 'Наистина ли желаете да пуснете артикула в продажба|*?';
                $warning = countR($changeMetaUrl) ? $warning : false;
                $btn = ht::createBtn($btnTitle,  $changeMetaUrl, $warning, null, "class=actionBtn {$className}");
                Request::removeProtected('Selected');
                $modalTpl->append($btn, 'TOOLBAR');
                
                break;
            case pos_Receipts::getClassId():
                $modalTpl =  $this->getReceipt($enlargeObjectId);
                $modalTpl->prepend('<div class="modalReceipt">');
                $modalTpl->append('</div>');
                break;
            default:
                $singleLayoutFile = ($EnlargeClass instanceof crm_Companies) ? 'pos/tpl/terminal/SingleLayoutCompanyModal.shtml' : (($EnlargeClass instanceof crm_Persons) ? 'pos/tpl/terminal/SingleLayoutPersonModal.shtml' : 'pos/tpl/terminal/SingleLayoutLocationModal.shtml');
                
                Mode::push('text', 'xhtml');
                Mode::push('noWrapper', true);
                Mode::push("singleLayout-{$EnlargeClass->className}{$enlargeObjectId}", getTplFromFile($singleLayoutFile));
                $modalTpl = Request::forward(array('Ctr' => $EnlargeClass->className, 'Act' => 'single', 'id' => $enlargeObjectId));
                Mode::pop("singleLayout-{$EnlargeClass->className}{$enlargeObjectId}");
                Mode::pop('noWrapper');
                Mode::pop('text');
        }
        
        // Ще се реплейсва и пулта
        $res = array();
        $resObj = new stdClass();
        $resObj->func = 'html';
        $resObj->arg = array('id' => 'modalContent', 'html' => $modalTpl->getContent(), 'replace' => true);
        $res[] = $resObj;
        
        return $res;
    }
    
    
    /**
     * Пълна клавиатура
     *
     * @return array $res
     */
    public function act_Help()
    {
        $tpl = getTplFromFile('pos/tpl/terminal/Help.shtml');

        for($i = 1; $i<=12; $i++) {
            $tpl->replace( ht::createElement('img', array('src' => sbf("pos/img/btn{$i}.png", ''))), "img{$i}");
        }

        
        // Ще се реплейсва и пулта
        $res = array();
        $resObj = new stdClass();
        $resObj->func = 'html';
        $resObj->arg = array('id' => 'modalContent', 'html' => $tpl->getContent(), 'replace' => true);
        $res[] = $resObj;
        
        return $res;
    }
    
    
    /**
     * Създава нова форма фирма и прехвърля с нея
     */
    public function act_TransferInNewCompany()
    {
        pos_Receipts::requireRightFor('terminal');
        pos_Receipts::requireRightFor('transfer');
        $receiptId = core_Request::get('receiptId', 'int');
        $rec = pos_Receipts::fetch($receiptId);
        pos_Receipts::requireRightFor('terminal', $rec);
        pos_Receipts::requireRightFor('transfer', $rec);
        crm_Companies::requireRightFor('add');
        
        $Companies = cls::get('crm_Companies');
        $data = (object)array('action' => 'manage', 'cmd' => 'add');
        $Companies->prepareEditForm($data);
        $data->form->setAction(array($this, 'TransferInNewCompany', 'receiptId' => $rec->id));
        $data->form->setField('inCharge', 'autohide=any');
        $data->form->setField('access', 'autohide=any');
        $data->form->setField('shared', 'autohide=any');
        $data->form->title = 'Създаване на нова фирма';
        
        // Събмитване на формата
        $data->form->input();
        $Companies->invoke('AfterInputEditForm', array($data->form));
        if ($data->form->isSubmitted()) {
            $companyRec = $data->form->rec;
            $Companies->save($companyRec);
            
            $rec->contragentClass = $Companies->getClassId();
            $rec->contragentObjectId = $companyRec->id;
            $rec->contragentName = cls::get($rec->contragentClass)->getVerbal($rec->contragentObjectId, 'name');
            pos_Receipts::save($rec, 'contragentObjectId,contragentClass,contragentName');
            
            redirect(array('pos_Terminal', 'open', 'receiptId' => $rec->id));
        }
        
        $data->form->toolbar->addSbBtn('Запис', 'save', 'id=save, ef_icon = img/16/disk.png', 'title=Запис на нова фирма');
        $data->form->toolbar->addBtn('Отказ', getRetUrl(), 'id=cancel, ef_icon = img/16/close-red.png', 'title=Прекратяване на действията');
        
        $content = $data->form->renderHtml();
        $content = cls::get('crm_Companies')->renderWrapping($content);
        
        return $content;
    }
    
    
    /**
     * Пълна клавиатура
     *
     * @return array $res
     */
    public function act_Keyboard()
    {
        $string = Request::get('string', 'varchar');
        $tpl = getTplFromFile('pos/tpl/terminal/KeyboardFull.shtml');
        $tpl->replace($string, 'STRING');
        
        // Ще се реплейсва и пулта
        $res = array();
        $resObj = new stdClass();
        $resObj->func = 'html';
        $resObj->arg = array('id' => 'modalContent', 'html' => $tpl->getContent(), 'replace' => true);
        $res[] = $resObj;
        
        return $res;
    }
    
    
    /**
     * Подготвяне на контролния панел
     * 
     * @param stdClass $rec
     * @return core_ET
     */
    private function getCommandPanel($rec)
    {
        $Receipts = cls::get('pos_Receipts');
        expect($rec = $Receipts->fetchRec($rec));
        
        $block = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('TAB_TOOLS');
        $operation = Mode::get("currentOperation{$rec->id}");
        $keyupUrl = array($this, 'displayOperation', 'receiptId' => $rec->id, 'refreshPanel' => 'no');
        $buttons = array();
        
        switch($operation){
            case 'add':
                $inputUrl = array('pos_ReceiptDetails', 'dispatch', 'receiptId' => $rec->id);
                if(isset($rec->revertId) && $rec->revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT){
                    $keyupUrl = null;
                }
                break;
            case 'quantity':
                $inputUrl = array('pos_ReceiptDetails', 'dispatch', 'receiptId' => $rec->id);
                $keyupUrl = null;
                break;
            case 'text':
                $inputUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'settext');
                $keyupUrl = null;
                break;
            case 'payment':
                $keyupUrl = null;
                break;
            case 'contragent':
                break;
            case 'receipts':
                break;
        }
        
        if(is_array($inputUrl)){
            $inputUrl = toUrl($inputUrl, 'local');
        }
        
        if(is_array($keyupUrl)){
            $keyupUrl = toUrl($keyupUrl, 'local');
        }
        
        $value = round(abs($rec->total) - abs($rec->paid), 2);
        $value = ($value > 0) ? $value : null;
        $inputValue = ($operation != 'payment') ? Mode::get("currentSearchString{$rec->id}") : null;
        
        $searchUrl = toUrl(array($this, 'displayOperation', 'receiptId' => $rec->id), 'local');
        $params = array('name' => 'ean', 'value' => $inputValue, 'type' => 'text', 'class'=> 'large-field select-input-pos', 'data-url' => $inputUrl, 'data-keyupurl' => $keyupUrl, 'data-defaultpayment' => $value, 'title' => 'Въвеждане', 'list' => 'suggestions', 'autocomplete' => 'off');
        if(Mode::is('screenMode', 'narrow')) {
            $params['readonly'] = 'readonly';
        }
        
        $operations = arr::make(self::$operationsArr);
        $allowedOperationsForNonDraftReceipts = arr::make(self::$allowedOperationOnNonDraftReceipts);
        $detailsCount = pos_ReceiptDetails::count("#receiptId = {$rec->id}");
        
        // Ако записаната операция в сесията я няма, то се избира първата възможна автоматично
        if(!array_key_exists($operation, $operations)){
            Mode::setPermanent("currentOperation{$rec->id}", key($operations));
        }
        
        // Показване на възможните операции
        $currentOperation = Mode::get("currentOperation{$rec->id}");
        $shortCuts = arr::make(static::$operationShortcuts);
        
        foreach ($operations as $operation => $operationCaption){
            $class = ($operation == $currentOperation) ? 'operationBtn active' : 'operationBtn';
            $attr = array('data-url' => $searchUrl, 'class' => $class, 'data-value' => $operation, 'title' => $operationCaption);
            $disabled = (empty($detailsCount) && in_array($operation, self::$forbiddenOperationOnEmptyReceipts)) || (!empty($rec->paid) && in_array($operation, self::$forbiddenOperationOnReceiptsWithPayment));
            
            if($rec->state != 'draft' && !array_key_exists($operation, $allowedOperationsForNonDraftReceipts)) {
                $disabled = true;
            }
            
            if($disabled){
                $attr['data-url'] = null;
                $attr['class'] .= ' disabledBtn';
                $attr['disabled'] = 'disabled';
            }
            
            $img = ht::createImg(array('path' => self::$operationImgs["operation-{$operation}"]));
            $buttons["operation-{$operation}"] = (object)array('body' => $img, 'attr' => $attr);
        }
        
        // Бутон за увеличение на избрания артикул
        $enlargeAttr = array('title' => 'Преглед на избрания елемент', 'data-url' => toUrl(array('pos_Terminal', 'enlargeElement', $rec->id), 'local'), 'class' => "enlargeProductBtn");
        $img = ht::createImg(array('path' => self::$operationImgs["enlarge"]));
        $buttons["enlarge"] = (object)array('body' => $img, 'attr' => $enlargeAttr);
        
        // Бутон за печат на бележката
        $img = ht::createImg(array('path' => self::$operationImgs["print"]));
        $buttons["print"] = (object)array('body' => $img, 'attr' => array('title' => 'Печат на бележката', 'class' => 'operationBtn printBtn', 'onclick' => 'location.reload();'), 'linkUrl' => array('pos_Terminal', 'Open', 'receiptId' => $rec->id, 'Printing' => true), 'newWindow' => true);
        
        // Бутон за увеличение на избрания артикул
        $img = ht::createImg(array('path' => self::$operationImgs["keyboard"]));
        $buttons["keyboard"] = (object)array('body' => $img, 'attr' => array('title' => 'Отваряне на виртуална клавиатура', 'data-url' => toUrl(array('pos_Terminal', 'Keyboard'), 'local'), 'class' => "keyboardBtn", 'data-modal-title' => tr('Виртуална клавиатура')));
        
        // Слагаме бутон за оттегляне ако имаме права
        $img = ht::createImg(array('path' => self::$operationImgs["reject"]));
        if (pos_Receipts::haveRightFor('reject', $rec)) {
             $buttons["reject"] = (object)array('body' => $img, 'attr' => array('title' => 'Оттегляне на бележката', 'class' => "rejectBtn"), 'linkUrl' => array('pos_Receipts', 'reject', $rec->id, 'ret_url' => toUrl(array('pos_Receipts', 'new'), 'local')), 'linkWarning' => 'Наистина ли желаете да оттеглите бележката|*?');
        } elseif (pos_Receipts::haveRightFor('delete', $rec)) {
             $img = ht::createImg(array('path' => self::$operationImgs["delete"]));
             $buttons["delete"] = (object)array('body' => $img, 'attr' => array('title' => 'Изтриване на бележката', 'class' => "rejectBtn"), 'linkUrl' => array('pos_Receipts', 'delete', $rec->id, 'ret_url' => toUrl(array('pos_Receipts', 'new'), 'local')), 'linkWarning' => 'Наистина ли желаете да изтриете бележката|*?');
        } elseif(pos_Receipts::haveRightFor('revert', $rec->id)){
            $buttons["delete"] = (object)array('body' => $img, 'attr' => array('title' => 'Сторниране на бележката', 'class' => "rejectBtn"), 'linkUrl' => array('pos_Receipts', 'revert', $rec->id), 'linkWarning' => 'Наистина ли желаете да сторнирате бележката|*?');
        } else {
            $buttons["delete"] = (object)array('body' => $img, 'attr' => array('class' => "rejectBtn disabledBtn", 'disabled' => 'disabled'));
        }
        
        // Бутон за увеличение на избрания артикул
        $img = ht::createImg(array('path' => self::$operationImgs["help"]));
        $buttons["help"] = (object)array('body' => $img, 'attr' => array('title' => 'Отваряне на прозорец с информация', 'data-url' => toUrl(array('pos_Terminal', 'Help'), 'local'), 'class' => "helpBtn", 'data-modal-title' => tr('Информация')));
        
        $logoutImg = ht::createImg(array('path' => 'pos/img/exit.png'));
        $buttons["exit"] = (object)array('body' => $logoutImg, 'attr' => array('class' => 'logout', 'title' => 'Излизане от системата'), 'linkUrl' => array('core_Users', 'logout', 'ret_url' => true));
       
        // Добавяне на бутоните за операции + шорткътите към тях
        foreach ($buttons as $key => $btnObj){
            $btnObj->body->append(ht::createElement('span', array('class' => 'buttonOverlay'), $shortCuts[$key], true));
            
            $holderAttr = $btnObj->attr;
            $holderAttr['class'] .= " operationHolder";
            if(!empty($btnObj->linkUrl) && !empty($btnObj->linkWarning)){
                $holderAttr['class'] .= " btnWithWarning";
            }
            
            // Рендиране на бутоните с операциите
            $btn = ht::createElement('div', $holderAttr, $btnObj->body, true);
            if(!empty($btnObj->linkUrl)){
                $warning = !empty($btnObj->linkWarning) ? $btnObj->linkWarning : false;
                $attr = array();
                if($btnObj->newWindow === true){
                    $attr['target'] = '_blank';
                }
                $btn = ht::createLink($btn, $btnObj->linkUrl, $warning, $attr);
            }
            
            $block->append($btn, 'BTNS');
        }
        
        // Добавяне на полето за търсене и клавиатурата
        $input = ht::createElement('input', $params);
        $holder = ht::createElement('div', array('class' => 'inputHolder'), $input, true);
        $block->append($holder, 'INPUT_FLD');
        
        // Добавяне на цифрова клавиатура
        $numKeyboard = getTplFromFile('pos/tpl/terminal/KeyboardNum.shtml');
        $block->append($numKeyboard, 'KEYBOARDS');
        
        return $block;
    }
    
    
    /**
     * Екшън за показване на текущата операция
     * 
     * @return array
     */
    function act_displayOperation()
    {
        expect($id = Request::get('receiptId', 'int'));
        expect($rec = pos_Receipts::fetch($id));
        expect($operation = Request::get('operation', "enum(" . self::$operationsArr . ")"));
        $refreshPanel = Request::get('refreshPanel', 'varchar');
        $refreshPanel = ($refreshPanel == 'no') ? false : true;
        pos_Receipts::requireRightFor('terminal', $rec);
        
        if($selectedRecId = Request::get('recId', 'int')){
            $selectedRec = pos_ReceiptDetails::fetch($selectedRecId, '*', false);
        }
        
        if(!is_object($selectedRec)){
            $selectedRecId = pos_ReceiptDetails::getLastRec($id)->id;
        }
        
        $string = Request::get('search', 'varchar');
        Mode::setPermanent("currentOperation{$id}", $operation);
        Mode::setPermanent("currentSearchString{$id}", $string);
        
        return static::returnAjaxResponse($rec->id, $selectedRecId, true, false, $refreshPanel, true, null);
    }
    
    
    /**
     * Рендиране на резултатите от операцията
     * 
     * @param stdClass $rec
     * @param string $currOperation
     * @param string $string
     * @param int|null $selectedRecId
     * 
     * @return core_ET
     */
    private function renderResult($rec, $currOperation, $string, $selectedRecId = null)
    {
        $detailsCount = pos_ReceiptDetails::count("#receiptId = {$rec->id}");
        if(empty($detailsCount) && in_array($currOperation, static::$forbiddenOperationOnEmptyReceipts)){
            
            return new core_ET("");
        }
        
        $string = trim($string);
        $selectedRec = isset($selectedRecId) ? pos_ReceiptDetails::fetchRec($selectedRecId) : null;
        
        switch($currOperation){
            case 'add':
                if(isset($rec->revertId) && $rec->revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT){
                    $res = $this->renderRevertReceiptRows($rec, $string, $selectedRec);
                } else {
                    $res = $this->renderResultProducts($rec, $string, $selectedRec);
                }
                break;
            case 'receipts':
                $res = $this->renderResultReceipts($rec, $string, $selectedRec);
                break;
            case 'quantity':
                
                $res = getTplFromFile('pos/tpl/terminal/ResultQuantityBlock.shtml');
                $this->renderResultQuantity($rec, $string, $selectedRec, $res);
                
                if(core_Packs::isInstalled('batch')){
                    $this->renderResultBatches($rec, $string, $selectedRec, $res);
                }
                
                if(pos_Setup::get('TERMINAL_PRICE_CHANGE') == 'yes'){
                    $this->renderResultPrice($rec, $string, $selectedRec, $res);
                    $this->renderResultDiscount($rec, $string, $selectedRec, $res);
                }
                
                break;
            case 'text':
                $res = $this->renderResultText($rec, $string, $selectedRec);
                break;
            case 'payment':
                $res = $this->renderResultPayment($rec, $string, $selectedRec);
                break;
            case 'contragent':
                $res = $this->renderResultContragent($rec, $string, $selectedRec);
                break;
            default:
                $res = " ";
                break;
        }
        
        return new core_ET($res);
    }
    
    
    /**
     * Рендира редовете на бележката, която ще се сторнира
     * 
     * @param stdClass $rec
     * @param string $currOperation
     * @param string $string
     * @param int|null $selectedRecId
     * 
     * @return core_ET
     */
    private function renderRevertReceiptRows($rec, $string, $selectedRec)
    {
        $revertRec = pos_Receipts::fetch($rec->revertId);
        $revertData = (object)array('rec' => $revertRec);
        $this->prepareReceipt($revertData);
        $revertData->receiptDetails->revertsReceipt = $rec;
        $detailsTpl = $this->renderReceiptDetail($revertData->receiptDetails);

        $tpl = new core_ET("<div class='divider'>[#receiptName#]</div><div class=''>[#details#]</div>");
        $tpl->append(pos_Receipts::getRecTitle($revertRec), 'receiptName');
        $tpl->append($detailsTpl, 'details');
        
        if($rec->revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT){
            $reloadAttr = array('id' => "reload{$rec->id}", 'class' => 'posBtns reload', 'title' => 'Зареждане на артикулите от сторнираната бележка');
            
            if(pos_ReceiptDetails::haveRightFor('load', (object)array('receiptId' => $rec->id))){
                $reloadUrl = array('pos_ReceiptDetails', 'load', 'receiptId' => $rec->id, 'from' => $rec->revertId, 'ret_url' => true);
                $reloadAttr['class'] .= ' navigable';
            } else {
                $reloadAttr['class'] .= ' disabledBtn';
                $reloadUrl = null;
            }
            
            $warning = tr('Наистина ли желаете да заредите всички редове от оригиналната бележка|*?');
            $reloadUrl = toUrl($reloadUrl);
            $reloadAttr['onclick'] = "confirmAndRefirect('{$warning}', '{$reloadUrl}')";
            $link = ht::createElement('a', $reloadAttr, "Всички", false);
            $tpl->append($link, 'details');
        }
        
        return $tpl;
    }
    
    
    /**
     * Променяме рендирането на детайлите
     */
    private function renderReceiptDetail($data)
    {
        $tpl = new ET('');
        $blocksTpl = getTplFromFile('pos/tpl/terminal/ReceiptDetail.shtml');
        
        $saleTpl = $blocksTpl->getBlock('sale');
        $paymentTpl = $blocksTpl->getBlock('payment');
        if ($data->rows) {
            foreach ($data->rows as $id => $row) {
                $row->id = $id;
                
                $row->ROW_CLASS = 'receiptRow';
                if(isset($data->revertsReceipt)){
                    if(pos_ReceiptDetails::haveRightFor('load', (object)array('receiptId' => $data->revertsReceipt->id, 'loadRecId' => $id))){
                        $row->ROW_CLASS .= ' navigable';
                        $row->DATA_URL = toUrl(array('pos_ReceiptDetails', 'load', 'receiptId' => $data->revertsReceipt->id, 'loadRecId' => $id), 'local');
                    } else {
                        $row->ROW_CLASS .= ' disabledBtn';
                    }
                }
                
                if(pos_ReceiptDetails::haveRightFor('delete', $id)){
                    $row->DATA_DELETE_URL = toUrl(array('pos_ReceiptDetails', 'deleteRec', $id), 'local');
                    $row->DATA_DELETE_WARNING = tr('|Наистина ли искате да изтриете избрания ред|*?');
                }
                
                $action = cls::get('pos_ReceiptDetails')->getAction($data->rows[$id]->action);
                if($action->type == 'sale'){
                    $row->ENLARGABLE_CLASS_ID = cat_Products::getClassId();
                    $row->ENLARGABLE_OBJECT_ID = $data->recs[$id]->productId;
                    $row->ENLARGABLE_MODAL_TITLE = cat_Products::getTitleById($data->recs[$id]->productId);
                }
                
                $at = ${"{$action->type}Tpl"};
                if (is_object($at)) {
                    $rowTpl = clone(${"{$action->type}Tpl"});
                    $rowTpl->placeObject($row);
                    $rowTpl->removeBlocks();
                    $tpl->append($rowTpl);
                }
            }
        } else {
            $tpl->append(new ET("<div class='noResult'>" . tr('Няма записи') . '</div>'));
        }
        
        return $tpl;
    }
    
    
    /**
     * Рендира таба с партиди
     * 
     * @param stdClass $rec
     * @param string $currOperation
     * @param string $string
     * @param int|null $selectedRecId
     * @param core_ET $tpl
     * 
     * @return core_ET
     */
    private function renderResultBatches($rec, $string, $selectedRec, &$tpl)
    {
        expect(core_Packs::isInstalled('batch'));
        $receiptRec = pos_ReceiptDetails::fetchRec($selectedRec);
       
        if($Def = batch_Defs::getBatchDef($receiptRec->productId)){
            $dataUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setbatch');
            $batchTpl = new core_ET("");

            $batchesInStore = batch_Items::getBatchQuantitiesInStore($receiptRec->productId, $receiptRec->storeId, $rec->valior);
            if(countR($batchesInStore)){
                $cnt = 0;
                $btn = ht::createElement("div", array('id' => "batch{$cnt}",'class' => 'resultBatch posBtns navigable', 'title' => 'Добавяне на артикул без партида', 'data-url' => toUrl($dataUrl, 'local')), 'Без партида', true);
                $batchTpl->append($btn);
            }

            foreach ($batchesInStore as $batch => $quantity){
                $class = 'resultBatch posBtns navigable';
                $cnt++;
                $dataUrl['string'] = urlencode($batch);
                
                $measureId = cat_Products::fetchField($receiptRec->productId, 'measureId');
                $quantity = cat_UoM::round($measureId, $quantity);
                $measureName = tr(cat_UoM::getSmartName($measureId, $quantity));
                
                $quantityVerbal = core_Type::getByName('double(smartRound)')->toVerbal($quantity);
                $quantityVerbal = ht::styleIfNegative($quantityVerbal, $quantity);
                $batchVerbal = $Def->toVerbal($batch) . "<span class='small'>({$quantityVerbal} {$measureName})</span>";
                if($selectedRec->batch == $batch){
                    $class .= ' current';
                }
                
                $btn = ht::createElement("div", array('id' => "batch{$cnt}",'class' => $class, 'title' => 'Избор на партидата', 'data-url' => toUrl($dataUrl, 'local')), $batchVerbal, true);
                $batchTpl->append($btn);
            }
            
            if(countR($batchesInStore)){
                $batchTpl = ht::createElement('div', array('class' => 'grid'), $batchTpl, true);
                $tpl->append($batchTpl, 'BATCHES');
            }
        }
    }
    
    
    /**
     * Рендиране на таблицата с последните текстове
     *
     * @param stdClass $rec - записа на бележката
     * @param string $string - въведения стринг за търсене
     * @param stdClass|null $selectedRec - селектирания ред (ако има)
     *
     * @return core_ET
     */
    private function renderResultText($rec, $string, $selectedRec)
    {
        $tpl = new core_ET("");
        $texts = array('' => '') + pos_ReceiptDetails::getMostUsedTexts();
        
        $count = 0;
        foreach ($texts as $text){
            $class = "textResult navigable posBtns";
            $dataUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'settext', 'string' => $text);
            $dataUrl = toUrl($dataUrl, 'local');
            
            $textCaption = (!empty($text)) ? $text : tr('Без');
            $class .= (!empty($text)) ? '' : ' emptyText';
            $class .= ($text == $selectedRec->text) ? ' selected' : '';
            $element = ht::createElement('div', array("id" => "text{$count}", "class" => $class, 'data-url' => $dataUrl), $textCaption, true);
            $tpl->append($element);
            $count++;
        }
        $tpl = ht::createElement('div', array('class' => 'grid'), $tpl, true);
        $tpl->prepend(tr("|*<div class='divider'>|Най-използвани текстове|*</div>"));
        return $tpl;
    }
    
    
    /**
     * Рендиране на таблицата с наличните отстъпки
     * 
     * @param stdClass $rec - записа на бележката
     * @param string $string - въведения стринг за търсене
     * @param stdClass|null $selectedRec - селектирания ред (ако има)
     * @param core_ET $tpl
     * 
     * @return core_ET
     */
    private function renderResultDiscount($rec, $string, $selectedRec, &$tpl)
    {
        $settings = pos_Points::getSettings($rec->pointId);
        if($settings->setDiscounts != 'yes') return;
        
        $allowedDiscounts = arr::extractValuesFromArray(type_Table::toArray($settings->usedDiscounts), 'discount');
        $discountsArr = array();
        array_walk($allowedDiscounts, function($a) use (&$discountsArr){$percent = $a / 100; $discountsArr["{$percent}"] = $percent;});
        $discountsArr =  array('0' => '0') + $discountsArr;
        
        $discountTpl = new core_ET("");
        foreach ($discountsArr as $discountPercent){
            $class = ($discountPercent == $selectedRec->discountPercent) ? 'current' : '';
            
            $discAmount = $discountPercent * 100;
            $url = toUrl(array('pos_ReceiptDetails', 'updateRec', 'receiptId' => $rec->id, 'action' => 'setdiscount', 'string' => "{$discAmount}"), 'local');
            
            $btnCaption = ($discountPercent == '0') ? tr('Без отстъпка') : "{$discAmount} %";
            $element = ht::createElement("div", array('id' => "discount{$discountPercent}", 'class' => "navigable posBtns discountBtn {$class}", 'data-url' => $url), $btnCaption, true);
            $discountTpl->append($element);
        }

        if(countR($discountsArr)){
            $discountTpl = ht::createElement('div', array('class' => 'grid'), $discountTpl, true);
            $tpl->append($discountTpl, 'DISCOUNTS');
        }
    }
    
    
    /**
     * Рендиране на таблицата с контрагентите
     * 
     * @param stdClass $rec - записа на бележката
     * @param string $string - въведения стринг за търсене
     * @param stdClass|null $selectedRec - селектирания ред (ако има)
     * 
     * @return core_ET
     */
    private function renderResultContragent($rec, $string, $selectedRec)
    {
        $defaultContragentId = pos_Points::defaultContragent($rec->pointId);
        $defaultContragentClassId = crm_Persons::getClassId();
        $canSetContragent = pos_Receipts::haveRightFor('setcontragent', $rec);
        
        $tpl = new core_ET("");
        if($rec->contragentObjectId == $defaultContragentId && $rec->contragentClass == $defaultContragentClassId){
            
            $contragents = array();
            
            $newCompanyAttr = array('id' => 'contragentnew', 'data-url' => toUrl(array('pos_Terminal', 'transferInNewCompany', 'receiptId' => $rec->id, 'ret_url' => true)), 'class' => 'posBtns');
            if(!crm_Companies::haveRightFor('add') || !pos_Receipts::haveRightFor('transfer', $rec)){
                $newCompanyAttr['disabled'] = 'disabled';
                $newCompanyAttr['class'] .= ' disabledBtn';
                unset($newCompanyAttr['data-url']);
            } else {
                $newCompanyAttr['class'] .= ' navigable newCompanyBtn';
            }
            
            $holderDiv = ht::createElement('div', $newCompanyAttr, 'Нова фирма', true);
            $holderTpl = ht::createElement('div', array('class' => 'grid'), $holderDiv, true);
            $tpl->append($holderTpl);
            $tpl->append(tr("|*<div class='divider'>|Намерени контрагенти|*</div>"));
            
            $count = 0;
            $stringInput = core_Type::getByName('varchar')->fromVerbal($string);
            
            // Ако има подаден стринг за търсене
            $ownCompany = crm_Companies::fetchOurCompany();
            $Varchar = core_Type::getByName('varchar');
            $searchString = plg_Search::normalizeText($stringInput);
            $showUniqueNumberLike = false;
            
            if(!empty($stringInput)){
                $showUniqueNumberLike = type_Int::isInt($searchString) || preg_match('/^[a-zA-Z]{2}\d/', $searchString);
                $maxContragents = pos_Points::getSettings($rec->pointId, 'maxSearchContragent');
                
                // Ако има клиентска карта с посочения номер, намира се контрагента ѝ
                if($cardRec = crm_ext_Cards::fetch("#number = '{$stringInput}'")){
                    $contragents["{$cardRec->contragentClassId}|{$cardRec->contragentId}"] = (object)array('contragentClassId' => $cardRec->contragentClassId, 'contragentId' => $cardRec->contragentId, 'title' => cls::get($cardRec)->getTitleById($cardRec->contragentId));
                    $count++;
                }
                
                $personClassId = crm_Persons::getClassId();
                $companyClassId = crm_Companies::getClassId();
                
                // Ако има фирма с такъв данъчен или национален номер
                $cQuery = crm_Companies::getQuery();
                $cQuery->fetch("#vatId = '{$stringInput}' OR #uicId = '{$stringInput}' AND #id != {$ownCompany->id}");
                $cQuery->show('id,folderId,vatId,uicId');
                while($cRec = $cQuery->fetch()){
                    $contragents["{$companyClassId}|{$cRec->id}"] = (object)array('contragentClassId' => crm_Companies::getClassId(), 'contragentId' => $cRec->id, 'title' => crm_Companies::getTitleById($cRec->id), 'vatId' => $Varchar->toVerbal($cRec->vatId), 'uicId' => $Varchar->toVerbal($cRec->uicId));
                    $count++;
                }
                
                // Ако има лице с такова егн или данъчен номер
                $pQuery = crm_Persons::getQuery();
                $pQuery->fetch("#egn = '{$stringInput}' OR #vatId = '{$stringInput}'");
                $pQuery->show('id,folderId,egn,vatId');
                while($pRec = $pQuery->fetch()){
                    $contragents["{$personClassId}|{$pRec->id}"] = (object)array('contragentClassId' => crm_Persons::getClassId(), 'contragentId' => $pRec->id, 'title' => crm_Persons::getTitleById($cRec->id), 'egn' => $Varchar->toVerbal($cRec->egn), 'uicId' => $Varchar->toVerbal($cRec->uicId));
                    $count++;
                }
                
                if($showUniqueNumberLike){
                    
                    // Ако има фирма чийто данъчен или национален номер започва с числото
                    $cQuery = crm_Companies::getQuery();
                    $cQuery->where("#vatId LIKE '{$searchString}%' OR #uicId LIKE '{$searchString}%'");
                    $cQuery->show('id,folderId,vatId,uicId');
                    while($cRec = $cQuery->fetch()){
                        $contragents["{$companyClassId}|{$cRec->id}"] = (object)array('contragentClassId' => crm_Companies::getClassId(), 'contragentId' => $cRec->id, 'title' => crm_Companies::getTitleById($cRec->id), 'vatId' => $Varchar->toVerbal($cRec->vatId), 'uicId' => $Varchar->toVerbal($cRec->uicId));
                        $count++;
                    }
                    
                    // Ако има лице чието егн или национален номер започва с числото
                    $pQuery = crm_Persons::getQuery();
                    $pQuery->where("#vatId LIKE '{$searchString}%' OR #egn LIKE '{$searchString}%'");
                    $pQuery->show('id,folderId,egn,vatId');
                    while($pRec = $pQuery->fetch()){
                        $contragents["{$personClassId}|{$pRec->id}"] = (object)array('contragentClassId' => crm_Persons::getClassId(), 'contragentId' => $pRec->id, 'title' => crm_Persons::getTitleById($cRec->id), 'egn' => $Varchar->toVerbal($cRec->egn), 'uicId' => $Varchar->toVerbal($cRec->uicId));
                        $count++;
                    }
                }
                
            } else {
                $maxContragents = pos_Points::getSettings($rec->pointId, 'maxSearchContragentStart');
            }
            
            foreach (array('crm_Companies', 'crm_Persons') as $ContragentClass){
                $cQuery = $ContragentClass::getQuery();
                $cQuery->where("#state != 'rejected' AND #state != 'closed'");
                
                if($ContragentClass == 'crm_Companies'){
                    $cQuery->where("#id != {$ownCompany->id}");
                    $cQuery->show('id,folderId,vatId,uicId,name');
                    $uicField = 'uicId';
                } else {
                    $cQuery->show('id,folderId,name,egn,vatId,name');
                    $uicField = 'egn';
                }
                
                // Обикалят се всички фирми/лице които съдържат търсения стринг в името си
                $classId = ($ContragentClass == 'crm_Companies') ? $companyClassId : $personClassId;
                if(!empty($stringInput)){
                    plg_Search::applySearch($stringInput, $cQuery);
                }
                
                while($cRec = $cQuery->fetch()){
                    $name = plg_Search::normalizeText($cRec->name);
                    
                    // Ако го съдържат в името си се добавят
                    if(empty($searchString) || strpos($name, $searchString) !== false){
                        if(!array_key_exists("{$classId}|{$cRec->id}", $contragents)){
                            $contragents["{$classId}|{$cRec->id}"] = (object)array('contragentClassId' => $ContragentClass::getClassId(), 'contragentId' => $cRec->id, 'title' => $ContragentClass::getTitleById($cRec->id), 'vatId' => $Varchar->toVerbal($cRec->vatId), "{$uicField}" => $Varchar->toVerbal($cRec->{$uicField}));
                            $count++;
                        }
                    }
                    
                    if($count > $maxContragents) break;
                }
            }
            
            // След това резултатите се допълват с тези отговарящи по ключовите думи
            if(!empty($searchString)){
                foreach (array('crm_Companies', 'crm_Persons') as $ContragentClass){
                    $cQuery = $ContragentClass::getQuery();
                    $cQuery->where("#state != 'rejected' AND #state != 'closed'");
                    if($ContragentClass == 'crm_Companies'){
                        $cQuery->show('id,folderId,name,vatId,uicId');
                        $cQuery->where("#id != {$ownCompany->id}");
                        $uicField = 'uicId';
                    } else {
                        $cQuery->show('id,folderId,name,egn,vatId');
                        $uicField = 'egn';
                    }
                    
                    // Обикалят се всички фирми/лице които съдържат търсения стринг в името си
                    $classId = ($ContragentClass == 'crm_Companies') ? $companyClassId : $personClassId;
                    
                    plg_Search::applySearch($stringInput, $cQuery);
                    while($cRec = $cQuery->fetch()){
                        if(!array_key_exists("{$classId}|{$cRec->id}", $contragents)){
                            $contragents["{$classId}|{$cRec->id}"] = (object)array('contragentClassId' => $ContragentClass::getClassId(), 'contragentId' => $cRec->id, 'title' => $ContragentClass::getTitleById($cRec->id), 'vatId' => $Varchar->toVerbal($cRec->vatId), "{$uicField}" => $Varchar->toVerbal($cRec->{$uicField}));
                            $count++;
                        }
                        
                        if($count > $maxContragents) break;
                    }
                }
            }
            
            $cnt = 0;
            $temp =  new core_ET("");
            foreach ($contragents as $obj){
                $setContragentUrl = toUrl(array('pos_Receipts', 'setcontragent', 'id' => $rec->id, 'contragentClassId' => $obj->contragentClassId, 'contragentId' => $obj->contragentId, 'ret_url' => true));
                $divAttr = array("id" => "contragent{$cnt}", 'class' => 'posResultContragent posBtns navigable enlargable', 'data-url' => $setContragentUrl, 'data-enlarge-object-id' => $obj->contragentId, 'data-enlarge-class-id' => $obj->contragentClassId, 'data-modal-title' => strip_tags($obj->title));
                if(!$canSetContragent){
                    $divAttr['disabled'] = 'disabled';
                    $divAttr['disabledBtn'] = 'disabledBtn';
                    unset($divAttr['data-url']);
                }
                
                $obj->title = str::limitLen($obj->title, 48);
                if($showUniqueNumberLike){
                    $subArr = array();
                    if(!empty($obj->vatId)){
                        $subArr[] = tr("ДДС №") . ": {$obj->vatId}";
                    }
                    if($obj->contragentId == $personClassId){
                        if(!empty($obj->egn)){
                            $subArr[] = tr("ЕГН") . ": {$obj->egn}";
                        }
                    } else {
                        if(!empty($obj->uicId)){
                            $subArr[] = tr("Нац. №") . ": {$obj->uicId}";
                        }
                    }
                    
                    if(countR($subArr)){
                        $stringInputSearch = strtoupper($stringInput);
                        array_walk($subArr, function(&$a) use ($stringInputSearch) {$a = str_replace($stringInputSearch, "<span style='color:blue'>{$stringInputSearch}</span>", $a);});
                        
                        $subTitle = implode('; ', $subArr);
                        $subTitle = "<div style='font-size:0.7em'>{$subTitle}</div>";
                        $obj->title .= $subTitle;
                    }
                }
                
                $holderDiv = ht::createElement('div', $divAttr, $obj->title, true);
                $temp->append($holderDiv);
                $cnt++;
            }
            $tpl->append(ht::createElement('div', array('class' => 'grid'), $temp, true));
        } else {
            $contragentName = cls::get($rec->contragentClass)->getTitleById($rec->contragentObjectId);
            $tpl = new core_ET("<div class='divider'>{$contragentName}</div><div class='grid'>");
            
            // Добавя бутон за прехвърляне към папката на контрагента
            $setDefaultContragentUrl = toUrl(array('pos_Receipts', 'setcontragent', 'id' => $rec->id, 'contragentClassId' => $defaultContragentClassId, 'contragentId' => $defaultContragentId, 'ret_url' => true));
            $transferDivAttr = $divAttr = array("id" => "contragent0", 'class' => 'posBtns contragentLinkBtns', 'data-url' => $setDefaultContragentUrl);
            
            $transferDivAttr['id'] = "contragent1";
            $transferDivAttr['data-url'] = toUrl(array('pos_Receipts', 'transfer', $rec->id, 'contragentClassId' => $rec->contragentClass, 'contragentId' => $rec->contragentObjectId));
            $transferDivAttr['data-reloadurl'] = toUrl(array('pos_Receipts', 'new', 'pointId' => $rec->pointId));
            if(!pos_Receipts::haveRightFor('transfer', $rec)){
                $transferDivAttr['disabled'] = 'disabled';
                $transferDivAttr['class'] .= ' disabledBtn';
                unset($transferDivAttr['data-url']);
            } else {
                $transferDivAttr['class'] .= ' navigable openInNewTab';
            }
            
            // Добавя бутон за премахване на избрания контрагент
            $transferImg = ht::createImg(array('path' => 'pos/img/right-arrow.png'));
            $transferBtnBody = new core_ET(tr("|*[#IMG#]|Прехвърляне|*"));
            $transferBtnBody->replace($transferImg, 'IMG');

            $transferDivAttr['class'] .= " imgDiv";
            $holderDiv = ht::createElement('div', $transferDivAttr, $transferBtnBody, true);
            $tpl->append($holderDiv);
            if(!$canSetContragent){
                $divAttr['disabled'] = 'disabled';
                $divAttr['class'] .= ' disabledBtn';
                unset($divAttr['data-url']);
            } else {
                $divAttr['class'] .= ' navigable';
            }
            
            $removeImg = ht::createImg(array('path' => 'pos/img/stop.png'));
            $removeBtnBody = new core_ET(tr("|*[#IMG#]|Премахване|*"));
            $removeBtnBody->replace($removeImg, 'IMG');
            $divAttr['class'] .= " imgDiv";
            $holderDiv = ht::createElement('div', $divAttr, $removeBtnBody, true);
            $tpl->append($holderDiv);
            $tpl->append("</div>");
            
            $locationArr = crm_Locations::getContragentOptions($rec->contragentClass, $rec->contragentObjectId);
            if(countR($locationArr)){
                $tpl->append(tr("|*<div class='divider'>|Локации|*</div><div class='grid'>"));
                foreach ($locationArr as $locationId => $locationName){
                    $locationAttr = array("id" => "location{$locationId}", 'class' => 'posBtns locationBtn enlargable', 'data-enlarge-object-id' => $locationId, 'data-enlarge-class-id' => crm_Locations::getClassId(), 'data-modal-title' => strip_tags($locationName));
                    if(pos_Receipts::haveRightFor('setcontragent', $rec)){
                        $locationAttr['class'] .= ' navigable';
                        $locationAttr['data-url'] = toUrl(array('pos_Receipts', 'setcontragent', 'id' => $rec->id, 'contragentClassId' => $rec->contragentClass, 'contragentId' => $rec->contragentObjectId, 'locationId' => $locationId, 'ret_url' => true));
                    } else {
                        $locationAttr['disabled'] = 'disabled';
                        $locationAttr['class'] .= ' disabledBtn';
                    }
                    
                    if($locationId == $rec->contragentLocationId){
                        $locationAttr['class'] .= ' current';
                    }
                    
                    $locationName .= "<div style=font-size:0.8em>" . crm_Locations::getAddress($locationId) . "</div>";
                    
                    $holderDiv = ht::createElement('div', $locationAttr, $locationName, true);
                    $tpl->append($holderDiv);
                }
                $tpl->append("</div>");
            }
        }
       
        return $tpl;
    }
    
    
    /**
     * Рендиране на таблицата с начините на плащане
     *
     * @param stdClass $rec - записа на бележката
     * @param string $string - въведения стринг за търсене
     * @param stdClass|null $selectedRec - селектирания ред (ако има)
     *
     * @return core_ET
     */
    private function renderResultPayment($rec, $string, $selectedRec)
    {
        $tpl = new core_ET(tr("|*<div class='grid'>[#PAYMENTS#]</div><div class='divider'>|Приключване|*</div><div class='grid'>[#CLOSE_BTNS#]</div>"));
        
        $payUrl = (pos_Receipts::haveRightFor('pay', $rec)) ? toUrl(array('pos_ReceiptDetails', 'makePayment', 'receiptId' => $rec->id), 'local') : null;
        $disClass = ($payUrl) ? 'navigable' : 'disabledBtn';
        
        $paymentArr = array();
        $paymentArr["payment-1"] = (object)array('body' => ht::createElement("div", array('id' => "payment-1", 'class' => "{$disClass} posBtns payment", 'data-type' => '-1', 'data-url' => $payUrl), tr('В брой'), true), 'placeholder' => 'PAYMENTS');
        $payments = pos_Points::fetchSelected($rec->pointId);
       
        foreach ($payments as $paymentId => $paymentTitle){
            $paymentArr["payment{$paymentId}"] = (object)array('body' => ht::createElement("div", array('id' => "payment{$paymentId}", 'class' => "{$disClass} posBtns payment", 'data-type' => $paymentId, 'data-url' => $payUrl), tr($paymentTitle), true), 'placeholder' => 'PAYMENTS');
        }
        
        $contoUrl = (pos_Receipts::haveRightFor('close', $rec)) ? array('pos_Receipts', 'close', $rec->id, 'ret_url' => true) : null;
        $disClass = ($contoUrl) ? 'navigable' : 'disabledBtn';
        $warning =  ($contoUrl) ? 'Наистина ли желаете да приключите продажбата|*?' : false;
        $closeBtn = ht::createLink('Приключено', $contoUrl, $warning, array('class' => "{$disClass} posBtns closeBtn"));
        $paymentArr["close"] = (object)array('body' => $closeBtn, 'placeholder' => 'CLOSE_BTNS');
        
        // Добавяне на бутон за приключване на бележката
        cls::get('pos_Receipts')->invoke('BeforeGetPaymentTabBtns', array(&$paymentArr, $rec));
        
        foreach ($paymentArr as $btnObject){
            $tpl->append($btnObject->body, $btnObject->placeholder);
        }
        
        if(!empty($rec->paid)){
            $tpl->append($this->renderDeleteRowBtn($selectedRec), 'PAYMENTS');
        }
        
        $tpl->append("<div class='clearfix21'></div>");
        
        return $tpl;
    }
    
    
    /**
     * Рендиране на таблицата с наличните опаковки
     *
     * @param stdClass $rec - записа на бележката
     * @param string $string - въведения стринг за търсене
     * @param stdClass|null $selectedRec - селектирания ред (ако има)
     * @param core_ET $tpl - шаблон
     *
     * @return core_ET
     */
    private function renderResultQuantity($rec, $string, $selectedRec, &$tpl)
    {
        $measureId = cat_Products::fetchField($selectedRec->productId, 'measureId');
        $packs = cat_Products::getPacks($selectedRec->productId);
        
        $buttons = $storeBtns = $frequentPackButtons = array();
        $baseClass = "resultPack navigable posBtns";
        $dataUrl = (pos_ReceiptDetails::haveRightFor('edit', $selectedRec)) ? toUrl(array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setquantity'), 'local') : null;
        $dataChangeStoreUrl = (pos_ReceiptDetails::haveRightFor('edit', $selectedRec)) ? toUrl(array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setstore'), 'local') : null;
        
        $count = 0;
        foreach ($packs as $packagingId => $packName){
            $packRec = cat_products_Packagings::getPack($selectedRec->productId, $packagingId);
            $packName = cat_UoM::getTitleById($packagingId);
            
            $btnCaption = tr($packName);
            if(is_object($packRec)){
                $baseMeasureId = $measureId;
                $packRec->quantity = cat_Uom::round($baseMeasureId, $packRec->quantity);
                $btnCaption = "|{$packName}|*</br> <small>" . core_Type::getByName('double(smartRound)')->toVerbal($packRec->quantity) . " " . tr(cat_UoM::getSmartName($baseMeasureId, $packRec->quantity)) . "</small>";
            }
            
            $selected = ($selectedRec->value == $packagingId) ? 'selected' : '';
            $buttons[$packRec->packagingId] = ht::createElement("div", array('id' => "packaging{$count}", 'class' => "{$baseClass} {$selected}", 'data-pack' => $packName, 'data-url' => $dataUrl), tr($btnCaption), true);
            $count++;
        }
        
        $buttons["delete{$selectedRec->id}"] = $this->renderDeleteRowBtn($selectedRec);
        
        $query = pos_ReceiptDetails::getQuery();
        $query->where("#productId = {$selectedRec->productId} AND #action = 'sale|code' AND #quantity > 0");
        $query->orderBy('quantity', 'ASC');
        $query->show("quantity,value");
        $query->groupBy("quantity,value");
        $query->limit(10);
        
        // Добавяне на бутони за последните количества, в които е продаван
        while ($detailRec = $query->fetch()) {
            $count++;
            Mode::push('text', 'plain');
            $quantity = core_Type::getByName('double(smartRound)')->toVerbal($detailRec->quantity);
            Mode::pop('text', 'plain');
            if(!$detailRec->value)  continue; // Да не гърми при лоши данни
            $packagingId = cat_UoM::getSmartName($detailRec->value, 1);
            $btnCaption =  "{$quantity} " . tr(cat_UoM::getSmartName($detailRec->value, $detailRec->quantity));
            $packDataName = cat_UoM::getTitleById($detailRec->value);
            $frequentPackButtons[] = ht::createElement("div", array('id' => "packaging{$count}", 'class' => "{$baseClass} packWithQuantity", 'data-quantity' => $detailRec->quantity, 'data-pack' => $packDataName, 'data-url' => $dataUrl), $btnCaption, true);
        }
        
        $productRec = cat_Products::fetch($selectedRec->productId, 'canStore');
        
        if($productRec->canStore == 'yes'){
            $stores = pos_Points::getStores($rec->pointId);
            if(countR($stores) > 1 && empty($rec->revertId)){
                $storeArr = array();
                foreach ($stores as $storeId){
                    $quantity = pos_Stocks::getQuantityByStore($selectedRec->productId, $storeId);
                    $storeArr[$storeId] = $quantity;
                }
                
                arsort($storeArr);
                foreach ($storeArr as $storeId => $quantity){
                    $btnClass = ($storeId == $selectedRec->storeId) ? 'current' : 'navigable';
                    $dataUrl = ($storeId == $selectedRec->storeId) ? null : $dataChangeStoreUrl;
                    
                    $quantityInStockVerbal = core_Type::getByName('double(smartRound)')->toVerbal($quantity);
                    $quantityInStockVerbal = ht::styleNumber($quantityInStockVerbal, $quantity);
                    $storeName = store_Stores::getTitleById($storeId);
                    $storeCaption = "<span><div class='storeNameInBtn'>{$storeName}</div> <div class='storeQuantityInStock'>({$quantityInStockVerbal} " . tr(cat_UoM::getShortName($measureId)) . ")</div></span>";
                    $storeBtns[] = ht::createElement("div", array('id' => "changeStore{$storeId}", 'class' => "{$btnClass} posBtns chooseStoreBtn", 'data-url' => $dataUrl, 'data-storeid' => $storeId), $storeCaption, true);
                }
            }
        }

        $btnTpl = new core_ET("");
        foreach ($buttons as $btn){
            $btnTpl->append($btn);
        }

        $btnTpl = ht::createElement('div', array('class' => 'grid'), $btnTpl, true);
        $tpl->append($btnTpl, 'PACK_BUTTONS');

        $freqTpl = new core_ET("");
        foreach ($frequentPackButtons as $freqbtn){
            $freqTpl->append($freqbtn);
        }

        $freqTpl = ht::createElement('div', array('class' => 'grid'), $freqTpl, true);
        $tpl->append($freqTpl, 'FREQUENT_PACK_BUTTONS');

        if(countR($storeBtns)){
            $storesTpl = new core_ET("");
            foreach ($storeBtns as $storeBtn){
                $storesTpl->append($storeBtn);
            }
            $storesTpl = ht::createElement('div', array('class' => 'grid'), $storesTpl, true);
            $tpl->append($storesTpl, 'STORE_BUTTONS');
        }

        return $tpl;
    }
    
    
    /**
     * Добавяне на бутон за изтриване на реда
     * 
     * @param stdClass $selectedRec
     * @return core_ET
     */
    private function renderDeleteRowBtn($selectedRec)
    {
        $deleteAttr = array('id' => "delete{$selectedRec->id}", 'class' => "posBtns deleteRow", 'title' => 'Изтриване на реда');
        $deleteAttr['class'] .= (pos_ReceiptDetails::haveRightFor('delete', $selectedRec)) ? ' navigable' : ' disabledBtn';
       
        return ht::createElement("div", $deleteAttr, tr('Изтриване'), true);
    }
    
    
    /**
     * Рендиране на таблицата с последните цени
     *
     * @param stdClass $rec - записа на бележката
     * @param string $string - въведения стринг за търсене
     * @param stdClass|null $selectedRec - селектирания ред (ако има)
     * @param core_ET $tpl
     *
     * @return core_ET
     */
    private function renderResultPrice($rec, $string, $selectedRec, $tpl)
    {
        $setPrices = pos_Points::getSettings($rec->pointId, 'setPrices');
        if($setPrices != 'yes') return;
        
        $buttons = array();
        
        $dQuery = pos_ReceiptDetails::getQuery();
        $dQuery->where("#action = 'sale|code' AND #productId = {$selectedRec->productId} AND #quantity > 0");
        $dQuery->orderBy('id', 'desc');
        if(isset($selectedRec->value)){
            $dQuery->where("#value = {$selectedRec->value}"); 
            $value = $selectedRec->value;
        } else {
            $dQuery->where("#value IS NULL");
            $value = cat_Products::fetchField($selectedRec->productId, 'measureId');
        }
        
        $cnt = 0;
        $packName = cat_UoM::getVerbal($value, 'name');
        $dQuery->show('price,param');
        $allPrices = $dQuery->fetchAll();
        
        if($stringPrice = core_Type::getByName('double')->fromVerbal($string)){
            $allPrices = array((object)array('price' => $stringPrice, 'param' => 0)) + $allPrices;
        }
        
        foreach($allPrices as $dRec){
            $dRec->price *= 1 + $dRec->param;
            Mode::push('text', 'plain');
            $price = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->price);
            Mode::pop('text', 'plain');
            
            $priceVerbal = currency_Currencies::decorate($price);
            $btnName = "|*{$priceVerbal}&nbsp;/&nbsp;|" . tr($packName);
            $dataUrl = toUrl(array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setprice', 'string' => $price), 'local');
            
            $cnt++;
            $buttons[$dRec->price] = ht::createElement("div", array('id' => "price{$cnt}",'class' => 'resultPrice posBtns navigable', 'data-url' => $dataUrl), tr($btnName), true);
        }

        $priceTpl = new core_ET("");
        foreach ($buttons as $btn){
            $priceTpl->append($btn);
        }

        $priceTpl = ht::createElement('div', array('class' => 'grid'), $priceTpl, true);
        $tpl->append($priceTpl, 'PRICES');

        return $tpl;
    }
    
    
    /**
     * Подготовка и рендиране на бележка
     *
     * @param int $id - ид на бележка
     *
     * @return core_ET $tpl - шаблона
     */
    public function getReceipt_($id)
    {
        $Receipts = cls::get('pos_Receipts');
        expect($rec = $Receipts->fetchRec($id));
        
        $data = new stdClass();
        $data->rec = $rec;
        $this->prepareReceipt($data);
        
        $tpl = $this->renderReceipt($data);
        $Receipts->invoke('AfterGetReceipt', array(&$tpl, $rec));
        
        return $tpl;
    }
    
    
    /**
     * Подготовка на бележка
     */
    private function prepareReceipt(&$data)
    {
        $Receipt = cls::get('pos_Receipts');
        
        $fields = $Receipt->selectFields();
        $fields['-terminal'] = true;
        $data->row = $Receipt->recToverbal($data->rec, $fields);
        unset($data->row->contragentName);
        $data->receiptDetails = $Receipt->pos_ReceiptDetails->prepareReceiptDetails($data->rec->id);
        $data->receiptDetails->rec = $data->rec;
    }
    
    
    /**
     * Подготовка и рендиране на бележка
     *
     * @return core_ET $tpl - шаблон
     */
    private function renderReceipt($data)
    {
        $tpl = getTplFromFile('pos/tpl/terminal/Receipt.shtml');
        if($data->rec->state == 'draft'){
            unset($data->row->STATE_CLASS);
        }
        $tpl->placeObject($data->row);
        
        if($lastRecId = pos_ReceiptDetails::getLastRec($data->rec->id)->id){
            $data->receiptDetails->rows[$lastRecId]->CLASS .= ' highlighted';
        }
        
        if($lastRec = pos_ReceiptDetails::getLastRec($data->rec->id)){
            if(strpos($lastRec->action, 'payment') !== false){
                $data->receiptDetails->rows[$lastRec->id]->CLASS .= ' highlighted';
            }
        }
        
        // Слагане на детайлите на бележката
        $detailsTpl = $this->renderReceiptDetail($data->receiptDetails);
        $tpl->append($detailsTpl, 'DETAILS');
        
        if(empty($data->rec->paid)){
            $tpl->removeBlock('PAYMENT_TAB');
        }
        
        return $tpl;
    }
    
    
    /**
     * Вкарване на css и js файлове
     */
    public function pushTerminalFiles(&$tpl, $rec)
    {
        $tpl->push('css/Application.css', 'CSS');
        $tpl->push('css/default-theme.css', 'CSS');
        $tpl->push('pos/tpl/css/styles.css', 'CSS');
        $tpl->push('pos/tpl/css/no-sass.css', 'CSS');
        
        if (!Mode::is('printing')) {
            $tpl->push('pos/js/scripts.js', 'JS');
            $tpl->push('pos/js/jquery.keynav.js', 'JS');
            $tpl->push('pos/js/shortcutkeys.js', 'JS');
            jquery_Jquery::run($tpl, 'posActions();');
            jquery_Jquery::run($tpl, 'afterload();');
            jquery_Jquery::run($tpl, 'scrollToHighlight();');
            jquery_Jquery::run($tpl, 'openCurrentPosTab();');
            
            $searchDelayTerminal = pos_Points::getSettings($rec->pointId, 'searchDelayTerminal');
            jquery_Jquery::run($tpl, "setSearchTimeout({$searchDelayTerminal});");
            
            jqueryui_Ui::enable($tpl);
        }
        
        // Добавяне на стилове за темата на терминала на ПОС-а, ако е различна от стандартната
        $theme = pos_Points::getSettings($rec->pointId, 'theme');
        if($theme != 'default'){
            if(getFullPath("pos/tpl/themes/{$theme}.css")){
                $tpl->push("pos/tpl/themes/{$theme}.css", 'CSS');
            }
        }
        
        cls::get('pos_Receipts')->invoke('AfterPushTerminalFiles', array(&$tpl, $rec));
        
        // Абониране за рефреш на хедъра
        core_Ajax::subscribe($tpl, array($this, 'autoRefreshHeader', $rec->id), 'refreshTime', 30);
    }
    
    
    /**
     * Автоматично рефрешване на хедъра
     */
    function act_autoRefreshHeader()
    {
        // Изискване на права
        pos_Receipts::requireRightFor('terminal');
        expect($id = Request::get('id', 'int'));
        pos_Receipts::requireRightFor('terminal', $id);
        $res = array();

        $resObj1 = new stdClass();
        $resObj1->func = 'clearStatuses';
        $resObj1->arg = array('type' => 'notice');
        $res[] = $resObj1;
        
        $resObj2 = new stdClass();
        $resObj2->func = 'clearStatuses';
        $resObj2->arg = array('type' => 'error');
        $res[] = $resObj2;
        
        return $res;
    }
    
    
    /**
     * Рендиране на таблицата с наличните отстъпки
     * 
     * @param stdClass $rec - записа на бележката
     * @param string $string - въведения стринг за търсене
     * @param int $revertReceiptId - ид-то на бележката за сторниране
     * 
     * @return core_ET
     */
    private function renderResultProducts($rec, $string, $selectedRec)
    {
        $settings = pos_Points::getSettings($rec->pointId);
        $searchString = plg_Search::normalizeText($string);
        $data = new stdClass();
        $data->rec = $rec;
        $data->searchString = $searchString;
        $data->searchStringPure = $string;
        
        $res = array();
        
        $foundResults = (object)array('rows' => $this->prepareProductTable($rec, $string), 'placeholder' => 'BLOCK2');
        
        if(isset($selectedRec->productId)){
            $res['similar'] = (object)array('rows' => $this->prepareResultSimilarProducts($rec, $selectedRec, $string), 'placeholder' => 'BLOCK1');
            $firstDividerCaption = countR($res['similar']->rows == 1) ? 'Избран артикул' : 'Свързани артикули';
        }
        
        if(!empty($settings->maxSearchProductInLastSales)){
            $res['contragent'] = (object)array('rows' => $this->prepareContragentProducts($rec, $string), 'placeholder' => 'BLOCK3');
        }
        
        $tpl = new core_ET(tr("|*[#BLOCK2#]
                                <!--ET_BEGIN BLOCK1--><div class='divider'>|{$firstDividerCaption}|*</div>
                                <div class='grid'>[#BLOCK1#]</div><!--ET_END BLOCK1-->
                                <!--ET_BEGIN BLOCK3--><div class='divider'>|Списък от предишни продажби|*</div>
                                <div class='grid'>[#BLOCK3#]</div><!--ET_END BLOCK3-->"));
        
        $block = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('PRODUCTS_RESULT');
        $count = 0;
        foreach ($res as $key => $obj){
            foreach ($obj->rows as $row){
                $row->elementId = "{$key}{$row->id}";
                $bTpl = clone $block;
                $bTpl->placeObject($row);
                $bTpl->removeBlocksAndPlaces();
                $tpl->append($bTpl, $obj->placeholder);
                $count++;
            }
        }
        
        $groups = keylist::toArray($settings->groups);
        $countGroups = countR($groups);
        
        // Ако има групи на артикулите
        $resultTpl = ($countGroups) ? new core_ET("<div class='scroll-holder productTabs'><ul class='tabHolder'>[#TAB#]</ul></div><div class='contentHolder'>") : new core_ET("");
        $groups = array('all' => null) + $groups;
        foreach ($groups as $groupId){
            $inGroup = array_filter($foundResults->rows, function($e) use ($groupId){ return is_null($groupId) || keylist::isIn($groupId, $e->_groups);});
            if($countGroups){
                Mode::push('treeShortName', true);
                $groupName = (isset($groupId)) ? cat_Groups::getVerbal($groupId, 'name') : tr("Всички");
                Mode::pop('treeShortName');
                $contentId = "content{$groupId}";
                $tab = "<li id='group{$groupId}' class='selectable' data-content = '{$contentId}'>{$groupName}</li>";
                $resultTpl->append($tab, "TAB");
            }
            
            // Показват се тези от резултатите, които са във всяка група
            $groupTpl = ($countGroups) ? new core_ET("<div class='content' id='{$contentId}'>[#RESULT_CONTENT#]</div>") : new core_ET("<div class='grid'>[#RESULT_CONTENT#]</div>");
            if(countR($inGroup)){
                $grTpl = new core_ET("");
                foreach($inGroup as $row){
                    $row->elementId = "result{$groupId}_{$row->id}";
                    $bTpl = clone $block;
                    $bTpl->placeObject($row);
                    $bTpl->removeBlocksAndPlaces();
                    $grTpl->append($bTpl);
                }
                
                if($countGroups){
                    $grTpl->prepend("<div class='grid'>");
                    $grTpl->append("</div>");
                } 
                
                $grTpl->removeBlocksAndPlaces();
                $groupTpl->append($grTpl, 'RESULT_CONTENT');
            } else {
                $groupTpl->append('<div class="noFoundInGroup">' . tr("Няма намерени артикули в групата") . '</div>', 'RESULT_CONTENT');
            }
            
            $resultTpl->append($groupTpl);
        }
        
        if($countGroups){
            $resultTpl->append("</div>");
        }
        
        $resultTpl->removeBlocksAndPlaces();
        if(!empty($data->searchString)){
            $resultTpl->prepend(tr("|*<div class='divider'>|Намерени артикули|*</div>"));
        }
        
        $tpl->append($resultTpl, 'BLOCK2');
        
        $holderClass = (empty($tab)) ? "noTabs" : "withTabs";
        $tpl->prepend("<div class='{$holderClass}'>");
        $tpl->append("</div>");
       
        return $tpl;
    }
    
    
    /**
     * Връща последно продаваните артикули на контрагента
     *
     * @return array
     */
    private function prepareContragentProducts($rec, $string)
    {
        $products = array();
        if($listId = cond_Parameters::getParameter($rec->contragentClass, $rec->contragentObjectId, 'salesList')){
            $maxSearchProductInLastSales = pos_Points::getSettings($rec->pointId, 'maxSearchProductInLastSales');
            $productsInList = arr::extractValuesFromArray(cat_Listings::getAll($listId, null, $maxSearchProductInLastSales), 'productId');
            if(is_array($productsInList)){
                foreach ($productsInList as $productId){
                    $products[$productId] = cat_Products::fetch($productId, 'name,isPublic,nameEn,code,canStore,measureId');
                }
            }
        }
        
        return $this->prepareProductResultRows($products, $rec);
    }
    
    
    /**
     * Връща избрания артикул от реда и свързаните с него артикули
     * 
     * @return array
     */
    private function prepareResultSimilarProducts($rec, $selectedRec, $string)
    {
        $productRelations = array();
        $maxSearchProductRelations = pos_Points::getSettings($rec->pointId)->maxSearchProductRelations;
        if(empty($maxSearchProductRelations)) {
            
            return $productRelations;
        }
        
        $products = array($selectedRec->productId => cat_Products::fetch($selectedRec->productId, 'name,isPublic,nameEn,code,canStore,measureId,canSell'));
        $products[$selectedRec->productId]->packId = $selectedRec->value;
        $similarProducts = sales_ProductRelations::fetchField("#productId = {$selectedRec->productId}", 'data');
       
        if(is_array($similarProducts)){
            $productRelations = array_keys($similarProducts);
            $productRelations = array_slice($productRelations, 0, $maxSearchProductRelations);
        }
        
        foreach ($productRelations as $productId){
            $products[$productId] = cat_Products::fetch($productId, 'name,isPublic,nameEn,code,canStore,measureId,canSell');
        }
        
        return $this->prepareProductResultRows($products, $rec);
    }
    
    
    /**
     * Подготвя данните от резултатите за търсене
     */
    private function prepareProductTable($rec, $searchString)
    {
        $result = core_Cache::get('planning_Terminal', "{$rec->pointId}_'{$searchString}'_{$rec->id}_{$rec->contragentClass}_{$rec->contragentObjectId}");
        
        $settings = pos_Points::getSettings($rec->pointId);
        if(!is_array($result)){
            
            $count = 0;
            $sellable = array();
            $searchStringPure = plg_Search::normalizeText($searchString);
            
            $pQuery = pos_SellableProductsCache::getQuery();
            $pQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
            $pQuery->EXT('measureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
            $pQuery->EXT('name', 'cat_Products', 'externalName=name,externalKey=productId');
            $pQuery->EXT('canSell', 'cat_Products', 'externalName=canSell,externalKey=productId');
            $pQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
            $pQuery->EXT('nameEn', 'cat_Products', 'externalName=nameEn,externalKey=productId');
            $pQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
            $pQuery->where("#priceListId = {$settings->policyId}");
            $pQuery->show('productId,name,nameEn,code,canStore,measureId,canSell,string,searchKeywords');
            
            // Ако не се търси подробно артикул, се показват тези от любими
            if(empty($searchString)){
                $groups = keylist::toArray(pos_Points::getSettings($rec->pointId, 'groups'));
                $productsArr = keylist::toArray(pos_Points::getSettings($rec->pointId, 'products'));
                
                if(countR($productsArr) || countR($groups)){
                    $pQuery->limit($settings->maxSearchProducts);
                    $pQuery->in("productId", array_keys($productsArr));
                    $pQuery->orderBy('code,name', 'ASC');
                    
                    if(countR($groups)){
                        $or = countR($productsArr) ? true : false;
                        $pQuery->likeKeylist('groups', $groups, $or);
                    }
                    
                    while($pRec = $pQuery->fetch()){
                        $sellable[$pRec->productId] = $pRec;
                    }
                }
            } else {
                $count = 0;
                $maxCount = $settings->maxSearchProducts;
                
                // Ако има артикул, чийто код отговаря точно на стринга, той е най-отгоре
                $foundRec = cat_Products::getByCode($searchString);
                
                if(isset($foundRec->productId)){
                    $cloneQuery = clone $pQuery;
                    $cloneQuery->where("#productId = {$foundRec->productId}");
                    if($productRec = $cloneQuery->fetch()){
                        $sellable[$foundRec->productId] = (object)array('id' => $foundRec->productId, 'canSell' => $productRec->canSell,'canStore' => $productRec->canStore, 'measureId' => $productRec->measureId, 'code' => $productRec->code, 'packId' => isset($foundRec->packagingId) ? $foundRec->packagingId : null);
                        $count++;
                    }
                }
                
                // След това се добавят артикулите, които съдържат стринга в името и/или кода си
                $pQuery1 = clone $pQuery;
                $pQuery1->orderBy('code,name', 'ASC');
                if(isset($foundRec->productId)){
                    $pQuery1->where("#productId != {$foundRec->productId}");
                }
                
                $searchString = plg_Search::normalizeText($searchString);
                $pQuery1->where("LOCATE ('{$searchString}', #string)");
                plg_Search::applySearch($searchString, $pQuery1);
                $pQuery1->limit($settings->maxSearchProducts);
                
                while($pRec1 = $pQuery1->fetch()){
                    $sellable[$pRec1->productId] = (object)array('id' => $pRec1->productId, 'canSell' => $pRec1->canSell, 'code' => $pRec1->code, 'canStore' => $pRec1->canStore, 'measureId' => $pRec1->measureId);
                    $count++;
                    $maxCount--;
                    if($count == $settings->maxSearchProducts) break;
                }
                
                // Ако не е достигнат лимита, се добавят и артикулите с търсене в ключовите думи
                if($count < $settings->maxSearchProducts){
                    $notInKeys = array_keys($sellable);
                    $pQuery2 = clone $pQuery;
                    $pQuery2->limit($settings->maxSearchProducts);
                    if(empty($searchStringPure)){
                        $pQuery2->where("1=2");
                    } else {
                        plg_Search::applySearch($searchString, $pQuery2);
                    }
                    
                    if(countR($notInKeys)){
                        $pQuery2->notIn('productId', $notInKeys);
                    }
                   
                    while($pRec2 = $pQuery2->fetch()){
                        $sellable[$pRec2->productId] = (object)array('id' => $pRec2->productId, 'canSell' => $pRec2->canSell, 'code' => $pRec2->code, 'canStore' => $pRec2->canStore, 'measureId' =>  $pRec2->measureId);
                        $count++;
                        $maxCount--;
                        if($count == $settings->maxSearchProducts) break;
                    }
                }
            }
            
            $result = $this->prepareProductResultRows($sellable, $rec);
            core_Cache::set('planning_Terminal', "{$rec->pointId}_'{$searchString}'_{$rec->id}_{$rec->contragentClass}_{$rec->contragentObjectId}", $result, 2);
        }
        
        return $result;
    }
    
    
    /**
     * Подготивка на редовете на търсените артикули
     * 
     * @param array $products
     * @param stdClass $rec
     * 
     * @return array $res
     */
    private function prepareProductResultRows($products, $rec)
    {
        $res = array();
        if(!countR($products)) {
            
            return $res;
        }
        
        $defaultContragentId = pos_Points::defaultContragent($rec->pointId);
        $defaultContragentClassId = crm_Persons::getClassId();
        $productClassId = cat_Products::getClassId();
        
        $Policy = cls::get('price_ListToCustomers');
        $listId = pos_Points::fetchField($rec->pointId, 'policyId');
        if(!($rec->contragentObjectId == $defaultContragentId && $rec->contragentClass == $defaultContragentClassId)){
            $listId = price_ListToCustomers::getListForCustomer($rec->contragentClass, $rec->contragentObjectId);
        }
        
        foreach ($products as $id => $pRec) {
            if(isset($pRec->packId)){
                $packId = $pRec->packId;
            } else {
                $packs = cat_Products::getPacks($id);
                $packId = key($packs);
            }
            
            $packQuantity = cat_products_Packagings::getPack($id, $packId, 'quantity');
            $perPack = (!empty($packQuantity)) ? $packQuantity : 1;
            $price = $Policy->getPriceByList($listId, $id, $packId, 1, null, 1, 'yes');
            
            // Обръщаме реда във вербален вид
            $res[$id] = new stdClass();;
            $Double = core_Type::getByName('double(decimals=2)');
            
            $obj = (object) array('productId' => $id, 'measureId' => $pRec->measureId, 'packagingId' => $packId);
            if (empty($price->price)){
                $res[$id]->price = "<b class='red'>n/a</b>";
            } else {
                if(!empty($price->discount)){
                    $price->price *= (1 - $price->discount);
                }
                
                $vat = cat_Products::getVat($id);
                $price = $price->price * $perPack;
                $price *= 1 + $vat;
                $obj->price = $price;
                
                $res[$id]->price = currency_Currencies::decorate($Double->toVerbal($obj->price));
            }
            
            $res[$id]->stock = core_Type::getByName('double(smartRound)')->toVerbal($obj->stock);
            $packagingId = ($obj->packagingId) ? $obj->packagingId : $obj->measureId;
            $res[$id]->packagingId = cat_UoM::getSmartName($packagingId, $obj->stock);
            $res[$id]->productId = mb_subStr(cat_Products::getVerbal($obj->productId, 'name'), 0, 95);
            $res[$id]->code = !empty($pRec->code) ? cat_Products::getVerbal($obj->productId, 'code') : "Art{$obj->productId}";
            
            $res[$id]->photo = $this->getPosProductPreview($obj->productId, 64, 64);
            $res[$id]->CLASS = ' pos-add-res-btn navigable enlargable';
            $res[$id]->DATA_URL = (pos_ReceiptDetails::haveRightFor('add', $obj)) ? toUrl(array('pos_ReceiptDetails', 'addProduct', 'receiptId' => $rec->id), 'local') : null;
            $res[$id]->DATA_ENLARGE_OBJECT_ID = $id;
            $res[$id]->DATA_ENLARGE_CLASS_ID = $productClassId;
            $res[$id]->DATA_MODAL_TITLE = cat_Products::getTitleById($id);
            $res[$id]->id = $id;
            $res[$id]->receiptId = $rec->id;
            if($pRec->canSell != 'yes'){
                $res[$id]->CLASS .= ' notSellable';
            }
            
            $stock = ($pRec->canStore == 'yes') ? pos_Stocks::getBiggestQuantity($id, $rec->pointId) : null;
            if($packId != cat_UoM::fetchBySysId('pcs')->id || (isset($stock) && empty($stock))){
                $res[$id]->measureId = tr(cat_UoM::getSmartName($packId));
            }
            
            if ((isset($stock) && empty($stock))) {
                $res[$id]->measureId = tr(cat_UoM::getSmartName($packId, 0));
                $res[$id]->measureId = "<span class='notInStock'>0 {$res[$id]->measureId}</span>";
            }
            
            $res[$id]->_groups = cat_Products::fetchField($id, 'groups');
        }
        
        return $res;
    }
    
    
    /**
     * Превю на артикула в ПОС-а
     * 
     * @param int $productId
     * @param int $width
     * @param int $height
     * 
     * @return core_ET|NULL
     */
    private function getPosProductPreview($productId, $width, $height)
    {
        $photo = cat_Products::getParams($productId, 'preview');
        $arr = array();
        $thumb = (!empty($photo)) ? new thumb_Img(array($photo, $height, $width, 'fileman')) : new thumb_Img(getFullPath('pos/img/default-image.jpg'), $width, $height, 'path');
        
        return $thumb->createImg($arr);
    }
    
    
    /**
     * Рендиране на таба с черновите
     *
     * @param stdClass $rec - записа на бележката
     * @param string $string - въведения стринг за търсене
     * @param stdClass|null $selectedRec - селектирания ред (ако има)
     *
     * @return core_ET $block - шаблон
     */
    private function renderResultReceipts($rec, $string, $selectedRec)
    {
        $rec = $this->fetchRec($rec);
        
        $string = plg_Search::normalizeText($string);
        $maxSearchReceipts = pos_Points::getSettings($rec->pointId, 'maxSearchReceipts');
       
        // Намираме всички чернови бележки и ги добавяме като линк
        $query = pos_Receipts::getQuery();
        $query->XPR('createdDate', 'date', 'DATE(#createdOn)');
        $query->orderBy("#createdDate,#id", 'DESC');
        $query->limit(5 * $maxSearchReceipts);
        if(!empty($string)){
            plg_Search::applySearch($string, $query);
        }
        
        $tpl = new core_ET("<div class='scroll-holder'><ul class='tabHolder'>[#TAB#]</ul></div><div class='contentHolder'>");
        
        // Групиране на записите по дата
        $arr = array('draft' => array('caption' => 'Чернови', 'receipts' => new core_ET(""), 'count' => 0),
                      'paid' => array('caption' => 'Платени', 'receipts' => new core_ET(""), 'count' => 0),
                      'closed' => array('caption' => 'Чакащи', 'receipts' => new core_ET(""), 'count' => 0),
                      'transfered' => array('caption' => 'Прехвърлени', 'receipts' => new core_ET(""), 'count' => 0),
                      'rejected' => array('caption' => 'Оттеглени', 'receipts' => new core_ET(""), 'count' => 0));
        
        $disabledClass = (pos_Receipts::haveRightFor('add')) ? 'navigable' : 'disabledBtn';
        $addUrl = (pos_Receipts::haveRightFor('add')) ? array('pos_Receipts', 'new', 'forced' => true) : array();
        
        $revertDefaultUrl = (pos_Receipts::haveRightFor('revert', pos_Receipts::DEFAULT_REVERT_RECEIPT)) ? array('pos_Receipts', 'revert', pos_Receipts::DEFAULT_REVERT_RECEIPT, 'ret_url' => true) : array();
        $disabledRevertClass = countR($revertDefaultUrl) ? 'navigable' : 'disabledBtn';
        $warning = countR($revertDefaultUrl) ? 'Наистина ли искате да създадете нова сторнираща бележка|*?' : null;
        
        $row = ht::createLink('+ Нова бележка', $addUrl, null, array('id' => "receiptnew", 'class' => "pos-notes posBtns {$disabledClass}", 'title' => 'Създаване на нова бележка'));
        $arr['draft']['receipts']->append($row);
        
        $revertBlock = ht::createLink('↶ Сторно бележка', $revertDefaultUrl, $warning, array('id' => "revertReceiptBtn", 'class' => "pos-notes posBtns revertReceiptBtn {$disabledRevertClass}", 'title' => 'Създаване на нова сторно бележка'));
        $arr['draft']['receipts']->append($revertBlock);
        
        while ($receiptRec = $query->fetch()) {
            $key = $receiptRec->state;
            if(isset($receiptRec->transferedIn)){
                $key = 'transfered';
            } elseif($receiptRec->paid && $receiptRec->state != 'closed'){
                $key = 'paid';
            } elseif($receiptRec->state == 'waiting'){
                $key = 'closed';
            }
            
            $openUrl = (pos_Receipts::haveRightFor('terminal', $receiptRec->id)) ? array('pos_Terminal', 'open', 'receiptId' => $receiptRec->id, 'opened' => true) : array();
            $class = (count($openUrl)) ? ' navigable' : ' disabledBtn';
            $class .= ($receiptRec->id == $rec->id) ? ' currentReceipt' : '';
            
            $btnTitle = self::getReceiptTitle($receiptRec);
            $btnTitle = ($rec->pointId != $receiptRec->pointId) ? ht::createHint($btnTitle, "Бележката е от друг POS") : $btnTitle;
            $row = ht::createLink($btnTitle, $openUrl, null, array('id' => "receipt{$receiptRec->id}", 'class' => "pos-notes posBtns {$class} state-{$receiptRec->state} enlargable", 'title' => 'Отваряне на бележката', 'data-enlarge-object-id' => $receiptRec->id, 'data-enlarge-class-id' => pos_Receipts::getClassId(), 'data-modal-title' => strip_tags(pos_Receipts::getRecTitle($receiptRec))));
            
            if($arr[$key]['count'] < $maxSearchReceipts){
                $arr[$key]['receipts']->append($row);
                $arr[$key]['count']++;
            }
        }
        
        foreach ($arr as $key => $element){
            $contentId = "content{$key}";
            $tab = "<li class='selectable' data-content = '{$contentId}'>{$element['caption']}</li>";
            $tpl->append($tab, "TAB");
            
            if($element['count']){
                $element['receipts']->prepend("<div class='content' id='{$contentId}'><div class='grid'>");
                $element['receipts']->append("</div></div>");
                $element['receipts']->removeBlocksAndPlaces();
                $tpl->append($element['receipts']);
            } else {
                $tpl->append("<div class='content' id='{$contentId}'><div class='noFoundInGroup'>" . tr("Няма намерени бележки") . "</div></div>");
            }
        }
        
        $tpl->append('</div>');
        
        $tpl->prepend("<div class='withTabs'>");
        $tpl->append("</div>");
        
        $tpl->removeBlocksAndPlaces();
       
        return $tpl;
    }
    
    
    /**
     * Как ще се показва бележката
     *
     * @param stdClass $rec
     * @param boolean $fullDate
     * 
     * @return string $title
     */
    private static function getReceiptTitle($rec, $fullDate = true)
    {
        $mask = ($fullDate) ? 'd.m. H:i' : 'H:i';
        $date = dt::mysql2verbal($rec->createdOn, $mask);
        $color = dt::getColorByTime($rec->createdOn);
        $date = "<span class='timeSpan' style=\"color:#{$color}\">{$date}</span>";
        
        $amountVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($rec->total);
        if(isset($rec->returnedTotal)){
            $returnedTotalVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($rec->returnedTotal);
            $amountVerbal .= " <span class='receiptResultReturnedAmount'>(-{$returnedTotalVerbal})</span>";
        } elseif(isset($rec->revertId)){
            $symbol = html_entity_decode('&#8630;', ENT_COMPAT | ENT_HTML401, 'UTF-8');
            $amountVerbal = "{$symbol}&nbsp;<span class='receiptResultReturnedAmount'>{$amountVerbal}</span>";
        }
        
        $num = pos_Receipts::getReceiptShortNum($rec->id);
        $contragentName = cls::get($rec->contragentClass)->getVerbal($rec->contragentObjectId, 'name');
        $contragentName = str::limitLen($contragentName, 14);
        $num .= "/{$contragentName}";
        
        $title = "{$num}<div class='nowrap'><span class='spanDate'>{$date}</span> <span class='receiptResultAmount'>{$amountVerbal}</span></div>";
       
        return $title;
    }
    
    
    /**
     * Връща отговора за Ajax-а
     * 
     * @param int $receiptId
     * @param int $selectedRecId
     * @param boolean $success
     * @param boolean $refreshTable
     * @param boolean $refreshPanel
     * 
     * @return array $res
     */
    public static function returnAjaxResponse($receiptId, $selectedRecId, $success, $refreshTable = false, $refreshPanel = true, $refreshResult = true, $sound = null, $clearInput = false)
    {
        $me = cls::get(get_called_class());
        $Receipts = cls::get('pos_Receipts');
        
        // Форсиране на обновяването на мастъра, за да е сигурно че данните в бележката са актуални
        $Receipts->flushUpdateQueue($receiptId);
        $rec = $Receipts->fetch($receiptId, '*', false);
        
        $operation = Mode::get("currentOperation{$rec->id}");
        $string = Mode::get("currentSearchString{$rec->id}");
        
        $res = array();
        if($success === true){
            
            if($refreshPanel === true){
                $toolsTpl = $me->getCommandPanel($rec);
                
                // Ще се реплейсва и пулта
                $resObj = new stdClass();
                $resObj->func = 'html';
                $resObj->arg = array('id' => 'tools-holder', 'html' => $toolsTpl->getContent(), 'replace' => true);
                $res[] = $resObj;
            }
            
            if($refreshTable === true){
                $receiptTpl = $me->getReceipt($rec);
                
                $resObj = new stdClass();
                $resObj->func = 'html';
                $resObj->arg = array('id' => 'receipt-table', 'html' => $receiptTpl->getContent(), 'replace' => true);
                $res[] = $resObj;

                $resObj = new stdClass();
                $resObj->func = 'calculateWidth';
                $res[] = $resObj;
                
                $resObj = new stdClass();
                $resObj->func = 'scrollToHighlight';
                $res[] = $resObj;
            }
            
            if($refreshResult === true){
                
                // Ще се реплейсват резултатите
                $resultTpl = $me->renderResult($rec, $operation, $string, $selectedRecId);
                $resObj = new stdClass();
                $resObj->func = 'html';
                $resObj->arg = array('id' => 'result-holder', 'html' => $resultTpl->getContent(), 'replace' => true);
                $res[] = $resObj;
            }            
            
            $resObj = new stdClass();
            $resObj->func = 'prepareResult';
            $res[] = $resObj;
            
            $resObj = new stdClass();
            $resObj->func = 'afterload';
            $res[] = $resObj;
            
            $resObj = new stdClass();
            $resObj->func = 'makeTooltipFromTitle';
            $res[] = $resObj;
            
            $resObj = new stdClass();
            $resObj->func = 'openCurrentPosTab';
            $res[] = $resObj;
        }
       
        $addedProduct = Mode::get("productAdded{$receiptId}");
        
        $resObj = new stdClass();
        $resObj->func = 'toggleAddedProductFlag';
        $resObj->arg = array('flag' => !empty($addedProduct) ? true : false);
        $res[] = $resObj;
        
        Mode::setPermanent("productAdded{$receiptId}", null);
        
        
        // Добавяне на звук
        if(isset($sound) && in_array($sound, array('add', 'edit', 'delete'))){
            $resObj = new stdClass();
            $resObj->func = 'Sound';
            
            $const = ($sound == 'delete') ? 'TERMINAL_DELETE_SOUND' : (($sound == 'edit') ? 'TERMINAL_EDIT_SOUND' : 'TERMINAL_ADD_SOUND');
            $sound = pos_Setup::get($const);
            $resObj->arg = array('soundMp3' => sbf("pos/sounds/{$sound}.wav", ''));
            $res[] = $resObj;
        }
        
        // Показване веднага на чакащите статуси
        $hitTime = Request::get('hitTime', 'int');
        $idleTime = Request::get('idleTime', 'int');
        $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
        
        $res = array_merge($res, (array) $statusData);
        Mode::setPermanent("lastEditedRow", null);
        
        return $res;
    }
}
