<?php 

/**
 * Списък с домейните, до които няма да се праща информационни (бласт) съобщения
 *
 *
 * @category  bgerp
 * @package   blast
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class blast_BlockedDomains extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Адреси, на които не се изпращат циркулярни имейли';
    
    
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
    public $loadList = 'blast_Wrapper, plg_RowTools2, plg_Sorting';
    
    
    /**
     * Описание на модела
     */
    protected function description()
    {
        $this->FLD('domain', 'varchar(ci)', 'caption=Домейн, mandatory');
        
        $this->setDbUnique('domain');
    }
    
    
    /**
     * Проверява дали домейна е блокиран
     *
     * @param string $email
     *
     * @return bool
     */
    public static function isBlocked($email)
    {
        if (strpos($email, '@') !== false) {
            list(, $domain) = explode('@', $email);
        } else {
            $domain = $email;
        }
        
        if (self::fetch(array("#domain = '[#1#]'", $domain))) {
            
            return true;
        }
        
        return false;
    }
}
