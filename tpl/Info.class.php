<?php



/**
 * Клас 'tpl_Info' - Шаблон за прозорец с информация
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  all
 * @package   tpl
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class tpl_Info extends core_ET {
    
    
    /**
     * @todo Чака за документация...
     */
    function tpl_Info() {
        if(Mode::is('screenMode', 'narrow')) {
            $this->core_ET(
                "\n<table style='max-width:480px;border:solid 1px black; background-color:#ffe'>" .
                "\n    <tr bgcolor='blue'>" .
                "\n        <td style='font-size:1.2em;padding:3px;color:white;'>" .
                "\n            " . tr('Информация') .
                "\n        </td>" .
                "\n</tr>" .
                "\n<tr bgcolor='#ccccff'>" .
                "\n    <td style='font-size:0.9em;font-family:Arial;padding:5px;'>[#text#]</td>" .
                "\n</tr>" .
                "\n<!--ET_BEGIN TOOLBAR-->" .
                "\n<tr>" .
                "\n    <td>[#TOOLBAR#]</td>" .
                "\n</tr>" .
                "\n<!--ET_END TOOLBAR-->" .
                "\n</table>");
        } else {
            $this->core_ET(
                "\n<CENTER>" .
                "\n<table style='max-width:480px;margin-top:50px; border:solid 1px black; background-color:#ffe''>" .
                "\n    <tr bgcolor='blue'>" .
                "\n        <td  style='font-size:24px;padding:5px;color:white;'>" .
                "\n            <img src=" . sbf('img/info32.gif') . "  align=absmiddle width=32 height=32>&nbsp;" . tr('Информация') .
                "\n        </td>" .
                "\n</tr>" .
                "\n<tr>" .
                "\n    <td style='font-size:1.2em;font-family:Arial;padding:15px;'>[#text#]</td>" .
                "\n</tr>" .
                "\n<!--ET_BEGIN TOOLBAR-->" .
                "\n<tr>" .
                "\n    <td><center>[#TOOLBAR#]</center></td>" .
                "\n</tr>" .
                "\n<!--ET_END TOOLBAR-->" .
                "\n</table>" .
                "\n</CENTER>");
        }
    }
}