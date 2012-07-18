<?php


/**
 * Инсталатор на плъгин за добавяне на бутона за преглед на документи в pixlr.com
 * Разширения: jpg,jpeg,bmp,gif,png,psd,pxd
 *
 * @category  vendors
 * @package   pixlr
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pixlr_Setup extends core_Manager {
    
    
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
    var $info = "Преглед на документи с pixlr.com";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $Plugins->forcePlugin('Преглед на документи с Pixlr', 'pixlr_Plugin', 'fileman_Files', 'private');
        $html .= "<li>Закачане на pixlr_Plugin към fileman_Files (Активно)";
        
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
        $Plugins->deinstallPlugin('pixlr_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'pixlr_Plugin'";
        
        return $html;
    }
}