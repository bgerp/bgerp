<?php



/**
 * class google_Setup
 *
 * Инсталиране/Деинсталиране на
 * плъгини свързани с 'google'
 *
 * @category  vendors
 * @package   google
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class google_Setup extends core_ProtoSetup
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
     * Инсталиране на пакета
     */
    function install()
    {
    	$htmp = parent::install();
    	
        //
        // Инсталиране на плъгин за автоматичен превод
        //
        $html .= core_Plugins::installPlugin('core_Lg Translate', 'google_plg_LgTranslate', 'core_Lg', 'private');
        
        return $html;
    }
    

    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
    	$html = parent::deinstall();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
    
        // Инсталираме клавиатурата към password полета
        if($delCnt = $Plugins->deinstallPlugin('google_plg_LgTranslate')) {
            $html .= "<li>Премахнати са {$delCnt} закачания на 'google_plg_LgTranslate'";
        } else {
            $html .= "<li>Не са премахнати закачания на плъгина";
        }
    
        return $html;
    }
}