<?php



/**
 * Клас 'calendarpicker_Setup' -
 *
 *
 * @category  vendors
 * @package   calendarpicker
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class calendarpicker_Setup extends core_ProtoSetup
{
    
    
    
//    var $commonCSS = 'calendarpicker/skins/aqua/theme.css';
    
    
    
//    var $commonJS = 'calendarpicker/calendar.js, calendarpicker/lang/calendar-[#CORE::EF_DEFAULT_LANGUAGE#].js, calendarpicker/calendar-setup.js';
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Добавя изскачащ календар в полетата за въвеждане на дата';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $html .= $Plugins->forcePlugin('Избор на дата', 'calendarpicker_Plugin', 'type_Date', 'private');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        $html = parent::deinstall();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Премахваме от type_Date полета
        $Plugins->deinstallPlugin('calendarpicker_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'calendarpicker_Plugin'";
        
        return $html;
    }
}
