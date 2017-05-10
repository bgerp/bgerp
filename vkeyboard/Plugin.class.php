<?php




/**
 * Клас 'vkeyboard_Plugin' -
 *
 *
 * @category  bgerp
 * @package   vkeyboard
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class vkeyboard_Plugin extends core_Plugin
{


    /**
     * Извиква се преди рендирането на HTML input
     */
    function on_BeforeRenderInput(&$invoker, &$ret, $name, $value, &$attr, $options = array())
    {
        $conf = core_Packs::getConfig('vkeyboard');

    }


    /**
     * Извиква се след рендирането на HTML input
     */
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, &$attr, $options = array())
    {
        $conf = core_Packs::getConfig('vkeyboard');

        $tpl->push("vkeyboard/js/jquery.keyboard.js", 'JS');
        $tpl->push("vkeyboard/js/bulgarian.js", 'JS');
        $tpl->push("vkeyboard/js/script.js", 'JS');
        $tpl->push("vkeyboard/css/keyboard.css", 'CSS');
        $tpl->push("vkeyboard/css/keyboard-basic.css", 'CSS');

        jquery_Jquery::run($tpl, 'keyboardAction();');
    }
}