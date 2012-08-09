<?php 


/**
 * Константи за домейн и пощенска кутия по подразбиране - изисква се да бъдат дефинирани
 */
defIfNot('BGERP_DEFAULT_EMAIL_DOMAIN', '');


/**
 * @todo Чака за документация...
 */
defIfNot('BGERP_DEFAULT_EMAIL_FROM', '');


/**
 * @todo Чака за документация...
 */
defIfNot('BGERP_DEFAULT_EMAIL_USER', '');


/**
 * @todo Чака за документация...
 */
defIfNot('BGERP_DEFAULT_EMAIL_HOST', '');


/**
 * @todo Чака за документация...
 */
defIfNot('BGERP_DEFAULT_EMAIL_PASSWORD', '');


/**
 * Email адреси
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_InboxesOld extends core_Master
{
    
    
    /**
     * Плъгини за работа
     */
    var $loadList = 'email_Wrapper, plg_State, plg_Created, plg_Modified, doc_FolderPlg, plg_RowTools, plg_CryptStore';
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Имейл кутии";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, email';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,manager,';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,manager,officer,executive';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,manager,officer,executive';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin, email';
    
    
    /**
     * Кой има права за
     */
    var $canEmail = 'ceo,manager,officer,executive';
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces =
    // Интерфейс за корица на папка
    'doc_FolderIntf';
    
    
    /**
     * полета от БД по които ще се търси
     */
    var $searchFields = 'email';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Е-кутия';
    
    
    /**
     * Път към картинка 16x16
     */
    var $singleIcon = 'img/16/inbox-image-icon.png';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'email';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, email, type, applyRouting=Общ, folderId, inCharge, access, shared, createdOn, createdBy';
    
    /**
     * Всички пощенски кутии
     */
    static $allBoxes;


    var $oldClassName = 'email_Inboxes';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD("email", "email(link=no)", "caption=Имейл");
        $this->FLD("type", "enum(internal=Вътрешен, pop3=POP3, imap=IMAP)", 'caption=Тип');
        $this->FLD("server", "varchar", 'caption=Сървър');
        $this->FLD('user', 'varchar', 'caption=Потребителско име');
        $this->FLD('password', 'password(64)', 'caption=Парола,crypt');
        $this->FLD('state', 'enum(active=Активен, stopped=Спрян)', 'caption=Статус');
        $this->FLD('period', 'int', 'caption=Период');
        
        $this->FLD('port', 'int', 'caption=Порт');
        $this->FLD('subHost', 'varchar', 'caption=Суб Хост');
        $this->FLD('ssl', 'varchar', 'caption=Сертификат');
        
        // Идеално това поле би било чек-бокс, но нещо не се получава с рендирането.
        $this->FLD('applyRouting', 'enum(yes=Да, no=Не)', 'notNull,caption=Общ (екипен)');
        
        // Поле, показващо, кога за последен път е имало пълно синхронизиране със сметката
        $this->FLD('lastFetchAll', 'datetime', 'caption=Последно източване,input=none');
        
        // Колко минути след свалянето от акаунта, това писмо да бъде изтрито
        $this->FLD('deleteAfterRetrieval', 'enum(no=Не,yes=Да)',
            'caption=Изтриване?,hint=Дали писмото да бъде изтрито от IMAP кутията след получаване в системата?');
        
        $this->setDbUnique('email');
    }
    
    
    /**
     * Връща името
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
     * Преди вкарване на запис в модела, проверява дали има вече регистрирана корица
     * и криптира паролата
     */
    static function on_BeforeSave($mvc, $id, &$rec)
    {
        list($name, $domain) = explode('@', $rec->email, 2);
        
        if (empty($domain)) {
            $domain = BGERP_DEFAULT_EMAIL_DOMAIN;
        }
        
        $rec->email = "{$name}@{$domain}";
        
        if (isset($rec->password)) {
            $rec->password = $rec->password;    
        }
    }
    
    
    /**
     * Преди рендиране на формата за редактиране
     */
    static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $data->form->setDefault('access', 'private');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getInboxes()
    {
        if (!self::$allBoxes) {
            $query = static::getQuery();
            $query->show('id, email, type');
            
            while ($rec = $query->fetch()) {
                self::$allBoxes[$rec->email] = $rec->type;
            }
        }
        
        return self::$allBoxes;
    }
    
    
    /**
     * Намира първия имейл в стринга, който е записан в системата
     */
    static function findFirstInbox($str)
    {
        //Всички пощенски кутии
        $allBoxes = static::getInboxes();
        
        //Вземаме всички имейли
        $emailsArr = email_Mime::extractEmailsFrom(strtolower($str));
        
        //Ако има имейли
        if (is_array($emailsArr) && count($emailsArr)) {
            foreach ($emailsArr as  $eml) {
                
                //Намираме първото съвпадение на имейл, който е 'internal'
                if ($allBoxes[$eml] == 'internal') {
                    
                    return $eml;
                }
            }
            
            //Намираме имейла, за който има активен потребител и домейна е общ
            foreach ($emailsArr as $eml) {
                
                //Ако намери съвпадение връща имейла
                if (static::findAlternativeEmail($eml)) {

                    return $eml;
                }
            }
        }
        
        return NULL;
    }
    
    
    /**
     * Проверява дали има вероятноста да има такъв потребител в системата
     */
    static function findAlternativeEmail($email)
    {
        //Разделяме имейла на акаунт и домейн
        $emailArr = explode('@', $email);
        
        $domain = '@' . $emailArr[1];
        
        $nick = $emailArr[0];
        
        //Ако домейна е общ и има активен потребител
        if ((static::isGroupDomain($domain)) && ($user = core_Users::isActiveUser($nick))) {
            
            //Създаваме папка
            $nick = $user->nick;
            if (EF_USSERS_EMAIL_AS_NICK) {
                $nick = type_Nick::parseEmailToNick($rec->nick);
            }
            
            //Запис необходим за създаване на папка
            $eRec = new stdClass();
            $eRec->inCharge = $user->id;
            $eRec->access = "private";
            $eRec->domain = BGERP_DEFAULT_EMAIL_DOMAIN;
            $eRec->type = 'internal';
            $eRec->applyRouting = 'no';
            $eRec->email = $email;
            $eRec->name = $nick;
            
            email_Inboxes::forceCoverAndFolder($eRec);
            
            return $email;
        }
        
        return FALSE;
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
     * Добавя акаунт, ако има зададен такъв в конфигурационния файл
     * и ако няма запис в базата
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        if(defined('BGERP_DEFAULT_EMAIL_FROM') && BGERP_DEFAULT_EMAIL_FROM != '') {
            if(!$mvc->fetch(array("#email = '[#1#]'", BGERP_DEFAULT_EMAIL_FROM))) {
                $rec = new stdClass();
                $rec->email = BGERP_DEFAULT_EMAIL_FROM;
                $rec->server = BGERP_DEFAULT_EMAIL_HOST;
                $rec->user = BGERP_DEFAULT_EMAIL_USER;
                $rec->password = BGERP_DEFAULT_EMAIL_PASSWORD;
                $rec->domain = BGERP_DEFAULT_EMAIL_DOMAIN;
                $rec->period = 1;
                $rec->port = 143;
                $rec->type = 'imap';
                $rec->applyRouting = "yes";
                
                $mvc->save($rec, NULL, 'IGNORE');
                
                //Създаваме папка на новата кутия
                $mvc->forceCoverAndFolder($rec);
                
                $res .= "<li>Добавен имейл по подразбиране: " . BGERP_DEFAULT_EMAIL_FROM;
            }
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
     * Форсира папката, асоциирана с тази наша пощенска кутия. Ако няма такава кутия не прави нищо.
     *
     * @param string $email
     * @return int key(mvc=doc_Folders)
     */
    public static function forceFolder($email)
    {
        $rec = static::fetch("#email = '{$email}'");
        
        if (!$rec) {
            
            return NULL;
        }
        
        return static::forceCoverAndFolder($rec->id);
    }
    
    
    /**
     * Връща id'то на кутия на потребителя, който сме подали.
     * Ако не сме подали параметър тогава връща на текущия потребител
     */
    static function getUserEmailId($userId = NULL)
    {
        //Ако не сме подали параметър, вземаме ник-а на текущия потребител
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        $email = email_Inboxes::getUserEmail($userId);
        
        $id = email_Inboxes::fetchField("#email = '{$email}'");
        
        return $id;
    }
    
    
    /**
     * Връща имейл-а на потребителя
     * Ако е посочено id' или име на потребителя тогава връща него, в противен случай връща на текущия потребител
     */
    static function getUserEmail($userId = NULL)
    {
        //Ако не сме подали параметър, вземаме ник-а на текущия потребител
        if (!$userId) {
            $userId = core_Users::getCurrent('nick');
        }
        
        $nick = $userId;
        
        //Ако сме подали id' тогава намира потребителя с това id
        if (is_numeric($userId)) {
            //Вземаме nick' а на потребителя
            $nick = core_Users::fetchField($userId, 'nick');
        }
        
        //генерираме имейл-а
        $email = $nick . '@' . BGERP_DEFAULT_EMAIL_DOMAIN;
        
        //Превръщаме имейл-а в малки букви
        $email = strtolower($email);
        
        return $email;
    }
    
    
    /**
     * Връща id' то на потребителя, който е inCharge на съответния имейл
     * 
     * @param email $email
     * 
     * @return integer $rec->inCharge
     */
    static function getEmailInCharge($email) 
    {
        //Тримваме имейла
        $email = str::trim($email);
        
        //Да е с малки букви
        $email = mb_strtolower($email);
        
        //Намираме записа за съответния имейл
        $rec = email_Inboxes::fetch("#email='{$email}'");
        
        //Връщаме inCharge id' то
        return $rec->inCharge;
    }
    
    
    /**
     * Кутиите, от които е позволено на даден потребител да изпраща писма
     * 
     * По дефиниция, това са активните кутии, които или са собственост на потребителя или са
     * споделени с него.
     * 
     * @param int $userId key(mvc=core_Users) ако е NULL - текущия потребител
     * @return array ключ - PK на кутия, стойност - имейл адреса на кутия. Този масив е готов за
     *                      използване като $options на полета от тип type_Key.
     */
    static function getAllowedFrom($userId = NULL)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->where("#inCharge = {$userId} OR #shared LIKE '%|{$userId}|%'");
        $query->where("#state = 'active'");

        $result = array();
        
        while ($rec = $query->fetch()) {
            $result[$rec->id] = $rec->email;
        }
        
        return $result;
    }
}
