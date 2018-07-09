<?php


/**
 * Плъгин позволяващ на документ да се посочва към коя фактура е
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deals_plg_SelectInvoice extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Master &$mvc)
    {
        $mvc->FLD('fromContainerId', 'int', 'caption=Към фактура,input=hidden,silent');
        $mvc->setDbIndex('fromContainerId');
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        if (isset($rec->fromContainerId)) {
            $number = sales_Invoices::fetchField("#containerId = {$rec->fromContainerId}", 'number');
            $numberPadded = str_pad($number, '10', '0', STR_PAD_LEFT);
            $res .= ' ' . plg_Search::normalizeText($number) . ' ' . plg_Search::normalizeText($numberPadded);
        }
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($rec->fromContainerId)) {
            $Document = doc_Containers::getDocument($rec->fromContainerId);
            $number = str_pad($Document->fetchField('number'), '10', '0', STR_PAD_LEFT);
            $row->fromContainerId = "#{$Document->abbr}{$number}";
            if (!Mode::isReadOnly()) {
                $row->fromContainerId = ht::createLink($row->fromContainerId, $Document->getSingleurlArray());
            }
        }
        
        if (!Mode::isReadOnly() && !isset($fields['-list'])) {
            if ($mvc->haveRightFor('selectinvoice', $rec)) {
                $row->fromContainerId = (!empty($rec->fromContainerId)) ? $row->fromContainerId : '<div class=border-field></div>';
                $row->fromContainerId = $row->fromContainerId . ht::createLink('', array($mvc, 'selectInvoice', $rec->id, 'ret_url' => true), false, 'title=Смяна на фактурата към която е документа,ef_icon=img/16/edit.png');
            }
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Mvc $mvc
     * @param mixed    $res
     * @param string   $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action != 'selectinvoice') {
            
            return;
        }
        
        $mvc->requireRightFor('selectinvoice');
        expect($id = Request::get('id', 'int'));
        expect($rec = $mvc->fetch($id));
        $mvc->requireRightFor('selectinvoice', $rec);
        
        $form = cls::get('core_Form');
        $form->title = core_Detail::getEditTitle($mvc, $rec->id, 'информация', $rec->id);
        $form->FLD('fromContainerId', 'int', 'caption=За фактура');
        
        $invoices = ($rec->isReverse == 'yes') ? deals_Helper::getInvoicesInThread($rec->threadId, null, false, false, true) : deals_Helper::getInvoicesInThread($rec->threadId, null, true, true, false);
        $form->setOptions('fromContainerId', array('' => '') + $invoices);
        $form->setDefault('fromContainerId', $rec->fromContainerId);
        
        $form->input();
        if ($form->isSubmitted()) {
            $rec->fromContainerId = $form->rec->fromContainerId;
            $mvc->save($rec, 'fromContainerId,searchKeywords');
            
            if ($mvc instanceof deals_PaymentDocument) {
                deals_Helper::updateAutoPaymentTypeInThread($rec->threadId);
                doc_DocumentCache::cacheInvalidation($rec->containerId);
            }
            
            $mvc->logWrite("Отнасяне към фактура|* '{$invoices[$rec->fromContainerId]}'", $rec->id);
            followRetUrl(null, 'Промяната е записана успешно');
        }
        
        // Добавяне на тулбар
        $form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png, title = Импорт');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        // Рендиране на опаковката
        $tpl = $mvc->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);
        
        $res = $tpl;
        
        return false;
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
            $hasInvoices = ($rec->isReverse == 'yes') ? deals_Helper::getInvoicesInThread($rec->threadId, null, false, false, true) : deals_Helper::getInvoicesInThread($rec->threadId, null, true, true, false);
            
            if ($rec->state == 'rejected' || !$hasInvoices) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    public static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = $data->form;
        
        // Ако е към проформа да се показва в описанието
        if (isset($mvc->reasonField, $form->rec->fromContainerId)) {
            $fromDocument = doc_Containers::getDocument($form->rec->fromContainerId);
            if ($fromDocument->isInstanceOf('sales_Proformas')) {
                $form->setDefault($mvc->reasonField, tr('Към') . ' #' . $fromDocument->getHandle());
                unset($form->rec->fromContainerId);
            }
        }
    }
}
