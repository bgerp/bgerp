<?php


/**
 * Драйвер за онлайн плащане чрез ePay.bg
 *
 * @category  bgerp
 * @package   epay
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @title     Плащане чрез ePay.bg
 * 
 * @since     v 0.1
 */
class epay_driver_OnlinePayment extends core_BaseClass
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
    public $title = 'Плащане чрез ePay.bg';
    
    
    /**
     * Какъв е домейна на ePay.bg
     */
    const EPAY_DOMAIN = 'https://www.epay.bg/';
    
    
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
        //@TODO тестово
        $amount = 0.01;
        $amount = round($amount, 2);
        
        $action = self::EPAY_DOMAIN;
        $reason = epay_Tokens::getPaymentReason($initiatorClass, $initiatorId);
        $okUrl['description'] = $reason;
        
        if($accountId = epay_Setup::get('OWN_ACCOUNT_ID')){
            $okUrl['accountId'] = $accountId;
        }
        
        Request::setProtected('description,accountId');
        $okUrl = toUrl($okUrl, 'absolute');
        Request::removeProtected('description,accountId');
        
        //@TODO тестово
        //$action = $okUrl;
        
        $data = (object)array('action' => $action,
                              'total' => $amount,
                              'description' => $reason,
                              'min' => epay_Setup::get('MIN'),
                              'checksum' => epay_Setup::get('CHECKSUM'),
                              'urlOk' => $okUrl,
                              'BTN_IMAGE' => sbf('epay/img/button.gif', ''),
                              'cancelUrl' => toUrl($cancelUrl, 'absolute'),
        );
        
        $tpl = getTplFromFile("epay/tpl/Button.shtml");
        $tpl->placeObject($data);
        
        return $tpl;
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
        $isMandatory = epay_Setup::get('MANDATORY_BEFORE_FINALIZATION');
        
        return ($isMandatory == 'yes') ? true : false;
    }
}