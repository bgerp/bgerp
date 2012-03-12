<?php



/**
 * Клас 'sens_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Core'
 *
 *
 * @category  bgerp
 * @package   sens
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class sens_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        Mode::set('pageMenu', 'Наблюдение');
        
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('sens_Sensors', 'Сензори');
        $tabs->TAB('sens_IndicationsLog', 'Показания');
        $tabs->TAB('sens_MsgLog', 'Съобщения');
        $tabs->TAB('sens_Params', 'Параметри');
        $tabs->TAB('sens_Overviews', 'Мениджър изгледи');
        
        // $tpl = $tabs->renderHtml($tpl, $invoker->className);
        $tpl = $tabs->renderHtml($tpl, $invoker->tabName ? $invoker->tabName : $invoker->className);
        
        $tpl->replace('', 'NAV_BAR');
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}