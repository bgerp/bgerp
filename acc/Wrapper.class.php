<?php



/**
 * Клас 'acc_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Acc'
 *
 *
 * @category  all
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class acc_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('acc_Balances', 'Оборотни ведомости');
        $tabs->TAB('acc_Articles', 'Мемориални Ордери');
        $tabs->TAB('acc_Journal', 'Журнал');
        
        $tpl = $tabs->renderHtml($tpl, empty($invoker->currentTab) ? $invoker->className : $invoker->currentTab);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
        
        $invoker->menuPage = 'Счетоводство:Книги';
    }
}