<?php

/**
 * Плъгин позволяващ на документ да се посочва към кои фактури е
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deals_plg_SelectInvoicesToDocument extends core_Plugin
{

    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    protected static function on_AfterDescription(core_Master &$mvc)
    {
        $mvc->FLD('fromContainerId', 'int', 'caption=Към,input=hidden,silent');
        setIfNot($mvc->canSelectOnlyOneInvoice, true);
    }


    /**
     * Проверка след изпращането на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted()) {
            if(isset($form->rec->id)){
                $form->rec->_isEdited = true;
            }
        }
    }


    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    protected static function on_BeforeSave($mvc, $id, $rec)
    {
        if(isset($rec->id) && $rec->_isEdited){
            $oData = $mvc->getPaymentData($rec->id);
            $nData = $mvc->getPaymentData($rec);

            // Прир едакция се проверява дали е сменяна валутата или сумата на документа
            if($oData->amount != $nData->amount){
                $rec->_amountChange = ($oData->amount > $nData->amount) ? 'decrease' : 'increase';
            }
            if($oData->amount != $nData->amount){
                $rec->_currencyChange = true;
            }
        }
    }


    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        if(isset($rec->fromContainerId)){

            // След създаване синхронизиране на модела
            $expectedAmountToPayData = deals_InvoicesToDocuments::getExpectedAmountToPay($rec->fromContainerId, $rec->containerId);
            $paymentCurrencyCode = currency_Currencies::getCodeById($mvc->getPaymentData($rec)->currencyId);

            $vAmount = currency_CurrencyRates::convertAmount($expectedAmountToPayData->amount, null, $expectedAmountToPayData->currencyCode, $paymentCurrencyCode);
            $vAmount = round($vAmount, 2);

            $paymentData = $mvc->getPaymentData($rec);
            $vAmount = min($paymentData->amount, $vAmount);

            if($vAmount){
                $dRec = (object)array('documentContainerId' => $rec->containerId, 'containerId' => $rec->fromContainerId, 'amount' => $vAmount);
                deals_InvoicesToDocuments::save($dRec);
            }
        }
    }


    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave($mvc, &$id, $rec)
    {
        if($rec->_amountChange || $rec->_currencyChange){

            // Какви са разпределените ф-ри
            $iQuery = deals_InvoicesToDocuments::getQuery();
            $iQuery->where("#documentContainerId = {$rec->containerId}");
            $iRecs = $iQuery->fetchAll();
            $count = countR($iRecs);

            // Ако няма нищо не се прави
            if(!$count) return;

            // Ако са повече от 1 се ресетват
            if($count > 1){
                $ids = arr::extractValuesFromArray($iRecs, 'id');
                $ids = implode(',', $ids);
                deals_InvoicesToDocuments::delete("#documentContainerId = {$rec->containerId} AND #id IN ({$ids})");
                core_Statuses::newStatus('Информацията за отнасянията по фактури е изтрита, поради промяна на сумата и/или валутата на документа. Моля разпределете ги отново');
                if(isset($rec->fromContainerId)){
                    $rec->fromContainerId = null;
                    $mvc->save_($rec, 'fromContainerId');
                }
            } elseif($rec->_amountChange == 'decrease') {

                // Ако е само една и сумата е намалена то остава по-малкото от новата сума и старата разпределена
                $nData = $mvc->getPaymentData($rec);
                $onlyInvoiceRec = $iRecs[key($iRecs)];
                $onlyInvoiceRec->amount = min($nData->amount, $onlyInvoiceRec->amount);
                cls::get('deals_InvoicesToDocuments')->save($onlyInvoiceRec, 'amount');
            }
        }
    }


    /**
     * Изпълнява се след закачане на детайлите
     */
    protected static function on_BeforeAttachDetails(core_Mvc $mvc, &$res, &$details)
    {
        $details = arr::make($details);
        $details['InvoicesToDocuments'] = 'deals_InvoicesToDocuments';
        $details = arr::fromArray($details);
    }


    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        if (!isset($res)) {
            $res = plg_Search::getKeywords($mvc, $rec);
        }

        if(isset($rec->containerId)){
            $invoicesArr = deals_InvoicesToDocuments::getInvoiceArr($rec->containerId);
            foreach ($invoicesArr as $iRec) {
                $invRec = sales_Invoices::fetch("#containerId = {$iRec->containerId}", 'number');
                $numberPadded = sales_Invoices::getVerbal($invRec, 'number');

                $res .= ' ' . plg_Search::normalizeText($invRec->number) . ' ' . plg_Search::normalizeText($numberPadded);
            }
        }
    }


    /**
     * Опциите за избор на основание
     */
    public static function on_AfterGetReasonContainerOptions($mvc, &$res, $rec)
    {
        $res = array();
        $threadsArr = deals_Helper::getCombinedThreads($rec->threadId);
        $iArr = ($rec->isReverse == 'yes') ? deals_Helper::getInvoicesInThread($threadsArr, null, false, false, true) : deals_Helper::getInvoicesInThread($threadsArr, null, true, true, false);
        foreach ($iArr as $k => $number){
            $iRec = doc_Containers::getDocument($k)->fetch();
            $rate = !empty($iRec->displayRate) ? $iRec->displayRate : $iRec->rate;
            $vAmount = 0;
            if($rate){
                $vAmount = abs(round(($iRec->dealValue + $iRec->vatAmount - $iRec->discountAmount) / $rate, 2));
            }
            $res[$k] = "{$number} ({$vAmount} {$iRec->currencyId})";
        }

        return $res;
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
        if ($action == 'selectinvoice' && isset($rec)) {
            $hasInvoices = $mvc->getReasonContainerOptions($rec);

            if ($rec->state == 'rejected' || !$hasInvoices) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Може ли документа да се отнася към повече от една ф-ри
     */
    protected static function on_AfterCanBeOnlyToOneInvoice($mvc, &$res, $rec)
    {
        if(!$res){
            $res = $mvc->canSelectOnlyOneInvoice;
        }
    }
}
