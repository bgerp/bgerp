<?php


/**
 * Плъгин за оцветяване на код
 *
 * @category  vendors
 * @package   hljs
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hljs_RichTextPlg extends core_Plugin
{    
    
    
    /**
     * Прихваща извикването на AfterCatchRichElements в fileman_Files
     */
    function on_AfterCatchRichElements(&$mvc, &$html)
    {
        // Вземаме място
        $place = $mvc->getPlace();
        
        // Добавяме шаблона
        $mvc->_htmlBoard[$place] = hljs_Adapter::enable();
    }
}
