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
class peripheral_FiscPrinterWeb extends peripheral_FiscPrinterIntf
{
    /**
     * Връща JS функция, която да се изпълни и да отпечата бележката
     *
     * @param stdClass $pRec
     * @param array    $paramsArr
     *
     * @return string
     */
    public function getJs($pRec, $paramsArr)
    {
        return $this->class->getJs($pRec, $paramsArr);
    }
    
    
    /**
     * Връща JS функция, която да провери дали има връзка с устройството
     *
     * @param stdClass $pRec
     *
     * @return string
     */
    public function getJsIsWorking($pRec)
    {
        return $this->class->getJsIsWorking($pRec);
    }
    
    
    /**
     * Връща JS функция, за отпечатване на дубликат
     *
     * @param stdClass $pRec
     *
     * @return string
     */
    public function getJsForDuplicate($pRec)
    {
        return $this->class->getJsForDuplicate($pRec);
    }
    
    
    /**
     * Връща JS функция за добавяне/изкарване на пари от касата
     *
     * @param stdClass $pRec
     * @param int      $operNum
     * @param string   $operPass
     * @param float    $amount
     * @param boolean  $printAvailability
     * @param string   $text
     *
     * @return string
     */
    public function getJsForCashReceivedOrPaidOut($pRec, $operNum, $operPass, $amount, $printAvailability = false, $text = '')
    {
        return $this->class->getJsForCashReceivedOrPaidOut($pRec, $operNum, $operPass, $amount, $printAvailability, $text);
    }
}
