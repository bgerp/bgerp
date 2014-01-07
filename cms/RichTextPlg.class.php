<?php



/**
 * Клас 'doc_RichTextPlg' - Добавя функционалност за поставяне на картинки
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Yusein Yuseinov <yyuseinov@gmail.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_RichTextPlg extends core_Plugin
{
    
    
    /**
     * Добавя бутон за качване на документ
     */
    function on_AfterGetToolbar($mvc, &$toolbarArr, &$attr)
    {
        // Добавяме бутон за добавяне на картинка
	    $toolbarArr->add("<a class='rtbutton' title='" . tr('Добавяне на картинка') . "' onclick=\"s('', '', document.getElementById('{$attr['id']}'))\">" . tr('Картинка') . "</a>", 'filesAndDoc');    
    }
}
