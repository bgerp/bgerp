<?php 

/**
 * Списък с имейли, до които няма да се праща информационни (бласт) съобщения
 *
 *
 * @category  bgerp
 * @package   blast
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class blast_BlockedEmails extends core_Manager
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
    public $loadList = 'blast_Wrapper, plg_RowTools2, plg_Sorting, bgerp_plg_Import';
    
    
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
        $this->FLD('state', 'enum(,ok=OK, blocked=Блокирано, error=Грешка)', 'caption=Състояние');
        $this->FLD('lastChecked', 'datetime(format=smartTime)', 'caption=Последно->Проверка, input=none');
        $this->FLD('lastSent', 'datetime(format=smartTime)', 'caption=Последно->Изпращане, input=none');
        $this->FLD('checkPoint', 'int', 'caption=Проверка->Точки, input=none');
        
        $this->setDbUnique('email');
    }
    
    
    /**
     *
     *
     * @param blast_BlockedEmails $mvc
     * @param array               $fields
     *
     * @see bgerp_plg_Import
     */
    public function on_AfterPrepareImportFields($mvc, &$fields)
    {
        $fields['state'] = array('caption' => 'Състояние', 'mandatory' => 'mandatory');
    }
    
    
    /**
     *
     *
     * @param blast_BlockedEmails $mvc
     * @param stdClass            $rec
     *
     * @return bool
     *
     * @see bgerp_plg_Import
     */
    public function on_BeforeImportRec($mvc, &$rec)
    {
        if (!trim($rec->email)) {
            
            return false;
        }
        
        if (!$rec->state) {
            $rec->state = 'ok';
        }
        
        // Опитваме се да определим състоянието
        if (!$rec->state) {
            $rec->state = 'ok';
        }
        
        if (!$mvc->fields['state']->type->options[$rec->state]) {
            $state = mb_strtolower($rec->state);
            if ($mvc->fields['state']->type->options[$state]) {
                $rec->state = $state;
            } else {
                $state = str::mbUcfirst($state);
                
                $rec->state = array_search($state, $mvc->fields['state']->type->options);
            }
        }
        
        if (!$rec->state) {
            
            return false;
        }
    }
    
    
    /**
     * Проверява дали имейла е блокиран
     *
     * @param string $email
     *
     * @return bool
     */
    public static function isBlocked($email)
    {
        if (self::fetch(array("#email = '[#1#]' AND (#state = 'blocked' OR (#state = 'error' AND #checkPoint = 0))", $email))) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Добавя имейла в блокирани
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
     * @return int
     */
    public static function unBlockEmail($email)
    {
        $rec = self::fetch(array("#email = '[#1#]'", $email));
        
        if (!$rec) {
            $rec = new stdClass();
            $rec->email = 'email';
        }
        
        $rec->state = null;
        
        return self::save($rec);
    }
    
    
    /**
     * Добавя подадения имейл в списъка
     *
     * @param string      $email
     * @param bool|string $update
     * @param string      $state- ok, blocked, error
     *
     * @return int|NULL
     */
    public static function addEmail($email, $update = true, $state = 'ok')
    {
        $rec = self::fetch(array("#email = '[#1#]'", $email));
        
        if (!$update && $rec) {
            
            return ;
        }
        
        if (!$rec) {
            $rec = new stdClass();
            $rec->state = $state;
            $saveFields = null;
        } else {
            $saveFields = array();
            $saveFields['email'] = 'email';
            $saveFields['lastSent'] = 'lastSent';
        }
        
        $rec->email = $email;
        $rec->lastSent = dt::now();
        
        if ($rec->state != 'blocked' || ($update === 'force')) {
            $rec->state = $state;
            if (is_array($saveFields)) {
                $saveFields['state'] = 'state';
            }
        }
        
        return self::save($rec, $saveFields);
    }
    
    
    /**
     * Добавя имейла в списъка, като го извлича от текстовата част
     *
     * @param string     $mid
     * @param email_Mime $mime
     * @param string     $state
     */
    public static function addSentEmailFromText($mid, $mime, $state = 'ok')
    {
        $text = $mime->textPart;
        $fromEml = $mime->getFromEmail();
        
        if (!$mid || (!$text && !$fromEml)) {
            
            return ;
        }
        
        $tSoup = $text . ' ' . $fromEml;
        
        $eArr = type_Email::extractEmails($tSoup);
        
        if (!empty($eArr)) {
            $hArr = array();
            
            $sRec = doclog_Documents::fetchByMid($mid);
            
            if ($sRec) {
                $sentEArr = type_Emails::toArray(strtolower($sRec->data->to));
                
                $sentEArr = arr::make($sentEArr, true);
                
                if (!empty($sentEArr)) {
                    foreach ($eArr as $email) {
                        $email = strtolower($email);
                        
                        if ($hArr[$email]) {
                            continue;
                        }
                        
                        $hArr[$email] = $email;
                        
                        if ($sentEArr[$email]) {
                            self::addEmail($email, true, $state);
                            
                            break;
                        }
                    }
                }
            }
        }
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
        
        if (!$rec) {
            
            return;
        }
        
        return $rec->state;
    }
    
    
    /**
     * Проверява дали имейла е валиден
     *
     * @param string $email
     *
     * @return bool
     */
    public static function validateEmail_($email)
    {
        $email = trim($email);
        
        static $validatedDomainsArr = array();
        
        if (!trim($email)) {
            
            return ;
        }
        
        if (!type_Email::isValidEmail($email)) {
            
            return ;
        }
        
        list(, $domain) = explode('@', $email);
        
        if (!trim($domain)) {
            
            return ;
        }
        
        $domain = mb_strtolower($domain);
        
        if (!isset($validatedDomainsArr[$domain])) {
            $DrData = cls::get('drdata_Emails');
            
            $validatedDomainsArr[$domain] = drdata_Emails::mxAndARecordsValidate($domain);
        }
        
        if ($validatedDomainsArr[$domain] === false) {
            
            return false;
        }
        
        if (!$validatedDomainsArr[$domain]) {
            
            return ;
        }
        
        return true;
    }
    
    
    /**
     * Преди запис в модела
     *
     * @param blast_BlockedEmails $mvc
     * @param NULL|int            $rec
     * @param stdClass            $rec
     */
    public static function on_BeforeSave($mvc, $res, $rec)
    {
        if (!$rec->state) {
            $rec->state = 'ok';
        }
        
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
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('lastSent', 'DESC');
        
        $data->listFilter->FNC('emailStr', 'varchar', 'caption=Имейл');
        
        // Да се показва полето за търсене
        $data->listFilter->showFields = 'state, emailStr';
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->setFieldTypeParams('state', 'allowEmpty');
        
        $data->listFilter->setDefault('state', '');
        
        //Добавяме бутон "Филтрирай"
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->input();
        
        if ($data->listFilter->rec->state) {
            $data->query->where(array("#state = '[#1#]'", $data->listFilter->rec->state));
        }
        
        if ($data->listFilter->rec->emailStr) {
            $data->query->like('email', $data->listFilter->rec->emailStr);
        }
    }
    
    
    /**
     * Функция, която се изпълнява от крона и проверява за валидност на имейлите
     */
    public function cron_CheckEmails()
    {
        $conf = core_Packs::getConfig('blast');
        $query = self::getQuery();
        
        // Ако е изпратен преди посоченото време да не се проверява
        $stopCheckingPeriod = dt::subtractSecs($conf->BLAST_STOP_CHECKING_EMAILS_PERIOD);
        $query->where("#lastSent >= '{$stopCheckingPeriod}'");
        
        $query->where("#state != 'blocked'");
        $query->orWhere('#state IS NULL');
        
        // Ако е проверяван скоро, да не се проверява повторно
        $recheckAfter = dt::subtractSecs((int) $conf->BLAST_RECHECK_EMAILS_AFTER);
        $query->where("#lastChecked <= '{$recheckAfter}'");
        $query->orWhere('#lastChecked IS NULL');
        $query->limit((int) $conf->BLAST_RECHECK_EMAILS_LIMIT);
        
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
    public static function on_AfterSetupMVC($mvc, &$res)
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
