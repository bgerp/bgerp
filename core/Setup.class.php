<?php


/**
 * Вербално заглавие на приложението
 */
DEFINE('EF_APP_TITLE', 'This Application Title');


/**
 * Дали да се презаписват .htaccess файловете?
 * Може да се зададе друга стойност в конфигурационния файл (напр. conf/bgerp.cfg.php)
 */
defIfNot('CORE_OVERWRITE_HTAACCESS', TRUE);


/**
 * Формат по подразбиране за датите
 */
defIfNot('EF_DATE_FORMAT', 'd.m.Y');


/**
 * Дали да се използва времевата зона на потребителя
 */
defIfNot('EF_DATE_USE_TIMEOFFSET', 'yes');


/**
 * Формат по подразбиране за датата при тесни екрани
 */
defIfNot('EF_DATE_NARROW_FORMAT', 'd.m.y');


/**
 * Минимален брой значещи десетични цифри по подразбиране
 */
defIfNot('EF_ROUND_SIGNIFICANT_DIGITS', '6');


/**
 * Минимален брой видими нули при подравняване
 */
defIfNot('CORE_MIN_ALIGN_DIGITS', 2);


/**
 * @todo Чака за документация...
 */
defIfNot('TYPE_KEY_MAX_SUGGESTIONS', 1000);


/**
 * Езикът по подразбиране е български
 */
defIfNot('EF_DEFAULT_LANGUAGE', 'bg');


/**
 * Максимален брой записи, които могат да се експортират на веднъж
 */
defIfNot('EF_MAX_EXPORT_CNT', 100000);


/**
 * Максимален брой символи, от които ще се генерират ключови думи
 */
defIfNot('PLG_SEACH_MAX_TEXT_LEN', 64000);


/**
 * Максималното отклоненение в таймстампа при логване в системата
 * 1 час и 30 мин.
 */
defIfNot('CORE_LOGIN_TIMESTAMP_DEVIATION', 5400);


/**
 * Брой логвания от един и същи потребител, за показване на ника по подразбиране
 */
defIfNot('CORE_SUCCESS_LOGIN_AUTOCOMPLETE', 3);


/**
 * Колко време назад да се търси в историята за логовете
 * 45 дни
 */
defIfNot('CORE_LOGIN_LOG_FETCH_DAYS_LIMIT', 3888000);



/**
 * Колко време назад да се търси в лога за first_login
 * 14 дни
 */
defIfNot('CORE_LOGIN_LOG_FIRST_LOGIN_DAYS_LIMIT', 1209600);


/**
 * Колко време да е живота на кукитата
 * 2 месеца
 */
defIfNot('CORE_COOKIE_LIFETIME', 5259492);


/**
 * Колко дълго да се пазят файловете в temp директорията
 * 10 дни
 */
defIfNot('CORE_TEMP_PATH_MAX_AGE', 864000);


/**
 * Разделител за хилядите при форматирането на числата
 */
defIfNot('EF_NUMBER_THOUSANDS_SEP', ' ');


/**
 * Дробен разделител при форматирането на числата
 */
defIfNot('EF_NUMBER_DEC_POINT', ',');


/**
 * Език на интерфейса след логване в системата
 */
defIfNot('EF_USER_LANG', '');


/**
 * HTML който се показва като информация във формата за логин
 */
defIfNot('CORE_LOGIN_INFO', "|*(|само за администраторите на сайта|*)");


/**
 * Опаковка по подразбиране за вътрешната страница
 */
defIfNot('CORE_PAGE_WRAPPER', 'core_page_InternalModern');



/**
 * Дали да може да се регистрират нови потребители от логин формата
 */
defIfNot('CORE_REGISTER_NEW_USER_FROM_LOGIN_FORM', 'no');



/**
 * Дали да може да се ресетват пароли от логин формата
 */
defIfNot('CORE_RESET_PASSWORD_FROM_LOGIN_FORM', 'yes');


/**
 * Ник на системния потребител
 */
defIfNot('CORE_SYSTEM_NICK', '@system');


/**
 * Потребителя, който ще се използва за първи администратор в системата
 */
defIfNot('CORE_FIRST_ADMIN', '');


/**
 * Свиване на секцията за споделяне
 */
defIfNot('CORE_AUTOHIDE_SHARED_USERS', 100);


defIfNot('CORE_PORTAL_ARRANGE', 'notifyTaskRecentlyCal');


/**
 * class 'core_Setup' - Начално установяване на пакета 'core'
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Setup extends core_ProtoSetup {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'core_Packs';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Администриране на системата";
    
    
    /**
     * Роли, които ще се добавят при инсталация
     */
    public $roles = 'translate';
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
               
           'EF_DATE_FORMAT'   => array ('enum(d.m.Y=|*22.11.1999, d-m-Y=|*22-11-1999, d/m/Y=|*22/11/1999, m.d.Y=|*11.22.1999, m-d-Y=|*11-22-1999, m/d/Y=|*11/22/1999, d.m.y=|*22.11.99, d-m-y=|*22-11-99, d/m/y=|*22/11/99, m.d.y=|*11.22.99, m-d-y=|*11-22-99, m/d/y=|*11/22/99)', 'caption=Формат по подразбиране за датата->Десктоп, customizeBy=user'),
           
           'EF_DATE_NARROW_FORMAT'   => array ('enum(d.m.y=|*22.11.99, d-m-y=|*22-11-99, d/m/y=|*22/11/99, m.d.y=|*11.22.99, m-d-y=|*11-22-99, m/d/y=|*11/22/99, d.m.Y=|*22.11.1999, d-m-Y=|*22-11-1999, d/m/Y=|*22/11/1999, m.d.Y=|*11.22.1999, m-d-Y=|*11-22-1999, m/d/Y=|*11/22/1999)', 'caption=Формат по подразбиране за датата->Мобилен, customizeBy=user'),
           
           'EF_DATE_USE_TIMEOFFSET'   => array ('enum(yes=Да, no=Не)', 'caption=Дали да се използва времевата зона на потребителя->Избор, customizeBy=user'),
            
           'EF_NUMBER_THOUSANDS_SEP' => array( 'enum(&#x20;=Интервал,\'=Апостроф,`=Обратен апостроф)', 'caption=Форматиране на числа->Разделител, customizeBy=user'),
            
           'EF_NUMBER_DEC_POINT' => array( 'enum(.=Точка,&#44;=Запетая)', 'caption=Форматиране на числа->Дробен знак, customizeBy=user'),
            
           'EF_USER_LANG' => array( "enum()", 'caption=Език на интерфейса след логване->Език, customizeBy=user, optionsFunc=core_Lg::getLangOptions'),
            
           'TYPE_KEY_MAX_SUGGESTIONS'   => array ('int', 'caption=Критичен брой опции|*&comma;| над които търсенето става по ajax->Опции'), 
    
           'EF_APP_TITLE'   => array ('varchar(16)', 'caption=Наименование на приложението->Име'),
            
           'CORE_SYSTEM_NICK'   => array ('varchar(16)', 'caption=Ник на системния потребител->Ник'),
            
           'CORE_FIRST_ADMIN'   => array ('user(roles=admin, rolesForTeams=admin, rolesForAll=admin, allowEmpty)', 'caption=Главен администратор на системата->Потребител'),
       
           'CORE_LOGIN_INFO'   => array ('varchar', 'caption=Информация във формата за логване->Текст'),
      
           'EF_MAX_EXPORT_CNT' => array ('int', 'caption=Възможен максимален брой записи при експорт->Брой записи'),
           
           'PLG_SEACH_MAX_TEXT_LEN' => array ('int', 'caption=Максимален брой символи за генериране на ключови думи->Брой символи'),
           
           'CORE_LOGIN_TIMESTAMP_DEVIATION' => array ('time(suggestions=30 мин|1 час|90 мин|2 часа)', 'caption=Максималното отклоненение в таймстампа при логване в системата->Време'),
           
           'CORE_SUCCESS_LOGIN_AUTOCOMPLETE' => array ('int', 'caption=Запомняне на потребителя при логване от един браузър->Брой логвания'),
           
           'CORE_LOGIN_LOG_FETCH_DAYS_LIMIT' => array ('time(suggestions=1 месец|45 дни|2 месеца|3 месеца)', 'caption=Колко време назад да се търси в лога->Време'),
           
           'CORE_LOGIN_LOG_FIRST_LOGIN_DAYS_LIMIT' => array ('time(suggestions=1 седмица|2 седмици|1 месец|2 месеца)', 'caption=Колко време назад да се търси в лога за first_login->Време'),
           
           'CORE_COOKIE_LIFETIME' => array ('time(suggestions=1 месец|2 месеца|3 месеца|1 година)', 'caption=Време на живот на кукитата->Време'),
           
           'CORE_TEMP_PATH_MAX_AGE' => array ('time(suggestions=3 ден|5 дни|10 дни|1 месец)', 'caption=Колко дълго да се пазят файловете в EF_TEMP_PATH директорията->Време'),
            
           'CORE_PAGE_WRAPPER' => array ('class(interface=core_page_WrapperIntf,select=title, allowEmpty)', 'caption=Вътрешен изглед->Страница, customizeBy=powerUser, placeholder=Автоматично'),

           'CORE_PORTAL_ARRANGE' => array ('enum(notifyTaskRecentlyCal=Известия - Задачи - Последно и Календар,notifyTaskCalRecently=Известия - Задачи - Календар и Последно,recentlyNotifyTaskCal=Последно - Известия - Задачи и Календар,taskNotifyRecentlyCal=Задачи - Известия - Последно и Календар)', 'caption=Вътрешен изглед->Портал, customizeBy=powerUser'),

           'CORE_REGISTER_NEW_USER_FROM_LOGIN_FORM' => array ('enum(yes=Да, no=Не)', 'caption=Дали да може да се регистрират нови потребители от логин формата->Избор'),
           
           'CORE_RESET_PASSWORD_FROM_LOGIN_FORM' => array ('enum(yes=Да, no=Не)', 'caption=Дали да може да се ресетват пароли от логин формата->Избор'),
        
           'CORE_MIN_ALIGN_DIGITS' => array('int', 'caption=Минимален брой видими нули при подравняване->Брой'),
           
           'CORE_AUTOHIDE_SHARED_USERS' => array ('int(min=0)', 'caption=Свиване на секцията за споделяне->При над,unit=потребителя'),
               
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        'core_Classes',
        'core_Interfaces',
        'core_Cache',
        'core_Plugins',
        'core_Packs',
        'core_Cron',
        'core_CallOnTime',
        'log_System',
        'core_Lg',
        'core_Roles',
        'core_Users',
        'core_Locks',
        'core_LoginLog',
        'migrate::loginLogTruncate',
        'core_Settings',
        'core_Forwards',
        'core_Updates',
    	'core_Permanent',
        'migrate::settigsDataFromCustomToCore',
        'migrate::movePersonalizationData',
        'migrate::repairUsersRolesInput',
        'migrate::removeFalseTranslate',
        'migrate::repairSearchKeywords'
    );
    
    
    /**
     * Дали пакета е системен
     */
    public $isSystem = TRUE;
    
    
    /**
     * Папки, които трябва да бъдат създадени
     */
    protected $folders = array(
            EF_SBF_PATH => 'за уеб ресурси', // sbf root за приложението
            EF_TEMP_PATH => 'за временни файлове', // временни файлове
            EF_UPLOADS_PATH => 'за качени файлове',// файлове на потребители
        );
    
    
    /**
     * Описание на системните действия
     */
    var $systemActions = array(
        array('title' => 'Миграции', 'url' => array ('core_Packs', 'InvalidateMigrations', 'ret_url' => TRUE), 'params' => array('title' => 'Преглед и инвалидиране на миграциите')),
        array('title' => 'Преводи', 'url' => array ('core_Lg', 'DeleteUsersTr', 'ret_url' => TRUE), 'params' => array('title' => 'Изтриване на преводите направени от различни потребители'))
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
        array(1.62, 'Система', 'Админ', 'core_Packs', 'default', 'admin'),
    );


    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html .= parent::install();
        
        if( CORE_OVERWRITE_HTAACCESS ) {
            $filesToCopy = array(
                EF_APP_PATH . '/_docs/tpl/htaccessSBF.txt' => EF_SBF_PATH . '/.htaccess',
                EF_APP_PATH . '/_docs/tpl/htaccessIND.txt' => EF_INDEX_PATH . '/.htaccess'
            );
            
            foreach($filesToCopy as $src => $dest) {
                $html .= self::addUniqLines($src, $dest);
            }
        }

        // Иконата
        $dest = EF_INDEX_PATH . '/favicon.ico';
        if(!file_exists($dest)) {
            $src = getFullPath('img/favicon.ico');
            if(copy($src, $dest)) {
                $html .= "<li class=\"green\">Копиран е файла: <b>{$src}</b> => <b>{$dest}</b></li>";
            } else {
                $html .= "<li class=\"red\">Не може да бъде копиран файла: <b>{$src}</b> => <b>{$dest}</b></li>";
            }
        }

        
        // Изтриване на старите файлове от sbf директорията
        $delCnt = core_Os::deleteOldFiles(EF_SBF_PATH, 2*30*24*60*60, "#^_[a-z0-9\-\/_]+#i");
        if($delCnt) {
            $html .= "<li class=\"green\">Изтрити са $delCnt файла в " . EF_SBF_PATH . "/</li>";
        }
        
        // Нагласяване на Крон да почиства кеша
        $rec = new stdClass();
        $rec->systemId = 'ClearCache';
        $rec->description = 'Почистване на обектите с изтекъл срок';
        $rec->controller = 'core_Cache';
        $rec->action = 'DeleteExpiredData';
        $rec->period = 24 * 60;
        $rec->offset = rand(60, 180); // от 1h до 3h
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $html .= core_Cron::addOnce($rec);

        // Нагласяване на Крон да почиства core_Forwards
        $rec = new stdClass();
        $rec->systemId = 'ClearForwards';
        $rec->description = 'Почистване на callback връзките с изтекъл срок';
        $rec->controller = 'core_Forwards';
        $rec->action = 'DeleteExpiredLinks';
        $rec->period = 60;
        $rec->offset = mt_rand(0,40);
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $html .= core_Cron::addOnce($rec);
        
        
        // Нагласяване на Крон да се проверява за нови версии
        $rec = new stdClass();
        $rec->systemId = 'CheckForCodeUpdates';
        $rec->description = 'Проверка за нови версии';
        $rec->controller = 'core_Updates';
        $rec->action = 'checkForUpdates';
        $rec->period = 24*60;
        $rec->offset = mt_rand(8*60, 12*60);
        $rec->delay = 0;
        $rec->timeLimit = 300;
        $html .= core_Cron::addOnce($rec);
        
        // Нагласяване на Крон да почиства кеша
        $rec = new stdClass();
        $rec->systemId = 'ClearPermCache';
        $rec->description = 'Почистване на постоянния кеш';
        $rec->controller = 'core_Permanent';
        $rec->action = 'DeleteExpiredPermData';
        $rec->period = 24 * 60;
        $rec->offset = rand(60, 180); // от 1h до 3h
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $html .= core_Cron::addOnce($rec);
        
        $html .= core_Classes::add('core_page_Internal');        
        $html .= core_Classes::add('core_page_InternalModern');


        $html .= core_Classes::rebuild();
		
        $html .= core_Cron::cleanRecords();
        
        $html .= static::addCronToDelOldTempFiles();
        
        return $html;
    }
    
    
    /**
     * Добавя в крон таблицата, функция за изтриване на старите временни файлове
     * 
     * @return string
     */
    static function addCronToDelOldTempFiles()
    {
        // Нагласяване на Крон
        $rec = new stdClass();
        $rec->systemId = 'clearOldTempFiles';
        $rec->description = 'Изтриване на старите временни файлове';
        $rec->controller = 'core_Os';
        $rec->action = 'clearOldFiles';
        $rec->period = 60;
        $rec->offset = mt_rand(0,40);
        $rec->delay = 0;
        $rec->timeLimit = 120;
        $res .= core_Cron::addOnce($rec);

        return $res;
    }
    
    
    /**
     * Миграция, която изтрива съдържанието на таблицата core_LoginLog
     */
    function loginLogTruncate()
    {
        $loginLog = cls::get('core_LoginLog');
        $loginLog->db->query("TRUNCATE TABLE `{$loginLog->dbTableName}`");
    }
    
    
    /**
     * Миграция за прехвъраляне на данните от `custom_Settings` в `core_Settings`
     */
    static function settigsDataFromCustomToCore()
    {
        if (!cls::load('custom_Settings', TRUE)) return ;
        
        $inst = cls::get('custom_Settings');
        
        if (!$inst->db->tableExists($inst->dbTableName)) return ;
        
        $dataArr = array();
        
        // Взема всички записи и общите ги обядинява в един
        $cQuery = custom_Settings::getQuery();
        while ($cRec = $cQuery->fetch()) {
            if (!cls::load($cRec->classId, TRUE)) continue;
            $classInst = cls::get($cRec->classId);
            if (!method_exists($classInst, 'getSettingsKey')) continue;
            
            $key = $classInst->getSettingsKey($cRec->objectId);
            
            $userId = $cRec->userId;
            if ($userId == -1) {
                $userId = type_UserOrRole::getAllSysTeamId();
            }
            
            $dataArr[$key][$userId][$cRec->property] = $cRec->value;
        }
        
        // Обикаля по получения резултат и добавя в новия модел
        foreach ((array)$dataArr as $key => $dataUserArr) {
            foreach ((array)$dataUserArr as $userId => $valArr) {
                if (!$valArr) continue;
                core_Settings::setValues($key, $valArr, $userId);
            }
        }
    }
    
    
    /**
     * Фунцкия за миграция
     * Премества персонализационните данни за потребителя от core_Users в core_Settings
     */
    static function movePersonalizationData()
    {
        $userInst = cls::get('core_Users');
        
        $userInst->db->connect();
        
        $confData = str::phpToMysqlName('configData');
        
        // Ако в модела в MySQL липсва колоната, няма нужда от миграция
        if (!$userInst->db->isFieldExists($userInst->dbTableName, $confData)) return ;
        
        $userInst->FLD('configData', 'blob(serialize,compress)', 'caption=Конфигурационни данни,input=none');
        
        // Преместваме всикчи данни от полето в core_Settings
        $userQuery = core_Users::getQuery();
        $userQuery->where("#configData IS NOT NULL");
        while ($rec = $userQuery->fetch()) {
            $key = core_Users::getSettingsKey($rec->id);
            
            core_Settings::setValues($key, $rec->configData, $rec->id);
        }
    }
    
    
    /**
     * Поправя потребителите с празни rolesInput
     */
    static function repairUsersRolesInput()
    {
        $query = core_Users::getQuery();
        $query->where("#rolesInput IS NULL");
        $query->orWhere("#rolesInput = ''");
        $query->orWhere("#rolesInput = '|'");
        
        while ($rec = $query->fetch()) {
            $rec->rolesInput = $rec->roles;
            
            core_Users::save($rec, 'rolesInput');
        }
    }
    
    
    /**
     * Премахва ненужните преводи, добавени по погрешка
     */
    static function removeFalseTranslate()
    {
        $query = core_Lg::getQuery();
        $query->where("1=1");
        
        $deleteArr = array();
        
        // Ако намери стрингкове, които не са преведени, ги премахваме от модела
        while ($rec = $query->fetch()) {
            $translated = str_ireplace(array("\n\r", "\r\n", "\n", "\r"), '<br />', $rec->translated);
        
            $translated = core_Lg::prepareKey($translated);
            
            if ($translated == $rec->kstring) {
                $deleteArr[$rec->id] = $rec->id;
            }
        }
        
        if (!empty($deleteArr)) {
            $in = implode(', ', $deleteArr);
            $delCnt = core_Lg::delete("#id IN ({$in})");
            
            core_Lg::logNotice("Изтрити {$delCnt} брой ненужни записи");
        }
    }
    

    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonJs()
    {
        $conf = core_Packs::getConfig('core');

        $intTheme = cls::get($conf->CORE_PAGE_WRAPPER);
        
        if (method_exists($intTheme, 'getCommonJs')) {
            $res = $intTheme->getCommonJs();
        } else {
            $res = '';
        }
        
        return $res;
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonCss()
    {
        return $res;
    }
    
    
    /**
     * Премахва всички * от полетата за търсене
     */
    public static function repairSearchKeywords()
    {
        // Вземаме инстанция на core_Interfaces
        $Interfaces = cls::get('core_Interfaces');
    
        // id' то на интерфейса
        $interfaceId = $Interfaces->fetchByName('core_ManagerIntf');
        
        $query = core_Classes::getQuery();
        $query->where("#state = 'active' AND #interfaces LIKE '%|{$interfaceId}|%'");
        
        while ($rec = $query->fetch()) {
            
            if (!cls::load($rec->name, TRUE)) continue;
            
            $Inst = cls::get($rec->name);
            
            // Ако няма таблица
            if (!$Inst || !$Inst->db) continue;
            
            // Ако таблицата не съществува в модела
            if (!$Inst->db->tableExists($Inst->dbTableName)) continue ;
            
            // Ако полето не съществува в таблицата
            $sk = str::phpToMysqlName('searchKeywords');
            if (!$Inst->db->isFieldExists($Inst->dbTableName, $sk)) continue ;
            
            $plugins = arr::make($Inst->loadList, TRUE);
            
            if (!isset($plugins['plg_Search']) && !$Inst->fields['searchKeywords']) continue;
            
            $searchField = str::phpToMysqlName('searchKeywords');
            
            $Inst->db->query("UPDATE {$Inst->dbTableName} SET {$searchField} = REPLACE({$searchField}, '*', '')");
        }
    }


    /**
     * Копира линиите от файла $src в $dest, които не се съдържат в него
     */
    public static function addUniqLines($src, $dest)
    {
        $emptyDest = FALSE;
        if(!file_exists($dest)) {
            if(file_put_contents($dest, '') === FALSE) {
                return "<li class=\"debug-error\">Не може да бъде създаден файла: <b>{$dest}</b></li>";
            }
            $emptyDest = TRUE;
        }
        if(!is_writable($dest)) {
            return "<li class=\"debug-error\">Не може да се записва във файла: <b>{$dest}</b></li>";
        }

        if(!is_readable($src)) {
            return "<li class=\"debug-error\">Не може да бъде прочетен файла: <b>{$src}</b></li>";
        }
        
        if(!is_readable($dest)) {
            return "<li class=\"debug-error\">Не може да бъде прочетен файла: <b>{$dest}</b></li>";
        }
     
        $exFile = file_get_contents($dest);

        $lines = file_get_contents($src);

        $lines = explode("\n", $lines);
        
        $flagChange = FALSE;
        $newLines = 0;
        foreach($lines as $l) {
            $l = rtrim($l);
            if((strlen($l) == 0 && $flagChange) || (strlen($l) > 0 && stripos($exFile, $l) === FALSE)) {
                file_put_contents($dest, ($emptyDest ? '' : "\n") . $l, FILE_APPEND);
                $flagChange = TRUE;
                $emptyDest  = FALSE;
                $newLines++;
            }
        }

        if($newLines > 0) {
            $res = "<li class=\"debug-new\">Във файла <b>{$dest}</b> са копирани {$newLines} линии от файла <b>{$src}</b></li>";
        } else {
            $res = "<li class=\"debug-info\">Във файла <b>{$dest}</b> не са копирани линии от файла <b>{$src}</b></li>";
        }

        return $res;
    }
}
