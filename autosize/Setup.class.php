<?php



/**
 * Версия на JS компонента
 */
defIfNot('AUTOSIZE_VERSION', 'v1.18.4');


/**
 * Максималните редове в широк режим
 */
defIfNot('AUTOSIZE_MAX_ROWS_WIDE', '600');


/**
 * Максималните редове в тесен режим
 */
defIfNot('AUTOSIZE_MAX_ROWS_NARROW', '400');


/**
 * Клас 'jqdatepick_Setup' -
 *
 *
 * @category  vendors
 * @package   autosize
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class autosize_Setup extends core_ProtoSetup
{
    
    
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
    var $info = "Автоматично увеличаване на височината на полетата за въвеждане на текст";
    
    
    /**
     * Пътища до JS файлове
     */
    var $commonJS = "autosize/[#AUTOSIZE_VERSION#]/jquery.autosize.min.js";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        
        'AUTOSIZE_VERSION' => array ('enum(v1.18.4=v1.18.4,
                                                 v1.18.9=v1.18.9)', 'mandatory, caption=Версията на програмата->Версия'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $html .= $Plugins->installPlugin('Редове на текст', 'autosize_Plugin', 'type_Richtext', 'private');
        
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
        
        // Премахваме от type_Date полета
        $Plugins->deinstallPlugin('autosize_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'autosize_Plugin'";
        
        return $html;
    }
}
