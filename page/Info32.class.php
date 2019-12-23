<?php


/**
 * Клас 'page_Info32' - Шаблон за прозорец с информация с 32х32 икона
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  ef
 * @package   tpl
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class page_Info32 extends core_ET
{
    /**
     * Конструктор на шаблона
     */
    public function __construct()
    {
        if (Mode::is('screenMode', 'narrow')) {
            parent::__construct(
                '<style> .formSection {max-width:600px;} </style>' .
                "\n<table   cellspacing=0 callpadding=0 class=\"formTable\">" .
                "\n <tr>" .
                "\n     <td class='formTitle'>" .
                "\n " . tr('Информация') .
                "\n     </td>" .
                "\n</tr>" .
                "\n<tr>" .
                "\n <td  class=\"formSection\">" .
                "\n     <div class=\"formInfo\">" .
                "\n         [#text#]" .
                "\n     </div>" .
                "\n  </td>" .
                "\n</tr>" .
                "\n<!--ET_BEGIN TOOLBAR-->" .
                "\n<tr>" .
                "\n <td style='padding:0px;'><div class=\"formToolbar\">[#TOOLBAR#]</div></td>" .
                "\n</tr>" .
                "\n<!--ET_END TOOLBAR-->" .
                "\n</table>"
            );
        } else {
            parent::__construct(
                '<style> .formSection {height:360px;width:600px;} </style>' .
                "\n<table cellspacing=0 callpadding=0 class=\"formTable\">" .
                "\n <tr>" .
                "\n     <td class='formTitle'>" .
                "\n         <img src=" . sbf('img/info32.gif') . '  align=absmiddle width=32 height=32>&nbsp;' . tr('Информация') .
                "\n     </td>" .
                "\n</tr>" .
                "\n<tr>" .
                "\n <td  class=\"formSection\">" .
                "\n     <div class=\"formInfo\">" .
                "\n         [#text#]" .
                "\n     </div>" .
                "\n  </td>" .
                "\n</tr>" .
                "\n<!--ET_BEGIN TOOLBAR-->" .
                "\n<tr>" .
                "\n <td style='padding:0px;'><div class=\"formToolbar\">[#TOOLBAR#]</div></td>" .
                "\n</tr>" .
                "\n<!--ET_END TOOLBAR-->" .
                "\n</table>"
            );
        }
    }
}
