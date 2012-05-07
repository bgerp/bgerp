<?php



/**
 * Клас 'cms_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'cms'
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_Wrapper extends core_Plugin
{
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'cms'));
        
        $tabs->TAB('cms_Content', 'Съдържание');
        $tabs->TAB('cms_Categories', 'Категории');
        $tabs->TAB('cms_Articles', 'Статии');
        $tabs->TAB('cms_Comments', 'Коментари');
        $tabs->TAB('cms_RSS', 'RSS');
         
        $tpl = $tabs->renderHtml($tpl, $invoker->tabName ? $invoker->tabName : $invoker->className);
        
        $tpl->append(tr($invoker->title) . " « ", 'PAGE_TITLE');
    }
}