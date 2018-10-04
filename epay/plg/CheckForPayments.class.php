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
        self:checkEmail($rec);
    }
    
    
    /**
     * Проверка на имейла
     * 
     * @param stdClass $rec - запис
     * @param array $explained - обяснение
     * 
     * @return void
     */
    private static function checkEmail($rec, &$explained = array())
    {
        // Ако входящия имейл не е от ePay.bg, продължава се
        $explained['SENDER'] = $rec->fromEml;
        $explained['SPAM'] = $rec->spamScore;
        
        if(!self::isFromEpay($rec)){
            $explained['CHECK'] = "NOT FROM EPAY";
            return;
        }
        
        $explained['CHECK'] = 'FROM EPAY';
        
        // Опит за извличане на информация за плащането
        $paymentData = self::getPaymentData($rec);
        $explained['PAYMENT_DATA'] = $paymentData;
        if(isset($paymentData['reason'])){
            
            // Проверка има ли актуален токен за плащане чрез ипей
            if($tokenData = epay_Tokens::findToken($paymentData['reason'])){
                $explained['TOKEN_DATA'] = $tokenData;
                $accountId = epay_Setup::get('OWN_ACCOUNT_ID');
                
                // Ако е намерен инциатор, информира се за постъпилото плащане
                if(cls::haveInterface('eshop_InitiatorPaymentIntf', $tokenData['initiatorClassId'])){
                    $explained['ACTIONS'] = cls::get($tokenData['initiatorClassId'])->receivePayment($tokenData['initiatorId'], $paymentData['reason'], $paymentData['payerName'], $paymentData['amount'], 'BGN', $accountId);
                } else {
                    $explained['ACTIONS'] = 'NONE';
                }
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
        if($rec->spamScore > epay_Setup::get('EMAIL_SPAM_SCORE')) return false;
        
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
        
        $newReason = epay_Tokens::getPaymentReason('eshop_Carts', 252);
        $text = str_replace('Bankov prevod 6U7EP0000000ASN 134155', $newReason, $text);
        
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
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     *
     * @return bool|null
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        //@TODO да се премахне като е готово
        if(haveRole('debug') && haveRole('admin')){
            $data->toolbar->addBtn('Проверка', array($mvc, 'checkIncoming', 'id' => $data->rec->id), 'ef_icon=img/16/bug.png,title=Дебъг');
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Mvc $mvc
     * @param mixed    $res
     * @param string   $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        if (strtolower($action) == strtolower('checkIncoming')) {
            requireRole('debug');
            requireRole('admin');
            
            $id = Request::get('id', 'int');
            $rec = $mvc->fetch($id);
            self::checkEmail($rec, $explained);
            
            Mode::set('wrapper', 'page_Empty');
            
            $res = ht::wrapMixedToHtml(ht::mixedToHtml($explained, 4));
            
            return false;
        }
    }
}