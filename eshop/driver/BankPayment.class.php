<?php


/**
 * Банково плащане за е-магазина
 *
 * @category  bgerp
 * @package   epay
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @title     Банково плащане за е-магазина
 * 
 * @since     v 0.1
 */
class eshop_driver_BankPayment extends core_BaseClass
{
    /**
     * Поддържани интерфейси
     *
     * var string|array
     */
    public $interfaces = 'cond_OnlinePaymentIntf';

    
    /**
     * Заглавие
     */
    public $title = 'Банково плащане';
    
    
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
        $html = $this->getText4Email($paymentId);
        
        return $html;
    }
    
    
    /**
     * Добавя за уведомителния имейл 
     * 
     * @param int $paymentId
     * 
     * @return string|null
     */
    public function getText4Email($paymentId)
    {
        $settings = cms_Domains::getSettings();
        $separator = Mode::is('text', 'plain') ? "\n" : "<br>";
        $paymentName = cond_PaymentMethods::getVerbal($paymentId, 'name');
        
        $html = $separator;
        $html .= tr("|Съгласно избрания начин на плащане, моля преведете сумата за плащане по тази сметка|*:") . $separator;
        
        if(!Mode::is('text', 'plain')){
            $ownAccount = bank_OwnAccounts::getVerbal($settings->ownAccount, 'bankAccountId');
            $ownAccount = "<b>{$ownAccount}</b>";
        }
        
        $html .= "IBAN {$ownAccount}" . $separator;
        $html .= tr("|Плащането трябва да се извърши по следния начин|*:") . $separator;
        $html .= tr($paymentName) . $separator;
        
        if(!Mode::is('text', 'plain')){
            $html = "<div class='eshop-bank-payment'>{$html}</div>";
        }
        
        return $html;
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
        return false;
    }
}