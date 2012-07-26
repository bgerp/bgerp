<?php
/**
 * Установяване на пакета oembed
 *
 * @link http://www.oembed.com
 *
 * @category  vendors
 * @package   oembed
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class oembed_Setup
{


    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Вграждане на външни ресурсу";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = '';
        
        $Cache = cls::get('oembed_Cache');
        $html .= $Cache->setupMVC();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за работа с документи от системата
        // Замества handle' ите на документите с линк към документа
        $Plugins->installPlugin('oEmbed връзки', 'oembed_Plugin', 'type_Richtext', 'private');
        $html .= "<li>Закачане на oembed_Plugin към полетата за RichEdit - (Активно)";
        
        return $html;
    }
    
    
    function deinstall()
    {
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за работа с документи от системата
        // Замества handle' ите на документите с линк към документа
        $Plugins->deinstallPlugin('oembed_Plugin');
        $html .= "<li>Деинсталиране на oembed_Plugin";
        
        return $html;
    }
}