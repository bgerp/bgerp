<?php


/**
 * Мениджър за "Бележки за продажби"
 *
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
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
    public $listFields = 'id,productId,value,quantity,price,discountPercent,amount';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'discountPercent';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Modified,plg_Created';
        
     
    /**
     * Поле за забележките
     */
    public $notesFld = 'text';
    
    
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
        $this->FLD('text', 'varchar', 'caption=Пояснение,input=none');
        $this->FLD('batch', 'varchar', 'caption=Партида,width=7em,input=none');
        
        $this->setDbIndex('action');
        $this->setDbIndex('productId');
    }
    
    
    /**
     * Променяме рендирането на детайлите
     */
    public function renderReceiptDetail($data)
    {
        $tpl = new ET('');
        $blocksTpl = getTplFromFile('pos/tpl/terminal/ReceiptDetail.shtml');
        
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
     * Добавяне на плащане към бележка
     */
    public function act_makePayment()
    {
        $this->requireRightFor('add');
        expect($receiptId = Request::get('receiptId', 'int'));
        expect($receiptRec = pos_Receipts::fetch($receiptId));
        pos_Receipts::requireRightFor('pay', $receiptRec);
        $success = true;
        
        try{
            $type = Request::get('type', 'int');
            if($type != -1){
                expect(cond_Payments::fetch($type), 'Неразпознат метод на плащане');
            }
            
            $amount = Request::get('amount', 'varchar');
            $amount = core_Type::getByName('double')->fromVerbal($amount);
            expect($amount, 'Не е подадане сума за плащане');
            expect($amount > 0, 'Сумата трябва да е положителна');
            
            $diff = abs($receiptRec->paid - $receiptRec->total);
            
            $paidAmount = $amount;
            if ($type != -1) {
                $paidAmount = cond_Payments::toBaseCurrency($type, $amount, $receiptRec->valior);
                expect(!(!cond_Payments::returnsChange($type) && (string) abs($paidAmount) > (string) $diff), 'Платежния метод не позволява да се плати по-голяма сума от общата|*!');
            }
            
            if($receiptRec->revertId){
                $amount *= -1;
            }
            
            // Подготвяме записа на плащането
            $rec = (object)array('receiptId' => $receiptRec->id, 'action' => "payment|{$type}", 'amount' => $amount);
            
            if($this->save($rec)){
                $this->Master->logInAct('Направено плащане', $receiptRec->id);
            }
            
       } catch(core_exception_Expect $e){
           $dump = $e->dump;
           $dump1 = $dump[0];
           
           if (!Request::get('ajax_mode')) {
               throw new core_exception_Expect('', 'Изключение', $dump);
           } else {
               core_Statuses::newStatus($dump1, 'error');
               $success = false;
           }
       }
        
       return pos_Terminal::returnAjaxResponse($receiptId, null, $success, true);
    }
    
    
    /**
     * Екшън модифициращ бележката
     */
    public function act_updaterec()
    {
        $this->requireRightFor('edit');
        expect($receiptId = Request::get('receiptId', 'int'));
        expect($receiptRec = pos_Receipts::fetch($receiptId));
        $success = true;
       
        try{
            $id = Request::get('recId', 'int');
            $id = isset($id) ? $id : self::getLastRec($receiptId, 'sale')->id;
            expect($rec = self::fetch($id), 'Не е избран ред');
            $this->requireRightFor('edit', $rec);
           
            expect($operation = Request::get('action', 'enum(setquantity,setdiscount,settext,setprice,setbatch)'), 'Невалидна операция');
            $string = Request::get('string', 'varchar');
            expect(isset($string), 'Проблем при разчитане на операцията');
            if(isset($receiptRec->revertId) && $receiptRec->revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT && in_array($operation, array('setdiscount', 'setprice'))){
                expect(false, 'Невалидна операция');
            }
            
            if($operation == 'settext' || $operation == 'setprice'){
                $firstValue = trim($string);
            } else {
                $string = str::removeWhiteSpace(trim($string), " ");
                list($firstValue, $secondValue) = explode(" ", $string, 2);
            }
            
            if($operation != 'settext'){
                expect(empty($receiptRec->paid), 'Не може да се променя информацията, ако има направено плащане|*!');
            }
            
            switch($operation){
                case 'setquantity':
                    expect($quantity = core_Type::getByName('double')->fromVerbal($firstValue), 'Не е зададено количество');
                    $rec->quantity = $quantity;
                    $rec->amount = $rec->price * $rec->quantity;
                    
                    if(!empty($secondValue)){
                        expect($packagingId = cat_UoM::fetchBySinonim($secondValue)->id, 'Не е разпозната опаковка');
                        $packs = cat_Products::getPacks($rec->productId);
                        
                        expect(array_key_exists($packagingId, $packs), 'Опаковката/мярка не е налична за въпросния артикул');
                        $rec->value = $packagingId;
                    }
                    
                    if(isset($receiptRec->revertId)){
                        if($receiptRec->revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT){
                            $originProductRec = $this->findSale($rec->productId, $receiptRec->revertId, $rec->value);
                            expect(abs($rec->quantity) <= abs($originProductRec->quantity), "Количеството е по-голямо от продаденото|* " . core_Type::getByName('double(smartRound)')->toVerbal($originProductRec->quantity));
                        }
                        
                        $rec->quantity *= -1;
                    }
                    
                    $sucessMsg = 'Количеството на реда е променено|*!';
                    break;
               case 'setdiscount':
                   $discount = core_Type::getByName('percent')->fromVerbal($firstValue);
                   expect(isset($discount), 'Не е въведен процент отстъпка');
                   expect($discount >= 0 && $discount <= 1, 'Отстъпката трябва да е между 0% и 100%');
                   $rec->discountPercent = $discount;
                   $sucessMsg = 'Отстъпката на реда е променена|*!';
                   break;
               case 'setprice':
                   expect($price = core_Type::getByName('double')->fromVerbal($firstValue), 'Не е зададена цена');
                   $price /= 1 + $rec->param;
                   $rec->price = $price;
                   $sucessMsg = 'Цената на реда е променена|*!';
                   break;
               case 'settext':
                   $text = core_Type::getByName('text')->fromVerbal($firstValue);
                   expect(isset($text), 'Не е зададено пояснение');
                   $rec->text = (!empty($text)) ? $text : null;
                   $sucessMsg = 'Променено пояснение на реда|*!';
                   break;
               case 'setbatch':
                   $batchDef = batch_Defs::getBatchDef($rec->productId);
                   expect($batchDef, 'Артикулът няма партидност');
                   if(!empty($string)){
                       $batechErrorMsg = null;
                       if(!$batchDef->isValid($string, $rec->quantity, $batechErrorMsg)){
                           expect(false, $batechErrorMsg);
                       }
                       $rec->batch = $batchDef->normalize($string);
                   } else {
                       $rec->batch = null;
                   }
                   
                   break;
            }
            
            if($this->save($rec)){
                $this->Master->logInAct($sucessMsg, $receiptId);
            }
            
        } catch(core_exception_Expect $e){
            $dump = $e->dump;
            $dump1 = $dump[0];
            
            if (!Request::get('ajax_mode')) {
                throw new core_exception_Expect('', 'Изключение', $dump);
            } else {
                core_Statuses::newStatus($dump1, 'error');
                $success = false;
            }
        }
        
        return pos_Terminal::returnAjaxResponse($receiptId, $id, $success, true);
    }
    
    
    /**
     * Екшън добавящ продукт в бележката
     */
    public function act_addProduct()
    {
        $this->requireRightFor('add');
        expect($receiptId = Request::get('receiptId', 'int'));
        expect($receiptRec = pos_Receipts::fetch($receiptId));
        $this->requireRightFor('add', (object)array('receiptId' => $receiptId));
        $success = false;
        
        try{
            expect(empty($receiptRec->paid), 'Не може да се добави артикул, ако има направено плащане|*!');
            
            // Запис на продукта
            $rec = (object)array('receiptId' => $receiptId, 'action' => 'sale|code');
            $quantity = Request::get('quantity');
            if ($quantity = cls::get('type_Double')->fromVerbal($quantity)) {
                $rec->quantity = $quantity;
            } else {
                $rec->quantity = 1;
            }
            
            // Ако е зададено ид на продукта
            if ($productId = Request::get('productId', 'int')) {
                $rec->productId = $productId;
            }
            
            // Ако е зададен код на продукта
            if ($ean = Request::get('string')) {
                $matches = array();
                
                // Проверяваме дали въведения "код" дали е във формата '< число > * < код >',
                // ако да то приемаме числото преди '*' за количество а след '*' за код
                preg_match('/([0-9+\ ?]*[\.|\,]?[0-9]*\ *)(\ ?\* ?)([0-9a-zа-я\- _]*)/iu', $ean, $matches);
                
                // Ако има намерени к-во и код от регулярния израз
                if (!empty($matches[1]) && !empty($matches[3])) {
                    
                    // Ако има ид на продукт
                    if (isset($rec->productId)) {
                        $rec->quantity = cls::get('type_Double')->fromVerbal($matches[1] * $matches[3]);
                    } else {
                        $rec->quantity = cls::get('type_Double')->fromVerbal($matches[1]);
                        $rec->ean = $matches[3];
                    }
                    
                    // Ако има само лява част приемаме, че е количество
                } elseif (!empty($matches[1]) && empty($matches[3])) {
                    $rec->quantity = cls::get('type_Double')->fromVerbal($matches[1]);
                } else {
                    if (isset($rec->productId)) {
                        $rec->quantity = cls::get('type_Double')->fromVerbal($ean);
                    } else {
                        $rec->ean = $ean;
                    }
                }
            }
            
            expect(!empty($rec->productId) || !empty($rec->ean), 'Не е избран артикул|*!');
            
            if ($packId = Request::get('packId', 'int')) {
                expect(cat_UoM::fetch($packId), "Невалидна опаковка|*!");
                $rec->value = $packId;
            }
            
            // Намираме нужната информация за продукта
            $this->getProductInfo($rec);
            expect($rec->productId, 'Няма такъв продукт в системата, или той не е продаваем|*!');
            
            // Ако няма цена
            if (!$rec->price) {
                $createdOn = pos_Receipts::fetchField($rec->receiptId, 'createdOn');
                $createdOn = dt::mysql2verbal($createdOn, 'd.m.Y H:i');
                expect(false,  "Артикулът няма цена към|* <b>{$createdOn}</b>");
            }
            
            $revertId = pos_Receipts::fetchField($receiptId, 'revertId');
            if (!empty($revertId)) {
                if($revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT){
                    expect($originProductRec = $this->findSale($rec->productId, $revertId, $rec->value), 'Артикулът го няма в оригиналната бележка|*!');
                }
                $rec->quantity *= -1;
            }
            
            // Намираме дали този проект го има въведен
            $sameProduct = $this->findSale($rec->productId, $rec->receiptId, $rec->value);
            if ($sameProduct) {
                
                // Ако цената и опаковката му е същата като на текущия продукт,
                // не добавяме нов запис а ъпдейтваме стария
                $newQuantity = $rec->quantity + $sameProduct->quantity;
                $rec->quantity = $newQuantity;
                $rec->amount += $sameProduct->amount;
                $rec->id = $sameProduct->id;
            }
            
            $error = '';
            if (!pos_Receipts::checkQuantity($rec, $error)) {
                expect(false, $error);
            }
            expect(!(!empty($revertId) && ($revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT) && abs($originProductRec->quantity) < abs($rec->quantity)), "Количеството е по-голямо от продаденото|* " . core_Type::getByName('double(smartRound)')->toVerbal($originProductRec->quantity));
            $this->save($rec);
            $success = true;
            $this->Master->logInAct('Добавяне на артикул', $rec->receiptId);
            
        } catch(core_exception_Expect $e){
            $dump = $e->dump;
            $dump1 = $dump[0];
            reportException($e);
            if (!Request::get('ajax_mode')) {
                throw new core_exception_Expect('', 'Изключение', $dump);
            } else {
                core_Statuses::newStatus($dump1, 'error');
                $success = false;
            }
        }
       
        return pos_Terminal::returnAjaxResponse($receiptId, null, $success, true);
    }
    
    
    /**
     * Изтриване на запис от бележката
     */
    public function act_DeleteRec()
    {
        $this->requireRightFor('delete');
        expect($id = Request::get('recId', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('delete', $id);
        
        $this->delete($rec->id);
        $this->Master->logInAct('Изтриване на ред', $rec->receiptId);
        
        Mode::setPermanent("currentOperation{$rec->receiptId}", 'add');
        Mode::setPermanent("currentSearchString{$rec->receiptId}", null);
        
        return pos_Terminal::returnAjaxResponse($rec->receiptId, null, true, true);
    }
    
    
    /**
     * Подготвя детайла на бележката
     */
    public function prepareReceiptDetails($receiptId)
    {
        $res = new stdClass();
        $query = $this->getQuery();
        $query->where("#receiptId = '{$receiptId}'");
        $query->orderBy('modifiedOn,id', 'ASC');
        
        while ($rec = $query->fetch()) {
            $res->recs[$rec->id] = $rec;
            $res->rows[$rec->id] = $this->recToVerbal($rec);
        }
        
        return $res;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $receiptRec = $mvc->Master->fetch($rec->receiptId, 'createdOn,revertId,paid');
        $row->currency = acc_Periods::getBaseCurrencyCode($receiptRec->createdOn);
        $canDelete = ($mvc->haveRightFor('delete', $rec) && !Mode::is('printing'));
        
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
                $row->amount = ht::styleNumber($row->amount, $rec->amount);
                
                if ($fields['-list']) {
                    $row->productId = tr('Плащане') . ': ' . $row->actionValue;
                    unset($row->quantity, $row->value);
                }
                break;
        }
        
        // Ако може да изтриваме ред и не сме в режим принтиране
        if ($canDelete) {
            $delUrl = toUrl(array($mvc->className, 'deleteRec'), 'local');
            $row->DEL_BTN = ht::createElement('img', array('src' => sbf('img/16/deletered.png', ''),
                'class' => 'pos-del-btn', 'data-recId' => $rec->id,
                'title' => 'Изтриване на реда',
                'data-warning' => tr('|Наистина ли искате да изтриете реда|*?'),
                'data-url' => $delUrl));
        }
    }
    
    
    /**
     * Рендира информацията за направената продажба
     */
    public function renderSale($rec, &$row, $receiptDate, $fields = array())
    {
        $Varchar = cls::get('type_Varchar');
        $Double = core_Type::getByName('double(decimals=2)');
        $productRec = cat_Products::fetch($rec->productId, 'code,measureId');
        
        $price = $this->Master->getDisplayPrice($rec->price, $rec->param, $rec->discountPercent, pos_Receipts::fetchField($rec->receiptId, 'pointId'), $rec->quantity);
        $row->price = $Double->toVerbal($price);
        $row->amount = $Double->toVerbal($price * $rec->quantity);
        $row->amount = ht::styleNumber($row->amount, $price * $rec->quantity);
        if ($rec->discountPercent < 0) {
            $row->discountPercent = '+' . trim($row->discountPercent, '-');
        }
        
        if(core_Packs::isInstalled('batch')){
            if($BatchDef = batch_Defs::getBatchDef($rec->productId)){
                if(!empty($rec->batch)){
                    $row->batch = $BatchDef->toVerbal($rec->batch);
                } elseif(isset($fields['-list'])){
                    $row->batch = "<span class='quiet'>" . tr('Без партида') . "</span>";
                }
            }
        }
        
        $row->code = $Varchar->toVerbal($productRec->code);
        
        if ($rec->value) {
            $packaging = cat_UoM::getVerbal($rec->value, 'name');
            $packaging = str::getPlural($rec->quantity, $packaging, true);
            $row->value = tr($packaging);
            $packRec = cat_products_Packagings::getPack($rec->productId, $rec->value);
            $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
            
            if(isset($fields['-list'])){
                deals_Helper::getPackInfo($row->value, $rec->productId, $rec->value, $quantityInPack);
            } else {
                if ($packRec = cat_products_Packagings::getPack($rec->productId, $rec->value)) {
                    if (cat_UoM::fetchField($rec->value, 'showContents') == 'yes') {
                        $baseMeasureId = $productRec->measureId;
                        $quantityInPack = cat_UoM::round($baseMeasureId, $packRec->quantity);
                        $row->quantityInPack = core_Type::getByName('double(smartRound)')->toVerbal($quantityInPack);
                        $row->quantityInPack .= " " . tr(cat_UoM::getShortName($baseMeasureId));
                    }
                }
            }
        } else {
            $row->value = tr(cat_UoM::getTitleById($productRec->measureId));
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
            expect(cat_Products::fetchField($rec->productId));
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
            $basePackId = (isset($rec->value)) ? $rec->value : key(cat_Products::getPacks($product->productId));
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
     * Модификация на ролите, които могат да видят избраната тема
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'delete') && isset($rec->receiptId)) {
            $masterRec = pos_Receipts::fetch($rec->receiptId, 'revertId,state,paid');
            
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
            if(empty($masterRec->revertId) || $masterRec->state != 'draft' || $masterRec->revertId == pos_Receipts::DEFAULT_REVERT_RECEIPT){
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
        
        $this->Master->flushUpdateQueue($receiptId);
        $paid = $this->Master->fetchField($receiptId, 'paid', false);
        
        Mode::setPermanent("currentOperation{$receiptId}", (empty($paid)) ? 'add' : 'payment');
        $this->Master->logInAct('Зареждане на всичко от сторнираната бележка', $receiptId);
        
        followRetUrl();
    }
    
    
    /**
     * Кой е последно добавения ред
     * 
     * @param int $receiptId
     * @return int|null
     */
    public static function getLastRec($receiptId, $type = null)
    {
        $query = pos_ReceiptDetails::getQuery();
        $query->where("#receiptId = {$receiptId}");
        if(isset($type)){
            $query->where("#action LIKE '%{$type}%'");
        }
        
        $query->orderBy("modifiedOn", 'DESC');
        $query->limit(1);
        $rec = $query->fetch();
        
        return is_object($rec) ? $rec : null;
    }
    
    
    /**
     * Преди подготовката на полетата за листовия изглед
     */
    protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        if(core_Packs::isInstalled('batch')){
            arr::placeInAssocArray($data->listFields, array('batch' => 'Партида'), 'quantity');
        }
    }
}
