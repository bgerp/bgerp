<?php



/**
 * @todo Чака за документация...
 */
defIfNot('VKI_version', '1.28');

/**
 * Клас 'keyboard_Setup' -
 *
 *
 * @category  vendors
 * @package   keyboard
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class vkeyboard_Setup extends core_ProtoSetup
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
    var $info = "Виртуална клавиатура ";


    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();

        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');


        // Инсталиране към всички полета, но без активиране
        $html .= $Plugins->installPlugin('Virtual keyboard', 'vkeyboard_Plugin', 'core_Type', 'family');

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

        $Plugins->deinstallPlugin('vkeyboard_Plugin');
        return $html;
    }


}