<?php


/**
 *
 *
 * @category  bgerp
 * @package   tremol
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tremol_FiscPrinterDriverIp extends tremol_FiscPrinterDriverParent
{
    public $interfaces = 'peripheral_FiscPrinterIp';
    
    public $title = 'IP ФУ на Тремол';
    
    public static $viewException = 'admin, peripheral';
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @param NULL|int $userId
     *
     * @return bool
     */
    public function canSelectDriver($userId = null)
    {
        return true;
    }
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        parent::addFields($fieldset);
        
        $fieldset->FLD('isElectronic', 'enum(no=Не, yes=Да)', 'caption=Настройки на ФУ->Електронна бележка, after=serialNumber');
    }
    
    
    /**
     * Връща JS функция за отпечатване на ФБ
     *
     * @param stdClass $pRec   - запис от peripheral_Devices
     * @param array    $params - масив с параметри необходими за отпечатване на ФБ
     * 
     * // Параметри за отваряне на ФБ
     * IS_ELECTRONIC - дали ще се разпечатва електронен бон
     * OPER_NUM - номер на оператор - от 1 до 20
     * OPER_PASS - парола на оператора
     * IS_DETAILED - дали ФБ да е детайлна
     * IS_PRINT_VAT - дали да се отпечата ДДС информацията - разбивка за сумите по ДДС
     * PRINT_TYPE_STR - начин на отпечатване - stepByStep, postponed, buffered
     * RCP_NUM - уникален номер на бележката - [a-zA-Z0-9]{8}-[a-zA-Z0-9]{4}-[0-9]{7}
     *
     * // products - масив с артикулите
     * PLU_NAME - име на артикула
     * PRICE - цена
     * VAT_CLASS - ДДС клас - 0-3 (А-Г)
     * QTY - количество
     * DISC_ADD_P - надбавка/отстъпка в проценти - може и с -
     * DISC_ADD_V - надбавка/отстъпка в стойнонст - може и с -
     * BEFORE_PLU_TEXT - стринг или масив от стрингове с текст, който ще се добавя преди продукта
     * AFTER_PLU_TEXT - стринг или масив от стрингове с текст, който ще се добавя след продукта
     * DEP_NUM - номер на департамнет. Ако е зададен (от 0-99), се добавя от този департамент
     *
     * DATE_TIME - времето за синхронизира във формат 'd-m-Y H:i:s'. Ако е false - няма да се синхронизира
     *
     * SERIAL_NUMBER - серийния номер на принтера за проверка. Ако е false - няма да се проверява. Ако има разминаване - спира процеса.
     *
     * CHECK_AND_CANCEL_PREV_OPEN_RECEIPT - дали да се проверява и прекратява предишна активна бележка - ако не се подаде нищо, се приема за true
     *
     * SERIAL_KEEP_PORT_OPEN - дали порта да се държи отворен при серийна връзка - докато се приключи
     *
     * BEGIN_TEXT - стринг или масив от стрингове с текст, който ще се добавя в началото на бележката - преди продуктите
     * END_TEXT - стринг или масив от стрингове с текст, който ще се добавя в края на бележката - след продуктите
     *
     * // payments - масив от видовете плащания на бележката - ако не се подаде се приема в брой в лв.
     * PAYMENT_CHANGE - дали да се изчисли рестото - 0 - с ресто, 1 - без
     * PAYMENT_AMOUNT - сума на плащането
     * PAYMENT_CHANGE_TYPE - типа на рестото - 0 - ресто в брой, 1 - същото като плащането, 2 - във валута
     * PAYMENT_TYPE - типа на плащането, което може да е от 0 до 11
     * 0 - В брой лв
     * 1 - Чек
     * 2 - Талон
     * 3 - В.Талон
     * 4 - Амбалаж
     * 5 - Обслужване
     * 6 - Повреди
     * 7 - Карта
     * 8 - Банка
     * 9 - Резерв 1 - валута 1
     * 10 - Резерв 2 - валута 2
     * 11 - Резерв 3 - валута 3
     *
     * PAY_EXACT_SUM_TYPE - лесен начин за плащане на цялата сума в една валута. Параметрите са същити, като PAYMENT_TYPE
     * Може частично да се плати с един или няколко payments, а остатъка с PAY_EXACT_SUM_TYPE
     *
     * Ако няма PAY_EXACT_SUM_TYPE и payments, плащането ще е "В брой лв" (0)
     *
     * // Параметри за сторниране
     * IS_STORNO - дали се създава сторно бележка. По подобен начин на ФБ, само, че в бележката е СТОРНО
     * STORNO_REASON - типа на сторно бележката. 0 - грешка от оператор, 1 - рекламация или връщане, 2 - данъчно облекчение.
     * Само при операторска грешка не се следи за наличност в склада
     * RELATED_TO_RCP_NUM - номер на фискалния бон, който ще се сторнира
     * RELATED_TO_RCP_DATE_TIME - дата и час на фискалния бон, който ще се сторнира
     * FM_NUM - номер на фискалната памет, от която е издаден фактурата
     * RELATED_TO_URN - уникален номер на бележката, която се сторнира - [a-zA-Z0-9]{8}-[a-zA-Z0-9]{4}-[0-9]{7} - подобно на RCP_NUM
     * Другите параметри са: OPER_NUM, OPER_PASS, IS_DETAILED, IS_PRINT_VAT, PRINT_TYPE_STR - като при издаване на ФБ
     * QR_CODE_DATA - резултата от ReadLastReceiptQRcodeData. Връща се в fpOnSuccess функцията - FM Number*Receipt Number*Receipt Date*Receipt Hour*Receipt Amount
     * Може да се подаде този номер и от там автоматично да се извлече FM_NUM, RELATED_TO_RCP_NUM и RELATED_TO_RCP_DATE_TIME, ако не са подадени.
     * Помощен параметър за определяне на някои стойности
     *
     * // Параметри за кредитно известие
     * IS_CREDIT_NOTE - дали се създава кредитно известие. По подобен начин на СТОРНО, само, че в бележката е кредитно известие
     * RECIPIENT - 26 символа за получателя на фактурата
     * BUYER - 16 симвла за купувача
     * VAT_NUMBER - 13 символа за VAT номер
     * UIC - 13 символа за UIC номер на клиента
     * ADDRESS - 30 символа за адрес на клиента
     * UIC_TYPE_STR - типа на UIC номера - bulstat, EGN, FN, NRA
     * RELATED_TO_INV_NUM - 10 символа за фактурата, която се сторница
     * RELATED_TO_INV_DATE_TIME - дата и час на фактурата, която ще се сторнира - може да се попълни от QR_CODE_DATA, ако не е попълнено
     * Другите параметри са: OPER_NUM, OPER_PASS, PRINT_TYPE_STR - като при издаване на ФБ
     * Другите параметри са: STORNO_REASON, RELATED_TO_RCP_NUM, FM_NUM, RELATED_TO_URN, QR_CODE_DATA - като при издаване на СТОРНО
     * 
     * // Параметри са издаване на фактура
     * IS_INVOICE - дали се създава фактура
     * IS_ELECTRONIC - дали ще се разпечатва електронна фактура
     * RECIPIENT - 26 символа за получателя на фактурата
     * BUYER - 16 симвла за купувача
     * VAT_NUMBER - 13 символа за VAT номер
     * UIC - 13 символа за UIC номер на клиента
     * ADDRESS - 30 символа за адрес на клиента
     * UIC_TYPE_STR - типа на UIC номера - bulstat, EGN, FN, NRA
     * RCP_NUM - уникален номер на бележката - [a-zA-Z0-9]{8}-[a-zA-Z0-9]{4}-[0-9]{7} - не е задължителен
     * 
     * Другите параметри са: OPER_NUM, OPER_PASS, PRINT_TYPE_STR - като при издаване на ФБ
     * 
     * @return boolean|string
     * 
     * @see peripheral_FiscPrinterIp
     */
    public function printReceipt($pRec, $params)
    {
        $fp = $this->connectToPrinter($pRec);
        
        if (!$fp) {
            
            return false;
        }
        
        if (!isset($params['IS_ELECTRONIC'])) {
            $params['IS_ELECTRONIC'] = $pRec->isElectronic == 'yes' ? true : false;
        }
        
        // Задаваме параметрите за отваряне на ФБ
        setIfNot($params['OPER_NUM'], 1);
        
        if (!isset($params['OPER_PASS'])) {
            $params['OPER_PASS'] = $this->getOperPass($params['OPER_NUM'], $pRec);
        }
        
        $params['IS_DETAILED'] = (int) $params['IS_DETAILED'];
        $params['IS_PRINT_VAT'] = (int) $params['IS_PRINT_VAT'];
        
        setIfNot($params['PRINT_TYPE_STR'], 'buffered');
        
        expect(($params['OPER_NUM'] >= 1) && ($params['OPER_NUM'] <= 20));
        expect(strlen($params['OPER_PASS']) <= 6);
        expect(($params['PRINT_TYPE_STR'] == 'stepByStep') || ($params['PRINT_TYPE_STR'] == 'postponed') || ($params['PRINT_TYPE_STR'] == 'buffered'));
        
        setIfNot($params['DATE_TIME'], false);
        
        if ($params['DATE_TIME'] === true) {
            $params['DATE_TIME'] = date('d-m-Y H:i:s');
        }
        
        if ($params['DATE_TIME'] !== false) {
            expect(dt::verbal2mysql($params['DATE_TIME']));
            
            try {
                $fp->SetDateTime($params['DATE_TIME']);
            } catch (\Tremol\SException $e) { }
        }
        
        // Проверяваме серийния номер
        if ($params['SERIAL_NUMBER'] !== false) {
            setIfNot($params['SERIAL_NUMBER'], $pRec->serialNumber);
            
            if (!$params['SERIAL_NUMBER']) {
                list($params['SERIAL_NUMBER']) = explode('-', $params['RCP_NUM'], 2);
            }
            
            expect($params['SERIAL_NUMBER'] && (strlen($params['SERIAL_NUMBER']) == 8), $pRec, $params);
            
            $sn = $this->getSerialNumber($pRec);
            
            expect($sn == $params['SERIAL_NUMBER']);
        }
        
        // Проверява за отворена бележка и ако има я прекратява преди да започне новата
        setIfNot($params['CHECK_AND_CANCEL_PREV_OPEN_RECEIPT'], true);
        if ($params['CHECK_AND_CANCEL_PREV_OPEN_RECEIPT']) {
            try {
                $status = $fp->ReadStatus();
                
                if ($status->Opened_Fiscal_Receipt) {
                    sleep(2);
                    $status = $fp->ReadStatus();
                    if ($status->Opened_Fiscal_Receipt) {
                        try {
                            // Опитваме се да прекратим предишната бележка, ако има такава и да пуснем пак
                            $fp->CancelReceipt();
                            status_Messages::newStatus('|Прекратена предишна отворена бележка');
                        } catch (\Tremol\SException $e) {
                            try {
                                // Няма друго какво да се направи и затово плащаме и отпечатваме бележката
                                $fp->CashPayCloseReceipt();
                                status_Messages::newStatus('|Отпечатана предишна отворена бележка');
                            } catch (\Tremol\SException $e) { }
                        }
                    }
                }
            } catch (\Tremol\SException $e) { }
        }
        
        // Отваря бележката
        if (!$params['IS_STORNO'] && !$params['IS_CREDIT_NOTE']) {
            if ($params['IS_INVOICE']) {
                if ($params['UIC_TYPE_STR'] == 'bulstat') {
                    $params['UIC_TYPE'] = 0;
                } else if ($params['UIC_TYPE_STR'] == 'EGN') {
                    $params['UIC_TYPE'] = 1;
                } else if ($params['UIC_TYPE_STR'] == 'FN') {
                    $params['UIC_TYPE'] = 2;
                } else if ($params['UIC_TYPE_STR'] == 'NRA') {
                    $params['UIC_TYPE'] = 3;
                } else {
                    expect(false, 'Непозволен тип за UIC');
                }
                
                if ($params['IS_ELECTRONIC']) {
                    try {
                        $fp->OpenElectronicInvoiceWithFreeCustomerData($params['OPER_NUM'], $params['OPER_PASS'], $params['RECIPIENT'], $params['BUYER'], $params['VAT_NUMBER'], $params['UIC'], $params['ADDRESS'], $params['UIC_TYPE'], $params['RCP_NUM']);
                    } catch (\Tremol\SException $e) {
                        $this->handleTremolException($e);
                    }
                } else {
                    if ($params['PRINT_TYPE_STR'] == 'postponed') {
                        $params['PRINT_TYPE'] = Tremol\OptionInvoicePrintType::Postponed_Printing;
                    } else if ($params['PRINT_TYPE_STR'] == 'buffered') {
                        $params['PRINT_TYPE'] = Tremol\OptionInvoicePrintType::Buffered_Printing;
                    } else if ($params['PRINT_TYPE_STR'] == 'stepByStep') {
                        $params['PRINT_TYPE'] = Tremol\OptionInvoicePrintType::Step_by_step_printing;
                    } else {
                        expect(false, "Непозволен тип за принтиране");
                    }
                    
                    if ($params['RCP_NUM']) {
                        expect(preg_match($this->rcpNumPattern, $params['RCP_NUM']));
                    }
                    
                    try {
                        $fp->OpenInvoiceWithFreeCustomerData($params['OPER_NUM'], $params['OPER_PASS'], $params['PRINT_TYPE'], $params['RECIPIENT'], $params['BUYER'], $params['VAT_NUMBER'], $params['UIC'], $params['ADDRESS'], $params['UIC_TYPE'], $params['RCP_NUM']);
                    } catch (\Tremol\SException $e) {
                        $this->handleTremolException($e);
                    }
                }
            } else {
                expect($params['RCP_NUM'] && preg_match($this->rcpNumPattern, $params['RCP_NUM']));
                try {
                    if ($params['IS_ELECTRONIC']) {
                        $fp->OpenElectronicReceipt($params['OPER_NUM'], $params['OPER_PASS'], $params['IS_DETAILED'], $params['IS_PRINT_VAT'], $params['RCP_NUM']);
                    } else {
                        if ($params['PRINT_TYPE_STR'] == 'postponed') {
                            $params['PRINT_TYPE'] = Tremol\OptionFiscalRcpPrintType::Postponed_printing;
                        } else if ($params['PRINT_TYPE_STR'] == 'buffered') {
                            $params['PRINT_TYPE'] = Tremol\OptionFiscalRcpPrintType::Buffered_printing;
                        } else if ($params['PRINT_TYPE_STR'] == 'stepByStep') {
                            $params['PRINT_TYPE'] = Tremol\OptionFiscalRcpPrintType::Step_by_step_printing;
                        } else {
                            expect(false, "Непозволен тип за принтиране");
                        }
                        
                        $fp->OpenReceipt($params['OPER_NUM'], $params['OPER_PASS'], $params['IS_DETAILED'], $params['IS_PRINT_VAT'], $params['PRINT_TYPE'], $params['RCP_NUM']);
                    }
                } catch (\Tremol\SException $e) {
                    $this->handleTremolException($e);
                }
            }
        } else {
            
            // Ако ще се прави сторно или кредитно известие
            
            // Опитваме се да попълним няко от задължителните параметри
            if ($params['QR_CODE_DATA']) {
                list($fmNum, $toRcpNum, $toRcpDate, $toRcpTime) = explode('*', $params['QR_CODE_DATA']);
                
                setIfNot($params['FM_NUM'], $fmNum);
                setIfNot($params['RELATED_TO_RCP_NUM'], (int) $toRcpNum);
                
                $toRcpDateAndTime = $toRcpDate . ' ' . $toRcpTime;
                $toRcpDateAndTime = dt::mysql2verbal($toRcpDateAndTime, 'd-m-Y H:i:s', null, false, false);
                setIfNot($params['RELATED_TO_RCP_DATE_TIME'], $toRcpDateAndTime);
                
                setIfNot($params['RELATED_TO_INV_DATE_TIME'], $toRcpDateAndTime);
            }
            
            setIfNot($params['STORNO_REASON'], 1);
            expect(($params['STORNO_REASON'] >= 0) && ($params['STORNO_REASON'] <= 2));
            expect(strlen($params['RELATED_TO_RCP_NUM']) <= 6);
            expect(strlen($params['FM_NUM']) == 8);
            
            setIfNot($params['RELATED_TO_URN'], $params['RCP_NUM'], null);
            
            if (isset($params['RELATED_TO_URN'])) {
                expect(preg_match($this->rcpNumPattern, $params['RELATED_TO_URN']));
            }
            
            if ($params['IS_STORNO']) {
                expect($params['RELATED_TO_RCP_NUM'] && $params['RELATED_TO_RCP_DATE_TIME'] && $params['FM_NUM']);
                expect(dt::verbal2mysql($params['RELATED_TO_RCP_DATE_TIME']));
                
                if ($params['PRINT_TYPE_STR'] == 'postponed') {
                    $params['PRINT_TYPE'] = Tremol\OptionStornoRcpPrintType::Postponed_Printing;
                } else if ($params['PRINT_TYPE_STR'] == 'buffered') {
                    $params['PRINT_TYPE'] = Tremol\OptionStornoRcpPrintType::Buffered_Printing;
                } else if ($params['PRINT_TYPE_STR'] == 'stepByStep') {
                    $params['PRINT_TYPE'] = Tremol\OptionStornoRcpPrintType::Step_by_step_printing;
                } else {
                    expect(false, "Непозволен тип за принтиране");
                }
                
                try {
                    $fp->OpenStornoReceipt($params['OPER_NUM'], $params['OPER_PASS'], $params['IS_DETAILED'], $params['IS_PRINT_VAT'], $params['PRINT_TYPE'], $params['STORNO_REASON'], $params['RELATED_TO_RCP_NUM'], $params['RELATED_TO_RCP_DATE_TIME'], $params['FM_NUM'], $params['RELATED_TO_URN']);
                } catch (\Tremol\SException $e) {
                    $this->handleTremolException($e);
                }
            } else if ($params['IS_CREDIT_NOTE']) {
                
                if ($params['PRINT_TYPE_STR'] == 'postponed') {
                    $params['PRINT_TYPE'] = Tremol\OptionInvoiceCreditNotePrintType::Postponed_Printing;
                } else if ($params['PRINT_TYPE_STR'] == 'buffered') {
                    $params['PRINT_TYPE'] = Tremol\OptionInvoiceCreditNotePrintType::Buffered_Printing;
                } else if ($params['PRINT_TYPE_STR'] == 'stepByStep') {
                    $params['PRINT_TYPE'] = Tremol\OptionInvoiceCreditNotePrintType::Step_by_step_printing;
                } else {
                    expect(false, "Непозволен тип за принтиране");
                }
                
                expect($params['RELATED_TO_RCP_NUM'] && $params['RELATED_TO_INV_DATE_TIME'] && $params['FM_NUM']);
                expect(dt::verbal2mysql($params['RELATED_TO_INV_DATE_TIME']));
                
                if ($params['UIC_TYPE_STR'] == 'bulstat') {
                    $params['UIC_TYPE'] = 0;
                } else if ($params['UIC_TYPE_STR'] == 'EGN') {
                    $params['UIC_TYPE'] = 1;
                } else if ($params['UIC_TYPE_STR'] == 'FN') {
                    $params['UIC_TYPE'] = 2;
                } else if ($params['UIC_TYPE_STR'] == 'NRA') {
                    $params['UIC_TYPE'] = 3;
                } else {
                    expect(false, 'Непозволен тип за UIC');
                }
                
                try {
                    $fp->OpenCreditNoteWithFreeCustomerData($params['OPER_NUM'], $params['OPER_PASS'], $params['PRINT_TYPE'], $params['RECIPIENT'], $params['BUYER'], $params['VAT_NUMBER'], $params['UIC'], $params['ADDRESS'], $params['UIC_TYPE'], $params['STORNO_REASON'], $params['RELATED_TO_INV_NUM'], $params['RELATED_TO_INV_DATE_TIME'], $params['RELATED_TO_RCP_NUM'], $params['FM_NUM'], $params['RELATED_TO_URN']);
                } catch (\Tremol\SException $e) {
                    $this->handleTremolException($e);
                }
            }
        }
        
        $maxTextLen = ($pRec->fpType == 'fiscalPrinter') ? $this->fpLen : $this->crLen;
        $maxTextLen -= $this->mLen;
        
        if (isset($params['BEGIN_TEXT'])) {
            $tArr = $this->parseTextToArr($params['BEGIN_TEXT'], $maxTextLen);
            foreach ($tArr as $text) {
                try {
                    $fp->PrintText($text);
                } catch (\Tremol\SException $e) { }
            }
        }
        
        // Добавяме продуктите към бележката
        foreach ($params['products'] as $pArr) {
            setIfNot($pArr['PRICE'], 0);
            setIfNot($pArr['VAT_CLASS'], 1);
            setIfNot($pArr['QTY'], 1);
            setIfNot($pArr['PLU_NAME'], '');
            
            expect(($pArr['VAT_CLASS'] >= 0) && ($pArr['VAT_CLASS'] <= 3));
            
            expect(is_numeric($pArr['PRICE']) && is_numeric($pArr['QTY']) && (is_numeric($pArr['DISC_ADD_P']) || !isset($pArr['DISC_ADD_P'])) && (is_numeric($pArr['DISC_ADD_V']) || !isset($pArr['DISC_ADD_V'])));
            
            if (isset($pArr['BEFORE_PLU_TEXT'])) {
                $tArr = $this->parseTextToArr($pArr['BEFORE_PLU_TEXT'], $maxTextLen);
                foreach ($tArr as $text) {
                    try {
                        $fp->PrintText($text);
                    } catch (\Tremol\SException $e) { }
                }
            }
            
            try {
                if (isset($pArr['DEP_NUM'])) {
                    expect(is_numeric($pArr['DEP_NUM']) && ($pArr['DEP_NUM'] >= 0) && ($pArr['DEP_NUM'] <= 99));
                    $fp->SellPLUwithSpecifiedVATfromDep($pArr['PLU_NAME'], $pArr['VAT_CLASS'], $pArr['PRICE'], $pArr['QTY'], $pArr['DISC_ADD_P'], $pArr['DISC_ADD_V'], $pArr['DEP_NUM']);
                } else {
                    $fp->SellPLUwithSpecifiedVAT($pArr['PLU_NAME'], $pArr['VAT_CLASS'], $pArr['PRICE'], $pArr['QTY'], $pArr['DISC_ADD_P'], $pArr['DISC_ADD_V']);
                }
            } catch (\Tremol\SException $e) {
                $this->handleTremolException($e);
            }
            
            if (isset($pArr['AFTER_PLU_TEXT'])) {
                $tArr = $this->parseTextToArr($pArr['AFTER_PLU_TEXT'], $maxTextLen);
                foreach ($tArr as $text) {
                    try {
                        $fp->PrintText($text);
                    } catch (\Tremol\SException $e) { }
                }
            }
        }
        
        if (isset($params['END_TEXT'])) {
            $tArr = $this->parseTextToArr($params['END_TEXT'], $maxTextLen);
            foreach ($tArr as $text) {
                try {
                    $fp->PrintText($text);
                } catch (\Tremol\SException $e) { }
            }
        }
        
        // Добавяме начините на плащане
        if ($params['payments']) {
            foreach ($params['payments'] as $paymentArr) {
                
                setIfNot($paymentArr['PAYMENT_TYPE'], 0);
                setIfNot($paymentArr['PAYMENT_CHANGE'], 0);
                setIfNot($paymentArr['PAYMENT_CHANGE_TYPE'], 0);
                
                expect(($paymentArr['PAYMENT_TYPE'] >= 0) && ($paymentArr['PAYMENT_TYPE'] <= 11));
                expect(($paymentArr['PAYMENT_CHANGE'] == 0) || ($paymentArr['PAYMENT_TYPE'] == 1));
                expect(($paymentArr['PAYMENT_CHANGE_TYPE'] >= 0) && ($paymentArr['PAYMENT_CHANGE_TYPE'] <= 2));
                
                expect($paymentArr['PAYMENT_AMOUNT']);
                
                try {
                    $fp->Payment($paymentArr['PAYMENT_TYPE'], $paymentArr['PAYMENT_CHANGE'], $paymentArr['PAYMENT_AMOUNT'], $paymentArr['PAYMENT_CHANGE_TYPE']);
                } catch (\Tremol\SException $e) { }
            }
        }
        
        if (isset($params['PAY_EXACT_SUM_TYPE'])) {
            expect(($paymentArr['PAY_EXACT_SUM_TYPE'] >= 0) && ($paymentArr['PAY_EXACT_SUM_TYPE'] <= 11));
            
            try {
                $fp->PayExactSum($paymentArr['PAY_EXACT_SUM_TYPE']);
            } catch (\Tremol\SException $e) { }
        }
        
        try {
            $fp->CashPayCloseReceipt();
        } catch (\Tremol\SException $e) {
            $res = true;
        }
        
        try {
            $res = $fp->ReadLastReceiptQRcodeData();
            if (!$res) {
                sleep(1);
                $res = $fp->ReadLastReceiptQRcodeData();
            }
        } catch (\Tremol\SException $e) {
            try {
                sleep(1);
                $res = $fp->ReadLastReceiptQRcodeData();
            } catch (\Tremol\SException $e) {
                $res = true;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Проверка дали има връзка с ФУ
     *
     * @param stdClass $rec
     *
     * @return boolean
     *
     * @see peripheral_FiscPrinterIp
     */
    public function checkConnection($rec)
    {
        if ($this->getSerialNumber($rec)) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Отпечатва дубликат на последната бележка
     *
     * @param stdClass $rec
     *
     * @return boolean
     *
     * @see peripheral_FiscPrinterIp
     */
    public function printDuplicate($rec)
    {
        try {
            $fp = self::connectToPrinter($rec);
            
            if ($fp) {
                $fp->PrintLastReceiptDuplicate();
                
                return true;
            }
        } catch (\Tremol\SException $e) {
            $this->handleTremolException($e);
        }
        
        return false;
    }
    
    
    /**
     * Добавя/изкарва пари от касата
     *
     * @param stdClass $pRec
     * @param int      $operNum
     * @param string   $operPass
     * @param float    $amount - ако е минус - изкрва пари, а с плюс - вкарва
     * @param boolean  $printAvailability
     * @param string   $text
     *
     * @return boolean
     *
     * @see peripheral_FiscPrinterIp
     */
    public function cashReceivedOrPaidOut($rec, $operNum, $operPass, $amount, $printAvailability = false, $text = '')
    {
        $printAvailability = (int) $printAvailability;
        
        expect(($operNum >= 1) && ($operNum <= 20));
        
        try {
            $fp = self::connectToPrinter($rec);
            
            if ($fp) {
                $fp->ReceivedOnAccount_PaidOut($operNum, $operPass, $amount, $printAvailability, $text);
                
                return true;
            }
        } catch (\Tremol\SException $e) {
            $this->handleTremolException($e);
        }
        
        return false;
    }
    
    
    /**
     * Записва бележката от съответното ФУ във файл и му връща манипулатора
     *
     * @param stdClass $pRec
     * @param integer|null $receiptNum
     *
     * @return false|string
     */
    public function saveReceiptToFile($pRec, $receiptNum = null)
    {
        $fp = $this->connectToPrinter($pRec);
        
        if (!$fp) {
            
            return false;
        }
        
        if (!isset($receiptNum)) {
            $receiptNum = $fp->ReadLastReceiptNum();
        }
        
        try {
            $fp->ReadElectronicReceipt_QR_Data($receiptNum);
            
            $resArr = $fp->RawRead(0, "@");
            
            $str = implode(array_map("chr", $resArr));
            
            $str = i18n_Charset::convertToUtf8($str, "windows-1251");
            
            $strArr = explode("\n", $str);
            
            $receipt = '';
            $cnt = countR($strArr);
            for($i = 0; $i < $cnt - 1; $i++) {
                
                $line = $strArr[$i];
                $line = mb_substr($line, 4, mb_strlen($line) - 6);
                
                // Предпоследният ред съдържа QR кода
                // Последния ред е празен
                if ($i == ($cnt - 2)) {
                    $qr = $line;
                } else {
                    $receipt .= $line . "\n";
                }
            }
            
            // Разделяме бона на две, за да вмъкнем QR кода
            $rBegin = $receipt;
            $rEnd = '';
            $matches = array();
            if (preg_match('/[^\w]*ФИСКАЛЕН БОН/', $receipt, $matches, PREG_OFFSET_CAPTURE) && $matches[0][1]) {
                
                // Трябва да е substr, а не mb_substr
                
                $rBegin = substr($receipt, 0, $matches[0][1]);
                $rEnd = substr($receipt, $matches[0][1]);
            }
            
            $tpl = getTplFromFile('/tremol/tpl/ElectronicReceipt.shtml');
            $tpl->replace($rBegin, 'REC_START');
            $tpl->replace($rEnd, 'REC_END');
            $tpl->replace(barcode_Qr::getUrl($line, true, 15), 'QR_URL');
            
            // Получения HTML файл го конвертираме към JPG
            $fileName = 'ER_' . str_pad($receiptNum, 6, '0', STR_PAD_LEFT);
            $fh = webkittopdf_Converter::convert($tpl->getContent(), $fileName . '.jpg', 'electronicReceipts', array(), true, array('--disable-smart-width', '--width 400'));
            
            return $fh;
        } catch (\Tremol\SException $e) {
            $this->handleTremolException($e);
        }
        
        return false;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param tremol_FiscPrinterDriverWeb $Driver
     * @param peripheral_Devices        $Embedder
     * @param core_ET                   $tpl
     * @param stdClass                  $data
     */
    protected static function on_AfterRenderSingle($Driver, $Embedder, &$tpl, $data)
    {
        if ($Embedder instanceof peripheral_Devices && $Embedder->haveRightFor('edit', $data->rec->id)) {
            try {
                $sn = $Driver->getSerialNumber($data->rec);
            } catch (Exception $e) {
                $Driver->handleAndShowException($e);
            }
            
            // Настройваме хедърите и футърите на ФУ
            if (Request::get('update')) {
                // Променя серийния номер на ФУ, ако не е коректно
                if ($sn) {
                    if ($sn != $data->rec->serialNumber) {
                        $data->rec->serialNumber = $sn;
                        
                        $Embedder->save($data->rec, 'serialNumber');
                        
                        status_Messages::newStatus('|Променен сериен номер на|* ' . $sn);
                    }
                }
                
                // Добавяме паролата на оператора
                try {
                    $oPass = $Driver->getOperPassFromFU($data->rec);
                } catch (Exception $e) {
                    $Driver->handleAndShowException($e);
                }
                
                if (isset($oPass)) {
                    if ($oPass != $data->rec->otherData['operPass']) {
                        $data->rec->otherData['operPass'] = $oPass;
                        
                        $Embedder->save($data->rec, 'otherData');
                    }
                }
                
                try {
                    $dPaymArr = $Driver->getDefaultPaymentsFromFU($data->rec);
                } catch (Exception $e) {
                    $Driver->handleAndShowException($e);
                }
                
                if ($dPaymArr['defPaymArr']) {
                    $data->rec->otherData['defPaymArr'] = $dPaymArr['defPaymArr'];
                    $data->rec->otherData['exRate'] = $dPaymArr['exRate'];
                    
                    $Embedder->save($data->rec, 'otherData');
                }
                
                try {
                    $depArr = $Driver->getDepArr($data->rec);
                } catch (Exception $e) {
                    $Driver->handleAndShowException($e);
                }
                
                if (!empty($depArr)) {
                    $data->rec->otherData['depArr'] = $depArr;
                    $Embedder->save($data->rec, 'otherData');
                }
                
                try {
                    self::setDateTime($data->rec);
                } catch (Exception $e) {
                    $Driver->handleAndShowException($e);
                }
                
                $maxTextLen = ($data->rec->fpType == 'fiscalPrinter') ? $Driver->fpLen : $Driver->crLen;
                
                $pHeaderArr = array();
                
                if ($data->rec->header == 'yes') {
                    for ($i = 1; $i <= 7; $i++) {
                        $h = headerText . $i;
                        $pHeaderArr[$i] .= self::formatText((string) $data->rec->{$h}, $data->rec->headerPos, $maxTextLen);
                    }
                    try {
                        self::progHeader($data->rec, $pHeaderArr);
                    } catch (Exception $e) {
                        $Driver->handleAndShowException($e);
                    }
                }
                
                if ($data->rec->footer == 'yes') {
                    try {
                        self::progFooter($data->rec, self::formatText((string) $data->rec->footerText, $data->rec->footerPos, $maxTextLen));
                    } catch (Exception $e) {
                        $Driver->handleAndShowException($e);
                    }
                }
            }
        }
    }
    
    
    /**
     * Помощна функция за връзка с ФУ
     * 
     * @param stdClass $rec
     * @param boolean $keepPortOpen
     * @param boolean $setDeviceSettings
     * 
     * @return false|\Tremol\FP
     */
    protected static function connectToPrinter($rec, $keepPortOpen = false, $setDeviceSettings = true)
    {
        static $fpArr = array();
        
        expect($rec);
        
        $key = md5($keepPortOpen . '|' . $setDeviceSettings . '|' . serialize($rec));
        
        $fp = $fpArr[$key];
        
        if (!isset($fp)) {
            try {
                require_once getFullPath("tremol/libs/{$rec->driverVersion}/fp.php");
                
                $fp = new \Tremol\FP();
                
                $serverIp = $rec->serverIp;
                
                $sArr = explode('://', $serverIp);
                
                if (countR($sArr) == 2) {
                    $serverIp = $sArr[1];
                }
                
                $fp->ServerSetSettings($serverIp, $rec->serverTcpPort);
                
                if ($setDeviceSettings) {
                    if ($rec->tcpIp) {
                        $fp->ServerSetDeviceTcpSettings($rec->tcpIp, $rec->tcpPort, $rec->tcpPass);
                    } else {
                        $fp->ServerSetDeviceSerialSettings($rec->serialPort, $rec->serialSpeed, $keepPortOpen);
                    }
                }
            } catch (\Tremol\SException $e) {
                self::handleTremolException($e);
                
                $fp = false;
            }
        }
        
        $fpArr[$key] = $fp;
        
        return $fp;
    }
    
    
    /**
     * Помощна функция за намиране на порта и скоростта на периферното устройство
     * 
     * @param stdClass $pRec
     * @param null|core_Et $jsTpl
     * 
     * @return array
     * 
     * {@inheritDoc}
     * @see tremol_FiscPrinterDriverParent::findDevicePort()
     */
    protected function findDevicePort($pRec, &$jsTpl = null)
    {
        $resArr = array();
        
        $fp = $this->connectToPrinter($pRec, false, false);
        
        if ($fp) {
            
            try {
                $fDev = $fp->ServerFindDevice(false);
                
                if (strlen($fDev->SerialPort)) {
                    $resArr['serialPort'] = $fDev->SerialPort;
                }
                
                if (strlen($fDev->BaudRate) && $fDev->BaudRate && $fDev->SerialPort) {
                    $resArr['baudRate'] = $fDev->BaudRate;
                }
            } catch (\Tremol\SException $e) {
                self::handleTremolException($e);
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща серийния номер на ФУ
     * 
     * @param stdClass $rec
     * 
     * @return false|string
     */
    protected static function getSerialNumber($rec)
    {
        $sn = false;
        
        try {
            $fp = self::connectToPrinter($rec);
            
            if ($fp) {
                $sn = $fp->ReadSerialAndFiscalNums()->SerialNumber;
            }
        } catch (\Tremol\SException $e) {
            self::handleTremolException($e);
        }
        
        return $sn;
    }
    
    
    /**
     * Връща паролата на оператора от ФУ
     *
     * @param stdClass $rec
     * @param integer $oper
     *
     * @return false|string
     */
    protected static function getOperPassFromFU($rec, $oper = 1)
    {
        $oPass = false;
        
        try {
            $fp = self::connectToPrinter($rec);
            
            if ($fp) {
                $oPass = $fp->ReadOperatorNamePassword($oper)->Password;
            }
        } catch (\Tremol\SException $e) {
            self::handleTremolException($e);
        }
        
        return $oPass;
    }
    
    
    /**
     * Връща зададените начини на плащания във ФУ
     *
     * @param stdClass $rec
     *
     * @return false|string
     */
    protected static function getDefaultPaymentsFromFU($rec)
    {
        $resArr = array();
        
        try {
            $fp = self::connectToPrinter($rec);
            
            $isNew = self::isNewVersion($fp);
            
            $dPaymArr = array();
            
            if ($isNew) {
                $paymRes = $fp->ReadPayments();
                for($i=0;$i<=11;$i++) {
                    try {
                        $namePayment = "NamePayment{$i}";
                        $dPaymArr[trim($paymRes->{$namePayment})] = $i;
                    } catch (Exception $e) { }
                }
                try {
                    $exchangeRate = trim($paymRes->ExchangeRate);
                } catch (Exception $e) {
                    $exchangeRate = null;
                }
            } else {
                $paymRes = $fp->ReadPayments_Old();
                
                for($i=0;$i<=4;$i++) {
                    try {
                        $namePayment = "NamePaym{$i}";
                        $codePayment = "CodePaym{$i}";
                        
                        $dPaymArr[trim($paymRes->{$namePayment})] = $i;
                    } catch (Exception $e) { }
                }
                
                try {
                    $exchangeRate = trim($paymRes->ExRate);
                } catch (Exception $e) {
                    $exchangeRate = null;
                }
            }
            
            $resArr['defPaymArr'] = $dPaymArr;
            $resArr['exRate'] = $exchangeRate;
        } catch (\Tremol\SException $e) {
            self::handleTremolException($e);
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща зададените департаменти
     *
     * @param stdClass $rec
     *
     * @return false|string
     */
    protected static function getDepArr($rec)
    {
        $resArr = array();
        
        // @todo - временно е спряно
        
//         try {
//             $fp = self::connectToPrinter($rec);
//             if ($fp) {
//                 for($depNum=0;$depNum<100;$depNum++) {
//                     $dep = $fp->ReadDepartment($depNum);
                    
//                     $depNumPad = str_pad($depNum, 2, 0, STR_PAD_LEFT);
                    
//                     $depName = trim($dep->DepName);
                    
//                     // Да избегнем дефолтно зададените
//                     if ($depName == 'Деп ' . $depNumPad) {
//                         continue;
//                     }
                    
//                     $resArr[$dep->DepNum] = $depName;
//                 }
//             }
//         } catch (\Tremol\SException $e) {
//             self::handleTremolException($e);
//         }
        
        return $resArr;
    }
    
    
    /**
     * Проверява дали ФУ е нова версия
     *
     * @param \Tremol\FP $fp
     * 
     * @return boolean
     */
    protected static function isNewVersion($fp)
    {
        $model = $fp->ReadVersion()->Model;
        
        if (strpos($model, "V2") == (strlen($model) - 2)) {
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Задава време на ФУ
     * 
     * @param stdClass $rec
     * @param null|string $date
     */
    protected static function setDateTime($rec, $date = null)
    {
        if (!isset($date)) {
            $date = date('d-m-Y H:i:s');
        }
        
        try {
            $fp = self::connectToPrinter($rec);
            
            if ($fp) {
                $fp->SetDateTime($date);
            }
        } catch (\Tremol\SException $e) {
            self::handleTremolException($e);
        }
    }
    
    
    /**
     * Настройва хедърите на ФУ
     * 
     * @param stdClass $rec
     * @param array $hArr
     */
    protected static function progHeader($rec, $hArr)
    {
        try {
            $fp = self::connectToPrinter($rec);
            
            if ($fp) {
                foreach ($hArr as $hPos => $hStr) {
                    $fp->ProgHeader($hPos, $hStr);
                }
            }
        } catch (\Tremol\SException $e) {
            self::handleTremolException($e);
        }
    }
    
    
    /**
     * Настройва футъра на ФУ
     * 
     * @param stdClass $rec
     * @param string $text
     */
    protected static function progFooter($rec, $text)
    {
        try {
            $fp = self::connectToPrinter($rec);
            
            if ($fp) {
                $fp->ProgFooter($text);
            }
        } catch (\Tremol\SException $e) {
            self::handleTremolException($e);
        }
    }
    
    
    /**
     * Помощна функция при вкарване/изкарване на средства от ФУ
     * 
     * @param stdClass $pRec
     * @param integer $operator
     * @param integer $operPass
     * @param double $amount
     * @param array $retUrl
     * @param boolean $printAvailability
     * @param string $text
     * @param string $actTypeVerb
     * @param null|core_Et $jsTpl
     * 
     * @see tremol_FiscPrinterDriverParent::getResForCashReceivedOrPaidOut()
     */
    protected function getResForCashReceivedOrPaidOut($pRec, $operator, $operPass, $amount, $retUrl = array(), $printAvailability = false, $text = '', $actTypeVerb = '', &$jsTpl = null)
    {
        try {
            $this->cashReceivedOrPaidOut($pRec, $operator, $operPass, $amount, $printAvailability, $text);
            if ($retUrl) {
                redirect($retUrl, false, "|Успешно {$actTypeVerb} във ФУ");
            }
        } catch (Exception $e) {
            $this->handleAndShowException($e);
            status_Messages::newStatus("|Грешка при {$actTypeVerb} във ФУ", 'error');
        }
    }
    
    
    /**
     * Помощна фунцкция при отпечаване на отчет
     * 
     * @param stdClass $pRec
     * @param stdClass $rec
     * @param string $rVerb
     * @param null|core_Et $jsTpl
     * @param array $retUrl
     * 
     * @see tremol_FiscPrinterDriverParent::getResForReport()
     */
    protected function getResForReport($pRec, $rec, $rVerb = '', &$jsTpl = null, $retUrl = array())
    {
        try {
            try {
                $fp = $this->connectToPrinter($pRec);
                
                expect($fp);
                
                $zeroing = ($rec->zeroing == 'yes') ? Tremol\OptionZeroing::Zeroing : Tremol\OptionZeroing::Without_zeroing;
                $isDetailed = ($rec->isDetailed == 'yes') ? true : false;
                
                if ($rec->report == 'day') {
                    if ($isDetailed) {
                        $fp->PrintDetailedDailyReport($zeroing);
                    } else {
                        $fp->PrintDailyReport($zeroing);
                    }
                }
                
                if ($rec->report == 'operator') {
                    $fp->PrintOperatorReport($zeroing, (int) $rec->operNum);
                }
                
                if (($rec->report == 'period') || ($rec->report == 'month') || ($rec->report == 'year') || ($rec->report == 'klen') || ($rec->report == 'csv')) {
                    $fromDate = dt::mysql2verbal($rec->fromDate, 'd-m-Y H:i:s');
                    $toDate = dt::mysql2verbal($rec->toDate . ' 23:59:59', 'd-m-Y H:i:s');
                    
                    if (($rec->report == 'klen') || ($rec->report == 'csv')) {
                        if ($rec->printType == 'save') {
                            $outType = $rec->saveType;
                        } else {
                            $outType = $rec->printIn;
                        }
                        
                        if (!$outType) {
                            $outType = 'pc';
                        }
                        
                        $outType = strtolower($outType);
                        
                        if ($rec->report == 'csv') {
                            
                            if ($outType == 'sd') {
                                $outTypeStr = Tremol\OptionStorageReport::To_SD_card;
                            } else if ($outType == 'usb') {
                                $outTypeStr = Tremol\OptionStorageReport::To_USB_Flash_Drive;
                            } else {
                                $outTypeStr = Tremol\OptionStorageReport::To_PC;
                            }
                            if ($rec->csvFormat == 'no') {
                                $csvFormatStr = Tremol\OptionCSVformat::No;
                            } else {
                                $csvFormatStr = Tremol\OptionCSVformat::Yes;
                            }
                            
                            $fp->ReadEJByDateCustom($outTypeStr, $csvFormatStr, $rec->flagReceipts, $rec->flagReports, $fromDate, $toDate);
                            
                            if ($outType == 'pc') {
                                
                                $ext = ($rec->csvFormat == 'yes') ? 'csv' : 'txt';
                                
                                try {
                                    $fh = $this->saveRawDataToFile($fp, 'tremol_CSV_' . $rec->fromDate . '_' . $rec->toDate . '.' . $ext);
                                } catch (\Tremol\SException $e) {
                                    $this->handleTremolException($e);
                                }
                                
                                if ($fh) {
                                    redirect(array('fileman_Files', 'single', $fh));
                                }
                            }
                        } else {
                            if ($outType == 'pc') {
                                if ($isDetailed) {
                                    $detailType = Tremol\OptionReportFormat::Detailed_EJ;
                                } else {
                                    $detailType = Tremol\OptionReportFormat::Brief_EJ;
                                }
                                
                                $fp->ReadEJByDate($detailType, $fromDate, $toDate);
                                
                                try {
                                    $fh = $this->saveRawDataToFile($fp, 'tremol_CSV_' . $rec->fromDate . '_' . $rec->toDate . '.csv');
                                } catch (\Tremol\SException $e) {
                                    $this->handleTremolException($e);
                                }
                                
                                if ($fh) {
                                    redirect(array('fileman_Files', 'single', $fh));
                                }
                            } else {
                                if ($outType == 'sd') {
                                    $reportStorage = Tremol\OptionReportStorage::SD_card_storage;
                                } elseif ($outType == 'usb') {
                                    $reportStorage = Tremol\OptionReportStorage::USB_storage;
                                } else {
                                    $reportStorage = Tremol\OptionReportStorage::Printing;
                                }
                                
                                $fp->PrintOrStoreEJByDate($reportStorage, $fromDate, $toDate);
                            }
                        }
                    } else {
                        if ($isDetailed) {
                            $fp->PrintDetailedFMReportByDate($fromDate, $toDate);
                        } else {
                            $fp->PrintBriefFMReportByDate($fromDate, $toDate);
                        }
                    }
                }
                
                if ($rec->report == 'number') {
                    $reportStorage = Tremol\OptionReportStorage::Printing;
                    if ($rec->printType == 'save') {
                        $reportStorage = Tremol\OptionReportStorage::USB_storage;
                        if ($rec->saveType == 'sd') {
                            $reportStorage = Tremol\OptionReportStorage::SD_card_storage;
                        }
                    }
                    
                    $fp->PrintOrStoreEJByRcpNum($reportStorage, $rec->fromNum, $rec->toNum);
                }
                
                $msg = "|Успешно отпечатан {$rVerb} отчет";
                if (!empty($retUrl)) {
                    
                    return redirect($retUrl, false, $msg);
                }
                status_Messages::newStatus($msg);
            } catch (\Tremol\SException $e) {
                $this->handleTremolException($e);
            }
        } catch (Exception $e) {
            $this->handleAndShowException($e);
        }
    }
    
    
    /**
     * Връща диапазона на фактурите
     *
     * @param stdClass $pRec
     * @param null|core_Et $jsTpl
     * 
     * @return array|null
     * 
     * @see tremol_FiscPrinterDriverParent::getResForReport()
     */
    protected function getInvoiceRange($pRec, &$jsTpl = null)
    {
        $iRange = null;
        try {
            try {
                $fp = self::connectToPrinter($pRec);
                
                if ($fp) {
                    $iRange = $fp->ReadInvoiceRange();
                }
            } catch (\Tremol\SException $e) {
                $this->handleTremolException($e);
            }
        } catch (Exception $e) {
            $this->handleAndShowException($e);
        }
        
        $iResArr = array();
        if ($iRange) {
            $iResArr['start'] = $iRange->StartNum;
            $iResArr['end'] = $iRange->EndNum;
        }
        
        return $iResArr;
    }
    
    
    /**
     * Задава диапазон на фактурите
     *
     * @param stdClass $pRec
     * @param int $from
     * @param int $to
     * @param null|core_Et $jsTpl
     * @param array $retUrl
     * 
     * @see tremol_FiscPrinterDriverParent::getResForReport()
     */
    protected function setInvoiceRange($pRec, $from, $to, &$jsTpl = null, $retUrl = array())
    {
        try {
            try {
                $fp = $this->connectToPrinter($pRec);
                
                expect($fp);
                
                $fp->SetInvoiceRange($from, $to);
                
                if (!empty($retUrl)) {
                    
                    return redirect($retUrl, false, "|Успешно зададен диапазон за фактурите");
                }
                status_Messages::newStatus($msg);
            } catch (\Tremol\SException $e) {
                $this->handleTremolException($e);
            }
        } catch (Exception $e) {
            $this->handleAndShowException($e);
        }
    }
    
    
    /**
     * Прочита и фоматира данните от ФУ и създава файл
     * 
     * @param \Tremol\FP $fp
     * @param string $name
     * @param string $bucket
     * 
     * @return string
     */
    protected static function saveRawDataToFile($fp, $name, $bucket = 'exportCsv')
    {
        $bytes = $fp->RawRead(0, "@");
        
        $str = implode(array_map("chr", $bytes));
        
        $lines = explode("\n",$str);
        $res = "";
        for($i= 0; $i < countR($lines); $i++) {
            $line = $lines[$i];
            $res.= mb_substr($line, 4, countR($line) - 3) . "\n";
        }
        
        $res = i18n_Charset::convertToUtf8($res, "windows-1251");
        
        $fh = fileman::absorbStr($res, $bucket, $name);
        
        return $fh;
    }
    
    
    /**
     * Прихващане на грешки
     * 
     * @param \Tremol\SException $ex
     */
    protected static function handleTremolException($ex)
    {
        if($ex instanceof \Tremol\SException) {
            $code = $ex->getCode();
            if($ex->isFpException()) {
                $ste1 = $ex->getSte1();
                $ste2 = $ex->getSte2();
                
                /**
                 *   Possible reasons:
                 * ste1 =                                              ste2 =
                 *  0x30 OK                                                   0x30 OK
                 *  0x31 Out of paper, printer failure                        0x31 Invalid command
                 *  0x32 Registers overflow                                   0x32 Illegal command
                 *  0x33 Clock failure or incorrect date&time                 0x33 Z daily report is not zero
                 *  0x34 Opened fiscal receipt                                0x34 Syntax error
                 *  0x35 Payment residue account                              0x35 Input registers overflow
                 *  0x36 Opened non-fiscal receipt                            0x36 Zero input registers
                 *  0x37 Registered payment but receipt is not closed         0x37 Unavailable transaction for correction
                 *  0x38 Fiscal memory failure                                0x38 Insufficient amount on hand
                 *  0x39 Incorrect password                                   0x3A No access
                 *  0x3a Missing external display
                 *  0x3b 24hours block – missing Z report
                 *  0x3c Overheated printer thermal head.
                 *  0x3d Interrupt power supply in fiscal receipt (one time until status is read)
                 *  0x3e Overflow EJ
                 *  0x3f Insufficient conditions
                 */
                if ($ste1 == 0x30 && $ste2 == 0x32) {
                    $msg = "Грешка! ste1 == 0x30 - Командата е ОК и ste2 == 0x32 - Командата е непозволена в текущото състояние на ФУ";
                } else if ($ste1 == 0x30 && $ste2 == 0x33) {
                    $msg = "Грешка! ste1 == 0x30 - Командата е ОК и ste2 == 0x33 - Направете Z отчет";
                } else if ($ste1 == 0x34 && $ste2 == 0x32) {
                    $msg = "Грешка! ste1 == 0x34 - Отворен фискален бон и ste2 == 0x32 - Командата е непозволена в текущото състояние на ФУ";
                } else if ($ste1 == 0x39 && $ste2 == 0x32) {
                    $msg = "Грешка! ste1 == 0x39 - Грешна парола и ste2 == 0x32 - Командата е непозволена";
                } else {
                    $msg = "Грешка! " . $ex->getMessage() . " ste1=" . $ste1 . ", ste2=" . $ste2;
                }
            } else if($code == \Tremol\ServerErrorType::ServerDefsMismatch) {
                $msg = "Грешка! Текущата версия на библиотеката и сървърните дефиниции се различават.";
            } else if ($code == \Tremol\ServerErrorType::ServMismatchBetweenDefinitionAndFPResult) {
                $msg = "Грешка! Текущата версия на библиотеката и фърмуера на ФУ са несъвместими";
            } else if ($code == \Tremol\ServerErrorType::ServerAddressNotSet) {
                $msg = "Грешка! Не е зададен адрес на сървъра!";
            } else if ($code == \Tremol\ServerErrorType::ServerConnectionError) {
                $msg = "Грешка! Не може да се осъществи връзка със ZfpLab сървъра";
            } else if ($code == \Tremol\ServerErrorType::ServSockConnectionFailed) {
                $msg = "Грешка! Сървъра не може да се свърже с ФУ";
            } else if ($code == \Tremol\ServerErrorType::ServTCPAuth) {
                $msg = "Грешка! Грешна TCP парола на устройството";
            } else if ($code == \Tremol\ServerErrorType::ServWaitOtherClientCmdProcessingTimeOut) {
                $msg = "Грешка! Обработката на другите клиенти на сървъра отнема много време";
            } else {
                $msg = "Грешка! " . $ex->getMessage();
            }
            
            self::logDebug($msg);
            
            throw new core_exception_Expect($msg);
        }
    }
    
    
    /**
     * Прихващане на грешки и показване на съобщение
     *
     * @param Exception $ex
     */
    public static function handleAndShowException($ex)
    {
        $msg = $ex->getMessage();
        
        self::logDebug($msg);
        
        wp($msg, $ex);
        
        if (haveRole(self::$viewException)) {
            status_Messages::newStatus('|*' . $msg, 'error');
        }
    }
    
    
    /**
     * Екшън за печат на дубликат
     */
    public function act_PrintDuplicate()
    {
        requireRole($this->canPrintDuplicate);
        expect($id = Request::get('id', 'int'));
        expect($pRec = peripheral_Devices::fetch($id));
        $Driver = peripheral_Devices::getDriver($pRec);
        
        try{
            if($Driver->printDuplicate($pRec)){
                core_Statuses::newStatus('Дубликатът е отпечатан успешно', 'notice');
            }
        } catch(core_exception_Expect $e){
            $this->logErr($e->getMessage(), $pRec->id);
            core_Statuses::newStatus('Грешка при отпечатването на дубликат', 'error');
        }
        
        return followRetUrl();
    }
}
