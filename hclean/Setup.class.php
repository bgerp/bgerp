<?php



/**
 * Клас 'hclean_Setup' - Инсталира плъгина за изчистване на HTML полетата и създава директория,
 *
 * необходима за работа на hclean_Purifier
 *
 *
 * @category  all
 * @package   hclean
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hclean_Setup extends core_Manager {
    
    
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
    var $info = "Изчистване на HTML";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        //Създаваме директорията, необходима за работа на hclean_Purifier
        $html .= hclean_Purifier::mkdir();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $Plugins->installPlugin('HClean', 'hclean_HtmlPurifyPlg', 'type_Html', 'private');
        $html .= "<li>Изчистване на HTML полетата - type_Html (Активно)";
        
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
        $Plugins->deinstallPlugin('chosen_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'hclean_HtmlPurifyPlg'";
        
        return $html;
    }
}