<?php


/**
 * Интерфейс за връзка с везни
 *
 * @category  bgerp
 * @package   wscales
 *
 * @author    Yusein Yuseinov
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class peripheral_FiscPrinterIp extends peripheral_FiscPrinterIntf
{
    /**
     * Отпечатва бележка с подадените параметри
     *
     * @param stdClass $pRec
     * @param array    $params
     *
     * @return  boolean|string
     */
    public function printReceipt($pRec, $params)
    {
        return $this->class->printReceipt($pRec, $params);
    }
    
    
    /**
     * Проверява дали има връзка с ФУ
     *
     * @param stdClass $pRec
     *
     * @return boolean
     */
    public function checkConnection($pRec)
    {
        return $this->class->checkConnection($pRec);
    }
    
    
    /**
     * Отпечатва дубликат на последната бележка
     *
     * @param stdClass $pRec
     *
     * @return boolean
     */
    public function printDuplicate($pRec)
    {
        return $this->class->printDuplicate($pRec);
    }
    
    
    /**
     * Добавя/изкарва пари от касата
     *
     * @param stdClass $pRec
     * @param int      $operNum
     * @param string   $operPass
     * @param float    $amount
     * @param boolean  $printAvailability
     * @param string   $text
     *
     * @return boolean
     */
    public function cashReceivedOrPaidOut($pRec, $operNum, $operPass, $amount, $printAvailability = false, $text = '')
    {
        return $this->class->cashReceivedOrPaidOut($pRec, $operNum, $operPass, $amount, $printAvailability, $text);
    }
    
    
    /**
     * Записва бележката от съответното ФУ във файл и му връща манипулатора
     * 
     * @param stdClass $pRec
     * @param integer|null $receiptNum
     * 
     * @return false|string
     */
    public function saveReceiptToFile($pRec, $receiptNum = null)
    {
        return $this->class->saveReceiptToFile($pRec, $receiptNum);
    }
}
