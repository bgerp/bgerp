<?php



/**
 * Клас 'punymce_Plugin' -
 *
 *
 * @category  vendors
 * @package   punymce
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class punymce_Plugin extends core_Plugin
{
    
    
    /**
     * Извиква се преди рендирането на HTML input
     */
    public function on_BeforeRenderInput(&$invoker, &$ret, $name, $value, &$attr)
    {
        ht::setUniqId($attr);
    }
    
    
    /**
     * Извиква се след рендирането на HTML input
     */
    public function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr)
    {
        if (!Mode::is('screenMode', 'narrow')) {
            $editor = cls::get('punymce_PunyMCE');
            $tpl = $editor->renderHtml($tpl, $attr);
        }
    }
}
