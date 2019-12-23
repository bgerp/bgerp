<?php


/**
 * Мениджър за "Бележки за продажби"
 *
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class pos_ReceiptDetails extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на бележката';
    
    
    /**
     * Мастър ключ към дъските
     */
    public $masterKey = 'receiptId';
    
    
    /**
     * Кой може да променя?
     */
    public $canAdd = 'pos, ceo';
    
    
    /**
     * Кой може да променя?
     */
    public $canEdit = 'pos, ceo';
    
    
    /**
     * Кой може да променя?
     */
    public $canWrite = 'pos, ceo';
    
    
    /**
     * Кой може да зарежда данни от бележката
     */
    public $canLoad = 'pos, ceo';
    
    
    /**
     * Кой може да променя?
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой може да променя?
     */
    public $canDelete = 'pos, ceo';
    
    
    /**
     * Полета за листов изглед
     */
    public $listFields = 'productId,value,quantity,price,discountPercent,amount';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'discountPercent';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('receiptId', 'key(mvc=pos_Receipts)', 'caption=Бележка, input=hidden, silent');
        $this->FLD('action', 'varchar(32)', 'caption=Действие,width=7em;top:1px;position:relative');
        $this->FLD('param', 'varchar(32)', 'caption=Параметри,width=7em,input=none');
        $this->FNC('ean', 'varchar(32)', 'caption=ЕАН, input, class=ean-text');
        $this->FLD('productId', 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Продукт,input=none');
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
        $this->FLD('quantity', 'double(smartRound)', 'caption=К-во,placeholder=К-во,width=4em');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума, input=none');
        $this->FLD('value', 'varchar(32)', 'caption=Мярка, input=hidden,smartCenter');
        $this->FLD('discountPercent', 'percent(min=0,max=1)', 'caption=Отстъпка,input=none');
    }
    
    
    /**
     * Променяме рендирането на детайлите
     */
    public function renderReceiptDetail($data)
    {
        $tpl = new ET('');
        $lastRow = Mode::get('lastAdded');
        
        if (!Mode::is('printing')) {
            $blocksTpl = getTplFromFile('pos/tpl/terminal/ReceiptDetail.shtml');
        } else {
            $blocksTpl = getTplFromFile('pos/tpl/terminal/ReceiptDetailPrint.shtml');
        }
        
        $saleTpl = $blocksTpl->getBlock('sale');
        $paymentTpl = $blocksTpl->getBlock('payment');
        if ($data->rows) {
            foreach ($data->rows as $id => $row) {
                $row->id = $id;
                $action = $this->getAction($data->rows[$id]->action);
                $at = ${"{$action->type}Tpl"};
                if (is_object($at)) {
                    $rowTpl = clone(${"{$action->type}Tpl"});
                    $rowTpl->placeObject($row);
                    if ($lastRow == $row->id) {
                        $rowTpl->replace('pos-hightligted', 'lastRow');
                    }
                    $rowTpl->removeBlocks();
                    $tpl->append($rowTpl);
                }
            }
        } else {
            $tpl->append(new ET("<tr><td colspan='3' class='receipt-sale'>" . tr('Няма записи') . '</td></tr>'));
        }
        
        return $tpl;
    }
    
    
    /**
     * Добавя отстъпка на избран продукт
     */
    public function act_setDiscount()
    {
        $this->requireRightFor('add');
        
        if (!$recId = Request::get('recId', 'int')) {
            core_Statuses::newStatus('|Не е избран ред|*!', 'error');
            
            return $this->returnError($recId);
        }
        
        if (!$rec = $this->fetch($recId)) {
            
            return $this->returnError($recId);
        }
        
        // Трябва да може да се редактира записа
        $this->requireRightFor('add', $rec);
        
        $discount = Request::get('amount');
        $this->getFieldType('discountPercent')->params['Max'] = 1;
        $discount = $this->getFieldType('discountPercent')->fromVerbal($discount);
        if (!isset($discount)) {
            core_Statuses::newStatus('|Не е въведено валидна процентна отстъпка|*!', 'error');
            
            return $this->returnError($rec->receiptId);
        }
        
        if ($discount > 1) {
            core_Statuses::newStatus('|Отстъпката не може да е над|* 100%!', 'error');
            
            return $this->returnError($rec->receiptId);
        }
        
        // Записваме променената отстъпка
        $rec->discountPercent = $discount;
        
        if ($this->save($rec)) {
            core_Statuses::newStatus('|Отстъпката е зададена успешно|*!');
            
            return $this->returnResponse($rec->receiptId);
        }
        core_Statuses::newStatus('|Проблем при задаване на отстъпка|*!', 'error');
        
        return $this->returnError($rec->receiptId);
    }
    
    
    /**
     * При грешка, ако е в Ajax режим, връща празен масив, иначе редиректва към бележката
     */
    public function returnError($id)
    {
        if (Request::get('ajax_mode')) {
            $hitTime = Request::get('hitTime', 'int');
            $idleTime = Request::get('idleTime', 'int');
            $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
            
            // Връщаме статусите ако има
            return (array) $statusData;
        }
        if (!$id) {
            redirect(array('pos_Receipts', 'list'));
        }
        
        redirect(array('pos_Receipts', 'terminal', $id));
    }
    
    
    /**
     * Връщане на отговор, при успех
     */
    public function returnResponse($receiptId)
    {
        // Ако заявката е по ajax
        if (Request::get('ajax_mode')) {
            $receiptTpl = $this->Master->getReceipt($receiptId);
            $toolsTpl = $this->Master->renderToolsTab($receiptId);
            $paymentTpl = $this->Master->renderPaymentTab($receiptId);
            
            // Ще реплейснем само бележката
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => 'receipt-table', 'html' => $receiptTpl->getContent(), 'replace' => true);
            
            // Ще реплесйнем и таба за плащанията
            $resObj1 = new stdClass();
            $resObj1->func = 'html';
            $resObj1->arg = array('id' => 'tools-payment', 'html' => $paymentTpl->getContent(), 'replace' => true);
            
            // Ще реплесйнем и пулта
            $resObj2 = new stdClass();
            $resObj2->func = 'html';
            $resObj2->arg = array('id' => 'tools-form', 'html' => $toolsTpl->getContent(), 'replace' => true);
            
            // Ще реплесйнем и таба за плащанията
            $resObj3 = new stdClass();
            $resObj3->func = 'html';
            $resObj3->arg = array('id' => 'result_contragents', 'html' => ' ', 'replace' => true);
            
            // Показваме веднага и чакащите статуси
            $hitTime = Request::get('hitTime', 'int');
            $idleTime = Request::get('idleTime', 'int');
            $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
            
            $res = array_merge(array($resObj, $resObj1, $resObj2, $resObj3), (array) $statusData);
            
            return $res;
        }
        
        // Ако не сме в Ajax режим пренасочваме към терминала
        redirect(array($this->Master, 'Terminal', $receiptId));
    }
    
    
    /**
     * Промяна на количество на избран продукт
     */
    public function act_setQuantity()
    {
        $this->requireRightFor('add');
        
        // Трябва да има избран ред
        if (!$recId = Request::get('recId', 'int')) {
            core_Statuses::newStatus('|Не е избран ред|*!', 'error');
            
            return $this->returnError($rec->receiptId);
        }
        
        // Трябва да има такъв запис
        if (!$rec = $this->fetch($recId)) {
            
            return $this->returnError($rec->receiptId);
        }
        
        // Трябва да може да се редактира записа
        $this->requireRightFor('add', $rec);
        $quantityId = Request::get('amount');
        
        // Трябва да е подадено валидно количество
        $quantityId = $this->getFieldType('quantity')->fromVerbal($quantityId);
        
        if ($quantityId === false) {
            core_Statuses::newStatus('|Въведеното количество не е валидно|*!', 'error');
            
            return $this->returnError($rec->receiptId);
        }
        
        // Ако е въведено '0' за количество изтриваме реда
        if ($quantityId === (double) 0) {
            $this->delete($recId);
            core_Statuses::newStatus('|Артикулът е изтрит успешно|*!');
            
            return $this->returnResponse($rec->receiptId);
        }
        
        $revertId = pos_Receipts::fetchField($rec->receiptId, 'revertId');
        if(!empty($revertId)){
            $originProductRec = $this->findSale($rec->productId, $revertId, $rec->value);
            if(is_object($originProductRec)){
                $quantityId *= -1;
                if(abs($originProductRec->quantity) < abs($quantityId)){
                    core_Statuses::newStatus("Количеството е по-голямо от продаденото|* {$originProductRec->quantity}", 'error');
                    
                    return $this->returnError($rec->receiptId);
                }
            }
        }
        
        // Преизчисляване на сумата
        $rec->quantity = $quantityId;
        $rec->amount = $rec->price * $rec->quantity;
        
        $error = '';
        if(!pos_Receipts::checkQuantity($rec, $error)){
            core_Statuses::newStatus($error, 'error');
            
            return $this->returnError($rec->receiptId);
        }
        
        // Запис на новото количество
        if ($this->save($rec)) {
            core_Statuses::newStatus('|Количеството е променено успешно|*!');
            
            return $this->returnResponse($rec->receiptId);
        }
        core_Statuses::newStatus('|Проблем при редакция на количество|*!', 'error');
        
        return $this->returnError($rec->receiptId);
    }
    
    
    /**
     * Добавяне на плащане към бележка
     */
    public function act_makePayment()
    {
        $this->requireRightFor('add');
        
        // Трябва да е избрана бележка
        if (!$recId = Request::get('receiptId', 'int')) {
            
            return $this->returnError($recId);
        }
        
        // Можем ли да направим плащане към бележката
        $this->Master->requireRightFor('pay', $recId);
        
        // Трябва да има избран запис на бележка
        if (!$receipt = $this->Master->fetch($recId)) {
            
            return $this->returnError($recId);
        }
        
        // Трябва да е подаден валидно ид на начин на плащане
        $type = Request::get('type', 'int');
        if (!cond_Payments::fetch($type) && $type != -1) {
            
            return $this->returnError($recId);
        }
        
        // Трябва да е подадена валидна сума
        $amount = Request::get('amount');
        $amount = $this->getFieldType('amount')->fromVerbal($amount);
        if(empty($amount)){
            core_Statuses::newStatus('|Липсва сума|*!', 'error');
            
            return $this->returnError($recId);
        } elseif($amount < 0){
            core_Statuses::newStatus('|Сумата трябва да е положителна|*!', 'error');
            
            return $this->returnError($recId);
        }
        
        $diff = abs($receipt->paid - $receipt->total);
        
        $paidAmount = $amount;
        if ($type != -1) {
            $paidAmount = cond_Payments::toBaseCurrency($type, $amount, $receipt->valior);
            
            // Ако платежния метод не поддържа ресто, не може да се плати по-голяма сума
            if (!cond_Payments::returnsChange($type) && (string) abs($paidAmount) > (string) $diff) {
                core_Statuses::newStatus('|Платежния метод не позволява да се плати по-голяма сума от общата|*!', 'error');
                
                return $this->returnError($recId);
            }
        }
        
        if($receipt->revertId){
            $amount *= -1;
        }
        
        // Подготвяме записа на плащането
        $rec = new stdClass();
        $rec->receiptId = $recId;
        $rec->action = "payment|{$type}";
        $rec->amount = $amount;
        
        $paidAmount = $rec->amount;
        if($type != -1){
            $paidAmount = cond_Payments::toBaseCurrency($type, $amount, $receipt->valior);
        }
        
        // Запис на плащането
        if ($this->save($rec)) {
            core_Statuses::newStatus('|Плащането е направено успешно|*!');
            
            return $this->returnResponse($recId);
        }
        core_Statuses::newStatus('|Проблем при плащането|*!', 'error');
        
        return $this->returnError($recId);
    }
    
    
    /**
     * Изтриване на запис от бележката
     */
    public function act_DeleteRec()
    {
        $this->requireRightFor('delete');
        
        // Трябва да има ид на ред за изтриване
        if (!$id = Request::get('recId', 'int')) {
            
            return $this->returnError($receiptId);
        }
        
        // Трябва да има такъв запис
        if (!$rec = $this->fetch($id)) {
            
            return $this->returnError($receiptId);
        }
        
        // Трябва да можем да изтриваме от бележката
        $this->requireRightFor('delete', $rec);
        
        $receiptId = $rec->receiptId;
        
        if ($this->delete($rec->id)) {
            core_Statuses::newStatus('|Успешно изтриване|*!');
            
            // Ъпдейт на бележката след изтриването
            $this->Master->updateReceipt($receiptId);
            
            return $this->returnResponse($receiptId);
        }
        core_Statuses::newStatus('|Проблем при изтриването на ред|*!', 'error');
        
        return $this->returnError($receiptId);
    }
    
    
    /**
     * Подготвя детайла на бележката
     */
    public function prepareReceiptDetails($receiptId)
    {
        $res = new stdClass();
        $query = $this->getQuery();
        $query->where("#receiptId = '{$receiptId}'");
        $query->orderBy('id', 'asc');
        while ($rec = $query->fetch()) {
            $res->recs[$rec->id] = $rec;
            $res->rows[$rec->id] = $this->recToVerbal($rec);
        }
        
        return $res;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $Double = cls::get('type_Double');
        $Double->params['smartRound'] = true;
        $receiptRec = $mvc->Master->fetch($rec->receiptId, 'createdOn,revertId');
        $row->currency = acc_Periods::getBaseCurrencyCode($receiptRec->createdOn);
        
        $action = $mvc->getAction($rec->action);
        switch ($action->type) {
            case 'sale':
                $mvc->renderSale($rec, $row, $receiptRec->createdOn, $fields);
                if ($fields['-list']) {
                    $row->quantity = ($rec->value) ? $row->quantity : $row->quantity;
                }
                break;
            case 'payment':
                $row->actionValue = ($action->value != -1) ? cond_Payments::getTitleById($action->value) : tr('В брой');
                $row->paymentCaption = (empty($receiptRec->revertId)) ? tr('Плащане') : tr('Връщане');
                
                if ($fields['-list']) {
                    $row->productId = tr('Плащане') . ': ' . $row->actionValue;
                    unset($row->quantity,$row->value);
                }
                break;
        }
        
        // Ако може да изтриваме ред и не сме в режим принтиране
        if ($mvc->haveRightFor('delete', $rec) && !Mode::is('printing')) {
            $delUrl = toUrl(array($mvc->className, 'deleteRec'), 'local');
            $row->DEL_BTN = ht::createElement('img', array('src' => sbf('img/16/deletered.png', ''),
                'class' => 'pos-del-btn', 'data-recId' => $rec->id,
                'title' => 'Изтриване на реда',
                'data-warning' => tr('|Наистина ли искате да изтриете записа|*?'),
                'data-url' => $delUrl));
        }
    }
    
    
    /**
     * Рендира информацията за направената продажба
     */
    public function renderSale($rec, &$row, $receiptDate, $fields = array())
    {
        $Varchar = cls::get('type_Varchar');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        
        $productInfo = cat_Products::getProductInfo($rec->productId);
        $perPack = ($productInfo->packagings[$rec->value]) ? $productInfo->packagings[$rec->value]->quantity : 1;
        
        $price = $this->Master->getDisplayPrice($rec->price, $rec->param, $rec->discountPercent, pos_Receipts::fetchField($rec->receiptId, 'pointId'), $rec->quantity);
        $row->price = $Double->toVerbal($price);
        $row->amount = $Double->toVerbal($price * $rec->quantity);
        if ($rec->discountPercent < 0) {
            $row->discountPercent = '+' . trim($row->discountPercent, '-');
        }
        
        $row->code = $Varchar->toVerbal($productInfo->productRec->code);
        
        if ($rec->value) {
            $row->value = tr(cat_UoM::getTitleById($rec->value));
            deals_Helper::getPackInfo($row->value, $rec->productId, $rec->value, $perPack);
        } else {
            $row->value = tr(cat_UoM::getTitleById($productInfo->productRec->measureId));
        }
        
        // Ако отстъпката е нула да не се показва
        if ($rec->discountPercent == 0) {
            unset($row->discountPercent);
        }
        
        $row->productId = ($fields['-list']) ? cat_Products::getHyperLink($rec->productId, true) : cat_Products::getTitleById($rec->productId, true);
    }
    
    
    /**
     * Метод връщаш обект с информация за избраното действие
     * и неговата стойност
     *
     * @param string $string - стринг където от вида "action|value"
     *
     * @return stdClass $action - обект съдържащ ид и стойноста извлечени
     *                  от стринга
     */
    public function getAction($string)
    {
        $actionArr = explode('|', $string);
        $allowed = array('sale', 'discount', 'payment');
        expect(in_array($actionArr[0], $allowed), 'Не е позволена такава операция');
        expect(count($actionArr) == 2, 'Стрингът не е в правилен формат');
        
        $action = new stdClass();
        $action->type = $actionArr[0];
        $action->value = $actionArr[1];
        
        return $action;
    }
    
    
    /**
     * Намира продукта по подаден номер и изчислява неговата цена
     * и отстъпка спрямо клиента, и ценоразписа
     *
     * @param stdClass $rec
     */
    public function getProductInfo(&$rec)
    {
        if ($rec->productId) {
            expect($productId = cat_Products::fetch($rec->productId));
            $product = (object) array('productId' => $rec->productId);
        } elseif ($rec->ean) {
            $product = cat_Products::getByCode($rec->ean);
        }
        
        if (!$product) {
            
            return $rec->productid = null;
        }
        
        $info = cat_Products::getProductInfo($product->productId);
        if (empty($info->meta['canSell'])) {
            
            return $rec->productid = null;
        }
        
        if (!$product->packagingId) {
            if (isset($rec->value)) {
                $basePackId = $rec->value;
            } else {
                $basePackId = key(cat_Products::getPacks($product->productId));
            }
        } else {
            $basePackId = $product->packagingId;
        }
        
        $perPack = ($info->packagings[$basePackId]) ? $info->packagings[$basePackId]->quantity : 1;
        $rec->value = ($basePackId) ? $basePackId : $info->productRec->measureId;
        
        $rec->productId = $product->productId;
        $receiptRec = pos_Receipts::fetch($rec->receiptId);
        $listId = pos_Points::fetchField($receiptRec->pointId, 'policyId');
        
        $Policy = cls::get('price_ListToCustomers');
        $price = $Policy->getPriceInfo($receiptRec->contragentClass, $receiptRec->contragentObjectId, $product->productId, $rec->value, 1, $receiptRec->createdOn, 1, 'no', $listId);
        
        $rec->price = $price->price * $perPack;
        $rec->param = cat_Products::getVat($rec->productId, $receiptRec->valior);
        $rec->amount = $rec->price * $rec->quantity;
    }
    
    
    /**
     *  Намира последната продажба на даден продукт в текущата бележка
     *
     *  @param int $productId - ид на продукта
     *  @param int $receiptId - ид на бележката
     *  @param int $packId - ид на опаковката
     *
     *  @return mixed $rec/FALSE - Последния запис или FALSE ако няма
     */
    public function findSale($productId, $receiptId, $packId)
    {
        $query = $this->getQuery();
        $query->where(array('#productId = [#1#]', $productId));
        $query->where(array('#receiptId = [#1#]', $receiptId));
        if ($packId) {
            $query->where(array('#value = [#1#]', $packId));
        } else {
            $query->where('#value IS NULL');
        }
        
        $query->orderBy('#id', 'DESC');
        $query->limit(1);
        if ($rec = $query->fetch()) {
            
            return $rec;
        }
        
        return false;
    }
    
    
    /**
     * След като създадем елемент, ъпдейтваме Бележката
     */
    public static function on_AfterSave($mvc, &$id, $rec, $fieldsList = null)
    {
        Mode::setPermanent('lastAdded', $id);
        $mvc->Master->updateReceipt($rec->receiptId);
    }
    
    
    /**
     * Модификация на ролите, които могат да видят избраната тема
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'delete') && isset($rec->receiptId)) {
            $masterRec = pos_Receipts::fetch($rec->receiptId, 'revertId,state');
            
            if ($masterRec->state != 'draft') {
                $res = 'no_one';
            } else {
                
                // Ако редактираме/добавяме/изтриваме ред с продукт, проверяваме имали направено плащане
                if (!($action == 'delete' && !$rec->productId)) {
                    if ($masterRec->paid) {
                        $res = 'no_one';
                    }
                }
            }
        }
        
        if($action == 'load' && isset($rec)){
            $masterRec = pos_Receipts::fetch($rec->receiptId, 'revertId,state');
            if(empty($masterRec->revertId) || $masterRec->state != 'draft'){
                $res = 'no_one';
            }
        }
    }
    
    
    /**
     * Използва се от репортите за извличане на данни за продажбата
     *
     * @param int $receiptId - ид на бележка
     *
     * @return array $result - масив от всички плащания и продажби на бележката;
     */
    public static function fetchReportData($receiptId)
    {
        expect($masterRec = pos_Receipts::fetch($receiptId));
        $storeId = pos_Points::fetchField($masterRec->pointId, 'storeId');
        $caseId = pos_Points::fetchField($masterRec->pointId, 'caseId');
        
        $result = array();
        $query = static::getQuery();
        $query->EXT('contragentClsId', 'pos_Receipts', 'externalName=contragentClass,externalKey=receiptId');
        $query->EXT('contragentId', 'pos_Receipts', 'externalName=contragentObjectId,externalKey=receiptId');
        $query->where("#receiptId = {$receiptId}");
        $query->where("#action LIKE '%sale%' || #action LIKE '%payment%'");
        
        while ($rec = $query->fetch()) {
            $obj = new stdClass();
            if ($rec->productId) {
                $obj->action = 'sale';
                $obj->pack = ($rec->value) ?  $rec->value : null;
                $pInfo = cat_Products::getProductInfo($rec->productId);
                $obj->quantityInPack = ($pInfo->packagings[$obj->pack]) ? $pInfo->packagings[$obj->pack]->quantity : 1;
                
                $obj->value = $rec->productId;
                $obj->storeId = $storeId;
                $obj->param = $rec->param;
            } else {
                if (!$rec->amount) {
                    continue;
                }
               
                $obj->action = 'payment';
                list(, $obj->value) = explode('|', $rec->action);
                $obj->pack = null;
                $obj->caseId = $caseId;
                
                if($obj->value == -1){
                    $rec->amount -= $masterRec->change;
                }
            }
            $obj->contragentClassId = $rec->contragentClsId;
            $obj->contragentId = $rec->contragentId;
            $obj->quantity = $rec->quantity;
            $obj->amount = ($rec->amount) * (1 - $rec->discountPercent);
            $obj->date = $masterRec->createdOn;
            
            $result[] = $obj;
        }
        
        return $result;
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    protected static function on_AfterPrepareListToolbar($mvc, $data)
    {
        unset($data->toolbar->buttons['btnAdd']);
    }
    
    
    /**
     * Зареждане на артикулите от сторнираната бележка
     */
    public function act_Load()
    {
        $this->requireRightFor('load');
        
        expect($receiptId = Request::get('receiptId', 'int'));
        expect($receiptRec = pos_Receipts::fetch($receiptId));
        $this->requireRightFor('load', (object)array('receiptId' => $receiptId));
        $this->delete("#receiptId = {$receiptId}");
        
        $query = $this->getQuery();
        $query->where("#receiptId = {$receiptRec->revertId}");
        $query->orderBy('id', 'asc');
        
        while($rec = $query->fetch()){
            unset($rec->id);
            if(!empty($rec->amount)) {
                $rec->amount *= -1;
            }
            if(!empty($rec->quantity)) {
                $rec->quantity *= -1;
            }
            $rec->receiptId = $receiptId;
            $this->save($rec);
        }
        
        cls::get('pos_Receipts')->updateReceipt($receiptId);
        
        followRetUrl();
    }
}
