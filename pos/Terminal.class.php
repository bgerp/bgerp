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
    protected static $allowedOperationOnNonDraftReceipts = 'receipts=Бележки,revert=Сторно,payment=Плащане,contragent=Прехвърляне';
    
    
    /**
     * Бутони за бърз достъп до терминала
     */
    protected static $operationShortcuts = 'operation-add=Ctrl A,operation-payment=Ctrl Z,operation-quantity=Ctrl S,operation-text=Ctrl E,operation-contragent=Ctrl K,operation-receipts=Ctrl B,enlarge=F2,print=Ctrl P,keyboard=Ctrl M,exit=Ctrl Q,reject=Ctrl O,help=F1,delete=Ctrl O,revert=Ctrl O';

    
    /**
     * Кои са разрешените операции
     */
    protected static $operationsArr = "add=Избор на артикул,quantity=Редактиране на реда,payment=Плащане по бележката,contragent=Търсене на клиент,text=Задаване на текст на реда,receipts=Търсене на бележка";


    /**
     * Икони за операциите
     */
    protected static $operationImgs = array('enlarge' => 'pos/img/search.png', 'print' => 'pos/img/printer.png', 'keyboard' => 'pos/img/keyboard.png', 'operation-add' => 'pos/img/а.png', 'operation-text' =>  'pos/img/comment.png', 'operation-payment' => 'pos/img/dollar.png', 'operation-quantity' => 'pos/img/multiply.png',  'operation-add' => 'pos/img/a.png',  'operation-receipts' => 'pos/img/receipt.png', 'operation-contragent' => 'pos/img/user.png', 'close' => 'pos/img/close.png', 'transfer' => 'pos/img/transfer.png', 'reject' => 'pos/img/cancel.png', 'delete' => 'pos/img/delete.png', 'help' => "pos/img/info.png", 'revert' => "pos/img/redo.png");

    
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
        $forcePoint = Request::get('force', 'int');
        $rec = $Receipts->fetch($id);
        if(empty($rec)) return new Redirect(array($Receipts, 'new'), '|Несъществуваща бележка', 'warning');


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

        $cPoint = pos_Points::getCurrent('id', false);
        if($forcePoint || !$cPoint){
            pos_Points::selectCurrent($rec->pointId);
        }

        $tpl = getTplFromFile('pos/tpl/terminal/Layout.shtml');
        $titleDelimiter = Mode::is('printing') ? ' « ' : '';
        $tpl->replace(pos_Points::getTitleById($rec->pointId) . "{$titleDelimiter}", 'PAGE_TITLE');
        $tpl->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf('img/16/cash-register.png', '"', true) . '> ', 'HEAD');
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
        $manualConfirmBtn = ht::createFnBtn('Ръчно потвърждение', '', '', array('class' => 'modalBtn confirmPayment disabledBtn'));
        $manualCancelBtn = ht::createFnBtn('Назад', '', '', array('class' => 'closePaymentModal modalBtn disabledBtn'));

        // Вкарване на css и js файлове
        $this->pushTerminalFiles($tpl, $rec);
        $modalTpl =  new core_ET('<div class="fullScreenCardPayment" style="position: fixed; top: 0; z-index: 1002; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9);display: none;"><div style="position: absolute; top: 30%; width: 100%"><h3 style="color: #fff; font-size: 56px; text-align: center;">' . tr('Плащане с банковия терминал') .' ...<br> ' . tr('Моля, изчакайте') .'!</h3><div class="flexBtns">' . $manualConfirmBtn->getContent() . ' ' . $manualCancelBtn->getContent() . '</div></div></div>');
        $tpl->append($modalTpl);
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
        
        $headerData = (object)array('pointId' => pos_Points::getHyperlink($rec->pointId, false),
                                    'createdBy' => core_Users::getVerbal($rec->createdBy, 'nick'),
                                    'ID' => ht::createLink(pos_Receipts::getVerbal($rec->id, 'id'), pos_Receipts::getSingleUrlArray($rec->id)),
                                    'TIME' => $this->renderCurrentTime(),
                                    'valior' => pos_Receipts::getVerbal($rec->id, 'valior'),
                                    'userId' => core_Users::getVerbal(core_Users::getCurrent(), 'nick'));

        // Ако контрагента е лице и е потребител да се показва и аватара му
        $headerData->contragentId = (!empty($rec->transferredIn)) ? sales_Sales::getLink($rec->transferredIn, 0, array('ef_icon' => false)) : pos_Receipts::getMaskedContragent($rec->contragentClass, $rec->contragentObjectId, $rec->pointId, array('blank' => true, 'policyId' => $rec->policyId));
       
        $img = ht::createImg(array('path' => 'img/16/bgerp.png'));
        $logoTpl = new core_ET("[#img#] [#APP_NAME#]");
        $logoTpl->replace($img, 'img');
        $logoTpl->replace(core_Setup::get('EF_APP_TITLE', true), 'APP_NAME');
        $logoTpl->removeBlocksAndPlaces();
        $logoLink = ht::createLink($logoTpl, array('bgerp_Portal', 'show'));
        
        $tpl->append($logoLink, 'APP_NAME');
        $tpl->placeObject($headerData);

        if (isDebug()) {
            if (log_Debug::haveRightFor('list') && defined('DEBUG_FATAL_ERRORS_FILE')) {
                $fileName = pathinfo(DEBUG_FATAL_ERRORS_FILE, PATHINFO_FILENAME) . '_x';
                $fileName = log_Debug::getDebugLogFile('2x', $fileName, false, false);
                $debugLink = ht::createLink('Debug', array('log_Debug', 'Default', 'debugFile' => $fileName), false, array('title' => 'Показване на debug информация', 'target' => '_blank'));
                $tpl->append($debugLink, 'DEBUG_LINK');
            }
        }

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
        $receiptId = Request::get('id', 'int');

        if(empty($enlargeClassId) || empty($enlargeObjectId)) return array();
        
        $EnlargeClass = cls::get($enlargeClassId);
        $receiptRec = pos_Receipts::fetch($receiptId);
        
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
                $settings = pos_Points::getSettings($receiptRec->pointId);

                if(!empty($receiptRec->policyId)){
                    $policy1 = $receiptRec->policyId;
                    $policy2 = pos_Receipts::isForDefaultContragent($receiptRec) ? $settings->policyId : price_ListToCustomers::getListForCustomer($receiptRec->contragentClass, $receiptRec->contragentObjectId);
                } else {
                    $policy1 = $settings->policyId;
                    $policy2 = pos_Receipts::isForDefaultContragent($receiptRec) ? null : price_ListToCustomers::getListForCustomer($receiptRec->contragentClass, $receiptRec->contragentObjectId);
                }

                $price = pos_ReceiptDetails::getLowerPriceObj($policy1, $policy2, $productRec->id, $productRec->measureId, 1, dt::now());
                $calcedPrice = !empty($price->discount) ? $price->price * (1 - $price->discount) : $price->price;
                $calcedPrice *= 1 + cat_Products::getVat($productRec->id, null, $settings->vatExceptionId);
                $Double = core_Type::getByName('double(decimals=2)');
                
                $row = new stdClass();
                $row->price = currency_Currencies::decorate($Double->toVerbal($calcedPrice));
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
                        $stockRec = store_Products::fetch("#storeId = '{$storeId}' AND #productId = '{$productRec->id}'");

                        foreach (array('reservedQuantity', 'expectedQuantity', 'quantity') as $fld){
                            $verbalQuantity = core_Type::getByName('double(smartRound)')->toVerbal($stockRec->{$fld});
                            $storeRow->{$fld} = ht::styleIfNegative($verbalQuantity, $stockRec->{$fld});
                        }

                        $freeQuantity = $stockRec->quantity - $stockRec->reservedQuantity + $stockRec->expectedQuantity;
                        $freeVerbal = core_Type::getByName('double(smartRound)')->toVerbal($freeQuantity);
                        $freeVerbal = ht::styleIfNegative($freeVerbal, $freeQuantity);
                        $storeRow->freeQuantity = $freeVerbal;
                        $block->placeObject($storeRow);
                        $row->INSTOCK .= $block->getContent();
                    }
                }

                $settings = pos_Points::getSettings($receiptRec->pointId);
                $row->preview = $this->getPosProductPreview($productRec->id, 400, 400, $settings);
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
                $changeMetaUrl = (cat_Products::haveRightFor('edit', $productRec->id)) ? array('cat_Products', 'changemeta', 'Selected' => $productRec->id, 'toggle' => 'canSell', 'ret_url' => array('pos_Terminal', 'open', 'receiptId' => $receiptId)) : array();
                $warning = ($productRec->canSell == 'yes') ? 'Наистина ли желаете да спрете артикула от продажба|*?' : 'Наистина ли желаете да пуснете артикула в продажба|*?';
                $warning = countR($changeMetaUrl) ? $warning : false;

                $btn = ht::createBtn($btnTitle,  $changeMetaUrl, $warning, null, "class=actionBtn {$className},title={$btnTitle} на артикула от продажба");
                $modalTpl->append($btn, 'TOOLBAR');

                if($tempCloseTime = pos_Setup::get('TEMPORARILY_CLOSE_PRODUCT_TIME')){
                    $changeMetaUrl['ret_url'] = array('pos_Terminal', 'setMakeSellableProductOnTime', 'productId' => $productRec->id, 'receiptId' => $receiptId, 'hash' => md5("{$productRec->id}_{$receiptId}_SALT"));

                    if($productRec->canSell == 'yes'){
                        $tempTimeVerbal = core_Type::getByName('time')->toVerbal($tempCloseTime);
                        $btnTemp = ht::createBtn("Спиране за|* {$tempTimeVerbal}",  $changeMetaUrl, "Наистина ли желаете да спрете артикула временно от продажба|*?", null, "class=actionBtn offTmpBtn,title=Временно спиране на артикула от продажба");
                        $modalTpl->append($btnTemp, 'TOOLBAR');
                    }
                }
                Request::removeProtected('Selected');
                break;
            case pos_Receipts::getClassId():
                Mode::push('text', 'xhtml');
                $modalTpl =  $this->getReceipt($enlargeObjectId);
                Mode::pop('text');
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
        pos_Receipts::requireRightFor('terminal');
        
        $tpl = getTplFromFile('pos/tpl/terminal/Help.shtml');
        $rejectAction = Request::get('rejectAction', 'enum(revert,delete,reject)');
        $pointId = Request::get('pointId', 'int');

        for($i = 1; $i<=11; $i++) {
            $tpl->replace( ht::createElement('img', array('src' => sbf("pos/img/btn{$i}.png", ''))), "img{$i}");
        }

        $settings = pos_Points::getSettings($pointId);
        if($settings->setDiscounts != 'yes'){
            $tpl->append('notActiveHint', 'discountHintTrClass');
        }

        if($settings->setPrices != 'yes'){
            $tpl->append('notActiveHint', 'priceHintTrClass');
        }

        $rejectIconNumber = ($rejectAction == 'reject') ? '12' : (($rejectAction == 'revert') ? '13' : '14'); 
        $tpl->replace(ht::createElement('img', array('src' => sbf("pos/img/btn{$rejectIconNumber}.png", ''))), "img{$rejectIconNumber}");
        
        // Ще се реплейсва и пулта
        $res = array();
        $resObj = new stdClass();
        $resObj->func = 'html';
        $resObj->arg = array('id' => 'modalContent', 'html' => $tpl->getContent(), 'replace' => true);
        $res[] = $resObj;
        
        return $res;
    }
    
    
    /**
     * Създава нова визитка на контрагент и прехвърляне в нея
     */
    public function act_TransferInNewContragent()
    {
        pos_Receipts::requireRightFor('terminal');
        pos_Receipts::requireRightFor('transfer');
        $receiptId = core_Request::get('receiptId', 'int');
        $class = core_Request::get('class', 'enum(crm_Companies,crm_Persons)');
        $rec = pos_Receipts::fetch($receiptId);
        pos_Receipts::requireRightFor('terminal', $rec);
        pos_Receipts::requireRightFor('transfer', $rec);
        $Contragent = cls::get($class);
        $Contragent->requireRightFor('add');

        // Показване на формата за създаване на визитка
        $data = (object)array('action' => 'manage', 'cmd' => 'add');
        $Contragent->prepareEditForm($data);
        $data->form->setAction(array($this, 'TransferInNewContragent', 'receiptId' => $rec->id, 'class' => $class));
        $data->form->setField('inCharge', 'autohide=any');
        $data->form->setField('access', 'autohide=any');
        $data->form->setField('shared', 'autohide=any');
        $singleTitle = ($class == 'crm_Companies') ? 'нова фирма' : 'ново лице';
        $data->form->title = "Създаване на {$singleTitle}";
        
        // Събмитване на формата
        $data->form->input();
        $Contragent->invoke('AfterInputEditForm', array($data->form));
        if ($data->form->isSubmitted()) {

            // Запис на новата визитка
            $contragentRec = $data->form->rec;
            $Contragent->save($contragentRec);
            if($Contragent instanceof crm_Persons){
                $Contragent->flushUpdatePriceLists();
            }

            // Бележката се прехвърля автоматично на новосъздадения контрагент
            pos_Receipts::setContragent($rec, $Contragent->getClassId(), $contragentRec->id);
            Mode::setPermanent("currentSearchString{$rec->id}", null);

            redirect(array('pos_Terminal', 'open', 'receiptId' => $rec->id));
        }
        
        $data->form->toolbar->addSbBtn('Запис', 'save', 'id=save, ef_icon = img/16/disk.png', "title=Запис на {$singleTitle}");
        $data->form->toolbar->addBtn('Отказ', getRetUrl(), 'id=cancel, ef_icon = img/16/close-red.png', 'title=Прекратяване на действията');
        $content = $data->form->renderHtml();
        
        return $Contragent->renderWrapping($content);
    }
    
    
    /**
     * Пълна клавиатура
     *
     * @return array $res
     */
    public function act_Keyboard()
    {
        pos_Receipts::requireRightFor('terminal');
        
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
        $inputUrl = null;

        switch($operation){
            case 'add':
                $inputUrl = array('pos_ReceiptDetails', 'dispatch', 'receiptId' => $rec->id);
                if(isset($rec->revertId) && $rec->revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT){
                    $keyupUrl = null;
                }
                break;
            case 'quantity':
                $inputUrl = array('pos_ReceiptDetails', 'dispatch', 'receiptId' => $rec->id);
                break;
            case 'text':
                $inputUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'settext');
                $keyupUrl = null;
                break;
            case 'payment':
                $keyupUrl = null;
                break;
            case 'contragent':
                $inputUrl = array('pos_ReceiptDetails', 'dispatchContragentSearch', 'receiptId' => $rec->id, 'action' => 'settext');
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

        $operations = arr::make(self::$operationsArr);
        $allowedOperationsForNonDraftReceipts = arr::make(self::$allowedOperationOnNonDraftReceipts);

        $productCount = pos_ReceiptDetails::count("#receiptId = {$rec->id} AND #action LIKE '%sale%'");
        $paymentCount = pos_ReceiptDetails::count("#receiptId = {$rec->id} AND #action LIKE '%payment%'");

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
            $disabled = (empty($productCount) && in_array($operation, self::$forbiddenOperationOnEmptyReceipts)) || (!empty($paymentCount) && in_array($operation, self::$forbiddenOperationOnReceiptsWithPayment));

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
        $img = ht::createImg(array('path' => self::$operationImgs["revert"]));
        if (pos_Receipts::haveRightFor('reject', $rec)) {
             $img = ht::createImg(array('path' => self::$operationImgs["reject"]));
             $buttons["reject"] = (object)array('body' => $img, 'attr' => array('title' => 'Оттегляне на бележката', 'class' => "rejectBtn", 'data-action' => 'reject'), 'linkUrl' => array('pos_Receipts', 'reject', $rec->id, 'ret_url' => toUrl(array('pos_Receipts', 'new'), 'local')), 'linkWarning' => 'Наистина ли желаете да оттеглите бележката|*?');
        } elseif (pos_Receipts::haveRightFor('delete', $rec)) {
             $img = ht::createImg(array('path' => self::$operationImgs["delete"]));
             $buttons["delete"] = (object)array('body' => $img, 'attr' => array('title' => 'Изтриване на бележката', 'class' => "rejectBtn", 'data-action' => 'delete'), 'linkUrl' => array('pos_Receipts', 'delete', $rec->id, 'ret_url' => toUrl(array('pos_Receipts', 'new'), 'local')), 'linkWarning' => 'Наистина ли желаете да изтриете бележката|*?');
        } elseif(pos_Receipts::haveRightFor('revert', $rec->id)){
            $buttons["revert"] = (object)array('body' => $img, 'attr' => array('title' => 'Сторниране на бележката', 'class' => "rejectBtn revert", 'data-action' => 'revert'), 'linkUrl' => array('pos_Receipts', 'revert', $rec->id), 'linkWarning' => 'Наистина ли желаете да сторнирате бележката|*?');
        } elseif(pos_Receipts::haveRightFor('revert', pos_Receipts::DEFAULT_REVERT_RECEIPT)) {
            $buttons["revert"] = (object)array('body' => $img, 'attr' => array('title' => 'Нова сторнираща бележка', 'class' => "rejectBtn revert", 'data-action' => 'revert'), 'linkUrl' => array('pos_Receipts', 'revert', pos_Receipts::DEFAULT_REVERT_RECEIPT), 'linkWarning' => 'Наистина ли желаете да създадете нова сторнираща бележка|*?');
        } else {
            $buttons["delete"] = (object)array('body' => $img, 'attr' => array('class' => "rejectBtn disabledBtn", 'disabled' => 'disabled', 'data-action' => 'reject'));
        }
        
        // Бутон за увеличение на избрания артикул
        $img = ht::createImg(array('path' => self::$operationImgs["help"]));
        $buttons["help"] = (object)array('body' => $img, 'attr' => array('title' => 'Отваряне на прозорец с информация', 'data-url' => toUrl(array('pos_Terminal', 'Help',  'pointId' => $rec->pointId), 'local'), 'class' => "helpBtn", 'data-modal-title' => tr('Информация')));
        
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
        $reset = ht::createElement('span', array("class" => "close-icon"), "&#10006;", true);
        $holder = ht::createElement('div', array('class' => 'inputHolder'), $input . $reset, true);
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
        $rec = pos_Receipts::fetch($id);
        if(!$rec) return new Redirect(array(cls::get('pos_Receipts'), 'new'), '|Несъществуваща бележка', 'warning');

        expect($operation = Request::get('operation', "enum(" . self::$operationsArr . ")"));
        $refreshPanel = Request::get('refreshPanel', 'varchar');
        $keyupTriggered = Request::get('keyupTriggered', 'varchar');
        
        $selectedProductGroupId = Request::get('selectedProductGroupId', 'varchar');
        $selectedReceiptFilter = Request::get('selectedReceiptFilter', 'varchar');
        
        $refreshPanel = !(($refreshPanel == 'no'));
        pos_Receipts::requireRightFor('terminal', $rec);

        $selectedRec = null;
        if($selectedRecId = Request::get('recId', 'int')){
            $selectedRec = pos_ReceiptDetails::fetch($selectedRecId, '*', false);
        }
        
        if(!is_object($selectedRec)){
            $selectedRecId = pos_ReceiptDetails::getLastRec($id)->id;
        }
       
        $oldSearchString = Mode::get("currentSearchString{$id}");
        $string = Request::get('search', 'varchar');
        
        Mode::setPermanent("currentOperation{$id}", $operation);
        Mode::setPermanent("currentSearchString{$id}", $string);
        Mode::setPermanent("currentSelectedGroup{$id}", $selectedProductGroupId);
        Mode::setPermanent("currentSelectedReceiptFilter{$id}", $selectedReceiptFilter);
        
        $refreshResults = true;
        if($keyupTriggered == 'yes' && $oldSearchString == $string){
            $refreshResults = false;
        }
        
        return static::returnAjaxResponse($rec->id, $selectedRecId, true, false, $refreshPanel, $refreshResults, null, true, false);
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
     * @param $rec
     * @param $string
     * @param $selectedRec
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
            $reloadAttr['onclick'] = "confirmAndRedirect('{$warning}', '{$reloadUrl}')";
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

                    if(!isset($data->revertsReceipt)){
                        core_RowToolbar::createIfNotExists($row->_rowTools);
                        cat_Products::addButtonsToDocToolbar($data->recs[$id]->productId, $row->_rowTools, 'pos_ReceiptDetails', $id);
                        $row->PRODUCT_BTNS = $row->_rowTools->renderHtml(10);
                    }
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

            $batchesInStore = batch_Items::getBatchQuantitiesInStore($receiptRec->productId, $receiptRec->storeId, $rec->valior, null, array(), true, null, true);
            $cnt = 0;

            // Може да се задава без парида, само ако е позволено на артикула
            if(countR($batchesInStore)){
                $alwaysRequire = $Def->getField('alwaysRequire');
                if($alwaysRequire != 'yes'){
                    $btn = ht::createElement("div", array('id' => "batch{$cnt}",'class' => 'resultBatch posBtns navigable', 'title' => 'Добавяне на артикул без партида', 'data-url' => toUrl($dataUrl, 'local')), 'Без партида', true);
                    $batchTpl->append($btn);
                }
            }

            if(!empty($string)){
                $foundBatches = array_filter($batchesInStore, function($b) use ($string){
                    if(mb_strpos(mb_strtolower($b), mb_strtolower($string)) !== false){
                        return true;
                    }
                    return false;
                }, ARRAY_FILTER_USE_KEY);

                $batchesInStore = $foundBatches;
            }
            $countBatchesInStore = count($batchesInStore);

            foreach ($batchesInStore as $batch => $quantity){
                if(!empty($string) && mb_strpos(mb_strtolower($batch), mb_strtolower($string)) === false) continue;

                $class = 'resultBatch posBtns navigable';
                if($countBatchesInStore == 1){
                    $class .= ' filteredBatch';
                }
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
                
                $btn = ht::createElement("div", array('id' => "batch{$cnt}",'class' => $class, 'title' => 'Задаване на партидата', 'data-url' => toUrl($dataUrl, 'local')), $batchVerbal, true);
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
        $tpl->prepend(tr("|*<div class='contentHolderResults'><div class='divider'>|Най-използвани текстове|*</div>"));
        $tpl->append("</div>");
        
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
            $discountPercent = str_replace('.', '', $discountPercent);
            $element = ht::createElement("div", array('id' => "discount{$discountPercent}", 'class' => "navigable posBtns discountBtn {$class}", 'data-url' => $url, 'title' => 'Задаване на избраната отстъпка'), $btnCaption, true);
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
        $personClassId = crm_Persons::getClassId();
        $companyClassId = crm_Companies::getClassId();
        $showUniqueNumberLike = false;

        $tpl = new core_ET("");
        if($rec->contragentObjectId == $defaultContragentId && $rec->contragentClass == $defaultContragentClassId){
            
            $contragents = array();
            $vatId = null;
            if(!empty($string)){
                list($status) = cls::get('drdata_Vats')->checkStatus($string);
                if($status == 'valid'){
                    $vatId = $string;
                }
            }

            // Добавяне на бутони за нови създаване на нови контрагенти
            $holderTpl = new core_ET("");
            foreach (array('crm_Companies', 'crm_Persons') as $contragentClassName){
                $newCompanyAttr = array('id' => "{$contragentClassName}New", 'data-url' => toUrl(array('pos_Terminal', 'TransferInNewContragent', 'receiptId' => $rec->id, 'vatId' => $vatId, 'class' => $contragentClassName, 'ret_url' => true)), 'class' => 'posBtns', 'title' => 'Създаване на нова визитка');
                if(!$contragentClassName::haveRightFor('add') || !pos_Receipts::haveRightFor('transfer', $rec)){
                    $newCompanyAttr['disabled'] = 'disabled';
                    $newCompanyAttr['class'] .= ' disabledBtn';
                    unset($newCompanyAttr['data-url']);
                } else {
                    $newCompanyAttr['class'] .= ' navigable newContragentBtn';
                }

                $btnName = $contragentClassName == 'crm_Companies' ? 'Нова фирма' : 'Ново лице';
                $holderTpl->append(ht::createElement('div', $newCompanyAttr, $btnName, true));
            }

            $holderTpl = ht::createElement('div', array('class' => 'grid'), $holderTpl, true);
            $tpl->append($holderTpl);
            $tpl->append(tr("|*<div class='divider'>|Търсене на клиенти|*</div>"));
            
            $count = 0;
            $stringInput = core_Type::getByName('varchar')->fromVerbal($string);
            
            // Ако има подаден стринг за търсене
            $ownCompany = crm_Companies::fetchOurCompany();
            $Varchar = core_Type::getByName('varchar');
            $searchString = plg_Search::normalizeText($stringInput);

            if(!empty($stringInput)){
                $showUniqueNumberLike = type_Int::isInt($searchString) || preg_match('/^[a-zA-Z]{2}\d/', $searchString);
                $maxContragents = pos_Points::getSettings($rec->pointId, 'maxSearchContragent');

                // Ако има клиентска карта с посочения номер, намира се контрагента ѝ
                $cardInfo = crm_ext_Cards::getInfo($stringInput);
                if($cardInfo['status'] == crm_ext_Cards::STATUS_ACTIVE){
                    $cData  = cls::get($cardInfo['contragentClassId'])->getContragentData($cardInfo['contragentId']);
                    $tel = !empty($cData->pTel) ? $cData->pTel : $cData->tel;
                    $email = !empty($cData->pEmail) ? $cData->pEmail : $cData->email;
                    $contragents["{$cardInfo['contragentClassId']}|{$cardInfo['contragentId']}"] = (object)array('contragentClassId' => $cardInfo['contragentClassId'], 'contragentId' => $cardInfo['contragentId'], 'title' => cls::get($cardInfo['contragentClassId'])->fetchField($cardInfo['contragentId'], 'name'), 'tel' => $tel, 'email' => $email);
                    $count++;
                }

                // Ако има фирма с такъв данъчен или национален номер
                $cQuery = crm_Companies::getQuery();
                $cQuery->fetch(array("#vatId = '[#1#]' OR #uicId = '[#1#]' AND #id != {$ownCompany->id}", $stringInput));
                $cQuery->show('id,folderId,vatId,uicId');
                while($cRec = $cQuery->fetch()){
                    $contragents["{$companyClassId}|{$cRec->id}"] = (object)array('contragentClassId' => crm_Companies::getClassId(), 'contragentId' => $cRec->id, 'title' => crm_Companies::getTitleById($cRec->id), 'vatId' => $Varchar->toVerbal($cRec->vatId), 'uicId' => $Varchar->toVerbal($cRec->uicId));
                    $count++;
                }
                
                // Ако има лице с такова егн или данъчен номер
                $pQuery = crm_Persons::getQuery();
                $pQuery->fetch(array("#egn = '[#1#]' OR #vatId = '[#1#]'", $stringInput));
                $pQuery->show('id,folderId,egn,vatId,name,email,tel');
                while($pRec = $pQuery->fetch()){
                    $contragents["{$personClassId}|{$pRec->id}"] = (object)array('contragentClassId' => crm_Persons::getClassId(), 'contragentId' => $pRec->id, 'title' => $cRec->name, 'egn' => $Varchar->toVerbal($cRec->egn), 'uicId' => $Varchar->toVerbal($cRec->uicId), 'email' => $cRec->email, 'tel' => $cRec->tel);
                    $count++;
                }

                if($showUniqueNumberLike){
                    
                    // Ако има фирма чийто данъчен или национален номер започва с числото
                    $cQuery = crm_Companies::getQuery();
                    $cQuery->where(array("#vatId LIKE '[#1#]%' OR #uicId LIKE '[#1#]%'", $searchString));
                    $cQuery->show('id,folderId,vatId,uicId,name');
                    while($cRec = $cQuery->fetch()){
                        $contragents["{$companyClassId}|{$cRec->id}"] = (object)array('contragentClassId' => crm_Companies::getClassId(), 'contragentId' => $cRec->id, 'title' => $cRec->name, 'vatId' => $Varchar->toVerbal($cRec->vatId), 'uicId' => $Varchar->toVerbal($cRec->uicId));
                        $count++;
                    }

                    // Ако има лице чието егн или национален номер започва с числото
                    $pQuery = crm_Persons::getQuery();
                    $pQuery->where(array("#vatId LIKE '[#1#]%' OR #egn LIKE '[#1#]%'", $searchString));
                    $pQuery->show('id,folderId,egn,vatId,name,email,tel');
                    while($pRec = $pQuery->fetch()){
                        $contragents["{$personClassId}|{$pRec->id}"] = (object)array('contragentClassId' => crm_Persons::getClassId(), 'contragentId' => $pRec->id, 'title' => $cRec->name, 'egn' => $Varchar->toVerbal($cRec->egn), 'uicId' => $Varchar->toVerbal($cRec->uicId), 'email' => $pRec->email, 'tel' => $pRec->tel);
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
                    $cQuery->show('id,folderId,name,egn,vatId,name,email,tel');
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
                            $contragents["{$classId}|{$cRec->id}"] = (object)array('contragentClassId' => $ContragentClass::getClassId(), 'contragentId' => $cRec->id, 'title' => $cRec->name, 'vatId' => $Varchar->toVerbal($cRec->vatId), "{$uicField}" => $Varchar->toVerbal($cRec->{$uicField}), 'email' => $cRec->email, 'tel' => $cRec->tel);
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
                        $cQuery->show('id,folderId,name,egn,vatId,email,tel');
                        $uicField = 'egn';
                    }
                    
                    // Обикалят се всички фирми/лице които съдържат търсения стринг в името си
                    $classId = ($ContragentClass == 'crm_Companies') ? $companyClassId : $personClassId;
                    
                    plg_Search::applySearch($stringInput, $cQuery);
                    while($cRec = $cQuery->fetch()){
                        if(!array_key_exists("{$classId}|{$cRec->id}", $contragents)){
                            $contragents["{$classId}|{$cRec->id}"] = (object)array('contragentClassId' => $ContragentClass::getClassId(), 'contragentId' => $cRec->id, 'title' => $cRec->name, 'vatId' => $Varchar->toVerbal($cRec->vatId), "{$uicField}" => $Varchar->toVerbal($cRec->{$uicField}), 'email' => $cRec->email, 'tel' => $cRec->tel);
                            $count++;
                        }

                        if($count > $maxContragents) break;
                    }
                }
            }
        } else {
            $contragentName = cls::get($rec->contragentClass)->getTitleById($rec->contragentObjectId);
            $tpl = new core_ET("<div class='divider'>{$contragentName}</div><div class='grid'>");
            
            // Добавя бутон за прехвърляне към папката на контрагента
            $setDefaultContragentUrl = toUrl(array('pos_Receipts', 'setcontragent', 'id' => $rec->id, 'contragentClassId' => $defaultContragentClassId, 'contragentId' => $defaultContragentId), 'local');
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

            $transferDivAttr['class'] .= " imgDiv contragentRedirectBtn";
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
            $removeBtnBody = new core_ET(tr("|*[#IMG#]|Отмяна|*"));
            $removeBtnBody->replace($removeImg, 'IMG');
            $divAttr['class'] .= " imgDiv";
            $holderDiv = ht::createElement('div', $divAttr, $removeBtnBody, true);
            $tpl->append($holderDiv);
            $tpl->append("</div>");
            
            $locationArr = crm_Locations::getContragentOptions($rec->contragentClass, $rec->contragentObjectId);
            if(countR($locationArr)){
                $tpl->append(tr("|*<div class='divider'>|Локации|*</div><div class='grid'>"));
                foreach ($locationArr as $locationId => $locationName){
                    $locationAttr = array("id" => "location{$locationId}", 'class' => 'posBtns locationBtn enlargable', 'data-enlarge-object-id' => $locationId, 'data-enlarge-class-id' => crm_Locations::getClassId(), 'data-modal-title' => strip_tags($locationName), 'title' => 'Избор на локация');
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
                    
                    $locationName .= "<div style=font-size:0.8em>" . str::limitLen(crm_Locations::getAddress($locationId), 68) . "</div>";
                    
                    $holderDiv = ht::createElement('div', $locationAttr, $locationName, true);
                    $tpl->append($holderDiv);
                }
                $tpl->append("</div>");
            }

            // Ако бележката е на лице и то има споделени фирмени папки, да се показват като бутони за добавяне
            $contragents = array();
            if(core_Packs::isInstalled('colab')){
                if($rec->contragentClass == $personClassId){
                    if($userId = crm_Profiles::getUserByPerson($rec->contragentObjectId)){
                        $sharedFolders = colab_Folders::getSharedFolders($userId, true, 'crm_CompanyAccRegIntf');
                        foreach($sharedFolders as $companyFolderId => $companyName){
                            $companyCover = doc_Folders::getCover($companyFolderId);
                            $companyRec = $companyCover->fetch();
                            $contragents["{$companyClassId}|{$companyCover->that}"] = (object)array('contragentClassId' => $companyClassId, 'contragentId' => $companyCover->that, 'title' => $companyName, 'vatId' => core_Type::getByName('varchar')->toVerbal($companyRec->vatId), "uicId" => core_Type::getByName('varchar')->toVerbal($companyRec->uicId));
                        }
                    }

                    if(countR($contragents)){
                        $tpl->append(tr("|*<div class='divider'>|Споделени фирми|*</div>"));
                    }
                } else {
                    $companyFolderId = cls::get($rec->contragentClass)->fetchField($rec->contragentObjectId, 'folderId');
                    $partners = colab_FolderToPartners::getContractorsInFolder($companyFolderId);
                    foreach($partners as $partnerId){
                        $partnerPersonId = crm_Profiles::getPersonByUser($partnerId);
                        $partnerPersonRec = crm_Persons::fetch($partnerPersonId);
                        $contragents["{$personClassId}|{$partnerPersonId}"] = (object)array('contragentClassId' => $personClassId, 'contragentId' => $partnerPersonId, 'title' => crm_Persons::getTitleById($partnerPersonId), 'vatId' => core_Type::getByName('varchar')->toVerbal($partnerPersonRec->vatId), "egn" => core_Type::getByName('varchar')->toVerbal($partnerPersonRec->egn));
                    }

                    if(countR($contragents)){
                        $tpl->append(tr("|*<div class='divider'>|Представители|*</div>"));
                    }
                }
            }
        }

        $cnt = 0;
        $temp =  new core_ET("");
        foreach ($contragents as $obj){

            $Class = cls::get($obj->contragentClassId);
            $setContragentUrl = toUrl(array('pos_Receipts', 'setcontragent', 'id' => $rec->id, 'contragentClassId' => $obj->contragentClassId, 'contragentId' => $obj->contragentId), 'local');
            $divAttr = array("id" => "contragent{$cnt}", 'class' => 'posResultContragent posBtns navigable enlargable', 'title' => "Избиране на клиента в бележката", 'data-url' => $setContragentUrl, 'data-enlarge-object-id' => $obj->contragentId, 'data-enlarge-class-id' => $obj->contragentClassId, 'data-modal-title' => strip_tags($obj->title));
            if(!$canSetContragent){
                $divAttr['disabled'] = 'disabled';
                $divAttr['disabledBtn'] = 'disabledBtn';
                unset($divAttr['data-url']);
            }

            $shortName = $Class->getVerbal($obj->contragentId, 'name');

            $obj->title = ht::createHint(str::limitLen($shortName, 28), "{$obj->title} [{$obj->contragentId}]");
            $subArr = array();
            if($Class instanceof crm_Persons){
                if(!empty($obj->email)){
                    $subArr[] = tr("Имейл") . ": " . str::maskEmail($obj->email);
                }
                if(!empty($obj->tel)){
                    $subArr[] = tr("Тел.") . ": " . str::maskString($obj->tel, 0, 3);
                }
            }

            if($showUniqueNumberLike){
                if(!empty($obj->vatId)){
                    $subArr[] = tr("ДДС №") . ": {$obj->vatId}";
                }
                if($obj->contragentId != $personClassId){
                    if(!empty($obj->uicId)){
                        $subArr[] = tr("Нац. №") . ": {$obj->uicId}";
                    }
                }
            }

            if(countR($subArr)){
                $stringInputSearch = strtoupper($stringInput);
                array_walk($subArr, function(&$a) use ($stringInputSearch) {$a = str_replace($stringInputSearch, "<span style='color:blue'>{$stringInputSearch}</span>", $a);});

                $subTitle = implode('; ', $subArr);
                $subTitle = "<div style='font-size:0.7em'>{$subTitle}</div>";
                $obj->title .= $subTitle;
            }

            $holderDiv = ht::createElement('div', $divAttr, $obj->title, true);
            $temp->append($holderDiv);
            $cnt++;
        }
        $tpl->append(ht::createElement('div', array('class' => 'grid'), $temp, true));



        $tpl->prepend("<div class='contentHolderResults'>");
        $tpl->append("</div>");
        
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
        // Ако попирнцип бележката не може да се приключи - да не може да се и прехвърля
        core_Debug::startTimer('RES_RENDER_PAYMENTS');
        $tpl = new core_ET(tr("|*<div class='contentHolderResults'><!--ET_BEGIN PAYMENT_ERROR--><div class='paymentErrorInfo'>[#PAYMENT_ERROR#]</div><!--ET_END PAYMENT_ERROR--><div class='grid'>[#PAYMENTS#]</div><div class='divider'>|Приключване|*</div><div class='grid'>[#CLOSE_BTNS#]</div></div>"));

        $rec->_disableAllPayments = false;
        $payUrl = (pos_Receipts::haveRightFor('pay', $rec)) ? toUrl(array('pos_ReceiptDetails', 'makePayment', 'receiptId' => $rec->id), 'local') : null;
        if(core_Packs::isInstalled('voucher')) {
            if(!isset($rec->revertId)){
                $productArr = arr::extractValuesFromArray(pos_Receipts::getProducts($rec->id), 'productId');
                $errorStartStr = 'Не може да платите, докато има артикули изискващи препоръчител и няма такъв';
                if ($error = voucher_Cards::getErrorForVoucherAndProducts($rec->voucherId, $productArr, $errorStartStr)) {
                    $tpl->append(tr($error), 'PAYMENT_ERROR');
                    $rec->_disableAllPayments = true;
                }
            }
        }

        $disClass = ($payUrl && !$rec->_disableAllPayments) ? 'navigable' : 'disabledBtn';
        $paymentArr = array();
        $paymentArr["payment-1"] = (object)array('body' => ht::createElement("div", array('id' => "payment-1", 'class' => "{$disClass} posBtns payment", 'data-type' => '-1', 'data-url' => $payUrl), tr('В брой'), true), 'placeholder' => 'PAYMENTS');
        $payments = pos_Points::fetchSelected($rec->pointId);

        if(!isset($rec->revertId)){
            $cardPaymentId = cond_Setup::get('CARD_PAYMENT_METHOD_ID');

            foreach ($payments as $paymentId => $paymentTitle){
                $attr = array('id' => "payment{$paymentId}", 'class' => "{$disClass} posBtns payment", 'data-type' => $paymentId, 'data-url' => $payUrl, 'title' => 'Избор на вид плащане');
                $currencyCode = cond_Payments::fetchField($paymentId, 'currencyCode');
                if(!empty($currencyCode)){
                    $attr['class'] .= ' currencyBtn disabledBtn';
                }

                $attr['data-sendamount'] = null;

                // Ако е плащане с карта и има периферия подменя се с връзка с касовия апарат
                if($paymentId == $cardPaymentId){
                    $deviceRec = peripheral_Devices::getDevice('bank_interface_POS');

                    if(is_object($deviceRec)){
                        $attr['id'] = 'card-payment';
                        $attr['data-onerror'] = tr('Неуспешно плащане с банковия терминал|*!');
                        $attr['data-oncancel'] = tr('Отказвано плащане с банков терминал|*!');
                        $diff = abs($rec->paid - $rec->total);
                        $attr['data-maxamount'] = $diff;
                        $attr['data-amountoverallowed'] = tr('Не може да платите повече отколкото се дължи по сметката|*!');
                        $attr['data-notnumericmsg'] = tr('Невалидна сума за плащане|*!');
                        $attr['data-sendamount'] = 'yes';
                    }
                }

                $paymentArr["payment{$paymentId}"] = (object)array('body' => ht::createElement("div", $attr, tr($paymentTitle), true), 'placeholder' => 'PAYMENTS');
            }
        }
        
        $contoUrl = (pos_Receipts::haveRightFor('close', $rec)) ? array('pos_Receipts', 'close', $rec->id, 'ret_url' => true) : null;
        $disClass = ($contoUrl) ? 'navigable' : 'disabledBtn';
        $warning =  ($contoUrl) ? 'Наистина ли желаете да приключите продажбата|*?' : false;
        $closeBtn = ht::createLink('Приключено', $contoUrl, $warning, array('class' => "{$disClass} posBtns closeBtn"));
        $paymentArr["close"] = (object)array('body' => $closeBtn, 'placeholder' => 'CLOSE_BTNS');
        
        // Добавяне на бутон за приключване на бележката
        cls::get('pos_Receipts')->invoke('BeforeGetPaymentTabBtns', array(&$paymentArr, $rec));

        $deleteBtn = $this->renderDeleteRowBtn($rec, $selectedRec);
        $paymentArr['delete'] = (object)array('body' => $deleteBtn, 'placeholder' => 'PAYMENTS');

        foreach ($paymentArr as $btnObject){
            $tpl->append($btnObject->body, $btnObject->placeholder);
        }
        
        $tpl->append("<div class='clearfix21'></div>");
        core_Debug::stopTimer('RES_RENDER_PAYMENTS');

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
            $packName = cat_UoM::getVerbal($packagingId, 'name');
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
        
        $buttons["delete{$selectedRec->id}"] = $this->renderDeleteRowBtn($rec, $selectedRec);
        
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

            $btnCaption =  "{$quantity} " . tr(cat_UoM::getSmartName($detailRec->value, $detailRec->quantity));
            $packDataName = cat_UoM::getTitleById($detailRec->value);
            $frequentPackButtons[] = ht::createElement("div", array('id' => "packaging{$count}", 'class' => "{$baseClass} packWithQuantity", 'data-quantity' => $detailRec->quantity, 'data-pack' => $packDataName, 'data-url' => $dataUrl, 'title' => 'Задаване на количеството'), $btnCaption, true);
        }
        
        $productRec = cat_Products::fetch($selectedRec->productId, 'canStore');
        
        if($productRec->canStore == 'yes'){
            $stores = pos_Points::getStores($rec->pointId);
            if(countR($stores) > 1 && empty($rec->revertId)){
                $storeArr = array();

                foreach ($stores as $storeId){
                    $stRec = store_Products::getQuantities($selectedRec->productId, $storeId);
                    $storeArr[$storeId] = $stRec->free;
                }
                
                arsort($storeArr);
                foreach ($storeArr as $storeId => $quantity){
                    $btnClass = ($storeId == $selectedRec->storeId) ? 'current navigable' : 'navigable';
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
     * @param stdClass $rec
     * @param stdClass $selectedRec
     * @return core_ET
     */
    private function renderDeleteRowBtn($rec, $selectedRec)
    {
        $deleteAttr = array('id' => "delete{$selectedRec->id}", 'class' => "posBtns deleteRow", 'title' => 'Изтриване на реда');
        if($selectedRec && !$rec->_disableAllPayments){
            if(strpos($selectedRec->action, 'payment') !== false){
                $deleteAttr['class'] .= (pos_ReceiptDetails::haveRightFor('delete', $selectedRec)) ? ' navigable' : ' disabledBtn';
            } else {
                $deleteAttr['class'] .= (empty($rec->paid) && pos_ReceiptDetails::haveRightFor('delete', $selectedRec)) ? ' navigable' : ' disabledBtn';
            }
        } else {
            $deleteAttr['class'] .= ' disabledBtn';
        }

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
        if(isset($selectedRec->value)){
            $dQuery->where("#value = {$selectedRec->value}"); 
            $value = $selectedRec->value;
        } else {
            $dQuery->where("#value IS NULL");
            $value = cat_Products::fetchField($selectedRec->productId, 'measureId');
        }

        $cnt = 0;
        $packName = cat_UoM::getVerbal($value, 'name');
        $dQuery->groupBy('price');
        $dQuery->orderBy('price', 'ASC');
        $dQuery->limit(5);
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
            $buttons[$dRec->price] = ht::createElement("div", array('id' => "price{$cnt}",'class' => 'resultPrice posBtns navigable', 'data-url' => $dataUrl, 'title' => 'Задаване на избраната цена'), tr($btnName), true);
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
            $deviceRec = peripheral_Devices::getDevice('bank_interface_POS');
            if(is_object($deviceRec)){
                $intf = cls::getInterface('bank_interface_POS', $deviceRec->driverClass);
                $tpl->append($intf->getJS($deviceRec), 'SCRIPTS');
            }

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
        $originState = $rec->state;
        core_Ajax::subscribe($tpl, array($this, 'autoRefreshHeader', $rec->id, 'originState' => $originState), 'refreshTime', 20000);
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
        $originState = Request::get('originState', 'enum(draft,waiting,rejected,closed)');
        $rec = pos_Receipts::fetch($id);

        // Ако има промяна в оригиналното състояние на бележката се прави нова
        if($originState != $rec->state){
            redirect(array('pos_Receipts', 'new', 'forced' => true));
        }
        
        $res = array();
        $min = date('i');
        if($min == '00'){
            if(!Mode::get("autoRefresh{$rec->id}")){
                $operation = Mode::get("currentOperation{$rec->id}");
                $string = Mode::get("currentSearchString{$rec->id}");
                if($operation == 'add'){
                    $resultTpl = $this->renderResult($rec, $operation, $string, null);
                    $resObj = new stdClass();
                    $resObj->func = 'html';
                    $resObj->arg = array('id' => 'result-holder', 'html' => $resultTpl->getContent(), 'replace' => true);
                    $res[] = $resObj;

                    $headerTpl = $this->renderHeader($rec);
                    $resObj6 = new stdClass();
                    $resObj6->func = 'html';
                    $resObj6->arg = array('id' => 'receiptTerminalHeader', 'html' => $headerTpl->getContent(), 'replace' => true);
                    $res[] = $resObj6;

                    $resObj7 = new stdClass();
                    $resObj7->func = 'afterload';
                    $res[] = $resObj7;

                    $resObj8 = new stdClass();
                    $resObj8->func = 'calculateWidth';
                    $res[] = $resObj8;
                    Mode::setPermanent("autoRefresh{$rec->id}", true);
                }
            }
        } else {
            if(Mode::get("autoRefresh{$rec->id}")){
                Mode::setPermanent("autoRefresh{$rec->id}", false);
            }
        }

        $resObj1 = new stdClass();
        $resObj1->func = 'clearStatuses';
        $resObj1->arg = array('type' => 'notice');
        $res[] = $resObj1;
        
        $resObj2 = new stdClass();
        $resObj2->func = 'clearStatuses';
        $resObj2->arg = array('type' => 'error');
        $res[] = $resObj2;

        $resObj3 = new stdClass();
        $resObj3->func = 'clearStatuses';
        $resObj3->arg = array('type' => 'warning');
        $res[] = $resObj3;

        return $res;
    }
    
    
    /**
     * Рендиране на таблицата с наличните отстъпки
     * 
     * @param stdClass $rec - записа на бележката
     * @param string $string - въведения стринг за търсене
     * @param stdClass|null $selectedRec - ид-то на бележката за сторниране
     * 
     * @return core_ET
     */
    private function renderResultProducts($rec, $string, $selectedRec)
    {
        $settings = pos_Points::getSettings($rec->pointId);
        $searchString = plg_Search::normalizeText($string);
        $rec->_selectedGroupId = Mode::get("currentSelectedGroup{$rec->id}");
        $rec->_selectedGroupId = (!empty($rec->_selectedGroupId)) ? $rec->_selectedGroupId : 'all';
        
        $productRows = $this->prepareProductTable($rec, $string, $selectedRec);
        $tpl = new core_ET(tr("|*<!--ET_BEGIN GROUP_TAB-->[#GROUP_TAB#]<!--ET_END GROUP_TAB-->
                                 <div class='contentHolderResults'>
                                    [#BLOCK#]
                                 </div>"));
        
        $groupsTable = type_Table::toArray($settings->productGroups);
        $groups = arr::extractValuesFromArray($groupsTable, 'groupId');
        $groupTabArr = array('all' => tr('Всички'));
        Mode::push('treeShortName', true);
        foreach ($groups as $groupId){
            $groupTabArr[$groupId] = cat_Groups::getVerbal($groupId, 'name');
        }
        Mode::pop('treeShortName');
        $groupTabArr['similar'] = tr('Свързани');
        if (Mode::get('screenMode') == 'narrow' && countR($groupsTable) > 3) {
            $resultTpl = new core_ET("<div class='tabs productTabs'><select style='width: 90%' class='tabHolder'>[#TAB#]</select></div>");
        } else {
            $resultTpl = new core_ET("<div class='tabs productTabs'><ul class='tabHolder'>[#TAB#]</ul></div>");
        }
       
        foreach ($groupTabArr as $groupId => $groupName){
            $active = ($rec->_selectedGroupId == $groupId) ? 'active' : '';
           
            $tabTitle = tr("Избор на група|*: \"{$groupName}\"");
            if (Mode::get('screenMode') == 'narrow' && countR($groupsTable) > 3) {
                $tab = "<option id='group{$groupId}' data-id = '{$groupId}'>{$groupName}</option>";
            } else {
                $tab = "<li id='group{$groupId}' data-id = '{$groupId}' class='selectable {$active}' title='{$tabTitle}'>{$groupName}</li>";
            }
            
            $resultTpl->append($tab, "TAB");
        }
        $tpl->append($resultTpl, 'GROUP_TAB');
        $blockTplPath = ($settings->productBtnTpl == 'wide') ? 'pos/tpl/terminal/ProductBtnWide.shtml' : (($settings->productBtnTpl == 'short') ? 'pos/tpl/terminal/ProductBtnShort.shtml' : (($settings->productBtnTpl == 'picture') ? 'pos/tpl/terminal/ProductBtnPicture.shtml' : 'pos/tpl/terminal/ProductBtnPictureAndText.shtml'));
        $block = getTplFromFile($blockTplPath);

        $countRows = countR($productRows);
        if($countRows){
            $pTpl = new core_ET("<div class='grid {$settings->productBtnTpl}'>[#RES#]</div>");
            foreach ($productRows as $row){
                $row->elementId = "{$rec->_selectedGroupId}{$row->id}";
                $bTpl = clone $block;
                $bTpl->placeObject($row);
                $bTpl->removeBlocksAndPlaces();
                $pTpl->append($bTpl, 'RES');
            }
            
            $pTpl->removeBlocksAndPlaces();
            $tpl->append($pTpl, 'BLOCK');
        } else {
            $tpl->append('<div class="noFoundInGroup">' . tr("Няма намерени артикули") . '</div>', 'BLOCK');
        }
        
        $tpl->prepend("<div class='withTabs'>");
        $tpl->append("</div>");
        
        return $tpl;
    }
    
    
    /**
     * Връща свързаните артикули с избрания
     * 
     * @return array
     */
    private function getSuggestedProductIds($rec, $selectedRec)
    {
        $suggestedArr = array();
        if(isset($selectedRec->productId)){
            $suggestedArr = sales_ProductRelations::fetchField("#productId = {$selectedRec->productId}", 'data');
            $suggestedArr = arr::make($suggestedArr);
            
            if($listId = cond_Parameters::getParameter($rec->contragentClass, $rec->contragentObjectId, 'salesList')){
                $productsInList = arr::extractValuesFromArray(cat_Listings::getAll($listId), 'productId');
                if(is_array($productsInList)){
                    $suggestedArr += $productsInList;
                }
            }
        }
        
        
        return $suggestedArr;
    }
    
    
    /**
     * Подготвя данните от резултатите за търсене
     */
    private function prepareProductTable($rec, $searchString, $selectedRec)
    {
        $priceCache = core_Permanent::get("priceListHash");
        $settings = pos_Points::getSettings($rec->pointId);
        $similarProducts = $this->getSuggestedProductIds($rec, $selectedRec);

        $count = 0;
        $sellable = array();
        $searchStringPure = plg_Search::normalizeText($searchString);
        core_Debug::startTimer('RES_RENDER_PREPARE_RECS');

        // Заявка към продаваемите артикули с цени
        $pQuery = pos_SellableProductsCache::getQuery();
        $pQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $pQuery->EXT('measureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
        $pQuery->EXT('name', 'cat_Products', 'externalName=name,externalKey=productId');
        $pQuery->EXT('canSell', 'cat_Products', 'externalName=canSell,externalKey=productId');
        $pQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $pQuery->EXT('nameEn', 'cat_Products', 'externalName=nameEn,externalKey=productId');
        $pQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        $pQuery->where("#priceListId = {$settings->policyId}");
        $pQuery->limit($settings->maxSearchProducts);

        // Ако не е посочен стринг се показват най-продаваните артикули
        if(empty($searchString)){
            $defaultOrder = true;
            if($rec->_selectedGroupId == 'similar'){
                if(countR($similarProducts)){
                    $pQuery->in('productId', $similarProducts);
                } else {
                    $pQuery->where("1=2");
                }
            } elseif(is_numeric($rec->_selectedGroupId)){
                plg_ExpandInput::applyExtendedInputSearch('cat_Products', $pQuery, $rec->_selectedGroupId, 'productId');
            } else {
                $groupsTable = type_Table::toArray($settings->productGroups);
                $groups = arr::extractValuesFromArray($groupsTable, 'groupId');
                if(countR($groups)){
                    $i = 1;
                    $orderByGroup = "(CASE ";
                    foreach ($groups as $groupId){
                        $orderByGroup .= " WHEN LOCATE('|$groupId|', #groups) THEN {$i}";
                        $i++;
                    }
                    $orderByGroup .= " ELSE {$i} END)";
                    $pQuery->XPR('orderByGroup', 'int', $orderByGroup);
                    $defaultOrder = false;
                    $pQuery->orderBy('orderByGroup=ASC,code=ASC');
                }
            }

            if($defaultOrder){
                $pQuery->orderBy('code', 'ASC');
            }

            // Добавят се към резултатите
            while ($pRec = $pQuery->fetch()){
                $sellable[$pRec->productId] = $pRec;
            }
        } else {
            // Ако има артикул, чийто код отговаря точно на стринга, той е най-отгоре
            $foundRec = cat_Products::getByCode($searchString);
            if(isset($foundRec->productId)){
                $cloneQuery = clone $pQuery;
                $cloneQuery->where("#productId = {$foundRec->productId}");

                if($rec->_selectedGroupId == 'similar'){
                    if(countR($cloneQuery)){
                        $pQuery->in('productId', $similarProducts);
                    } else {
                        $pQuery->where("1=2");
                    }
                } elseif(is_numeric($rec->_selectedGroupId)){
                    plg_ExpandInput::applyExtendedInputSearch('cat_Products', $cloneQuery, $rec->_selectedGroupId, 'productId');
                }

                if($productRec = $cloneQuery->fetch()){
                    $sellable[$foundRec->productId] = (object)array('id' => $foundRec->productId, 'canSell' => $productRec->canSell,'canStore' => $productRec->canStore, 'measureId' => $productRec->measureId, 'name' => $productRec->name, 'nameEn' => $productRec->nameEn, 'code' => $productRec->code, 'packId' => $foundRec->packagingId ?? null);
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
            $pQuery1->where("LOCATE (' {$searchString}', #string)");
            plg_Search::applySearch($searchString, $pQuery1);

            if($rec->_selectedGroupId == 'similar'){
                if(countR($similarProducts)){
                    $pQuery1->in('productId', $similarProducts);
                } else {
                    $pQuery1->where("1=2");
                }
            } elseif(is_numeric($rec->_selectedGroupId)){
                plg_ExpandInput::applyExtendedInputSearch('cat_Products', $pQuery1, $rec->_selectedGroupId, 'productId');
            }
            $pQuery1->limit($settings->maxSearchProducts);

            while($pRec1 = $pQuery1->fetch()){
                $sellable[$pRec1->productId] = (object)array('id' => $pRec1->productId, 'canSell' => $pRec1->canSell, 'code' => $pRec1->code, 'canStore' => $pRec1->canStore, 'measureId' => $pRec1->measureId, 'name' => $pRec1->name, 'nameEn' => $pRec1->nameEn);
                $count++;
                if($count == $settings->maxSearchProducts) break;
            }

            // Ако не е достигнат лимита, се добавят и артикулите с търсене в ключовите думи
            if($count < $settings->maxSearchProducts){
                $notInKeys = array_keys($sellable);
                $pQuery2 = clone $pQuery;
                $pQuery2->limit($settings->maxSearchProducts);
                if($rec->_selectedGroupId == 'similar'){
                    if(countR($similarProducts)){
                        $pQuery2->in('productId', $similarProducts);
                    } else {
                        $pQuery2->where("1=2");
                    }
                } elseif(is_numeric($rec->_selectedGroupId)){
                    plg_ExpandInput::applyExtendedInputSearch('cat_Products', $pQuery2, $rec->_selectedGroupId, 'productId');
                }

                if(empty($searchStringPure)){
                    $pQuery2->where("1=2");
                } else {
                    plg_Search::applySearch($searchString, $pQuery2);
                }

                if(countR($notInKeys)){
                    $pQuery2->notIn('productId', $notInKeys);
                }

                while($pRec2 = $pQuery2->fetch()){
                    $sellable[$pRec2->productId] = (object)array('id' => $pRec2->productId, 'canSell' => $pRec2->canSell, 'code' => $pRec2->code, 'canStore' => $pRec2->canStore, 'measureId' =>  $pRec2->measureId, 'name' => $pRec2->name, 'nameEn' => $pRec2->nameEn);
                    $count++;
                    if($count == $settings->maxSearchProducts) break;
                }
            }
        }

        if(!empty($rec->policyId)){
            $rec->_policy1 = $rec->policyId;
            $rec->_policy2 = pos_Receipts::isForDefaultContragent($rec) ? $settings->policyId : price_ListToCustomers::getListForCustomer($rec->contragentClass, $rec->contragentObjectId);
        } else {
            $rec->_policy1 = $settings->policyId;
            $rec->_policy2 = pos_Receipts::isForDefaultContragent($rec) ? null : price_ListToCustomers::getListForCustomer($rec->contragentClass, $rec->contragentObjectId);
        }
        core_Debug::stopTimer('RES_RENDER_PREPARE_RECS');

        $cacheKey = "{$rec->_policy1}_{$rec->_policy2}_{$priceCache}_{$rec->_selectedGroupId}_{$searchString}";
        $result = core_Cache::get('pos_Terminal', $cacheKey);

        if(!is_array($result)){
            core_Debug::startTimer('RES_RENDER_RESULT_VERBAL');
            $result = $this->prepareProductResultRows($sellable, $rec, $settings);
            core_Debug::stopTimer('RES_RENDER_RESULT_VERBAL');
            core_Cache::set('pos_Terminal', $cacheKey, $result, 180);
        }

        foreach ($result as &$row){
            $row->DATA_URL = (pos_ReceiptDetails::haveRightFor('add', (object)array('receiptId' => $rec->id))) ? toUrl(array('pos_ReceiptDetails', 'addProduct', 'receiptId' => $rec->id), 'local') : null;
            $row->receiptId = $rec->id;
        }

        return $result;
    }


    /**
     * Подготивка на редовете на търсените артикули
     * 
     * @param array $products
     * @param stdClass $rec
     * @param stdClass $settings
     *
     * @return array $res
     */
    private function prepareProductResultRows($products, $rec, $settings)
    {
        $res = array();
        if(!countR($products)) return $res;

        $productClassId = cat_Products::getClassId();
        $showExactQuantities = pos_Setup::get('SHOW_EXACT_QUANTITIES');

        $packs = array();
        $packQuery = cat_products_Packagings::getQuery();
        $packQuery->in('productId', array_keys($products));
        $packQuery->show('productId,packagingId,quantity,isBase');
        while ($packRec = $packQuery->fetch()){
            $packs[$packRec->productId][$packRec->packagingId] = $packRec;
        }

        $now = dt::now();

        foreach ($products as $id => $pRec) {
            if(isset($pRec->packId)){
                $packId = $pRec->packId;
            } else {
                $productPacks = is_array($packs[$id]) ? $packs[$id] : array();
                $foundBasePackArr = array_filter($productPacks, function($a) {return $a->isBase == 'yes';});
                if(countR($foundBasePackArr)){
                    $packId = key($foundBasePackArr);
                } else {
                    $packId = $pRec->measureId;
                }
            }
            $perPack = isset($packs[$id][$packId]) ? $packs[$id][$packId]->quantity : 1;
            core_Debug::startTimer('TERMINAL_RESULT_GET_LOWER_PRICE');
            $priceRes = pos_ReceiptDetails::getLowerPriceObj($rec->_policy1, $rec->_policy2, $id, $packId, 1, $now);
            core_Debug::stopTimer('TERMINAL_RESULT_GET_LOWER_PRICE');
            $productRec = (object)array('id' => $id, 'name' => $pRec->name, 'nameEn' => $pRec->nameEn, 'code' => $pRec->code);

            // Обръщаме реда във вербален вид
            $res[$id] = new stdClass();;
            $Double = core_Type::getByName('double(decimals=2)');
            $obj = (object) array('productId' => $id, 'measureId' => $pRec->measureId, 'packagingId' => $packId);

            if (empty($priceRes->price)){
                $res[$id]->price = "<b class='red'>n/a</b>";
            } else {
                if(!empty($priceRes->discount)){
                    $priceRes->price *= (1 - $priceRes->discount);
                }

                $price = $priceRes->price * $perPack;
                if($settings->chargeVat == 'yes'){
                    $vat = cat_Products::getVat($id, null, $settings->vatExceptionId);
                    $price *= 1 + $vat;
                }

                $obj->price = $price;
                $res[$id]->price = currency_Currencies::decorate($Double->toVerbal($obj->price));
            }
            
            $res[$id]->stock = core_Type::getByName('double(smartRound)')->toVerbal($obj->stock);
            $packagingId = ($obj->packagingId) ? $obj->packagingId : $obj->measureId;
            $res[$id]->packagingId = cat_UoM::getSmartName($packagingId, $obj->stock);
            $res[$id]->productId = mb_subStr(cat_Products::getVerbal($productRec, 'name'), 0, 80);

            if($settings->showProductCode == 'yes'){
                $res[$id]->code = !empty($pRec->code) ? cat_Products::getVerbal($productRec, 'code') : "Art{$obj->productId}";
            }

            $res[$id]->photo = $this->getPosProductPreview($obj->productId, 140, 140, $settings);
            $res[$id]->CLASS = ' pos-add-res-btn navigable enlargable';
            if($settings->productBtnTpl == 'pictureAndText' && !$res[$id]->photo){
                $res[$id]->CLASS .= " noPhoto";
            }
            $res[$id]->DATA_ENLARGE_OBJECT_ID = $id;
            $res[$id]->DATA_ENLARGE_CLASS_ID = $productClassId;
            $res[$id]->DATA_MODAL_TITLE = cat_Products::getRecTitle($productRec);
            $res[$id]->id = $id;
            if($pRec->canSell != 'yes'){
                $res[$id]->CLASS .= ' notSellable';
            }

            $stock = ($pRec->canStore == 'yes') ? pos_Receipts::getBiggestQuantity($id, $rec->pointId) : null;
            if($packId != cat_UoM::fetchBySysId('pcs')->id || (isset($stock) && empty($stock))){
                $res[$id]->measureId = tr(cat_UoM::getSmartName($packId, null,2));
            }

            if (isset($stock)) {
                if($showExactQuantities == 'yes'){
                    $stockInPack = $stock / $perPack;
                    $stockInPackVerbal = core_Type::getByName('double(smartRound)')->toVerbal($stockInPack);
                    $res[$id]->measureId = $stockInPackVerbal . " <i>" . cat_UoM::getSmartName($packId, $stockInPack) . "</i>";
                    $res[$id]->measureId = ht::styleNumber($res[$id]->measureId, $stockInPack);
                } elseif($stock <= 0) {
                    $measureId = cat_UoM::getSmartName($packId, 0);
                    $res[$id]->measureId = "<span class='notInStock'>0 {$measureId}</span>";
                }
            }
            $res[$id]->_groups = $pRec->groups;
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
    private function getPosProductPreview($productId, $width, $height, $settings = array())
    {
        $photo = cat_Products::getParams($productId, 'preview');
        if($settings->productBtnTpl == 'pictureAndText' && empty($photo)) return;

        $arr = array();
        core_Debug::startTimer('RENDER_RESULT_GET_PREVIEW_THUMB');
        $thumb = (!empty($photo)) ? new thumb_Img(array($photo, $height, $width, 'fileman')) : new thumb_Img(getFullPath('pos/img/default-image.jpg'), $width, $height, 'path');
        $res = $thumb->createImg($arr);
        core_Debug::stopTimer('RENDER_RESULT_GET_PREVIEW_THUMB');

        return $res;
    }
    
    
    /**
     * Рендиране на таба с бележките
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
        $rec->_selectedReceiptFilter = Mode::get("currentSelectedReceiptFilter{$rec->id}");
        $rec->_selectedReceiptFilter = (!empty($rec->_selectedReceiptFilter)) ? $rec->_selectedReceiptFilter : 'draft';
        
        $string = plg_Search::normalizeText($string);
        $maxSearchReceipts = pos_Points::getSettings($rec->pointId, 'maxSearchReceipts');
       
        if (Mode::get('screenMode') == 'narrow') {
            $tpl = new core_ET("<div class='tabs receiptTabs'><select style='width: 90%' class='tabHolder receipts'>[#TAB#]</select></div>");
        } else {
            $tpl = new core_ET("<div class='tabs receiptTabs'><ul class='tabHolder receipts'>[#TAB#]</ul></div>");
        }
        
        $tabArr = array('draft' => tr('Чернови'), 'paid' => tr('Платени'), 'waiting' => tr('Чакащи'), 'closed' => tr('Приключени'), 'transfered' => tr('Прехвърлени'), 'rejected' => tr('Оттеглени'));
        foreach ($tabArr as $tabValue => $tabCaption){
            $active = ($rec->_selectedReceiptFilter == $tabValue) ? 'active' : '';
            
            $tabTitle = tr("Избор на|*: \"{$tabCaption}\"");
            if (Mode::get('screenMode') == 'narrow') {
                $tab = "<option id='group{$tabValue}' data-id = '{$tabValue}'>{$tabCaption}</option>";
            } else {
                $tab = "<li id='group{$tabValue}' data-id = '{$tabValue}' class='selectable {$active}' title='{$tabTitle}'>{$tabCaption}</li>";
            }
            
            $tpl->append($tab, "TAB");
        }
        
        // Намираме всички чернови бележки и ги добавяме като линк
        $query = pos_Receipts::getQuery();
        $query->XPR('createdDate', 'date', 'DATE(#createdOn)');
        $query->orderBy("#createdDate,#id", 'DESC');
        $query->limit($maxSearchReceipts);
        if(!empty($string)){
            plg_Search::applySearch($string, $query);
            if(type_Int::isInt($string)){
                $query->orWhere(array("#id = [#1#]", $string));
            }
        }
        
        if(in_array($rec->_selectedReceiptFilter, array('draft', 'waiting', 'closed', 'rejected'))){
            $query->where("#state = '{$rec->_selectedReceiptFilter}'");
        } elseif($rec->_selectedReceiptFilter == 'transfered'){
            $query->where("#transferredIn IS NOT NULL");
        } elseif($rec->_selectedReceiptFilter == 'paid'){
            $query->where("#paid IS NOT NULL AND #paid != 0 AND (#state != 'closed' && #state != 'rejected')");
        }
        
        $disabledClass = (pos_Receipts::haveRightFor('add')) ? 'navigable' : 'disabledBtn';
        $addUrl = (pos_Receipts::haveRightFor('add')) ? array('pos_Receipts', 'new') : array();
        
        $rows = $otherContragentReceipts = array();
        $pointId = pos_Points::getCurrent();
        $rows[$pointId] = array();
        $isAnonymous = pos_Receipts::isForDefaultContragent($rec);

        while($receiptRec = $query->fetch()){
            $openUrl = (pos_Receipts::haveRightFor('terminal', $receiptRec->id)) ? array('pos_Terminal', 'open', 'receiptId' => $receiptRec->id, 'opened' => true) : array();
            $class = (countR($openUrl)) ? ' navigable' : ' disabledBtn';
            $class .= ($receiptRec->id == $rec->id) ? ' currentReceipt' : '';
            $btnTitle = self::getReceiptTitle($receiptRec);
            $warning = null;
            if($rec->pointId != $receiptRec->pointId){
                $warning = 'Бележката е от друг POS|*!';
            }
            $rows[$receiptRec->pointId][$receiptRec->id] = ht::createLink($btnTitle, $openUrl, $warning, array('id' => "receipt{$receiptRec->id}", 'class' => "pos-notes posBtns {$class} state-{$receiptRec->state} enlargable", 'title' => 'Отваряне на бележката', 'data-enlarge-object-id' => $receiptRec->id, 'data-enlarge-class-id' => pos_Receipts::getClassId(), 'data-modal-title' => strip_tags(pos_Receipts::getRecTitle($receiptRec))));

            // Ако текущата бележка е на НЕ анонимен клиент, търсят се и другите негови бележки;
            if(!$isAnonymous && $rec->contragentClass == $receiptRec->contragentClass && $rec->contragentObjectId == $receiptRec->contragentObjectId){
                $url = ($receiptRec->id != $rec->id) ? $openUrl : array();
                $otherClass = ($receiptRec->id != $rec->id) ? $class : 'disabledBtn current';
                $otherContragentReceipts[$receiptRec->id] = ht::createLink($btnTitle, $url, $warning, array('id' => "receiptSameClient{$receiptRec->id}", 'class' => "pos-notes posBtns {$otherClass} state-{$receiptRec->state} enlargable", 'title' => 'Отваряне на бележката', 'data-enlarge-object-id' => $receiptRec->id, 'data-enlarge-class-id' => pos_Receipts::getClassId(), 'data-modal-title' => strip_tags(pos_Receipts::getRecTitle($receiptRec))));
            }
        }

        $contragentName = cls::get($rec->contragentClass)->getVerbal($rec->contragentObjectId, 'name');
        if($rec->_selectedReceiptFilter == 'draft'){
            $rows[$pointId] = array('-1' => ht::createLink('+ Нова бележка', $addUrl, null, array('id' => "receiptnew", 'class' => "pos-notes posBtns {$disabledClass}", 'title' => 'Създаване на нова бележка'))) + $rows[$pointId];
            if(countR($otherContragentReceipts)){
                $addUrl['contragentClass'] = $rec->contragentClass;
                $addUrl['contragentObjectId'] = $rec->contragentObjectId;
                $addUrl['forced'] = true;
                $otherContragentReceipts = array(ht::createLink("+ {$contragentName}", $addUrl, null, array('id' => "receiptnewSame", 'class' => "pos-notes posBtns {$disabledClass}", 'title' => 'Създаване на нова бележка на същия клиент'))) + $otherContragentReceipts;
            }
        }

        uksort($rows, function($k, $v) use ($rec) {
            return ($k == $rec->pointId) ? -1 : 1;
        });

        if(countR($otherContragentReceipts)){
            $rows = array('-1' => $otherContragentReceipts) + $rows;
        }

        if(isset($rec->revertId) && $rec->revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT){
            $revertRec = pos_Receipts::fetch($rec->revertId);
            $btnTitle = self::getReceiptTitle($revertRec);
            $openUrl = (pos_Receipts::haveRightFor('terminal', $revertRec->id)) ? array('pos_Terminal', 'open', 'receiptId' => $revertRec->id, 'opened' => true) : array();
            $class = (countR($openUrl)) ? ' navigable' : ' disabledBtn';

            $revertBtn = ht::createLink($btnTitle, $openUrl, null, array('id' => "receiptRevertClient{$revertRec->id}", 'class' => "pos-notes posBtns {$class} state-{$revertRec->state} enlargable", 'title' => 'Отваряне на бележката', 'data-enlarge-object-id' => $revertRec->id, 'data-enlarge-class-id' => pos_Receipts::getClassId(), 'data-modal-title' => strip_tags(pos_Receipts::getRecTitle($revertRec))));
            $rows = array('-2' => array($revertRec->id => $revertBtn)) + $rows;
        }

        if(countR($rows)){
            $tpl->prepend("<div class='contentHolderResults'>");
            foreach ($rows as $pId => $btnRows){
                $pointName = pos_Points::getTitleById($pId);
                $text = ($pId != -1) ? ($pId == -2 ? 'СТОРНО' : "|Бележки в|* {$pointName}") : $contragentName;

                $tpl->append(tr("|*<div class='divider'>{$text}</div>"));
                $tpl->append("<div class='grid'>");
                foreach ($btnRows as $receiptBtn){
                    $tpl->append($receiptBtn);
                }
                $tpl->append("</div>");
            }
            $tpl->append("</div>");
        } else {
            $tpl->append("<div class='contentHolderResults'><div class='noFoundInGroup'>" . tr("Няма намерени бележки") . "</div></div>");
        }
        
        $tpl->prepend("<div class='withTabs'>");
        $tpl->append("</div>");
        $tpl->removeBlocksAndPlaces();
        jquery_Jquery::run($tpl, "changePriceSpans();");
        
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

        $amountVerbalInner = '';
        if($rec->change < 0 && $rec->paid){
            $changedVerbal = core_Type::getByName('double(decimals=2)')->toVerbal(abs($rec->change));
            $amountVerbalInner = "<span class='prices'><span class='receiptResultAmount'>" .core_Type::getByName('double(decimals=2)')->toVerbal($rec->total) . "</span>";
            $amountVerbalInner .= "<span class='receiptResultChangeAmount hidden'>{$changedVerbal}</span></span>";
            $amountVerbal = "";
        } else {
            $amountVerbal = "<span class='receiptResultAmount'>" .core_Type::getByName('double(decimals=2)')->toVerbal($rec->total) . "</span>";
        }

        if(isset($rec->returnedTotal)){
            $returnedTotalVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($rec->returnedTotal);
            $amountVerbal .= " <span class='receiptResultReturnedAmount'>(-{$returnedTotalVerbal})</span>";

        } elseif(isset($rec->revertId)){
            $symbol = html_entity_decode('&#8630;', ENT_COMPAT | ENT_HTML401, 'UTF-8');
            $amountVerbal .= " <span class='receiptResultReturnedAmount'>{$symbol}{$amountVerbal}</span>";
        }

        $num = pos_Receipts::getReceiptShortNum($rec->id);
        $contragentName = pos_Receipts::isForDefaultContragent($rec) ? pos_Points::getVerbal($rec->pointId, 'name') : cls::get($rec->contragentClass)->getVerbal($rec->contragentObjectId, 'name');
        $contragentName = str::limitLen($contragentName, 18);
        $num .= " / {$contragentName}";

        $title = "{$num}<div class='nowrap'><span class='spanDate'>{$date}</span>  {$amountVerbalInner}<span class='otherPrice'> {$amountVerbal}</span></div>";

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
     * @param boolean $autoFlush
     * @param boolean $autoFiscPrintIfPossible
     * 
     * @return array $res
     */
    public static function returnAjaxResponse($receiptId, $selectedRecId, $success, $refreshTable = false, $refreshPanel = true, $refreshResult = true, $sound = null, $refreshHeader = false, $autoFlush = true, $removeBlurScreen = null, $autoFiscPrintIfPossible = false)
    {
        $me = cls::get(get_called_class());
        $Receipts = cls::get('pos_Receipts');
        
        // Форсиране на обновяването на мастъра, за да е сигурно че данните в бележката са актуални
        if($autoFlush){
            $Receipts->flushUpdateQueue($receiptId);
        }
        $rec = $Receipts->fetch($receiptId, '*', false);
        $operation = Mode::get("currentOperation{$rec->id}");
        $string = Mode::get("currentSearchString{$rec->id}");

        $res = array();
        if($success === true){
            
            if($refreshPanel === true){
                $toolsTpl = $me->getCommandPanel($rec);
                
                // Ще се реплейсва и пулта
                core_Debug::startTimer('RES_RENDER_COMMAND_PANEL');
                $resObj = new stdClass();
                $resObj->func = 'html';
                $resObj->arg = array('id' => 'tools-holder', 'html' => $toolsTpl->getContent(), 'replace' => true);
                core_Debug::stopTimer('RES_RENDER_COMMAND_PANEL');

                $res[] = $resObj;
            }
            
            if($refreshTable === true){
                $receiptTpl = $me->getReceipt($rec);

                core_Debug::startTimer('RES_RENDER_RECEIPT');
                $resObj = new stdClass();
                $resObj->func = 'html';
                $resObj->arg = array('id' => 'receipt-table', 'html' => $receiptTpl->getContent(), 'replace' => true);
                core_Debug::stopTimer('RES_RENDER_RECEIPT');
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
                core_Debug::startTimer('RES_RENDER_RESULT');
                $resultTpl = $me->renderResult($rec, $operation, $string, $selectedRecId);
                $resObj = new stdClass();
                $resObj->func = 'html';
                $resObj->arg = array('id' => 'result-holder', 'html' => $resultTpl->getContent(), 'replace' => true);
                core_Debug::stopTimer('RES_RENDER_RESULT');

                $res[] = $resObj;
            } else {
                $resObj = new stdClass();
                $resObj->func = 'restoreOpacity';
                $res[] = $resObj;
            }

            // Ще се реплейсват резултатите
            if($refreshHeader){
                $headerTpl = $me->renderHeader($rec);
                $resObj = new stdClass();
                $resObj->func = 'html';
                $resObj->arg = array('id' => 'receiptTerminalHeader', 'html' => $headerTpl->getContent(), 'replace' => true);
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
        } else {
            $resObj = new stdClass();
            $resObj->func = 'restoreOpacity';
            $res[] = $resObj;
        }

        if(isset($removeBlurScreen)){
            $resObj = new stdClass();
            $resObj->func = 'removeBlurScreen';
            $resObj->arg = array('elementClass' => $removeBlurScreen);
            $res[] = $resObj;
        }

        if($autoFiscPrintIfPossible){
            $resObj = new stdClass();
            $resObj->func = 'autoFiscPrintIfPossible';
            $res[] = $resObj;
        }

        $addedProduct = Mode::get("productAdded{$receiptId}");
        
        $resObj = new stdClass();
        $resObj->func = 'toggleAddedProductFlag';
        $resObj->arg = array('flag' => !empty($addedProduct));
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


    /**
     * Задаване на артикула да стане продаваем отново по разписание
     */
    public function act_setMakeSellableProductOnTime()
    {
        expect($productId = Request::get('productId', 'int'));
        expect($receiptId = Request::get('receiptId', 'int'));
        expect($hash = Request::get('hash', 'varchar'));
        expect($tempCloseTime = pos_Setup::get('TEMPORARILY_CLOSE_PRODUCT_TIME'));
        expect($hash == md5("{$productId}_{$receiptId}_SALT"));

        core_CallOnTime::setOnce('cat_Products', 'makeSellableAgainOnTime', $productId, dt::addSecs($tempCloseTime, dt::now()));
        $timeVerbal = core_Type::getByName('time')->toVerbal($tempCloseTime);

        redirect(array('pos_Terminal', 'open', 'receiptId' => $receiptId), false, "Артикулът ще стане отново продаваем след|* {$timeVerbal}");
    }
}
