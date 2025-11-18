<?php


/**
 * Клас 'bgfisc_plg_CashDocument' - за добавяне на функционалност от наредба 18 към ПОС бележките към ПКО-та и РКО-та
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
class bgfisc_plg_CashDocument extends core_Plugin
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'n18_plg_CashDocument';


    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->FLD('cashRegNum', 'varchar(nullIfEmpty,maxRadio=1)', 'caption=Фискално устройство->Избор,after=name,input=none');
        setIfNot($mvc->canHardconto, 'salesMaster,ceo');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec)) {
            if (!self::isApplicable($rec->threadId)) {
                
                return;
            }
            
            // документите може да се издават само към продажби с експедиране, фактури и складови документи
            $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
            if ($firstDoc->isInstanceOf('sales_Sales')) {
                if (!empty($rec->originId) && empty($rec->fromContainerId)) {
                    $Origin = doc_Containers::getDocument($rec->originId);
                    if ($Origin->isInstanceOf('sales_Sales')) {
                        $originRec = $Origin->fetch('contoActions,state,amountDeal');
                        if ($originRec->state != 'active') {
                            $requiredRoles = 'no_one';
                        } else {
                            $contoActions = type_Set::toArray($originRec->contoActions);
                            if ($mvc instanceof cash_Pko) {
                                if (!isset($contoActions['ship']) || empty($originRec->amountDeal) || isset($contoActions['pay'])) {
                                    $requiredRoles = 'no_one';
                                }
                            } else {
                                if (!isset($contoActions['pay']) || empty($originRec->amountDeal)) {
                                    $requiredRoles = 'no_one';
                                }
                            }
                        }
                    }
                }
                
                if(isset($rec->fromContainerId)){
                    $FromContainer = doc_Containers::getDocument($rec->fromContainerId);
                    if($FromContainer->isInstanceOf('sales_Proformas')){
                        $requiredRoles = 'no_one';
                    }
                }
                
                if ($mvc instanceof cash_Rko) {
                    if (empty($rec->originId)) {
                        $requiredRoles = 'no_one';
                    }
                } elseif (empty($rec->fromContainerId) && empty($rec->originId)) {
                    $requiredRoles = 'no_one';
                } elseif($mvc instanceof cash_Rko) {
                        $requiredRoles = 'no_one';
                }
            } 
        }
        
        if ($action == 'conto' && isset($rec)) {
            if (self::isApplicable($rec->threadId)) {
                if ($rec->state == 'active') {
                    $requiredRoles = 'no_one';
                } elseif (bgfisc_PrintedReceipts::getQrCode($mvc, $rec->id)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if (in_array($action, array('reject', 'restore', 'correction', 'revert')) && isset($rec)) {
            if (!self::isApplicable($rec->threadId)) {
                
                return;
            }
            if (bgfisc_PrintedReceipts::getQrCode($mvc, $rec->id)) {
                $requiredRoles = 'no_one';
            }
        }
        
        // Не може да се променя след създаване към коя фактура е документа
        if (in_array($action, array('clonerec')) && isset($rec)) {
            if (!self::isApplicable($rec->threadId)) {
                
                return;
            }
            $requiredRoles = 'no_one';
        }
        
        if ($mvc instanceof cash_Pko && in_array($action, array('selectinvoice')) && isset($rec)) {
            if (!self::isApplicable($rec->threadId)) {
                
                return;
            }
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * Изпълнява се преди контиране на документа
     */
    public static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        if (!self::isApplicable($rec->threadId)) {
            
            return;
        }
        
        // Проверка ще се контира ли
        $error = null;
        $caseId = ($mvc instanceof sales_Sales) ? $rec->caseId : (($rec->peroCase) ? $rec->peroCase : $mvc->getDefaultCase($rec));
        if (!bgfisc_plg_PrintFiscReceipt::checkBeforeConto($caseId, $rec->currencyId, $error)) {
            
            throw new core_exception_Expect($error, 'Несъответствие');
        }
    }
    
    public static function getContoWarning($mvc, $rec, $defaultWarning)
    {
        $rec = $mvc->fetchRec($rec);
        
        if(!($mvc instanceof cash_Rko)){
            $containerId = ($mvc instanceof cash_Pko) ? (isset($rec->fromContainerId) ? $rec->fromContainerId : $rec->originId) : $rec->originId;
            
            if(isset($containerId)){
                $Document = doc_Containers::getDocument($containerId);
                if ($Document->isInstanceOf('sales_Sales')) {
                    $saleRec = $Document->fetch('amountDelivered,currencyRate');
                    $expectedAmount = $saleRec->amountDelivered / $saleRec->currencyRate;
                    $expectedAmount -= self::getPaidByNow($containerId);
                } else {
                    $expectedAmount = $mvc->getExpectedAmount($containerId, $rec);

                    $firstDocument = doc_Threads::getFirstDocument($Document->fetchField('threadId'));
                    if($firstDocument->isInstanceOf('sales_Sales')){
                        $saleActions = type_Set::toArray($firstDocument->fetchField('contoActions'));
                        if(isset($saleActions['pay'])){
                            $expectedAmount -= $firstDocument->fetchField('amountDeal');
                        }
                    }
                }
                
                $firstDocument = doc_Threads::getFirstDocument($rec->threadId);
                if($firstDocument->isInstanceOf('deals_DealMaster')){
                    $dealPaid = $firstDocument->fetchField('amountPaid');
                    $dealBl = $firstDocument->fetchField('amountBl');

                    // Проверява се дали не се прави опит за надплащане над допустимия толеранс
                    if($rec->amountDeal >= $expectedAmount){
                        $diff = abs(round($rec->amountDeal - $expectedAmount, 2));
                        $tolerance = acc_Setup::get('MONEY_TOLERANCE');

                        $aboveTolerance = empty($diff) || $diff > $tolerance;
                        if ($aboveTolerance  && $dealPaid && $dealBl <= 0) {
                            $additionalWarning = "ЦЯЛАТА СУМА ПО ДОКУМЕНТА ИЗГЛЕЖДА ВЕЧЕ Е ПЛАТЕНА|*!";
                            $defaultWarning = (!empty($additionalWarning)) ? "{$additionalWarning}, {$defaultWarning}" : "{$additionalWarning}, Наистина ли желаете документът да бъде контиран|*?";
                        }
                    }
                }
            }
        }
        
        return $defaultWarning;
    }
    
    
    /**
     * Уорнинг на бутона за контиране/активиране
     */
    public static function on_AfterGetContoWarning($mvc, &$res, $rec, $isContable)
    {
        $res = self::getContoWarning($mvc, $rec, $res);
    }
    
    
    /**
     * Преди рендиране на тулбара
     */
    public static function on_BeforeRenderSingleToolbar($mvc, &$res, &$data)
    {
        $rec = &$data->rec;
        if (!self::isApplicable($rec->threadId)) {
            
            return;
        }
        
        // Ако има опции за избор на контирането, подмяна на бутона за контиране
        if ($data->toolbar->haveButton('btnConto')) {
            if (!$data->toolbar->isErrorBtn('btnConto')) {

                if(!bgfisc_Register::doRequireFiscForConto($mvc, $rec)) return;

                $data->toolbar->removeBtn('btnConto');
                $contoUrl = toUrl(array($mvc, 'contocash', $rec->id), 'local');
                $warning = $mvc->getContoWarning($rec, $rec->isContable);
                
                $data->toolbar->addFnBtn('Контиране', '', array('id' => 'btnConto', 'warning' => $warning, 'data-url' => $contoUrl, 'class' => 'document-conto-btn'), 'ef_icon = img/16/tick-circle-frame.png,title=Контиране на документа');
            }
        }
    }
    
    
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
        if (!self::isApplicable($data->rec->threadId)) {
            
            return;
        }
        
        if (haveRole('debug')) {
            $data->toolbar->addBtn('ФБ', array($mvc, 'printFiscReceipt', $data->rec->id), 'ef_icon=img/16/bug.png, title = Тестово разпечатване на фискален бон,row=2');
        }
        
        if ($mvc instanceof cash_Pko) {
            if (cash_Rko::haveRightFor('add', (object) array('originId' => $data->rec->containerId, 'threadId' => $data->rec->threadId))) {
                $data->toolbar->addBtn('РКО', array('cash_Rko', 'add', 'originId' => $data->rec->containerId, 'ret_url' => true), 'ef_icon=img/16/money_delete.png,title=Създаване на нов разходен касов документ');
            }
        }
        
        if ($mvc->haveRightFor('hardconto', $data->rec)) {
            $data->toolbar->addBtn('Ръчно контиране', array($mvc, 'hardconto', 'id' => $data->rec->id, 'ret_url' => true), 'ef_icon=img/16/bug.png,title=Ръчно контиране без създаване на касова бележка,row=3');
        }
        
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        if (!self::isApplicable($rec->threadId)) return;
        
        if($form->isSubmitted()){
            if(isset($rec->_allIsPaid)){
                $form->setWarning('amountDeal', 'Цялата сума по документа е платена|! |Наистина ли желаете да продължите|*?');
            }
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        
        if (isset($rec->originId) && empty($rec->fromContainerId)) {
            $Origin = doc_Containers::getDocument($rec->originId);
            $form->setDefault('reason', '#' . $Origin->getHandle());
        }
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param core_Manager $mvc
     * @param core_ET      $tpl
     * @param stdClass     $data
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if (!self::isApplicable($data->rec->threadId)) {
            
            return;
        }
        
        // Добавяне на файл с допълнителен скрипт
        if (!Mode::is('printing')) {
            $tpl->push('bgfisc/js/Receipt.js', 'JS');
            jquery_Jquery::run($tpl, 'fiscActions();', true);
        }
    }
    
    
    /**
     * Дали плъгина ще се изпълнява
     *
     * @param int $threadId
     *
     * @return bool
     */
    public static function isApplicable($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        if (empty($firstDoc)) {
            
            return false;
        }
        
        return $firstDoc->isInstanceOf('sales_Sales');
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $rec = &$data->rec;
        $row = &$data->row;
        if (!self::isApplicable($rec->threadId)) {
            
            return;
        }
        
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        if ($cashReg = bgfisc_Register::getRec($firstDoc->getInstance(), $firstDoc->that)) {
            $urn = bgfisc_Register::getUrlLink($cashReg->urn);
            
            $row->otherCaption = tr('УНП');
            $row->otherText = $urn;
        }

        $peroCaseId = isset($rec->peroCase) ? $rec->peroCase : $mvc->getDefaultCase($rec);
        if ($peroCaseId) {
            $serial = !empty($rec->cashRegNum) ? $rec->cashRegNum : cash_Cases::fetchField($peroCaseId, 'cashRegNum');
            $serialLink = bgfisc_Register::getFuLinkBySerial($serial);
            $row->peroCase .= tr("|*<br><span style='font-weight:normal'>|ФУ|*: <b>{$serialLink}</b></span>");
        }
    }
    
    
    /**
     * Какви са плащанията
     *
     * @param core_Mvc                   $mvc
     * @param array|null                 $res
     * @param stdClass                   $rec
     * @param peripheral_FiscPrinterIntf $Driver
     * @param stdClass                   $registerRec
     */
    public static function on_AfterGetDocumentFiscPayments($mvc, &$res, $rec, $Driver, $registerRec)
    {
        if (empty($res)) {
            $errors = $res = array();
            $cashAmount = $rec->amount;

            $valior = !empty($rec->valior) ? $rec->valior : dt::today();

            if ($mvc instanceof cash_Pko) {
                $dQuery = cash_NonCashPaymentDetails::getQuery();
                $dQuery->where("#classId = {$mvc->getClassId()} AND #objectId = '{$rec->id}'");
                while ($dRec = $dQuery->fetch()) {
                    $paymentCode = 0;

                    // Ако сме в периода на работата с двете валути безналичното плащане в БГН да се приема за платено в брой
                    $skipCheck = false;
                    if($dRec->paymentId == eurozone_Setup::getBgnPaymentId()) {
                        if ($valior > acc_Setup::getEurozoneDate() && $valior <= acc_Setup::getBgnDeprecationDate()) {
                            $skipCheck = true;
                        }
                    }

                    if(!$skipCheck){
                        if (!$paymentCode = $Driver->getPaymentCode($registerRec, $dRec->paymentId)) {
                            $title = cond_Payments::getTitleById($dRec->paymentId);
                            $errors[] = $title;
                            continue;
                        }
                    }

                    $dRec->amount = cond_Payments::toBaseCurrency($dRec->paymentId, $dRec->amount, $valior);
                    $dRec->amount /= $rec->rate;
                    $dRec->amount = round($dRec->amount, 2);

                    $arr = array('PAYMENT_TYPE' => $paymentCode, 'PAYMENT_AMOUNT' => $dRec->amount);
                    
                    $paymentRec = cond_Payments::fetch($dRec->paymentId, 'title,text');
                    if(!empty($paymentRec->text)){
                        $arr['PAYMENT_TEXT'] = "{$paymentRec->title}: {$paymentRec->text}";
                    }
                    
                    $res[] = $arr;
                }

                if (count($errors)) {
                    $msg = 'Следните плащания нямат код във ФУ|*: ' . implode(',', $errors);
                    throw new core_exception_Expect($msg, 'Несъответствие');
                }
            }
        }
    }
    
    
    /**
     * Връща артикулите за ФУ от фактурата
     */
    private static function getFiscProductsFromInvoice($Driver, $registerRec, $mvc, $Origin, $originRec, $rec)
    {
        $res = array();
        
        $sysId = (!in_array($originRec->vatRate, array('yes', 'separate', 'no'))) ? 'A' : 'B';
        $vatClass = $Driver->getVatGroupCode(acc_VatGroups::getIdBySysId($sysId), $registerRec);
        
        if ($originRec->type == 'dc_note' && $originRec->changeAmount) {
            $iName = ($originRec->changeAmount > 0) ? 'Плащане по ДИ' : 'Връщане по КИ';
            $am = round($rec->amount * $rec->rate, 2);
            $arr = array('PLU_NAME' => $iName, 'QTY' => 1, 'PRICE' => $am, 'VAT_CLASS' => $vatClass);
            $res[$vatClass] = $arr;
        } elseif (!empty($originRec->dpAmount) && $originRec->dpOperation == 'accrued') {
            $name = 'Плащане по фактура';
            $totalValue = $originRec->dealValue - $originRec->discountAmount + $originRec->vatAmount;
            $am = round($totalValue / $originRec->rate, 2);
            $arr = array('PLU_NAME' => $name, 'QTY' => 1, 'PRICE' => $am, 'VAT_CLASS' => $vatClass);
            $res[$vatClass] = $arr;
        } else {
            if ($Origin->isInstanceOf('sales_Proformas')) {
                $dQuery = sales_ProformaDetails::getQuery();
                $dQuery->where("#proformaId = {$originRec->id}");
            } else {
                $dQuery = sales_InvoiceDetails::getQuery();
                $dQuery->where("#invoiceId = {$originRec->id}");
            }
            $dRecs = $dQuery->fetchAll();
            $iName = 'Плащане по фактура';
            if ($originRec->type == 'dc_note') {
                deals_InvoiceDetail::modifyDcDetails($dRecs, $originRec, cls::get('sales_InvoiceDetails'));
                
                $dRecs = array_filter($dRecs, function ($a) {
                    
                    return ($a->changedQuantity === true || $a->changedPrice === true);
                });
                $iName = ($originRec->changeAmount > 0) ? 'Плащане по ДИ' : 'Връщане по КИ';
            }
            
            $vats = array();
            foreach ($dRecs as $dRec) {
                if (in_array($originRec->vatRate, array('yes', 'separate', 'no'))) {
                    $vatSysId = cat_products_VatGroups::getCurrentGroup($dRec->productId)->sysId;
                    setIfNot($vatSysId, 'B');
                } else {
                    $vatSysId = 'A';
                }
                $vatId = acc_VatGroups::getIdBySysId($vatSysId);
                
                if (!array_key_exists($vatId, $vats)) {
                    $vats[$vatId] = 0;
                }
                
                $dAmount = abs($dRec->quantity * $dRec->packPrice);
                $r = $dAmount - ($dAmount * $dRec->discount);
                $vats[$vatId] += $r;
            }
            
            if (!empty($originRec->dpAmount) && $originRec->dpOperation == 'deducted') {
                $dAmount = abs(($originRec->dpAmount / $originRec->rate));
                $vatSysId = (in_array($originRec->vatRate, array('yes', 'separate', 'no'))) ? 'B' : 'A';
                $vatId = acc_VatGroups::getIdBySysId($vatSysId);
                
                if (!array_key_exists($vatId, $vats)) {
                    $dAmount = round($dAmount, 2);
                }
                $vats[$vatId] -= $dAmount;
            }
            
            $bynow = 0;
            
            foreach ($vats as $vatLetterId => $vatAmount) {
                $percent = round($vatAmount / ($originRec->dealValue - $originRec->discountAmount), 2);
                
                $vatClass = $Driver->getVatGroupCode($vatLetterId, $registerRec);
                
                $am = abs(round($rec->amount * $rec->rate * $percent, 2));
                $arr = array('PLU_NAME' => $iName, 'QTY' => 1, 'PRICE' => $am, 'VAT_CLASS' => $vatClass);
                $bynow += $am;
                $res[$vatClass] = $arr;
                
                if (round($bynow, 2) >= round($rec->amount * $rec->rate, 2) && $vatAmount > 0) {
                    // break;
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Какви са артикулите за ФУ
     *
     * @param core_Mvc                   $mvc
     * @param array|null                 $res
     * @param stdClass                   $rec
     * @param peripheral_FiscPrinterIntf $Driver
     * @param stdClass                   $registerRec
     */
    public static function on_AfterGetProducts4FiscReceipt($mvc, &$res, $rec, $Driver, $registerRec)
    {
        if (empty($res)) {
            $res = array();
            
            if (isset($rec->fromContainerId)) {
                $Origin = doc_Containers::getDocument($rec->fromContainerId);
                $originRec = $Origin->fetch();
                
                if ($Origin->isInstanceOf('sales_Invoices')) {
                    $res = self::getFiscProductsFromInvoice($Driver, $registerRec, $mvc, $Origin, $originRec, $rec);
                } else {
                    $res = self::getFiscProductsFromShipmentDocument($Driver, $registerRec, $mvc, $Origin, $originRec, $rec);
                }
            } elseif (isset($rec->originId)) {
                $Origin = doc_Containers::getDocument($rec->originId);

                if($Origin->isInstanceOf('cash_Pko')){
                    $oRec = $Origin->fetch('originId,fromContainerId');
                    $sourceId = isset($oRec->fromContainerId) ? $oRec->fromContainerId : $oRec->originId;
                    $Origin = doc_Containers::getDocument($sourceId);
                }

                $originRec = $Origin->fetch();

                if ($Origin->isInstanceOf('store_ShipmentOrders') || $Origin->isInstanceOf('sales_Sales') || $Origin->isInstanceOf('sales_Services')) {
                    $res = self::getFiscProductsFromShipmentDocument($Driver, $registerRec, $mvc, $Origin, $originRec, $rec);
                } elseif($Origin->isInstanceOf('sales_Invoices')){
                    $res = self::getFiscProductsFromInvoice($Driver, $registerRec, $mvc, $Origin, $originRec, $rec);
                }
            }
        }
    }
    
    
    /**
     * Връща артикулите от експедиращоя документ
     */
    private static function getFiscProductsFromShipmentDocument($Driver, $registerRec, $mvc, $Origin, $originRec, $rec)
    {
        $anotherRes = bgfisc_plg_PrintFiscReceipt::getProductsByOrigin($originRec->containerId, $Driver, $registerRec);
        
        if (round($originRec->amountDelivered, 2) == round($rec->amount * $rec->rate, 2)) {
            $res = $anotherRes;
        } else {
            $vats = array();
            $beforeTextArr = array();
            foreach ($anotherRes as $anotherArr) {
                $vatClass = $anotherArr['VAT_CLASS'];
                $vats[$vatClass] = $vatClass;
                $beforeTextArr[] = $anotherArr['PLU_NAME'];
                $beforePluText = '  ' . $anotherArr['BEFORE_PLU_TEXT'];
                if (isset($anotherArr['PERCENT'])) {
                    $beforePluText .= "-{$anotherArr['PERCENT']}%";
                }
                $beforePluText .= '=' . round($anotherArr['PRICE'] + $anotherArr['DISC_ADD_V'], 2) . 'лв';
                $beforeTextArr[] = $beforePluText;
            }
            
            // Забраняване на частичното плащане, ако има артикули с различни ставки
            $countVats = count($vats);
            if ($countVats && $countVats != 1) {
                throw new core_exception_Expect('Частично плащане е позволено, само ако всички артикули са от една ДДС група|*!', 'Несъответствие');
            }
            
            $arr = array('PLU_NAME' => 'Платени', 'QTY' => 1, 'PRICE' => round($rec->amount * $rec->rate, 2), 'VAT_CLASS' => $vatClass);
            $arr['BEFORE_PLU_TEXT'] = $beforeTextArr;
            $res[] = $arr;
        }
        
        return $res;
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        // Екшън за контиране на касов документ
        if (strtolower($action) == 'contocash') {
            $id = Request::get('id', 'int');
            $lineId = Request::get('lineId', 'int');
            $rec = $mvc->fetch($id);
            
            $resArr = array();
            $mvc->requireRightFor('conto', $rec);
            
            core_Locks::obtain("lock_{$mvc->className}_{$rec->id}", 90, 15, 5, false);
            
            try {
                $mvc->conto($rec->id);
                $rec = $mvc->fetch($rec->id, '*', false);
                $rec->_lineId = $lineId;
                bgfisc_PrintedReceipts::logPrinted($mvc, $rec->id);
                
                // Ако има контировка, печата се фискален бон
                if (acc_Journal::fetchByDoc($mvc, $id)) {
                    $obj = bgfisc_plg_PrintFiscReceipt::getFiscReceiptTpl($mvc, $rec);
                    
                    $rec->cashRegNum = $obj->arr['SERIAL_NUMBER'];
                    $mvc->save_($rec, 'cashRegNum');
                    
                    $resObj = new stdClass();
                    if (isset($obj->js)) {
                        $resObj->func = 'js';
                        $resObj->arg = array('js' => $obj->js);
                    } else {
                        $resObj->func = 'redirect';
                        $resObj->arg = array('url' => $obj->redirect);
                    }
                    
                    $resArr[] = $resObj;
                }
            } catch (core_exception_Expect $e) {
                reportException($e);
                $errorMsg = $e->getMessage();
                if($mvc->rollbackConto($rec)){
                    $mvc->logWrite('Ревъртване на контировката (2)', $rec);
                }

                $mvc->logErr($errorMsg, $id);
                $cu = core_Users::getCurrent();
                if($cu == core_Users::ANONYMOUS_USER){
                    wp("АНОНИМНО РЕВЪРТВАНЕ", $rec, $obj);
                }

                core_Statuses::newStatus($errorMsg, 'error');
                bgfisc_PrintedReceipts::removeWaitingLog($mvc, $rec->id);
                
                $rec->cashRegNum = null;
                $mvc->save_($rec, 'cashRegNum');
                
                $resObj = new stdClass();
                $resObj->func = 'redirect';
                $resObj->arg = array('url' => toUrl($mvc->getSingleUrlArray($rec->id)));
                $resArr[] = $resObj;
                
                core_Locks::release("lock_{$mvc->className}_{$rec->id}");
            }
            
            // Показваме веднага и чакащите статуси
            $hitTime = Request::get('hitTime', 'int');
            $idleTime = Request::get('idleTime', 'int');
            $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
            $res = array_merge($resArr, (array) $statusData);
            
            return false;
        } elseif(strtolower($action) == 'hardconto'){
            $mvc->requireRightFor('hardconto');
            expect($id = Request::get('id', 'int'));
            expect($rec = $mvc->fetch($id));
            $mvc->requireRightFor('hardconto', $rec);
            
            $form = cls::get('core_Form');
            $form->title = 'Контиране без отпечатване на ФБ на|* ' . $mvc->getFormTitleLink($rec);
            $form->info = tr("За да контирате документа ръчно, моля въведете номер на вече издадена бележка");
            $form->FLD('qr', 'varchar', 'caption=QR код');

            $form->FLD('qrFN', 'varchar', 'caption=Ръчно въвеждане->ФП');
            $form->FLD('qrNum', 'varchar', 'caption=Ръчно въвеждане->№');
            $form->FLD('qrDate', 'date', 'caption=Ръчно въвеждане->Дата');
            $form->FLD('qrTime', 'varchar', 'caption=Ръчно въвеждане->Време');
            $form->FLD('qrAmount', 'double(maxAllowedDecimals=2)', 'caption=Ръчно въвеждане->Сума');

            $form->input();
            if($form->isSubmitted()){
                $fRec = $form->rec;
                $nums = array();
                foreach (array('qrFN', 'qrNum', 'qrDate', 'qrTime', 'qrAmount') as $fld){
                    if(!empty($fRec->{$fld})){
                        $nums[$fld] = $fRec->{$fld};
                    }
                }
                $numFldCount = countR($nums);

                if(!empty($fRec->qr) && $numFldCount){
                    $form->setError('qr,qrFN,qrNum,qrDate,qrTime,qrAmount', 'Трябва да е въведен или само QR код или ръчно да са въведени останалите полета');
                }

                if(!$form->gotErrors()){
                    $qrCode = !empty($fRec->qr) ? $fRec->qr : implode('*', $nums);
                    if(!empty($qrCode)){
                        if($printedRec = bgfisc_PrintedReceipts::fetch(array("#string = '[#1#]'", $qrCode))){
                            if(!($printedRec->classId == $mvc->getClassId() && $printedRec->objectId == $rec->id)){
                                $errFld = !empty($fRec->qr) ? 'qr' : 'qrFN,qrNum,qrDate,qrTime,qrAmount';
                                $form->setError($errFld, "Има вече издадена бележка с код|*:<b>{$qrCode}</b>");
                            }
                        } else {
                            $parsedQr = explode('*', $qrCode);
                            if(!empty($fRec->qr)){
                                if(countR($parsedQr) != 5){
                                    $form->setError('qr', "QR кода трябва да съдържа пет низа разделени с|* '*'");
                                }
                            }

                            if(!preg_match("/^\d+$/", $parsedQr[0])){
                                $errFld = !empty($fRec->qr) ? 'qr' : 'qrFN';
                                $errMsg = !empty($fRec->qr) ? 'Първият низ трябва да съдържа само цифри' : 'Трябва да съдържа само цифри';
                                $form->setError($errFld, $errMsg);
                            }

                            if(!preg_match("/^\d+$/", $parsedQr[1])){
                                $errFld = !empty($fRec->qr) ? 'qr' : 'qrNum';
                                $errMsg = !empty($fRec->qr) ? 'Вторият низ трябва да съдържа само цифри' : 'Трябва да съдържа само цифри';
                                $form->setError($errFld, $errMsg);
                            }

                            $date = dt::checkByMask($parsedQr[2], 'Y-m-d');
                            if(!$date){
                                $dummyDate = dt::today();
                                $errFld = !empty($fRec->qr) ? 'qr' : 'qrDate';
                                $errMsg = !empty($fRec->qr) ? "Третият низ не е дата във формата|* {$dummyDate}" : "Датата да е във формата|* {$dummyDate}";
                                $form->setError($errFld, $errMsg);
                            }

                            $parsedTime = explode(':', $parsedQr[3]);
                            if(countR($parsedTime) != 3){
                                $errFld = !empty($fRec->qr) ? 'qr' : 'qrNum';
                                $errMsg = !empty($fRec->qr) ? 'Четвъртият низ не е валиднен час:минути:секунди' : 'Не е във формата час:минути:секунди';
                                $form->setError($errFld, $errMsg);
                            }
                        }
                    }

                    if(!$form->gotErrors()){
                        core_Locks::obtain("lock_{$mvc->className}_{$rec->id}", 90, 15, 5, false);
                        $mvc->logWrite('Ръчно контиране на документа', $rec);
                        $mvc->conto($rec);
                        if(!empty($qrCode)){
                            bgfisc_PrintedReceipts::logPrinted($mvc, $rec->id, $qrCode);
                        }
                        core_Locks::release("lock_{$mvc->className}_{$rec->id}");

                        followRetUrl(null, 'Документа е контиран без издаване на ФБ');
                    }
                }
            }
            
            $form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
            $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
            
            $res = $mvc->renderWrapping($form->renderHtml());
            core_Form::preventDoubleSubmission($res, $form);
            
            return false;
        }
    }
    
    
    /**
     * Връща очакваната сума според оридижна
     */
    public static function on_AfterGetExpectedAmount($mvc, &$res, $fromContainerId, $rec)
    {
        if((is_null($res) && $mvc instanceof cash_Pko)){
            $Document = doc_Containers::getDocument($fromContainerId);
            if($Document->isInstanceOf('sales_Sales')){
                
                $res = $Document->fetchField('amountDeal') / $Document->fetchField('currencyRate');
                $res = round($res, 2);
            }
        }
        
        if (!self::isApplicable($rec->threadId) || (is_null($res) && $mvc instanceof cash_Pko)) {
            
            return;
        }
        
        $paidByNow = self::getPaidByNow($fromContainerId);
        
        if ($mvc instanceof cash_Pko) {
            $res -= $paidByNow;
        } else {
            $res = $paidByNow;
        }
        
        // Да се сетва ли предупреждение че всичко е платено
        if(!empty($paidByNow) && empty($res)){
            $origin = $mvc->getOrigin($rec);
            $dealInfo = $origin->getAggregateDealInfo();
            $expectedPayment = $dealInfo->get('expectedPayment');
            if(empty($expectedPayment)){
                $rec->_allIsPaid = true;
            }
        }
    }
    
    
    /**
     * Колко е платено досега по документа
     *
     * @param int $containerId
     *
     * @return int $paidByNow
     */
    private static function getPaidByNow($containerId)
    {
        $paidByNow = null;
        $Document = doc_Containers::getDocument($containerId);
        
        if ($Document->isInstanceOf('cash_Pko')) {
            $paidByNow += $Document->fetchField('amountDeal');
            
            $query = cash_Rko::getQuery();
            $query->where("#state = 'active' AND #originId = {$containerId}");
            while ($cRec = $query->fetch()) {
                $paidByNow -= $cRec->amountDeal;
            }
        } else {
            $query = cash_Pko::getQuery();
            $query->where("#state = 'active' AND ((#fromContainerId = '{$containerId}') OR (#fromContainerId IS NULL AND #originId = '{$containerId}'))");
            while ($cRec = $query->fetch()) {
                $paidByNow += $cRec->amountDeal;
            }
            
            $query1 = bank_IncomeDocuments::getQuery();
            $query1->where("#state = 'active' AND ((#fromContainerId = {$containerId}) OR (#fromContainerId IS NULL AND #originId={$containerId}))");
            while ($cRec1 = $query1->fetch()) {
                $paidByNow += $cRec1->amountDeal;
            }
        }
        
        return $paidByNow;
    }
    
    
    /**
     * След подготовка на безналичните методи на плащане
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareNonCashPayments($mvc, &$data)
    {
        // Ако има дефинирано ФУ
        $registerRec = bgfisc_Register::getFiscDevice($data->masterData->rec->peroCase);
        if(!is_object($registerRec) || Mode::isReadOnly() || !count($data->rows)) {
            
            return;
        }
        
        $Driver = peripheral_Devices::getDriver($registerRec);
        if(!is_object($Driver)) {
            
            return;
        }
        
        // За всеки безналичен метод проверява се има ли код във ФУ
        $valior = !empty($data->masterData->rec->valior) ? $data->masterData->rec->valior : dt::today();
        foreach ($data->rows as $id => &$row){
            $rec = $data->recs[$id];

            // Ако сме в периода за приемане на плащане в лева да не се проверява дали съответства код
            if($rec->paymentId == eurozone_Setup::getBgnPaymentId()){
                if($valior > acc_Setup::getEurozoneDate() && $valior <= acc_Setup::getBgnDeprecationDate()) continue;
            }

            if($rec->paymentId == -1) continue;
            if(!$Driver->getPaymentCode($registerRec, $rec->paymentId)){
                $row->paymentId = "<b class='red'>{$row->paymentId}</b>";
                $row->paymentId = ht::createHint($row->paymentId, 'Безналичният метод на плащане не е зададен във ФУ', 'error', false);
            }
        }
    }


    /**
     * Информацията на документа, за показване в транспортната линия
     *
     * @param core_Mvc $mvc
     *
     * @return array
     *               ['baseAmount']     double|NULL - сумата за инкасиране във базова валута
     *               ['amount']         double|NULL - сумата за инкасиране във валутата на документа
     *               ['amountVerbal']   double|NULL - сумата за инкасиране във валутата на документа
     *               ['currencyId']     string|NULL - валутата на документа
     *               ['notes']          string|NULL - забележки за транспортната линия
     *               ['stores']         array       - склад(ове) в документа
     *               ['weight']         double|NULL - общо тегло на стоките в документа
     *               ['volume']         double|NULL - общ обем на стоките в документа
     *               ['transportUnits'] array   - използваните ЛЕ в документа, в формата ле -> к-во
     *               ['contragentName'] double|NULL - име на контрагента
     *               ['address']        double|NULL - адрес ба диставка
     *               ['storeMovement']  string|NULL - посока на движението на склада
     *               ['locationId']     string|NULL - ид на локация на доставка (ако има)
     *               ['addressInfo']    string|NULL - информация за адреса
     *               ['countryId']      string|NULL - ид на държава
     *
     * @param mixed $id
     * @param int $lineId
     * @return void
     */
    public static function on_AfterGetTransportLineInfo($mvc, &$res, $id, $lineId)
    {
        $rec = $mvc->fetchRec($id);
       
        if($mvc->haveRightFor('conto', $rec) && (!Mode::is('printing') && !Mode::is('xhtml'))){
            
            $contoUrl = toUrl(array($mvc, 'contocash', $rec->id, 'lineId' => $lineId), 'local');
            $warning = $mvc->getContoWarning($rec, $rec->isContable);
            
            $amountVerbal = core_type::getByName('double(decimals=2)')->toVerbal($res['amount']);
            Mode::push('text', 'plain');
            $res['amountVerbal'] = currency_Currencies::decorate($amountVerbal, $rec->currencyId);
            Mode::pop('text');
            $res['amountVerbal'] = str_replace('&nbsp;', ' ', $res['amountVerbal']);
            
            $btn = ht::createFnBtn($res['amountVerbal'], '', $warning, "class=document-conto-btn,ef_icon = img/16/tick-circle-frame.png,title=Контиране на документ,data-url={$contoUrl},id={$mvc->getHandle($rec->id)}");
            $btn->push('bgfisc/js/Receipt.js', 'JS');
            jquery_Jquery::run($btn, 'fiscActions();', true);
            $res['amountVerbal'] = $btn;
        }
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        if(isset($rec->threadId)){
            if(self::isApplicable($rec->threadId)){
                
                // Добавяне на УНП-то на основния документ
                $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
                if ($urn = bgfisc_Register::getRec($firstDoc->getInstance(), $firstDoc->that)->urn) {
                    $res .= ' ' . plg_Search::normalizeText($urn);
                }
            }
        }
    }
}
