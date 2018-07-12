<?php


/**
 * Плъгин за оцветяване на код
 *
 * @category  vendors
 * @package   hljs
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class hljs_RichTextPlg extends core_Plugin
{
    /**
     * Прихваща извикването на AfterHighLightCode в type_Richtext
     */
    public function on_AfterHighLightCode(&$mvc, $style = 'github')
    {
        // Ако има въведено, да не се въвежда
        if ($mvc->_htmlBoard['hljs']) {
            
            return ;
        }
        
        // Добавяме шаблона
        $mvc->_htmlBoard['hljs'] = hljs_Adapter::enable($style);
    }
}
