<?php


/**
 * Вербално заглавие на приложението
 */
defIfNot('EF_APP_TITLE', 'Application Title');


/**
 * Вербално заглавие на приложението
 */
defIfNot('EF_BGERP_LINK_TITLE', 'За bgERP||About');


/**
 * Дали да се презаписват .htaccess файловете?
 * Може да се зададе друга стойност в конфигурационния файл (напр. conf/bgerp.cfg.php)
 */
defIfNot('CORE_OVERWRITE_HTAACCESS', true);


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
 * @todo Чака за документация...
 */
defIfNot('TYPE_KEY_MAX_SUGGESTIONS', 1000);


/**
 * Пределен брой опции, за авто-отваряне групите на чек-лист
 */
defIfNot('CORE_MAX_OPT_FOR_OPEN_GROUPS', 30);


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
 * Максималното отклонение в таймстампа при логване в системата
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
 *
 * 30 дни
 */
defIfNot('CORE_STOP_BLOCKING_LOGIN_PERIOD', 2592000);


/**
 * Колко време назад да се търси в лога за first_login
 */
defIfNot('CORE_STOP_BLOCKING_LOGIN_COUNT', 10);


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
defIfNot('EF_NUMBER_THOUSANDS_SEP', '&#x20;');


/**
 * Дробен разделител при форматирането на числата
 */
defIfNot('EF_NUMBER_DEC_POINT', '&#44;');


/**
 * Език на интерфейса след логване в системата
 */
defIfNot('EF_USER_LANG', '');


/**
 * HTML който се показва като информация във формата за логин
 */
defIfNot('CORE_LOGIN_INFO', '|*(|само за администраторите на сайта|*)');


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
 * Име на системния потребител
 */
defIfNot('CORE_SYSTEM_NAME', 'Системата');


/**
 * Потребителя, който ще се използва за първи администратор в системата
 */
defIfNot('CORE_FIRST_ADMIN', '');


/**
 * Свиване на секцията за споделяне
 */
defIfNot('CORE_AUTOHIDE_SHARED_USERS', 100);


/**
 * Максимален брой редове при печат
 */
defIfNot('CORE_MAX_ROWS_FOR_PRINTING', 1000);


/**
 * Забрана за записване на паролата
 */
defIfNot('CORE_ALLOW_PASS_SAVE', 'yes');


/**
 * Версия на кода, към която са актуални данните в базата
 * По дефолт, стойността е равна на версия "Ореляк" - последната,
 * която носи всички миграции. Тази константа не трябва да се
 * променя при по-нови версии
 */
define('CORE_LAST_DB_VERSION', '18.25-Shabran');


/**
 * Версия на кода която работи в момента
 * Тази константа не трябва да се ползва с core_Setup::getConfig(),
 * а само с: core_setup::CURRENT_VERSION
 */
define('CORE_CODE_VERSION', '21.45-Dzhano');


/**
 * Включена ли е бекъп функционалността?
 */
defIfNot('CORE_BACKUP_ENABLED', 'no');


/**
 * Включена ли е бекъп функционалността?
 */
defIfNot('CORE_BACKUP_MAX_CNT', 2);


/**
 * Парола за архиви
 */
defIfNot('CORE_BACKUP_PASS', '');


/**
 * Колко минути е периода за флъшване на SQL лога
 */
defIfNot('CORE_BACKUP_SQL_LOG_FLUSH_PERIOD', 60 * 60);


/**
 * Колко колко минути е периода за пълен бекъп?
 */
defIfNot('CORE_BACKUP_CREATE_FULL_PERIOD', (60 * 24) * 60);


/**
 * В колко минути след периода да започва пълният бекъп?
 */
defIfNot('CORE_BACKUP_CREATE_FULL_OFFSET', (60 * 3 + 50) * 60);


/**
 * 
 */
defIfNot('CORE_BGERP_UNIQ_ID', '');


/**
 * class 'core_Setup' - Начално установяване на пакета 'core'
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Setup extends core_ProtoSetup
{
    /**
     * Последна стабилна версия на цялата система
     */
    const CURRENT_VERSION = CORE_CODE_VERSION;
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'core_Packs';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Администриране на системата';
    
    
    /**
     * Роли, които ще се добавят при инсталация
     */
    public $roles = 'translate';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'CORE_LAST_DB_VERSION' => array('varchar(32)', 'caption=Версия на системата->База данни,readOnly'),
        
        'CORE_CODE_VERSION' => array('varchar(32)', 'caption=Версия на системата->Код,readOnly'),
        
        'EF_DATE_FORMAT' => array('enum(d.m.Y=|*22.11.1999, d-m-Y=|*22-11-1999, d/m/Y=|*22/11/1999, m.d.Y=|*11.22.1999, m-d-Y=|*11-22-1999, m/d/Y=|*11/22/1999, d.m.y=|*22.11.99, d-m-y=|*22-11-99, d/m/y=|*22/11/99, m.d.y=|*11.22.99, m-d-y=|*11-22-99, m/d/y=|*11/22/99)', 'caption=Формат по подразбиране за датата->Десктоп, customizeBy=user'),
        
        'EF_DATE_NARROW_FORMAT' => array('enum(d.m.y=|*22.11.99, d-m-y=|*22-11-99, d/m/y=|*22/11/99, m.d.y=|*11.22.99, m-d-y=|*11-22-99, m/d/y=|*11/22/99, d.m.Y=|*22.11.1999, d-m-Y=|*22-11-1999, d/m/Y=|*22/11/1999, m.d.Y=|*11.22.1999, m-d-Y=|*11-22-1999, m/d/Y=|*11/22/1999)', 'caption=Формат по подразбиране за датата->Мобилен, customizeBy=user'),
        
        'EF_DATE_USE_TIMEOFFSET' => array('enum(yes=Да, no=Не)', 'caption=Дали да се използва времевата зона на потребителя->Избор, customizeBy=user'),
        
        'EF_NUMBER_THOUSANDS_SEP' => array('enum(&#x20;=Интервал,\'=Апостроф,`=Обратен апостроф)', 'caption=Форматиране на числа->Разделител, customizeBy=user'),
        
        'EF_NUMBER_DEC_POINT' => array('enum(.=Точка,&#44;=Запетая)', 'caption=Форматиране на числа->Дробен знак, customizeBy=user'),
        
        'EF_USER_LANG' => array('enum()', 'caption=Език на интерфейса след логване->Език, customizeBy=user, optionsFunc=core_Lg::getLangOptions'),
        
        'TYPE_KEY_MAX_SUGGESTIONS' => array('int', 'caption=Критичен брой опции|*&comma;| над които търсенето става по ajax->Опции'),
        
        'CORE_MAX_OPT_FOR_OPEN_GROUPS' => array('int', 'caption=Критичен брой опции|*&comma;| под който се отварят групите->Опции'),
        
        'CORE_AUTOHIDE_SHARED_USERS' => array('int(min=0)', 'caption=Свиване на секцията за споделяне->При над,unit=потребителя'),
        
        'EF_APP_TITLE' => array('varchar(16)', 'caption=Наименование на приложението->Име'),
        
        'CORE_SYSTEM_NICK' => array('varchar(16)', 'caption=Системен потребител->Ник'),
        
        'CORE_SYSTEM_NAME' => array('varchar(16)', 'caption=Системен потребител->Име'),
        
        'CORE_FIRST_ADMIN' => array('user(roles=admin, rolesForTeams=admin, rolesForAll=admin, allowEmpty)', 'caption=Главен администратор на системата->Потребител'),
        
        'CORE_LOGIN_INFO' => array('varchar', 'caption=Информация във формата за логване->Текст'),
        
        'EF_MAX_EXPORT_CNT' => array('int', 'caption=Възможен максимален брой записи при експорт->Брой записи'),
        
        'CORE_MAX_ROWS_FOR_PRINTING' => array('int', 'caption=Размер на страницата при печат->Брой редове'),
        
        'PLG_SEACH_MAX_TEXT_LEN' => array('int', 'caption=Максимален брой символи за генериране на ключови думи->Брой символи'),
        
        'CORE_LOGIN_TIMESTAMP_DEVIATION' => array('time(suggestions=30 мин|1 час|90 мин|2 часа)', 'caption=Максималното отклонение в таймстампа при логване в системата->Време'),
        
        'CORE_SUCCESS_LOGIN_AUTOCOMPLETE' => array('int', 'caption=Запомняне на потребителя при логване от един браузър->Брой логвания'),
        
        'CORE_ALLOW_PASS_SAVE' => array('enum(yes=Да,no=Не)', 'caption=Запомняне в браузъра на паролата за логин->Разрешено'),
        
        'CORE_LOGIN_LOG_FETCH_DAYS_LIMIT' => array('time(suggestions=1 месец|45 дни|2 месеца|3 месеца)', 'caption=Колко време назад да се търси в лога->Време'),
        
        'CORE_LOGIN_LOG_FIRST_LOGIN_DAYS_LIMIT' => array('time(suggestions=1 седмица|2 седмици|1 месец|2 месеца)', 'caption=Колко време назад да се търси в лога за first_login->Време'),
        
        'CORE_STOP_BLOCKING_LOGIN_PERIOD' => array('time(suggestions=1 седмица|2 седмици|1 месец|2 месеца)', 'caption=Спиране на блокирането|*&#44; |ако има дублиране от различни устройства->Време'),
        
        'CORE_STOP_BLOCKING_LOGIN_COUNT' => array('int', 'caption=Спиране на блокирането|*&#44; |ако има дублиране от различни устройства->Брой'),
        
        'CORE_COOKIE_LIFETIME' => array('time(suggestions=1 месец|2 месеца|3 месеца|1 година)', 'caption=Време на живот на кукитата->Време'),
        
        'CORE_TEMP_PATH_MAX_AGE' => array('time(suggestions=3 ден|5 дни|10 дни|1 месец)', 'caption=Колко дълго да се пазят файловете в EF_TEMP_PATH директорията->Време'),
        
        'CORE_PAGE_WRAPPER' => array('class(interface=core_page_WrapperIntf,select=title, allowEmpty)', 'caption=Вътрешен изглед->Страница, customizeBy=powerUser, placeholder=Автоматично'),
        
        'CORE_REGISTER_NEW_USER_FROM_LOGIN_FORM' => array('enum(yes=Да, no=Не)', 'caption=Дали да може да се регистрират нови потребители от логин формата->Избор'),
        
        'CORE_RESET_PASSWORD_FROM_LOGIN_FORM' => array('enum(yes=Да, no=Не)', 'caption=Дали да може да се ресетват пароли от логин формата->Избор'),
        
        'CORE_BACKUP_ENABLED' => array('enum(no=Не, yes=Да)', 'caption=Настройки за бекъп->Включен бекъп'),
        
        'CORE_BACKUP_MAX_CNT' => array('enum(1=1,2=2,3=3,4=4,5=5,6=6,7=7)', 'caption=Настройки за бекъп->Макс. брой'),
        
        'CORE_BACKUP_PASS' => array('password(show)', 'caption=Настройки за бекъп->Ключ за криптиране'),
        
        'CORE_BACKUP_SQL_LOG_FLUSH_PERIOD' => array('time', 'caption=Настройки за бекъп->Запис на SQL лог през'),
        
        'CORE_BACKUP_CREATE_FULL_PERIOD' => array('time', 'caption=Настройки за бекъп->Пълен бекъп през'),
        
        'CORE_BACKUP_CREATE_FULL_OFFSET' => array('time', 'caption=Настройки за бекъп->Изместване'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'core_Classes',
        'core_Interfaces',
        'core_Cache',
        'core_Plugins',
        'core_Packs',
        'core_Cron',
        'core_CallOnTime',
        'log_System',
        'core_Lg',
        'core_UserTranslates',
        'core_Roles',
        'core_RoleLogs',
        'core_Users',
        'core_Locks',
        'core_LoginLog',
        'core_Settings',
        'core_Forwards',
        'core_Updates',
        'core_Permanent',
        'migrate::clearCallOnTimeBadData2212',
        'migrate::repairSearchKeywords31920',
        'migrate::setBGERPUNIQId3020'
    );
    
    
    /**
     * Дали пакета е системен
     */
    public $isSystem = true;
    
    
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
    public $systemActions = array(
        array('title' => 'Миграции', 'url' => array('core_Packs', 'InvalidateMigrations', 'ret_url' => true), 'params' => array('title' => 'Преглед и инвалидиране на миграциите')),
        array('title' => 'Преводи', 'url' => array('core_Lg', 'DeleteUsersTr', 'ret_url' => true), 'params' => array('title' => 'Изтриване на преводите направени от различни потребители'))
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.62, 'Система', 'Админ', 'core_Packs', 'default', 'admin'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        // Спираме SQL лога, ако има такъв
        core_Db::$sqlLogEnebled = false;
        
        $html .= parent::install();
        
        if (CORE_OVERWRITE_HTAACCESS) {
            $filesToCopy = array(
                EF_APP_PATH . '/_docs/tpl/htaccessSBF.txt' => EF_SBF_PATH . '/.htaccess',
                EF_APP_PATH . '/_docs/tpl/htaccessIND.txt' => EF_INDEX_PATH . '/.htaccess'
            );
            
            foreach ($filesToCopy as $src => $dest) {
                $html .= self::addUniqLines($src, $dest);
            }
        }
        
        // Изтриване на старите файлове от sbf директорията
        $delCnt = core_Os::deleteOldFiles(EF_SBF_PATH, 2 * 30 * 24 * 60 * 60, "#^_[a-z0-9\-\/_]+#i");
        if ($delCnt) {
            $html .= "<li class=\"green\">Изтрити са ${delCnt} файла в " . EF_SBF_PATH . '/</li>';
        }
        
        // Нагласяване на Крон да почиства кеша
        $rec = new stdClass();
        $rec->systemId = 'ClearCache';
        $rec->description = 'Почистване на обектите с изтекъл срок';
        $rec->controller = 'core_Cache';
        $rec->action = 'DeleteExpiredData';
        $rec->period = 24 * 60;
        $rec->offset = rand(60, 180); // от 1h до 3h
        $rec->isRandOffset = true;
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
        $rec->offset = mt_rand(0, 40);
        $rec->isRandOffset = true;
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $html .= core_Cron::addOnce($rec);
        
        // Нагласяване на Крон да се проверява за нови версии
        $rec = new stdClass();
        $rec->systemId = 'CheckForCodeUpdates';
        $rec->description = 'Проверка за нови версии';
        $rec->controller = 'core_Updates';
        $rec->action = 'checkForUpdates';
        $rec->period = 24 * 60;
        $rec->offset = mt_rand(8 * 60, 12 * 60);
        $rec->isRandOffset = true;
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
        $rec->isRandOffset = true;
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $html .= core_Cron::addOnce($rec);
        
        if (core_Setup::get('BACKUP_ENABLED') == 'yes') {
            
            // Създаваме директориите
            $tempDir = core_Backup::getTempPath();
            core_Os::forceDir($tempDir, 0744);
            $backupDir = core_Backup::getBackupPath();
            core_Os::forceDir($backupDir, 0744);

            // Нагласяване Крон да прави пълен бекъп
            $rec = new stdClass();
            $rec->systemId = 'Backup_Create';
            $rec->description = 'Създаване на бекъп';
            $rec->controller = 'core_Backup';
            $rec->action = 'Create';
            $rec->period = round(core_Setup::get('BACKUP_CREATE_FULL_PERIOD') / 60);
            $rec->offset = round(core_Setup::get('BACKUP_CREATE_FULL_OFFSET') / 60);
            $rec->delay = 20;
            $rec->timeLimit = 1800;
            $html .= core_Cron::addOnce($rec);
            
            // Нагласяване Крон да се флъшва sql лога
            $rec = new stdClass();
            $rec->systemId = 'Sql_Log_Flush';
            $rec->description = 'Флъшване на SQL лога';
            $rec->controller = 'core_Backup';
            $rec->action = 'FlushSqlLog';
            $rec->period = round(core_Setup::get('BACKUP_SQL_LOG_FLUSH_PERIOD') / 60);
            $rec->offset = 0;
            $rec->delay = 2;
            $rec->timeLimit = 20;
            $html .= core_Cron::addOnce($rec);
            core_SystemData::set('flagDoSqlLog');
        } else {
            core_Cron::delete("#systemId = 'Backup_Create'");
            core_Cron::delete("#systemId = 'Sql_Log_Flush'");
            core_SystemData::remove('flagDoSqlLog');
        }
        
        
        // Регистрираме класовете, които не може да се регистрират автоматично
        $html .= core_Classes::add('core_Classes');
        $html .= core_Classes::add('core_Interfaces');
        
        $html .= core_Classes::add('core_page_Internal');
        $html .= core_Classes::add('core_page_InternalModern');
        
        $html .= static::addCronToDelOldTempFiles();
        
        try {
            $this->setBGERPUniqId();
        } catch (Exception $e) {
            reportException($e);
        } catch (Throwable $t) {
            reportException($t);
        }
        
        return $html;
    }
    
    
    /**
     * Добавя в крон таблицата, функция за изтриване на старите временни файлове
     *
     * @return string
     */
    public static function addCronToDelOldTempFiles()
    {
        // Нагласяване на Крон
        $rec = new stdClass();
        $rec->systemId = 'clearOldTempFiles';
        $rec->description = 'Изтриване на старите временни файлове';
        $rec->controller = 'core_Os';
        $rec->action = 'clearOldFiles';
        $rec->period = 60;
        $rec->offset = mt_rand(0, 40);
        $rec->isRandOffset = true;
        $rec->delay = 0;
        $rec->timeLimit = 120;
        $res .= core_Cron::addOnce($rec);
        
        return $res;
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
     * Поверява дали конфига е добре настроен
     */
    public function checkConfig()
    {
        return core_Backup::checkConfig();
    }
    
    
    /**
     * Копира линиите от файла $src в $dest, които не се съдържат в него
     */
    public static function addUniqLines($src, $dest)
    {
        $emptyDest = false;
        if (!file_exists($dest)) {
            if (file_put_contents($dest, '') === false) {
                
                return "<li class=\"debug-error\">Не може да бъде създаден файла: <b>{$dest}</b></li>";
            }
            $emptyDest = true;
        }
        if (!is_writable($dest)) {
            
            return "<li class=\"debug-error\">Не може да се записва във файла: <b>{$dest}</b></li>";
        }
        
        if (!is_readable($src)) {
            
            return "<li class=\"debug-error\">Не може да бъде прочетен файла: <b>{$src}</b></li>";
        }
        
        if (!is_readable($dest)) {
            
            return "<li class=\"debug-error\">Не може да бъде прочетен файла: <b>{$dest}</b></li>";
        }
        
        $exFile = file_get_contents($dest);
        
        $lines = file_get_contents($src);
        
        $lines = explode("\n", $lines);
        
        $flagChange = false;
        $newLines = 0;
        foreach ($lines as $l) {
            $l = rtrim($l);
            if ((strlen($l) == 0 && $flagChange) || (strlen($l) > 0 && stripos($exFile, $l) === false)) {
                file_put_contents($dest, ($emptyDest ? '' : "\n") . $l, FILE_APPEND);
                $flagChange = true;
                $emptyDest = false;
                $newLines++;
            }
        }
        
        if ($newLines > 0) {
            $res = "<li class=\"debug-new\">Във файла <b>{$dest}</b> са копирани {$newLines} линии от файла <b>{$src}</b></li>";
        } else {
            $res = "<li class=\"debug-info\">Във файла <b>{$dest}</b> не са копирани линии от файла <b>{$src}</b></li>";
        }
        
        return $res;
    }
    
    
    /**
     * Форсира регенерирането на ключовите думи за всички мениджъри, които използват `plg_Search`
     */
    public static function repairSearchKeywords31920()
    {
        // Вземаме инстанция на core_Interfaces
        $Interfaces = cls::get('core_Interfaces');
        
        // id' то на интерфейса
        $interfaceId = $Interfaces->fetchByName('core_ManagerIntf');
        
        $query = core_Classes::getQuery();
        $query->where("#state = 'active' AND #interfaces LIKE '%|{$interfaceId}|%'");
        
        $secs = 180;
        
        while ($rec = $query->fetch()) {
            if (!cls::load($rec->name, true)) {
                continue;
            }
            
            $Inst = cls::get($rec->name);
            
            // Ако няма таблица
            if (!$Inst || !$Inst->db) {
                continue;
            }
            
            // Ако таблицата не съществува в модела
            if (!$Inst->db->tableExists($Inst->dbTableName)) {
                continue ;
            }
            
            // Ако полето не съществува в таблицата
            $sk = str::phpToMysqlName('searchKeywords');
            if (!$Inst->db->isFieldExists($Inst->dbTableName, $sk)) {
                continue ;
            }
            
            $plugins = arr::make($Inst->loadList, true);
            
            if (!isset($plugins['plg_Search']) && !$Inst->fields['searchKeywords']) {
                continue;
            }
            
            core_CallOnTime::setCall('plg_Search', 'repairSerchKeywords', $rec->name, dt::addSecs($secs));
            
            $secs += 60;
        }
    }
    
    
    /**
     * Връща уникалното ID на системата
     *
     * @return string
     */
    public static function getBGERPUniqId()
    {
        
        return core_Setup::get('BGERP_UNIQ_ID');
    }
    
    
    /**
     * Задаване на уникално ID на системата
     *
     * @param boolean $force
     *
     * @return string
     */
    protected static function setBGERPUniqId($force = false)
    {
        $id = '';
        if (!$force) {
            $id = self::getBGERPUniqId();
        }
        
        if (!$id) {
            $id = self::generateBGERPUniqId();
            
            core_Packs::setConfig('core', array('CORE_BGERP_UNIQ_ID' => $id));
        }
        
        return $id;
    }
    
    
    /**
     * Връща 19 цифрено уникалното id на системата за тази инсталация
     *
     * @return string
     */
    protected static function generateBGERPUniqId()
    {
        $res = '';
        
        $fm = filectime(getFullPath('core'));
        $t = date('md', $fm);
        $y = date('y', $fm);
        $res = str_pad(($y % 10) . $t, 5, 0, STR_PAD_LEFT);
        
        $u = substr(crc32(php_uname('s')), 0, 3);
        $res .= str_pad($u, 3, 0, STR_PAD_LEFT);
        
        $m = substr(crc32(exec("ifconfig -a | grep -Po 'HWaddr \K.*$'")), 0, 2);
        $res .= str_pad($m, 2, 0, STR_PAD_LEFT);
        
        $s = substr(crc32(EF_SALT . "SystemID"), 0, 2);
        $res .= str_pad($s, 2, 0, STR_PAD_LEFT);
        
        $res .= str::getRand('##');
        
        $resCrc = substr(crc32($res), 0, 2);
        $res .= str_pad($resCrc, 2, 0, STR_PAD_LEFT);
        
        $res = str_pad($res, 16, 0, STR_PAD_LEFT);
        
        $res = substr($res, 0, 16);
        
        $res = implode('-', str_split($res, 4));
        
        return $res;
    }
    
    
    /**
     * Миграция за добавянер на уникален номер на системата
     */
    function setBGERPUNIQId3020()
    {
        $this->setBGERPUniqId(true);
    }


    /**
     * Изчиства лошите данни от core_CallOnTime
     */
    function clearCallOnTimeBadData2212()
    {
        core_CallOnTime::delete(array("#callOn < '[#1#]' AND #state = 'pending' AND #methodName = 'repairSerchKeywords'", dt::subtractSecs(30 * 86400)));
    }
}
