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
 * SALT ключа за генериране на уникален sid (status id)
 */
defIfNot('EF_STATUSE_SALT', '');


/**
 * Формат по подразбиране за датите
 */
defIfNot('EF_DATE_FORMAT', 'd-m-YEAR');

/**
 * Формат по подразбиране за датата при тесни екрани
 */
defIfNot('EF_DATE_NARROW_FORMAT', 'd-m-year');

/**
 * @todo Чака за документация...
 */
defIfNot('TYPE_KEY_MAX_SUGGESTIONS', 1000);

/**
 * Езикът по подразбиране е български
 */
defIfNot('EF_DEFAULT_LANGUAGE', 'bg');

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
class core_Setup {
    
    
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
    var $info = "Ядро на Experta Framework";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        
               
           'EF_DATE_FORMAT'   => array ('varchar'),
    
           'EF_DATE_NARROW_FORMAT'   => array ('varchar'),
         
           'TYPE_KEY_MAX_SUGGESTIONS'   => array ('int'), 

           'EF_DEFAULT_LANGUAGE'   => array ('varchar'),
    
           'EF_APP_TITLE'   => array ('varchar'),
           
    
        );
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
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
        
        $Lg = cls::get('core_Lg');
        $html .= $Lg->setupMVC();
        
        $Cache = cls::get('core_Cache');
        $html .= $Cache->setupMVC();
        
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
                    $html .= "<li style='color:red;'>Не може да се създаде директорията: <b>{$path}</b>";
                } else {
                    $html .= "<li style='color:green;'>Създадена е директорията: <b>{$path}</b>";
                }
            } else {
                $html .= "<li>Съществуваща от преди директория: <b>{$path}</b>";
            }
            
            if(!is_writable($path)) {
                $html .= "<li style='color:red;'>Не може да се записва в директорията <b>{$path}</b>";
            }
        }
        
        if( CORE_OVERWRITE_HTAACCESS ) {
            $filesToCopy = array(
                EF_EF_PATH . '/_docs/tpl/htaccessSBF.txt' => EF_SBF_PATH . '/.htaccess',
                EF_EF_PATH . '/_docs/tpl/htaccessIND.txt' => EF_INDEX_PATH . '/.htaccess'
            );
            
            foreach($filesToCopy as $src => $dest) {
                if(copy($src, $dest)) {
                        $html .= "<li style='color:green;'>Копиран е файла: <b>{$src}</b> => <b>{$dest}</b>";
                } else {
                        $html .= "<li style='color:red;'>Не може да бъде копиран файла: <b>{$src}</b> => <b>{$dest}</b>";
                }
            }
        }

        // Изтриваме всички поддиректории на sbf които не започват със символа '_'
	    if ($handle = opendir(EF_SBF_PATH)) {
		    while (false !== ($entry = readdir($handle))) {
		        if ($entry != "." && $entry != ".." && false === strpos($entry, '_') && $entry != '.htaccess') {
		        	if (core_Os::deleteDir(EF_SBF_PATH . "/{$entry}")) {
		        		$html .= "<li style='color:green;'>Директория: <b>" . EF_SBF_PATH . "/{$entry}</b> е изтрита";
		        	}
		        	else {
		        		$html .= "<li style='color:red;'>Директория: <b>" . EF_SBF_PATH . "/{$entry}</b> не беше изтрита";	
		        	}
		        }
		    }
	    
		    closedir($handle);
		}
		     
        return $html;
    }
}
