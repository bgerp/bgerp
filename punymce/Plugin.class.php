<?php


/**
 * Клас 'punymce_Plugin' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    punymce
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class punymce_Plugin extends core_Plugin {
    
    
    /**
     *  Извиква се преди рендирането на HTML input
     */
    function on_BeforeRenderInput(&$invoker, &$ret, $name, $value, &$attr)
    {
        ht::setUniqId($attr);
    }
    
    
    /**
     *  Извиква се след рендирането на HTML input
     */
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr)
    {
        if(!Mode::is('screenMode', 'narrow')) {
            $editor = cls::get('punymce_PunyMCE');
            $tpl = $editor->renderHtml($tpl, $attr);
        }
    }
}