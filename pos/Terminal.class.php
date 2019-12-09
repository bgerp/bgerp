<?php


/**
 * Контролер на терминала за пос продажби
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com> и Ivelin Dimov <ivelin_pdimov@abv.bg> 
 * @copyright 2006 - 2019 Experta OOD
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
     * Име на източника
     */
    protected $clsName = 'pos_Points';
    
    
    /**
     * При търсене до колко продукта да се показват в таба
     */
    protected $maxSearchProducts = 20;
    
    
    /**
     * Полета
     */
    protected $fieldArr = array('payments', 'policyId', 'caseId', 'storeId');
    
    
    /**
     * Кои са разрешените операции
     */
    protected static $operationsArr = "add=Артикул,payment=Плащане,quantity=Количество,price=Цена,discount=Отстъпка,text=Текст,contragent=Клиент,receipts=Бележки,revert=Сторно";
    
    
    /**
     * Кои операции са забранени за нови бележки
     */
    protected static $forbiddenOperationOnEmptyReceipts = array('discount', 'price', 'text', 'quantity', 'payment');
    
    
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
        if(Request::get('opened', 'int')){
            $redirectUrl = getCurrentUrl();
            unset($redirectUrl['opened']);
            
            Mode::setPermanent("currentOperation", 'add');
            Mode::setPermanent("currentSearchString", null);
            redirect($redirectUrl);
        }
        
        $Receipts = cls::get('pos_Receipts');
        $Receipts->requireRightFor('terminal');
        expect($id = Request::get('receiptId', 'int'));
        expect($rec = $Receipts->fetch($id));
        
        // Имаме ли достъп до терминала
        if (!$Receipts->haveRightFor('terminal', $rec)) {
            
            return new Redirect(array($Receipts, 'new'));
        }
        
        $tpl = getTplFromFile('pos/tpl/terminal/Layout2.shtml');
        $tpl->replace(pos_Points::getTitleById($rec->pointId), 'PAGE_TITLE');
        $tpl->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf('img/16/cash-register.png', '"', true) . '>', 'HEAD');
        $img = ht::createImg(array('path' => 'img/16/logout.png'));
        
        // Добавяме бележката в изгледа
        $receiptTpl = $this->getReceipt($rec);
        $tpl->replace($receiptTpl, 'RECEIPT');
        $tpl->replace(ht::createLink($img, array('core_Users', 'logout', 'ret_url' => true), false, 'title=Излизане от системата'), 'EXIT_TERMINAL');
        
        // Ако не сме в принтиране, сменяме обвивквата и рендираме табовете
        if (!Mode::is('printing')) {
            
            // Задаване на празна обвивка
            Mode::set('wrapper', 'page_Empty');
            
            // Ако сме чернова, добавяме пултовете
            if ($rec->state == 'draft') {
                
                $defaultOperation = Mode::get("currentOperation") ? Mode::get("currentOperation") : 'quantity';
                $defaultSearchString = Mode::get("currentSearchString");
                
                // Добавяне на табовете под бележката
                $toolsTpl = $this->getCommandPanel($rec, $defaultOperation);
                $tpl->replace($toolsTpl, 'TAB_TOOLS');
                
                // Добавяне на табовете показващи се в широк изглед отстрани
                $lastRecId = pos_ReceiptDetails::getLastRec($rec->id, 'sale')->id;
                $resultTabHtml = $this->renderResult($rec, $defaultOperation, $defaultSearchString, $lastRecId);
                $tpl->append($resultTabHtml, 'SEARCH_RESULT');
            }
        }
        
        $data = (object) array('rec' => $rec);
        $this->invoke('AfterRenderSingle', array(&$tpl, $data));
        
        // Вкарване на css и js файлове
        $this->pushTerminalFiles($tpl, $rec);
        $this->renderWrapping($tpl);
        
        return $tpl;
    }
    
    
    /**
     * Увеличаване на избрания артикул
     * 
     * @return array $res
     */
    public function act_EnlargeProduct()
    {
        $productId = Request::get('productId', 'int');
        if(empty($productId)) {
            
            return array();
        }
        
        $document = doc_Containers::getDocument(cat_Products::fetchField($productId, 'containerId'));
        
        // Рендиране на изгледа на артикула
        Mode::push('noBlank', true);
        $docHtml = $document->getInlineDocumentBody('xhtml');
        Mode::pop('noBlank', true);
        
        // Ще се реплейсва и пулта
        $res = array();
        $resObj = new stdClass();
        $resObj->func = 'html';
        $resObj->arg = array('id' => 'productInfo', 'html' => $docHtml->getContent(), 'replace' => true);
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
        $operation = Mode::get("currentOperation");
        $keyupUrl = null;
        
        switch($operation){
            case 'add':
                $inputUrl = array('pos_ReceiptDetails', 'addProduct', 'receiptId' => $rec->id);
                $keyupUrl = array($this, 'displayOperation', 'receiptId' => $rec->id, 'refreshPanel' => 'no');
                break;
            case 'quantity':
                $inputUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setquantity');
                break;
            case 'discount':
                $inputUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setdiscount');
                $keyupUrl = array($this, 'displayOperation', 'receiptId' => $rec->id, 'refreshPanel' => 'no');
                break;
            case 'price':
                $inputUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setprice');
                break;
            case 'text':
                $inputUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'settext');
                break;
            case 'payment';
                break;
            case 'contragent';
                $keyupUrl = array($this, 'displayOperation', 'receiptId' => $rec->id, 'refreshPanel' => 'no');
                break;
            case 'revert';
            $keyupUrl = array($this, 'displayOperation', 'receiptId' => $rec->id, 'refreshPanel' => 'no');
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
        $inputValue = ($operation == 'payment') ? $value : Mode::get("currentSearchString");
        
        $searchUrl = toUrl(array($this, 'displayOperation', 'receiptId' => $rec->id), 'local');
        $params = array('name' => 'ean', 'value' => $inputValue, 'type' => 'text', 'class'=> 'large-field select-input-pos', 'data-url' => $inputUrl, 'data-keyupurl' => $keyupUrl, 'title' => 'Въвеждане', 'list' => 'suggestions');
        if(Mode::is('screenMode', 'narrow')) {
            $params['readonly'] = 'readonly';
        }
        
        // Може ли да се задава отстъпка?
        $operations = arr::make(self::$operationsArr);
        if (pos_Setup::get('SHOW_DISCOUNT_BTN') != 'yes') {
            unset($operations['discount']);
        }
        
        $detailsCount = pos_ReceiptDetails::count("#receiptId = {$rec->id}");
        if(empty($detailsCount)){
            foreach (self::$forbiddenOperationOnEmptyReceipts as $operationToRemove){
                unset($operations[$operationToRemove]);
            }
        }
        
        // Показване на възможните операции
        $currentOperation = Mode::get("currentOperation");
        if(Mode::is('screenMode', 'narrow')){
            $operationSelectFld = ht::createSelect('operation', $operations, $currentOperation, array('class' => '', 'data-url' => $searchUrl));
            $block->append($operationSelectFld, 'INPUT_FLD');
        } else {
            foreach ($operations as $operation => $operationCaption){
                $class = 'operationBtn';
                if($operation == $currentOperation){
                    $class .= " active";
                }
                $btn = ht::createFnBtn($operationCaption, '', '', array('data-url' => $searchUrl, 'class' => $class, 'data-value' => $operation));
                $block->append($btn, 'INPUT_FLD');
            }
        }
        
        // Бутон за трансфер, ако контрагента не е дефолтния
        if(pos_Receipts::haveRightFor('transfer')){
            $defaultContragentId = pos_Points::defaultContragent($rec->pointId);
            if(!($defaultContragentId == $rec->contragentObjectId && $rec->contragentClass == crm_Persons::getClassId())){
                $transferUrl = array('pos_Receipts', 'transfer', $rec->id, 'contragentClassId' => $rec->contragentClass, 'contragentId' => $rec->contragentObjectId);
                $transferBtn = ht::createBtn('Прехвърли', $transferUrl, 'Наистина ли желаете да прехвърлите бележката към папката на контрагента|*?', false, 'class=operationBtn button');
                $block->append($transferBtn, 'INPUT_FLD');
            }
        }
        
        $contoUrl = (pos_Receipts::haveRightFor('close', $rec)) ? array('pos_Receipts', 'close', $rec->id) : null;
        $disClass = ($contoUrl) ? '' : 'disabledBtn';
        $closeBtn = ht::createBtn('Приключи', $contoUrl, 'Желаете ли да приключите бележката|*?', false, "class=operationBtn button closeBtn {$disClass}");
        $block->append($closeBtn, 'INPUT_FLD');
        
        if($currentOperation == 'add'){
            $enlargeBtn = ht::createFnBtn(' ', '', '', array('data-url' => toUrl(array('pos_Terminal', 'EnlargeProduct'), 'local'), 'class' => 'operationBtn enlargeProductBtn', 'ef_icon' => 'img/32/search.png'));
            $block->append($enlargeBtn, 'INPUT_FLD');
        }

        $input = ht::createElement('input', $params);
        $holder = ht::createElement('div', array('class' => 'inputHolder'), $input, true);
        $block->append($holder, 'INPUT_FLD');
        $block->append($this->renderKeyboard('tools'), 'KEYBOARDS');
        
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
        $selectedRecId = Request::get('recId', 'int');
       
        $string = Request::get('search', 'varchar');
        Mode::setPermanent("currentOperation", $operation);
        Mode::setPermanent("currentSearchString", $string);
        
        return static::returnAjaxResponse($rec, $selectedRecId, true, false, $refreshPanel);
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
                if(isset($rec->revertId)){
                    $res = $this->getResultProducts($rec, $string, $rec->revertId);
                } else {
                    $res = (empty($string)) ? $this->renderProductBtns($rec) : $this->getResultProducts($rec, $string);
                }
                break;
            case 'receipts':
                $res = $this->renderResultReceipt($rec);
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
                $res = $this->renderResultRevert($rec, $string, $selectedRec);
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
        
        $count = 0;
        $query = pos_ReceiptDetails::getQuery();
        $query->where("#action = 'sale|code' AND #text IS NOT NULL AND #text != ''");
        $query->XPR('orderBy', 'int', "(CASE #receiptId WHEN '{$rec->id}' THEN 1 ELSE 2 END)");
        $query->show('text');
        $query->orderBy('orderBy');
        $query->limit(10);
        
        $texts = arr::extractValuesFromArray($query->fetchAll(), 'text');
        
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
        $discountInputed = core_Type::getByName('double')->fromVerbal($string);
        if(isset($discountInputed) && $discountInputed >= 0 && $discountInputed <= 100){
            if(!in_array($discountInputed, $discountsArr)){
                $discountsArr = array_merge(array($discountInputed), $discountsArr);
            }
        }
        
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
        $tpl = new core_ET("");
        if(empty($string)){
            $tpl->append(tr("Моля търсете контрагенти"));
            
            return $tpl;
        }
        
        $contragents = array();
        
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
        
        $canSetContragent = pos_Receipts::haveRightFor('setcontragent', $rec);
        $cnt = 0;
        foreach ($contragents as $obj){
            $setContragentUrl = ($canSetContragent === true) ? array('pos_Receipts', 'setcontragent', 'id' => $rec->id, 'contragentClassId' => $obj->contragentClassId, 'contragentId' => $obj->contragentId, 'ret_url' => true) : array();
            $holderDiv = ht::createElement('div', array("id" => "contragent{$cnt}", 'class' => 'posResultContragent navigable'), $obj->title, true);
            $holderDiv = ht::createLink($holderDiv, $setContragentUrl);
            
            $tpl->append($holderDiv);
            $cnt++;
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
    private function renderResultRevert($rec, $string, $selectedRec)
    {
        $Receipts = cls::get('pos_Receipts');
        $string = plg_Search::normalizeText($string);
        $query = $Receipts->getQuery();
        $query->where("#revertId IS NULL AND #state != 'draft' AND #pointId = {$rec->pointId}");
        
        //$foundArr = $Receipts->findReceiptByNumber($string, true);
        
        if (is_object($foundArr['rec'])) {
            $query->where(array("#id = {$foundArr['rec']->id}"));
        } else {
            $query->where(array("#searchKeywords LIKE '%[#1#]%'", $string));
        }
        
        $buttons = array();
        $cnt = 0;
        while($receiptRec = $query->fetch()){
            $buttons[] = ht::createLink(self::getReceiptTitle($receiptRec), array('pos_Receipts', 'revert', $receiptRec->id, 'ret_url' => true), 'Наистина ли желаете да сторнирате бележката|*?', "title=Сторниране на бележката,class=navigable posBtns pos-notes revert-receipt,id=revert{$cnt}");
            $cnt++;
        }
        
        $tpl = new core_ET("");
        foreach ($buttons as $btn){
            $tpl->append($btn);
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
        $Receipts = cls::get('pos_Receipts');
        $tpl = new core_ET("");
        
        $payUrl = (pos_Receipts::haveRightFor('pay', $rec)) ? toUrl(array('pos_ReceiptDetails', 'makePayment', 'receiptId' => $rec->id), 'local') : null;
        $disClass = ($payUrl) ? '' : 'disabledBtn';
        
        $element = ht::createElement("div", array('id' => "payment-1", 'class' => "{$disClass} navigable posBtns payment", 'data-type' => '-1', 'data-url' => $payUrl), tr('В брой'), true);
        $tpl->append($element);
        
        $payments = pos_Points::fetchSelected($rec->pointId);
        foreach ($payments as $paymentId => $paymentTitle){
            $element = ht::createElement("div", array('id' => "payment{$paymentId}", 'class' => "{$disClass} navigable posBtns payment", 'data-type' => $paymentId, 'data-url' => $payUrl), tr($paymentTitle), true);
            $tpl->append($element);
        }
        $tpl->append("<div class='clearfix21'></div>");
        
        // Добавяне на бутон за приключване на бележката
        $buttons = array();
        $Receipts->invoke('BeforeGetPaymentTabBtns', array(&$buttons, $rec));
        foreach ($buttons as $btn){
            $tpl->append($btn);
        }
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
        
        $baseClass = "resultPack navigable posBtns";
        $basePackName = cat_UoM::getVerbal($measureId, 'name');
        $dataUrl = (pos_ReceiptDetails::haveRightFor('edit', $selectedRec)) ? toUrl(array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setquantity'), 'local') : null;
        
        $buttons = array();
        $buttons[$measureId] = ht::createElement("div", array('id' => "packaging{$basePackName}", 'class' => $baseClass, 'data-pack' => $basePackName, 'data-url' => $dataUrl), tr($basePackName), true);
       
        $packQuery = cat_products_Packagings::getQuery();
        $packQuery->where("#productId = {$selectedRec->productId}");
        while ($packRec = $packQuery->fetch()) {
            
            $packagingId = cat_UoM::getVerbal($packRec->packagingId, 'name');
            $baseMeasureId = $measureId;
            $packRec->quantity = cat_Uom::round($baseMeasureId, $packRec->quantity);
            $packaging = "|{$packagingId}|*</br> <small>" . core_Type::getByName('double(smartRound)')->toVerbal($packRec->quantity) . " " . cat_UoM::getVerbal($baseMeasureId, 'name') . "</small>";
            
            $buttons[$packRec->packagingId] = ht::createElement("div", array('id' => "packaging{$packagingId}{$selectedRec->productId}", 'class' => $baseClass, 'data-pack' => $packagingId, 'data-url' => $dataUrl), tr($packaging), true);
        }
        
        $firstBtn = $buttons[$basePackagingId];
        unset($buttons[$basePackagingId]);
        $buttons = array($basePackagingId => $firstBtn) + $buttons;
        
        $query = pos_ReceiptDetails::getQuery();
        $query->where("#productId = {$selectedRec->productId} AND #action = 'sale|code' AND #quantity > 0");
        $query->show("quantity,value");
        $query->groupBy("quantity,value");
        $query->limit(10);
        
        while ($productRec = $query->fetch()) {
            
            $packagingId = cat_UoM::getVerbal($productRec->value, 'name');
            Mode::push('text', 'plain');
            $quantity = core_Type::getByName('double(smartRound)')->toVerbal($productRec->quantity);
            Mode::pop('text', 'plain');
            $btnCaption =  "{$quantity} " .tr(str::getPlural($productRec->quantity, $packagingId, true));
            $buttons["{$productRec->packagingId}|{$productRec->quantity}"] = ht::createElement("div", array('id' => "packaging{$packagingId}{$productRec->quantity}{$selectedRec->productId}", 'class' => "{$baseClass} packWithQuantity", 'data-quantity' => $productRec->quantity, 'data-pack' => $packagingId, 'data-url' => $dataUrl), $btnCaption, true);
        }
        
        $tpl = new core_ET("");
        foreach ($buttons as $btn){
            $tpl->append($btn);
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
     * Рендира бързите бутони
     *
     * @return core_ET $block - шаблон
     */
    private function renderProductBtns($rec)
    {
        $products = pos_Favourites::prepareProducts($rec);
        if (!$products->arr) {
            
            return false;
        }
        
        $tpl = pos_Favourites::renderPosProducts($products);
        
        return $tpl;
    }
    
    
    
    /**
     * Рендира клавиатурата
     *
     * @return core_ET $tpl
     */
    public static function renderKeyboard($tab)
    {
        $tpl = getTplFromFile('pos/tpl/terminal/Keyboards.shtml');
        
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
        
        // Слагане на мастър данните
        if (!Mode::is('printing')) {
            $tpl = getTplFromFile('pos/tpl/terminal/Receipt.shtml');
        } else {
            $tpl = getTplFromFile('pos/tpl/terminal/ReceiptPrint.shtml');
        }
        
        $tpl->placeObject($data->row);
        $img = ht::createElement('img', array('src' => sbf('pos/img/bgerp.png', '')));
        $logo = ht::createLink($img, array('bgerp_Portal', 'Show'), null, array('target' => '_blank', 'class' => 'portalLink', 'title' => 'Към портала'));
        $tpl->append($logo, 'LOGO');
        
        if($lastRecId = pos_ReceiptDetails::getLastRec($data->rec->id, 'sale')->id){
            $data->receiptDetails->rows[$lastRecId]->CLASS = 'highlighted';
        }
        
        if($lastRec = pos_ReceiptDetails::getLastRec($data->rec->id)){
            if(strpos($lastRec->action, 'payment') !== false){
                $data->receiptDetails->rows[$lastRec->id]->CLASS = 'paymentMade';
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
    public function pushTerminalFiles_(&$tpl, $rec)
    {
        $tpl->push('css/Application.css', 'CSS');
        $tpl->push('css/default-theme.css', 'CSS');
        $tpl->push('pos/tpl/css/styles.css', 'CSS');
        if (!Mode::is('printing')) {
            $tpl->push('pos/js/scripts.js', 'JS');
            $tpl->push('pos/js/jquery.keynav.js', 'JS');
            $tpl->push('pos/js/hotkeys.js', 'JS');
            jquery_Jquery::run($tpl, 'posActions();');
        }
        
        jqueryui_Ui::enable($tpl);
        
        //@TODO да се добавят стилове от тема $rec->theme
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
        foreach ($data->rows as $row){
            if(!empty($data->rec->paid)){
                $row->CLASS = 'disabledBtn';
            }
            $row->elementId = "product{$row->id}";
            $bTpl = clone $block;
            $bTpl->placeObject($row);
            $bTpl->removeBlocksAndPlaces();
            $tpl->append($bTpl);
        }
        
        if(isset($data->revertReceiptId)){
            $tpl->prepend(tr('Артикулите от оригиналната бележка'));
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
        
        // Ако има сторнираща бележка
        if(isset($data->revertReceiptId)){
            
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
            $folderId = cls::get($data->rec->contragentClass)->fetchField($data->rec->contragentObjectId, 'folderId');
            $pQuery = cat_Products::getQuery();
            $pQuery->where("#canSell = 'yes' AND #state = 'active'");
            $pQuery->where("#isPublic = 'yes' OR (#isPublic = 'no' AND #folderId = '{$folderId}')");
            
            plg_Search::applySearch($data->searchString, $pQuery);
            
            $pQuery->show('id,name,isPublic,nameEn,code');
            $pQuery->limit($this->maxSearchProducts);
            $sellable = $pQuery->fetchAll();
            
            if (!count($sellable)) {
                
                return;
            }
        }
        
        // Ако има стринг и по него отговаря артикул той ще е на първо място
        if(!empty($data->searchString)){
            $foundRec = cat_Products::getByCode($data->searchString);
            if(isset($foundRec->productId) && (!isset($data->revertReceiptId) || (isset($data->revertReceiptId) && pos_ReceiptDetails::fetchField("#receiptId = {$data->revertReceiptId} AND #productId = {$foundRec->productId}")))){
                $sellable = array("{$foundRec->productId}" => (object)array('packId' => isset($foundRec->packagingId) ? $foundRec->packagingId : null)) + $sellable;
            }
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
            $data->rows[$id]->CLASS = ' pos-add-res-btn navigable';
            $data->rows[$id]->DATA_URL = (pos_ReceiptDetails::haveRightFor('add', $obj)) ? toUrl(array('pos_ReceiptDetails', 'addProduct', 'receiptId' => $data->rec->id), 'local') : null;
            $data->rows[$id]->id = $pRec->id;

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
        $row->packagingId = ($obj->packagingId) ? cat_UoM::getTitleById($obj->packagingId) : cat_UoM::getTitleById($obj->measureId);
        $row->packagingId = str::getPlural($obj->stock, $row->packagingId, true);
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
     * @param int $id -ид на бележка
     *
     * @return core_ET $block - шаблон
     */
    private function renderResultReceipt($id)
    {
        $rec = $this->fetchRec($id);
        $block = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('DRAFTS');
        $block->prepend("");
        $pointId = pos_Points::getCurrent('id');
        
        if (pos_Receipts::haveRightFor('add')) {
            $addBtn = ht::createLink("+", array('pos_Receipts', 'new', 'forced' => true), null, 'id=newreceipt,class=pos-notes posBtns openNoteBtn navigable');
            $block->append($addBtn);
        }
        
        // Намираме всички чернови бележки и ги добавяме като линк
        $query = pos_Receipts::getQuery();
        $query->where("#state = 'draft' AND #pointId = '{$pointId}' AND #id != {$rec->id}");
        while ($rec = $query->fetch()) {
            $class = isset($rec->revertId) ? 'revert-receipt' : '';
            $row = ht::createLink(self::getReceiptTitle($rec), array('pos_Terminal', 'open', 'receiptId' => $rec->id, 'opened' => true), null, array('id' => "receipt{$rec->id}", 'class' => "pos-notes posBtns navigable {$class}", 'title' => 'Преглед на бележката'));
            $block->append($row);
        }
        
        $block = ht::createElement('div', array('class' => 'displayFlex'), $block, true);
        
        return $block;
    }
    
    
    /**
     * Как ще се показва бележката
     * 
     * @param stdClass $rec
     * @return string $title
     */
    private static function getReceiptTitle($rec)
    {
        $date = dt::mysql2verbal($rec->createdOn, 'd.m. h:i');
        $amountVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($rec->total);
        $title = "{$rec->id} / {$amountVerbal} <br> {$date}";
        
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
        $rec = $Receipts->fetchRec($receiptId);
        $operation = Mode::get("currentOperation");
        $string = Mode::get("currentSearchString");
        $res = array();
       
        if($success === true){
            $resultTpl = $me->renderResult($rec, $operation, $string, $selectedRecId);
            
            // Ще се реплейсват резултатите
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => 'result-holder', 'html' => $resultTpl->getContent(), 'replace' => true);
            $res[] = $resObj;
            
            if($refreshPanel === true){
                $toolsTpl = $me->getCommandPanel($rec, $operation, $string);
                
                // Ще се реплейсва и пулта
                $resObj = new stdClass();
                $resObj->func = 'html';
                $resObj->arg = array('id' => 'tools-holder', 'html' => $toolsTpl->getContent(), 'replace' => true);
                $res[] = $resObj;
            }
            
            $resObj = new stdClass();
            $resObj->func = 'prepareResult';
            $res[] = $resObj;
            
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
            
            $resObj = new stdClass();
            $resObj->func = 'afterload';
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
