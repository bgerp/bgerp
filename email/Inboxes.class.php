<?php 


/**
 * Домейн и пощенска кутия по подразбиране - изисква се да бъдат дефинирани
 */
defIfNot('BGERP_DEFAULT_EMAIL_DOMAIN');
defIfNot('BGERP_DEFAULT_EMAIL_FROM');
defIfNot('BGERP_DEFAULT_EMAIL_USER');
defIfNot('BGERP_DEFAULT_EMAIL_HOST');
defIfNot('BGERP_DEFAULT_EMAIL_PASSWORD');


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
class email_Inboxes extends core_Master
{
    
    
    /**
     * Плъгини за работа
     */
    var $loadList = 'email_Wrapper, plg_State, plg_Created, doc_FolderPlg, plg_RowTools';
    
    
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
    var $canEdit = 'admin, email';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, email';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin, email';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, email';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin, email';
    
    
    /**
     * Кой има права за имейли-те?
     */
    var $canEmail = 'admin, email';
    
    
    /**
     * Интерфайси, поддържани от този мениджър
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
    var $listFields = 'id, email, type, bypassRoutingRules=Общ, folderId, inCharge, access, shared, createdOn, createdBy';
    
    /**
     * Всички пощенски кутии
     */
    static $allBoxes;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD("email", "email", "caption=Имейл");
        $this->FLD("type", "enum(internal=Вътрешен, pop3=POP3, imap=IMAP)", 'caption=Тип');
        $this->FLD("server", "varchar", 'caption=Сървър');
        $this->FLD('user', 'varchar', 'caption=Потребителско име');
        $this->FLD('password', 'password(64)', 'caption=Парола');
        $this->FLD('state', 'enum(active=Активен, stopped=Спрян)', 'caption=Статус');
        $this->FLD('period', 'int', 'caption=Период');
        
        $this->FLD('port', 'int', 'caption=Порт');
        $this->FLD('subHost', 'varchar', 'caption=Суб Хост');
        $this->FLD('ssl', 'varchar', 'caption=Сертификат');
        
        // Идеално това поле би било чек-бокс, но нещо не се получава с рендирането.
        $this->FLD('bypassRoutingRules', 'enum(no=Да, yes=Не)', 'caption=Сортиране на писмата');
        
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
    static function getRecTitle($rec)
    {
        return $rec->email;
    }
    
    
    /**
     * Преди вкарване на запис в модела, проверява дали има вече регистрирана корица
     */
    function on_BeforeSave($mvc, $id, &$rec)
    {
        list($name, $domain) = explode('@', $rec->email, 2);
        
        if (empty($domain)) {
            $domain = BGERP_DEFAULT_EMAIL_DOMAIN;
        }
        
        $rec->email = "{$name}@{$domain}";
    }
    
    
    /**
     * Преди рендиране на формата за редактиране
     */
    function on_AfterPrepareEditForm($mvc, &$data)
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
     * Намира първия мейл в стринга, който е записан в системата
     */
    static function findFirstInbox($str)
    {
        $allBoxes = static::getInboxes();
        
        $emailsArr = email_Mime::extractEmailsFrom(strtolower($str));
        
        if (is_array($emailsArr) && count($emailsArr)) {
            foreach ($emailsArr as  $eml) {
                if ($allBoxes[$eml] == 'internal') {
                    return $eml;
                }
            }
        }
        
        return NULL;
    }
    
    
    /**
     * Добавя имаил акаунт ако има зададен такъв в конфигурационния файл
     */
    function on_AfterSetupMVC($mvc, $res)
    {   
            
		$rec = $mvc->fetch("#email = '" . BGERP_DEFAULT_EMAIL_FROM . "'");
            
        $rec->email = BGERP_DEFAULT_EMAIL_FROM;
        $rec->server = BGERP_DEFAULT_EMAIL_HOST;
        $rec->user = BGERP_DEFAULT_EMAIL_USER;
        $rec->password = BGERP_DEFAULT_EMAIL_PASSWORD;
        $rec->domain = BGERP_DEFAULT_EMAIL_DOMAIN;
        $rec->period = 1;
        $rec->port = 143;
        $rec->type = 'imap';
        $rec->bypassRoutingRules = "no";
            
        if (!$rec->id) {
            $res .= "<li>Добавен имейл по подразбиране";
        } else {
            $res .= "<li>Обновен имейл по подразбиране";
        }
            
        $mvc->save($rec);
            
        //Създаваме папка на новата кутия
        $mvc->forceCoverAndFolder($rec);
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
        
        return (boolean)$rec && ($rec->bypassRoutingRules == 'no');
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
     * Връща id' то на пощенкста кутия на текущия потребител
     */
    static function getCurrentUserInbox()
    {
        $nick = core_Users::getCurrent('nick');
        
        $email = $nick . '@' . BGERP_DEFAULT_EMAIL_DOMAIN;
        
        $id = email_Inboxes::fetchField("#email = '{$email}'");
        
        return $id;

    }
}
