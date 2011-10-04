<?php
/**
 * Опаковка на пакета `accda`
 *
 * Поддържа системното меню и табовете на пакета 'Acc'
 *
 * @category   BGERP
 * @package    accda
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 */
class accda_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('accda_Da', 'Инвентарна книга');
        $tabs->TAB('accda_Groups', 'Групи');
        $tabs->TAB('accda_Documents', 'Документи');
        
        $tpl = $tabs->renderHtml($tpl, empty($invoker->currentTab)?$invoker->className:$invoker->currentTab);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');

        $invoker->menuPage = 'Счетоводство:ДА';
    }
}