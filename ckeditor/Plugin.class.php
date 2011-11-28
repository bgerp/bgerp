<?php


/**
 * Клас 'ckeditor_Plugin' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    ckeditor
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class ckeditor_Plugin extends core_Plugin {
    
    
    /**
     *  Извиква се преди рендирането на HTML input
     */
    function on_BeforeRenderInput(&$invoker, &$ret, $name, $value, &$attr, $options=array())
    {
        ht::setUniqId($attr);
    }
    
    
    /**
     *  Извиква се след рендирането на HTML input
     */
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr, $options=array())
    {
        if(Mode::is('screenMode', 'narrow')) return;
        
        $editor = cls::get('ckeditor_CKeditor');
        $tpl = $editor->renderHtml($tpl, $attr);
    }
} 