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
    public $listFields = 'id,productId,value,quantity,storeId,price,discountPercent=Отстъпка->Ръчна,autoDiscount=Отстъпка->Авт.,amount';


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
        $this->FLD('productId', 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Артикул,input=none');
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
        $this->FLD('quantity', 'double(smartRound)', 'caption=К-во,placeholder=К-во,width=4em');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума, input=none');
        $this->FLD('value', 'varchar(32)', 'caption=Мярка, input=hidden,smartCenter');
        $this->FLD('discountPercent', 'percent(min=0,max=1)', 'caption=Отстъпка,input=none');
        $this->FLD('autoDiscount', 'percent(min=0,max=1)', 'input=none');
        $this->FLD('inputDiscount', 'percent(min=0,max=1)', 'caption=Ръчна отстъпка,input=none');
        $this->FLD('text', 'varchar', 'caption=Пояснение,input=none');
        $this->FLD('batch', 'varchar', 'caption=Партида,input=none');
        $this->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Склад,input=none');
        $this->FLD('revertRecId', 'int', 'caption=Сторнира ред, input=none');
        $this->FLD('transferedIn', 'int', 'caption=Прехвърлена в, input=none');

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
        $param = Request::get('param', 'varchar');
        $success = true;
        $rec = null;
        $autoFiscPrintIfPossible = false;
        $amount = Request::get('amount', 'varchar');
        $amount = empty($amount) ? 0 : $amount;

        if(pos_Receipts::haveRightFor('setvoucher', $receiptRec)){
            $voucherInfo = voucher_Cards::getByNumber($amount);
            if($voucherInfo['error']){
                core_Statuses::newStatus($voucherInfo['error'], 'error');
                $success = false;
            } elseif(isset($voucherInfo['id'])){
                $forwardUrl = array('Ctr' =>'pos_Receipts', 'Act' => 'setvoucher', 'id' => $receiptRec->id, 'ajax_mode' => 1, 'voucherId' => $voucherInfo['id']);

                return core_Request::forward($forwardUrl);
            }
        }

        if($success){
            try{
                if(core_Packs::isInstalled('voucher')) {

                    if (!isset($receiptRec->revertId)) {
                        $productArr = arr::extractValuesFromArray(pos_Receipts::getProducts($receiptRec->id), 'productId');
                        $errorStartStr = 'Не може да платите, докато има артикули изискващи препоръчител и няма такъв';
                        if ($error = voucher_Cards::getErrorForVoucherAndProducts($receiptRec->voucherId, $productArr, $errorStartStr)) {
                            expect(false, $error);
                        }
                    }
                }

                expect(!(abs($receiptRec->paid) >= abs($receiptRec->total) && $receiptRec->total != 0), 'Вече е платено достатъчно|*!');

                if(!pos_Receipts::haveRightFor('pay', $receiptRec)){
                    expect(false, 'Не може да се добави друго плащане');
                }
                $type = Request::get('type', 'int');
                if($type != -1){
                    expect(cond_Payments::fetch($type), 'Неразпознат метод на плащане');
                }

                $amount = core_Type::getByName('double')->fromVerbal($amount);
                $paymentCount = pos_ReceiptDetails::count("#receiptId = {$receiptRec->id} AND #action LIKE '%payment%'");
                $countProducts = pos_ReceiptDetails::count("#receiptId = {$receiptRec->id} AND #action LIKE '%sale%'");
                if($countProducts && $receiptRec->total != 0){
                    expect($amount, 'Невалидна сума за плащане|*!');
                    expect($amount > 0, 'Сумата трябва да е положителна');
                } else {
                    expect(!$paymentCount, 'Има вече направено плащане|*!');
                    expect($type == -1, 'На бележките с нулева сума е позволено само плащане в брой|*!');
                    expect($amount == 0, 'Не може да платите по-голяма сума|*!');
                }

                $diff = abs($receiptRec->paid - $receiptRec->total);

                if ($type != -1) {
                    $paidAmount = cond_Payments::toBaseCurrency($type, $amount, $receiptRec->valior);
                    expect(!(!cond_Payments::returnsChange($type) && (string) abs($paidAmount) > (string) $diff), 'Платежния метод не позволява да се плати по-голяма сума от общата|*!');
                }

                if($receiptRec->revertId){
                    $amount *= -1;
                }

                // Подготвяме записа на плащането
                $rec = (object)array('receiptId' => $receiptRec->id, 'action' => "payment|{$type}", 'amount' => $amount);

                if(!empty($param)){
                    $cardPaymentId = cond_Setup::get('CARD_PAYMENT_METHOD_ID');
                    if($type == $cardPaymentId){
                        $rec->param = $param;
                    }
                }

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

            if($success && in_array($param, array('manual', 'card'))){
                $autoFiscPrintIfPossible = true;
            }
        }

        return pos_Terminal::returnAjaxResponse($receiptId, $rec, $success, true, true, true, 'add', false, true, null, $autoFiscPrintIfPossible);
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
                    $firstChar = substr($firstValue, 0, 1);

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
                        if($firstChar == '+'){
                            expect($quantity > 0, 'Количеството трябва да е положително');
                            $quantity = $rec->quantity + $quantity;
                        } elseif($firstChar == '-'){
                            $quantity = $rec->quantity + $quantity;
                            if($quantity <= 0){
                                core_Statuses::newStatus('Редът беше изтрит защото количеството стана отрицателно|*!');

                                return Request::forward(array('Ctr' => 'pos_ReceiptDetails', 'Act' => 'DeleteRec', 'id' => $rec->id));
                            }
                        }
                    }

                    $errorQuantity = null;
                    if(!deals_Helper::checkQuantity($rec->value, $quantity, $errorQuantity)){
                        expect(empty($errorQuantity), $errorQuantity);
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
                        $errorQuantity = $warningQuantity = null;
                        if (!pos_Receipts::checkQuantity($rec, $errorQuantity, $warningQuantity)) {
                            expect(false, $errorQuantity);
                        }

                        if(!empty($warningQuantity)){
                            core_Statuses::newStatus($warningQuantity, 'warning');
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
                       if($discount != 0){
                           if(strpos($string, '%') === 0){
                               $discount = -1 * $discount;
                            }
                       } else {
                           $discount = null;
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
                       $rec->amount = $rec->price * $rec->quantity;
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
                   $errorQuantity = $warningQuantity = null;
                   if (!pos_Receipts::checkQuantity($rec, $errorQuantity, $warningQuantity)) {
                       expect(false, $errorQuantity);
                   }

                   if(!empty($warningQuantity)){
                       core_Statuses::newStatus($warningQuantity, 'warning');
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
                   $errorQuantity = $warningQuantity = null;
                   if (!pos_Receipts::checkQuantity($rec, $errorQuantity, $warningQuantity)) {
                       expect(false, $errorQuantity);
                   }

                   if(!empty($warningQuantity)){
                       core_Statuses::newStatus($warningQuantity, 'warning');
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

        $firstChar = substr($string, 0, 1);
        $lastChar = substr($string, -1, 1);
        if($firstChar == "%" || $lastChar == '%'){

            // Ако се съдържа "%" значи се задава отстъпка/надценка
            $res = Request::forward(array('Ctr' => 'pos_ReceiptDetails', 'Act' => 'updaterec', 'receiptId' => $receiptId, 'action' => 'setdiscount', 'recId' => $recId));
        } elseif($firstChar == "*"){

            // Ако се започва с "*" значи се задава цена
            $res = Request::forward(array('Ctr' => 'pos_ReceiptDetails', 'Act' => 'updaterec', 'receiptId' => $receiptId, 'action' => 'setprice', 'recId' => $recId));
        } elseif(str::endsWith($string, '*') || $firstChar == "+" || $firstChar == "-"){

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
     * Диспечер на контрагентските операции
     */
    public function act_dispatchContragentSearch()
    {
        $this->requireRightFor('edit');
        expect($receiptId = Request::get('receiptId', 'int'));
        $receiptRec = pos_Receipts::fetch($receiptId);
        $this->requireRightFor('edit', $receiptId);
        $string = Request::get('string', 'varchar');

        $forwardUrl = array('Ctr' =>'pos_Terminal', 'Act' =>'displayOperation', 'search' => $string, 'operation' => 'contragent', 'receiptId' => $receiptId, 'refreshPanel' => 'true');

        // Ако е засечена клиентска карта - редирект към избора на контрагента ѝ
        if(pos_Receipts::haveRightFor('setcontragent', $receiptRec)){
            $cardInfo = crm_ext_Cards::getInfo($string);
            if($cardInfo['status'] == crm_ext_Cards::STATUS_ACTIVE){
                $redirectToNotEmptyReceiptResponse = $this->getRedirectToNotEmptyReceiptAjaxResponse($receiptRec, $cardInfo['contragentClassId'], $cardInfo['contragentId']);
                if(is_array($redirectToNotEmptyReceiptResponse)) return $redirectToNotEmptyReceiptResponse;

                $forwardUrl = array('Ctr' =>'pos_Receipts', 'Act' => 'setcontragent', 'id' => $receiptId, 'ajax_mode' =>1,'contragentClassId' => $cardInfo['contragentClassId'], 'contragentId' => $cardInfo['contragentId'], 'autoSelect' => true);
            } if($cardInfo['status'] == crm_ext_Cards::STATUS_NOT_ACTIVE){
                core_Statuses::newStatus("Клиентската карта е неактивна|*!", 'warning');
            }
        }

        return core_Request::forward($forwardUrl);
    }


    /**
     * Екшън добавящ продукт в бележката
     */
    public function act_addProduct()
    {
        core_Debug::startTimer('ADD_PRODUCT');
        $this->requireRightFor('add');
        expect($receiptId = Request::get('receiptId', 'int'));
        expect($receiptRec = pos_Receipts::fetch($receiptId));
        $this->requireRightFor('add', (object)array('receiptId' => $receiptId));

        $selectedRec = null;
        if($recId = request::get('recId', 'int')){
            $selectedRec = $this->fetch($recId);
        }

        $refreshHeader = false;
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
                preg_match('/(\-?[0-9+\ ?]*[\.|\,]?[0-9]*\ *)(\ ?\* ?)([0-9a-zа-я\- _\/\.]*)/iu', $ean, $matches);

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
            core_Debug::startTimer('ADD_PRODUCT_GET_PRODUCT_INFO');
            $this->getProductInfo($rec);
            core_Debug::stopTimer('ADD_PRODUCT_GET_PRODUCT_INFO');

            if($rec->ean && empty($rec->productId)){
                $operation = Mode::get("currentOperation{$rec->receiptId}");
                $forwardUrl = array('Ctr' =>'pos_Terminal', 'Act' =>'displayOperation', 'search' => $rec->ean, 'receiptId' => $receiptId, 'operation' => $operation, 'refreshPanel' => 'no');

                $check4Voucher = true;
                if(pos_Receipts::haveRightFor('setcontragent', $receiptRec)){
                    $cardInfo = crm_ext_Cards::getInfo($rec->ean);
                    if($cardInfo['status'] == crm_ext_Cards::STATUS_ACTIVE){
                        $redirectToNotEmptyReceiptResponse = $this->getRedirectToNotEmptyReceiptAjaxResponse($receiptRec, $cardInfo['contragentClassId'], $cardInfo['contragentId']);
                        if(is_array($redirectToNotEmptyReceiptResponse)) return $redirectToNotEmptyReceiptResponse;
                        $check4Voucher = false;
                        $forwardUrl = array('Ctr' =>'pos_Receipts', 'Act' => 'setcontragent', 'id' => $rec->receiptId, 'ajax_mode' => 1,'contragentClassId' => $cardInfo['contragentClassId'], 'contragentId' => $cardInfo['contragentId'], 'autoSelect' => true);
                    } elseif($cardInfo['status'] == crm_ext_Cards::STATUS_NOT_ACTIVE){
                        $check4Voucher = false;
                        core_Statuses::newStatus("Клиентската карта е неактивна|*!", 'warning');
                    }
                }

                // Проверка дали търсения стринг е неизползван ваучер
                if($check4Voucher && core_Packs::isInstalled('voucher')){
                    if(pos_Receipts::haveRightFor('setvoucher', $receiptRec)){
                        $voucherInfo = voucher_Cards::getByNumber($rec->ean);
                        if($voucherInfo['error']){
                            core_Statuses::newStatus($voucherInfo['error'], 'error');
                        } elseif(isset($voucherInfo['id'])){
                            $forwardUrl = array('Ctr' =>'pos_Receipts', 'Act' => 'setvoucher', 'id' => $rec->receiptId, 'ajax_mode' => 1, 'voucherId' => $voucherInfo['id']);
                        }
                    }
                }

                return core_Request::forward($forwardUrl);
            }

            expect($rec->productId, 'Няма такъв продукт в системата|*!', $rec);
            expect($rec->notSellable !== true, 'Артикулът е спрян от продажба|*!');

            // Ако няма цена
            if (!$rec->price) {
                $now = dt::mysql2verbal(dt::now(), 'd.m.Y H:i');
                expect(false,  "Артикулът няма цена към|* <b>{$now}</b>");
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

            if(core_Packs::isInstalled('batch')){
                if(isset($defaultStoreId)){
                    $batchQuantities = batch_Items::getBatchQuantitiesInStore($rec->productId, $defaultStoreId, null, null, array(), false, null, true);
                    $rec->batch = key($batchQuantities);
                }

                // Ако артикулът е с задължителна партидност, но няма налична така да се даде грешка
                if($batchDef = batch_Defs::getBatchDef($rec->productId)){
                    if(empty($rec->batch)){
                        $alwaysRequire = $batchDef->getField('alwaysRequire');
                        expect($alwaysRequire != 'yes', "Артикулът е със задължителна партидност, но няма налични|*!");
                    }
                }
            }

            if((!empty($selectedRec->batch) && empty($rec->batch))){
                $selectedRec = null;
            }

            $separateInPos = cat_Products::getParams($rec->productId, 'separateInPos');
            if($selectedRec->productId == $rec->productId && $selectedRec->value == $rec->value && $selectedRec->batch == $rec->batch){
                $rec->value = $selectedRec->value;
                $rec->batch = $selectedRec->batch;
            } else {
                if($separateInPos != 'yes'){
                    $count = $this->count("#receiptId = {$rec->receiptId} && #productId = {$rec->productId} AND #value = {$rec->value}");
                    expect($count <= 1, 'Не е избран конкретен ред|*!');
                }
            }

            // Намираме дали този проект го има въведен
            if($separateInPos != 'yes'){
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
                }
            }

            if(empty($receiptRec->revertId)) {
                expect($rec->quantity > 0, 'При добавяне количеството трябва да е положително');
            }

            if($rec->_canStore == 'yes'){
                $rec->storeId = $rec->storeId ?? $defaultStoreId;
                if(empty($rec->storeId)){
                    $pName = cat_Products::getTitleById($rec->productId);
                    expect(false,  "|*{$pName}: |не е наличен в нито един склад свързан с POS-а|*");
                }
            }

            $error = $warningQuantity = null;
            if ($rec->_canStore == 'yes') {
                $instantBomRec = cat_Products::getLastActiveBom($rec->productId, 'instant');
                if(!$instantBomRec){
                    if(!pos_Receipts::checkQuantity($rec, $error, $warningQuantity)){
                        expect(false, $error);
                    }
                }
            }

            if(!empty($warningQuantity)){
                core_Statuses::newStatus($warningQuantity, 'warning');
            }

            expect(!(!empty($receiptRec->revertId) && ($receiptRec->revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT) && abs($originProductRec->quantity) < abs($rec->quantity)), "Количеството е по-голямо от продаденото|* " . core_Type::getByName('double(smartRound)')->toVerbal($originProductRec->quantity));
            $pointRec = pos_Points::getSettings($receiptRec->pointId);

            if($pointRec->chargeVat == 'yes'){
                $rec->param = cat_Products::getVat($rec->productId, dt::now(), $pointRec->vatExceptionId);
            } else {
                $rec->param = 0;
            }

            $productsByNow = pos_ReceiptDetails::count("#receiptId = {$rec->receiptId}");
            $this->save($rec);
            $success = true;
            $this->Master->logInAct('Добавяне на артикул', $rec->receiptId);
            Mode::setPermanent("currentOperation{$rec->receiptId}", 'add');
            $selectedRecId = $rec;
            if(empty($productsByNow)){
                $receiptRec->createdBy = core_Users::getCurrent();
                $receiptRec->createdOn = dt::now();
                $receiptRec->valior = dt::today();
                cls::get('pos_Receipts')->save_($receiptRec, 'createdBy,createdOn,valior');
                $refreshHeader = true;
            }
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

        $string = Mode::get("currentSearchString{$rec->receiptId}");
        $refreshResult = !empty($string);

        Mode::setPermanent("productAdded{$rec->receiptId}", $rec->productId);
        Mode::setPermanent("currentSearchString{$rec->receiptId}", null);

        core_Debug::stopTimer('ADD_PRODUCT');
        core_Debug::log("END ADD_PRODUCT " . round(core_Debug::$timers["ADD_PRODUCT"]->workingTime, 6));

        core_Debug::startTimer('ADD_PRODUCT_RESULT');
        $res = pos_Terminal::returnAjaxResponse($receiptId, $selectedRecId, $success, true, true, $refreshResult, 'add', $refreshHeader);
        core_Debug::stopTimer('ADD_PRODUCT_RESULT');
        core_Debug::log("END ADD_PRODUCT_RESULT " . round(core_Debug::$timers["ADD_PRODUCT_RESULT"]->workingTime, 6));

        return $res;
    }


    /**
     * Помощна ф-я редиректваща към единствената започната бележка на клиента
     *
     * @param stdClass $receiptRec
     * @param int $contragentClassId
     * @param int $contragentId
     * @param int|null $userId
     * @return array|null
     */
    private function getRedirectToNotEmptyReceiptAjaxResponse($receiptRec, $contragentClassId, $contragentId, $userId = null)
    {
        if(pos_ReceiptDetails::count("#receiptId = {$receiptRec->id}")) return null;
        if(pos_Receipts::count("#contragentClass = {$contragentClassId} AND #contragentObjectId = {$contragentId} AND #state = 'draft'") != 1) return null;
        $existingId = pos_Receipts::fetchField("#contragentClass = {$contragentClassId} AND #contragentObjectId = {$contragentId} AND #state = 'draft'");
        if(!pos_Receipts::haveRightFor('terminal', $existingId, $userId)) return null;

        $resObj = new stdClass();
        $resObj->func = 'redirect';
        $resObj->arg = array('url' => toUrl(array('pos_Terminal', 'open', 'receiptId' => $existingId)));
        $res[] = $resObj;
        core_Statuses::newStatus("Отворена е последната чернова бележка на клиента|*!");

        $hitTime = Request::get('hitTime', 'int');
        $idleTime = Request::get('idleTime', 'int');
        $statusData = status_Messages::getStatusesData($hitTime, $idleTime);

        return array_merge($res, (array) $statusData);
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

        if(strpos($rec->action, 'payment') !== false && $rec->param == 'card'){
            $this->logWrite("Изтриване на потвърдено плащане", $rec->receiptId);
        }
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
        $receiptRec = $mvc->Master->fetch($rec->receiptId);
        $row->currency = acc_Periods::getBaseCurrencyCode($receiptRec->createdOn);

        $action = $mvc->getAction($rec->action);
        switch ($action->type) {
            case 'sale':
                $mvc->renderSale($rec, $row, $receiptRec, $fields);
                if ($fields['-list']) {
                    $row->productId = cat_Products::getHyperlink($rec->productId, true);
                }


                break;
            case 'payment':
                $row->actionValue = ($action->value != -1) ? cond_Payments::getTitleById($action->value) : tr('В брой');
                $row->paymentCaption = (empty($receiptRec->revertId)) ? tr('Плащане') : tr('Връщане');
                $row->amount = ht::styleNumber($row->amount, $rec->amount);

                $cardPaymentId = cond_Setup::get('CARD_PAYMENT_METHOD_ID');
                if($action->value == $cardPaymentId){
                    if(!empty($rec->param)){
                        $paramVal = ($rec->param == 'card') ? tr('Потв.') : tr('Ръчно потв.');
                        $row->actionValue .= " [{$paramVal}]";
                    }
                }

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
    public function renderSale($rec, &$row, $receiptRec, $fields = array())
    {
        $receiptDate = $receiptRec->valior;
        $Varchar = cls::get('type_Varchar');
        $Double = core_Type::getByName('double(decimals=2)');
        $productRec = cat_Products::fetch($rec->productId, 'code,measureId');
        $defaultStoreId = pos_Points::fetchField(pos_Receipts::fetchField($rec->receiptId, 'pointId'), 'storeId');

        $discountPercent = $rec->discountPercent;
        if($receiptRec->state == 'draft'){
            if(isset($discountPercent)){
                if(isset($rec->autoDiscount)){
                    $discountPercent = round((1 - (1 - $rec->discountPercent) * (1 - $rec->autoDiscount)), 8);
                }
            } elseif(isset($rec->autoDiscount)) {
                $discountPercent = $rec->autoDiscount;
            }
        }

        $price = $this->Master->getDisplayPrice($rec->price, $rec->param, $discountPercent, pos_Receipts::fetchField($rec->receiptId, 'pointId'), $rec->quantity);
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

        if(!$fields['-list']){
            $row->discountPercent = core_Type::getByName('percent')->toVerbal($discountPercent);
            if ($discountPercent < 0) {
                $row->discountPercent = "<span class='surchargeText'>+" . trim($row->discountPercent, '-') . "</span>";
            } else {
                $row->discountPercent = "<span class='discountText'>-" . $row->discountPercent . "</span>";
            }
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
                    if(!countR($batchesInStore)){
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
        if ($discountPercent == 0) {
            unset($row->discountPercent);
        }

        Mode::push('text', 'xhtml');
        $row->productId = ($fields['-list']) ? cat_Products::getHyperLink($rec->productId, true) :  cat_Products::getAutoProductDesc($rec->productId, null, 'short', 'public', core_Lg::getCurrent(), null, true, 95);
        Mode::pop('text');

        // Показване на склада, само ако е различен от дефолтния
        if(isset($fields['-list'])){
            if(isset($rec->storeId)){
                $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
            }
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
        expect(countR($actionArr) == 2, 'Стрингът не е в правилен формат');

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
     * @throws core_exception_Expect
     */
    public function getProductInfo(&$rec)
    {
        $product = null;
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

        $productRec = cat_Products::fetch($product->productId, 'canSell,measureId,canStore,state');
        if ($productRec->canSell != 'yes' || $productRec->state != 'active') {
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
        $receiptRec = pos_Receipts::fetch($rec->receiptId);

        $discountPolicyId = pos_Points::getSettings($receiptRec->pointId, 'discountPolicyId');
        $posPolicyId = pos_Points::getSettings($receiptRec->pointId, 'policyId');

        if(!empty($receiptRec->policyId)){
            $policy1 = $receiptRec->policyId;
            $policy2 = pos_Receipts::isForDefaultContragent($receiptRec) ? $posPolicyId : price_ListToCustomers::getListForCustomer($receiptRec->contragentClass, $receiptRec->contragentObjectId);
        } else {
            $policy1 = $posPolicyId;
            $policy2 = pos_Receipts::isForDefaultContragent($receiptRec) ? null : price_ListToCustomers::getListForCustomer($receiptRec->contragentClass, $receiptRec->contragentObjectId);
        }
        $price = static::getLowerPriceObj($policy1, $policy2, $product->productId, $rec->value, 1, dt::now(), $discountPolicyId);

        $rec->discountPercent = $price->discount;
        $rec->price = $price->price * $perPack;
        $rec->amount = $rec->price * $rec->quantity;
        $rec->_canStore = $productRec->canStore;
    }


    /**
     *  Намира последната продажба на даден продукт в текущата бележка
     *
     *  @param int $productId - ид на продукта
     *  @param int $receiptId - ид на бележката
     *  @param int $packId - ид на опаковката
     *  @param string $batch - партида
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
                $query->where(array("#batch = '[#1#]'", $batch));
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
            }
        }

        if($action == 'load' && isset($rec)){
            $masterRec = pos_Receipts::fetch($rec->receiptId, 'revertId,state,total');
            if(empty($masterRec->revertId) || $masterRec->state != 'draft' || $masterRec->revertId == pos_Receipts::DEFAULT_REVERT_RECEIPT){
                $res = 'no_one';
            }

            if(isset($rec->loadRecId)){
                if($mvc->fetchField("#receiptId = {$rec->receiptId} AND #revertRecId = {$rec->loadRecId}")){
                    $res = 'no_one';
                }
                $loadRec = $mvc->fetch($rec->loadRecId);
                if(strpos($loadRec->action, 'payment') !== false){
                    if(empty($masterRec->total)){
                        $res = 'no_one';
                    }
                }
            }
        }

        if ($action == 'delete' && isset($rec->receiptId)) {
            if(strpos($rec->action, 'payment') !== false){
                if($rec->param == 'card'){
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
        $query->EXT('revertId', 'pos_Receipts', 'externalName=revertId,externalKey=receiptId');
        $query->EXT('contragentClsId', 'pos_Receipts', 'externalName=contragentClass,externalKey=receiptId');
        $query->EXT('contragentId', 'pos_Receipts', 'externalName=contragentObjectId,externalKey=receiptId');
        $query->EXT('waitingBy', 'pos_Receipts', 'externalName=waitingBy,externalKey=receiptId');
        $query->EXT('receiptCreatedBy', 'pos_Receipts', 'externalName=createdBy,externalKey=receiptId');
        $query->where("#receiptId = {$receiptId}");
        $query->where("#action LIKE '%sale%' || #action LIKE '%payment%'");

        while ($rec = $query->fetch()) {
            $sign = isset($rec->revertId) ? -1 : 1;
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
                if($rec->amount > 0){
                    $rec->amount = $sign * ($rec->amount);
                }
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

            setIfNot($obj->userId, $rec->waitingBy, $rec->receiptCreatedBy);
            $obj->contragentClassId = $rec->contragentClsId;
            $obj->contragentId = $rec->contragentId;
            $obj->quantity = $rec->quantity;
            $obj->amount = $rec->amount * (1 - $rec->discountPercent);
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
            $query->orderBy('id', 'asc');
        }
        $recs = $query->fetchAll();
        foreach ($recs as $exRec) {
            // Заредените плащания за сторниране ще са само в брой
            if(strpos($exRec->action, 'payment') !== false){
                $exRec->action = "payment|-1";
                if(isset($id)){
                    $exRec->amount = min($exRec->amount, abs($receiptRec->total - $receiptRec->paid));
                }
            }

            $exRec->discountPercent = $exRec->inputDiscount;
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

        Mode::setPermanent("currentOperation{$receiptId}", (!isset($paid)) ? 'add' : 'payment');
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
            $quantityArr[$storeId] = store_Products::getQuantities($productId, $storeId)->quantity;
        });

        // Изчисляване на нужното количество в основната мярка
        $quantityInPack = 1;
        if(isset($packagingId)){
            $packRec = cat_products_Packagings::getPack($productId, $packagingId);
            $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
        }
        $expectedQuantity = round($quantityInPack * $quantity, 2);
        $firstStoreId = key($quantityArr);

        // Ако к-то е налично в основния склад - взима се с приоритет от там
        $defaultStoreId = $firstStoreId;
        $quantityInDefaultStore = $quantityArr[$firstStoreId];

        if($expectedQuantity > $quantityInDefaultStore){
            // Ако няма се търси в другите складове, където е с най-голямо но достатъчно к-во
            unset($quantityArr[$firstStoreId]);
            arsort($quantityArr);

            $storeIdWithMostQuantity = key($quantityArr);
            $quantityInDefaultStore = $quantityArr[$storeIdWithMostQuantity];
            $defaultStoreId = ($expectedQuantity <= $quantityInDefaultStore) ? $storeIdWithMostQuantity : $firstStoreId;
        }

        // Връщане на склада с най-голямо к-во, ако може да се продават неналични артикули
        return $defaultStoreId;
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


    /**
     * Помощна ф-я намираща по-малката цена от клиентската и от тази на пос-а
     *
     * @param int $policy1               - първа ЦП
     * @param int $policy2               - втора ЦП
     * @param int $productId             - ид на артикул
     * @param int $packagingId           - ид на опаковка
     * @param double $quantity           - за какво количество
     * @param datetime $date             - за какво количество
     * @param int|null $discountPolicyId - политика за отстъпка
     *
     * @return stdClass $priceRes
     */
    public static function getLowerPriceObj($policy1, $policy2, $productId, $packagingId, $quantity, $date, $discountPolicyId = null)
    {
        $Policy = cls::get('price_ListToCustomers');
        $contragentPrice = (object)array('price' => null, 'discount' => null);

        core_Debug::startTimer('TERMINAL_RESULT_GET_LOWER_PRICE_FETCH');
        $price = $Policy->getPriceByList($policy1, $productId, $packagingId, $quantity, $date, 1, 'no', $discountPolicyId);
        if(isset($policy2)){
            $contragentPrice = $Policy->getPriceByList($policy2, $productId, $packagingId, $quantity, $date, 1, 'no', $discountPolicyId);
        }
        core_Debug::stopTimer('TERMINAL_RESULT_GET_LOWER_PRICE_FETCH');

        // Ще се взима по-малката крайна цена от тази на клиента и на пос-а
        if(!empty($price->price) && !empty($contragentPrice->price)){
            $priceRes = $price;
            $withoutDiscount1 = $price->price * (1 - $price->discount);
            $withoutDiscount2 = $contragentPrice->price * (1 - $contragentPrice->discount);
            if(round($withoutDiscount1, 5) > round($withoutDiscount2, 5)){
                $priceRes = $contragentPrice;
            }
        } else {
            $priceRes = !empty($contragentPrice->price) ? $contragentPrice : $price;
        }

        return $priceRes;
    }
}
