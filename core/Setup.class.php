<?php

/**
 *  class 'core_Setup' - Начално установяване на пакета 'core'
 *
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class core_Setup {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'core_Packs';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Ядро на Experta Framework";
    
    /**
     *  Инсталиране на пакета
     */
    function install($Plugins = NULL)
    {
        // Установяване за първи път
        
        // Правим това, защото процедурата по начално установяване
        // може да се задейства още от конструктура на core_Plugins
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
        
        $Roles = cls::get('core_Roles');
        $html .= $Roles->setupMVC();
        
        $Users = cls::get('core_Users');
        $html .= $Users->setupMVC();
        
        $Cache = cls::get('core_Cache');
        $html .= $Cache->setupMVC();
        
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
        
        $filesToCopy = array(EF_EF_PATH . '/_docs/tpl/htaccessSBF.txt' => EF_SBF_PATH . '/.htaccess',
                             EF_EF_PATH . '/_docs/tpl/htaccessIND.txt' => EF_INDEX_PATH . '/.htaccess'
                            );
        
        foreach($filesToCopy as $src => $dest) {
            if(!file_exists(EF_SBF_PATH .'/.htaccess') || ($src == (EF_EF_PATH . '/_docs/tpl/htaccessSBF.default'))) {
                if(copy($src, $dest)) {
                    $html .= "<li style='color:green;'>Копиран е файла: <b>{$path}</b>";
                } else {
                    $html .= "<li style='color:red;'>Не може да бъде копиран файла: <b>{$path}</b>";
                }
            }
        }
        
        return $html;
    }
}