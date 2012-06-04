<?php



/**
 * Клас 'plg_SystemWrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Core'
 *
 *
 * @category  vendors
 * @package   recently
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class recently_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('recently_Values', 'Последно използвани');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->className);
        
        $tpl->prepend(tr($invoker->title) . " « ", 'PAGE_TITLE');
    }
}