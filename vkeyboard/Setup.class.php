<?php

/**
 * Клас 'vkeyboard_Setup' -
 *
 *
 * @category  bgerp
 * @package   vkeyboard
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class vkeyboard_Setup extends core_ProtoSetup
{


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
    public $info = 'Виртуална клавиатура ';


    /**
     * Инсталиране на пакета
     */
    public function install()
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
    public function deinstall()
    {
        $html = parent::deinstall();

        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');

        $Plugins->deinstallPlugin('vkeyboard_Plugin');

        return $html;
    }
}
