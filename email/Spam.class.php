<?php 


/**
 * Клас 'email_Spam' - регистър на квалифицираните като твърд спам писма
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Milen Georgiev <milen2experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Spam extends email_ServiceEmails
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Твърд спам";
    


    /**
     * Описание на модела
     */
    function description()
    {
        $this->addFields();  
    }

     

    /**
     * Проверява дали в $mime се съдържа спам писмо и ако е
     * така - съхранява го за определено време в този модел
     */
    static function process_($mime, $accId, $uid)
    {
        if(self::detectSpam($mime, $accId, $uid)) {      
            $rec = new stdClass();
            // Само първите 100К от писмото
            $rec->data = substr($mime->getData(), 0, 100000);
            $rec->accountId = $accId;
            $rec->uid = $uid;
            $rec->createdOn = dt::verbal2mysql();

            self::save($rec);
            
            self::logNotice('Маркиран имейл като спам', $rec->id);
            
            return $rec->id;
        }
    }

    
    /**
     * Дали писмото е SPAM?
     */
    static function detectSpam($mime, $accId, $uid)
    {   
        $isSpam = FALSE;
        
        // Ако е отговор на наш имейл да не се приема като спам
        $subject = $mime->getHeader('subject');
        if ($subject && email_ThreadHandles::extractThreadFromSubject($subject)) {
            
            return $isSpam;
        }
        
        $inReplyTo = $mime->getHeader('In-Reply-To');
        if ($inReplyTo) {
            if ($mid = email_Router::extractMidFromMessageId($inReplyTo)) {
                if (doclog_Documents::fetchByMid($mid)) {
                    
                    return $isSpam;
                }
            }
        }
        
        // Ако няма адрес на изпращача, писмото го обявяваме за спам
        if(!($fromEmail = $mime->getFromEmail())) {
            $isSpam = TRUE;
            
            return $isSpam;
        }
        
        // Ако изпращането е станало, през някои от регистрираните имейл акаунти
        // и в настройките на тази сметка е указано, че изходящи писма чрез нея ще се пращат
        // само през bgERP, то проверява се дали изходящото писмо има валиден mid
        
        // TODO

        // Гледаме спам рейтинга
        $score = self::getSpamScore($mime->parts[1]->headersArr);
        if (isset($score) && ($score >= email_Setup::get('HARD_SPAM_SCORE'))) {
            $isSpam = TRUE;
        }
        
        return $isSpam;
    }
    
    
    /**
     * Връща спам рейтинга от хедърите
     *
     * @param array $headerArr
     * @param boolean $notNull
     */
    public static function getSpamScore($headerArr, $notNull = TRUE)
    {
        $headersNames = email_Setup::get('CHECK_SPAM_SCORE_HEADERS');
        
        $headersNamesArr = type_Set::toArray($headersNames);
        
        static $scoreArr = array();
        
        $hash = md5(serialize($headerArr));
        
        if (!$scoreArr[$hash]) {
            
            $score = NULL;
            
            // Проверяваме рейтинга във всички зададени хедъри
            if ($headersNamesArr) {
                
                foreach ($headersNamesArr as $header) {
                    
                    $header = trim($header);
                    
                    if (!$header) continue;
                    
                    $score = email_Mime::getHeadersFromArr($headerArr, $header);
                    
                    if (!is_numeric($score)) {
                        if(preg_match('/score\s*=\s*([0-9\.]+)(\s|$|[^0-9])/i', $score, $matches)) {
                            $score = $matches[1];
                        }
                    }
                    
                    if (isset($score) && is_numeric($score)) break;
                }
            }
            
            if (!is_numeric($score)) {
                $score = NULL;
            }
            
            $scoreArr[$hash]['score'] = $score;
        } else {
            $score = $scoreArr[$hash]['score'];
        }
        
        if (!isset($score) && $notNull) {
            $score = 0;
        }
        
        return $score;
    }
}
