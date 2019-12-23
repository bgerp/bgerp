<?php


/**
 * Описание на IMAP/POP3 акаунти за входящи имейли
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Milen Georgiev <milen@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class email_Accounts extends core_Master
{
    /**
     * Плъгини за работа
     */
    public $loadList = 'email_Wrapper, plg_State, plg_Created, plg_Modified, plg_RowTools2, plg_CryptStore';
    
    
    /**
     * Заглавие на таблицата
     */
    public $title = 'Имейл акаунти';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin, ceo';
    
    
    public $canList = 'admin';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canWrite = 'admin';
    
    
    /**
     * полета от БД по които ще се търси
     */
    public $searchFields = 'email';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'email/tpl/SingleLayoutAccounts.shtml';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Имейл акаунт';
    
    
    /**
     * Път към картинка 16x16
     */
    public $singleIcon = 'img/16/emailSettings.png';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'email';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, email, type, applyRouting=Рутиране, retreiving=Получаване, sending=Изпращане';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('email', 'email(link=no)', 'caption=Имейл,mandatory,width=100%', array('hint' => 'Имейл адреса, съответстващ на сметката', 'attr' => array('onblur' => 'mailServerSettings();', 'id' => 'email')));
        
        // Дали да се рутират писмата, получени на този акаунт
        $this->FLD('applyRouting', 'enum(no=Без рутиране, yes=С рутиране)', 'notNull,caption=Рутиране,maxRadio=2');
        
        // Дали към този акаунт отговаря и за други вътрешни адреси, освен посочения в email?
        // Самостоятелна сметка: Отговаря само за имейл адреса, с който е дефинирана
        // Събирателна сметка: Отговаря и за други имейл адреси
        // Корпоративна сметка: Отговаря за много други имейл адреси, и кутията по подразбиране на потребителите е в същия домейн, като на тази сметка
        $this->FLD('type', 'enum(single=Самостоятелна,common=Събирателна,corporate=Корпоративна)', 'notNull,caption=Тип');
        
        // Входящо получаване
        $this->FLD('server', 'varchar(128)', 'caption=Получаване->Сървър,width=100%,mandatory', array('attr' => array('id' => 'server')));
        $this->FLD('protocol', 'enum(imap=IMAP, pop3=POP3)', 'caption=Получаване->Протокол,notNull', array('attr' => array('id' => 'protocol')));
        $this->FLD('security', 'enum(default=По подразбиране,tls=TLS,notls=No TLS,ssl=SSL)', 'caption=Получаване->Сигурност,notNull', array('attr' => array('id' => 'security')));
        $this->FLD('cert', 'enum(noValidate=Без валидиране,validate=С валидиране)', 'caption=Получаване->Сертификат,notNull', array('attr' => array('id' => 'cert')));
        $this->FLD('folder', 'varchar(64)', 'caption=Получаване->IMAP папка,value=INBOX', array('attr' => array('id' => 'folder')));
        $this->FLD('user', 'varchar', 'caption=Получаване->Потребител,width=100%', array('attr' => array('id' => 'user')));
        $this->FLD('password', 'password(64,autocomplete=off)', 'caption=Получаване->Парола,width=100%,crypt');
        
        // Изтегляне
        $this->FLD('state', 'enum(active=Активен, stopped=Спрян)', 'caption=Изтегляне->Статус');
        $this->FLD('fetchingPeriod', 'time(suggestions=30 секунди|1 минута|2 минути|5 минути|10 минути|30 минути|1 час,min=30)', 'caption=Изтегляне->Проверка през,placeholder=1 минута');
        $this->FLD(
            'deleteAfterPeriod',
            'time(uom=minutes,suggestions=Веднага|2 часа|6 часа|1 ден|1 седмица|1 месец)',
            'caption=Изтегляне->Изтриване,hint=Кога писмото да бъде изтрито от IMAP/POP3 сметката след получаване в системата?,placeholder=Никога,unit=след получаване,oldFiledName=datetime'
        );
        $this->FLD('imapFlag', 'enum(,seen=Като прочетени,unseen=Като непрочетени)', 'caption=Маркиране,placeholder=Без промяна');
        
        // Изпращане
        $this->FLD('smtpServer', 'varchar', 'caption=Изпращане->SMTP сървър,width=100%,oldFieldName=smtp', array('attr' => array('id' => 'smtpServer')));
        $this->FLD('smtpSecure', 'enum(no=Без криптиране,tls=TLS,ssl=SSL)', 'caption=Изпращане->Сигурност', array('attr' => array('id' => 'smtpSecure')));
        $this->FLD('smtpAuth', 'enum(no=Не се изисква,LOGIN=Изисква се,NTLM=MS NTLM)', 'caption=Изпращане->Аутентикация', array('attr' => array('id' => 'smtpAuth')));
        $this->FLD('smtpUser', 'varchar', 'caption=Изпращане->Потребител,width=100%', array('attr' => array('id' => 'smtpUser')));
        $this->FLD('smtpPassword', 'password(64,autocomplete=off)', 'caption=Изпращане->Парола,width=100%,crypt');
        
        $this->setDbUnique('email');
    }
    
    
    /**
     * Изплънява се след подготовката на вербалните стойности за записа
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = null)
    {
        //  protocol, server, user, smtpServer, smtpUser,
        
        if ($fields['-list']) {
            $row->retreiving = $mvc->getVerbal($rec, 'protocol');
            $row->retreiving .= ' / ' . $mvc->getVerbal($rec, 'server');
            $row->retreiving .= '<br>' . $mvc->getVerbal($rec, 'user');
            
            $row->sending = $mvc->getVerbal($rec, 'smtpServer');
            $row->sending .= '<br>' . $mvc->getVerbal($rec, 'smtpUser');
        }
        
        if ($fields['-single']) {
            if (!$rec->deleteAfterPeriod) {
                $row->deleteAfterPeriod .= tr('Никога');
            }
        }
    }
    
    
    /**
     * Връща записа на корпоративната сметка, ако има такъв
     */
    public static function getCorporateAcc()
    {
        $rec = self::fetch("#type = 'corporate' AND #state = 'active'");
        
        if ($rec) {
            list($rec->user, $rec->domain) = explode('@', $rec->email);
        }
        
        return $rec;
    }
    
    
    /**
     * Връща всички активни корпоративни и общи имейли
     *  
     * @param string $type
     *
     * @return array
     */
    public static function getCommonAndCorporate($type = null)
    {
        // Масива, който ще връщаме
        $arr = array();
        
        // Запитване за извличане на активните корпоративни и общи домейни
        $query = static::getQuery();
        
        if (!$type) {
            $query->where("#type = 'corporate'");
            $query->orWhere("#type = 'common'");
        } else {
            $query->where(array("#type = '[#1#]'", $type));
        }
        
        $query->where("#state = 'active'");
        
        while ($rec = $query->fetch()) {
            $email = mb_strtolower(trim($rec->email));
            $arr[$email] = $email;
        }
        
        return $arr;
    }
    
    
    /**
     * Връща всички активни корпоративни и общи домейни
     *
     * @param string $type
     *
     * @return array
     */
    public static function getCommonAndCorporateDomain($type = null)
    {
        // Масива, който ще връщаме
        $arr = array();
        
        // Запитване за извличане на активните корпоративни и общи домейни
        $query = static::getQuery();
        
        if (!$type) {
            $query->where("#type = 'corporate'");
            $query->orWhere("#type = 'common'");
        } else {
            $query->where(array("#type = '[#1#]'", $type));
        }
        
        $query->where("#state = 'active'");
        
        // Обхождаме записа
        while ($rec = $query->fetch()) {
            
            // Вземаме домейна
            list(, $domain) = explode('@', $rec->email);
            
            if (!$domain) {
                continue;
            }
            
            // Домейна в долен регистър
            $domain = mb_strtolower($domain);
            
            // Добавяме в масива
            $arr[$domain] = $domain;
        }
        
        return $arr;
    }
    
    
    /**
     * Връща всички активни кутии
     *
     * @param $type NULL|string|array
     *
     * @return array
     */
    public static function getActiveAccounts($type = null)
    {
        $resArr = array();
        $query = self::getQuery();
        
        if ($type) {
            $typeArr = arr::make($type, true);
            $query->orWhereArr('type', $typeArr);
        }
        
        $query->where("#state = 'active'");
        
        while ($rec = $query->fetch()) {
            $resArr[$rec->id] = $rec;
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща масив с всички активни сметки
     *
     * @param array $filterArr
     *
     * @return array
     */
    public static function getEmailsByType($filterArr = array('common', 'corporate'))
    {
        static $resArr = array();
        
        $filterArr = arr::make($filterArr);
        
        $hash = md5(implode('|', $filterArr));
        
        if (isset($resArr[$hash])) {
            
            return $resArr[$hash];
        }
        
        $resArr[$hash] = array();
        
        $query = self::getQuery();
        $query->where("#state = 'active'");
        
        if ($filterArr) {
            $query->orWhereArr('type', $filterArr);
        }
        
        while ($rec = $query->fetch()) {
            $resArr[$hash][$rec->email] = $rec->email;
        }
        
        return $resArr[$hash];
    }
    
    
    /**
     * Връща масив с активните корпоратвини акауни
     *
     * @return array
     */
    public static function getCorporateDomainsArr()
    {
        $resArr = self::getCommonAndCorporateDomain('corporate');
        
        return $resArr;
    }
    
    
    /**
     * Дали чрез дадената сметка може да изпращат писма
     */
    public static function canSendEmail($id)
    {
        $rec = self::fetch($id);
        
        return ($rec->smtpServer != '' && $rec->state == 'active');
    }
    
    
    /**
     * Връща името за папката
     */
    public function getFolderTitle($id)
    {
        $rec = $this->fetch($id);
        
        $title = $rec->email;
        
        return strtolower($title);
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        return $rec->email;
    }
    
    
    /**
     * Преди рендиране на формата за редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $data->form->setDefault('access', 'private');
    }
    
    
    /**
     * Проверка за валидност на входните данни
     */
    public function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        // Да не може да се добавя повече от една активна корпоративна сметка
        if ($form->isSubmitted()) {
            if ($form->rec->type == 'corporate' && ($form->rec->state != 'stopped')) {
                $cAcc = $mvc->getCorporateAcc();
                
                if ($cAcc && (!$form->rec->id || $form->rec->id != $cAcc->id)) {
                    $form->setError('type', 'Можете да имате само една активна корпоративна сметка');
                }
            }
        }
        
        if ($form->isSubmitted()) {
            if (email_Router::isPublicDomain(type_Email::domain($rec->email))) {
                if ($rec->type != 'single') {
                    $form->setError('type', 'Сметка в публична имейл услуга може да бъде само Самостоятелна');
                }
            }
        }
        
        // Показваме предупреждение, ако възникне грешка при свързване
        if ($form->isSubmitted()) {
            $accRec = $form->rec;
            
            // Ако се редактира записа, непопълнените полета ги вземаме от модела
            if ($form->rec->id) {
                $sRec = $mvc->fetch($form->rec->id);
                foreach ((array) $sRec as $k => $v) {
                    if (!empty($accRec->{$k})) {
                        continue;
                    }
                    if ($mvc->getFieldType($k) instanceof type_Password) {
                        $accRec->{$k} = $v;
                    }
                }
            }
            
            $imapConn = cls::get('email_Imap', array('accRec' => $accRec));
            
            try {
                // Логването и генериране на съобщение при грешка е винаги в контролерната част
                if ($imapConn->connect() === false) {
                    $anchor = 'Kak-se-nastroyvat-smetkite';
                    if ($rec->email) {
                        list(, $domain) = explode('@', $rec->email);
                        if (strtolower($domain) == 'gmail.com') {
                            $anchor = 'Dopalnitelni-nastroyki-v-sarvarite';
                        }
                    }
                    $errMsg = 'Грешка при свързване|*: ' . $imapConn->getLastError() . '<br>|За повече информация|*: ' . ht::createLink('bgerp.com', "https://bgerp.com/Bg/Zadavane-na-IMAP-POP3-akaunt/#{$anchor}", false, array('target' => '_blank', 'title' => 'Задаване на IMAP/POP3 акаунт'));
                    
                    $form->setWarning('server', $errMsg);
                }
            } catch (ErrorException $e) {
                reportException($e);
                $form->setError('server', 'Грешка при свързване');
            }
        }
    }
    
    
    /**
     * Проверява дали домейна е общ
     */
    public static function isGroupDomain($domain)
    {
        $rec = static::fetch("#email LIKE '%{$domain}' AND #applyRouting = 'yes'");
        
        return $rec;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete') {
            if ($rec->id) {
                if (email_Inboxes::fetch("#accountId = {$rec->id}")) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Когато се създава акаунт, към него се съзадава и входяща пощенска кутия
     */
    public function on_BeforeSave($mvc, &$id, $rec, $saveFileds = null)
    {
        // Вдигаме флаг, да се създаде корпоративен имейл на всички потребители с определена роля
        // Ако се добавя активна корпоративна сметка или се сменя домейна
        if (($rec->type == 'corporate') && ($rec->state == 'active')) {
            if (!$rec->id) {
                $rec->AddCorporateEmail = true;
            } else {
                $oCAcc = $mvc->getCorporateAcc();
                list(, $domain) = explode('@', $rec->email);
                if ($domain != $oCAcc->domain) {
                    $rec->AddCorporateEmail = true;
                }
            }
        }
    }
    
    
    /**
     * Когато се създава акаунт, към него се съзадава и входяща пощенска кутия
     */
    public function on_AfterSave($mvc, &$id, $rec, $saveFileds = null)
    {
        if (email_Inboxes::fetch("#email = '{$rec->email}'")) {
            
            return;
        }
        
        $boxRec = new stdClass();
        
        $boxRec->email = $rec->email;
        
        $boxRec->accountId = $rec->id;
        
        setIfNot($boxRec->access, $rec->access, 'private');
        
        $userId = core_Users::getCurrent();
        
        setIfNot($boxRec->inCharge, $rec->inCharge, $userId);
        
        setIfNot($boxRec->shared, $rec->shared);
        
        // @todo: Да се махне долния участък, след повсеместния ъпдейт
        setIfNot($boxRec->folderId, $rec->folderId);
        
        if ($boxRec->id = $rec->oldInboxId) {
            $boxRec->createdOn = dt::verbal2mysql();
            $boxRec->createdBy = core_Users::getCurrent();
            email_Inboxes::save($boxRec, null, 'REPLACE');
        }
        
        email_Inboxes::forceCoverAndFolder($boxRec);
        
        if ($rec->AddCorporateEmail) {
            $uArr = core_Users::getByRole(email_Setup::get('ROLE_FOR_CORPORATE_EMAIL'));
            
            foreach ($uArr as $uId) {
                $uRec = core_Users::fetch($uId);
                $uRec->CorporateAccId = $rec->id;
                $uRec->_notModified = true;
                core_Users::save($uRec, 'id');
            }
        }
    }
    
    
    /**
     * Добавя акаунт, ако има зададен такъв в конфигурационния файл
     * и ако няма запис в базата
     */
    public static function loadData()
    {
        $mvc = cls::get('email_Accounts');
        
        if (defined('BGERP_DEFAULT_EMAIL_FROM') && BGERP_DEFAULT_EMAIL_FROM != '') {
            if (!$mvc->fetch(array("#email = '[#1#]'", BGERP_DEFAULT_EMAIL_FROM))) {
                $rec = new stdClass();
                
                $rec->email = BGERP_DEFAULT_EMAIL_FROM;
                
                // Дали да се рутират писмата, получени на този акаунт
                defIfNot('BGERP_DEFAULT_EMAIL_APPLY_ROUTING', 'yes');
                $rec->applyRouting = BGERP_DEFAULT_EMAIL_APPLY_ROUTING;
                
                // Дали към този акаунт отговаря и за други вътрешни адреси, освен посочения в email?
                // Самостоятелна сметка: Отговаря само за имейл адреса, с който е дефинирана
                // Събирателна сметка: Отговаря и за други имейл адреси
                // Корпоративна сметка: Отговаря за много други имейл адреси, и кутията по подразбиране на потребителите е в същия домейн, като на тази сметка
                defIfNot('BGERP_DEFAULT_EMAIL_TYPE', 'corporate');
                $rec->type = BGERP_DEFAULT_EMAIL_TYPE;
                
                // Входящо получаване
                defIfNot('BGERP_DEFAULT_EMAIL_HOST', 'localhost');
                $rec->server = BGERP_DEFAULT_EMAIL_HOST;
                
                defIfNot('BGERP_DEFAULT_EMAIL_PROTOCOL', 'imap');
                $rec->protocol = BGERP_DEFAULT_EMAIL_PROTOCOL;
                
                list($server, $port) = explode(':', $rec->server);
                defIfNot('BGERP_DEFAULT_EMAIL_SECURITY', ($port == 995 || $port == 993) ? 'ssl' : 'default');
                $rec->security = BGERP_DEFAULT_EMAIL_SECURITY;
                
                defIfNot('BGERP_DEFAULT_EMAIL_CERT', 'noValidate');
                $rec->cert = BGERP_DEFAULT_EMAIL_CERT;
                
                defIfNot('BGERP_DEFAULT_EMAIL_FOLDER', 'inbox');
                $rec->folder = BGERP_DEFAULT_EMAIL_FOLDER;
                
                $rec->user = BGERP_DEFAULT_EMAIL_USER;
                $rec->password = BGERP_DEFAULT_EMAIL_PASSWORD ;
                
                // Изтегляне
                $rec->state = 'active';
                $rec->period = 1;
                
                defIfNot('BGERP_DEFAULT_EMAIL_DELETE', 'no');
                $rec->deleteAfterRetrieval = BGERP_DEFAULT_EMAIL_DELETE;
                
                // Изпращане
                defIfNot('BGERP_DEFAULT_EMAIL_SMTP_SERVER', 'localhost');
                $rec->smtpServer = BGERP_DEFAULT_EMAIL_SMTP_SERVER;
                
                defIfNot('BGERP_DEFAULT_EMAIL_SMTP_SECURE', 'no');
                $rec->smtpSecure = BGERP_DEFAULT_EMAIL_SMTP_SECURE;
                
                defIfNot('BGERP_DEFAULT_EMAIL_SMTP_AUTH', 'no');
                $rec->smtpAuth = BGERP_DEFAULT_EMAIL_SMTP_AUTH;
                
                defIfNot('BGERP_DEFAULT_EMAIL_SMTP_USER', null);
                $rec->smtpUser = BGERP_DEFAULT_EMAIL_SMTP_USER;
                
                defIfNot('BGERP_DEFAULT_EMAIL_SMTP_PASSWORD', null);
                $rec->smtpPassword = BGERP_DEFAULT_EMAIL_SMTP_PASSWORD;
                
                $mvc->save($rec, null, 'IGNORE');
                
                $res .= '<li>Добавен вх./изх. имейл сметка: ' . BGERP_DEFAULT_EMAIL_FROM;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Определя дали един имейл адрес е "ОБЩ" или не е.
     *
     * @param string $email
     *
     * @return bool
     */
    public static function isGeneric($email)
    {
        $rec = static::fetch("#email = '{$email}'");
        
        return (boolean) $rec && ($rec->applyRouting == 'yes');
    }
    
    
    /**
     * @return PHPMailer
     */
    public static function getPML($emailFrom)
    {
        expect($accId = email_Inboxes::fetchField("#email = '{$emailFrom}'", 'accountId'));
        
        expect($rec = self::fetch($accId));
        
        $params = array();
        
        // Метода за изпращане на писма ще е SMTP
        $params['Mailer'] = 'smtp';
        
        // Определяне на хоста и порта
        $hostArr = explode(':', $rec->smtpServer, 2);
        
        $params['Host'] = $hostArr[0];
        
        if (stripos($params['Host'], 'ssl://') === 0) {
            $params['Host'] = substr($params['Host'], 6);
            $rec->smtpSecure = 'ssl';
        }
        
        if (stripos($params['Host'], 'tls://') === 0) {
            $params['Host'] = substr($params['Host'], 6);
            $rec->smtpSecure = 'tls';
        }
        
        if (count($hostArr) == 2) {
            $params['Port'] = $hostArr[1];
        } else {
            if ($rec->smtpSecure == 'tls') {
                $params['Port'] = 587;
            } elseif ($rec->smtpSecure == 'ssl') {
                $params['Port'] = 465;
            } else {
                $params['Port'] = 25;
            }
        }
        
        // Дали да се използва криптиране по време на сесията?
        $params['SMTPSecure'] = ($rec->smtpSecure == 'no') ? '' : $rec->smtpSecure;
        
        // Дали да се прави аутентикация на потребителя и каква да е тя?
        if ($rec->smtpAuth != 'no') {
            $params['SMTPAuth'] = true;
            $params['AuthType'] = $rec->smtpAuth;
            
            if ($rec->smtpUser) {
                $params['Username'] = $rec->smtpUser;
            } else {
                $params['Username'] = $rec->user;
            }
            
            if ($rec->smtpPassword) {
                $params['Password'] = $rec->smtpPassword;
            } else {
                $params['Password'] = $rec->password;
            }
        } else {
            $params['SMTPAuth'] = false;
        }
        
        $params['XMailer'] = 'bgERP email client';
        
        $pml = cls::get('phpmailer_Instance', $params);
        
        return $pml;
    }
    
    
    /**
     * Форсира проверка на свалените имейли
     */
    public function act_checkMailBox()
    {
        requireRole('admin');
        
        // Вземаме последователно сметките, подредени по случаен начин
        $accQuery = email_Accounts::getQuery();
        $accQuery->XPR('order', 'double', 'RAND()');
        $accQuery->orderBy('#order');
        
        $resMsg = '';
        
        while (($accRec = $accQuery->fetch("#state = 'active'"))) {
            $resMsg .= '<li><b>' . $accRec->email . '</b>: ';
            
            $pKey = 'checkMailBox|' . $accRec->id;
            
            $emlStatus = core_Permanent::get($pKey);
            
            if ($emlStatus) {
                $resMsg .= tr('вече има активирана проверка за този имейл');
                continue;
            }
            
            $lockKey = 'Inbox:' . $accRec->id;
            
            expect(core_Locks::get($lockKey, 50, 30));
            
            // Връзка по IMAP към сървъра на посочената сметка
            $imapConn = cls::get('email_Imap', array('accRec' => $accRec));
            
            if ($imapConn->connect() !== false) {
                // Получаваме броя на писмата в INBOX папката
                $numMsg = $imapConn->getStatistic('messages');
                
                // Махаме заключването от кутията
                core_Locks::release($lockKey);
                
                if (!$numMsg) {
                    $resMsg .= tr('няма имейли в кутията');
                    continue;
                }
                
                $emlStatus = $accRec->id . '|0|' . $numMsg;
                
                $mp = $accRec->id;
                if ($mp > 10) {
                    $mp = rand(1, 10);
                }
                
                $callOn = dt::addSecs(60 * $mp++);
                core_CallOnTime::setCall('email_Accounts', 'checkMailBox', $emlStatus, $callOn);
                
                core_Permanent::set($pKey, $emlStatus, 100000);
                
                $resMsg .= tr('успешно добавена кутия за проверка');
            } else {
                $resMsg .= tr('грешка при свързване');
            }
        }
        
        if (!$resMsg) {
            $resMsg = tr('Няма имейл кутии за проверка');
        }
        
        return $resMsg;
    }
    
    
    /**
     * Функция за проверка на свалените имейли
     * Ако хеша го няма - предизвиква сваляне
     *
     * @param string $emlStatus
     */
    public static function callback_checkMailBox($emlStatus)
    {
        $tLimit = ini_get('max_execution_time') + 70;
        core_App::setTimeLimit($tLimit);
        
        list($accId, $begin, $end) = explode('|', $emlStatus);
        
        if (!$accId) {
            
            return ;
        }
        
        if (!$begin) {
            $begin = 1;
        }
        
        $pKey = 'checkMailBox|' . $accId;
        
        if ($begin >= $end) {
            email_Accounts::logNotice('Приключи проверката на имейл кутията', $accId);
            
            core_Permanent::remove($pKey);
            
            return ;
        }
        
        $accRec = email_Accounts::fetch($accId);
        
        if ($accRec->state != 'active') {
            
            return ;
        }
        
        sleep(7);
        
        $deadline = time() + 40;
        
        $lockKey = 'Inbox:' . $accRec->id;
        
        if (core_Locks::get($lockKey, 55, 30)) {
            $imapConn = cls::get('email_Imap', array('accRec' => $accRec));
            
            if ($imapConn->connect() !== false) {
                
                // За да не гърми с warning при надвишаване на броя имейли
                $numMsg = $imapConn->getStatistic('messages');
                if (isset($numMsg) && $end != $numMsg) {
                    email_Accounts::logDebug("Променен брой имейли за проверка от {$end} на {$numMsg}", $accId);
                    
                    $end = $numMsg;
                }
                if ($begin >= $end) {
                    email_Accounts::logNotice('Приключи проверката на имейл кутията', $accId);
                    
                    core_Permanent::remove($pKey);
                    
                    core_Locks::release($lockKey);
                    
                    return ;
                }
                
                $Incomings = cls::get('email_Incomings');
                
                for ($i = $begin; $i < $end && ($deadline > time()); $i++) {
                    try {
                        $status = $Incomings->fetchEmail($imapConn, $i);
                        
                        if ($status != 'duplicated') {
                            email_Incomings::logNotice('Свален имейл, който е бил пропуснат');
                        }
                    } catch (Exception $e) {
                        reportException($e);
                    }
                }
                
                $begin = $i;
            }
        }
        
        email_Accounts::logNotice("Проверени {$begin} от общо {$end} имейли", $accId);
        
        core_Locks::release($lockKey);
        
        $emlStatus = $accRec->id . '|' . $begin . '|' . $end;
        
        core_Permanent::set($pKey, $emlStatus, 100000);
        
        $mp = $accId;
        if ($mp == 1 || $mp > 10) {
            $mp = rand(1, 10);
        }
        
        $callOn = dt::addSecs(60 * $mp);
        core_CallOnTime::setCall('email_Accounts', 'checkMailBox', $emlStatus, $callOn);
    }
}
