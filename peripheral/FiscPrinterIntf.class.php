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
class peripheral_FiscPrinterIntf extends peripheral_DeviceIntf
{
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Връща цената с ддс и приспадната отстъпка, подходяща за касовия апарат
     *
     * @param float      $priceWithoutVat
     * @param float      $vat
     * @param float|null $discountPercent
     * @param float|null $quantity
     *
     * @return float
     */
    public function getDisplayPrice($priceWithoutVat, $vat, $discountPercent, $quantity)
    {
        return $this->class->getDisplayPrice($priceWithoutVat, $vat, $discountPercent, $quantity);
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
    
    
    /**
     * Какъв е кода на основанието за сторниране
     *
     * @param stdClass $rec - запис
     * @param string $reason   - основание
     * @return string|null  - намерения код или null, ако няма
     */
    public function getStornoReasonCode($rec, $reason)
    {
        return $this->class->getStornoReasonCode($rec, $reason);
    }
    
    
    /**
     * Какви са разрешените основания за сторниране
     *
     * @param stdClass $rec - запис
     * @return array  - $res
     */
    public function getStornoReasons($rec)
    {
        return $this->class->getStornoReasons($rec);
    }
    
    
    /**
     * Какъв е кода отговарящ на ДДС групата на артикула
     *
     * @param int $groupId  - ид на ДДС група
     * @param stdClass $rec - запис
     * @return string|null  - намерения код или null, ако няма
     */
    public function getVatGroupCode($groupId, $rec)
    {
        return $this->class->getVatGroupCode($groupId, $rec);
    }
    
    
    /**
     * Връща програмираните департаменти
     *
     * @param stdClass $rec
     * 
     * @return array
     */
    public function getDepartmentArr($rec)
    {
        
        return $this->class->getDepartmentArr($rec);
    }
}
