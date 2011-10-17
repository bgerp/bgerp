<?php
/**
 * Покупки - опаковка
 *
 * @category   BGERP
 * @package    sales
 * @author     Милен Георгиев
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 */
class sales_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('sales_Deals', 'Сделки');
         
        $tpl = $tabs->renderHtml($tpl, empty($invoker->currentTab)?$invoker->className:$invoker->currentTab);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}