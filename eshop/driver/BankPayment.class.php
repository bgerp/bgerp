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
        $html = '';
        
        return $html;
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
        $data = $this->getTextData($paymentId, $cartRec);
        $txt = "|Моля, приведете сумата от|* " . $data->AMOUNT. " |по банков път, към|*:\n";
        $txt .= "{$data->MyCompany}\n"; 
        $txt .= "{$data->MyAddress}\n"; 
        $txt .= "BIC: {$data->BIC}\n";
        $txt .= "IBAN: {$data->IBAN}\n";
        $txt .= "|В основанието за плащане, моля напишете|*: {$data->HANDLER}\n";
        $txt .= "|Може да свалите примерно платежно нареждане от|* {$data->PO_LINK}\n";
        
        return tr($txt);
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
        $fieldset->FLD('ownAccount', 'key(mvc=bank_OwnAccounts,select=bankAccountId,allowEmpty)', 'caption=Банкова сметка,mandatory,after=onlinePaymentText');
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
    
    
    /**
     * Информативния текст за онлайн плащането
     *
     * @param mixed $rec
     * @return string|null
     */
    public function getDisplayHtml($rec)
    {
        return null;
    }
    
    private function getTextData($id, $cartRec)
    {
        $res = array();
        
        $settings = cms_Domains::getSettings($cartRec->domainId);
        $rec = cond_PaymentMethods::fetchRec($id);
        
        $ownCompany = crm_Companies::fetchOwnCompany();
        $res['MyCompany'] = cls::get('type_Varchar')->toVerbal($ownCompany->company);
        $res['MyCompany'] = transliterate(tr($res['MyCompany']));
        $res['MyAddress'] = trim(cls::get('crm_Companies')->getFullAdress($ownCompany->companyId, true, false)->getContent());
        if(Mode::is('text', 'plain')){
            $res['MyAddress'] = str_replace('<br>', ',', $res['MyAddress']);
        }
        
        $res['HANDLER'] = "#Sal{$cartRec->saleId}";
        
        $bankInfo = bank_OwnAccounts::getOwnAccountInfo($rec->ownAccount);
        $res['BANK'] = tr($bankInfo->bank);
        $res['BIC'] = $bankInfo->bic;
        $res['IBAN'] = bank_OwnAccounts::getVerbal($rec->ownAccount, 'bankAccountId');
        
        $amount = currency_CurrencyRates::convertAmount($cartRec->total, null, null, $settings->currencyId);
        $amount = core_Type::getByName('double(decimals=2)')->toVerbal($amount);
        $amount= str_replace('&nbsp;', ' ', $amount);
        $res['AMOUNT'] = "{$amount} {$settings->currencyId}";
        
        return (object)$res;
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
        $lang = cms_Domains::fetchField($cartRec->domainId, 'lang');
        core_Lg::push($lang);
        
        $tpl = getTplFromFile('eshop/tpl/BankPaymentInfo.shtml');
        
        $data = $this->getTextData($id, $cartRec);
        $tpl->placeObject($data);
        
        $shopUrl = cls::get('eshop_Groups')->getUrlByMenuId(null);
        $shopLink = ht::createLink(tr("|*« |Към магазина|*"), $shopUrl);
        $tpl->replace($shopLink, 'BACK_BTN');
        
        core_Lg::pop();
        
        return $tpl;
    }
}