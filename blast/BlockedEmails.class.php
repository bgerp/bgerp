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
    public $loadList = 'blast_Wrapper, plg_RowTools2, plg_Sorting';
    
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
        $this->FLD('state', 'enum(ok=OK, blocked=Блокирано, error=Грешка)', 'caption=Състояние');
        $this->FLD('lastChecked', 'datetime(format=smartTime)', 'caption=Последно->Проверка, input=none');
        $this->FLD('lastSent', 'datetime(format=smartTime)', 'caption=Последно->Изпращане, input=none');
        $this->FLD('checkPoint', 'int', 'caption=Проверка->Точки, input=none');
        
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
        if (self::fetch(array("#email = '[#1#]' AND (#state = 'blocked' OR (#state = 'error' AND #checkPoint = 0))", $email))) return TRUE;
        
        return FALSE;
    }
    
    
    /**
     * Добавя имейлa в блокирани
     *
     * @param string $email
     *
     * @return object
     */
    public static function blockEmail($email)
    {
        if (!$rec = self::fetch(array("#email = '[#1#]'", $email))) {
            $rec = new stdClass();
            $rec->email = $email;
        }
        
        $rec->state = 'blocked';
        
        $rec = self::save($rec);
        
        return $rec;
    }
    
    
    /**
     * Премахва имейла от листата на блокираните
     *
     * @param string $email
     *
     * @return integer
     */
    public static function unBlockEmail($email)
    {
        $rec = self::fetch(array("#email = '[#1#]'", $email));
        
        if (!$rec) {
            $rec = new stdClass();
            $rec->email = 'email';
        }
        
        $rec->state = NULL;
        
        return self::save($rec);
    }
    
    
    /**
     * Добавя подадения имейл в списъка
     * 
     * @param string $email
     * @param boolean $update - ok, blocked, error
     * @param string $state
     * 
     * @return integer|NULL
     */
    public static function addEmail($email, $update = TRUE, $state = 'ok')
    {
        $rec = self::fetch(array("#email = '[#1#]'", $email));
        
        if (!$update && $rec) return ;
        
        if (!$rec) {
            $rec = new stdClass();
            $rec->state = $state;
            $saveFields = NULL;
        } else {
            $saveFields = array();
            $saveFields['email'] = 'email';
            $saveFields['lastSent'] = 'lastSent';
        }
        
        $rec->email = $email;
        $rec->lastSent = dt::now();
        
        if ($rec->state != 'blocked') {
            $rec->state = $state;
            if (is_array($saveFields)) {
                $saveFields['state'] = 'state';
            }
        }
        
        return self::save($rec, $saveFields);
    }
    
    
    /**
     * Връща състоянието на имейла
     * 
     * @param string $email
     * 
     * @return NULL|string
     */
    public static function getState($email)
    {
        $rec = self::fetch(array("#email = '[#1#]'", $email));
        
        if (!$rec) return;
        
        return $rec->state;
    }
    
    
    /**
     * Проверява дали имейла е валиден
     * 
     * @param string $email
     * 
     * @return boolean
     */
    public static function validateEmail_($email)
    {
        $email = trim($email);
        
        static $validatedDomainsArr = array();
        
        if (!trim($email)) return ;
        
        if (!type_Email::isValidEmail($email)) return ;
        
        list(, $domain) = explode('@', $email);
        
        if (!trim($domain)) return ;
        
        $domain = mb_strtolower($domain);
        
        if (!isset($validatedDomainsArr[$domain])) {
            
            $DrData = cls::get('drdata_Emails');
            
            $validatedDomainsArr[$domain] = drdata_Emails::mxAndARecordsValidate($domain);
        }
        
        if ($validatedDomainsArr[$domain] === FALSE) return FALSE;
        
        if (!$validatedDomainsArr[$domain]) return ;
        
        return TRUE;
    }
    
    
    /**
     * Преди запис в модела
     *
     * @param blast_BlockedEmails $mvc
     * @param NULL|integer $rec
     * @param stdClass $rec
     */
    public static function on_BeforeSave($mvc, $res, $rec)
    {
        if (!isset($rec->lastSent)) {
            $rec->lastSent = dt::now();
        }
        
        if ($rec->state == 'error') {
            $rec->checkPoint--;
            
            if ($rec->checkPoint < 0 || !(isset($rec->checkPoint))) {
                $rec->checkPoint = 0;
            }
            
        } elseif ($rec->state == 'ok') {
            $rec->checkPoint++;
            
            if ($rec->checkPoint > 5) {
                $rec->checkPoint = 5;
            }
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('lastSent', 'DESC');
    }
    
    
    /**
     * Функция, която се изпълнява от крона и проверява за валидност на имейлите
     */
    function cron_CheckEmails()
    {
        $conf = core_Packs::getConfig('blast');
        $query = self::getQuery();
        
        // Ако е изпратен преди посоченото време да не се проверява
        $stopCheckingPeriod = dt::subtractSecs($conf->BLAST_STOP_CHECKING_EMAILS_PERIOD);
        $query->where("#lastSent >= '{$stopCheckingPeriod}'");
        
        $query->where("#state != 'blocked'");
        $query->orWhere("#state IS NULL");
        
        // Ако е проверяван скоро, да не се проверява повторно
        $recheckAfter = dt::subtractSecs((int)$conf->BLAST_RECHECK_EMAILS_AFTER);
        $query->where("#lastChecked <= '{$recheckAfter}'");
        $query->orWhere("#lastChecked IS NULL");
        $query->limit((int)$conf->BLAST_RECHECK_EMAILS_LIMIT);
        
        $query->orderBy('lastChecked', 'ASC');
        
        while ($rec = $query->fetch()) {
            $rec->lastChecked = dt::now();
            if (self::validateEmail($rec->email)) {
                $rec->state = 'ok';
            } else {
                $rec->state = 'error';
            }
            self::save($rec, 'lastChecked, state, checkPoint');
        }
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'checkEmails';
        $rec->description = 'Проверка за валидност на имейлите';
        $rec->controller = $mvc->className;
        $rec->action = 'CheckEmails';
        $rec->period = 3;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 100;
        $res .= core_Cron::addOnce($rec);
    }
}
