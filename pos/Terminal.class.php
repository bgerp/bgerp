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
    protected static $operationShortcuts = 'operation-add=Ctrl A,operation-payment=Ctrl Z,operation-quantity=Ctrl S,operation-text=Ctrl E,operation-contragent=Ctrl K,operation-receipts=Ctrl B,enlarge=F2,print=Ctrl P,keyboard=Ctrl V,exit=Ctrl X,reject=Ctrl O,help=F1,delete=Ctrl I';

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
        $headerData->contragentId = (!empty($rec->transferedIn)) ? sales_Sales::getLink($rec->transferedIn, 0, array('ef_icon' => false)) : cls::get($rec->contragentClass)->getTitleById($rec->contragentObjectId);
        
        $tpl->append(ht::createImg(array('path' => 'img/16/bgerp.png')), 'OTHER_ELEMENTS');
        $tpl->placeObject($headerData);        
        $Receipts->invoke('AfterRenderterminalHeader', array(&$tpl, $rec));
        $tpl->replace(' bgERP', 'OTHER_ELEMENTS');
        
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
                $modalTpl = new core_ET('ART');
                $productRec = cat_Products::fetch($enlargeObjectId);
                $modalTpl = getTplFromFile('pos/tpl/terminal/SingleLayoutProductModal.shtml');
                
                Mode::push('text', 'xhtml');
                $packData = (object)array('masterMvc' => cls::get('cat_Products'), 'masterId' => $enlargeObjectId);
                cls::get('cat_products_Packagings')->preparePackagings($packData);
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
                        
                        $inStock = pos_Stocks::getQuantityByStore($productRec->id, $storeId);
                        $inStockVerbal = core_Type::getByName('double(smartRound)')->toVerbal($inStock);
                        $inStockVerbal = ht::styleIfNegative($inStockVerbal, $inStock);
                        
                        $storeRow->inStock .= "{$inStockVerbal} " . cat_UoM::getShortName($productRec->measureId);
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
                $inputUrl = array('pos_ReceiptDetails', 'addProduct', 'receiptId' => $rec->id);
                if(isset($rec->revertId) && $rec->revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT){
                    $keyupUrl = null;
                }
                
                break;
            case 'quantity':
                $inputUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setquantity');
                break;
            case 'discount':
                $inputUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setdiscount');
                $keyupUrl = null;
                break;
            case 'price':
                $inputUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setprice');
                $keyupUrl = null;
                break;
            case 'text':
                $inputUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'settext');
                $keyupUrl = null;
                break;
            case 'payment':
                $keyupUrl = null;
                break;
            case 'batch':
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
        
        return static::returnAjaxResponse($rec->id, $selectedRecId, true, false, $refreshPanel);
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

        $tpl = new core_ET("<div class='divider'>[#receiptName#]</div><div class='grid'>[#details#]</div>");
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
            
            $link = ht::createLink('Всички', $reloadUrl, 'Наистина ли желаете да заредите всички редове от оригиналната бележка|*?', $reloadAttr);
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
                $btn = ht::createElement("div", array('id' => "batch{$cnt}",'class' => 'resultBatch posBtns navigable', 'title' => 'Артикулът да е без партида', 'data-url' => toUrl($dataUrl, 'local')), 'Без партида', true);
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
        } else {
            $tpl->append(tr('Нямат партидност'), 'BATCHES');
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
        $price = pos_Receipts::getDisplayPrice($selectedRec->price, $selectedRec->param, null, $rec->pointId, 1);
        $discountsArr = pos_ReceiptDetails::getSuggestedProductDiscounts($selectedRec->productId, $price);
        if($stringPrice = core_Type::getByName('percent')->fromVerbal($string)){
            if($stringPrice < 1 && $stringPrice > 0){
                $discountsArr["{$stringPrice}"] = $stringPrice;
            }
        }
        
        if(!empty($selectedRec->discountPercent)){
            $discountsArr =  array('0' => '0') + $discountsArr;
        }

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
            
            
            if(!empty($stringInput)){
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
                $cQuery->fetch("#vatId = '{$stringInput}' OR #uicId = '{$stringInput}'");
                $cQuery->show('id,folderId');
                while($cRec = $cQuery->fetch()){
                    $contragents["{$companyClassId}|{$cRec->id}"] = (object)array('contragentClassId' => crm_Companies::getClassId(), 'contragentId' => $cRec->id, 'title' => crm_Companies::getTitleById($cRec->id));
                    $count++;
                }
                
                // Ако има лице с такова егн или данъчен номер
                $pQuery = crm_Persons::getQuery();
                $pQuery->fetch("#egn = '{$stringInput}' OR #vatId = '{$stringInput}'");
                $pQuery->show('id,folderId');
                while($pRec = $pQuery->fetch()){
                    $contragents["{$personClassId}|{$pRec->id}"] = (object)array('contragentClassId' => crm_Persons::getClassId(), 'contragentId' => $pRec->id, 'title' => crm_Persons::getTitleById($cRec->id));
                    $count++;
                }
            } else {
                $maxContragents = pos_Points::getSettings($rec->pointId, 'maxSearchContragentStart');
            }
            
            $searchString = plg_Search::normalizeText($stringInput);
            foreach (array('crm_Companies', 'crm_Persons') as $ContragentClass){
                $cQuery = $ContragentClass::getQuery();
                $cQuery->where("#state != 'rejected' AND #state != 'closed'");
                $cQuery->show('id,folderId,name');
                
                // Обикалят се всички фирми/лице които съдържат търсения стринг в името си
                $classId = ($ContragentClass == 'crm_Companies') ? $companyClassId : $personClassId;
                while($cRec = $cQuery->fetch()){
                    $name = plg_Search::normalizeText($cRec->name);
                    
                    // Ако го съдържат в името си се добавят
                    if(empty($searchString) || strpos($name, $searchString) !== false){
                        if(!array_key_exists("{$classId}|{$cRec->id}", $contragents)){
                            $contragents["{$classId}|{$cRec->id}"] = (object)array('contragentClassId' => $ContragentClass::getClassId(), 'contragentId' => $cRec->id, 'title' => $ContragentClass::getTitleById($cRec->id));
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
                    $cQuery->show('id,folderId,name');
                    
                    // Обикалят се всички фирми/лице които съдържат търсения стринг в името си
                    $classId = ($ContragentClass == 'crm_Companies') ? $companyClassId : $personClassId;
                    
                    plg_Search::applySearch($stringInput, $cQuery);
                    while($cRec = $cQuery->fetch()){
                        if(!array_key_exists("{$classId}|{$cRec->id}", $contragents)){
                            $contragents["{$classId}|{$cRec->id}"] = (object)array('contragentClassId' => $ContragentClass::getClassId(), 'contragentId' => $cRec->id, 'title' => $ContragentClass::getTitleById($cRec->id));
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
                
                $holderDiv = ht::createElement('div', $divAttr, $obj->title, true);
                $temp->append($holderDiv);
                $cnt++;
            }
            $tpl->append(ht::createElement('div', array('class' => 'grid'), $temp, true));
        } else {
            $tpl = new core_ET("");
            
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
            
            $contragentName = cls::get($rec->contragentClass)->getTitleById($rec->contragentObjectId);
            $tpl->prepend("<div class='contragentName clearfix21'>{$contragentName}</div>");
            
            $locationArr = crm_Locations::getContragentOptions($rec->contragentClass, $rec->contragentObjectId);
            if(countR($locationArr)){
                $tpl->append(tr("|*<div class='divider'>|Локации|*</div>"));
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
                    
                    $holderDiv = ht::createElement('div', $locationAttr, $locationName, true);
                    $tpl->append($holderDiv);
                }
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
        $tpl = new core_ET(tr("|*<div class='grid'>[#PAYMENTS#]</div><div class='divider'>|Приключване|*</div>[#CLOSE_BTNS#]"));
        
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
        $closeBtn = ht::createLink('Приключено', $contoUrl, $warning, array('class' => "{$disClass} posBtns payment closeBtn"));
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
        while ($productRec = $query->fetch()) {
            $count++;
            Mode::push('text', 'plain');
            $quantity = core_Type::getByName('double(smartRound)')->toVerbal($productRec->quantity);
            Mode::pop('text', 'plain');
            if(!$productRec->value)  continue; // Да не гърми при лоши данни
            $packagingId = cat_UoM::getSmartName($productRec->value, 1);
            $btnCaption =  "{$quantity} " . tr(cat_UoM::getSmartName($productRec->value, $productRec->quantity));
            $packDataName = cat_UoM::getTitleById($productRec->value);
            $frequentPackButtons[] = ht::createElement("div", array('id' => "packaging{$count}", 'class' => "{$baseClass} packWithQuantity", 'data-quantity' => $productRec->quantity, 'data-pack' => $packDataName, 'data-url' => $dataUrl), $btnCaption, true);
        }
        
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

        $storesTpl = new core_ET("");
        foreach ($storeBtns as $storeBtn){
            $storesTpl->append($storeBtn);
        }

        $storesTpl = ht::createElement('div', array('class' => 'grid'), $storesTpl, true);
        $tpl->append($storesTpl, 'STORE_BUTTONS');

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
        core_Ajax::subscribe($tpl, array($this, 'autoRefreshHeader', $rec->id), 'refreshTime', 60);
    }
    
    
    /**
     * Автоматично рефрешване на хедъра
     */
    function act_autoRefreshHeader()
    {
        core_Users::getCurrent();
        
        // Добавяме резултата
        $resObj = new stdClass();
        $resObj->func = 'html';
        $resObj->arg = array('id' => 'terminalTime', 'html' => $this->renderCurrentTime()->getContent(), 'replace' => true);
        
        return array($resObj);
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
        $searchString = plg_Search::normalizeText($string);
        $data = new stdClass();
        $data->rec = $rec;
        $data->searchString = $searchString;
        $data->baseCurrency = acc_Periods::getBaseCurrencyCode();
        $this->prepareProductTable($data);
        
        $tpl = new core_ET(" ");
        $block = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('PRODUCTS_RESULT');
        
        // Ако има категории
        $count = 0;
        if(countR($data->categoriesArr)){

            foreach ($data->categoriesArr as $categoryRec){
                $cTpl = new core_ET("");
                // Под всяка категория се рендират артикулите към нея
                $productsInCategory = array_filter($data->rows, function($a) use ($categoryRec){ return in_array($categoryRec->id, $a->favouriteCategories);});
                if(countR($productsInCategory)){

                    foreach ($productsInCategory as $row){
                        $row->elementId = "product{$count}";
                        $bTpl = clone $block;
                        $bTpl->placeObject($row);
                        $bTpl->removeBlocksAndPlaces();
                        $cTpl->append($bTpl);
                        $count++;
                    }
                    $cTpl->removeBlocksAndPlaces();

                }
                $tpl->append("<div class='divider'>{$categoryRec->name}</div>");
                $tpl->append(ht::createElement('div', array('class' => 'grid'), $cTpl, true));
            }
        } else {
            foreach ($data->rows as $row){
                $row->elementId = "product{$count}";
                $bTpl = clone $block;
                $bTpl->placeObject($row);
                $bTpl->removeBlocksAndPlaces();
                $tpl->append($bTpl);
                $count++;
            }

            $tpl->prepend("<div class='grid'>");
            $tpl->append("</div>");
        }
        
        if(!count($data->rows)){
            $tpl->prepend("<div class='resultText'>" . tr('Няма намерени артикули|*!') . "</div>");
        }
        
        return $tpl;
    }
    
    
    /**
     * Подготвя данните от резултатите за търсене
     */
    private function prepareProductTable(&$data)
    {
        $count = 0;
        $data->rows = array();
        $favouriteProductsArr = $data->categoriesArr = array();
        $sellable = array();
        
        $folderId = cls::get($data->rec->contragentClass)->fetchField($data->rec->contragentObjectId, 'folderId');
        $pQuery = cat_Products::getQuery();
        $pQuery->where("#canSell = 'yes' AND #state = 'active'");
        $pQuery->where("#isPublic = 'yes' OR (#isPublic = 'no' AND #folderId = '{$folderId}')");
        $pQuery->show('id,name,isPublic,nameEn,code,canStore,measureId');
        
        $Policy = cls::get('price_ListToCustomers');
        $maxSearchProducts = pos_Points::getSettings($data->rec->pointId, 'maxSearchProducts');
        
        // Ако не се търси подробно артикул, се показват тези от любими
        if(empty($data->searchString)){
            $favouriteProductsArr = array();
            
            // Ако има любими категории да се извлекат артикулите към тях
            $data->categoriesArr = pos_FavouritesCategories::prepareAll($data->rec->pointId);
            $categoriesIds = arr::extractValuesFromArray($data->categoriesArr, 'id');
            $favouriteQuery = pos_Favourites::getQuery();
            $favouriteQuery->likeKeylist('catId', $categoriesIds);
            $favouriteQuery->show('productId,catId');
            while($favRec = $favouriteQuery->fetch()){
                $favouriteProductsArr[$favRec->productId] = keylist::toArray($favRec->catId);
            }
            
            $pQuery->in("id", array_keys($favouriteProductsArr));
            $pQuery->orderBy('code,name', 'ASC');
            $sellable = $pQuery->fetchAll();
            
        } else {
            $count = 0;
            $maxCount = $maxSearchProducts;
            
            // Ако има артикул, чийто код отговаря точно на стринга, той е най-отгоре
            $foundRec = cat_Products::getByCode($data->searchString);
            if(isset($foundRec->productId) && (!isset($data->revertReceiptId) || (isset($data->revertReceiptId) && pos_ReceiptDetails::fetchField("#receiptId = {$data->revertReceiptId} AND #productId = {$foundRec->productId}")))){
                $sellable[$foundRec->productId] = (object)array('id' => $foundRec->productId, 'canStore' => cat_Products::fetchField($foundRec->productId, 'canStore'), 'packId' => isset($foundRec->packagingId) ? $foundRec->packagingId : null);
                $count++;
            }
            
            // След това се добавят артикулите, които съдържат стринга в името и/или кода си
            $pQuery1 = clone $pQuery;
            $pQuery1->orderBy('code,name,measureId', 'ASC');
            while($pRec1 = $pQuery1->fetch()){
                $name = plg_Search::normalizeText($pRec1->name);
                $code = plg_Search::normalizeText($pRec1->code);
                if(strpos($name, $data->searchString) !== false || strpos($code, $data->searchString) !== false){
                    $sellable[$pRec1->id] = (object)array('id' => $pRec1->id, 'canStore' => $pRec1->canStore, 'measureId' => $pRec1->measureId);
                    $count++;
                    $maxCount--;
                    if($count == $maxSearchProducts) break;
                }
            }
            
            // Ако не е достигнат лимита, се добавят и артикулите с търсене в ключовите думи
            if($count < $maxSearchProducts){
                $notInKeys = array_keys($sellable);
                $pQuery2 = clone $pQuery;
                plg_Search::applySearch($data->searchString, $pQuery2);
                if(countR($notInKeys)){
                    $pQuery2->in('id', $notInKeys);
                }
                
                while($pRec2 = $pQuery2->fetch()){
                    $sellable[$pRec2->id] = (object)array('id' => $pRec2->id, 'canStore' => $pRec2->canStore);
                    $count++;
                    $maxCount--;
                    if($count == $maxSearchProducts) break;
                }
            }
        }
        
        foreach ($sellable as $id => $pRec) {
            if(!isset($pRec->packId)){
                $packs = cat_Products::getPacks($id);
                $packId = key($packs);
            } else {
                $packId = $pRec->packId;
            }
            
            $packRec = cat_products_Packagings::getPack($id, $packId);
            $perPack = (is_object($packRec)) ? $packRec->quantity : 1;
            $price = $Policy->getPriceInfo($data->rec->contragentClass, $data->rec->contragentObjectId, $id, $packId, 1, $data->rec->createdOn, 1, 'yes');
            
            // Ако няма цена също го пропускаме
            if (empty($price->price)) continue;
            
            $vat = cat_Products::getVat($id);
            $price = $price->price * $perPack;
            
            
            $obj = (object) array('productId' => $id, 'measureId' => $pRec->measureId, 'price' => $price, 'packagingId' => $packId, 'vat' => $vat);
            if ($pRec->canStore == 'yes') {
                $obj->stock = pos_Stocks::getBiggestQuantity($id, $data->rec->pointId);
                $obj->stock /= $perPack;
            }
            
            // Обръщаме реда във вербален вид
            $data->rows[$id] = $this->getVerbalSearchresult($obj, $data);
            $data->rows[$id]->CLASS = ' pos-add-res-btn navigable enlargable';
            $data->rows[$id]->DATA_URL = (pos_ReceiptDetails::haveRightFor('add', $obj)) ? toUrl(array('pos_ReceiptDetails', 'addProduct', 'receiptId' => $data->rec->id), 'local') : null;
            $data->rows[$id]->DATA_ENLARGE_OBJECT_ID = $id;
            $data->rows[$id]->DATA_ENLARGE_CLASS_ID = cat_Products::getClassId();
            $data->rows[$id]->DATA_MODAL_TITLE = cat_Products::getTitleById($id);
            $data->rows[$id]->favouriteCategories = array();
            $data->rows[$id]->id = $pRec->id;
            if(array_key_exists($id, $favouriteProductsArr)){
                $data->rows[$id]->favouriteCategories = $favouriteProductsArr[$id];
            }
            
            if($pRec->measureId != cat_UoM::fetchBySysId('pcs')->id){
                $data->rows[$id]->measureId = tr(cat_UoM::getVerbal($pRec->measureId, 'name'));
            }
            
            $count++;
        }
    }
    
    
    /**
     * Връща вербалното представяне на един ред от резултатите за търсене
     */
    private function getVerbalSearchResult($obj, &$data)
    {
        $Double = core_Type::getByName('double(decimals=2)');
        $row = new stdClass();
        
        $row->price = currency_Currencies::decorate($Double->toVerbal($obj->price));
        $row->stock = core_Type::getByName('double(smartRound)')->toVerbal($obj->stock);
        $packagingId = ($obj->packagingId) ? $obj->packagingId : $obj->measureId;
        $row->packagingId = cat_UoM::getSmartName($packagingId, $obj->stock);
        $obj->receiptId = $data->rec->id;
        
        $productRec = cat_Products::fetch($obj->productId, 'code');
        $row->productId = mb_subStr(cat_Products::getVerbal($obj->productId, 'name'), 0, 95);
        $row->code = (!empty($productRec->code)) ? cat_Products::getVerbal($obj->productId, 'code') : "Art{$obj->productId}";
        
        $row->stock = ht::styleNumber($row->stock, $obj->stock, 'green');
        $row->stock = "{$row->stock} <span class='pos-search-row-packagingid'>{$row->packagingId}</span>";
        $row->photo = $this->getPosProductPreview($obj->productId, 64, 64);
        
        return $row;
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
        $tpl = new core_ET("");
        
        $string = plg_Search::normalizeText($string);
        $addUrl = (pos_Receipts::haveRightFor('add')) ? array('pos_Receipts', 'new', 'forced' => true) : array();
        $revertDefaultUrl = (pos_Receipts::haveRightFor('revert', pos_Receipts::DEFAULT_REVERT_RECEIPT)) ? array('pos_Receipts', 'revert', pos_Receipts::DEFAULT_REVERT_RECEIPT, 'ret_url' => true) : array();
        $revertUrl = (pos_Receipts::haveRightFor('revert', $rec->id)) ? array('pos_Receipts', 'revert', $rec->id, 'ret_url' => true) : array();
        
        $disabledClass = (pos_Receipts::haveRightFor('add')) ? 'navigable' : 'disabledBtn';
        $disabledRevertClass = countR($revertDefaultUrl) ? 'navigable' : 'disabledBtn';
        $disabledRevertWarning = countR($revertDefaultUrl) ? 'Наистина ли искате да създадете нова сторно бележка|*?' : false;
        $maxSearchReceipts = pos_Points::getSettings($rec->pointId, 'maxSearchReceipts');
       
        // Намираме всички чернови бележки и ги добавяме като линк
        $query = pos_Receipts::getQuery();
        $query->XPR('createdDate', 'date', 'DATE(#createdOn)');
        $query->where("#state != 'rejected'");
        $query->orderBy("#createdDate,#id", 'DESC');
        $query->limit($maxSearchReceipts);
        if(!empty($string)){
            plg_Search::applySearch($string, $query);
        }
        
        // Добавяне на бутона за нова бележка да е в блока 'Днес'
        $dateBlock = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('RECEIPT_RESULT');
        
        $addBtn = ht::createLink("Нова бележка", $addUrl, null, "id=receiptnew,class=pos-notes posBtns newNoteBtn {$disabledClass},title=Създаване на нова бележка");
        $tpl->append($addBtn);
        
        if(countR($revertUrl)){
            $revertBtn = ht::createLink("Сторниране", $revertUrl, 'Наистина ли искате да сторнирате текущата бележката|*?', "id=revertthis,class=pos-notes posBtns revertReceiptBtn {$disabledClass},title=Сторниране на текущата бележка");
            $tpl->append($revertBtn);
        } else {
            $revertDefaultBtn = ht::createLink("Сторно бележка", $revertDefaultUrl, $disabledRevertWarning, "id=receiptrevertdefault,class=pos-notes posBtns newNoteBtn revertReceiptBtn {$disabledRevertClass},title=Създаване на нова сторно бележка");
            $tpl->append($revertDefaultBtn);
        }
        
        // Групиране на записите по дата
        $arr = array();
        while ($receiptRec = $query->fetch()) {
            if(!array_key_exists($receiptRec->createdDate, $arr)){
                $arr[$receiptRec->createdDate] = clone $dateBlock;
                $arr[$receiptRec->createdDate]->replace(dt::mysql2verbal($receiptRec->createdDate, 'smartDate'), 'groupName');
            }
            
            $class = isset($receiptRec->revertId) ? 'revertReceipt' : '';
            $openUrl = (pos_Receipts::haveRightFor('terminal', $receiptRec->id)) ? array('pos_Terminal', 'open', 'receiptId' => $receiptRec->id, 'opened' => true) : array();
            $class .= (count($openUrl)) ? ' navigable' : ' disabledBtn';
            $class .= ($receiptRec->id == $rec->id) ? ' currentReceipt' : '';
            
            $btnTitle = self::getReceiptTitle($receiptRec);
            $btnTitle = ($rec->pointId != $receiptRec->pointId) ? ht::createHint($btnTitle, "Бележката е от друг POS") : $btnTitle;
            $row = ht::createLink($btnTitle, $openUrl, null, array('id' => "receipt{$receiptRec->id}", 'class' => "pos-notes posBtns {$class} state-{$receiptRec->state} enlargable", 'title' => 'Отваряне на бележката', 'data-enlarge-object-id' => $receiptRec->id, 'data-enlarge-class-id' => pos_Receipts::getClassId(), 'data-modal-title' => strip_tags(pos_Receipts::getRecTitle($receiptRec))));
            $arr[$receiptRec->createdDate]->append($row, 'element');
        }
        
        foreach ($arr as $blockTpl){
            $blockTpl->removeBlocksAndPlaces();
            $tpl->append($blockTpl);
        }
        $tpl = ht::createElement('div', array('class' => 'grid'), $tpl, true);
        
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
        
        $amountVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($rec->total);
        if(isset($rec->returnedTotal)){
            $returnedTotalVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($rec->returnedTotal);
            $amountVerbal .= " <span class='receiptResultReturnedAmount'>(-{$returnedTotalVerbal})</span>";
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
    public static function returnAjaxResponse($receiptId, $selectedRecId, $success, $refreshTable = false, $refreshPanel = true, $refreshResult = true)
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
        }
        
        // Показване веднага на чакащите статуси
        $hitTime = Request::get('hitTime', 'int');
        $idleTime = Request::get('idleTime', 'int');
        $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
        
        $res = array_merge($res, (array) $statusData);
        
        return $res;
    }
}
