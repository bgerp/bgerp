<?php



/**
 * Покупки - опаковка
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('purchase_Offers', 'Оферти');
        $tabs->TAB('purchase_Requests', 'Заявки');
        $tabs->TAB('purchase_Debt', 'Задължения');
        
        $tpl = $tabs->renderHtml($tpl, empty($invoker->currentTab) ? $invoker->className : $invoker->currentTab);
        
        $tpl->append(tr($invoker->title) . " « ", 'PAGE_TITLE');
        
        $invoker->menuPage = 'Доставки:Покупки';
    }
}