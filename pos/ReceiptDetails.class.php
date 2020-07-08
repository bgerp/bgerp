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
    public $listFields = 'id,productId,value,quantity,storeId,price,discountPercent,amount';
    
    
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
     * Плейсхолдър за клас за обновяване
     */
    private static $updatedOperationPlaceholderMap = array('setquantity' => 'quantityUpdated', 'settext' => 'textUpdated', 'setbatch' => 'batchUpdated', 'setprice' => 'priceUpdated', 'setstore' => 'storeUpdated', 'setdiscount' => 'discountUpdated');
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('receiptId', 'key(mvc=pos_Receipts)', 'caption=Бележка, input=hidden, silent');
        $this->FLD('action', 'varchar(32)', 'caption=Действие,width=7em;top:1px;position:relative');
        $this->FLD('param', 'varchar(32)', 'caption=Параметри,width=7em,input=none');
        $this->FLD('productId', 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Продукт,input=none');
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
        $this->FLD('quantity', 'double(smartRound)', 'caption=К-во,placeholder=К-во,width=4em');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума, input=none');
        $this->FLD('value', 'varchar(32)', 'caption=Мярка, input=hidden,smartCenter');
        $this->FLD('discountPercent', 'percent(min=0,max=1)', 'caption=Отстъпка,input=none');
        $this->FLD('text', 'varchar', 'caption=Пояснение,input=none');
        $this->FLD('batch', 'varchar', 'caption=Партида,input=none');
        $this->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Склад,input=none');
        $this->FLD('revertRecId', 'int', 'caption=Сторнира ред, input=none');
        
        $this->setDbIndex('action');
        $this->setDbIndex('productId');
        $this->setDbIndex('productId,receiptId');
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
       
       return pos_Terminal::returnAjaxResponse($receiptId, null, $success, true, true, true, 'add');
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
        $skip = false;
        $refreshResult = true;
        
        try{
            $id = Request::get('recId', 'int');
            $id = isset($id) ? $id : self::getLastRec($receiptId, 'sale')->id;
            expect($id, 'Не е избран ред');
            expect($rec = self::fetch($id), 'Не е избран ред');
            $this->requireRightFor('edit', $rec);
           
            expect($operation = Request::get('action', 'enum(setquantity,setdiscount,settext,setprice,setbatch,setstore)'), 'Невалидна операция');
            $string = Request::get('string', 'varchar');
            
            expect(isset($string), 'Проблем при разчитане на операцията');
            if(isset($receiptRec->revertId) && $receiptRec->revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT && in_array($operation, array('setdiscount', 'setprice'))){
                expect(false, 'Невалидна операция');
            }
            
            if($operation == 'settext' || $operation == 'setprice'|| $operation == 'setstore'){
                $firstValue = trim($string);
            } else {
                $string = str::removeWhiteSpace(trim($string), " ");
                list($firstValue, $secondValue) = explode(" ", $string, 2);
                $firstValue = trim($firstValue);
                $secondValue = trim($secondValue);
            }
            
            if($operation != 'settext'){
                expect(empty($receiptRec->paid), 'Не може да се променя информацията, ако има направено плащане|*!');
            }
            
            $productRec = cat_Products::fetch($rec->productId, 'canStore');
            
            switch($operation){
                case 'setquantity':
                    expect($quantity = core_Type::getByName('double')->fromVerbal(str_replace('*', '', $firstValue)), 'Не е зададено количество');
                    if(str::endsWith($firstValue, '*')){
                        if($quantity < 0){
                            $quantity = $rec->quantity + $quantity;
                            if($quantity == 0){
                                core_Statuses::newStatus('Редът беше изтрит защото количеството стана отрицателно|*!');
                                
                                return Request::forward(array('Ctr' => 'pos_ReceiptDetails', 'Act' => 'DeleteRec', 'id' => $rec->id));
                            } elseif($quantity < 0 && empty($receiptRec->revertId)){
                                expect(false, 'Количеството не може да стане отрицателно|*!');
                            }
                        }
                    } else {
                        expect($quantity > 0, 'Количеството трябва да е положително');
                    }
                    
                    $rec->quantity = $quantity;
                   
                    if(!empty($secondValue)){
                        expect($packagingId = cat_UoM::fetchBySinonim($secondValue)->id, 'Не е разпозната опаковка');
                        $packs = cat_Products::getPacks($rec->productId);
                        expect(array_key_exists($packagingId, $packs), 'Опаковката/мярка не е налична за въпросния артикул');
                        $rec->value = $packagingId;
                    
                        // Преизчисляване на цената на опаковката
                        $this->getProductInfo($rec);
                    }
                    $rec->amount = $rec->price * $rec->quantity;
                    
                    if(isset($receiptRec->revertId)){
                        if($receiptRec->revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT){
                            $originProductRec = $this->findSale($rec->productId, $receiptRec->revertId, $rec->value);
                            expect(abs($rec->quantity) <= abs($originProductRec->quantity), "Количеството е по-голямо от продаденото|*: " . core_Type::getByName('double(smartRound)')->toVerbal($originProductRec->quantity));
                        }
                        
                        $rec->quantity *= -1;
                    } else {
                        
                        // Проверка дали количеството е допустимо
                        $errorQuantity = null;
                        if (!pos_Receipts::checkQuantity($rec, $errorQuantity)) {
                            expect(false, $errorQuantity);
                        }
                    }
                    
                    $sucessMsg = 'Количеството на реда е променено|*!';
                    break;
               case 'setdiscount':
                   $setDiscounts = pos_Points::getSettings($receiptRec->pointId, 'setDiscounts');
                   expect($setDiscounts == 'yes', 'Задаването на отстъпки/надценки не е разрешено|*!');
                   $discount = core_Type::getByName('percent')->fromVerbal($firstValue);
                   
                   if(isset($discount)){
                       expect($discount >= -1 && $discount <= 1, 'Отстъпката трябва да е между -100% и 100%|*!');
                       if(strpos($string, '%')){
                           $discount *= -1;
                       }
                       
                       $rec->discountPercent = $discount;
                       $sucessMsg = 'Отстъпката на реда е променена|*!';
                   } else {
                       $skip = true;
                   }
                   
                   break;
               case 'setprice':
                   $setPrices = pos_Points::getSettings($receiptRec->pointId, 'setPrices');
                   expect($setPrices == 'yes', 'Ръчното задаване на цена не е разрешено|*!');
                   
                   if(!empty($firstValue)){
                       $firstValue = str_replace('*', '', $firstValue);
                       expect($price = core_Type::getByName('double')->fromVerbal($firstValue), 'Неразпозната цена');
                       $price /= 1 + $rec->param;
                       $rec->price = $price;
                       $sucessMsg = 'Цената на реда е променена|*!';
                   } else {
                       $skip = true;
                   }
                   
                   break;
               case 'settext':
                   $text = core_Type::getByName('text')->fromVerbal($firstValue);
                   $text = str::removeWhiteSpace(trim($text), ' ');
                   
                   $rec->text = (!empty($text)) ? $text : null;
                   $sucessMsg = 'Променено пояснение на реда|*!';
                   break;
               case 'setbatch':
                   expect(core_Packs::isInstalled('batch'), 'Пакета за партидности не е инсталиран');
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
                   
                   $foundRec = $this->findSale($rec->productId, $rec->receiptId, $rec->value, $rec->batch);
                   if(isset($foundRec->id) && $foundRec->id != $rec->id){
                       expect(false, 'Партидата е вече зададена на друг ред');
                   }
                   
                   // Проверка дали количеството е допустимо
                   $errorQuantity = null;
                   if (!pos_Receipts::checkQuantity($rec, $errorQuantity)) {
                       expect(false, $errorQuantity);
                   }
                   
                   break;
               case 'setstore':
                   if($productRec->canStore != 'yes'){
                       expect(false, "Не може да се зададе склад, защото артикула е услуга");
                   }
                   
                   $stores = pos_Points::getStores($receiptRec->pointId);
                   expect(in_array($firstValue, $stores), 'Невъзможен склад за избор');
                   $rec->storeId = $firstValue;
                   
                   // Проверка дали количеството е допустимо
                   $errorQuantity = null;
                   if (!pos_Receipts::checkQuantity($rec, $errorQuantity)) {
                       expect(false, $errorQuantity);
                   }
                   
                   break;
            }
            
            if($this->save($rec) && $skip !== true){
                $this->Master->logInAct($sucessMsg, $receiptId);
                Mode::setPermanent("currentSearchString{$receiptId}", null);
                Mode::setPermanent("lastEditedRow", array('id' => $rec->id, 'action' => $operation));
            }
            
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
        
        return pos_Terminal::returnAjaxResponse($receiptId, $id, $success, true, true, $refreshResult, 'edit');
    }
    
    
    /**
     * Диспечер на специалните символи
     */
    public function act_Dispatch()
    {
        $this->requireRightFor('edit');
        expect($receiptId = Request::get('receiptId', 'int'));
        $this->requireRightFor('edit', $receiptId);
        $string = Request::get('string', 'varchar');
        $recId = Request::get('recId', 'varchar');
       
        if(substr($string, 0, 1) == "%" || substr($string, -1, 1) == '%'){
            
            // Ако се съдържа "%" значи се задава отстъпка/надценка
            $res = Request::forward(array('Ctr' => 'pos_ReceiptDetails', 'Act' => 'updaterec', 'receiptId' => $receiptId, 'action' => 'setdiscount', 'recId' => $recId));
        } elseif(substr($string, 0, 1) == "*"){
           
            // Ако се започва с "*" значи се задава цена
            $res = Request::forward(array('Ctr' => 'pos_ReceiptDetails', 'Act' => 'updaterec', 'receiptId' => $receiptId, 'action' => 'setprice', 'recId' => $recId));
        } elseif(str::endsWith($string, '*')){
           
            // Ако завършва с "*" значи се задава количество
            $res = Request::forward(array('Ctr' => 'pos_ReceiptDetails', 'Act' => 'updaterec', 'receiptId' => $receiptId, 'action' => 'setquantity', 'recId' => $recId));
        } else {
            
            // Ако започва с "-" но няма "*" се приема че се вади едно от количеството
            if(substr($string, 0, 1) == "-" && strpos($string, '*') === false){
                $string = ltrim($string, "-");
                $string = "-1*{$string}";
            }
            
            $res = Request::forward(array('Ctr' => 'pos_ReceiptDetails', 'Act' => 'addproduct', 'receiptId' => $receiptId, 'string' => $string, 'recId' => $recId));
        }
       
        return $res;
    }
    
    
    /**
     * Екшън добавящ продукт в бележката
     */
    public function act_addProduct()
    {
        $this->requireRightFor('add');
        expect($receiptId = Request::get('receiptId', 'int'));
        expect($receiptRec = pos_Receipts::fetch($receiptId, 'paid,pointId,revertId'));
        
        $this->requireRightFor('add', (object)array('receiptId' => $receiptId));
        $success = false;
        
        $selectedRec = null;
        if($recId = request::get('recId', 'int')){
            $selectedRec = $this->fetch($recId);
        }
        
        try{
            expect(empty($receiptRec->paid), 'Не може да се добави артикул, ако има направено плащане|*!');
            $increment = false;
            
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
                $increment = true;
                $rec->productId = $productId;
            }
            
            // Ако е зададен код на продукта
            if ($ean = Request::get('string')) {
                $matches = array();
               
                // Проверяваме дали въведения "код" дали е във формата '< число > * < код >',
                // ако да то приемаме числото преди '*' за количество а след '*' за код
                preg_match('/([\-]?[0-9+\ ?]*[\.|\,]?[0-9]*\ *)(\ ?\* ?)([0-9a-zа-я\- _]*)/iu', $ean, $matches);

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
                    $increment = true;
                    if (isset($rec->productId)) {
                        $rec->quantity = cls::get('type_Double')->fromVerbal($ean);
                    } else {
                        $rec->ean = $ean;
                    }
                }
            }
            
            $sign = isset($receiptRec->revertId) ? -1 : 1;
            $rec->quantity *= $sign;
            
            expect(!empty($rec->productId) || !empty($rec->ean), 'Не е избран артикул|*!');
            if ($packId = Request::get('packId', 'int')) {
                expect(cat_UoM::fetchField($packId), "Невалидна опаковка|*!");
                $rec->value = $packId;
            }
            
            // Намираме нужната информация за продукта
            core_Debug::startTimer('getProductInfo');
            $this->getProductInfo($rec);
            
            core_Debug::stopTimer('getProductInfo');
            core_Debug::log('GET PRODUCT INFO END: ' . round(core_Debug::$timers['getProductInfo']->workingTime, 2));
            
            expect($rec->productId, 'Няма такъв продукт в системата|*!');
            expect($rec->notSellable !== true, 'Артикулът е спрян от продажба|*!');
            
            // Ако няма цена
            if (!$rec->price) {
                $createdOn = pos_Receipts::fetchField($rec->receiptId, 'createdOn');
                $createdOn = dt::mysql2verbal($createdOn, 'd.m.Y H:i');
                expect(false,  "Артикулът няма цена към|* <b>{$createdOn}</b>");
            }
            
            if (!empty($receiptRec->revertId)) {
                if($receiptRec->revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT){
                    expect($originProductRec = $this->findSale($rec->productId, $receiptRec->revertId, $rec->value), 'Артикулът го няма в оригиналната бележка|*!');
                }
            }
            
            // Проверка дали избраната мярка приема подаденото количество
            $errorQuantity = null;
            if(!deals_Helper::checkQuantity($rec->value, $rec->quantity, $errorQuantity)){
                expect(empty($errorQuantity), $errorQuantity);
            }
            
            // Ако селектирания ред е с партида, се приема че ще се добавя нов ред
            $defaultStoreId = static::getDefaultStoreId($receiptRec->pointId, $rec->productId, $rec->quantity, $rec->value);
            
            if(isset($defaultStoreId)){
                if(core_Packs::isInstalled('batch')){
                    $batchQuantities = batch_Items::getBatchQuantitiesInStore($rec->productId, $defaultStoreId);
                    if(countR($batchQuantities) != 1){
                        $rec->batch = key($batchQuantities);
                    }
                }
            }
            
            if(!empty($selectedRec->batch) && empty($rec->batch)){ 
                $selectedRec = null;
            }
            
            if($selectedRec->productId == $rec->productId){
                $rec->value = $selectedRec->value;
                $rec->batch = $selectedRec->batch;
            } else {
                $count = $this->count("#receiptId = {$rec->receiptId} && #productId = {$rec->productId}");
                expect($count <= 1, 'Не е избран конкретен ред|*!');
            }
            
            // Намираме дали този проект го има въведен
            $sameProduct = $this->findSale($rec->productId, $rec->receiptId, $rec->value, $rec->batch);
            if ($sameProduct) {
                
                // Ако текущо селектирания ред е избрания инкрементира се, ако не се задава ново количество
                $newQuantity = ($selectedRec->id == $sameProduct->id) ? $rec->quantity + $sameProduct->quantity : (($increment === true) ? ($rec->quantity + $sameProduct->quantity) : $rec->quantity);
                if($newQuantity <= 0 && !isset($receiptRec->revertId)){
                    core_Statuses::newStatus('Редът беше изтрит защото количеството стана отрицателно|*!');
                    
                    return Request::forward(array('Ctr' => 'pos_ReceiptDetails', 'Act' => 'DeleteRec', 'id' => $sameProduct->id));
                }
                
                $rec->quantity = $newQuantity;
                $rec->price = $sameProduct->price;
                $rec->storeId = $sameProduct->storeId;
                $rec->amount += $sameProduct->amount;
                $rec->id = $sameProduct->id;
                
                Mode::setPermanent("lastEditedRow", array('id' => $rec->id, 'action' => 'setquantity'));
            } elseif(empty($receiptRec->revertId)) {
                expect($rec->quantity >= 1, 'При добавяне количеството трябва да е положително');
            }
            
            if($rec->_canStore == 'yes'){
                $rec->storeId = isset($rec->storeId) ? $rec->storeId : $defaultStoreId;
                if(empty($rec->storeId)){
                    expect(false,  "Артикулът не е наличен в нито един склад свързан с POS-а");
                }
            }
            
            $error = '';
            if ($rec->_canStore == 'yes' && !pos_Receipts::checkQuantity($rec, $error)) {
                expect(false, $error);
            }
            expect(!(!empty($receiptRec->revertId) && ($receiptRec->revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT) && abs($originProductRec->quantity) < abs($rec->quantity)), "Количеството е по-голямо от продаденото|* " . core_Type::getByName('double(smartRound)')->toVerbal($originProductRec->quantity));
            
            
            $this->save($rec);
            $success = true;
            $this->Master->logInAct('Добавяне на артикул', $rec->receiptId);
            Mode::setPermanent("currentOperation{$rec->receiptId}", 'add');
            $selectedRecId = $rec;
            
        } catch(core_exception_Expect $e){
            $selectedRecId = null;
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
       
        if(isset($productId)){
            $clear = false;
        } else {
            $clear = true;
            Mode::setPermanent("currentSearchString{$rec->receiptId}", null);
        }
        
        return pos_Terminal::returnAjaxResponse($receiptId, $selectedRecId, $success, true, true, true, 'add', $clear);
    }
    
    
    /**
     * Изтриване на запис от бележката
     */
    public function act_DeleteRec()
    {
        $this->requireRightFor('delete');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('delete', $id);
        
        $this->delete($rec->id);
        $this->Master->logInAct('Изтриване на ред', $rec->receiptId);
        $paidByNow = pos_Receipts::fetchField($rec->receiptId, 'paid');
        $defaultOperation = (empty($paidByNow)) ? 'add' : 'payment';
        
        Mode::setPermanent("currentOperation{$rec->receiptId}", $defaultOperation);
        Mode::setPermanent("currentSearchString{$rec->receiptId}", null);
        $lastRecId = pos_ReceiptDetails::getLastRec($rec->receiptId)->id;
        
        return pos_Terminal::returnAjaxResponse($rec->receiptId, $lastRecId, true, true, true, true, 'delete');
    }
    
    
    /**
     * Подготвя детайла на бележката
     */
    public function prepareReceiptDetails($receiptId)
    {
        $res = new stdClass();
        $query = $this->getQuery();
        $query->where("#receiptId = '{$receiptId}'");
        $query->orderBy('id', 'ASC');
        
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
    }
    
    
    /**
     * Рендира информацията за направената продажба
     */
    public function renderSale($rec, &$row, $receiptDate, $fields = array())
    {
        $Varchar = cls::get('type_Varchar');
        $Double = core_Type::getByName('double(decimals=2)');
        $productRec = cat_Products::fetch($rec->productId, 'code,measureId');
        $defaultStoreId = pos_Points::fetchField(pos_Receipts::fetchField($rec->receiptId, 'pointId'), 'storeId');
        
        $price = $this->Master->getDisplayPrice($rec->price, $rec->param, $rec->discountPercent, pos_Receipts::fetchField($rec->receiptId, 'pointId'), $rec->quantity);
        $row->price = $Double->toVerbal($rec->price * (1 + $rec->param));
        $row->amount = $Double->toVerbal($price * $rec->quantity);
        $row->amount = ht::styleNumber($row->amount, $price * $rec->quantity);
        
        $lastEdited = Mode::get("lastEditedRow");
        if($rec->id == $lastEdited['id']){
            $operationPlaceholder = self::$updatedOperationPlaceholderMap[$lastEdited['action']];
            if($operationPlaceholder){
               $row->{$operationPlaceholder} = 'updatedDiv flash';
            }
        }
        
        if ($rec->discountPercent < 0) {
            $row->discountPercent = "<span class='surchargeText'>+" . trim($row->discountPercent, '-') . "</span>";
        } else {
            $row->discountPercent = "<span class='discountText'>-" . $row->discountPercent . "</span>";
        }
        
        if(cat_Products::fetchField($rec->productId, 'canSell') != 'yes'){
            $row->STOPPED_PRODUCT = tr("спрян");
        }
        
        if(core_Packs::isInstalled('batch')){
            if($BatchDef = batch_Defs::getBatchDef($rec->productId)){
                if(!empty($rec->batch)){
                    $row->batch = $BatchDef->toVerbal($rec->batch);
                } elseif(isset($fields['-list'])){
                    $row->batch = "<span class='quiet'>" . tr('Без партида') . "</span>";
                } else {
                    $batchesInStore = batch_Items::getBatchQuantitiesInStore($rec->productId, $rec->storeId, $receiptDate);
                    if(!count($batchesInStore)){
                        $row->CLASS .= 'noBatch';
                    }
                }
            } else {
                $row->CLASS .= 'noBatch';
            }
        } else {
            unset($row->batch);
        }
        
        $row->code = $Varchar->toVerbal($productRec->code);
        if ($rec->value) {
            $row->value = tr(cat_UoM::getSmartName($rec->value, $rec->quantity));
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
        
        $row->productId = ($fields['-list']) ? cat_Products::getHyperLink($rec->productId, true) :  mb_subStr(cat_Products::getTitleById($rec->productId), 0, 95);
    
        // Показване на склада, само ако е различен от дефолтния
        if(isset($fields['-list'])){
            $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        } elseif($rec->storeId == $defaultStoreId) {
            unset($row->storeId);
        }
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
            $rec->productId = null;
            return;
        }
        
        $productRec = cat_Products::fetch($product->productId, 'canSell,measureId,canStore');
        if ($productRec->canSell != 'yes') {
            $rec->notSellable = true;
            return;
        }
        
        if (!$product->packagingId) {
            $basePackId = (isset($rec->value)) ? $rec->value : key(cat_Products::getPacks($product->productId));
        } else {
            $basePackId = $product->packagingId;
        }
        
        $packRec = cat_products_Packagings::getPack($product->productId, $basePackId);
        $perPack = (is_object($packRec)) ? $packRec->quantity : 1;
        $rec->value = ($basePackId) ? $basePackId : $productRec->measureId;
        
        $rec->productId = $product->productId;
        $receiptRec = pos_Receipts::fetch($rec->receiptId, 'pointId,contragentClass,contragentObjectId,valior,createdOn');
        
        $listId = null;
        $defaultContragentId = pos_Points::defaultContragent($receiptRec->pointId);
        if($rec->contragentClass == crm_Persons::getClassId() && $defaultContragentId == $rec->contragentObjectId){
            $listId = pos_Points::getSettings($receiptRec->pointId, 'policyId');
        }
        
        $Policy = cls::get('price_ListToCustomers');
        $price = $Policy->getPriceInfo($receiptRec->contragentClass, $receiptRec->contragentObjectId, $product->productId, $rec->value, 1, $receiptRec->createdOn, 1, 'no', $listId);
        
        $rec->price = $price->price * $perPack;
        $rec->param = cat_Products::getVat($rec->productId, $receiptRec->valior);
        $rec->amount = $rec->price * $rec->quantity;
        $rec->_canStore = $productRec->canStore;
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
    public function findSale($productId, $receiptId, $packId, $batch = null)
    {
        $query = $this->getQuery();
        $query->where(array('#productId = [#1#]', $productId));
        $query->where(array('#receiptId = [#1#]', $receiptId));
        if ($packId) {
            $query->where(array('#value = [#1#]', $packId));
        } else {
            $query->where('#value IS NULL');
        }
        
        if(core_Packs::isInstalled('batch')){
            if(isset($batch)){
                $query->where("#batch = '{$batch}'");
            } else {
                $query->where("#batch IS NULL OR #batch = ''");
            }
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
            
            if(isset($rec->loadRecId)){
                if($mvc->fetchField("#receiptId = {$rec->receiptId} AND #revertRecId = {$rec->loadRecId}")){
                    $res = 'no_one';
                }
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
                $obj->storeId = $rec->storeId;
                $obj->param = $rec->param;
                $obj->batch = $rec->batch;
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
        $id = Request::get('loadRecId', 'int');
        
        $this->requireRightFor('load', (object)array('receiptId' => $receiptId, 'loadRecId' => $id));
        
        $query = $this->getQuery();
        $query->where("#receiptId = {$receiptRec->revertId}");
        if(isset($id)){
            $this->delete("#receiptId = {$receiptId} AND #revertRecId = {$id}");
            $query->where("#id = {$id}");
        } else {
            $this->delete("#receiptId = {$receiptId}");
        }
        $query->orderBy('id', 'asc');
        
        while($exRec = $query->fetch()){
            if(!empty($exRec->amount)) {
                $exRec->amount *= -1;
            }
            if(!empty($exRec->quantity)) {
                $exRec->quantity *= -1;
            }
            $exRec->receiptId = $receiptId;
            if(isset($id)){
                $exRec->revertRecId = $id;
            } else {
                $exRec->revertRecId = $exRec->id;
            }
            unset($exRec->id);
            $this->save($exRec);
        }
        
        $this->Master->flushUpdateQueue($receiptId);
        $paid = $this->Master->fetchField($receiptId, 'paid', false);
        
        Mode::setPermanent("currentOperation{$receiptId}", (empty($paid)) ? 'add' : 'payment');
        $this->Master->logInAct('Зареждане на всичко от сторнираната бележка', $receiptId);
        
        if(Request::get('ajax_mode')){
            return pos_Terminal::returnAjaxResponse($receiptId, $exRec->id, true, true, true, true, 'add');
        } else {
            followRetUrl();
        }
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
    
    
    /**
     * Връща дефолтния склад, от който ще се експедира артикула
     * това е склада с най-голямо количество от тези избрани в точката
     * 
     * @param int $pointId           - ид на точка
     * @param int $productId         - ид на артикул
     * @param double $quantity       - количество
     * @param int|null $packagingId  - ид на опаковка
     * @return NULL|int              - ид на дефолтния склад или null, ако няма такъв с налично количество
     */
    private static function getDefaultStoreId($pointId, $productId, $quantity, $packagingId = null)
    {
        // Извличане на всички налични количества в склада
        $stores = pos_Points::getStores($pointId);
        $quantityArr = array();
        array_walk($stores, function($storeId) use(&$quantityArr, $productId) {
            $quantityArr[$storeId] = pos_Stocks::getQuantityByStore($productId, $storeId);
        });
        
        // Кой е основния склад и какво количество е в него
        $firstStoreId = key($quantityArr);
        $quantityInDefaultStore = $quantityArr[$firstStoreId];
        
        // Ако е забранена продажбата на неналични артикули
        $notInStockChosen = pos_Setup::get('ALLOW_SALE_OF_PRODUCTS_NOT_IN_STOCK');
        if($notInStockChosen != 'yes'){
            
            // Изчисляване на нужното количество в основната мярка
            $quantityInPack = 1;
            if(isset($packagingId)){
                $packRec = cat_products_Packagings::getPack($productId, $packagingId);
                $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
            }
            $expectedQuantity = round($quantityInPack * $quantity, 2);
            
            // Ако в основния е налична, връща се той
            if($expectedQuantity <= $quantityInDefaultStore){
                
                return $firstStoreId;
            }
            
            // Ако не е налична в основния, връща се склада с най-голямо количество където е налична
            // ако няма се връща null
            unset($quantityArr[$firstStoreId]);
            arsort($quantityArr);
            $storeIdWithMostQuantity = key($quantityArr);
            $inStock = $quantityArr[$storeIdWithMostQuantity];
            
            return ($expectedQuantity <= $inStock) ? $storeIdWithMostQuantity : null;
        }
        
        // Връщане на склада с най-голямо к-во, ако може да се продават неналични артикули
        return $firstStoreId;
    }
    
    
    /**
     * Връща масив с последно използваните текстове за определен период ор време
     * Данните се взимат от постоянния кеш
     * 
     * @param number $months
     * @param boolean $clearCache - дали първо да се инвалидира кеша
     * 
     * @return array $textArr
     */
    public static function getMostUsedTexts($months = 24, $clearCache = false)
    {
        // Изтриване на кеша ако е нужно
        if($clearCache === true){
            core_Permanent::remove("pos_MostUsedReceiptText{$months}");
        }
    
        // Кои са последно използваните текстове за посочените месеци
        $textArr = core_Permanent::get("pos_MostUsedReceiptText{$months}");
        if (!isset($textArr)) {
            $textArr = array();
            $valiorFrom = dt::addMonths(-1 * $months, null, false);
            
            $query = pos_ReceiptDetails::getQuery();
            $query->EXT('valior', 'pos_Receipts', 'externalName=valior,externalKey=receiptId');
            $query->where("#text IS NOT NULL AND #text != '' AND #valior >= '{$valiorFrom}'");
            $query->show('text');
            
            $count = 0;
            while($rec = $query->fetch()){
                $normalizedText = str::removeWhiteSpace(trim($rec->text), ' ');
                $textArr[] = $normalizedText;
                $count++;
                if($count >= 50) continue;
            }
            
            natsort($textArr);
            $textArr = array_combine(array_values($textArr), array_values($textArr));
            
            core_Permanent::set("pos_MostUsedReceiptText{$months}", $textArr, 4320);
        }
        
        return $textArr;
    }
}
