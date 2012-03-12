<?php



/**
 * Клас 'gen_Setup' -
 *
 * Инсталиране на плъгина за родословие към визитника
 *
 *
 * @category  vendors
 * @package   gen
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class gen_Setup extends core_Manager {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = '';
    
    
    /**
     * Описание на модула
     */
    var $info = "Възможност за родословно дърво, към хората във;
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за аватари
        $Plugins->installPlugin('Родословно дърво', 'gen_Plugin', 'crm_Persons', 'private');
        
        $Persons = cls::get('crm_Persons');
        
        $genPlg = cls::get('gen_Plugin');
        $genPlg->on_AfterDescription($Persons);
        
        $html .= $Persons->setupMVC();
        
        $html .= "<li>Могат да се добавят родители на хората от визитника";
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
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