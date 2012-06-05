<?php



/**
 * Клас 'cat_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'cat'
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'cat'));
        
        $tabs->TAB('cat_Products', 'Списък');
        $tabs->TAB('cat_Groups', 'Групи');
        $tabs->TAB('cat_Categories', 'Категории');
        $tabs->TAB('cat_Packagings', 'Опаковки');
        $tabs->TAB('cat_Params', 'Параметри');
        $tabs->TAB('cat_UoM', 'Мерки');
        
        if (haveRole("admin,cat")) {
        }
        
        $tpl = $tabs->renderHtml($tpl, $invoker->tabName ? $invoker->tabName : $invoker->className);
        
        $tpl->prepend(tr($invoker->title) . " « ", 'PAGE_TITLE');
    }
}