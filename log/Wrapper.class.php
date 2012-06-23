<?php



/**
 * Поддържа системното меню и табове-те на пакета 'log'
 *
 *
 * @category  bgerp
 * @package   log
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class log_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('log_Documents', 'Документи');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->currentTab ? : $invoker->className);
        
        $tpl->prepend(tr($invoker->title) . " « " , 'PAGE_TITLE');
    }
}
