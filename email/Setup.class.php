<?php


/**
 * Максимално време за еднократно фетчване на писма
 */
defIfNot('EMAIL_MAX_FETCHING_TIME', 30);


/**
 * Минимална дължина над която ще се проверява за баркод при сваляне на файл
 */
defIfNot('EMAIL_MIN_FILELEN_FOR_BARCOCE', 15000);


/**
 * Максимална дължина пок която ще се проверява за баркод при сваляне на файл
 */
defIfNot('EMAIL_MAX_FILELEN_FOR_BARCOCE', 250000);


/**
 * Разширения, в които ще се търси баркод при сваляне на имейл
 */
defIfNot('EMAIL_ALLOWED_EXT_FOR_BARCOCE', "pdf,tif,tiff,jpg,jpeg");


/**
 * Период за сваляне на имейли
 */
defIfNot('EMAIL_DOWNLOAD_PERIOD', 120);


/**
 * Максималната разрешена памет за използване
 */
defIfNot('EMAIL_MAX_ALLOWED_MEMORY', '838860800');


/**
 * Шаблон за име на папките, където отиват писмата от дадена държава и неподлежащи на
 * по-адекватно сортиране
 */
defIfNot('EMAIL_UNSORTABLE_COUNTRY', 'Несортирани - %s');


/**
 * Потребител, който ще е отговорник на несортираните имейли
 */
defIfNot('EMAIL_UNSORTABLE_INCHARGE', '');


/**
 * Максималното време за изчакване на буфера
 */
defIfNot('EMAIL_POP3_TIMEOUT', 2);


/**
 * Максималната големина на файловете, които ще се приемат за CID
 * 10kB
 */
defIfNot('EMAIL_MAXIMUM_CID_LEN', 10240);


/**
 * След колко време (в секунди) след първото изпращане към един имейл да се взема в предвид, че е изпратено преди (Повторно изпращане) 
 * 
 * По подразбиране 12 часа
 */
defIfNot('EMAIL_RESENDING_TIME', '43200');


/**
 * Максимална дължина на текстовата част на входящите имейли
 */
defIfNot('EMAIL_MAX_TEXT_LEN', '1000000');


/**
 * Дали манипулатора на нишката да е в началото на събджекта на писмото
 */
defIfNot('EMAIL_THREAD_HANDLE_POS', 'BEFORE_SUBJECT');


/**
 * Ограничава рутирането по папки до папките на контрагент и "Несортирани - %"
 */
defIfNot('EMAIL_RESTRICT_ROUTE', 'no');


/**
 * След колко време да не се използват обръщеният по имейл за нова нишка
 */
defIfNot('EMAIL_SALUTATION_EMAIL_TIME_LIMIT', 7776000); // 60*60*24*90 - 90 дни


/**
 * Какъв тип да е генерирания манипулатор за събджект на имейл
 * t0 - <123456>
 * t1 - EML234SGR
 * t2 - #123496
 */
defIfNot('EMAIL_THREAD_HANDLE_TYPE', 'type1');


/**
 * Какъв какви типове манипулатори за събджект на имейл се 
 * с минали периоди 
 * t0 - <123456> (номер на нишка)
 * t1 - EML234SGR (манипулатор на документ + защита)
 * t2 - #123496 (номер на нишка + защита)
 */
defIfNot('EMAIL_THREAD_HANDLE_LEGACY_TYPES', 'type0');


/**
 * Максимален размер на примкачените файлове при изпращане на имейл
 * 20MB
 */
defIfNot('EMAIL_MAX_ATTACHED_FILE_LIMIT', 20971520);


/**
 * Имейла по подразбиране, при изпращане
 */
defIfNot('EMAIL_DEFAULT_SENT_INBOX', '');


/**
 * Автоматично попълване на имейлите в полето копие
 */
defIfNot('EMAIL_AUTO_FILL_EMAILS_FROM_CC', 0);


/**
 * Хедъра на имейла на текстовата част, който се генерира автоматично при създаване на изходящ имейл
 */
defIfNot('EMAIL_OUTGOING_HEADER_TEXT', "[#hello#] [#salutation#] [#name#]");


/**
 * Хедъра на имейла на текстовата част, който се генерира автоматично при създаване на изходящ имейл - на английски
 */
defIfNot('EMAIL_OUTGOING_HEADER_TEXT_EN', "[#hello#] [#salutation#] [#name#]");


/**
 * Футъра на имейла на текстовата част, който се генерира автоматично при създаване на изходящ имейл
 */
defIfNot('EMAIL_OUTGOING_FOOTER_TEXT', "Сърдечни поздрави,\n[#name#]\n[#company#]\n[#position#]\nТел.: [#tel#]\nФакс: [#fax#]\n[#email#]\n[#website#]");


/**
 * Футъра на имейла на текстовата част, който се генерира автоматично при създаване на изходящ имейл - на английски
 */
defIfNot('EMAIL_OUTGOING_FOOTER_TEXT_EN', "Best regards,\n[#name#]\n[#company#]\n[#position#]\nTel.: [#tel#]\nFax: [#fax#]\n[#email#]\n[#website#]");


/**
 * Текст по подразбиране при отговор на имейл
 */
defIfNot('EMAIL_INCOMINGS_DEFAULT_EMAIL_BODY', "Благодаря за имейла от [#DATETIME#]");


/**
 * Текст по подразбиране при отговор на имейл - на английски
 */
defIfNot('EMAIL_INCOMINGS_DEFAULT_EMAIL_BODY_EN', "Thanks for the email on [#DATETIME#]");


/**
 * Текст по подразбиране при препращане на имейл
 */
defIfNot('EMAIL_FORWARDING_DEFAULT_EMAIL_BODY_FORWARDING', "Моля запознайте се с препратения имейл [#MSG#]");


/**
 * Текст по подразбиране при препращане на имейл - на английски
 */
defIfNot('EMAIL_FORWARDING_DEFAULT_EMAIL_BODY_FORWARDING_EN', "Please read the forwarded email [#MSG#]");


/**
 * Имейл домейни за подменяне
 */
defIfNot('EMAIL_REPLACE_DOMAINS', '');


/**
 * Имейл до кото няма да се праща
 */
defIfNot('EMAIL_STOP_SEND_TO', 'no-reply@*,noreply@*');


/**
 * Хедъри, които ще се проверяват за спам скоре
 */
defIfNot('EMAIL_CHECK_SPAM_SCORE_HEADERS', 'x-spam-status,x-spam-score');


/**
 * Стойност, над която имейлите ще се приемат за спам и ще се оттеглят
 */
defIfNot('EMAIL_HARD_SPAM_SCORE', 7);


/**
 * Стойност, над която имейлите ще се приемат за спам и ще се оттеглят
 */
defIfNot('EMAIL_REJECT_SPAM_SCORE', 4);


/**
 * class email_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с 'email'
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'email_Incomings';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Управление на входящи имeйл сметки и вътрешни кутии";
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'fileman=0.1,doc=0.1';
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    
            'EMAIL_DOWNLOAD_PERIOD' => array ('time(suggestions=1 мин.|2 мин.|3 мин.)', 'mandatory, caption=Период за сваляне на имейлите->Време'),
            
            // Максимално време за еднократно фетчване на писма
            'EMAIL_MAX_FETCHING_TIME' => array ('time(suggestions=1 мин.|2 мин.|3 мин.)', 'mandatory, caption=Максимално време за получаване на имейли в една сесия->Време'),
    
            // Максималното време за изчакване на буфера
            'EMAIL_POP3_TIMEOUT'  => array ('time(suggestions=1 сек.|2 сек.|3 сек.)', 'mandatory, caption=Таймаут на POP3 сокета->Време'),
            
            // Максималната разрешена памет за използване
            'EMAIL_MAX_ALLOWED_MEMORY' => array ('fileman_FileSize', 'mandatory, caption=Максималната разрешена памет за използване при парсиране на имейли->Размер, suggestions=100 MB|200 MB|400 MB|800 MB|1200 MB'),

            // Шаблон за име на папки
            'EMAIL_UNSORTABLE_COUNTRY' => array ('varchar', 'mandatory, caption=Шаблон за име на папки с несортирани имейли->Шаблон'),
            
            // Потребител, който ще е отговорник на несортираните имейли
            'EMAIL_UNSORTABLE_INCHARGE' => array ('user(roles=powerUser, rolesForTeams=admin, rolesForAll=admin, allowEmpty)', 'caption=Потребител|*&comma;| който ще е отговорник на несортираните имейли->Потребител'),

            // Максималната големина на файловете, които ще се приемат за CID
            'EMAIL_MAXIMUM_CID_LEN' => array ('int', 'caption=Максималната големина на файловете|*&comma;| които ще се приемат за вградени изображения->Размер'),
            
            // След колко време (в секунди) след първото изпращане към един имейл да се взема в предвид, че е изпратено преди (Повторно изпращане) 
            'EMAIL_RESENDING_TIME' => array ('time(suggestions=1 часа|2 часа|3 часа|5 часа|7 часа|10 часа|12 часа)', 'caption=Време от първото изпращане на имейл|*&comma;| след което се маркира "Преизпращане"->Време'),
            
            // Максимален брой символи в текстовата част на входящите имейли
            'EMAIL_MAX_TEXT_LEN' => array ('int', 'caption=Максимален брой символи в текстовата част на входящите имейли->Символи'),
            
            // Тип на манипулатора в събджекта
            'EMAIL_THREAD_HANDLE_POS' => array ('enum(BEFORE_SUBJECT=Преди събджекта,AFTER_SUBJECT=След събджекта)', 'caption=Манипулатор на нишка в събджект на имейл->Позиция'),
            
            // Позиция на манипулатора в събджекта
            'EMAIL_THREAD_HANDLE_TYPE' => array ('enum(type0=Тип 0 <1234>,type1=Тип 1 #EML123DEW,type2=Тип 2 #123498,type3=Тип 3 <aftepod>)', 'caption=Манипулатор на нишка в събджект на имейл->Тип'),
            
            // Позиция на манипулатора в събджекта
            'EMAIL_THREAD_HANDLE_LEGACY_TYPES' => array ('set(type0=Тип 0 <1234>,type1=Тип 1 #EML123DEW,type2=Тип 2 #123498,type3=Тип 3 <aftepod>)', 'caption=Манипулатор на нишка в събджект на имейл->Наследени,columns=1'),
            
            // Домейни за заменяне
            'EMAIL_REPLACE_DOMAINS' => array ('varchar', 'caption=Домейни за заменяне->Списък,columns=1', array('hint' => 'OldDomain1=NewDomain1,OldDomain2=NewDomain2,...')),

            // Максимален размер на прикачените файлове и документи
            'EMAIL_MAX_ATTACHED_FILE_LIMIT' => array ('fileman_FileSize', 'caption=Максимален размер на прикачените файлове/документи в имейла->Размер, suggestions=10 MB|20 MB|30 MB'),
            
            'EMAIL_DEFAULT_SENT_INBOX' => array ('key(mvc=email_Inboxes,select=email,allowEmpty)', 'caption=Изпращач на изходящите имейли->От, placeholder=Автоматично,customizeBy=powerUser, optionsFunc=email_Inboxes::getAllowedFromEmailOptions'),
            
            'EMAIL_AUTO_FILL_EMAILS_FROM_CC' => array ('int', 'caption=Автоматично попълване на имейлите в полето копие|*&comma; |когато са до->Брой, customizeBy=powerUser'),
            
            'EMAIL_RESTRICT_ROUTE' => array ('enum(yes=Да, no=Не)', 'caption=Ограничаване на рутурането по папки->Избор'),
            
            'EMAIL_OUTGOING_HEADER_TEXT' => array ('richtext(rows=5,bucket=Postings)', 'caption=Привет в изходящите имейли->На български, customizeBy=powerUser'),
    
            'EMAIL_OUTGOING_HEADER_TEXT_EN' => array ('richtext(rows=5,bucket=Postings)', 'caption=Привет в изходящите имейли->На английски, customizeBy=powerUser'),
    
            'EMAIL_OUTGOING_FOOTER_TEXT' => array ('richtext(rows=5,bucket=Postings)', 'caption=Подпис за изходящите имейли->На български, customizeBy=powerUser'),
    
            'EMAIL_OUTGOING_FOOTER_TEXT_EN' => array ('richtext(rows=5,bucket=Postings)', 'caption=Подпис за изходящите имейли->На английски, customizeBy=powerUser'),
    
            'EMAIL_SALUTATION_EMAIL_TIME_LIMIT' => array ('time(suggestions=30 дни|90 дни|180 дни)', 'caption=След колко време да не се използват обръщенията по имейл за нова нишка->Време'),
            
            'EMAIL_INCOMINGS_DEFAULT_EMAIL_BODY' => array ('varchar', 'caption=Текст по подразбиране при отговор на имейл->На български, customizeBy=powerUser'),
            
            'EMAIL_INCOMINGS_DEFAULT_EMAIL_BODY_EN' => array ('varchar', 'caption=Текст по подразбиране при отговор на имейл->На английски, customizeBy=powerUser'),
    
            'EMAIL_FORWARDING_DEFAULT_EMAIL_BODY_FORWARDING' => array ('varchar', 'caption=Текст по подразбиране при препращане на имейл->На български, customizeBy=powerUser'),
    
            'EMAIL_FORWARDING_DEFAULT_EMAIL_BODY_FORWARDING_EN' => array ('varchar', 'caption=Текст по подразбиране при препращане на имейл->На английски, customizeBy=powerUser'),
            
            'EMAIL_STOP_SEND_TO' => array ('varchar', 'caption=Шаблон за имейли до които няма да се праща->Шаблон'),
            
            'EMAIL_CHECK_SPAM_SCORE_HEADERS' => array ('varchar', 'caption=Проверка на СПАМ рейтинг->Хедъри'),
            
            'EMAIL_HARD_SPAM_SCORE' => array ('varchar', 'caption=Проверка на СПАМ рейтинг->Твърд спам'),
            
            'EMAIL_REJECT_SPAM_SCORE' => array ('varchar', 'caption=Проверка на СПАМ рейтинг->Оттегляне'),
        );
        
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'email_Incomings',
            'email_Outgoings',
            'email_Inboxes',
            'email_Accounts',
            'email_Router',
            'email_Addresses',
            'email_FaxSent',
            'email_Filters',
            'email_Returned',
            'email_Receipts',
            'email_Spam',
            'email_Fingerprints',
            'email_Unparsable',
            'email_Salutations',
            'email_ThreadHandles',
            'email_SendOnTime',
            'migrate::transferThreadHandles',
            'migrate::fixEmailSalutations',
            'migrate::repairRecsInFilters',
            'migrate::repairSendOnTimeClasses',
            'migrate::updateUserInboxesD',
            'migrate::repairSalutations',
            'migrate::repairDelayTime',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'email, fax';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.23, 'Документи', 'Имейли', 'email_Outgoings', 'default', "admin, email, fax, user"),
        );
        
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
       
        $html = parent::install();
            
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('Email', 'Прикачени файлове в имейлите', NULL, '104857600', 'user', 'user');
             
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $html .= $Plugins->installPlugin('UserInbox', 'email_UserInboxPlg', 'core_Users', 'private');
        
        // Инсталираме плъгина за преобразуване на имейлите в линкове
        $html .= $Plugins->installPlugin('EmailToLink', 'email_ToLinkPlg', 'type_Email', 'private');
        
        //
        // Инсталиране на плъгин за автоматичен превод на входящата поща
        //
        $html .= $Plugins->installPlugin('Email Translate', 'email_plg_IncomingsTranslate', 'email_Incomings', 'private');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }


    /**
     * Миграция, която прехвърля манипулаторите на нишки от модел doc_Threads 
     * в email_ThreadHandles
     */
    function transferThreadHandles()
    {
        $docThreads = cls::get('doc_Threads');
        
        if($docThreads->db->isFieldExists($docThreads->dbTableName, 'handle')) {
            // Манипулатор на нишката (thread handle)
            $docThreads->FLD('handle', 'varchar(32)', 'caption=Манипулатор');

            $tQuery = $docThreads->getQuery();

            while($rec = $tQuery->fetch("#handle IS NOT NULL")) {
                $rec->handle = strtoupper($rec->handle);
                if($rec->handle{0} >= 'A' && $rec->handle{0} <= 'Z') {
                    email_ThreadHandles::save( (object) array('threadId' => $rec->id, 'handle' => '#' . $rec->handle), NULL, 'IGNORE');
                }
            }
        } 
    }
    
    
    /**
     * Миграция
     * Премахва празните записи и добавя toEmail
     */
    public static function fixEmailSalutations()
    {
        $query = email_Salutations::getQuery();
        while ($rec = $query->fetch()) {
            
            // Ако няма обръщение, премахваме от списъка
            if (!trim($rec->salutation) || !$rec->containerId) {
                email_Salutations::delete($rec->id);
                continue;
            }
            
            // От имейла извличаме стойността на полето имейл и обновяваме записа
            $doc = doc_Containers::getDocument($rec->containerId);
            if (($doc->instance instanceof email_Outgoings) && $doc->that) {
                $emailRec = $doc->getInstance()->fetch($doc->that);
                
                $rec->state = $emailRec->state;
                
                $rec->toEmail = $emailRec->email;
                
                email_Salutations::save($rec);
            }
        }
    }
    
    
    /**
     * Миграция, за да се изтрият повтарящите се записи и да се изчисли systemId
     */
    public static function repairRecsInFilters()
    {
        $query = email_Filters::getQuery();
        
        $condFieldArr = array();
        $condFieldArr[] = 'email';
        $condFieldArr[] = 'subject';
        $condFieldArr[] = 'body';
        
        $systemArr = array();
        
        while ($rec = $query->fetch()) {
            
            foreach ($condFieldArr as $field) {
                $rec->$field = str_replace('%', '*', $rec->$field);
            }
            
            $systemId = email_Filters::getSystemId($rec);
            
            if ($systemArr[$systemId]) {
                
                email_Filters::delete($rec->id);
                continue;
            }
                
            $systemArr[$systemId] = $systemId;
        
            $rec->systemId = $systemId;
            
            email_Filters::save($rec);
        }
    }
    
    
    /**
     * Миграция, за поправка на класовете в sendOnTime
     */
    public static function repairSendOnTimeClasses()
    {
        $query = email_SendOnTime::getQuery();
        while ($rec = $query->fetch()) {
            if (!cls::load($rec->class, TRUE)) continue;
            $clsInst = cls::get($rec->class);
            
            $rec->class = core_Cls::getClassName($clsInst);
            
            email_SendOnTime::save($rec, 'class');
        }
    }
    
    
    /**
     * Обновява имейл акаунтите в userInboxes в email_Incomings
     */
    public static function updateUserInboxesD()
    {
        $callOn = dt::addSecs(120);
        core_CallOnTime::setOnce('email_Setup', 'migrateEmails', NULL, $callOn);
    }
    
    
    /**
     * Извиква се от core_CallOnTime
     * Прави миграцията на updateUserInboxesD - добавя стойности за userInboxes и toAndCc
     * 
     * @see core_CallOnTime
     */
    public static function callback_migrateEmails()
    {
        $isLogging = core_Debug::$isLogging;
        
        try {
            core_Debug::$isLogging = FALSE;
            
            $inst = cls::get('email_Incomings');
            
            $query = $inst->getQuery();
            $query->where("#headers IS NOT NULL");
            $query->orWhere("#emlFile IS NOT NULL");
            
            $query->where("#userInboxes IS NULL");
            $query->orWhere("#toAndCc IS NULL");
            
            $query->limit(1000);
            $query->orderBy('createdOn', 'DESC');
            
            while ($rec = $query->fetch()) {
                
                $haveRec = TRUE;
                
                $inst->calcAllToAndCc($rec);
                
                $inst->updateUserInboxes($rec);
            }
            
            if ($haveRec) {
                $callOn = dt::addSecs(120);
                core_CallOnTime::setCall('email_Setup', 'migrateEmails', NULL, $callOn);
            }
        } catch (core_exception_Expect $e) {
            reportException($e);
            
            $callOn = dt::addSecs(300);
            core_CallOnTime::setCall('email_Setup', 'migrateEmails', NULL, $callOn);
        }
        
        core_Debug::$isLogging = $isLogging;
    }
    
    
    /**
     * Проверяваме дали всичко е сетнато, за да работи пакета
     * Ако има грешки, връщаме текст
     */
    public function checkConfig()
    {
        if (!function_exists('imap_open')) {
            
            return 'Не е инсталиран IMAP модула на PHP';
        }
    }
    
    
    /**
     * Поправка на userId на обръщенията
     */
    public function repairSalutations()
    {
        $query = email_Salutations::getQuery();
        $query->where("#userId IS NULL");
        $query->orWhere("#userId = '0' || #userId = '-1'");
        
        while ($rec = $query->fetch()) {
            $rec->userId = $rec->createdBy;
            
            email_Salutations::save($rec, 'userId');
        }
    }


    /**
     * Миграция за поправка на полетата за изчакване от time в datetime
     */
    public static function repairDelayTime()
    {
        // Ако полето липсва в таблицата на модела да не се изпълнява
        $cls = cls::get('email_SendOnTime');
        $cls->db->connect();
        $delayField = str::phpToMysqlName('delay');
        if (!$cls->db->isFieldExists($cls->dbTableName, $delayField)) return ;
        
        $eQuery = $cls->getQuery();
        
        unset($eQuery->fields['delay']);
        $eQuery->FLD('delay', 'time');
        
        $eQuery->where("#delaySendOn IS NULL");
        $eQuery->where("#delay IS NOT NULL");
        $eQuery->where("#delay != 0");
        
        while($eRec = $eQuery->fetch()) {  
            $eRec->delaySendOn = dt::addSecs($eRec->delay, $eRec->createdOn);
            
            $cls->save($eRec, 'delaySendOn');
        }
    }
    
    
    /**
     * Зареждане на данни
     */
    function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);
        
        $res .= $this->callMigrate('repairDownloadedOn', 'email');
        
        return $res;
    }
    
    
    /**
     * Миграция за задаване на текущото време на свалените имейли
     */
    public static function repairDownloadedOn()
    {
        $Fingerprints = cls::get('email_Fingerprints');
        
        $downOnFiled = str::phpToMysqlName('downloadedOn');
        
        $now = dt::now();
        
        $Fingerprints->db->query("UPDATE `{$Fingerprints->dbTableName}` SET `{$downOnFiled}` = '{$now}'");
    }
}
