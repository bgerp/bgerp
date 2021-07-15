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

            if($oData->amount != $nData->amount || $oData->currencyId != $nData->currencyId){
                if(deals_InvoicesToDocuments::count("#documentContainerId = {$rec->containerId}")){
                    $rec->_resetInvoices = true;
                }
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
            $amount = $mvc->getPaymentData($rec)->amount;
            $dRec = (object)array('documentContainerId' => $rec->containerId, 'containerId' => $rec->fromContainerId, 'amount' => $amount);
            deals_InvoicesToDocuments::save($dRec);
        }
    }


    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave($mvc, &$id, $rec)
    {
        if($rec->_resetInvoices){
            deals_InvoicesToDocuments::delete("#documentContainerId = {$rec->containerId}");
            core_Statuses::newStatus('Информацията за отнасянията по фактури е изтрита, поради промяна на сумата и/или валутата на документа. Моля разпределете ги отново');
            if(isset($rec->fromContainerId)){
                $rec->fromContainerId = null;
                $mvc->save_($rec, 'fromContainerId');
            }
        }
    }


    /**
     * Изпълнява се след закачане на детайлите
     */
    protected static function on_BeforeAttachDetails(core_Mvc $mvc, &$res, &$details)
    {
        $details = arr::make($details);
        $details['Invoices'] = 'deals_InvoicesToDocuments';
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
        $threadsArr = array($rec->threadId => $rec->threadId);

        // Ако в документа е разрешено да се показват ф-те към обединените сделки
        if($firstDocument = doc_Threads::getFirstDocument($rec->threadId)){
            $closedDocuments = keylist::toArray($firstDocument->fetchField('closedDocuments'));
            if(countR($closedDocuments)){
                $docQuery = $firstDocument->getQuery();
                $docQuery->in('id', $closedDocuments);
                $docQuery->show('threadId');
                $threadsArr += arr::extractValuesFromArray($docQuery->fetchAll(), 'threadId');
            }
        }

        $res = array();
        $iArr = ($rec->isReverse == 'yes') ? deals_Helper::getInvoicesInThread($threadsArr, null, false, false, true) : deals_Helper::getInvoicesInThread($threadsArr, null, true, true, false);
        foreach ($iArr as $k => $number){
            $iRec = doc_Containers::getDocument($k)->fetch();
            $vAmount = abs(round(($iRec->dealValue + $iRec->vatAmount - $iRec->discountAmount) / $iRec->displayRate, 2));
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
