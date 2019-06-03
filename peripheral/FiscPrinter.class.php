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
class peripheral_FiscPrinter
{
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
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
    public function getJsForCashReceivedOrPaidOut($pRec, $operNum, $operPass, $amount, $printAvailability, $text = '')
    {
        return $this->class->getJsForCashReceivedOrPaidOut($pRec, $operNum, $operPass, $amount, $printAvailability, $text);
    }
    
    
    /**
     * Връща цената с ддс и приспадната отстъпка, подходяща за касовия апарат
     *
     * @param float      $priceWithoutVat
     * @param float      $vat
     * @param float|null $discountPercent
     *
     * @return float
     */
    public function getDisplayPrice($priceWithoutVat, $vat, $discountPercent)
    {
        return $this->class->getDisplayPrice($priceWithoutVat, $vat, $discountPercent);
    }
    
    
    /**
     * Дали във ФУ има е нагласена подадената валута
     *
     * @param stdClass $rec
     * @param string $currencyCode
     * @return boolean
     */
    public function isCurrencySupported($rec, $currencyCode)
    {
        return $this->class->isCurrencySupported($rec, $currencyCode);
    }
    
    
    /**
     * Какъв е кода на плащането в настройките на апарата
     *
     * @param stdClass $rec
     * @param int $paymentId
     * @return string|null
     */
    public function getPaymentCode($rec, $paymentId)
    {
        return $this->class->getPaymentCode($rec, $paymentId);
    }
}
