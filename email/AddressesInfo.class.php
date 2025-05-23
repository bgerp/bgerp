<?php 

/**
 * Списък с имейли, до които няма да се праща информационни (бласт) съобщения
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class email_AddressesInfo extends core_Manager
{


    /**
     * Заглавие
     */
    public $title = 'Информация за имейл адресите';


    /**
     * Кой има право да чете?
     */
    protected $canRead = 'ceo, blast, email, admin';


    /**
     * Кой има право да променя?
     */
    protected $canEdit = 'ceo, blast, email, admin';


    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'ceo, blast, email, admin';


    /**
     * Кой може да го види?
     */
    protected $canView = 'ceo, blast, email, admin';


    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'ceo, blast, email, admin';


    /**
     * Кой може да го изтрие?
     */
    protected $canDelete = 'ceo, blast, email, admin';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'email_Wrapper, plg_RowTools2, plg_Sorting, bgerp_plg_Import, plg_Search';


    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'blast_BlockedEmails';


    /**
     * @var string
     * @see plg_Search
     */
    public $searchFields = 'email, redirection';


    /**
     * Стойност по подразбиране при импортиране на csv
     */
    public $expOnExist = 'update';


    /**
     * @var array
     */
    protected static $mapArr = array();


    /**
     * Описание на модела
     */
    protected function description()
    {
        $this->FLD('email', 'email(showOriginal,ci)', 'caption=Имейл, mandatory, silent');
        $this->FLD('redirection', 'email', 'caption=Пренасочване при изпращане->Имейл');
        $this->FLD('state', 'enum(,ok=OK, blocked=Блокирано, error=Грешка)', 'caption=Състояние');
        $this->FLD('lastChecked', 'datetime(format=smartTime)', 'caption=Последно->Проверка, input=none');
        $this->FLD('lastSent', 'datetime(format=smartTime)', 'caption=Последно->Движение, input=none');
        $this->FLD('checkPoint', 'int', 'caption=Проверка->Точки, input=none');

        $this->setDbUnique('email');
        $this->setDbIndex('redirection');
        $this->setDbIndex('lastSent');
        $this->setDbIndex('lastChecked');
    }


    /**
     * Връща записа за имейл адреса
     *
     * @param string $email
     *
     * @return object|null
     */
    public static function getRecFor($email)
    {
        $email = trim($email);
        $email = mb_strtolower($email);

        $rEmail = self::fetch(array("#email = '[#1#]'", $email));

        return $rEmail;
    }


    /**
     * Обновява данните за имейл адреса
     *
     * @param string $email
     * @param array $fArr
     *
     * @return object
     */
    public static function updateRecFor($email, $fArr = array())
    {
        $rEmail = self::getRecFor($email);

        if (!$rEmail) {
            $rEmail = new stdClass();
            $rEmail->email = $email;
        }

        $saveFieldsArr = array();
        foreach ($fArr as $fName => $fValue) {
            $rEmail->{$fName} = $fValue;
            $saveFieldsArr[$fName] = $fName;
        }

        $saveFieldsArr = $rEmail->id ? $saveFieldsArr : null;

        self::save($rEmail, $saveFieldsArr);

        return $rEmail;
    }


    /**
     * Връща имейла, към който да се изпраща
     * С приоритет е пренасоченият имейл
     *
     * @param string $email
     * @return string
     */
    public static function getEmail($email)
    {
        $oEmail = $email;
        $email = trim($email);
        $email = mb_strtolower($email);

        if (isset(self::$mapArr[$email])) {

            return self::$mapArr[$email];
        }

        $rEmail = self::fetchField(array("#email = '[#1#]'", $email), 'redirection');
        if (trim($rEmail)) {
            $oEmail = $rEmail;
        }

        self::$mapArr[$email] = $oEmail;

        return $oEmail;
    }


    /**
     * Редиректва към създване/редактиране на запис
     *
     * @return Redirect
     * @throws core_exception_Expect
     */
    function act_AddEmail()
    {
        $eId = Request::get('docId', 'int');

        expect($eId);

        $eRec = email_Incomings::fetch($eId);

        expect($eRec);

        email_Incomings::requireRightFor('single', $eRec);

        $this->requireRightFor('add');

        // Вземаме имейлите от копи и до
        email_Incomings::calcAllToAndCc($eRec);
        $allEmailsArr = array_merge($eRec->AllTo, $eRec->AllCc);
        $emailArr = array();
        foreach ($allEmailsArr as $allTo) {
            $email = $allTo['address'];
            $email = trim($email);
            $emailArr[$email] = $email;
        }

        // Вземаме имейлите от текстовата част
        $emailsFromText = email_Mime::getAllEmailsFromStr($eRec->textPart, true);
        $emailsFromTextArr = type_Emails::toArray($emailsFromText);
        $emailsFromTextArr = arr::make($emailsFromTextArr, true);
        $emailArr = array_merge($emailArr, $emailsFromTextArr);

        unset($emailArr[$eRec->fromEml]);

        if (!empty($emailArr)) {
            $emailArr = email_Inboxes::removeOurEmails($emailArr);
        }

        $emailsStr = '';
        if (!empty($emailArr)) {
            $emailsStr = type_Emails::fromArray($emailArr);
        }

        $email = trim($eRec->fromEml);

        expect(strlen($email), $email);

        $email = mb_strtolower($email);
        $oRecId = self::fetchField(array("#email = '[#1#]'", $email));

        if (isset($oRecId)) {

            $this->requireRightFor('edit', $oRecId);

            return new Redirect(array($this, 'edit', $oRecId, 'eSugg' => $emailsStr, 'ret_url' => getRetUrl()));
        }

        return new Redirect(array($this, 'add', 'email' => $email, 'eSugg' => $emailsStr, 'ret_url' => getRetUrl()));
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $eSugg = Request::get('eSugg');
        if (isset($eSugg)) {
            $eSuggArr = type_Emails::toArray($eSugg);
            if (!empty($eSuggArr)) {
                array_unshift($eSuggArr , '');
                $eSuggArr = arr::make($eSuggArr, true);
                $data->form->setSuggestions('redirection', $eSuggArr);
            }
        }

        $form = $data->form;
        if ($form->isSubmitted()) {
            if (!isset($form->rec->id) && isset($form->rec->email)) {
                $oRec = $mvc->fetch(array("#email = '[#1#]'", $form->rec->email));
                if (isset($oRec->id)) {
                    Request::push(array('id' => $oRec->id));

                    $form->input('state');
                    if (!isset($form->rec->state)) {
                        Request::push(array('state' => $oRec->state));
                    }
                }
            }
        }
    }


    /**
     *
     *
     * @param email_AddressesInfo $mvc
     * @param array               $fields
     *
     * @see bgerp_plg_Import
     */
    public function on_AfterPrepareImportFields($mvc, &$fields)
    {
        $fields['state'] = array('caption' => 'Статус за изпращане на циркулярни имейли->Изпращане', 'mandatory' => 'mandatory');
        $fields['state']['notColumn'] = true;
        $fields['state']['type'] = 'enum(blocked=Забранено, error=Дава грешка, ok=Разрешено)';
    }
    
    
    /**
     *
     *
     * @param email_AddressesInfo $mvc
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
        
        if (blast_BlockedDomains::isBlocked($email)) {
            
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
            $saveFields['checkPoint'] = 'checkPoint';
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
            
            return ;
        }
        
        if (blast_BlockedDomains::isBlocked($email)) {
            
            return 'blocked';
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
            $validatedDomainsArr[$domain] = drdata_Emails::mxAndARecordsValidate($domain);
            if ($validatedDomainsArr[$domain] === false) {
                if (checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A')) {
                    $validatedDomainsArr[$domain] = true;
                }
            }
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
     * @param email_AddressesInfo $mvc
     * @param NULL|int            $rec
     * @param stdClass            $rec
     */
    public static function on_BeforeSave($mvc, $res, $rec, &$fields = null)
    {
        if (!$rec->state) {
            $rec->state = 'ok';
        }
        
        if (!Mode::is('importing')) {
            if (!isset($rec->lastSent)) {
                $rec->lastSent = dt::now();
            }
            
            if ($rec->state == 'error') {
                $rec->checkPoint--;

                if ($rec->checkPoint < 0 || !(isset($rec->checkPoint))) {
                    $rec->checkPoint = 0;
                }
            } elseif ($rec->state == 'ok') {
                if (!$rec->checkPoint) {
                    $rec->checkPoint = 5;
                } else {
                    $rec->checkPoint++;
                }

                if ($rec->checkPoint > 5) {
                    $rec->checkPoint = 5;
                }
            }

            if (is_array($fields) && !$fields['checkPoint'] && $fields['state']) {
                if (isset($rec->checkPoint)) {
                    $fields['checkPoint'] = 'checkPoint';
                }
            }
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('lastSent', 'DESC');
        $data->query->orderBy('id', 'DESC');

        $data->listFilter->FNC('emailStr', 'varchar', 'caption=Имейл');
        
        // Да се показва полето за търсене
        $data->listFilter->showFields = 'state, search';
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->setFieldTypeParams('state', 'allowEmpty');
        
        $data->listFilter->setDefault('state', '');
        
        //Добавяме бутон "Филтрирай"
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->input();
        
        if ($data->listFilter->rec->state) {
            $data->query->where(array("#state = '[#1#]'", $data->listFilter->rec->state));
        }
    }
    
    
    /**
     * Функция, която се изпълнява от крона и проверява за валидност на имейлите
     */
    public function cron_CheckEmails()
    {
        $query = self::getQuery();
        
        // Ако е изпратен преди посоченото време да не се проверява
        $stopCheckingPeriod = dt::subtractSecs(email_Setup::get('STOP_CHECKING_EMAILS_PERIOD'));
        $query->where("#lastSent >= '{$stopCheckingPeriod}'");
        
        $query->where("#state != 'blocked'");
        $query->orWhere('#state IS NULL');
        
        // Ако е проверяван скоро, да не се проверява повторно
        $recheckAfter = dt::subtractSecs((int) email_Setup::get('RECHECK_EMAILS_AFTER'));
        $query->where("#lastChecked <= '{$recheckAfter}'");
        $query->orWhere('#lastChecked IS NULL');
        $query->limit((int) email_Setup::get('RECHECK_EMAILS_LIMIT'));
        
        $query->orderBy('lastChecked', 'ASC');

        while ($rec = $query->fetch()) {
            $rec->lastChecked = dt::now();
            if (!self::validateEmail($rec->email)) {
                $rec->state = 'error';
                self::save($rec, 'lastChecked, state, checkPoint');
            }
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
