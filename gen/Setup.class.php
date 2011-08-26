<?php


/**
 * Клас 'gen_Setup' -
 *
 * Инсталиране на плъгина за родословие към визитника
 *
 * @category   Experta Framework
 * @package    avatar
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class gen_Setup extends core_Manager {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = '';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = '';
    
        
    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за аватари
        $Plugins->installPlugin('Родословно дърво', 'gen_Plugin', 'crm_Persons', 'private');
        
        $Persons = cls::get('crm_Persons');

        $html .= $Persons->setupMVC();

         
        $html .= "<li>Могат да се добавят родители на хората от визитника";
        
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $Plugins->deinstallPlugin('gen_Plugin');
        $html .= "<li>Родословното дърво е премахнато";
        
        return $html;
    }
}