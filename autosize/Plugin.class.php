<?php


/**
 * Клас 'ckeditor_Plugin' -
 *
 *
 * @category  vendors
 * @package   ckeditor
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class autosize_Plugin extends core_Plugin
{
    /**
     * Изпълнява се преди рендирането на input
     */
    public function on_BeforeRenderInput(&$invoker, &$ret, $name, $value, &$attr = array())
    {
        ht::setUniqId($attr);
        $attr['class'] = ($attr['class'] ?? '') . ' autosize';
    }
    
    
    /**
     * Изпълнява се след рендирането на input
     */
    public function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr = array())
    {
        foreach (autosize_Setup::getJsFiles() as $file) {
            $tpl->push($file, 'JS');
        }
        
        jquery_Jquery::run($tpl, "$('.autosize:visible').autosize({maxHeight:$(window).height() - 150});");
    }
}
