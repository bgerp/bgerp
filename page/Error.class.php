<?php



/**
 * Клас 'page_Info' - Шаблон за прозорец със съобщение за грешка
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  ef
 * @package   page
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class page_Error extends core_ET {
    
    
    /**
     * Конструктор на шаблона
     */
    function page_Error() {
        if(Mode::is('screenMode', 'narrow')) {
            $this->core_ET(
                "\n<CENTER>" .
                "\n<table style='max-width:600; border:1px solid red; background-color:#FFFF66'>" .
                "\n    <tr bgcolor='#FFCC33'>" .
                "\n        <td style='font-size:1.2em;padding:3px;'>" .
                "\n            " . tr('Грешка') .
                "\n        </td>" .
                "\n</tr>" .
                "\n<tr>" .
                "\n    <td style='font-size:0.9em;font-family:Arial;padding:5px;'>[#text#]</td>" .
                "\n</tr>" .
                "\n<!--ET_BEGIN TOOLBAR-->" .
                "\n<tr>" .
                "\n    <td>[#TOOLBAR#]</td>" .
                "\n</tr>" .
                "\n<!--ET_END TOOLBAR-->" .
                "\n</table>" .
                "\n</CENTER>");
        } else {
            $this->core_ET(
                "\n<CENTER>" .
                "\n<table style='max-width:600;margin-top:50px; border:1px solid red;background-color:#FFFF66'>" .
                "\n    <tr bgcolor='#FFCC33'>" .
                "\n        <td  style='font-size:36px;padding:5px;'>" .
                "\n            <img src=" . sbf('img/error.gif') . "  align=absmiddle width=48 height=48>&nbsp;" . tr('Грешка') .
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