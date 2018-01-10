<?php


/**
 * Описание на IMAP/POP3 акаунти за входящи имейли
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Milen Georgiev <milen@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Accounts extends core_Master
{
    
    /**
     * Плъгини за работа
     */
    var $loadList = 'email_Wrapper, plg_State, plg_Created, plg_Modified, plg_RowTools2, plg_CryptStore';
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Имейл акаунти";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, ceo';
    
    
    /**
     * 
     */
    var $canList = 'admin';

    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'admin';
    
	
    /**
     * Кой може да го разглежда?
     */
    var $canWrite = 'admin';
    
         
    /**
     * полета от БД по които ще се търси
     */
    var $searchFields = 'email';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'email/tpl/SingleLayoutAccounts.shtml';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Имейл акаунт';
    
    
    /**
     * Път към картинка 16x16
     */
    var $singleIcon = 'img/16/emailSettings.png';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'email';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, email, type, applyRouting=Рутиране, retreiving=Получаване, sending=Изпращане, lastFetchAll';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD("email", "email(link=no)", "caption=Имейл,mandatory,width=100%", array('hint' => 'Имейл адреса, съответстващ на сметката', 'attr' => array('onblur' => "mailServerSettings();", 'id' => 'email')));
        
        // Дали да се рутират писмата, получени на този акаунт
        $this->FLD('applyRouting', 'enum(no=Без рутиране, yes=С рутиране)', 'notNull,caption=Рутиране,maxRadio=2');
        
        // Дали към този акаунт отговаря и за други вътрешни адреси, освен посочения в email?
        // Самостоятелна сметка: Отговаря само за имейл адреса, с който е дефинирана
        // Събирателна сметка: Отговаря и за други имейл адреси
        // Корпоративна сметка: Отговаря за много други имейл адреси, и кутията по подразбиране на потребителите е в същия домейн, като на тази сметка
        $this->FLD('type', 'enum(single=Самостоятелна,common=Събирателна,corporate=Корпоративна)', 'notNull,caption=Тип');
        
        // Входящо получаване
        $this->FLD("server", "varchar(128)", 'caption=Получаване->Сървър,width=100%,mandatory', array('attr' => array('id' => 'server')));
        $this->FLD("protocol", "enum(imap=IMAP, pop3=POP3)", 'caption=Получаване->Протокол,notNull', array('attr' => array('id' => 'protocol')));
        $this->FLD('security', 'enum(default=По подразбиране,tls=TLS,notls=No TLS,ssl=SSL)', 'caption=Получаване->Сигурност,notNull', array('attr' => array('id' => 'security')));
        $this->FLD('cert', 'enum(noValidate=Без валидиране,validate=С валидиране)', 'caption=Получаване->Сертификат,notNull', array('attr' => array('id' => 'cert')));
        $this->FLD('folder', 'varchar(64)', 'caption=Получаване->IMAP папка,value=INBOX', array('attr' => array('id' => 'folder')));
        $this->FLD('user', 'varchar', 'caption=Получаване->Потребител,width=100%', array('attr' => array('id' => 'user')));
        $this->FLD('password', 'password(64,autocomplete=off)', 'caption=Получаване->Парола,width=100%,crypt');
        
        // Изтегляне
        $this->FLD('state', 'enum(active=Активен, stopped=Спрян)', 'caption=Изтегляне->Статус');
        $this->FLD('fetchingPeriod', 'time(suggestions=30 секунди|1 минута|2 минути|5 минути|10 минути|30 минути|1 час,min=30)', 'caption=Изтегляне->Проверка през,placeholder=1 минута');
        $this->FLD('lastFetchAll', 'datetime', 'caption=Изтегляне->Проверка,input=none');
        $this->FLD('deleteAfterPeriod', 'time(uom=minutes,suggestions=Веднага|2 часа|6 часа|1 ден|1 седмица|1 месец)',
            'caption=Изтегляне->Изтриване,hint=Кога писмото да бъде изтрито от IMAP/POP3 сметката след получаване в системата?,placeholder=Никога,unit=след получаване,oldFiledName=datetime');
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
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = NULL)
    {
        //  protocol, server, user, smtpServer, smtpUser,

        if($fields['-list']) {
            $row->retreiving  = $mvc->getVerbal($rec, 'protocol');
            $row->retreiving .= ' / ' . $mvc->getVerbal($rec, 'server');
            $row->retreiving .= "<br>" . $mvc->getVerbal($rec, 'user');

            $row->sending  = $mvc->getVerbal($rec, 'smtpServer');
            $row->sending .= "<br>" . $mvc->getVerbal($rec, 'smtpUser');
        }
    }

    
    /**
     * Връща записа на корпоративната сметка, ако има такъв
     */
    static function getCorporateAcc()
    {
        $rec = self::fetch("#type = 'corporate' AND #state = 'active'");
        
        if($rec) {
            list($rec->user, $rec->domain) = explode('@', $rec->email);
        }

        return $rec;
    }

    
    /**
     * Връща всички активни корпоративни и общи домейни
     * 
     * @param string $type
     * 
     * @return array
     */
    static function getCommonAndCorporateDomain($type = NULL)
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
            
            if (!$domain) continue;
            
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
    public static function getActiveAccounts($type = NULL)
    {
        $resArr = array();
        $query = self::getQuery();
        
        if ($type) {
            $typeArr = arr::make($type, TRUE);
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
        
        if (isset($resArr[$hash])) return $resArr[$hash];
        
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
    static function canSendEmail($id)
    {
        $rec = self::fetch($id);

        return ($rec->smtpServer != '' && $rec->state == 'active');
    }

    /**
     * Връща името за папката
     */
    function getFolderTitle($id)
    {
        $rec = $this->fetch($id);
        
        $title = $rec->email;
        
        return strtolower($title);
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        return $rec->email;
    }


    /**
     * Преди рендиране на формата за редактиране
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $data->form->setDefault('access', 'private');
         
    }


    /**
     * Проверка за валидност на входните данни
     */
    function on_AfterInputEditForm($mvc, $form)
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
        
        if($form->isSubmitted()) {
            if (email_Router::isPublicDomain(type_Email::domain($rec->email))) {
                if($rec->type != 'single') {  
                    $form->setError('type', "Сметка в публична имейл услуга може да бъде само Самостоятелна");
                }
            }
        }
        
        // Показваме предупреждение, ако възникне грешка при свързване
        if($form->isSubmitted()) {
            
            $accRec = $form->rec;
            
            // Ако се редактира записа, непопълнените полета ги вземаме от модела
            if ($form->rec->id) {
                $sRec = $mvc->fetch($form->rec->id);
                foreach ((array)$sRec as $k => $v) {
                    if (!empty($accRec->{$k})) continue;
                    if($mvc->getFieldType($k) instanceof type_Password) {
                        $accRec->{$k} = $v;
                    }
                }
            }
 
            $imapConn = cls::get('email_Imap', array('accRec' => $accRec));
            
            try {
                // Логването и генериране на съобщение при грешка е винаги в контролерната част
                if ($imapConn->connect() === FALSE) {
                    $errMsg = "Грешка при свързване|*: " . $imapConn->getLastError();
                    
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
    static function isGroupDomain($domain)
    {
        $rec = static::fetch("#email LIKE '%{$domain}' AND #applyRouting = 'yes'");

        return $rec;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'delete') {
            if($rec->id) {
                if(email_Inboxes::fetch("#accountId = {$rec->id}")) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }


    /**
     * Когато се създава акаунт, към него се съзадава и входяща пощенска кутия
     */
    function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        if(email_Inboxes::fetch("#email = '{$rec->email}'")) {

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
        
        if($boxRec->id = $rec->oldInboxId) {
            $boxRec->createdOn = dt::verbal2mysql();
            $boxRec->createdBy = core_Users::getCurrent();
            email_Inboxes::save($boxRec, NULL, 'REPLACE');
        }
        
        email_Inboxes::forceCoverAndFolder($boxRec);
    }
    
    
    /**
     * Добавя акаунт, ако има зададен такъв в конфигурационния файл
     * и ако няма запис в базата
     */
    static function loadData()
    {
        $mvc = cls::get('email_Accounts');

        if(defined('BGERP_DEFAULT_EMAIL_FROM') && BGERP_DEFAULT_EMAIL_FROM != '') {
            if(!$mvc->fetch(array("#email = '[#1#]'", BGERP_DEFAULT_EMAIL_FROM))) {


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
                
                defIfNot('BGERP_DEFAULT_EMAIL_SMTP_USER', NULL);
                $rec->smtpUser = BGERP_DEFAULT_EMAIL_SMTP_USER;
                
                defIfNot('BGERP_DEFAULT_EMAIL_SMTP_PASSWORD', NULL);
                $rec->smtpPassword = BGERP_DEFAULT_EMAIL_SMTP_PASSWORD;

                $mvc->save($rec, NULL, 'IGNORE');
                                
                $res .= "<li>Добавен вх./изх. имейл сметка: " . BGERP_DEFAULT_EMAIL_FROM;
            }
        }

        return $res;
    }
    
    
    /**
     * Определя дали един имейл адрес е "ОБЩ" или не е.
     *
     * @param string $email
     * @return boolean
     */
    public static function isGeneric($email)
    {
        $rec = static::fetch("#email = '{$email}'");
        
        return (boolean)$rec && ($rec->applyRouting == 'yes');
    }
    

    /**
     * @return  PHPMailer
     */
    static function getPML($emailFrom)
    {   
        expect($accId = email_Inboxes::fetchField("#email = '{$emailFrom}'", 'accountId'));
        
        expect($rec = self::fetch($accId));

        $params = array();
        
        // Метода за изпращане на писма ще е SMTP
        $params['Mailer'] = 'smtp';
        
        // Определяне на хоста и порта
        $hostArr = explode(':', $rec->smtpServer, 2);
        
        $params['Host'] = $hostArr[0];
        
        if(stripos($params['Host'], 'ssl://') === 0) {
            $params['Host'] = substr($params['Host'], 6);
            $rec->smtpSecure = 'ssl';
        }
        
        if(stripos($params['Host'], 'tls://') === 0) {
            $params['Host'] = substr($params['Host'], 6);
            $rec->smtpSecure = 'tls';
        }

        if(count($hostArr) == 2) {
            $params['Port'] = $hostArr[1];
        } else {
            if($rec->smtpSecure == 'tls') {
                $params['Port'] = 587;
            } elseif($rec->smtpSecure == 'ssl') {
                $params['Port'] = 465;
            } else {
                $params['Port'] = 25;
            }
        }
        
        // Дали да се използва криптиране по време на сесията?
        $params['SMTPSecure'] = ($rec->smtpSecure == 'no') ? '' : $rec->smtpSecure;
        
        // Дали да се прави аутентикация на потребителя и каква да е тя?
        if($rec->smtpAuth != 'no') {
            $params['SMTPAuth'] = TRUE;
            $params['AuthType'] = $rec->smtpAuth;
            
            if($rec->smtpUser) {
                $params['Username'] = $rec->smtpUser;
            } else {
                $params['Username'] = $rec->user;
            }
            
            if($rec->smtpPassword) {
                $params['Password'] = $rec->smtpPassword;
            } else {
                $params['Password'] = $rec->password;
            }

        } else {
            $params['SMTPAuth'] = FALSE;
        }

        $params['XMailer'] = 'bgERP email client';

        $pml = cls::get('phpmailer_Instance', $params);

        return $pml;
    }

}
