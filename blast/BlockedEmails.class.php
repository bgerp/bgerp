<?php 


/**
 * Списък с имейли, до които няма да се праща информационни (бласт) съобщения
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blast_BlockedEmails extends core_Manager
{
    
    /**
     * Заглавие
     */
    public $title = "Адреси, на които не се изпращат циркулярни имейли";
    
    /**
     * Кой има право да чете?
     */
    protected $canRead = 'ceo, blast, admin';
    
    /**
     * Кой има право да променя?
     */
    protected $canEdit = 'ceo, blast, admin';
    
    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'ceo, blast, admin';
    
    /**
     * Кой може да го види?
     */
    protected $canView = 'ceo, blast, admin';
    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'ceo, blast, admin';
    
    /**
     * Кой може да го изтрие?
     */
    protected $canDelete = 'ceo, blast, admin';
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'blast_Wrapper, plg_RowTools';
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'blast_Blocked';
    
    
    /**
     * Описание на модела
     */
    protected function description()
    {
        $this->FLD('email', 'email', 'caption=Имейл, mandatory');
        
        $this->setDbUnique('email');
    }
    
    
    /**
     * Проверява дали имейла е блокиран
     *
     * @param string $email
     *
     * @return boolean
     */
    public static function isBlocked($email)
    {
        if (self::fetch(array("#email = '[#1#]'", $email))) return TRUE;
        
        return FALSE;
    }
    
    
    /**
     * Добавя имейлв в блокирани
     *
     * @param string $email
     *
     * @return object
     */
    public static function add($email)
    {
        $rec = new stdClass();
        
        $rec->email = $email;
        
        $rec = blast_BlockedEmails::save($rec, NULL, 'IGNORE');
        
        return $rec;
    }
    
    
    /**
     * Премахва имейла от листата на блокираните
     *
     * @param string $email
     *
     * @return integer
     */
    public static function remove($email)
    {
        $cnt = blast_BlockedEmails::delete(array("#email='[#1#]'", $email));
        
        return $cnt;
    }
}
