<?php 


/**
 * Клас 'email_Returned' - регистър на върнатите имейли
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Milen Georgiev <milen2experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Returned extends email_ServiceEmails
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Неполучени, върнати писма";


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
    static function process($mime, $accId, $uid)
    {
        // Извличаме информация за вътрешния системен адрес, към когото е насочено писмото
        $soup = $mime->getHeader('X-Original-To', '*') . ' ' .
                $mime->getHeader('Delivered-To', '*') . ' ' .
                $mime->getHeader('To', '*');
        
        if (!preg_match('/^.+\+returned=([a-z]+)@/i', $soup, $matches)) {
            
            if ($accId && preg_match('/^.+returned=([a-z]+)@/i', $soup) && ($accRec = email_Accounts::fetch($accId))) {
                if ($accRec->email) {
                    list($accEmail) = explode('@', $accRec->email);
                }
                
                if ($accEmail) {
                    $accEmail = preg_quote($accEmail, '/');
                    
                    preg_match("/^.+{$accEmail}returned=([a-z]+)@/i", $soup, $matches);
                }
            }
            
            if (empty($matches)) return;
        }
        
        $mid = $matches[1];
        
        // Правим проверка да не е обратна разписка за получено писмо
        // Някои сървъри отговарят на `Return-Path`
        if (email_Receipts::isForReceipts($mime)) {
            
            return email_Receipts::process($mime, $accId, $uid, $mid);
        }
        
        // Намираме датата на писмото
        $date = $mime->getSendingTime();
        
        $ip = $mime->getSenderIp();
        
        $isReturnedMail = doclog_Documents::returned($mid, $date, $ip);
        
        if($isReturnedMail) {
            $rec = new stdClass();
            // Само първите 100К от писмото
            $rec->data = substr($mime->getData(), 0, 100000);
            $rec->accountId = $accId;
            $rec->uid = $uid;
            $rec->createdOn = dt::verbal2mysql();

            self::save($rec);
            
            self::logNotice('Върнат имейл', $rec->id);
        }

        return $isReturnedMail;
    }
}
