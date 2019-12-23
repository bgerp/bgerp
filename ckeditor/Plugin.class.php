<?php


/**
 * Клас 'ckeditor_Plugin' -
 *
 *
 * @category  vendors
 * @package   ckeditor
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class ckeditor_Plugin extends core_Plugin
{
    /**
     * Извиква се преди рендирането на HTML input
     */
    public function on_BeforeRenderInput(&$invoker, &$ret, $name, $value, &$attr, $options = array())
    {
        ht::setUniqId($attr);
    }
    
    
    /**
     * Извиква се след рендирането на HTML input
     */
    public function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr, $options = array())
    {
        if (Mode::is('screenMode', 'narrow')) {
            
            return;
        }
        
        $editor = cls::get('ckeditor_CKeditor');
        $tpl = $editor->renderHtml($tpl, $attr);
    }
}
