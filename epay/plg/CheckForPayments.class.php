<?php


/**
 * Плъгин проверяващ входящите имейли дали идват от ипей
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class epay_plg_CheckForPayments extends core_Plugin
{
    

    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        // Ако входящия имейл не е от ePay.bg, продължава се
        if(!self::isFromEpay($rec)) return;
        
        // Опит за извличане на информация за плащането
        $paymentData = self::getPaymentData($rec);
        if(isset($paymentData['reason'])){
            
            // Проверка има ли актуален токен за плащане чрез ипей
            if($tokenData = epay_Tokens::findToken($paymentData['reason'])){
                //@TODO
            }
        }
    }
    
    
    /**
     * Проверява дали входящия имейл е от ePay.bg със малък спам рейтинг
     *
     * @param stdClass $rec
     * @return boolean
     */
    private static function isFromEpay($rec)
    {
        if($rec->spamScore > 3) return false;
        
        $needle = strtolower(trim(epay_Setup::get('EMAIL_DOMAIN')));
        $search = strpos(strtolower(trim($rec->fromEml)), $needle);
        
        return ($search !== false) ? true : false;
    }
    
    
    /**
     * Опит за намиране на информация за онлайн плащане
     * 
     * @param stdClass $rec
     * @return array $res
     *            ['amount'] - сума в BGN
     *            ['payer']  - име на платеца
     *            ['reason'] - основание
     */ 
    private static function getPaymentData($rec)
    {
        $res = array();
        $text = $rec->textPart;
        
        // Извличане на сумата за плащане
        $amountMatches = array();
        if(preg_match('/Amount:\s*([0-9]+\.[0-9]+)\s*BGN\s*/', $text, $amountMatches)){
            if(isset($amountMatches[1])){
                $res['amount'] = trim(core_Type::getByName('double')->fromVerbal($amountMatches[1]));
            }
        }
        
        // Извличане на платеца
        $payerMatches = array();
        if(preg_match('/Payer:\s*(.*?)(\n|Reason:\s+)/', $text, $payerMatches)){
            if(isset($payerMatches[1])){
                $res['payerName'] = trim(core_Type::getByName('varchar')->fromVerbal($payerMatches[1]));
            }
        }
        
        // Извличане на сумата за плащане
        $reasonMatches = array();
        if(preg_match('/Reason:\s*(.*?)(\n|Amount:\s+)/', $text, $reasonMatches)){
            if(isset($reasonMatches[1])){
                $res['reason'] = trim(core_Type::getByName('varchar')->fromVerbal($reasonMatches[1]));
            }
        }
        
        return $res;
    }
}