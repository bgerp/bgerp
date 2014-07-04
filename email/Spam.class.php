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
class email_Spam extends core_Manager
{
    /**
     * Плъгини за работа
     */
    var $loadList = 'email_Wrapper';
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Твърд спам";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, ceo, email';
    
    
    /**
     * Кой има право да променя?
     */
    var $canWrite = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin, email';
	

    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('data', 'blob(compress)', 'caption=Данни');
        $this->FLD('accountId', 'key(mvc=email_Accounts,select=email)', 'caption=Сметка');
        $this->FLD('uid', 'int', 'caption=Imap UID');
        $this->FLD('createdOn', 'datetime', 'caption=Създаване');
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

            return $rec->id;
        }
    }

    
    /**
     * Дали писмото е SPAM?
     */
    static function detectSpam($mime, $accId, $uid)
    {   
        // Ако няма адрес на изпращача, писмото го обявяваме за спам
        if(!($fromEmail = $mime->getFromEmail())) {
            $isSpam = TRUE;
        }
        
        // Ако изпращането е станало, през някои от регистрираните имейл акаунти
        // и в настройките на тази сметка е указано, че изходящи писма чрез нея ще се пращат
        // само през bgERP, то проверява се дали изходящото писмо има валиден mid
        
        // TODO


        // Според нивото на спам-статуса от SpamAssassin
        $spamStatus = $mime->getHeader('X-Spam-Status');
        $conf = core_Packs::getConfig('email');
        if(preg_match('/^.+ score=([0-9\.]+) /i', $spamStatus, $matches)) {
            $score = $matches[1];
            if($score >= $conf->SPAM_SA_SCORE_LIMIT) {
                $isSpam = TRUE;
            }
        }

        return $isSpam;
    }
     
}