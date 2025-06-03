<?php


/**
 * Клас 'bgfisc_plg_PrintFiscReceipt' - за добавяне на функционалност от наредба 18 към ПОС бележките към касите
 *
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgfisc_plg_PrintFiscReceipt extends core_Plugin
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'n18_plg_PrintFiscReceipt';


    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     *
     * @return bool|null
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        // дебъг бутон за печатане на фискален бон
        if (haveRole('debug') && $mvc instanceof sales_Sales) {
            $data->toolbar->addBtn('ФБ', array($mvc, 'printFiscReceipt', $data->rec->id), 'ef_icon=img/16/bug.png, title = Тестово разпечатване на фискален бон,row=2');
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        // Ако ще се отпечатва фискален бон
        if (strtolower($action) == 'printfiscreceipt') {
            requireRole('debug');
            expect($id = Request::get('id', 'int'));
            $rec = $mvc->fetch($id);
            
            try {
                $obj = self::getFiscReceiptTpl($mvc, $rec);
            } catch (core_exception_Expect $e) {
                $obj = (object) array('arr' => tr($e->getMessage()));
            }
           
            $doc = $mvc->getLink($rec->id, 0);
            $res = new core_ET('');
            $res->append($doc);
            $res->append(ht::mixedToHtml($obj->arr));
            $res->append($obj->js, 'SCRIPTS');
            $mvc->logWrite('Дебъг печатане на касов бон', $rec->id);

            return false;
        }
        
        // Какво става ако е имало проблем при отпечатването на бележката
        if (strtolower($action) == 'printreceipterror') {
            Request::setProtected('hash');
            expect($hash = Request::get('hash', 'varchar'));
            expect(str::checkHash($hash, 4));
            expect($err = Request::get('err', 'varchar'));
            $mvc->requireRightFor('conto');

            $id = Request::get('id', 'int');
            $rec = $mvc->fetch($id);
            bgfisc_PrintedReceipts::removeWaitingLog($mvc, $id);
            if($mvc->rollbackConto($id)){
                $mvc->logWrite('Ревъртване на контировката (3)', $rec);
            }
            $mvc->logErr($err, $id);
            core_Statuses::newStatus($err, 'error');
            $cu = core_Users::getCurrent();
            if($cu == core_Users::ANONYMOUS_USER){
                wp("АНОНИМНО РЕВЪРТВАНЕ", $rec);
            }

            if ($mvc instanceof cash_Pko) {
                $rec->cashRegNum = null;
                $mvc->save_($rec, 'cashRegNum');
            }
            
            core_Locks::release("lock_{$mvc->className}_{$rec->id}");
            
            return redirect($mvc->getSingleUrlArray($id));
        }
    }
    
    
    /**
     * Генерира масива за отпечатване на фискален бон
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @param string   $errorMsg
     * @param bool     $debug
     *
     * @return stdClass
     */
    public static function getFiscReceiptTpl($mvc, $rec)
    {
        $caseId = ($mvc instanceof sales_Sales) ? $rec->caseId : (($rec->peroCase) ? $rec->peroCase : $mvc->getDefaultCase($rec));

        $registerRec = bgfisc_Register::getFiscDevice($caseId);
       
        if (empty($registerRec)) {
            throw new core_exception_Expect('Не е подадено ФУ', 'Несъответствие');
        }
        
        if (!$Driver = peripheral_Devices::getDriver($registerRec)) {
            throw new core_exception_Expect('Има проблем със зареждането на драйвера', 'Несъответствие');
        }
        
        $payments = $mvc->getDocumentFiscPayments($rec, $Driver, $registerRec);
        $productsArr = $mvc->getProducts4FiscReceipt($rec, $Driver, $registerRec);
       
        $textArr = array();
        array_walk($payments, function($a) use(&$textArr){if(array_key_exists('PAYMENT_TEXT', $a)){$textArr[$a['PAYMENT_TEXT']] = $a['PAYMENT_TEXT'];}});
        $fiscalArr = array('products' => $productsArr, 'payments' => $payments);
        
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        $chargeVat = $firstDoc->fetchField('chargeVat');
        if (in_array($chargeVat, array('yes', 'separate', 'no'))) {
            $fiscalArr['IS_PRINT_VAT'] = 1;
        }
        
        $cu = core_Users::getCurrent();
        $fiscalArr['SERIAL_NUMBER'] = (bgfisc_Setup::get('CHECK_SERIAL_NUMBER') == 'yes') ? $registerRec->serialNumber : false;
        $fiscalArr['BEGIN_TEXT'] = 'Касиер: ' . core_Users::getVerbal($cu, 'names');
        $fiscalArr['IS_PRINT_VAT'] = bgfisc_Setup::get('PRINT_VAT_GROUPS') == 'yes';

        $receiptNumber = bgfisc_Register::getSaleNumber($mvc, $rec->id);
        if ($rec->isReverse == 'yes') {
            $Origin = doc_Containers::getDocument($rec->originId);
            $fiscalArr['RELATED_TO_URN'] = $receiptNumber;
            $fiscalArr['IS_STORNO'] = true;
            
            $reasonCode = $Driver->getStornoReasonCode($registerRec, $rec->stornoReason);
            if (!isset($reasonCode)) {
                if($reasonCode == 0){
                    $valior = $Origin->fetchField($Origin->valiorFld);
                    $maxDateForError = bgfisc_Register::getMaxDateForStornoOperationError($valior);
                    if(dt::today() > $maxDateForError){
                        $maxDateForErrorVerbal = dt::mysql2verbal($maxDateForError, 'd.m.Y');

                        throw new core_exception_Expect("Сторно по бележката с основание \"Операторска грешка\" не може да се издава след|*: {$maxDateForErrorVerbal}", 'Несъответствие');
                    }
                }
            }
            
            $fiscalArr['STORNO_REASON'] = $reasonCode;
            $fiscalArr['QR_CODE_DATA'] = bgfisc_PrintedReceipts::getQrCode($Origin->getInstance(), $Origin->that);
            if (empty($fiscalArr['QR_CODE_DATA']) || $fiscalArr['QR_CODE_DATA'] == bgfisc_PrintedReceipts::MISSING_QR_CODE) {
                throw new core_exception_Expect('Към оригиналната бележка няма фискален бон', 'Несъответствие');
            }
        } else {
            $fiscalArr['RCP_NUM'] = $receiptNumber;
        }
        
        if ($rec->fromContainerId) {
            $DocumentFrom = doc_Containers::getDocument($rec->fromContainerId);
            if ($DocumentFrom->isInstanceOf('sales_Invoices')) {
                $invoiceRec = $DocumentFrom->fetch();
                if ($invoiceRec->type == 'dc_note') {
                    $totalValue = $invoiceRec->dealValue - $invoiceRec->discountAmount + $invoiceRec->vatAmount;
                    $text = ($totalValue > 0) ? 'Дебитно известие' : 'Кредитно известие';
                } else {
                    $text = 'Фактура';
                }
                $text = "{$text} N" . str_pad($invoiceRec->number, 10, '0', STR_PAD_LEFT) . '/' . dt::mysql2verbal($invoiceRec->date, 'd.m.Y');
                $fiscalArr['BEGIN_TEXT'] = array($text, $fiscalArr['BEGIN_TEXT']);
            }
        }
        
        Request::setProtected('hash');
        $hash = str::addHash('fiscreceipt', 4);

        $retUrl = null;
        if(isset($rec->_lineId)){
            
            // Ако е към линия, ще се редиректва към нишката на линията закотвено за реда който е натиснат
            $detailRecId = trans_LineDetails::fetchField("#containerId = '{$rec->containerId}' AND #lineId = '{$rec->_lineId}'");
            $threadId = trans_Lines::fetchField($rec->_lineId, 'threadId');
            $retUrl = toUrl(array('doc_Containers', 'list', 'threadId' => $threadId, "#" => "ld{$detailRecId}"), 'local');
        }
        
        $logUrl = toUrl(array('bgfisc_PrintedReceipts', 'log', 'docClassId' => $mvc->getClassId(), 'docId' => $rec->id, 'ret_url' => $retUrl, 'hash' => $hash));
        $errorUrl = toUrl(array($mvc, 'printreceipterror', $rec->id, 'hash' => $hash));
        Request::removeProtected('hash');
        
        if(countR($textArr)){
            $fiscalArr['END_TEXT'] = implode(', ', $textArr);
        }

        $showPosDevice = bgfisc_Setup::get('SHOW_BPT_IN_RECEIPT') == 'yes';
        if($showPosDevice){
            $nQuery = cash_NonCashPaymentDetails::getQuery();
            $nQuery->where("#classId = {$mvc->getClassId()} AND #objectId = {$rec->id} AND #deviceId IS NOT NULL");
            $deviceIds = arr::extractValuesFromArray($nQuery->fetchAll(), 'deviceId');
            foreach ($deviceIds as $deviceId) {
                $deviceRec = peripheral_Devices::fetch($deviceId);
                $deviceName = cls::get($deviceRec->driverClass)->getBtnName($deviceRec);
                $fiscalArr['END_TEXT'][$deviceName] = "Платено през: {$deviceName}";
            }
        }

        $Driver = cls::get($registerRec->driverClass);
        if (cls::haveInterface('peripheral_FiscPrinterWeb', $Driver)) {
            $interface = core_Cls::getInterface('peripheral_FiscPrinterWeb', $registerRec->driverClass);
            
            $js = $interface->getJS($registerRec, $fiscalArr);
            $js .= '
                function fpOnSuccess(res)
                {
                    document.location = " ' . $logUrl . '&res=" + res;
                }
                            
                function fpOnError(err) {
                    document.location = " ' . $errorUrl . '&err=" + err;
                }';
            
            $res = (object) array('js' => $js, 'arr' => $fiscalArr);
        } else {
            $interface = core_Cls::getInterface('peripheral_FiscPrinterIp', $registerRec->driverClass);
            $result = $interface->printReceipt($registerRec, $fiscalArr);
            $res = (object) array('arr' => $fiscalArr);
  
            $redirectUrl = $logUrl . "&res={$result}";
            
            if ($registerRec->isElectronic == 'yes' && $rec->isReverse != 'yes' && !empty($result)) {
                list(, $receiptNum) = explode('*', $result);
                usleep(1000000);
                $fh = $interface->saveReceiptToFile($registerRec, $receiptNum);
                if ($fh !== false) {
                    $redirectUrl .= "&fh={$fh}";
                }
            }
            
            $res->redirect = $redirectUrl;
        }
        
        return $res;
    }
    
    
    /**
     * Връща артикулите според ориджина
     *
     * @param int                        $containerId
     * @param peripheral_FiscPrinterIntf $Driver
     * @param stdClass                   $registerRec
     *
     * @return array $res
     */
    public static function getProductsByOrigin($containerId, $Driver, $registerRec)
    {
        $Origin = doc_Containers::getDocument($containerId);
        $originRec = $Origin->fetchRec();
        $vatExceptionId = cond_VatExceptions::getFromThreadId($originRec->threadId);

        if ($Origin->isInstanceOf('store_ShipmentOrders')) {
            $dQuery = store_ShipmentOrderDetails::getQuery();
            $dQuery->where("#shipmentId = {$originRec->id}");
        } elseif ($Origin->isInstanceOf('store_Receipts')) {
            $dQuery = store_ReceiptDetails::getQuery();
            $dQuery->where("#receiptId = {$originRec->id}");
        } elseif ($Origin->isInstanceOf('sales_Services')) {
            $dQuery = sales_ServicesDetails::getQuery();
            $dQuery->where("#shipmentId = {$originRec->id}");
        } else {
            $dQuery = sales_SalesDetails::getQuery();
            $dQuery->where("#saleId = {$originRec->id}");
        }
        $all = $dQuery->fetchAll();
        
        $res = array();
        foreach ($all as $dRec) {
            $amountWithVatNotRound = $dRec->amount;
            $vatSysId = cat_products_VatGroups::getCurrentGroup($dRec->productId, null, $vatExceptionId)->sysId;
            $amount = $dRec->amount;
            
            if (in_array($originRec->chargeVat, array('yes', 'separate'))) {
                $vatPercent = cat_Products::getVat($dRec->productId, null, $vatExceptionId);
                $amount = round($dRec->amount + ($dRec->amount * $vatPercent), 2);
                $amountWithVatNotRound += ($dRec->amount * $vatPercent);
                setIfNot($vatSysId, 'B');
            } else{
                setIfNot($vatSysId, 'A');
            }
            $vatClass = $Driver->getVatGroupCode(acc_VatGroups::getIdBySysId($vatSysId), $registerRec);
            
            $amount = round($amount, 2);
            $name = cat_Products::getVerbal($dRec->productId, 'name');
            $name = str_replace(array('&lt;', '&amp;'), array('<', '&'), $name);

            $arr = array('PLU_NAME' => $name, 'QTY' => 1, 'PRICE' => $amount, 'VAT_CLASS' => $vatClass);
            $price = round($amount / $dRec->packQuantity, bgfisc_Setup::get('PRICE_FU_ROUND'));
            $arr['BEFORE_PLU_TEXT'] = "{$dRec->packQuantity} x {$price}лв";
            if (!empty($dRec->discount)) {
                $arr['PERCENT'] = $dRec->discount * 100;
                $arr['DISC_ADD_V'] = -1 * round($dRec->discount * $amountWithVatNotRound, 2);
            }
            
            $res[$dRec->id] = $arr;
        }
        
        return $res;
    }
    
    
    /**
     * Проверка преди контиране
     *
     * @param int         $caseId
     * @param int         $currencyId
     * @param string|null $error
     *
     * @return bool
     */
    public static function checkBeforeConto($caseId, $currencyId, &$error)
    {
        $registerRec = bgfisc_Register::getFiscDevice($caseId, $serialNum);
        if($serialNum == bgfisc_Register::WITHOUT_REG_NUM) return true;

        if (empty($registerRec)) {
            $error = 'Няма връзка с ФУ';
            
            return false;
        }
        
        // Дали валутата се поддържа
        $Driver = peripheral_Devices::getDriver($registerRec);
        $currencyCode = currency_Currencies::getCodeById($currencyId);
        if (!$Driver->isCurrencySupported($registerRec, $currencyCode)) {
            $error = "ФУ|*: <b>{$registerRec->serialNumber}</b> |не приема плащания в|* <b>{$currencyCode}</b>";
            
            return false;
        }
        
        return true;
    }
}
