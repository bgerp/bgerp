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

        if (!doc_plg_HidePrices::canSeePriceFields($this, $rec)) {
            $recTitle = tr('Заличено');
        } else {
            $recTitle = currency_Currencies::decorate($rec->amount, $rec->currencyId);
        }

        $date = ($rec->valior) ? $rec->valior : ($rec->termDate ?? null);
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

        return (object)array('amount' => $rec->amount, 'currencyId' => $rec->currencyId, 'amountDeal' => $rec->amountDeal, 'dealCurrencyId' => $rec->dealCurrencyId, 'operationSysId' => $rec->operationSysId, 'isReverse' => ($rec->isReverse == 'yes'));
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($fields['-list'])){
            $invoicesArr = deals_InvoicesToDocuments::getInvoiceArr($rec->containerId, array(), true);
            if(countR($invoicesArr)){
                $row->invoices = implode(',', $invoicesArr);
            }
        }
    }


    /**
     * Дали да се показва предупреждение при избраната операция
     *
     * @param string $operationSysId
     * @param bgerp_iface_DealAggregator $dealInfo
     * @param stdClass $rec
     * @return false|string
     */
    protected function getOperationWarning($operationSysId, bgerp_iface_DealAggregator $dealInfo, $rec)
    {
        // ако в сделката е обявен % авансово плащане
        // и ако създаваното в момента документ е с избрана опция Плащане от Клиент/Доставчик
        // и ако сумата на платения до момента аванс + сумата в създавания документ е < очаквания аванс +20%
        // извеждаме Уорнинг:
        if(in_array($operationSysId, array('customer2case', 'customer2bank', 'bank2supplier', 'case2supplier'))) {
            $currentDp = $dealInfo->get('downpayment');
            $agreedDp = $dealInfo->get('agreedDownpayment');
            if (!empty($agreedDp)) {
                $checkAmount = $currentDp + ($rec->amountDeal * $rec->rate);

                $name = in_array($operationSysId, array('bank2supplier', 'case2supplier')) ? 'Плащане към доставчик' : 'Плащане от клиент';
                if ($checkAmount < $agreedDp * 1.2) return "Въведената сума предполага възможно доплащане по аванс, а е избрана опцията за окончателно \"{$name}\"|*!";
            }
        }

        return false;
    }
}
