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
    var $loadList = 'email_Wrapper, plg_State, plg_Created, plg_Modified, plg_RowTools, plg_CryptStore';
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Имейл акаунти";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, ceo';

     
     
    /**
     * Кой може да го разглежда?
     */
    var $canWrite = 'admin';
    
         
    /**
     * полета от БД по които ще се търси
     */
    var $searchFields = 'email';
    
    
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
    var $listFields = 'id, email, type, applyRouting=Рутиране, protocol, server, user, smtpServer, smtpUser, createdOn, createdBy';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD("email", "email(link=no)", "caption=Имейл,width=100%");
        
        // Дали да се рутират писмата, получени на този акаунт
        $this->FLD('applyRouting', 'enum(no=Без рутиране, yes=С рутиране)', 'notNull,caption=Рутиране,maxRadio=2');
        
        // Дали към този акаунт отговаря и за други вътрешни адреси, освен посочения в email?
        // Самостоятелна сметка: Отговаря само за имейл адреса, с който е дефинирана
        // Събирателна сметка: Отговаря и за други имейл адреси
        // Корпоративна сметка: Отговаря за много други имейл адреси, и кутията по подразбиране на потребителите е в същия домейн, като на тази сметка
        $this->FLD('type', 'enum(single=Самостоятелна,common=Събирателна,corporate=Корпоративна)', 'notNull,caption=Тип');
        
        // Входящо получаване
        $this->FLD("server", "varchar(128)", 'caption=Получаване->Сървър,width=100%');
        $this->FLD("protocol", "enum(imap=IMAP, pop3=POP3)", 'caption=Получаване->Протокол,notNull');
        $this->FLD('security', 'enum(default=По подразбиране,tls=TLS,notls=No TLS,ssl=SSL)', 'caption=Получаване->Сигурност,notNull');
        $this->FLD('cert', 'enum(noValidate=Без валидиране,validate=С валидиране)', 'caption=Получаване->Сертификат,notNull');
        $this->FLD('folder', 'identifier(64)', 'caption=Получаване->IMAP папка,value=INBOX');
        $this->FLD('user', 'varchar', 'caption=Получаване->Потребител,width=100%');
        $this->FLD('password', 'password(64)', 'caption=Получаване->Парола,width=100%,crypt');
        
        // Изтегляне
        $this->FLD('state', 'enum(active=Активен, stopped=Спрян)', 'caption=Изтегляне->Статус');
        $this->FLD('period', 'int', 'caption=Изтегляне->Период');
        $this->FLD('lastFetchAll', 'datetime', 'caption=Последно източване,input=none');
        $this->FLD('deleteAfterRetrieval', 'enum(no=Не,yes=Да)',
            'caption=Изтриване?,hint=Дали писмото да бъде изтрито от IMAP кутията след получаване в системата?');
        
        // Изпращане
        $this->FLD('smtpServer', 'varchar', 'caption=Изпращане->SMTP сървър,width=100%,oldFieldName=smtp');
        $this->FLD('smtpSecure', 'enum(no=Без криптиране,tls=TLS,ssl=SSL)', 'caption=Изпращане->Сигурност');
        $this->FLD('smtpAuth', 'enum(no=Не се изисква,LOGIN=Изисква се,NTLM=MS NTLM)', 'caption=Изпращане->Аутентикация');
        $this->FLD('smtpUser', 'varchar', 'caption=Изпращане->Потребител,width=100%');
        $this->FLD('smtpPassword', 'password(64)', 'caption=Изпращане->Парола,width=100%,crypt');

        $this->setDbUnique('email');
    }

    
    /**
     * Връща записа на корпоративната сметка, ако има такъв
     */
    static function getCorporateAcc()
    {
        $rec = self::fetch("#type = 'corporate'");
        
        if($rec) {
            list($rec->user, $rec->domain) = explode('@', $rec->email);
        }

        return $rec;
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
    static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $data->form->setDefault('access', 'private');
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
     * Когато се създава акаунт, към него се съзадава и входяща пощенска кутия
     */
    function on_AfterSave($mvc, $id, $rec)
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
    static function on_AfterSetupMVC($mvc, &$res)
    {
        if(!$mvc->count()) {
            
            // Импорт на сметките
            $oldQuery = email_InboxesOld::getQuery();

            while($oldRec = $oldQuery->fetch()) {
                if($oldRec->type =='pop3' || $oldRec->type =='imap') {
                    $rec = new stdClass();
                    $rec->id = $oldRec->id;
                    $rec->email = $oldRec->email;
                    $rec->protocol = $oldRec->type;
                    $rec->server = $oldRec->server;
                    
                    if($oldRec->port) {
                        $rec->server .= ':' . $oldRec->port;
                    }

                    if($oldRec->port == 995 || $oldRec->port == 993) {
                        $rec->security = 'ssl';
                    }

                    if($rec->subHost) {
                        $rec->server .= '/' . $oldRec->subHost;
                    }
                    if($oldRec->ssl) {
                        $rec->server .= '/' . $oldRec->ssl;
                    }

                    $rec->user = $oldRec->user;
                    $rec->password = $oldRec->password;

                    $rec->applyRouting = $oldRec->applyRouting;

                    list($user, $domain) = explode('@', $rec->email);

                    if(drdata_Domains::isPublic($domain)) {
                        $rec->type = 'single';
                    } else {
                        $rec->type = 'corporate';
                    }

                    $rec->state = $oldRec->state;
                    $rec->period = $oldRec->period;
                    $rec->lastFetchAll = $oldRec->lastFetchAll;
                    $rec->deleteAfterRetrieval = $oldRec->deleteAfterRetrieval;
                    
                    $rec->inCharge = $oldRec->inCharge;
                    $rec->access   = $oldRec->access;
                    $rec->shared   = $oldRec->shared;
                    $rec->folderId = $oldRec->folderId;
                
                    $rec->oldInboxId = $oldRec->id;

                    $rec->createdOn = $oldRec->createdOn;

                    $rec->createdBy = $oldRec->createdBy;

                    self::save($rec, NULL, 'REPLACE');
                }
            }

            // Импорт на кутиите
            $oldQuery = email_InboxesOld::getQuery();

            // Намираме сметка за входящи писма от корпоративен тип, с домейла на имейла
            $corpAccRec = static::getCorporateAcc();
            
            if($corpAccRec) {
                
                // Добавка за да има SMTP сървър
                if(!$corpAccRec->smtpServer) {
                    $corpAccRec->smtpServer = 'localhost';
                    self::save($corpAccRec);
                }
                while($oldRec = $oldQuery->fetch()) {
                    if($oldRec->type == 'internal') {
                        

                        list($oldUser, $oldDomain) = explode('@', $oldRec->email);
                        
                        if($corpAccRec->domain == $oldDomain) {

                            if($userRec = core_Users::fetch("#nick = '{$oldUser}'")) {

                                if(!email_Inboxes::fetch("#email = '{$oldRec->email}'")) {
                                    $boxRec = new stdClass();
                                    $boxRec->email = $oldRec->email;
                                    $boxRec->accountId = $corpAccRec->id;
                                    $boxRec->inCharge = $oldRec->inCharge;
                                    $boxRec->access   = $oldRec->access;
                                    $boxRec->shared   = $oldRec->shared;
                                    $boxRec->folderId = $oldRec->folderId;

                                    email_Inboxes::save($boxRec, NULL, 'REPLACE');
                                }
                            }
                        }
                    }
                }
            }
        }


        $corpAccRec = static::getCorporateAcc();
            
        // Добавка за да има SMTP сървър
        if(!$corpAccRec->smtpServer) {
            list($corpAccRec->smtpServer) = explode(':', $corpAccRec->server);
            self::save($corpAccRec);
        }

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

        $params['XMailer'] = 'bgERP using PML';

        $pml = cls::get('phpmailer_Instance', $params);

        return $pml;
    }

}
