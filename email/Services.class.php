<?php
/**
 * Мениджър на онлайн услуги, които изпращат имейли
 *
 * @category  bgerp
 * @package   email
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       https://github.com/bgerp/bgerp/issues/253
 */
class email_Services extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,email_Wrapper, plg_RowTools';
    
    
    /**
     * Заглавие
     */
    var $title    = "Имейл услуги";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead   = 'admin';
    
    
    /**
     * Кой има право да пише?
     */
    var $canWrite  = 'admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'admin';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title' , 'varchar', 'caption=Услугата');
        $this->FLD('email' , 'email', 'caption=Имейл на услугата');
        $this->FLD('convertPatterns' , 'text', 'caption=Шаблони за конвертиране');
    }
    
    
    /**
     * Проверява дали входящото писмо е от известна публична услуга
     * 
     * Когато имейла на изпращача ($rec->fromEml) съвпадне с полето #email на модела 
     * email_Services, се прави опит за определяне на имейла на реалния изпращач. Това става 
     * като от събджекта ($rec->subject) и текста ($rec->textPart) писмото първо се извлекат
     * всички имейл адреси, а след това от този списък се премахнат:
     * 
     *  o нашите имейл адреси (имейл адресите в домейна BGERP_DEFAULT_EMAIL_DOMAIN)
     *  o имейл адресите, които е много вероятно да са на доставчика на публичната услуга
     *  
     * @param stdClass $rec запис от модела email_Incomings
     */
    public static function preroute($rec)
    {
        // Търсим услугата, чрез която е изпратено писмото
        $serviceRec = static::detect($rec);
        
        if (!$serviceRec) {
            // не бе разпозната услуга
            return;
        }
        
        // Приговяме "супа" от избрани полета на входящото писмо
        $soup = implode(' ',
            array(
                $rec->subject, 
                $rec->textPart,
            )
        );
        
        // Извличаме всичко имейл адреси от супата ...
        $emails = email_Mime::extractEmailsFrom($soup);

        // ... махаме нашите имейли
        $emails = static::filterOurEmails($emails);
        
        // ... и махаме имейлите присъщи на услугата
        $emails = static::filterServiceEmails($emails, $serviceRec);
        
        // Ако нещо е останало ...
        if (count($emails) > 0) {
            // ... то се приема за реалния адрес на изпращача на писмото
            if (strpos($rec->fromName, $emails[0]) === FALSE) {
                $rec->fromName .= ' ' . $emails[0];
            }
            $rec->fromName .= ' през ' . $serviceRec->title;
            $rec->fromEml = $emails[0];
        }
    }
    
    
    /**
     * Определя дали и от коя услуга е изпратено писмо
     * 
     * @param stdClass $rec запис от модела email_Incomings
     * @return stdClass запис от модела email_Services или FALSE ако не е разпозната услуга
     */
    protected static function detect($rec)
    {
        $serviceRec = static::fetch(array("#email = '[#1#]'", $rec->fromEml));
        
        return $serviceRec ? $serviceRec : FALSE;
    }
    
    
    /**
     * Премахва нашите имейли от зададен списък с имейли.
     * 
     * "Нашите" имейли са имейлите в домейна BGERP_DEFAULT_EMAIL_DOMAIN.
     * 
     * @param array $emails
     * @return array
     */
    protected static function filterOurEmails($emails)
    {
        $emails = array_filter($emails, function ($email) {
            return strtolower(type_Email::domain($email)) != strtolower(BGERP_DEFAULT_DOMAIN);
        });
        
        return array_values($emails);
    }
    
    
    protected static function filterServiceEmails($emails, $serviceRec)
    {
        $self = get_called_class();
        
        return array_filter($emails, function ($email) use ($serviceRec, $self) {
            return !$self::isServiceEmail($email, $serviceRec);
        });
    }
    
    
    public static function isServiceEmail($email, $serviceRec)
    {
        return FALSE; // @TODO
    }
}
