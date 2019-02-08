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
class tremol_FiscPrinterDriver2 extends core_Mvc
{
    public $interfaces = 'peripheral_DeviceIntf, peripheral_FiscPrinter';
    
    public $title = 'Принтер на тремол';
    
    protected $canMakeZReport = 'admin, peripheral';
    
    protected $canMakeXReport = 'admin, peripheral';
    
    protected $rcpNumPattern = '/^[a-z0-9]{8}-[a-z0-9]{4}-[0-9]{7}$/i';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('serverIp', 'ip', 'caption=Сървър->IP адрес, mandatory');
        $fieldset->FLD('serverTcpPort', 'int', 'caption=Сървър->TCP порт, mandatory');
        $fieldset->FLD('type', 'enum(tcp=TCP връзка, serial=Сериен порт)', 'caption=ФУ->Връзка, mandatory, notNull, removeAndRefreshForm=tcpIp|tcpPort|tcpPass|serialPort|serialSpeed');
        
        $fieldset->FLD('serialNumber', 'varchar(8)', 'caption=ФУ->Сериен номер');
        
        $fieldset->FLD('tcpIp', 'ip', 'caption=TCP->IP адрес, mandatory');
        $fieldset->FLD('tcpPort', 'int', 'caption=TCP->TCP порт, mandatory');
        $fieldset->FLD('tcpPass', 'password', 'caption=TCP->Парола, mandatory');
        
        $fieldset->FLD('serialPort', 'varchar', 'caption=Сериен->Порт, mandatory');
        $fieldset->FLD('serialSpeed', 'int', 'caption=Сериен->Скорост, mandatory');
        
        if ($fieldset instanceof core_Form) {
            $fieldset->input('type');
            
            if ($fieldset->rec->type != 'serial') {
                $fieldset->setField('serialPort', 'input=none');
                $fieldset->setField('serialSpeed', 'input=none');
            } else {
                $fieldset->setField('tcpIp', 'input=none');
                $fieldset->setField('tcpPort', 'input=none');
                $fieldset->setField('tcpPass', 'input=none');
            }
        }
    }
    
    
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
     *
     * DATE_TIME - времето за синхронизира във формат 'd-m-Y H:i:s'. Ако е false - няма да се синхронизира
     *
     * SERIAL_NUMBER - серийния номер на принтера за проверка. Ако е false - няма да се проверява. Ако има разминаване - спира процеса.
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
     * @return string
     *
     * @see peripheral_FiscPrinter
     */
    public function getJs($pRec, $params)
    {
        // Шаблона за JS
        $js = getTplFromFile('/tremol/js/fiscPrintTpl.txt');
        
        $this->addTplFile($js);
        
        $this->connectToPrinter($js, $pRec, $params['SERIAL_KEEP_PORT_OPEN']);
        
        // Задаваме параметрите за отваряне на ФБ
        setIfNot($params['OPER_NUM'], 1);
        setIfNot($params['OPER_PASS'], 0);
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
        
        if (!$params['IS_STORNO']) {
            expect($params['RCP_NUM'] && preg_match($this->rcpNumPattern, $params['RCP_NUM']));
            $js->replace(json_encode($params['RCP_NUM']), 'RCP_NUM');
            
            $js->removeBlock('OPEN_STORNO_RECEIPT_1');
            $js->removeBlock('OPEN_STORNO_RECEIPT_2');
        } else {
            
            // Ако ще се прави сторно
            
            // Опитваме се да попълним няко от задължителните параметри
            if ($params['QR_CODE_DATA'] && (!$params['RELATED_TO_RCP_NUM'] || !$params['RELATED_TO_RCP_DATE_TIME'] || !$params['FM_NUM'])) {
                list($fmNum, $toRcpNum, $toRcpDate, $toRcpTime) = explode('*', $params['QR_CODE_DATA']);
                
                setIfNot($params['FM_NUM'], $fmNum);
                setIfNot($params['RELATED_TO_RCP_NUM'], (int) $toRcpNum);
                
                $toRcpDateAndTime = $toRcpDate . ' ' . $toRcpTime;
                $toRcpDateAndTime = dt::mysql2verbal($toRcpDateAndTime, 'd-m-Y H:i:s', null, false, false);
                setIfNot($params['RELATED_TO_RCP_DATE_TIME'], $toRcpDateAndTime);
            }
            
            expect($params['RELATED_TO_RCP_NUM'] && $params['RELATED_TO_RCP_DATE_TIME'] && $params['FM_NUM']);
            
            setIfNot($params['STORNO_REASON'], 1);
            expect(($params['STORNO_REASON'] >= 0) && ($params['STORNO_REASON'] <= 2));
            expect(strlen($params['RELATED_TO_RCP_NUM']) <= 6);
            expect(dt::verbal2mysql($params['RELATED_TO_RCP_DATE_TIME']));
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
        }
        
        expect(($params['OPER_NUM'] >= 1) && ($params['OPER_NUM'] <= 20));
        expect(strlen($params['OPER_PASS']) <= 6);
        expect(($params['PRINT_TYPE_STR'] == 'stepByStep') || ($params['PRINT_TYPE_STR'] == 'postponed') || ($params['PRINT_TYPE_STR'] == 'buffered'));
        
        $js->replace($params['OPER_NUM'], 'OPER_NUM');
        $js->replace(json_encode($params['OPER_PASS']), 'OPER_PASS');
        $js->replace($params['IS_DETAILED'], 'IS_DETAILED');
        $js->replace($params['IS_PRINT_VAT'], 'IS_PRINT_VAT');
        $js->replace(json_encode($params['PRINT_TYPE_STR']), 'PRINT_TYPE_STR');
        
        // Добавяме продуктите към бележката
        foreach ($params['products'] as $pArr) {
            setIfNot($pArr['PRICE'], 0);
            setIfNot($pArr['VAT_CLASS'], 1);
            setIfNot($pArr['QTY'], 1);
            setIfNot($pArr['DISC_ADD_P'], 0);
            setIfNot($pArr['DISC_ADD_V'], 0);
            setIfNot($pArr['PLU_NAME'], '');
            
            expect(($pArr['VAT_CLASS'] >= 0) && ($pArr['VAT_CLASS'] <= 3));
            
            expect(is_numeric($pArr['PRICE']) && is_numeric($pArr['QTY']) && is_numeric($pArr['DISC_ADD_P']) && is_numeric($pArr['DISC_ADD_V']));
            
            $fpSalePLU = $js->getBlock('fpSalePLU');
            
            $fpSalePLU->replace(json_encode($pArr['PLU_NAME']), 'PLU_NAME');
            $fpSalePLU->replace($pArr['VAT_CLASS'], 'VAT_CLASS');
            $fpSalePLU->replace(json_encode($pArr['PRICE']), 'PRICE');
            $fpSalePLU->replace($pArr['QTY'], 'QTY');
            $fpSalePLU->replace($pArr['DISC_ADD_P'], 'DISC_ADD_P');
            $fpSalePLU->replace($pArr['DISC_ADD_V'], 'DISC_ADD_V');
            
            if (isset($pArr['BEFORE_PLU_TEXT'])) {
                $this->replaceTextArr($pArr['BEFORE_PLU_TEXT'], $fpSalePLU, 'BEFORE_PLU_TEXT', true);
            }
            
            if (isset($pArr['AFTER_PLU_TEXT'])) {
                $this->replaceTextArr($pArr['AFTER_PLU_TEXT'], $fpSalePLU, 'AFTER_PLU_TEXT', true);
            }
            
            $fpSalePLU->removeBlocks();
            $fpSalePLU->append2master();
        }
        
        // Синхронизираме времената
        setIfNot($params['DATE_TIME'], date('d-m-Y H:i:s'));
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
        
        if (isset($params['BEGIN_TEXT'])) {
            $this->replaceTextArr($params['BEGIN_TEXT'], $js, 'BEGIN_TEXT');
        }
        
        if (isset($params['END_TEXT'])) {
            $this->replaceTextArr($params['END_TEXT'], $js, 'END_TEXT');
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
     * Помощна функция за добавяне на необходимите JS файлове
     *
     * @param core_ET $tpl
     */
    protected function addTplFile(&$tpl)
    {
        // Добавяме необходимите JS файлове
        $tpl->replace(sbf('tremol/js/' . tremol_Setup::get('FP_DRIVER_VERSION') . '/fp_core.js'), 'FP_CORE_JS');
        $tpl->replace(sbf('tremol/js/' . tremol_Setup::get('FP_DRIVER_VERSION') . '/fp.js'), 'FP_JS');
        $tpl->replace(sbf('tremol/js/fiscPrinter.js'), 'FISC_PRINT_JS');
    }
    
    
    /**
     * Помощна фунцкия за връзка с принтер
     *
     * @param core_ET   $tpl
     * @param stdClass  $pRec
     * @param null|bool $serialKeepPortOpen
     */
    protected function connectToPrinter($tpl, $pRec, $serialKeepPortOpen = null)
    {
        // Задаваме настройките за връзка със сървъра
        $tpl->replace(json_encode($pRec->serverIp), 'SERVER_IP');
        $tpl->replace(json_encode($pRec->serverTcpPort), SERVER_TCP_PORT);
        
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
    
    
    /**
     * Помощна фунцкия за заместване на плейсхолдерите за текст
     *
     * @param array|string $tArr
     * @param core_ET      $jTpl
     * @param string       $placeName
     */
    protected function replaceTextArr($tArr, &$jTpl, $placeName, $removeBlock = false)
    {
        $resStrArr = array();
        if (!is_array($tArr)) {
            $tArr = array($tArr);
        }
        
        foreach ($tArr as $tStr) {
            $tStr = hyphen_Plugin::getHyphenWord($tStr, 25, 30, '<wbr>');
            
            $resStrArr = array_merge($resStrArr, explode('<wbr>', $tStr));
        }
        
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
     * @param tremol_FiscPrinterDriver2 $Driver
     * @param peripheral_Devices        $Embedder
     * @param core_ET                   $tpl
     * @param stdClass                  $data
     */
    protected static function on_AfterRenderSingle(tremol_FiscPrinterDriver2 $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        if ($Embedder instanceof peripheral_Devices && $Embedder->haveRightFor('edit', $data->rec->id)) {
            $setSerialUrl = toUrl(array($Driver, 'setSerialNumber', $data->rec->id), 'local');
            $setSerialUrl = urlencode($setSerialUrl);
            
            $jsTpl = new ET("[#/tremol/js/FiscPrinterTplFileImportBegin.txt#]
                                try {
                                    [#/tremol/js/FiscPrinterTplConnect.txt#]
                                    getEfae().process({url: '{$setSerialUrl}'}, {serial: fpSerialNumber()});
                                } catch(ex) {
                                    render_showToast({timeOut: 800, text: '" . tr('Грешка при свързване с принтера') . ": ' + ex.message, isSticky: true, stayTime: 8000, type: 'warning'});
                                }
                            [#/tremol/js/FiscPrinterTplFileImportEnd.txt#]");
            
            $Driver->addTplFile($jsTpl);
            $Driver->connectToPrinter($jsTpl, $data->rec, false);
            
            jquery_Jquery::run($tpl, $jsTpl);
        }
    }
    
    
    /**
     * Екшън за промяна на серийния номер
     *
     * @return array|string
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
     * След подготовка на тулбара на единичен изглед
     *
     * @param tremol_FiscPrinterDriver2 $Driver
     * @param peripheral_Devices        $mvc
     * @param object                    $res
     * @param object                    $data
     */
    public static function on_AfterPrepareSingleToolbar($Driver, $mvc, &$res, $data)
    {
        if (haveRole($Driver->canMakeXReport)) {
            $data->toolbar->addFnBtn('X отчет', 'fpPrintXReport()', 'ef_icon = img/16/report.png,title=Отпечатване на X отчет');
            $data->_Xreport = true;
        }
        
        if (haveRole($Driver->canMakeZReport)) {
            $data->toolbar->addFnBtn('Z отчет', 'fpPrintZReport()', 'ef_icon = img/16/report.png,title=Отпечатване на Z отчет, row=2', array('warning' => 'Сигурни ли сте, че искате да нулирате касовия апарат?'));
            $data->_Zreport = true;
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     *
     * @param tremol_FiscPrinterDriver2 $Driver
     * @param peripheral_Devices        $mvc
     * @param core_ET                   $tpl
     * @param object                    $data
     */
    public static function on_AfterRenderSingleToolbar($Driver, $mvc, &$tpl, $data)
    {
        if ($data->_Xreport) {
            $jsTpl = new ET("function fpPrintXReport() {
                                [#/tremol/js/FiscPrinterTplFileImportBegin.txt#]
                                try {
                                    [#/tremol/js/FiscPrinterTplConnect.txt#]
                                    fpXReport();
                                    render_showToast({timeOut: 800, text: '" . tr('Успешно отпечатан X отчет') . "', isSticky: false, stayTime: 8000, type: 'notice'});
                                } catch(ex) {
                                    render_showToast({timeOut: 800, text: '" . tr('Грешка при отпечатване на X отчет') . ": ' + ex.message, isSticky: true, stayTime: 8000, type: 'error'});
                                }
                                [#/tremol/js/FiscPrinterTplFileImportEnd.txt#]
                            }");
            
            $Driver->addTplFile($jsTpl);
            $Driver->connectToPrinter($jsTpl, $data->rec, false);
            
            $tpl->appendOnce($jsTpl, 'SCRIPTS');
        }
        
        if ($data->_Zreport) {
            $jsTpl = new ET("function fpPrintZReport() {
                                [#/tremol/js/FiscPrinterTplFileImportBegin.txt#]
                                try {
                                    [#/tremol/js/FiscPrinterTplConnect.txt#]
                                    fpZReport();
                                    render_showToast({timeOut: 800, text: '" . tr('Успешно отпечатан Z отчет') . "', isSticky: false, stayTime: 8000, type: 'notice'});
                                } catch(ex) {
                                    render_showToast({timeOut: 800, text: '" . tr('Грешка при отпечатване на Z отчет') . ": ' + ex.message, isSticky: true, stayTime: 8000, type: 'error'});
                                }
                                [#/tremol/js/FiscPrinterTplFileImportEnd.txt#]
                            }");
            
            $Driver->addTplFile($jsTpl);
            $Driver->connectToPrinter($jsTpl, $data->rec, false);
            
            $tpl->appendOnce($jsTpl, 'SCRIPTS');
        }
    }
}
