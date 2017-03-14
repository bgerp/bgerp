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
    protected static $negativeWordsArr = array('fail', 'sorry', 'rejected', 'not be delivered', "couldn t be delivered");
    
    
    /**
     * Масив с думи, които трябва да съществуват в стринга
     */
    protected static $positiveWordsArr = array('delivered to');
    
    
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
     * Проверява дали трябва да е обрабтна разписак
     * 
     * @param email_Mime $mime
     */
    public static function isForReceipts($mime)
    {
        $isGoodTextPart = self::checkTextPart($mime->textPart);
        
        // Ако съдържа някои от позитивните или отрицателните думи
        if (!is_null($isGoodTextPart)) return $isGoodTextPart;
        
        // Проверяваме и хедърите за специфични маркери
        $isGoodHeader = self::checkHeader($mime);
        
        return $isGoodHeader;
    }
    
    
    /**
     * Проверява подадения текст, дали може да е обратна разписка
     * 
     * @param string $text
     * 
     * @return boolean|NULL
     */
    protected static function checkTextPart($text)
    {
        $text = plg_Search::normalizeText($text);
        
        // При наличие на някоя от негативните думи, прекратяваме
        foreach (self::$negativeWordsArr as $negativeWord) {
            if (stripos($text, $negativeWord) !== FALSE) {
                
                return FALSE;
            }
        }
        
        // Ако открием съвпадение с някоя дума, от позитивните думи
        foreach (self::$positiveWordsArr as $positveWord) {
            if (stripos($text, $positveWord) !== FALSE) {
                
                return TRUE;
            }
        }
    }
    
    
    /**
     * Проверява хедърите, дали може да е обратна разписка
     * 
     * @param email_Mime $mime
     * 
     * @return boolean
     */
    protected static function checkHeader($mime)
    {
        $autoSubmitted = $mime->getHeader('Auto-Submitted', '*');
        $autoSubmitted = strtolower($autoSubmitted);
        $autoSubmitted = trim($autoSubmitted);
        
        if (!$autoSubmitted) return FALSE;
        
        if (stripos($autoSubmitted, 'auto-replied') !== FALSE) return TRUE;
        
        return FALSE;
    }
}
