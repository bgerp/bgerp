<?php

/**
 * Клас 'tinymce_Setup' - Редактор за HTML текстови полета
 *
 *
 * @category  bgerp
 * @package   tinymce
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 * @see       https://www.tinymce.com/
 */
class tinymce_Setup extends core_ProtoSetup
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
    var $info = "Wysiwyg редактор за HTML данни използващ tinyMCE";


    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();

        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');


        // Инсталиране към всички полета, но без активиране
        $html .= $Plugins->installPlugin('tinyMCE', 'tinymce_Plugin', 'type_Html', 'private');

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

        $Plugins->deinstallPlugin('tinyMCE');
        return $html;
    }


}