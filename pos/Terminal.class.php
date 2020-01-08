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
     * При търсене до колко продукта да се показват в таба
     */
    protected $maxSearchProducts = 20;
    
    
    /**
     * При търсене на бележки до колко да се показват
     */
    protected static $maxSearchReceipts = 100;
    
    
    /**
     * Полета
     */
    protected $fieldArr = array('payments', 'policyId', 'caseId', 'storeId');
    
    

    
    /**
     * Кои операции са забранени за нови бележки
     */
    protected static $forbiddenOperationOnEmptyReceipts = array('discount', 'price', 'text', 'quantity', 'payment', 'batch');
    
    
    /**
     * Кои операции са забранени за бележки с направено плащане
     */
    protected static $forbiddenOperationOnReceiptsWithPayment = array('discount', 'price', 'quantity', 'add', 'batch', 'text');
    
    
    /**
     * Кои операции са забранени за сторниращите бележки
     */
    protected static $forbiddenOperationOnRevertReceipts = array('discount', 'price');
    
    
    /**
     * Кои операции са забранени за сторниращите бележки
     */
    protected static $allowedOperationOnNonDraftReceipts = 'receipts=Бележки,revert=Сторно,payment=Плащане';
    
    
    /**
     * Бутони за бърз достъп до терминала
     */
    protected static $operationShortcuts = 'operation-add=A,operation-payment=P,operation-quantity=K,operation-price=Z,operation-discount=5,operation-text=T,operation-contragent=C,operation-receipts=R,enlarge=F,print=3,operation-batch=B,keyboard=V,exit=X,reject=N';

    /**
     * Кои са разрешените операции
     */
    protected static $operationsArr = "add=Добавяне на артикул,payment=Плащане по бележката,quantity=Промяна на количеството/опаковката,batch=Задаване на партида на артикула,price=Задаване на цена,discount=Задаване на отстъпка,text=Текст,contragent=Избор на контрагент,receipts=Преглед на бележките,revert=Сторниране на бележка";


    /**
     * Икони за операциите
     */
    protected static $operationImgs = array('enlarge' => 'pos/img/search.png', 'print' => 'pos/img/printer.png', 'keyboard' => 'pos/img/keyboard.png', 'operation-add' => 'pos/img/а.png', 'operation-text' =>  'pos/img/comment.png', 'operation-discount' => 'pos/img/sale.png', 'operation-payment' => 'pos/img/dollar.png',  'operation-price' => 'pos/img/price-tag2.png', 'operation-quantity' => 'pos/img/multiply.png',  'operation-add' => 'pos/img/a.png',  'operation-batch' => 'pos/img/P_32x32.png',  'operation-receipts' => 'pos/img/receipt.png', 'operation-contragent' => 'pos/img/right-arrow.png', 'operation-revert' => 'pos/img/receipt.png', 'close' => 'pos/img/close.png', 'transfer' => 'pos/img/transfer.png', 'reject' => 'pos/img/cancel.png', 'delete' => 'pos/img/delete.png', 'reload' => "pos/img/reload.png");

    
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
            $defaultSearchString = Mode::get("currentSearchString{$rec->id}");
            if(!Mode::is('printing')){
                
                // Добавяне на табовете под бележката
                $toolsTpl = $this->getCommandPanel($rec, $defaultOperation);
                $tpl->replace($toolsTpl, 'TAB_TOOLS');
                
                // Добавяне на табовете показващи се в широк изглед отстрани
                $lastRecId = pos_ReceiptDetails::getLastRec($rec->id, 'sale')->id;
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
                                    'contragentId' => cls::get($rec->contragentClass)->getTitleById($rec->contragentObjectId),
                                    'userId' => core_Users::getVerbal(core_Users::getCurrent(), 'nick'));
        
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
        
        if(empty($enlargeClassId) || empty($enlargeObjectId)) {
            
            return array();
        }
        
        $EnlargeClass = cls::get($enlargeClassId);
        
        switch ($enlargeClassId){
            case cat_Products::getClassId():
                $modalTpl = new core_ET('ART');
                break;
            case pos_Receipts::getClassId():
                $modalTpl = $this->getReceipt($enlargeObjectId);
                break;
            default:
                $singleLayoutFile = ($enlargeClassId == 'crm_Companies') ? 'pos/tpl/terminal/SingleLayoutCompanyModal.shtml' : (($enlargeClassId == 'pos_Receipts') ? 'pos/tpl/terminal/modalCompany.shtml' : 'pos/tpl/terminal/SingleLayoutPersonModal.shtml');
                
                Mode::push('noWrapper', true);
                Mode::push("singleLayout-{$EnlargeClass->className}{$enlargeObjectId}", getTplFromFile($singleLayoutFile));
                $modalTpl = Request::forward(array('Ctr' => $EnlargeClass->className, 'Act' => 'single', 'id' => $enlargeObjectId));
                Mode::pop("singleLayout-{$EnlargeClass->className}{$enlargeObjectId}");
                Mode::pop('noWrapper');
        }
        
        
        //$document = doc_Containers::getDocument(cat_Products::fetchField($rec->productId, 'containerId'));
        
        // Рендиране на изгледа на артикула
        //Mode::push('noBlank', true);
        //$docHtml = $document->getInlineDocumentBody('xhtml');
       // Mode::pop('noBlank', true);
        
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
                break;
            case 'quantity':
                $inputUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setquantity');
                $keyupUrl = null;
                break;
            case 'discount':
                $inputUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setdiscount');
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
            case 'revert';
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
        $inputValue = ($operation == 'payment') ? $value : Mode::get("currentSearchString{$rec->id}");
        
        $searchUrl = toUrl(array($this, 'displayOperation', 'receiptId' => $rec->id), 'local');
        $params = array('name' => 'ean', 'value' => $inputValue, 'type' => 'text', 'class'=> 'large-field select-input-pos', 'data-url' => $inputUrl, 'data-keyupurl' => $keyupUrl, 'title' => 'Въвеждане', 'list' => 'suggestions', 'autocomplete' => 'off');
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
            $disabled = (empty($detailsCount) && in_array($operation, self::$forbiddenOperationOnEmptyReceipts)) || (!empty($rec->paid) && in_array($operation, self::$forbiddenOperationOnReceiptsWithPayment)) || (isset($rec->revertId) && $rec->revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT && in_array($operation, self::$forbiddenOperationOnRevertReceipts));
            
            if($rec->state != 'draft' && !array_key_exists($operation, $allowedOperationsForNonDraftReceipts)) {
                $disabled = true;
            } elseif($operation == 'discount' && pos_Setup::get('SHOW_DISCOUNT_BTN') != 'yes'){
                $disabled = true;
            } elseif($operation == 'batch' && !core_Packs::isInstalled('batch')){
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
        $enlargeAttr = array('title' => 'Преглед на избрания артикул', 'data-url' => toUrl(array('pos_Terminal', 'enlargeElement'), 'local'), 'class' => "enlargeProductBtn");
        $img = ht::createImg(array('path' => self::$operationImgs["enlarge"]));
        $buttons["enlarge"] = (object)array('body' => $img, 'attr' => $enlargeAttr);
        
        // Бутон за печат на бележката
        $img = ht::createImg(array('path' => self::$operationImgs["print"]));
        $buttons["print"] = (object)array('body' => $img, 'attr' => array('title' => 'Печат на бележката', 'class' => 'operationBtn printBtn'), 'linkUrl' => array('pos_Terminal', 'Open', 'receiptId' => $rec->id, 'Printing' => true), 'newWindow' => true);
        
        // Бутон за увеличение на избрания артикул
        $img = ht::createImg(array('path' => self::$operationImgs["keyboard"]));
        $buttons["keyboard"] = (object)array('body' => $img, 'attr' => array('title' => 'Отваряне на виртуална клавиатура', 'data-url' => toUrl(array('pos_Terminal', 'Keyboard'), 'local'), 'class' => "keyboardBtn"));
        
        $reloadAttr = array('class' => "reloadBtn", 'title' => 'Зареждане на артикулите от сторнираната бележка');
        $reloadUrl = (pos_ReceiptDetails::haveRightFor('load', (object)array('receiptId' => $rec->id))) ? array('pos_ReceiptDetails', 'load', 'receiptId' => $rec->id, 'from' => $rec->revertId, 'ret_url' => true) : null;
        if(empty($rec->revertId) || $rec->revertId == pos_Receipts::DEFAULT_REVERT_RECEIPT || !count($reloadUrl)){
            $reloadAttr['class'] .= ' disabledBtn';
            $reloadAttr['disabled'] = 'disabled';
            unset($reloadAttr['title']);
            $reloadUrl = null;
        }
        
        $img = ht::createImg(array('path' => self::$operationImgs["reload"]));
        $buttons["reload"] = (object)array('body' => $img, 'attr' => $reloadAttr, 'linkUrl' => $reloadUrl);
        
        $img = ht::createImg(array('path' => self::$operationImgs["keyboard"]));
        $buttons["keyboard"] = (object)array('body' => $img, 'attr' => array('title' => 'Отваряне на виртуална клавиатура', 'data-url' => toUrl(array('pos_Terminal', 'Keyboard'), 'local'), 'class' => "keyboardBtn"));
        
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
        if($selectedRecId = Request::get('recId', 'int')){
            $selectedRec = pos_ReceiptDetails::fetch($selectedRecId, '*', false);
        }
        
        if(!is_object($selectedRec)){
            $selectedRecId = pos_ReceiptDetails::getLastRec($id, 'sale')->id;
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
                $revertId = (isset($rec->revertId) && $rec->revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT) ? $rec->revertId : null;
                $res = $this->getResultProducts($rec, $string, $revertId);
                break;
            case 'receipts':
                $res = $this->renderResultReceipts($rec, $string, $selectedRec);
                break;
            case 'quantity':
                $res = $this->renderResultQuantity($rec, $string, $selectedRec);
                break;
            case 'discount':
                $res = $this->renderResultDiscount($rec, $string, $selectedRec);
                break;
            case 'text':
                $res = $this->renderResultText($rec, $string, $selectedRec);
                break;
            case 'price':
                $res = $this->renderResultPrice($rec, $string, $selectedRec);
                break;
            case 'payment':
                $res = $this->renderResultPayment($rec, $string, $selectedRec);
                break;
            case 'revert':
                $res = $this->renderResultRevertReceipts($rec, $string, $selectedRec);
                break;
            case 'contragent':
                $res = $this->renderResultContragent($rec, $string, $selectedRec);
                break;
            case 'batch':
                $res = $this->renderResultBatches($rec, $string, $selectedRec);
                break;
            default:
                $res = " ";
                break;
        }
        
        return new core_ET($res);
    }
    
    
    /**
     * Рендира таба с партиди
     * 
     * @param stdClass $rec
     * @param string $currOperation
     * @param string $string
     * @param int|null $selectedRecId
     * 
     * @return core_ET
     */
    private function renderResultBatches($rec, $string, $selectedRec)
    {
        expect(core_Packs::isInstalled('batch'));
        $receiptRec = pos_ReceiptDetails::fetchRec($selectedRec);
        
        $tpl = new core_ET(" ");
        if($Def = batch_Defs::getBatchDef($receiptRec->productId)){
            $dataUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setbatch');
            
            $cnt = 0;
            $btn = ht::createElement("div", array('id' => "batch{$cnt}",'class' => 'resultBatch posBtns navigable', 'title' => 'Артикулът да е без партида', 'data-url' => toUrl($dataUrl, 'local')), 'Без партида', true);
            $tpl->append($btn);
            
            $batchesInStore = batch_Items::getBatchQuantitiesInStore($receiptRec->productId, $receiptRec->storeId, $rec->valior);
            foreach ($batchesInStore as $batch => $quantity){
                $cnt++;
                $dataUrl['string'] = urlencode($batch);
                
                $measureId = cat_Products::fetchField($receiptRec->productId, 'measureId');
                $quantity = cat_UoM::round($measureId, $quantity);
                $measureName = tr(cat_UoM::getSmartName($measureId, $quantity));
                
                $quantityVerbal = core_Type::getByName('double(smartRound)')->toVerbal($quantity);
                $quantityVerbal = ht::styleIfNegative($quantityVerbal, $quantity);
                $batchVerbal = $Def->toVerbal($batch) . "<span class='small'>({$quantityVerbal} {$measureName})</span>";
                
                $btn = ht::createElement("div", array('id' => "batch{$cnt}",'class' => 'resultBatch posBtns navigable', 'title' => 'Избор на партидата', 'data-url' => toUrl($dataUrl, 'local')), $batchVerbal, true);
                $tpl->append($btn);
            }
        } else {
            $tpl->append(tr('Нямат партидност'));
        }
        
        return $tpl;
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
        $tpl = new core_ET(tr("|*<div class='divider'>|Най-използвани текстове|*</div>"));
        $texts = pos_ReceiptDetails::getMostUsedTexts();
        
        $count = 0;
        foreach ($texts as $text){
            $dataUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'settext', 'string' => $text);
            $dataUrl = toUrl($dataUrl, 'local');
            
            $element = ht::createElement('div', array("id" => "text{$count}", "class" => "textResult navigable posBtns", 'data-url' => $dataUrl), $text, true);
            $tpl->append($element);
            $count++;
        }
        $tpl = ht::createElement('div', array('class' => 'displayFlex'), $tpl, true);
        
        return $tpl;
    }
    
    
    /**
     * Рендиране на таблицата с наличните отстъпки
     * 
     * @param stdClass $rec - записа на бележката
     * @param string $string - въведения стринг за търсене
     * @param stdClass|null $selectedRec - селектирания ред (ако има)
     * 
     * @return core_ET
     */
    private function renderResultDiscount($rec, $string, $selectedRec)
    {
        $discountsArr = array('0', '10', '20', '30', '40', '50', '60', '70', '80', '90', '100');
        $string = trim(str_replace('%', '', $string));
        
        $tpl = new core_ET("");
        foreach ($discountsArr as $discAmount){
            $url = toUrl(array('pos_ReceiptDetails', 'updateRec', 'receiptId' => $rec->id, 'action' => 'setdiscount', 'string' => "{$discAmount}"), 'local');
            $element = ht::createElement("div", array('id' => "discount{$discAmount}", 'class' => 'navigable posBtns discountBtn', 'data-url' => $url), "{$discAmount} %", true);
            $tpl->append($element);
        }
        $tpl = ht::createElement('div', array('class' => 'displayFlex'), $tpl, true);
        
        return $tpl;
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
            
            $newCompanyAttr = array('id' => 'contragentnew', 'data-url' => toUrl(array('crm_Companies', 'add')), 'class' => 'posBtns contragentLinkBtns');
            if(!crm_Companies::haveRightFor('add')){
                $newCompanyAttr['disabled'] = 'disabled';
                $newCompanyAttr['class'] .= ' disabledBtn';
                unset($newCompanyAttr['data-url']);
            } else {
                $newCompanyAttr['class'] .= ' navigable openInNewTab';
            }
            $holderDiv = ht::createElement('div', $newCompanyAttr, 'Нова фирма', true);
            $tpl->append($holderDiv);
            $tpl->append(tr("|*<div class='divider'>|Контрагенти|*</div>"));
            
            
            if(!empty($string)){
                $stringInput = core_Type::getByName('varchar')->fromVerbal($string);
                if($cardRec = crm_ext_Cards::fetch("#number = '{$stringInput}'")){
                    $contragents["{$cardRec->contragentClassId}|{$cardRec->contragentId}"] = (object)array('contragentClassId' => $cardRec->contragentClassId, 'contragentId' => $cardRec->contragentId, 'title' => cls::get($cardRec)->getTitleById($cardRec->contragentId));
                }
                
                $personClassId = crm_Persons::getClassId();
                $companyClassId = crm_Companies::getClassId();
                
                $cQuery = crm_Companies::getQuery();
                $cQuery->fetch("#vatId = '{$stringInput}' OR #uicId = '{$stringInput}'");
                $cQuery->show('id,folderId');
                while($cRec = $cQuery->fetch()){
                    $contragents["{$companyClassId}|{$cRec->id}"] = (object)array('contragentClassId' => crm_Companies::getClassId(), 'contragentId' => $cRec->id, 'title' => crm_Companies::getTitleById($cRec->id));
                }
                
                $pQuery = crm_Persons::getQuery();
                $pQuery->fetch("#egn = '{$stringInput}' OR #vatId = '{$stringInput}'");
                $pQuery->show('id,folderId');
                while($pRec = $pQuery->fetch()){
                    $contragents["{$personClassId}|{$pRec->id}"] = (object)array('contragentClassId' => crm_Persons::getClassId(), 'contragentId' => $pRec->id, 'title' => crm_Persons::getTitleById($cRec->id));
                }
            }
            
            foreach (array('crm_Companies', 'crm_Persons') as $ContragentClass){
                $cQuery = $ContragentClass::getQuery();
                $stringInput = plg_Search::normalizeText($stringInput);
                plg_Search::applySearch($stringInput, $cQuery);
                $cQuery->where("#state != 'rejected' AND #state != 'closed'");
                $cQuery->show('id,folderId');
                
                $classId = ($ContragentClass == 'crm_Companies') ? $companyClassId : $personClassId;
                while($cRec = $cQuery->fetch()){
                    if(!array_key_exists("{$classId}|{$cRec->id}", $contragents)){
                        $contragents["{$classId}|{$cRec->id}"] = (object)array('contragentClassId' => $ContragentClass::getClassId(), 'contragentId' => $cRec->id, 'title' => $ContragentClass::getTitleById($cRec->id));
                    }
                    
                    if(count($contragents) > 20) break;
                }
            }
            
            $cnt = 0;
            foreach ($contragents as $obj){
                $setContragentUrl = toUrl(array('pos_Receipts', 'setcontragent', 'id' => $rec->id, 'contragentClassId' => $obj->contragentClassId, 'contragentId' => $obj->contragentId, 'ret_url' => true));
                $divAttr = array("id" => "contragent{$cnt}", 'class' => 'posResultContragent posBtns navigable enlargable', 'data-url' => $setContragentUrl, 'data-enlarge-object-id' => $obj->contragentId, 'data-enlarge-class-id' => $obj->contragentClassId, 'data-enlarge-title' => strip_tags($obj->title));
                if(!$canSetContragent){
                    $divAttr['disabled'] = 'disabled';
                    $divAttr['disabledBtn'] = 'disabledBtn';
                    unset($divAttr['data-url']);
                }
                
                $holderDiv = ht::createElement('div', $divAttr, $obj->title, true);
                $tpl->append($holderDiv);
                $cnt++;
            }
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
            $holderDiv = ht::createElement('div', $transferDivAttr, 'Прехвърляне', true);
            $tpl->append($holderDiv);
            if(!$canSetContragent){
                $divAttr['disabled'] = 'disabled';
                $divAttr['class'] .= ' disabledBtn';
                unset($divAttr['data-url']);
            } else {
                $divAttr['class'] .= ' navigable';
            }
            
            $holderDiv = ht::createElement('div', $divAttr, 'Премахване', true);
            $tpl->append($holderDiv);
        }
       
        return $tpl;
    }
    
    
    /**
     * Рендиране на таблицата с бележките за сторниране
     *
     * @param stdClass $rec - записа на бележката
     * @param string $string - въведения стринг за търсене
     * @param stdClass|null $selectedRec - селектирания ред (ако има)
     *
     * @return core_ET
     */
    private function renderResultRevertReceipts($rec, $string, $selectedRec)
    {
        $Receipts = cls::get('pos_Receipts');
        $string = plg_Search::normalizeText($string);
        
        $query = $Receipts->getQuery();
        $query->XPR('toReturn', 'double', 'ROUND(#total - COALESCE(#returnedTotal, 0), 2)');
        $query->where("#revertId IS NULL AND (#state = 'waiting' || #state = 'closed') AND #toReturn > 0");
        $query->XPR('orderField', 'int', "(CASE WHEN #pointId = {$rec->pointId} THEN 1 ELSE 2 END)");
        $query->orderBy('#orderField=ASC,#pointId=ASC,#id=DESC');
        $query->limit(self::$maxSearchReceipts);
        
        if(!empty($string)){
            $foundArr = $Receipts->findReceiptByNumber($string, true);
            if (is_object($foundArr['rec'])) {
                $query->where(array("#id = {$foundArr['rec']->id}"));
            }
        }
        
        $cnt = 0;
        $tpl = new core_ET("");
        
        $linkUrl = (pos_Receipts::haveRightFor('revert', pos_Receipts::DEFAULT_REVERT_RECEIPT)) ? array('pos_Receipts', 'revert', pos_Receipts::DEFAULT_REVERT_RECEIPT, 'ret_url' => true) : array();
        $disClass = ($linkUrl) ? 'navigable' : 'disabledBtn';
        $warning = ($linkUrl) ? 'Наистина ли желаете да създадете нова сторнираща бележка|*?' : null;
        $addBtn = ht::createLink("+", $linkUrl, $warning, "class=pos-notes posBtns newNoteBtn {$disClass}, title=Създаване на нова сторно бележка");
        $tpl->append($addBtn);
        
        while($receiptRec = $query->fetch()){
            $linkUrl = (pos_Receipts::haveRightFor('revert', $receiptRec->id)) ? array('pos_Receipts', 'revert', $receiptRec->id, 'ret_url' => true) : array();
            $class = ($rec->pointId != $receiptRec->pointId) ? 'differentPosBtn' : '';
            $btnTitle = ($rec->pointId != $receiptRec->pointId) ? ht::createHint(self::getReceiptTitle($receiptRec), "Бележката е от друг POS") : self::getReceiptTitle($receiptRec);
            $class .= ($linkUrl) ? ' navigable' : ' disabledBtn';
            $warning = ($linkUrl) ? 'Наистина ли желаете да сторнирате бележката|*?' : false;
            
            $btn = ht::createLink($btnTitle, $linkUrl, $warning, "title=Сторниране на бележката,class=posBtns pos-notes {$class} state-{$receiptRec->state},id=revert{$cnt}");
            $tpl->append($btn);
            $cnt++;
        }
        
        $tpl = ht::createElement('div', array('class' => 'displayFlex'), $tpl, true);
        
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
        $tpl = new core_ET(tr("|*<div class='paymentBtnsHolder'>[#PAYMENTS#]</div><div class='divider'>|Приключване|*</div>[#CLOSE_BTNS#]"));
        
        $payUrl = (pos_Receipts::haveRightFor('pay', $rec)) ? toUrl(array('pos_ReceiptDetails', 'makePayment', 'receiptId' => $rec->id), 'local') : null;
        $disClass = ($payUrl) ? 'navigable' : 'disabledBtn';
        
        $paymentArr = array();
        $paymentArr["payment-1"] = (object)array('body' => ht::createElement("div", array('id' => "payment-1", 'class' => "{$disClass} posBtns payment", 'data-type' => '-1', 'data-url' => $payUrl), tr('В брой'), true), 'placeholder' => 'PAYMENTS');
        
        $payments = pos_Points::fetchSelected($rec->pointId);
        foreach ($payments as $paymentId => $paymentTitle){
            $paymentArr["payment{$paymentId}"] = (object)array('body' => ht::createElement("div", array('id' => "payment{$paymentId}", 'class' => "{$disClass} posBtns payment", 'data-type' => $paymentId, 'data-url' => $payUrl), tr($paymentTitle), true), 'placeholder' => 'PAYMENTS');
        }
        
        $contoUrl = (pos_Receipts::haveRightFor('close', $rec)) ? array('pos_Receipts', 'close', $rec->id, 'ret_url' => true) : null;
        $disClass = ($contoUrl) ? '' : 'disabledBtn';
        $warning =  ($contoUrl) ? 'Наистина ли желаете да приключите продажбата|*?' : false;
        $closeBtn = ht::createLink('Приключено', $contoUrl, $warning, array('class' => "{$disClass} posBtns payment closeBtn"));
        $paymentArr["close"] = (object)array('body' => $closeBtn, 'placeholder' => 'CLOSE_BTNS');
        
        // Добавяне на бутон за приключване на бележката
        cls::get('pos_Receipts')->invoke('BeforeGetPaymentTabBtns', array(&$paymentArr, $rec));
        
        foreach ($paymentArr as $btnObject){
            $tpl->append($btnObject->body, $btnObject->placeholder);
        }
        
        $tpl->append("<div class='clearfix21'></div>");
        $tpl = ht::createElement('div', array('class' => 'displayFlex'), $tpl, true);
        
        return $tpl;
    }
    
    
    /**
     * Рендиране на таблицата с наличните опаковки
     *
     * @param stdClass $rec - записа на бележката
     * @param string $string - въведения стринг за търсене
     * @param stdClass|null $selectedRec - селектирания ред (ако има)
     *
     * @return core_ET
     */
    private function renderResultQuantity($rec, $string, $selectedRec)
    {
        $measureId = cat_Products::fetchField($selectedRec->productId, 'measureId');
        $packs = cat_Products::getPacks($selectedRec->productId);
        $basePackagingId = key($packs);
        $count = 0;
        
        $baseClass = "resultPack navigable posBtns";
        $basePackName = cat_UoM::getVerbal($measureId, 'name');
        $dataUrl = (pos_ReceiptDetails::haveRightFor('edit', $selectedRec)) ? toUrl(array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setquantity'), 'local') : null;
        $dataChangeStoreUrl = (pos_ReceiptDetails::haveRightFor('edit', $selectedRec)) ? toUrl(array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setstore'), 'local') : null;
        
        $buttons = $storeBtns = $frequentPackButtons = array();
        $buttons[$measureId] = ht::createElement("div", array('id' => "packaging{$count}", 'class' => $baseClass, 'data-pack' => $basePackName, 'data-url' => $dataUrl), tr($basePackName), true);
       
        // Добавяне на бутони за продуктовите опаковки
        $packQuery = cat_products_Packagings::getQuery();
        $packQuery->where("#productId = {$selectedRec->productId}");
        while ($packRec = $packQuery->fetch()) {
            $count++;
            $packagingId = cat_UoM::getVerbal($packRec->packagingId, 'name');
            $baseMeasureId = $measureId;
            $packRec->quantity = cat_Uom::round($baseMeasureId, $packRec->quantity);
            $packaging = "|{$packagingId}|*</br> <small>" . core_Type::getByName('double(smartRound)')->toVerbal($packRec->quantity) . " " . tr(cat_UoM::getSmartName($baseMeasureId, $packRec->quantity)) . "</small>";
            $buttons[] = ht::createElement("div", array('id' => "packaging{$count}", 'class' => $baseClass, 'data-pack' => $packagingId, 'data-url' => $dataUrl), tr($packaging), true);
        }
        
        // Основната мярка/опаковка винаги е на първа позиция
        $firstBtn = $buttons[$basePackagingId];
        unset($buttons[$basePackagingId]);
        $buttons = array($basePackagingId => $firstBtn) + $buttons;
        
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
            $frequentPackButtons[] = ht::createElement("div", array('id' => "packaging{$count}", 'class' => "{$baseClass} packWithQuantity", 'data-quantity' => $productRec->quantity, 'data-pack' => $packagingId, 'data-url' => $dataUrl), $btnCaption, true);
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
                $btnClass = ($storeId == $selectedRec->storeId) ? 'currentStore' : 'navigable';
                $dataUrl = ($storeId == $selectedRec->storeId) ? null : $dataChangeStoreUrl;
                
                $quantityInStockVerbal = core_Type::getByName('double(smartRound)')->toVerbal($quantity);
                $quantityInStockVerbal = ht::styleNumber($quantityInStockVerbal, $quantity);
                $storeName = store_Stores::getTitleById($storeId);
                $storeCaption = "<span><div class='storeNameInBtn'>{$storeName}</div> <div class='storeQuantityInStock'>({$quantityInStockVerbal} " . cat_UoM::getShortName($measureId). ")</div></span>";
                $storeBtns[] = ht::createElement("div", array('id' => "changeStore{$storeId}", 'class' => "{$btnClass} posBtns chooseStoreBtn", 'data-url' => $dataUrl, 'data-storeid' => $storeId), $storeCaption, true);
            }
        }
        
        $tpl = new core_ET(tr("|*<div class='divider'>|Промяна на мярка|*</div>[#PACK_BUTTONS#]<!--ET_BEGIN FREQUENT_PACK_BUTTONS--><div class='divider'>|Най-използвани|*</div>[#FREQUENT_PACK_BUTTONS#]<!--ET_END FREQUENT_PACK_BUTTONS--><!--ET_BEGIN STORE_BUTTONS--><div class='divider'>|Складове|*</div>[#STORE_BUTTONS#]<!--ET_END STORE_BUTTONS-->"));
        foreach ($buttons as $btn){
            $tpl->append($btn, 'PACK_BUTTONS');
        }
        
        foreach ($frequentPackButtons as $freqbtn){
            $tpl->append($freqbtn, 'FREQUENT_PACK_BUTTONS');
        }
        
        foreach ($storeBtns as $storeBtn){
            $tpl->append($storeBtn, 'STORE_BUTTONS');
        }
        
        $tpl = ht::createElement('div', array('class' => 'displayFlex'), $tpl, true);
        
        return $tpl;
    }
    
    
    /**
     * Рендиране на таблицата с последните цени
     *
     * @param stdClass $rec - записа на бележката
     * @param string $string - въведения стринг за търсене
     * @param stdClass|null $selectedRec - селектирания ред (ако има)
     *
     * @return core_ET
     */
    private function renderResultPrice($rec, $string, $selectedRec)
    {
        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
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
        while($dRec = $dQuery->fetch()){
            $dRec->price *= 1 + $dRec->param;
            Mode::push('text', 'plain');
            $price = core_Type::getByName('double(smartRound)')->toVerbal($dRec->price);
            Mode::pop('text', 'plain');
            $btnName = "|*{$price} {$baseCurrencyCode}</br> |" . tr($packName);
            $dataUrl = toUrl(array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setprice', 'string' => $price), 'local');
            
            $cnt++;
            $buttons[$dRec->price] = ht::createElement("div", array('id' => "price{$cnt}",'class' => 'resultPrice posBtns navigable', 'data-url' => $dataUrl), tr($btnName), true);
        }
        
        $tpl = new core_ET("");
        foreach ($buttons as $btn){
            $tpl->append($btn);
        }
        $tpl = ht::createElement('div', array('class' => 'displayFlex'), $tpl, true);
        
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
        $Receipt = cls::get('pos_Receipts');
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
        $detailsTpl = $Receipt->pos_ReceiptDetails->renderReceiptDetail($data->receiptDetails);
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
        
        if (!Mode::is('printing')) {
            $tpl->push('pos/js/scripts.js', 'JS');
            $tpl->push('pos/js/jquery.keynav.js', 'JS');
            $tpl->push('pos/js/shortcutkeys.js', 'JS');
            jquery_Jquery::run($tpl, 'posActions();');
            jquery_Jquery::run($tpl, 'afterload();');
            
            jqueryui_Ui::enable($tpl);
        }
        
        // Добавяне на стилове за темата на терминала на ПОС-а, ако е различна от стандартната
        $pointTheme = pos_Points::fetchField($rec->pointId, 'theme');
        if($pointTheme != 'default'){
            if(getFullPath("pos/tpl/themes/{$pointTheme}.css")){
                $tpl->push("pos/tpl/themes/{$pointTheme}.css", 'CSS');
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
        $id = Request::get('id', 'int');
        
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
    private function getResultProducts($rec, $string, $revertReceiptId = null)
    {
        $searchString = plg_Search::normalizeText($string);
        $data = new stdClass();
        $data->rec = $rec;
        $data->searchString = $searchString;
        $data->baseCurrency = acc_Periods::getBaseCurrencyCode();
        $data->revertReceiptId = $revertReceiptId;
        $this->prepareProductTable($data);
        
        $tpl = new core_ET(" ");
        $block = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('PRODUCTS_RESULT');
        
        // Ако има категории
        $count = 0;
        if(countR($data->categoriesArr)){
            foreach ($data->categoriesArr as $categoryRec){
                
                // Под всяка категория се рендират артикулите към нея
                $productsInCategory = array_filter($data->rows, function($a) use ($categoryRec){ return in_array($categoryRec->id, $a->favouriteCategories);});
                if(countR($productsInCategory)){
                    $cTpl = new core_ET("<div class='divider'>{$categoryRec->name}</div>");
                    foreach ($productsInCategory as $row){
                        $row->elementId = "product{$count}";
                        $bTpl = clone $block;
                        $bTpl->placeObject($row);
                        $bTpl->removeBlocksAndPlaces();
                        $cTpl->append($bTpl);
                        $count++;
                    }
                    $cTpl->removeBlocksAndPlaces();
                    $tpl->append($cTpl);
                }
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
        }
        
        if(!count($data->rows)){
            $tpl->prepend(tr('Не са намерени артикули|*!'));
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
        $conf = core_Packs::getConfig('pos');
        $data->showParams = $conf->POS_RESULT_PRODUCT_PARAMS;
        $data->categoriesArr = array();
        
        // Ако има сторнираща бележка
        if(isset($data->revertReceiptId) && $data->revertReceiptId != pos_Receipts::DEFAULT_REVERT_RECEIPT){
            
            // Наличните артикули, са тези от оригиналната
            $pdQuery = pos_ReceiptDetails::getQuery();
            $pdQuery->where("#receiptId =  '{$data->revertReceiptId}' AND #productId IS NOT NULL");
            $pdQuery->EXT('searchKeywords', 'cat_Products', 'externalName=searchKeywords,externalKey=productId');
            if(!empty($data->searchString)){
                plg_Search::applySearch($data->searchString, $pdQuery);
            }
            
            $sellable = array();
            while($pdRec = $pdQuery->fetch()){
                $pdRec->_isRevert = true;
                $pdRec->packId = $pdRec->value;
                $pdRec->stock = $pdRec->quantity;
                $pdRec->price = $pdRec->amount;
                $pdRec->vat = $pdRec->param;
                $sellable[$pdRec->productId] = $pdRec;
            }
        } else {
            
            // Ако не се търси подробно артикул, се показват тези от любими
            $favouriteProductsArr = array();
            if(empty($data->searchString)){
                $data->categoriesArr = pos_FavouritesCategories::prepareAll($data->rec->pointId);
                $categoriesIds = arr::extractValuesFromArray($data->categoriesArr, 'id');
                $favouriteQuery = pos_Favourites::getQuery();
                $favouriteQuery->likeKeylist('catId', $categoriesIds);
                $favouriteQuery->show('productId,catId');
                
                while($favRec = $favouriteQuery->fetch()){
                    $favouriteProductsArr[$favRec->productId] = keylist::toArray($favRec->catId);
                }
            }
           
            $folderId = cls::get($data->rec->contragentClass)->fetchField($data->rec->contragentObjectId, 'folderId');
            $pQuery = cat_Products::getQuery();
            $pQuery->where("#canSell = 'yes' AND #state = 'active'");
            $pQuery->where("#isPublic = 'yes' OR (#isPublic = 'no' AND #folderId = '{$folderId}')");
            plg_Search::applySearch($data->searchString, $pQuery);
           
            if(countR($favouriteProductsArr)){
                $pQuery->in("id", array_keys($favouriteProductsArr));
            }
            
            $pQuery->show('id,name,isPublic,nameEn,code');
            $pQuery->limit($this->maxSearchProducts);
            $sellable = $pQuery->fetchAll();
        }
        
        // Ако има стринг и по него отговаря артикул той ще е на първо място
        if(!empty($data->searchString)){
            $foundRec = cat_Products::getByCode($data->searchString);
            if(isset($foundRec->productId) && (!isset($data->revertReceiptId) || (isset($data->revertReceiptId) && pos_ReceiptDetails::fetchField("#receiptId = {$data->revertReceiptId} AND #productId = {$foundRec->productId}")))){
                $sellable = array("{$foundRec->productId}" => (object)array('packId' => isset($foundRec->packagingId) ? $foundRec->packagingId : null)) + $sellable;
            }
        }
       
        if (!count($sellable)) {
            
            return;
        }
        
        $Policy = cls::get('price_ListToCustomers');
        foreach ($sellable as $id => $obj) {
            $pRec = cat_Products::fetch($id, 'canStore,measureId');
            $inStock = null;
            
            if($obj->_isRevert === true){
                $vat = $obj->vat;
                $price = pos_Receipts::getDisplayPrice($obj->price, $obj->vat, null, $data->rec->pointId, 1);
                if ($pRec->canStore == 'yes') {
                    $inStock = $obj->stock;
                }
            } else {
                if(!isset($obj->packId)){
                    $packs = cat_Products::getPacks($id);
                    $packId = key($packs);
                } else {
                    $packId = $obj->packId;
                }
                
                $packRec = cat_products_Packagings::getPack($id, $packId);
                $perPack = (is_object($packRec)) ? $packRec->quantity : 1;
                
                $price = $Policy->getPriceInfo($data->rec->contragentClass, $data->rec->contragentObjectId, $id, $packId, 1, $data->rec->createdOn, 1, 'yes');
                $vat = cat_Products::getVat($id);
                
                // Ако няма цена също го пропускаме
                if (empty($price->price)) continue;
                $price = $price->price * $perPack;
                
                if ($pRec->canStore == 'yes') {
                    $inStock = pos_Stocks::getQuantity($id, $data->rec->pointId);
                    $inStock /= $perPack;
                }
            }
            
            $obj = (object) array('productId' => $id, 'measureId' => $pRec->measureId, 'price' => $price, 'packagingId' => $packId, 'vat' => $vat);
            $photo = cat_Products::getParams($id, 'preview');
            if (!empty($photo)) {
                $obj->photo = $photo;
            }
            
            if (isset($inStock)) {
                $obj->stock = $inStock;
            }
            
            // Обръщаме реда във вербален вид
            $data->rows[$id] = $this->getVerbalSearchresult($obj, $data);
            $data->rows[$id]->CLASS = ' pos-add-res-btn navigable enlargable';
            $data->rows[$id]->DATA_URL = (pos_ReceiptDetails::haveRightFor('add', $obj)) ? toUrl(array('pos_ReceiptDetails', 'addProduct', 'receiptId' => $data->rec->id), 'local') : null;
            $data->rows[$id]->DATA_ENLARGE_OBJECT_ID = $id;
            $data->rows[$id]->DATA_ENLARGE_CLASS_ID = cat_Products::getClassId();
            $data->rows[$id]->DATA_ENLARGE_TITLE = cat_Products::getTitleById($id);
            
            $data->rows[$id]->id = $pRec->id;
            if(array_key_exists($id, $favouriteProductsArr)){
                $data->rows[$id]->favouriteCategories = $favouriteProductsArr[$id];
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
        $row->stock = $Double->toVerbal($obj->stock);
        $packagingId = ($obj->packagingId) ? $obj->packagingId : $obj->measureId;
        $row->packagingId = cat_UoM::getSmartName($packagingId, $obj->stock);
        $obj->receiptId = $data->rec->id;
        
        $row->productId = cat_Products::getTitleById($obj->productId);
        if ($data->showParams) {
            $params = keylist::toArray($data->showParams);
            foreach ($params as $pId) {
                if ($vRec = cat_products_Params::fetch("#productId = {$obj->productId} AND #paramId = {$pId}")) {
                    $row->productId .= ' &nbsp;' . strip_tags(cat_products_Params::recToVerbal($vRec, 'paramValue')->paramValue);
                }
            }
        }
        
        $row->stock = ht::styleNumber($row->stock, $obj->stock, 'green');
        $row->stock = "{$row->stock} <span class='pos-search-row-packagingid'>{$row->packagingId}</span>";
       
        if (!Mode::is('screenMode', 'narrow')) {
            $thumb = (!empty($obj->photo)) ? new thumb_Img(array($obj->photo, 64, 64, 'fileman')) : new thumb_Img(getFullPath('pos/img/default-image.jpg'), 64, 64, 'path');
            $arr = array();
            $row->photo = $thumb->createImg($arr);
        }
        
        return $row;
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
        $rec = $this->fetchRec($rec );
        $tpl = new core_ET("");
        $today = dt::today();
        
        $string = plg_Search::normalizeText($string);
        $addUrl = (pos_Receipts::haveRightFor('add')) ? array('pos_Receipts', 'new', 'forced' => true) : array();
        $disabledClass = (pos_Receipts::haveRightFor('add')) ? 'navigable' : 'disabledBtn';
        
        // Намираме всички чернови бележки и ги добавяме като линк
        $query = pos_Receipts::getQuery();
        $query->XPR('createdDate', 'date', 'DATE(#createdOn)');
        $query->where("#state != 'rejected' AND #pointId = {$rec->pointId}");
        $query->orderBy("#createdDate,#id", 'DESC');
        $query->limit(self::$maxSearchReceipts);
        if(!empty($string)){
            plg_Search::applySearch($string, $query);
        }
        
        // Добавяне на бутона за нова бележка да е в блока 'Днес'
        $dateBlock = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('RECEIPT_RESULT');
        $arr = array("{$today}" => clone $dateBlock);
        $arr[$today]->replace(dt::mysql2verbal($today, 'smartDate'), 'groupName');
        $addBtn = ht::createLink("+", $addUrl, null, "id=receiptnew,class=pos-notes posBtns newNoteBtn {$disabledClass},title=Създаване на нова бележка");
        $arr[$today]->append($addBtn, 'element');
        
        // Групиране на записите по дата
        while ($receiptRec = $query->fetch()) {
            if(!array_key_exists($receiptRec->createdDate, $arr)){
                $arr[$receiptRec->createdDate] = clone $dateBlock;
                $arr[$receiptRec->createdDate]->replace(dt::mysql2verbal($receiptRec->createdDate, 'smartDate'), 'groupName');
            }
            
            $class = isset($receiptRec->revertId) ? 'revertReceipt' : '';
            $openUrl = (pos_Receipts::haveRightFor('terminal', $receiptRec->id)) ? array('pos_Terminal', 'open', 'receiptId' => $receiptRec->id, 'opened' => true) : array();
            $class .= (count($openUrl)) ? ' navigable' : ' disabledBtn';
            $class .= ($receiptRec->id == $rec->id) ? ' currentReceipt' : '';
            
            $row = ht::createLink(self::getReceiptTitle($receiptRec, false), $openUrl, null, array('id' => "receipt{$receiptRec->id}", 'class' => "pos-notes posBtns {$class} state-{$receiptRec->state} enlargable", 'title' => 'Отваряне на бележката', 'data-enlarge-object-id' => $receiptRec->id, 'data-enlarge-class-id' => pos_Receipts::getClassId(), 'data-enlarge-title' => strip_tags(pos_Receipts::getRecTitle($receiptRec))));
            $arr[$receiptRec->createdDate]->append($row, 'element');
        }
        
        foreach ($arr as $blockTpl){
            $blockTpl->removeBlocksAndPlaces();
            $tpl->append($blockTpl);
        }
        $tpl = ht::createElement('div', array('class' => 'displayFlex'), $tpl, true);
        
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
    public static function returnAjaxResponse($receiptId, $selectedRecId, $success, $refreshTable = false, $refreshPanel = true)
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
                $toolsTpl = $me->getCommandPanel($rec, $operation, $string);
                
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
            }
            
            // Ще се реплейсват резултатите
            $resultTpl = $me->renderResult($rec, $operation, $string, $selectedRecId);
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => 'result-holder', 'html' => $resultTpl->getContent(), 'replace' => true);
            $res[] = $resObj;
            
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
