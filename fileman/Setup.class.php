<?php

/**
 * Клас 'fileman_Setup' - Начално установяване на пакета 'fileman'
 *
 * @category   Experta Framework
 * @package    fileman
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class fileman_Setup extends core_Manager {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'fileman_Files';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    
    
    /**
     *  Инсталиране на пакета
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
        $Get = cls::get('fileman_Get');
        $html .= $Get->setupMVC();
        
        // Установяваме MIME-типовете;
        $Mime2Ext = cls::get('fileman_Mime2Ext');
        $html .= $Mime2Ext->setupMVC();
        
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        return "<h4>Пакета fileman е деинсталиран</h4>";
    }
}