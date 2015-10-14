<?php 


/**
 * Клас 'email_Returned' - регистър на обратните разписки
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Milen Georgiev <milen2experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Receipts extends email_ServiceEmails
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Обратни разписки за получаване на имейл";
    
    
    /**
     * Масив с думи, които НЕ трябва да съществуват в стринга
     */
    protected static $negativeWordsArr = array('fail', 'sorry', 'rejected', 'not be delivered', "couldn't be delivered");
    
    
    /**
     * Масив с думи, които трябва да съществуват в стринга
     */
    protected static $positiveWordsArr = array('delivered to the user');
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->addFields();  
    }
    
    
    /**
     * Проверява дали в $mime се съдържа върнато писмо и
     * ако е така - съхраняваго за определено време в този модел
     */
    static function process($mime, $accId, $uid, $forcedMid = FALSE)
    {
        if ($forcedMid === FALSE) {
            // Извличаме информация за вътрешния системен адрес, към когото е насочено писмото
            $soup = $mime->getHeader('X-Original-To', '*') .
                    $mime->getHeader('Delivered-To', '*') .
                    $mime->getHeader('To', '*');
    
            if (!preg_match('/^.+\+received=([a-z]+)@/i', $soup, $matches)) {
                
                return;
            }
            
            $mid = $matches[1];
        } else {
            $mid = $forcedMid;
        }
        
        // Намираме датата на писмото
        $date = $mime->getSendingTime();

        // Намираме ip-то на изпращача
        $ip = $mime->getSenderIp();
            
        $isReceipt = doclog_Documents::received($mid, $date, $ip);

        if($isReceipt) {
            $rec = new stdClass();
            // Само първите 100К от писмото
            $rec->data = substr($mime->getData(), 0, 100000);
            $rec->accountId = $accId;
            $rec->uid = $uid;
            $rec->createdOn = dt::verbal2mysql();

            self::save($rec);
        }

        return $isReceipt;
    }
    
    
    /**
     * Проверява подадения текст, дали може да е обратна разписка
     * 
     * @param string $text
     * 
     * @return boolean
     */
    public static function isForReceipts($text)
    {
        // При наличие на някоя от негативните думи, прекратяваме
        foreach (self::$negativeWordsArr as $negativeWord) {
            if (stripos($text, $negativeWord)) {
                
                return FALSE;
            }
        }
        
        // Ако открием съвпадение с някоя дума, от позитивните думи
        foreach (self::$positiveWordsArr as $positveWord) {
            if (stripos($text, $positveWord)) {
                
                return TRUE;
            }
        }
        
        return FALSE;
    }
}
