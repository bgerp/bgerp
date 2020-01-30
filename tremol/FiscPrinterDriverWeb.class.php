<?php


/**
 *
 *
 * @category  bgerp
 * @package   tremol
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tremol_FiscPrinterDriverWeb extends tremol_FiscPrinterDriverParent
{
    public $interfaces = 'peripheral_FiscPrinterWeb';
    
    public $title = 'Уеб ФУ на Тремол';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'tremol_FiscPrinterDriver2';
    
    
    /**
     * 
     * @var string
     */
    public $loadList = 'peripheral_DeviceWebPlg';
    
    
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
     * Връща JS функция за отпечатване на ФБ
     *
     * @param stdClass $pRec   - запис от peripheral_Devices
     * @param array    $params - масив с параметри необходими за отпечатване на ФБ
     *
     * // Параметри за отваряне на ФБ
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
     * 
     * Другите параметри са: OPER_NUM, OPER_PASS, PRINT_TYPE_STR - като при издаване на ФБ
     * Другите параметри са: STORNO_REASON, RELATED_TO_RCP_NUM, FM_NUM, RELATED_TO_URN, QR_CODE_DATA - като при издаване на СТОРНО
     * 
     * @return string
     *
     * @see peripheral_FiscPrinterWeb
     */
    public function getJs($pRec, $params)
    {
        // Шаблона за JS
        $js = getTplFromFile('/tremol/js/fiscPrintTpl.txt');
        
        $this->addTplFile($js, $pRec->driverVersion);
        
        $this->connectToPrinter($js, $pRec, $params['SERIAL_KEEP_PORT_OPEN']);
        
        // Задаваме параметрите за отваряне на ФБ
        setIfNot($params['OPER_NUM'], 1);
        if (!isset($params['OPER_PASS'])) {
            $params['OPER_PASS'] = $this->getOperPass($params['OPER_NUM'], $pRec);
        }
        
        if ($params['IS_DETAILED']) {
            $params['IS_DETAILED'] = 'true';
        } else {
            $params['IS_DETAILED'] = 'false';
        }
        if ($params['IS_PRINT_VAT']) {
            $params['IS_PRINT_VAT'] = 'true';
        } else {
            $params['IS_PRINT_VAT'] = 'false';
        }
        setIfNot($params['PRINT_TYPE_STR'], 'buffered');
        
        if (!$params['IS_STORNO'] && !$params['IS_CREDIT_NOTE']) {
            expect($params['RCP_NUM'] && preg_match($this->rcpNumPattern, $params['RCP_NUM']));
            $js->replace(json_encode($params['RCP_NUM']), 'RCP_NUM');
            
            $js->removeBlock('OPEN_STORNO_RECEIPT_1');
            $js->removeBlock('OPEN_STORNO_RECEIPT_2');
            $js->removeBlock('OPEN_CREDIT_NOTE_1');
            $js->removeBlock('OPEN_CREDIT_NOTE_2');
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
            
            if ($params['IS_STORNO']) {
                expect($params['RELATED_TO_RCP_NUM'] && $params['RELATED_TO_RCP_DATE_TIME'] && $params['FM_NUM']);
                expect(dt::verbal2mysql($params['RELATED_TO_RCP_DATE_TIME']));
                
                $js->removeBlock('OPEN_CREDIT_NOTE_1');
                $js->removeBlock('OPEN_CREDIT_NOTE_2');
            } else if ($params['IS_CREDIT_NOTE']) {
                expect($params['RELATED_TO_RCP_NUM'] && $params['RELATED_TO_INV_DATE_TIME'] && $params['FM_NUM']);
                expect(dt::verbal2mysql($params['RELATED_TO_INV_DATE_TIME']));
                
                $js->removeBlock('OPEN_STORNO_RECEIPT_1');
                $js->removeBlock('OPEN_STORNO_RECEIPT_2');
            }
            
            setIfNot($params['STORNO_REASON'], 1);
            expect(($params['STORNO_REASON'] >= 0) && ($params['STORNO_REASON'] <= 2));
            expect(strlen($params['RELATED_TO_RCP_NUM']) <= 6);
            expect(strlen($params['FM_NUM']) == 8);
            
            $js->replace($params['STORNO_REASON'], 'STORNO_REASON');
            $js->replace($params['RELATED_TO_RCP_NUM'], 'RELATED_TO_RCP_NUM');
            $js->replace(json_encode($params['RELATED_TO_RCP_DATE_TIME']), 'RELATED_TO_RCP_DATE_TIME');
            $js->replace(json_encode($params['FM_NUM']), 'FM_NUM');
            
            setIfNot($params['RELATED_TO_URN'], $params['RCP_NUM'], 'null');
            $js->replace(json_encode($params['RELATED_TO_URN']), 'RELATED_TO_URN');
            
            if ($params['RELATED_TO_URN'] != 'null') {
                expect(preg_match($this->rcpNumPattern, $params['RELATED_TO_URN']));
            }
            
            $js->removeBlock('OPEN_FISC_RECEIPT_1');
            $js->removeBlock('OPEN_FISC_RECEIPT_2');
            
            if ($params['IS_CREDIT_NOTE']) {
                $js->replace(json_encode($params['RECIPIENT']), 'RECIPIENT');
                $js->replace(json_encode($params['BUYER']), 'BUYER');
                $js->replace(json_encode($params['VAT_NUMBER']), 'VAT_NUMBER');
                $js->replace(json_encode($params['UIC']), 'UIC');
                $js->replace(json_encode($params['ADDRESS']), 'ADDRESS');
                $js->replace(json_encode($params['UIC_TYPE_STR']), 'UIC_TYPE_STR');
                $js->replace(json_encode($params['RELATED_TO_INV_NUM']), 'RELATED_TO_INV_NUM');
                $js->replace(json_encode($params['RELATED_TO_INV_DATE_TIME']), 'RELATED_TO_INV_DATE_TIME');
            }
        }
        
        expect(($params['OPER_NUM'] >= 1) && ($params['OPER_NUM'] <= 20));
        expect(strlen($params['OPER_PASS']) <= 6);
        expect(($params['PRINT_TYPE_STR'] == 'stepByStep') || ($params['PRINT_TYPE_STR'] == 'postponed') || ($params['PRINT_TYPE_STR'] == 'buffered'));
        
        $js->replace($params['OPER_NUM'], 'OPER_NUM');
        $js->replace(json_encode($params['OPER_PASS']), 'OPER_PASS');
        $js->replace($params['IS_DETAILED'], 'IS_DETAILED');
        $js->replace($params['IS_PRINT_VAT'], 'IS_PRINT_VAT');
        $js->replace(json_encode($params['PRINT_TYPE_STR']), 'PRINT_TYPE_STR');
        
        $maxTextLen = ($pRec->fpType == 'fiscalPrinter') ? $this->fpLen : $this->crLen;
        $maxTextLen -= $this->mLen;
        
        // Добавяме продуктите към бележката
        foreach ($params['products'] as $pArr) {
            setIfNot($pArr['PRICE'], 0);
            setIfNot($pArr['VAT_CLASS'], 1);
            setIfNot($pArr['QTY'], 1);
            setIfNot($pArr['DISC_ADD_P'], '');
            setIfNot($pArr['DISC_ADD_V'], '');
            setIfNot($pArr['PLU_NAME'], '');
            
            expect(($pArr['VAT_CLASS'] >= 0) && ($pArr['VAT_CLASS'] <= 3));
            
            expect(is_numeric($pArr['PRICE']) && is_numeric($pArr['QTY']) && (is_numeric($pArr['DISC_ADD_P']) || $pArr['DISC_ADD_P'] == '') && (is_numeric($pArr['DISC_ADD_V']) || $pArr['DISC_ADD_V'] == ''));
            
            $fpSalePLU = $js->getBlock('fpSalePLU');
            
            $fpSalePLU->replace(json_encode($pArr['PLU_NAME']), 'PLU_NAME');
            $fpSalePLU->replace($pArr['VAT_CLASS'], 'VAT_CLASS');
            $fpSalePLU->replace(json_encode($pArr['PRICE']), 'PRICE');
            $fpSalePLU->replace($pArr['QTY'], 'QTY');
            $fpSalePLU->replace(json_encode($pArr['DISC_ADD_P']), 'DISC_ADD_P');
            $fpSalePLU->replace(json_encode($pArr['DISC_ADD_V']), 'DISC_ADD_V');
            
            if (isset($pArr['BEFORE_PLU_TEXT'])) {
                $this->replaceTextArr($pArr['BEFORE_PLU_TEXT'], $fpSalePLU, 'BEFORE_PLU_TEXT', true, $maxTextLen);
            }
            
            if (isset($pArr['AFTER_PLU_TEXT'])) {
                $this->replaceTextArr($pArr['AFTER_PLU_TEXT'], $fpSalePLU, 'AFTER_PLU_TEXT', true, $maxTextLen);
            }
            
            if (isset($pArr['DEP_NUM'])) {
                expect(is_numeric($pArr['DEP_NUM']) && ($pArr['DEP_NUM'] >= 0) && ($pArr['DEP_NUM'] <= 99));
            } else {
                $pArr['DEP_NUM'] = 'false';
            }
            
            $fpSalePLU->replace($pArr['DEP_NUM'], 'DEP_NUM');
            
            $fpSalePLU->removeBlocks();
            $fpSalePLU->append2master();
        }
        
        setIfNot($params['DATE_TIME'], false);
        
        if ($params['DATE_TIME'] === true) {
            $params['DATE_TIME'] = date('d-m-Y H:i:s');
        }
        
        if ($params['DATE_TIME'] !== false) {
            expect(dt::verbal2mysql($params['DATE_TIME']));
            $js->replace(json_encode($params['DATE_TIME']), 'DATE_TIME');
        } else {
            $js->removeBlock('DATE_TIME');
        }
        
        // Проверяваме серийния номер
        if ($params['SERIAL_NUMBER'] !== false) {
            setIfNot($params['SERIAL_NUMBER'], $pRec->serialNumber);
            
            if (!$params['SERIAL_NUMBER']) {
                list($params['SERIAL_NUMBER']) = explode('-', $params['RCP_NUM'], 2);
            }
            
            expect($params['SERIAL_NUMBER'] && (strlen($params['SERIAL_NUMBER']) == 8), $pRec, $params);
            $js->replace(json_encode($params['SERIAL_NUMBER']), 'SERIAL_NUMBER');
        } else {
            $js->removeBlock('SERIAL_NUMBER');
        }
        
        // Проверява за отворена бележка и ако има я прекратява преди да започне новата
        setIfNot($params['CHECK_AND_CANCEL_PREV_OPEN_RECEIPT'], true);
        if ($params['CHECK_AND_CANCEL_PREV_OPEN_RECEIPT'] === true) {
            $js->replace(' ', 'CHECK_AND_CANCEL_PREV_OPEN_RECEIPT');
        } else {
            $js->removeBlock('CHECK_AND_CANCEL_PREV_OPEN_RECEIPT');
        }
        
        if (isset($params['BEGIN_TEXT'])) {
            $this->replaceTextArr($params['BEGIN_TEXT'], $js, 'BEGIN_TEXT', false, $maxTextLen);
        }
        
        if (isset($params['END_TEXT'])) {
            $this->replaceTextArr($params['END_TEXT'], $js, 'END_TEXT', false, $maxTextLen);
        }
        
        // Добавяме начините на плащане
        if ($params['payments']) {
            foreach ($params['payments'] as $paymentArr) {
                $payment = $js->getBlock('PAYMENT');
                
                setIfNot($paymentArr['PAYMENT_TYPE'], 0);
                setIfNot($paymentArr['PAYMENT_CHANGE'], 0);
                setIfNot($paymentArr['PAYMENT_CHANGE_TYPE'], 0);
                
                expect(($paymentArr['PAYMENT_TYPE'] >= 0) && ($paymentArr['PAYMENT_TYPE'] <= 11));
                expect(($paymentArr['PAYMENT_CHANGE'] == 0) || ($paymentArr['PAYMENT_TYPE'] == 1));
                expect(($paymentArr['PAYMENT_CHANGE_TYPE'] >= 0) && ($paymentArr['PAYMENT_CHANGE_TYPE'] <= 2));
                
                expect($paymentArr['PAYMENT_AMOUNT']);
                
                $payment->replace($paymentArr['PAYMENT_TYPE'], 'PAYMENT_TYPE');
                $payment->replace($paymentArr['PAYMENT_CHANGE'], 'PAYMENT_CHANGE');
                $payment->replace($paymentArr['PAYMENT_CHANGE_TYPE'], 'PAYMENT_CHANGE_TYPE');
                $payment->replace(json_encode($paymentArr['PAYMENT_AMOUNT']), 'PAYMENT_AMOUNT');
                
                $payment->removeBlocks();
                $payment->append2master();
            }
        } else {
            $js->removeBlock('PAYMENT');
        }
        
        if (isset($params['PAY_EXACT_SUM_TYPE'])) {
            $js->replace($params['PAY_EXACT_SUM_TYPE'], 'PAY_EXACT_SUM_TYPE');
            expect(($paymentArr['PAY_EXACT_SUM_TYPE'] >= 0) && ($paymentArr['PAY_EXACT_SUM_TYPE'] <= 11));
        } else {
            $js->removeBlock('PAY_EXACT_SUM_TYPE');
        }
        
        $js = $js->getContent();
        
        // Минифициране на JS
        $js = minify_Js::process($js);
        
        return $js;
    }
    
    
    /**
     * Връща JS функция, която да провери дали има връзка с устройството
     * При успех вика `fpOnConnectionSuccess`, а при грешка fpOnConnectionErr
     *
     * @param stdClass $pRec - запис от peripheral_Devices
     *
     * @return string
     *
     * @see peripheral_FiscPrinterWeb
     */
    public function getJsIsWorking($pRec)
    {
        $jsTpl = new ET('[#/tremol/js/FiscPrinterTplFileImportBegin.txt#]
                                try {
                                    [#/tremol/js/FiscPrinterTplConnect.txt#]
                                    fpSerialNumber();
                                    fpOnConnectionSuccess();
                                } catch(ex) {
                                    fpOnConnectionErr(ex.message);
                                }
                            [#/tremol/js/FiscPrinterTplFileImportEnd.txt#]');
        
        $this->addTplFile($jsTpl, $pRec->driverVersion);
        $this->connectToPrinter($jsTpl, $pRec, false);
        
        $js = $jsTpl->getContent();
        
        // Минифициране на JS
        $js = minify_Js::process($js);
        
        return $js;
    }
    
    
    /**
     * Връща JS функция, за отпечатване на дубликат
     * При успех вика `fpOnDuplicateSuccess`, а при грешка fpOnDuplicateErr
     *
     * @param stdClass $pRec - запис от peripheral_Devices
     *
     * @return string
     *
     * @see peripheral_FiscPrinterWeb
     */
    public function getJsForDuplicate($pRec)
    {
        $jsTpl = new ET('[#/tremol/js/FiscPrinterTplFileImportBegin.txt#]
                                try {
                                    [#/tremol/js/FiscPrinterTplConnect.txt#]
                                    fpPrintLastReceiptDuplicate();
                                    fpOnDuplicateSuccess();
                                } catch(ex) {
                                    fpOnDuplicateErr(ex.message);
                                }
                            [#/tremol/js/FiscPrinterTplFileImportEnd.txt#]');
        
        $this->addTplFile($jsTpl, $pRec->driverVersion);
        $this->connectToPrinter($jsTpl, $pRec, false);
        
        $js = $jsTpl->getContent();
        
        // Минифициране на JS
        $js = minify_Js::process($js);
        
        return $js;
    }
    
    
    /**
     * Връща JS функция за добавяне/изкарване на пари от касата
     *
     * @param stdClass $pRec
     * @param int      $operNum
     * @param string   $operPass
     * @param float    $amount - ако е минус - изкрва пари, а с плюс - вкарва
     * @param boolean  $printAvailability
     * @param string   $text
     *
     * @return string
     *
     * @see peripheral_FiscPrinterWeb
     */
    public function getJsForCashReceivedOrPaidOut($pRec, $operNum, $operPass, $amount, $printAvailability = false, $text = '')
    {
        expect(($operNum >= 1) && ($operNum <= 20));
        $jsTpl = new ET('[#/tremol/js/FiscPrinterTplFileImportBegin.txt#]
                                try {
                                    [#/tremol/js/FiscPrinterTplConnect.txt#]
                                    fp.ReceivedOnAccount_PaidOut([#OPER_NUM#], [#OPER_PASS#], [#AMOUNT#], [#PRINT_AVAILABILITY#], [#TEXT#]);
                                    fpOnCashReceivedOrPaidOut();
                                } catch(ex) {
                                    fpOnCashReceivedOrPaidOutErr(ex.message);
                                }
                            [#/tremol/js/FiscPrinterTplFileImportEnd.txt#]');
        
        $this->addTplFile($jsTpl, $pRec->driverVersion);
        $this->connectToPrinter($jsTpl, $pRec, false);
        
        if ($printAvailability) {
            $printAvailability = 1;
        } else {
            $printAvailability = 0;
        }
        
        $jsTpl->replace(json_encode($operNum), 'OPER_NUM');
        $jsTpl->replace(json_encode($operPass), 'OPER_PASS');
        $jsTpl->replace(json_encode($amount), 'AMOUNT');
        $jsTpl->replace(json_encode($printAvailability), 'PRINT_AVAILABILITY');
        $jsTpl->replace(json_encode($text), 'TEXT');
        
        $js = $jsTpl->getContent();
        
        // Минифициране на JS
        $js = minify_Js::process($js);
        
        return $js;
    }
    
    
    /**
     * Помощна функция за добавяне на необходимите JS файлове
     *
     * @param core_ET $tpl
     * @param string $driverVersion
     */
    protected function addTplFile(&$tpl, $driverVersion)
    {
        if (!$driverVersion) {
            $driverVersion = '19.03.22';
        }
        
        // Добавяме необходимите JS файлове
        $tpl->replace(sbf("tremol/libs/{$driverVersion}/fp_core.js"), 'FP_CORE_JS');
        $tpl->replace(sbf("tremol/libs/$driverVersion/fp.js"), 'FP_JS');
        $tpl->replace(sbf("tremol/js/fiscPrinter.js"), 'FISC_PRINT_JS');
    }
    
    
    /**
     * Помощна фунцкия за връзка с принтер
     *
     * @param core_ET   $tpl
     * @param stdClass  $pRec
     * @param null|bool $serialKeepPortOpen
     * @param boolean $setDeviceSettings
     */
    protected function connectToPrinter($tpl, $pRec, $serialKeepPortOpen = null, $setDeviceSettings = true)
    {
        // Задаваме настройките за връзка със сървъра
        $tpl->replace(json_encode($pRec->serverIp), 'SERVER_IP');
        $tpl->replace(json_encode($pRec->serverTcpPort), 'SERVER_TCP_PORT');
        
        if ($setDeviceSettings) {
            // Свързваме се с ФП
            if ($pRec->type == 'tcp') {
                $tpl->replace(json_encode($pRec->tcpIp), 'TCP_IP');
                $tpl->replace($pRec->tcpPort, 'TCP_PORT');
                $tpl->replace(json_encode($pRec->tcpPass), 'TCP_PASS');
                
                $tpl->replace('false', 'SERIAL_PORT');
                $tpl->replace('false', 'SERIAL_BAUD_RATE');
                $tpl->replace('false', 'SERIAL_KEEP_PORT_OPEN');
            } elseif ($pRec->type == 'serial') {
                $tpl->replace('false', 'TCP_IP');
                $tpl->replace('false', 'TCP_PORT');
                $tpl->replace('false', 'TCP_PASS');
                $tpl->replace(json_encode($pRec->serialPort), 'SERIAL_PORT');
                $tpl->replace($pRec->serialSpeed, 'SERIAL_BAUD_RATE');
                
                setIfNot($serialKeepPortOpen, 'true');
                if ($serialKeepPortOpen) {
                    $serialKeepPortOpen = 'true';
                } else {
                    $serialKeepPortOpen = 'false';
                }
                
                $tpl->replace($serialKeepPortOpen, 'SERIAL_KEEP_PORT_OPEN');
            } else {
                expect(false, $pRec);
            }
        }
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
        $jsTpl = new ET("[#/tremol/js/FiscPrinterTplFileImportBegin.txt#]
                                try {
                                    fpServerSetSettings([#SERVER_IP#], [#SERVER_TCP_PORT#]);
                                    var res = fpServerFindDevice(false);
                                    if (res.serialPort && res.baudRate) {
                                        $('.serialSpeedInput').val(res.baudRate);
                                    }
                                    
                                    if (res.serialPort) {
                                        $('.serialPortInput').val(res.serialPort);
                                    }
                                } catch(ex) { }
                            [#/tremol/js/FiscPrinterTplFileImportEnd.txt#]");
        
        $this->addTplFile($jsTpl, $pRec->driverVersion);
        $this->connectToPrinter($jsTpl, $pRec, false, false);
        
        return array();
    }
    
    
    /**
     * Помощна фунцкия за заместване на плейсхолдерите за текст
     *
     * @param array|string $tArr
     * @param core_ET      $jTpl
     * @param string       $placeName
     * @param boolean       $removeBlock
     * @param integer       $maxLen
     */
    protected function replaceTextArr($tArr, &$jTpl, $placeName, $removeBlock = false, $maxLen = 30)
    {
        $resStrArr = $this->parseTextToArr($tArr, $maxLen);
        
        if (!empty($resStrArr)) {
            foreach ($resStrArr as $str) {
                $bTpl = $jTpl->getBlock($placeName);
                
                $bTpl->replace(json_encode($str), $placeName);
                
                $bTpl->removeBlocks();
                $bTpl->append2master();
            }
            
            if ($removeBlock) {
                unset($jTpl->blocks[$placeName]);
            }
        }
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
        $update = Request::get('update');
        
        if ($update !== 0) {
            if ($Embedder instanceof peripheral_Devices && $Embedder->haveRightFor('edit', $data->rec->id)) {
                $jsTpl = new ET("[#/tremol/js/FiscPrinterTplFileImportBegin.txt#]
                                try {
                                    [#/tremol/js/FiscPrinterTplConnect.txt#]
                                
                                    var sn = fpSerialNumber();
                                    
                                    [#OTHER#]
                                } catch(ex) {
                                    render_showToast({timeOut: 800, text: '" . tr('Грешка при свързване с принтера') . ": ' + ex.message, isSticky: true, stayTime: 8000, type: 'warning'});
                                }
                            [#/tremol/js/FiscPrinterTplFileImportEnd.txt#]");
                
                // След запис, обновяваме хедър и футъра
                if ($update) {
                    if (($update == 'department') || ($update == '1')) {
                        // Вземаме начините на плащане от ФУ
                        $setDepartments = toUrl(array($Driver, 'SetDepartments', $data->rec->id), 'local');
                        $setDepartments = urlencode($setDepartments);
                        $updateSn = "try {
                                     var depArr = fpGetDepArr();
                                     depArr = JSON.stringify(depArr);
                                     getEfae().process({url: '{$setDepartments}'}, {depArr: depArr});
                                 } catch(ex) {
                                     render_showToast({timeOut: 800, text: '" . tr('Грешка при добавяне на плащания') . ": ' + ex.message, isSticky: true, stayTime: 8000, type: 'notice'});
                                 }";
                        
                        $jsTpl->prepend($updateSn, 'OTHER');
                        
                    }
                    
                    if (($update == 'sn') || ($update == '1')) {
                        // Вземаме серийния номер от ФУ
                        $setSerialUrl = toUrl(array($Driver, 'setSerialNumber', $data->rec->id), 'local');
                        $setSerialUrl = urlencode($setSerialUrl);
                        $updateSn = "try {
                                     getEfae().process({url: '{$setSerialUrl}'}, {serial: sn});
                                 } catch(ex) {
                                     render_showToast({timeOut: 800, text: '" . tr('Грешка при обновяване на серийния номер') . ": ' + ex.message, isSticky: true, stayTime: 8000, type: 'notice'});
                                 }";
                        $jsTpl->prepend($updateSn, 'OTHER');
                    }
                    
                    if (($update == 'opass') || ($update == '1')) {
                        // Вземаме паролата от ФУ
                        $setOperPassUrl = toUrl(array($Driver, 'SetOperPass', $data->rec->id), 'local');
                        $setOperPassUrl = urlencode($setOperPassUrl);
                        $updateSn = "try {
                                     var operPass = fpGetOperPass();
                                     getEfae().process({url: '{$setOperPassUrl}'}, {operPass: operPass});
                                 } catch(ex) {
                                     render_showToast({timeOut: 800, text: '" . tr('Грешка при промяна на парола за връзка с ФУ') . ": ' + ex.message, isSticky: true, stayTime: 8000, type: 'notice'});
                                 }";
                        
                        $jsTpl->prepend($updateSn, 'OTHER');
                    }
                    
                    if (($update == 'payments') || ($update == '1')) {
                        // Вземаме начините на плащане от ФУ
                        $setDefaultPaymenst = toUrl(array($Driver, 'SetDefPayments', $data->rec->id), 'local');
                        $setDefaultPaymenst = urlencode($setDefaultPaymenst);
                        $updateSn = "try {
                                     var defPaym = fpGetDefPayments();
                                     defPaym = JSON.stringify(defPaym);
                                     getEfae().process({url: '{$setDefaultPaymenst}'}, {defPaym: defPaym});
                                 } catch(ex) {
                                     render_showToast({timeOut: 800, text: '" . tr('Грешка при добавяне на плащания') . ": ' + ex.message, isSticky: true, stayTime: 8000, type: 'notice'});
                                 }";
                        
                        $jsTpl->prepend($updateSn, 'OTHER');
                    }
                    
                    if (($update == 'date') || ($update == '1')) {
                        // Сверяваме времето
                        $now = json_encode(date('d-m-Y H:i:s'));
                        $updateTime = "try {
                                    fpSetDateTime({$now});
                                } catch(ex) {
                                    render_showToast({timeOut: 800, text: '" . tr('Не може да се синхронизира времето') . ": ' + ex.message, isSticky: false, stayTime: 12000, type: 'warning'});
                                }";
                        $jsTpl->prepend($updateTime, 'OTHER');
                    }
                    
                    if (($update == 'hf') || ($update == '1')) {
                        // Нулираме другихте хедъри
                        $headersTextStr = '';
                        
                        $maxTextLen = ($data->rec->fpType == 'fiscalPrinter') ? $Driver->fpLen : $Driver->crLen;
                        
                        if ($data->rec->header == 'yes') {
                            for ($i = 1; $i <= 7; $i++) {
                                $h = headerText . $i;
                                $ht = (string) $data->rec->{$h};
                                $ht = self::formatText($ht, $data->rec->headerPos, $maxTextLen);
                                $ht = json_encode($ht);
                                $headersTextStr .= "fpProgramHeader({$ht}, {$i});";
                            }
                        }
                        $footerTextStr = '';
                        if ($data->rec->footer == 'yes') {
                            $ft = (string) $data->rec->footerText;
                            $ft = self::formatText($ft, $data->rec->footerPos, $maxTextLen);
                            $ft = json_encode($ft);
                            $footerTextStr = "fpProgramFooter({$ft});";
                        }
                        
                        if ($headersTextStr || $footerTextStr) {
                            $headerText = "try {
                                        {$headersTextStr}
                                    } catch(ex) {
                                        render_showToast({timeOut: 800, text: '" . tr('Грешка при програмиране на хедъра на устройството') . ": ' + ex.message, isSticky: true, stayTime: 8000, type: 'warning'});
                                    }
                                    
                                    try {
                                        {$footerTextStr}
                                    } catch(ex) {
                                        render_showToast({timeOut: 800, text: '" . tr('Грешка при програмиране на футъра на устройството') . ": ' + ex.message, isSticky: true, stayTime: 8000, type: 'warning'});
                                    }";
                                        $jsTpl->append($headerText, 'OTHER');
                        }
                    }
                }
                
                $Driver->addTplFile($jsTpl, $data->rec->driverVersion);
                $Driver->connectToPrinter($jsTpl, $data->rec, false);
                
                $jsTpl->removePlaces();
                
                jquery_Jquery::run($tpl, $jsTpl);
            }
        }
    }
    
    
    /**
     * Екшън за промяна на серийния номер
     *
     * @return array
     */
    public function act_SetSerialNumber()
    {
        expect(Request::get('ajax_mode'));
        
        peripheral_Devices::requireRightFor('single');
        
        $serial = Request::get('serial');
        $id = Request::get('id', 'int');
        
        expect($id);
        
        $pRec = peripheral_Devices::fetch($id);
        
        expect($pRec);
        
        peripheral_Devices::requireRightFor('single', $id);
        peripheral_Devices::requireRightFor('edit', $id);
        
        $res = array();
        
        if ($pRec->serialNumber != $serial) {
            $oldSerial = $pRec->serialNumber;
            $pRec->serialNumber = $serial;
            
            $statusData = array();
            
            if (peripheral_Devices::save($pRec)) {
                if (trim($oldSerial)) {
                    $statusData['text'] = tr('Променен сериен номер от') . " {$oldSerial} " . tr('на') . " {$serial}";
                } else {
                    $statusData['text'] = tr('Добавен сериен номер');
                }
                
                $statusData['type'] = 'notice';
                $statusData['timeOut'] = 700;
                $statusData['isSticky'] = 0;
                $statusData['stayTime'] = 8000;
            } else {
                $statusData['text'] = tr('Грешка при промяна на сериен номер');
                $statusData['type'] = 'error';
                $statusData['timeOut'] = 700;
                $statusData['isSticky'] = 1;
                $statusData['stayTime'] = 15000;
            }
            
            $statusObj = new stdClass();
            $statusObj->func = 'showToast';
            $statusObj->arg = $statusData;
            
            $res[] = $statusObj;
        }
        
        return $res;
    }
    
    
    /**
     * Екшън за промяна на паролата на оператора за връзка с ФУ
     *
     * @return array
     */
    public function act_SetOperPass()
    {
        expect(Request::get('ajax_mode'));
        
        peripheral_Devices::requireRightFor('single');
        
        $operPass = Request::get('operPass');
        $id = Request::get('id', 'int');
        
        expect($id);
        
        $pRec = peripheral_Devices::fetch($id);
        
        expect($pRec);
        
        peripheral_Devices::requireRightFor('single', $id);
        peripheral_Devices::requireRightFor('edit', $id);
        
        $res = array();
        
        if ($pRec->otherData['operPass'] != $operPass) {
            $pRec->otherData['operPass'] = $operPass;
            
            peripheral_Devices::save($pRec, 'otherData');
        }
        
        return $res;
    }
    
    
    /**
     * Екшън за добавяне на плащания по подразбиране
     *
     * @return array
     */
    public function act_SetDefPayments()
    {
        expect(Request::get('ajax_mode'));
        
        peripheral_Devices::requireRightFor('single');
        
        $operPass = Request::get('operPass');
        $id = Request::get('id', 'int');
        
        expect($id);
        
        $pRec = peripheral_Devices::fetch($id);
        
        expect($pRec);
        
        peripheral_Devices::requireRightFor('single', $id);
        peripheral_Devices::requireRightFor('edit', $id);
        
        $defPaym = Request::get('defPaym');
        
        $dPaymArr = json_decode($defPaym, true);
        
        if ($dPaymArr['defPaymArr']) {
            $pRec->otherData['defPaymArr'] = $dPaymArr['defPaymArr'];
            $pRec->otherData['exRate'] = $dPaymArr['exRate'];
            
            peripheral_Devices::save($pRec, 'otherData');
        }
        
        return array();
    }
    
    
    /**
     * Екшън за добавяне на департаменти
     *
     * @return array
     */
    public function act_SetDepartments()
    {
        expect(Request::get('ajax_mode'));
        
        peripheral_Devices::requireRightFor('single');
        
        $operPass = Request::get('operPass');
        $id = Request::get('id', 'int');
        
        expect($id);
        
        $pRec = peripheral_Devices::fetch($id);
        
        expect($pRec);
        
        peripheral_Devices::requireRightFor('single', $id);
        peripheral_Devices::requireRightFor('edit', $id);
        
        $depArr = Request::get('depArr');
        
        $depArr = json_decode($depArr, true);
        
        if ($depArr) {
            $pRec->otherData['depArr'] = $depArr;
            
            peripheral_Devices::save($pRec, 'otherData');
        }
        
        return array();
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
        
        // Опит за отпечатване на дубликат на касовата бележка
        $retUrl = toUrl(getRetUrl());
        $js =  $Driver->getJsForDuplicate($pRec);
        $js .= 'function fpOnDuplicateSuccess(res)
                        {
                            render_showToast({timeOut: 800, text: "Успешно отпечатване", isSticky: true, stayTime: 8000, type: "notice"});
                            setInterval(function(){document.location = " ' . $retUrl . ' ";}, 7000);
                        };
                                
                        function fpOnDuplicateErr(err) {
                            render_showToast({timeOut: 800, text: err, isSticky: true, stayTime: 8000, type: "error"});
                            setInterval(function(){document.location = " ' . $retUrl . ' ";}, 7000);
                        }';
        
        $tpl = new core_ET("");
        Mode::set('wrapper', 'page_Empty');
        $tpl = new core_ET('');
        $tpl->append('<body><div class="fullScreenBg" style="position: fixed; top: 0; z-index: 1002; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9);display: block;"><h3 style="color: #fff; font-size: 56px; text-align: center; position: absolute; top: 30%; width: 100%">Отпечатва се дубликат на фискален бон ...<br> Моля, изчакайте!</h3></div></body>');
        $tpl->append($js, 'SCRIPTS');
        
        return $tpl;
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
        $jsFunc = $this->getJsForCashReceivedOrPaidOut($pRec, $operator, $operPass, $amount, $printAvailability, $text);
        
        $retUrlDecoded = toUrl($retUrl);
        
        $jsTpl = new ET("function fpOnCashReceivedOrPaidOut() {render_redirect({url: '{$retUrlDecoded}'}); };
                             function fpOnCashReceivedOrPaidOutErr(message) { $('.fullScreenBg').fadeOut(); render_showToast({timeOut: 800, text: '" . tr('Грешка при') . ' ' . tr($actTypeVerb) . ' ' . tr('във ФУ') . ": ' + message, isSticky: true, stayTime: 8000, type: 'error'});}");
        
        $jsTpl->prepend('$(\'body\').append(\'<div class="fullScreenBg" style="position: fixed; top: 0; z-index: 10; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9);display: none;"></div>\'); $(".fullScreenBg").fadeIn();');
        
        $jsTpl->append($jsFunc);
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
        $fnc = '';
        
        $isDetailed = 'false';
        if ($rec->isDetailed == 'yes') {
            $isDetailed = 'true';
        }
        
        $isZeroing = 'false';
        if ($rec->zeroing == 'yes') {
            $isZeroing = 'true';
        }
        
        if ($rec->report == 'day') {
            $fnc = "fpDayReport({$isZeroing},{$isDetailed})";
        }
        
        if ($rec->report == 'operator') {
            $operator = (int) $rec->operNum;
            $fnc = "fpOperatorReport({$isZeroing}, {$operator})";
        }
        
        if (($rec->report == 'period') || ($rec->report == 'month') || ($rec->report == 'year') || ($rec->report == 'klen') || ($rec->report == 'csv')) {
            $fromDate = json_encode(dt::mysql2verbal($rec->fromDate, 'd-m-Y H:i:s'));
            $toDate = json_encode(dt::mysql2verbal($rec->toDate . ' 23:59:59', 'd-m-Y H:i:s'));
            $fnc = "fpPeriodReport({$fromDate}, {$toDate}, {$isDetailed})";
            
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
                $outType = json_encode($outType);
                
                if ($rec->report == 'csv') {
                    $csvFormat = json_encode($rec->csvFormat);
                    $fnc = "fpOutputCSV({$outType}, {$fromDate}, {$toDate}, {$csvFormat}, {$rec->flagReceipts}, {$rec->flagReports})";
                } else {
                    $fnc = "fpOutputKLEN({$outType}, {$fromDate}, {$toDate}, {$isDetailed})";
                }
            }
        }
        
        expect($fnc);
        
        $fnc .= ';';
        
        if (!empty($retUrl)) {
            $toUrul = toUrl($retUrl);
            $location = "document.location = '{$toUrul}';";
        } else {
            $location = '';
        }
        
        $jsTpl = new ET("function fpPrintReport() {
                                $('.fullScreenBg').fadeIn();
                                [#/tremol/js/FiscPrinterTplFileImportBegin.txt#]
                                try {
                                    [#/tremol/js/FiscPrinterTplConnect.txt#]
                                    {$fnc}
                                    {$location}
                                } catch(ex) {
                                    render_showToast({timeOut: 800, text: '" . tr("Грешка при отпечатване на {$rVerb} отчет") . ": ' + ex.message, isSticky: true, stayTime: 8000, type: 'error'});
                                }
                                [#/tremol/js/FiscPrinterTplFileImportEnd.txt#]
                                $('.fullScreenBg').fadeOut();
                            }
                                                    
                            fpPrintReport();");
        
        $jsTpl->prepend('$(\'body\').append(\'<div class="fullScreenBg" style="position: fixed; top: 0; z-index: 10; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9);display: none;"></div>\');');
        
        $this->addTplFile($jsTpl, $pRec->driverVersion);
        $this->connectToPrinter($jsTpl, $pRec, false);
    }
}
