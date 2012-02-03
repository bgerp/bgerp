<?php 
/**
 * История от събития, свързани с изпращането и получаването на писма
 * 
 * @category   bgerp
 * @package    email
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 *
 */
class email_Log extends core_Manager
{
    /**
     * Заглавие на таблицата
     */
    var $title = "Лог за имейли";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, email';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, email';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin, email';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, email';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin, email';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'email_Wrapper, plg_Printing, plg_Created';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // Дата на събитието
        $this->FLD("date", "datetime", "caption=Дата");
        
        // Тип на събитието
        $this->FLD("action", "enum(sent, printed, shared)", "caption=Действие");
        
        // Нишка на документа, за който се отнася събитието
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка');
        
        // Документ, за който се отнася събитието
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Контейнер');
        
        // Само за събитие `sent`: дата на получаване на писмото
        $this->FLD('receivedOn', 'datetime', 'caption=Получено->На');
        
        // Само за събитие `sent`: IP от което е получено писмото
        $this->FLD('receivedIp', 'ip', 'caption=Получено->IP');
        
        // Само за събитие `sent`: дата на връщане на писмото (в случай, че не е получено)
        $this->FLD('returnedOn', 'datetime', 'input=none,caption=Върнато на');
        
        // MID на документа
        $this->FLD('mid', 'varchar', 'input=none,caption=Ключ');
        
        // Само за събитие `shared`: Потребител, с който е споделен документа
        $this->FLD("userId", "key(mvc=core_Users)", 'caption=Потребител');
        
        // Допълнителни обстоятелства, в зависимост от събитието (в PHP serialize() формат)
        $this->FLD("data", "blob", 'caption=Потребител');
    }
    
    
    /**
     * Отразява в историята акта на изпращане на писмо
     *
     * @param stdClass $messageRec
     */
    public static function sent($messageRec)
    {
        expect($messageRec->containerId);
        expect($messageRec->mid);

        if (empty($messageRec->threadId)) {
            $messageRec->threadId    = doc_Containers::fetchField($messageRec->containerId, 'threadId');
        }
        
        expect($messageRec->threadId);
        
        $rec = new stdClass();
        
        $rec->date        = dt::now();
        $rec->action      = 'sent';
        $rec->containerId = $messageRec->containerId;
        $rec->threadId    = $messageRec->threadId;
        $rec->mid         = $messageRec->mid;
        $rec->data        = array(
            'boxFrom' => $messageRec->boxFrom,
            'toEml'   => $messageRec->toEml,
            'subject' => $messageRec->subject,
            'options' => $messageRec->options,
        );
        
        $rec->data = serialize($rec->data);
        
        return static::save($rec);
    } 
    
    
    /**
     * Отразява в историята факта, че (по-рано изпратено от нас) писмо е видяно от получателя си
     *
     * @param string $mid
     * @param string $date
     * @param string $ip
     */
    public static function received($mid, $date = NULL, $ip = NULL)
    {
        if ( !($rec = static::fetch("#mid = '{$mid}'")) ) {
            return FALSE;
        }
        
        if (!isset($date)) {
            $date = dt::now();
        }
        
        $rec->receivedOn = $date;
        $rec->receivedIp = $ip;
        
        return static::save($rec);
    } 
    
    
    /**
     * Отрязава в историята факта че (по-рано изпратено от нас) писмо не е доставено до получателя си
     *
     * @param string $mid
     * @param string $date дата на върнатото писмо
     */
    public static function returned($mid, $date = NULL)
    {
        if ( !($rec = static::fetch("#mid = '{$mid}'")) ) {
            return FALSE;
        }

        if (!isset($date)) {
            $date = dt::now();
        }
        
        $rec->returnedOn = $date;
        
        return static::save($rec);
    }
    
    
    /**
     * Отразява факта, че документ е споделен
     *
     * @param int $userId key(mvc=core_Users) с кого е споделен документа
     * @param int $containerId key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Threads)
     */
    public static function shared($userId, $containerId, $threadId = NULL)
    {
        expect($userId);
        expect($containerId);
        
        if (empty($threadId)) {
            $threadId = doc_Containers::fetchField($containerId, 'threadId');
        }
        
        expect($threadId);
        
        $rec = new stdClass();
        
        $rec->date        = dt::now();
        $rec->action      = 'shared';
        $rec->containerId = $containerId;
        $rec->threadId    = $threadId;
        $rec->userId      = $userId;
        
        return static::save($rec);
    }
    
    
    /**
     * Отразява факта, че документ е отпечатан
     *
     * @param int $containerId key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Threads)
     */
    public static function printed($containerId, $threadId = NULL)
    {
        expect($containerId);
        
        if (empty($threadId)) {
            $threadId = doc_Containers::fetchField($containerId, 'threadId');
        }
        
        expect($threadId);
        
        $rec = new stdClass();
        
        $rec->date        = dt::now();
        $rec->action      = 'printed';
        $rec->containerId = $containerId;
        $rec->threadId    = $threadId;
        $rec->userId      = core_Users::getCurrent();
        
        return static::save($rec);
        
    }
}
