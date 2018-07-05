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
    public $title = 'Обратни разписки за получаване на имейл';
    
    
    /**
     * Масив с думи, които НЕ трябва да съществуват в стринга
     */
    protected static $negativeWordsArr = array('fail', 'sorry', 'rejected', 'not be delivered', 'couldn t be delivered');
    
    
    /**
     * Масив с думи, които трябва да съществуват в стринга
     */
    protected static $positiveWordsArr = array('delivered to');
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->addFields();
    }
    
    
    /**
     * Проверява дали в $mime се съдържа върнато писмо и
     * ако е така - съхраняваго за определено време в този модел
     */
    public static function process($mime, $accId, $uid, $forcedMid = false)
    {
        if ($forcedMid === false) {
            // Извличаме информация за вътрешния системен адрес, към когото е насочено писмото
            $soup = $mime->getHeader('X-Original-To', '*') . ' ' .
                    $mime->getHeader('Delivered-To', '*') . ' ' .
                    $mime->getHeader('To', '*');
            
            if (!preg_match('/^.+\+received=([a-z]+)@/i', $soup, $matches)) {
                if ($accId && preg_match('/^.+received=([a-z]+)@/i', $soup) && ($accRec = email_Accounts::fetch($accId))) {
                    if ($accRec->email) {
                        list($accEmail) = explode('@', $accRec->email);
                    }
                    
                    if ($accEmail) {
                        $accEmail = preg_quote($accEmail, '/');
                        
                        preg_match("/^.+{$accEmail}received=([a-z]+)@/i", $soup, $matches);
                    }
                }
            }
            
            if (!empty($matches)) {
                $mid = $matches[1];
            } else {
                $mid = self::getMidFromReceipt($mime, $accId);
            }
            
            if (!$mid) {
                return ;
            }
        } else {
            $mid = $forcedMid;
        }
        
        // Намираме датата на писмото
        $date = $mime->getSendingTime();
        
        // Намираме ip-то на изпращача
        $ip = $mime->getSenderIp();
        
        $isReceipt = doclog_Documents::received($mid, $date, $ip);
        
        if ($isReceipt) {
            $rec = new stdClass();
            // Само първите 100К от писмото
            $rec->data = substr($mime->getData(), 0, 100000);
            $rec->accountId = $accId;
            $rec->uid = $uid;
            $rec->createdOn = dt::verbal2mysql();

            self::save($rec);
            
            self::logNotice('Получена обратна разписка', $rec->id);
            
            blast_BlockedEmails::addSentEmailFromText($mid, $mime);
        }

        return $isReceipt;
    }
    
    
    /**
     * В зависимост от съдържанието на заглавието и текста, се опитваме да определим mid за обратна разписка
     *
     * @param email_Mime $mime
     * @param integer    $accId
     *
     * @return string|NULL
     */
    protected static function getMidFromReceipt($mime, $accId)
    {
        $subject = trim($mime->getSubject());
        $textPart = $mime->textPart;
        $maxTextLen = 500;
        
        if ($subject) {
            $subject = $mime->decodeHeader($subject);
            
            $tId = email_ThreadHandles::extractThreadFromSubject($subject);
            
            if ($tId) {
                $returnMid = false;
                if (stripos($subject, 'read report') === 0) {
                    if (stripos($textPart, 'time of reading') !== false) {
                        $returnMid = true;
                    }
                } elseif (!$mime->getFiles() && (strlen($textPart) < $maxTextLen)) {
                    if (stripos($textPart, 'this is a receipt for the mail') !== false) {
                        $returnMid = true;
                    }
                }
                
                if ($returnMid) {
                    $dQuery = doclog_Documents::getQuery();
                    $dQuery->where(array("#threadId = '[#1#]' AND #action = '[#2#]'", $tId, doclog_Documents::ACTION_SEND));
                    $dQuery->where('#mid IS NOT NULL');
                    $dQuery->limit(1);
                    $dQuery->show('mid');
                    $dQuery->orderBy('createdOn', 'DESC');
                    $dRec = $dQuery->fetch();
                    if ($dRec && $dRec->mid) {
                        return $dRec->mid;
                    }
                }
            }
        }
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
        if (!is_null($isGoodTextPart)) {
            return $isGoodTextPart;
        }
        
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
            if (stripos($text, $negativeWord) !== false) {
                return false;
            }
        }
        
        // Ако открием съвпадение с някоя дума, от позитивните думи
        foreach (self::$positiveWordsArr as $positveWord) {
            if (stripos($text, $positveWord) !== false) {
                return true;
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
        
        if (!$autoSubmitted) {
            return false;
        }
        
        if (stripos($autoSubmitted, 'auto-replied') !== false) {
            return true;
        }
        
        return false;
    }
}
