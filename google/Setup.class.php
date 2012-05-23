<?php



/**
 * class google_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с 'email'
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class google_Setup 
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = NULL;
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = NULL;
    
    
    /**
     * Описание на модула
     */
    var $info = "Услуги на Google";
    
    
    /**
     * Необходими пакети
     */
    var $depends = '';
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        //
        // Инсталиране на плъгин за автоматичен превод на входящата поща
        //
        core_Plugins::installPlugin('Email Translate', 'google_plg_Translate', 'email_Incomings', 'private');
        $html .= "<li>Закачане на Google Translate към входящата поща";
        
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
        if($delCnt = $Plugins->deinstallPlugin('google_plg_Translate')) {
            $html .= "<li>Премахнати са {$delCnt} закачания на 'google_plg_Translate'";
        } else {
            $html .= "<li>Не са премахнати закачания на плъгина";
        }
    
        return $html;
    }
}