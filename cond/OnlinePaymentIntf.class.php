<?php


/**
 * Интерфейс за онлайн плащане
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_OnlinePaymentIntf extends embed_DriverIntf
{
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Генериране на бутон за онлайн плащане
     * 
     * @param int $paymentId         - начин на плащане
     * @param double $amount         - сума на плащане
     * @param string $currency       - валута на плащане
     * @param string $okUrl          - урл при потвърждение
     * @param string $cancelUrl      - урл при отказ
     * @param mixed $initiatorClass  - класа инициатор
     * @param int $initiatorId       - ид на инициатор
     * @param array $soldItems
     *                  [sysId]      - системен номер на артикула
     *                  [name]       - име на артикула
     *                  [quantity]   - продадено количество
     *                  [price]      - цена на артикула
     * 
     * @return string $button        - бутон за онлайн плащане
     */
    public function getPaymentBtn($paymentId, $amount, $currency, $okUrl, $cancelUrl, $initiatorClass, $initiatorId, $soldItems = array())
    {
        return $this->class->getPaymentBtn($paymentId, $amount, $currency, $okUrl, $cancelUrl, $initiatorClass, $initiatorId, $soldItems);
    }
    
    
    /**
     * Задължително ли е онлайн плащането или е опционално
     *
     * @param int $paymentId
     * @param mixed $initiatorClass
     * @param int $initiatorId
     * @return boolean
     */
    public function isPaymentMandatory($paymentId, $initiatorClass, $initiatorId)
    {
        return $this->class->isPaymentMandatory($paymentId, $initiatorClass, $initiatorId);
    }
    
    
    /**
     * Добавя за уведомителния имейл 
     * 
     * @param int $paymentId
     * @param stdClass $cartRec
     * 
     * @return string|null
     */
    public function getText4Email($paymentId, $cartRec)
    {
        return $this->class->getText4Email($paymentId, $cartRec);
    }
    
    
    /**
     * Информативния текст за онлайн плащането
     * 
     * @param mixed $rec
     * @return string|null
     */
    public function getDisplayHtml($rec)
    {
        return $this->class->getDisplayHtml($rec);
    }
    
    
    /**
     * Хтмл за показване след финализиране на плащането
     *
     * @param int $id
     * @param stdClass $cartRec
     * @return core_ET|null $tpl
     */
    function displayHtmlAfterPayment($id, $cartRec)
    {
        return $this->class->displayHtmlAfterPayment($id, $cartRec);
    }
    
    
    /**
     * Връща типа на метода на плащане
     *
     * @param mixed $id
     * @return string
     */
    public function getPaymentType($id)
    {
        return $this->class->getPaymentType($id);
    }
}