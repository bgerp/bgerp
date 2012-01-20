<?php


/**
 * Клас 'fileman_Setup' - Начално установяване на пакета 'fileman'
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_Setup extends core_Manager {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Контролер на връзката от менюто core_Packs
     */
    var $startCtr = 'fileman_Files';
    
    
    /**
     * Екшън на връзката от менюто core_Packs
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Мениджър на файлове: качване, съхранение и използване";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Установяваме папките;
                $Buckets = cls::get('fileman_Buckets');
        $html .= $Buckets->setupMVC();
        
        // Установяваме файловете;
                $Files = cls::get('fileman_Files');
        $html .= $Files->setupMVC();
        
        // Установяваме версиите;
                $Versions = cls::get('fileman_Versions');
        $html .= $Versions->setupMVC();
        
        // Установяваме даните;
                $Data = cls::get('fileman_Data');
        $html .= $Data->setupMVC();
        
        // Установяваме свалянията;
                $Download = cls::get('fileman_Download');
        $html .= $Download->setupMVC();
        
        // Установяваме вземанията от URL;
                // $Get = cls::get('fileman_Get');
                // $html .= $Get->setupMVC();
        
        // Установяваме MIME-типовете;
                $Mime2Ext = cls::get('fileman_Mime2Ext');
        $html .= $Mime2Ext->setupMVC();
        
        // Конвертира старите имена, които са на кирилица
                if(Request::get('Full')) {
            $query = $Files->getQuery();
            
            while($rec = $query->fetch()) {
                if(STR::utf2ascii($rec->name) != $rec->name) {
                    $rec->name = $Files->getPossibleName($rec->name, $rec->bucketId);
                    $Files->save($rec, 'name');
                }
            }
        }
        
        //Инсталиране на плъгина за проверка на разширенията
                $setExtPlg = cls::get('fileman_SetExtensionPlg');
        
        if (stristr(PHP_OS, 'WIN')) {
            if (EF_EXTENSION_FILE_PROGRAM == 'file') {
                
                return $html;
            }
        }
        
        // Зареждаме мениджъра на плъгините
                $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
                $Plugins->installPlugin('SetExtension', 'fileman_SetExtensionPlg', 'fileman_Files', 'private');
        $html .= "<li>Закачане на SetExtension към полетата за данни - fileman_Files (Активно)";
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Зареждаме мениджъра на плъгините
                $Plugins = cls::get('core_Plugins');
        
        // Премахваме от type_Keylist полета
                $Plugins->deinstallPlugin('fileman_SetExtensionPlg');
        
        return "<h4>Пакета fileman е деинсталиран</h4>";
    }
}
