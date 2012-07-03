<?php
/**
 * Ръчно задаване на правила за рутиране на имейли
 * 
 * Всеки имейл филтър дефинира шаблон за входящо писмо и действие, което да бъде изпълнено ако
 * когато входящо писмо отговаря на шаблона. Това филтриране на входящата поща се изпълнява
 * точно след опита за рутиране на писмото по номер на нишка (зададен в събджекта на писмото)
 * 
 * Шаблоните се задават като MySQL LIKE изрази, за полетата:
 *  
 *   o изпращач, 
 *   o събджект и 
 *   o текст на писмото
 *   
 * (`%` - произволна последователност от нула или повече символи, `_` - произволен символ.
 * Шаблоните не зависят от големи и малки букви (case insensitive).
 * 
 * Действието, при разпознаване на шаблон може да е едно от:
 * 
 *  o по имейл - това действие подменя реалния изпращач на писмото с имейл адрес намерен някъде 
 *               вътре в събджекта или текста (@see email_Filters::prerouteByEmail())
 *  о в папка  - писмата, отговарящи на шаблона попадат директно в папката зададена в правилото
 *  o спам     - писмата, отговарящи на шаблона се маркират като спам 
 * 
 *
 * @category  bgerp
 * @package   email
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       https://github.com/bgerp/bgerp/issues/253
 */
class email_Filters extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State2, email_Wrapper, plg_RowTools';
    
    
    /**
     * Заглавие
     */
    var $title    = "Имейл филтри";
    
    
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
    
    
    var $listFields = 'state=С, id, email, subject, body, action, folderId, note=Забележка';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('email' , 'varchar', 'caption=Шаблон->Изпращач', array('attr'=>array('style'=>'width: 350px;')));
        $this->FLD('subject' , 'varchar', 'caption=Шаблон->Относно', array('attr'=>array('style'=>'width: 350px;')));
        $this->FLD('body' , 'varchar', 'caption=Шаблон->Текст', array('attr'=>array('style'=>'width: 350px;')));
        $this->FLD('action' , 'enum(email=По имейл,folder=В папка,spam=Спам)', 'caption=Действие->Действие');
        $this->FLD('folderId' , 'key(mvc=doc_Folders, select=title, allowEmpty)', 'caption=Действие->Папка');
        $this->FLD('note' , 'text', 'caption=Още->Забележка', array('attr'=>array('style'=>'width: 350px;', 'rows'=>4)));
    }
    
    
    /**
     * Проверява дали входящото писмо се прихваща от един от записаните в този модел филтри.
     * 
     * В случай, че някой от филтрите сработи, се прави описаното в него действие. Действието 
     * може да е едно от:
     * 
     *     o По имейл : имейла на изпращача се подменя с други мейл, намерен в писмото
     *                  (@see email_Filterss::prerouteByEmail()) 
     *     o В папка  : писмото се рутира директно в зададената папка.
     *     o Спам     : писмото се маркира като спам
     *     
     *     
     * Забележка: Този метод е част от процедурата за рутиране на входяща поща. Той се изпълнява
     *            веднага след (неуспешен) опит за рутиране директно в нишка 
     *            (@see email_Incomings::routeByThread()).
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
        
        switch ($serviceRec->action) {
            case 'email':
                static::prerouteByEmail($rec, $serviceRec);
                break;
            case 'folder':
                $rec->folderId = $serviceRec->folderId;
                break;
            case 'spam':
                $rec->isSpam = TRUE;
                break;
        }
    }
    
    
    /**
     * Опит за определяне на имейла на реалния изпращач
     * 
     * Това става като от събджекта ($rec->subject) и текста ($rec->textPart) писмото първо се 
     * извлекат всички имейл адреси, а след това от този списък се премахнат:
     * 
     *  o нашите имейл адреси (имейл адресите в домейна BGERP_DEFAULT_EMAIL_DOMAIN)
     *  o имейл адресите, които е много вероятно да са на доставчика на публичната услуга
     *  
     * @param stdClass $rec запис от модела email_Incomings
     * @param stdClass $serviceRec запис от модела email_Filters
     */
    protected static function prerouteByEmail($rec, $serviceRec)
    {
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
     * Определя дали писмото отговаря на някой от зададените шаблони
     * 
     * @param stdClass $rec запис от модела email_Incomings
     * @return stdClass запис от модела email_Filters или FALSE ако не е разпозната услуга
     */
    protected static function detect($rec)
    {
        $fieldsMap = array(
            'fromEml'  => 'email',
            'subject'  => 'subject', 
            'textPart' => 'body', 
        ); 
        
        /* @var $query core_Query */
        $query = static::getQuery();
        
        // Търсим само активни филтри
        $query->where("#state = 'active'");
        
        foreach ($fieldsMap as $emailField => $patternField) {
            $query->where(
                array(
                    "#{$patternField} IS NULL OR #{$patternField} = ''" .
                    " OR LOWER('[#1#]') LIKE CONCAT('%', LOWER(#{$patternField}), '%')",
                    $rec->{$emailField}
                )
            );
        }
        
        $query->limit(1);
        
        $serviceRec = $query->fetch();
        
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
