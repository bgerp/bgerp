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
        $rec = cond_PaymentMethods::fetch($paymentId);
        $separator = Mode::is('text', 'plain') ? "\n" : "<br>";
        $paymentName = cond_PaymentMethods::getVerbal($paymentId, 'name');
        
        $html = $separator;

        if(!Mode::is('text', 'plain')){
            $html .= "<div class='info-block'>";
        }

        $html .= tr("|Избрано е плащане по банков път. Моля, преведете дължимата сума по сметка|*:") . $separator;
        $ownAccount = bank_OwnAccounts::getVerbal($rec->ownAccount, 'bankAccountId');

        if(!Mode::is('text', 'plain')){
            $ownAccount = "<b>{$ownAccount}</b>";
        }
        
        $html .= "IBAN {$ownAccount}" . $separator;
        if(!Mode::is('text', 'plain')){
            $html .= "</div>";
            $html = "<div class='eshop-bank-payment' style='margin-bottom: 20px;'>{$html}</div>";
        } else {
            $html .= tr($paymentName);
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


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('ownAccount', 'key(mvc=bank_OwnAccounts,select=bankAccountId,allowEmpty)', 'caption=Банкова сметка,mandatory,after=onlinePaymentDriver');
    }


    /**
     * Извиква се след успешен запис в модела
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param int               $id
     * @param stdClass          $rec
     */
    protected static function on_AfterRecToVerbal($Driver, embed_Manager $Embedder, &$row, $rec)
    {
        $row->ownAccount = bank_OwnAccounts::getHyperlink($rec->ownAccount, true);
    }
}