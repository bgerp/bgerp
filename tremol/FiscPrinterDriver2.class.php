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
     * RCP_NUM - уникален номер на бележката - [a-zA-Z0-9]{8}-[a-zA-Z0-9]{4}-[0-9]{7}.
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
        setIfNot($params['PRINT_TYPE_STR'], 'buffered'); // postponed || stepByStep
        
        expect($params['RCP_NUM']);
        $js->replace($params['OPER_NUM'], 'OPER_NUM');
        $js->replace(json_encode($params['OPER_PASS']), 'OPER_PASS');
        $js->replace($params['IS_DETAILED'], 'IS_DETAILED');
        $js->replace($params['IS_PRINT_VAT'], 'IS_PRINT_VAT');
        $js->replace(json_encode($params['PRINT_TYPE_STR']), 'PRINT_TYPE_STR');
        $js->replace(json_encode($params['RCP_NUM']), 'RCP_NUM');
        
        // Добавяме продуктите към бележката
        foreach ($params['products'] as $pArr) {
            setIfNot($pArr['PRICE'], 0);
            setIfNot($pArr['VAT_CLASS'], 1); // 0 ... 3
            setIfNot($pArr['QTY'], 1);
            setIfNot($pArr['DISC_ADD_P'], 0);
            setIfNot($pArr['DISC_ADD_V'], 0);
            setIfNot($pArr['PLU_NAME'], '');
            
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
            
            expect($params['SERIAL_NUMBER'], $pRec, $params);
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
            
            if (peripheral_Devices::save($pRec, 'serialNumber')) {
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
