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
        $amount = 0.01;
        
        $action = self::EPAY_DOMAIN;
        //$action = "https://demo.epay.bg/";
        
        $token = epay_Tokens::force($initiatorClass, $initiatorId, $paymentId, $currency);
        $data = (object)array('action' => $action,
                              'total' => $amount,
                              'description' => "Плащане по поръчка {$token}",
                              'min' => epay_Setup::get('MIN'),
                              'checksum' => epay_Setup::get('CHECKSUM'),
                              'okUrl' => toUrl($okUrl, 'absolute'),
                              'cancelUrl' => toUrl($cancelUrl, 'absolute'),
        );
        
        $tpl = getTplFromFile("epay/tpl/Button.shtml");
        $tpl->placeObject($data);
        
        return $tpl;
    }
}