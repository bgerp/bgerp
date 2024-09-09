<?php


/**
 * Клас 'bgfisc_plg_Sales' - за добавяне на функционалност от наредба 18 към Продажбите
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
class bgfisc_plg_Sales extends core_Plugin
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'n18_plg_Sales';


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            
            if (!$form->gotErrors()) {
                if(bgfisc_Register::doRequireFiscForConto($mvc, $rec)){
                    $registerRec = bgfisc_Register::getFiscDevice($rec->caseId);
                    if (empty($registerRec)) {
                        $form->setError('caseId', 'Не може да се генерира УНП, защото не може да се определи ФУ');
                    }
                }
                
                if ($rec->makeInvoice == 'no' && !in_array($rec->chargeVat, array('yes', 'separate'))) {
                    if($mvc->isOwnCompanyVatRegistered($rec)){
                        $form->setError('makeInvoice,chargeVat', 'Не може едновременно да не се начислява ДДС и без фактуриране|*!');
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec     Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields  Имена на полетата, които трябва да бъдат записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if(empty($rec->id) && ($rec->_onlineSale === true || isset($rec->originId))){
            if(!bgfisc_Register::getFiscDevice($rec->caseId, $rec->bankAccountId)){
                
                throw new core_exception_Expect('Не може да се генерира УНП, защото не може да се определи ФУ', 'Несъответствие');
            }
        }
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        $regRec = bgfisc_Register::createUrn($mvc, $rec->id, true);
        core_Statuses::newStatus("Създаване на продажба с УНП|*: '<b>{$regRec->urn}<b>'");
        
        // Добавяне на УНП-то в ключовите думи
        $rec->searchKeywords .= ' ' . plg_Search::normalizeText($regRec->urn);
        
        $rec->searchKeywords = plg_Search::purifyKeywods($rec->searchKeywords);
        
        $mvc->save_($rec, 'searchKeywords');
    }

    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        // Думите за търсене са името на документа-основания
        if(isset($rec->id)){
            if($urn = bgfisc_Register::getRec($mvc, $rec->id)->urn){
                $res .= ' ' . plg_Search::normalizeText($urn);
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if($urn = bgfisc_Register::getRec($mvc, $rec->id)->urn){
            $row->cashRegNum = bgfisc_Register::getUrlLink($urn);
        } else {
            $row->cashRegNum = ht::createHint('Стара продажба', 'Стара продажба, ще се генерира УНП, при издаване на фискален бон', 'warning', false);
        }
    }
    
    
    /**
     * След инпут на формата за избор на действие
     *
     * @see deals_DealMaster::act_Chooseaction
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     * @param stdClass  $rec
     *
     * @return void
     */
    protected static function on_AfterInputSelectActionForm($mvc, &$form, $rec)
    {
        if ($form->isSubmitted()) {
            $action = type_Set::toArray($form->rec->action);
            
            if (isset($action['pay'])) {
                $error = null;
                if (!bgfisc_plg_PrintFiscReceipt::checkBeforeConto($rec->caseId, currency_Currencies::getIdByCode($rec->currencyId), $error)) {
                    $form->setError('action', $error);
                }
            }
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        $block = tr("|*<span class='quiet'>|УНП|*</span>: {$data->row->cashRegNum}");
        $tpl->replace($block, 'ADDITIONAL_BLOCK');
    }
    
    
    /**
     * Ролбакване на транзакцията за контиране
     */
    public static function on_AfterRollbackConto($mvc, $res, $id)
    {
        if (!isset($res)) {
            $rec = $mvc->fetchRec($id);
            $rec->state = 'draft';
            $rec->brState = 'active';
            $rec->contoActions = null;
            
            if (acc_Journal::fetchByDoc($mvc, $rec->id)) {
                acc_Journal::deleteTransaction($mvc, $rec->id);
                $res = true;
            }
            
            $mvc->save($rec, 'state,brState,contoActions');
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
            $res = array();
            
            /* $cashAmount = round($rec->amountDeal / $rec->currencyRate, 2);

             $paymentId = ($rec->currencyId == acc_Periods::getBaseCurrencyCode($rec->valior)) ? -1 : cond_Payments::fetchField("#currencyCode = '{$rec->currencyId}'", 'id');
             $paymentCode = $Driver->getPaymentCode($registerRec, $paymentId);
             if(!isset($paymentCode)){
                 $errorMsg = 'Плащането няма зареден код';
                 expect(false);
             }

             $res[] = array('PAYMENT_TYPE' => $paymentCode, 'PAYMENT_AMOUNT' => $cashAmount);*/
        }
    }
    
    
    /**
     * Какви са артикулите
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
            $res = bgfisc_plg_PrintFiscReceipt::getProductsByOrigin($rec->containerId, $Driver, $registerRec);
        }
    }
    
    
    /**
     * След контиране на документа
     *
     * @param accda_Da $mvc
     * @param stdClass $rec
     */
    public static function on_AfterContoQuickSale($mvc, &$rec)
    {
        $actions = type_Set::toArray($rec->contoActions);
        if (isset($actions['pay'])) {
            if(!bgfisc_Register::doRequireFiscForConto($mvc, $rec)) return;

            // След плащане с продажбата редирект към екшън за печат на бележка
            Request::setProtected('hash');
            $url = toUrl(array($mvc, 'trytoprintreceipt', $rec->id, 'hash' => 'yes'));
            Request::removeProtected('hash');
            
            redirect($url);
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        // Ако ще се отпечатва фискален бон
        if (strtolower($action) == 'trytoprintreceipt') {
            Request::setProtected('hash');
            expect($hash = Request::get('hash', 'varchar'));
            expect($hash == 'yes');
            expect($id = Request::get('id', 'int'));
            expect($rec = $mvc->fetch($id));
            
            $exRec = bgfisc_PrintedReceipts::get($mvc, $rec->id);
            if(!empty($exRec->string)){
                $mvc->logErr('Рефреш на екшъна за печат на касова бележка', $id);
                
                redirect($mvc->getSingleUrlArray($rec->id), 'false', 'Има вече разпечатана касова бележка', 'error');
            }
            
            try {
                // Ако има контировка, издава се фискален бон
                if (acc_Journal::fetchByDoc($mvc, $id)) {
                    $obj = bgfisc_plg_PrintFiscReceipt::getFiscReceiptTpl($mvc, $rec);
                    Mode::set('wrapper', 'page_Empty');
                    $res = new core_ET('');
                    $res->append('<body><div class="fullScreenBg" style="position: fixed; top: 0; z-index: 1002; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9);display: block;"><h3 style="color: #fff; font-size: 56px; text-align: center; position: absolute; top: 30%; width: 100%">Отпечатва се фискален бон ...<br> Моля, изчакайте!</h3></div></body>');
                    
                    $js = $obj->js;
                    if (isset($obj->redirect)) {
                        $js = "document.location = '{$obj->redirect}';";
                    }
                    
                    $res->append($js, 'SCRIPTS');
                    
                    return false;
                }
            } catch (core_exception_Expect $e) {
                
                // Ако бележката не е издадена успешно ревъртва се
                reportException($e);
                $errorMsg = $e->getMessage();
                
                $mvc->rollbackConto($rec);
                $mvc->logWrite('Ревъртване на контировката', $rec);
                $mvc->logErr($errorMsg, $id);
                
                core_Statuses::newStatus($errorMsg, 'error');
                bgfisc_PrintedReceipts::removeWaitingLog($mvc, $rec->id);
                core_Locks::release("lock_{$mvc->className}_{$rec->id}");
                
                redirect($mvc->getSingleUrlArray($rec->id), 'false', $errorMsg, 'error');
            }
        }
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
        if (in_array($action, array('reject', 'restore', 'correction', 'revert')) && isset($rec)) {
            
            // Ако има отпечатана бележка, сделката не може да се оттегля/възстановява
            if (bgfisc_PrintedReceipts::getQrCode($mvc, $rec->id)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Изпълнява се преди контиране на документа
     */
    public static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
    {
        // Ако е платено със сделката локва се процеса на отпечатване
        $rec = $mvc->fetch($id);
        $actions = type_Set::toArray($rec->contoActions);
        if (isset($actions['pay'])) {
            bgfisc_PrintedReceipts::logPrinted($mvc, $rec->id);
            core_Locks::get("lock_{$mvc->className}_{$rec->id}", 90, 5, false);
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
        if (cash_Rko::haveRightFor('add', (object) array('originId' => $data->rec->containerId, 'threadId' => $data->rec->threadId))) {
            $data->toolbar->addBtn('РКО', array('cash_Rko', 'add', 'originId' => $data->rec->containerId, 'amountDeal' => $data->rec->amountDeal, 'ret_url' => true), 'ef_icon=img/16/money_delete.png,title=Създаване на нов разходен касов документ');
        }
    }
    
    
    /**
     * Реакция в счетоводния журнал при оттегляне на счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        $mvc->logWrite('Анулиране на документ', $rec->id);
    }
}
