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
defIfNot('EF_DATE_FORMAT', 'd.m.YEAR');


/**
 * Формат по подразбиране за датата при тесни екрани
 */
defIfNot('EF_DATE_NARROW_FORMAT', 'd.m.year');


/**
 * Минимален брой значещи десетични цифри по подразбиране
 */
defIfNot('EF_ROUND_SIGNIFICANT_DIGITS', '6');


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
defIfNot('EF_NUMBER_DEC_POINT', '.');


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
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
               
           'EF_DATE_FORMAT'   => array ('varchar', 'caption=Формат по подразбиране за датата при широки екрани->Формат'),
    
           'EF_DATE_NARROW_FORMAT'   => array ('varchar', 'caption=Формат по подразбиране за датата при мобилни екрани->Формат'),
            
           'EF_NUMBER_THOUSANDS_SEP' => array( 'enum(&#x20;=|*&#x20;,\'=\',`=`)', 'caption=Форматиране на числа->Разделител за хилядите'),

           'EF_NUMBER_DEC_POINT' => array( 'enum(.=|*.,&#44;=|*&#44;)', 'caption=Форматиране на числа->Дробен разделител'),
         
           'TYPE_KEY_MAX_SUGGESTIONS'   => array ('int', 'caption=Критичен брой опции|*&comma;| над които търсенето става по ajax->Опции'), 
    
           'EF_APP_TITLE'   => array ('varchar', 'caption=Наименование на приложението->Име'),
           
           'EF_MAX_EXPORT_CNT' => array ('int', 'caption=Възможен максимален брой записи при експорт->Брой записи'),
           
           'PLG_SEACH_MAX_TEXT_LEN' => array ('int', 'caption=Максимален брой символи за генериране на ключови думи->Брой символи'),
           
           'CORE_LOGIN_TIMESTAMP_DEVIATION' => array ('time(suggestions=30 мин|1 час|90 мин|2 часа)', 'caption=Максималното отклоненение в таймстампа при логване в системата->Време'),
           
           'CORE_SUCCESS_LOGIN_AUTOCOMPLETE' => array ('int', 'caption=Запомняне на потребителя при логване от един браузър->Брой логвания'),
           
           'CORE_LOGIN_LOG_FETCH_DAYS_LIMIT' => array ('time(suggestions=1 месец|45 дни|2 месеца|3 месеца)', 'caption=Колко време назад да се търси в лога->Време'),
           
           'CORE_LOGIN_LOG_FIRST_LOGIN_DAYS_LIMIT' => array ('time(suggestions=1 седмица|2 седмици|1 месец|2 месеца)', 'caption=Колко време назад да се търси в лога за first_login->Време'),
           
           'CORE_COOKIE_LIFETIME' => array ('time(suggestions=1 месец|2 месеца|3 месеца|1 година)', 'caption=Време на живот на кукитата->Време'),
           
           'CORE_TEMP_PATH_MAX_AGE' => array ('time(suggestions=3 ден|5 дни|10 дни|1 месец)', 'caption=Колко дълго да се пазят файловете в EF_TEMP_PATH директорията->Време'),
        );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        'core_LoginLog',
        'migrate::loginLogTruncate',
        'core_Browser',
        'migrate::clearBrowserInfo',
    );
    
    
    /**
     * Път до js файла
     */
    var $commonJS = '';
    

    /**
     * Път до css файла
     */
    var $commonCSS = '';
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html .= parent::install();
        
        // Установяване за първи път
        
        // Правим това, защото процедурата по начално установяване
        // може да се задейства още от конструктора на core_Plugins
        global $PluginsGlobal;
        
        if($PluginsGlobal) {
            $Plugins = $PluginsGlobal;
        } else {
            $Plugins = cls::get('core_Plugins');
        }
        
        $Classes = cls::get('core_Classes');
        $html .= $Classes->setupMVC();
        
        $Interfaces = cls::get('core_Interfaces');
        $html .= $Interfaces->setupMVC();
        
        $html .= $Plugins->setupMVC();
        
        $Packs = cls::get('core_Packs');
        $html .= $Packs->setupMVC();
        
        $Cron = cls::get('core_Cron');
        $html .= $Cron->setupMVC();
        
        $Logs = cls::get('core_Logs');
        $html .= $Logs->setupMVC();
        
        $Cache = cls::get('core_Cache');
        $html .= $Cache->setupMVC();

        $Lg = cls::get('core_Lg');
        $html .= $Lg->setupMVC();
        
        $Roles = cls::get('core_Roles');
        $html .= $Roles->setupMVC();
        
        $Users = cls::get('core_Users');
        $html .= $Users->setupMVC();
        
        $Locks = cls::get('core_Locks');
        $html .= $Locks->setupMVC();
        
        // Проверяваме дали имаме достъп за четене/запис до следните папки
        $folders = array(
            EF_SBF_PATH, // sbf root за приложението
            EF_TEMP_PATH, // временни файлове
            EF_UPLOADS_PATH // файлове на потребители
        );
        
        foreach($folders as $path) {
            if(!is_dir($path)) {
                if(!mkdir($path, 0777, TRUE)) {
                    $html .= "<li style='color:red;'>Не може да се създаде директорията: <b>{$path}</b></li>";
                } else {
                    $html .= "<li style='color:green;'>Създадена е директорията: <b>{$path}</b></li>";
                }
            } else {
                $html .= "<li>Съществуваща от преди директория: <b>{$path}</b></li>";
            }
            
            if(!is_writable($path)) {
                $html .= "<li style='color:red;'>Не може да се записва в директорията <b>{$path}</b></li>";
            }
        }
        
        if( CORE_OVERWRITE_HTAACCESS ) {
            $filesToCopy = array(
                EF_EF_PATH . '/_docs/tpl/htaccessSBF.txt' => EF_SBF_PATH . '/.htaccess',
                EF_EF_PATH . '/_docs/tpl/htaccessIND.txt' => EF_INDEX_PATH . '/.htaccess'
            );
            
            foreach($filesToCopy as $src => $dest) {
                if(copy($src, $dest)) {
                        $html .= "<li style='color:green;'>Копиран е файла: <b>{$src}</b> => <b>{$dest}</b></li>";
                } else {
                        $html .= "<li style='color:red;'>Не може да бъде копиран файла: <b>{$src}</b> => <b>{$dest}</b></li>";
                }
            }
        }

        // Изтриваме всички поддиректории на sbf които не започват със символа '_'
	    if ($handle = opendir(EF_SBF_PATH)) {
		    while (false !== ($entry = readdir($handle))) {
		        if ($entry != "." && $entry != ".." && false === strpos($entry, '_') && $entry != '.htaccess') {
		        	if (core_Os::deleteDir(EF_SBF_PATH . "/{$entry}")) {
		        		$html .= "<li style='color:green;'>Директория: <b>" . EF_SBF_PATH . "/{$entry}</b> е изтрита</li>";
		        	}
		        	else {
		        		$html .= "<li style='color:red;'>Директория: <b>" . EF_SBF_PATH . "/{$entry}</b> не беше изтрита</li>";	
		        	}
		        }
		    }
	    
		    closedir($handle);
		}

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
        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'clearOldTempFiles';
        $rec->description = 'Изтрива старите временни файлове';
        $rec->controller = 'core_Os';
        $rec->action = 'clearOldFiles';
        $rec->period = 60;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 120;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $html .= "<li><font color='green'>Задаване по крон да се изтриват старите файлове от " . EF_TEMP_PATH . "</font></li>";
        } else {
            $html .= "<li>Отпреди Cron е бил нагласен да изтрива старите файлове от " . EF_TEMP_PATH . "</li>";
        }
        
        return $html;
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
     * Миграция - почистване на модела core_Browser
     */
    function clearBrowserInfo()
    {
        $Browser = cls::get('core_Browser');

        $Browser->db->query("TRUNCATE TABLE `{$Browser->dbTableName}`");
    }
}
