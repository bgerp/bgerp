<?php


/**
 * Базов документ за наследяване на платежни документи
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class deals_PaymentDocument extends core_Master
{
    /**
     * Име на полето за основание
     */
    public $reasonField = 'reason';
    
    
    /**
     * Дефолтен брой копия при печат
     *
     * @var int
     */
    public $defaultCopiesOnPrint = 2;
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        // Обновяваме автоматично изчисления метод на плащане на всички фактури в нишката на документа
        $threadId = ($rec->threadId) ? $rec->threadId : $mvc->fetchField($rec->id, 'threadId');
        deals_Helper::updateAutoPaymentTypeInThread($threadId);
    }
    
    
    /**
     * След оттегляне на документа
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $rec)
    {
        $id = (is_object($rec)) ? $rec->id : $rec;
        if ($rec->brState == 'active') {
            
            // Обновяваме автоматично изчисления метод на плащане на всички фактури в нишката на документа
            $threadId = ($rec->threadId) ? $rec->threadId : $mvc->fetchField($id, 'threadId');
            deals_Helper::updateAutoPaymentTypeInThread($threadId);
        }
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $this->getRecTitle($rec);
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        
        $recTitle = currency_Currencies::decorate($rec->amount, $rec->currencyId);
        $date = ($rec->valior) ? $rec->valior : (isset($rec->termDate) ? $rec->termDate : null);
        if (isset($date)) {
            $recTitle .= ' / ' . dt::mysql2verbal($date, 'd.m.y');
        }
        
        $row->recTitle = html_entity_decode($recTitle);
        
        return $row;
    }


    /**
     * Връща информация за сумите по платежния документ
     *
     * @param mixed $id
     * @return object
     */
    public function getPaymentData($id)
    {
        if(is_object($id)){
            $rec = $id;
        } else {
            $rec = $this->fetchRec($id, '*', false);
        }

        return (object)array('amount' => $rec->amount, 'currencyId' => $rec->currencyId, 'amountDeal' => $rec->amountDeal, 'dealCurrencyId' => $rec->dealCurrencyId);
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($fields['-list'])){
            $invoicesArr = deals_InvoicesToDocuments::getInvoiceArr($rec->containerId);
            if(countR($invoicesArr)){
                $invLinkArr = array();
                foreach ($invoicesArr as $iArr){
                    $invLinkArr[] = doc_Containers::getDocument($iArr->containerId)->getLink(0)->getContent();
                }
                $row->invoices = implode(',', $invLinkArr);
            }
        }
    }
}
