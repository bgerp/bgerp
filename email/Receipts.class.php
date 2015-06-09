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
class email_Receipts extends core_Manager
{
    /**
     * Плъгини за работа
     */
    var $loadList = 'email_Wrapper';
    

    /**
     * Заглавие на таблицата
     */
    var $title = "Обратни разписки за получаване на имейл";
    
    
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
     * Проверява дали в $mime се съдържа върнато писмо и
     * ако е така - съхраняваго за определено време в този модел
     */
    static function process($mime, $accId, $uid)
    {
        // Извличаме информация за вътрешния системен адрес, към когото е насочено писмото
        $soup = $mime->getHeader('X-Original-To', '*') .
                $mime->getHeader('Delivered-To', '*') .
                $mime->getHeader('To', '*');

        if (!preg_match('/^.+\+received=([a-z]+)@/i', $soup, $matches)) {
            return;
        }
        
        $mid = $matches[1];

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
     
}