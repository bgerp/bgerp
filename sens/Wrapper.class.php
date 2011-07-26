<?php

/**
 * Клас 'common_Wrapper'
 *
 * Поддържа системното меню и табовете на пакета 'Core'
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id: Guess.php,v 1.29 2009/04/09 22:24:12 dufuz Exp $
 * @link
 * @since
 */
class sens_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        Mode::set('pageMenu', 'Наблюдение');
        
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('sens_Sensors', 'Сензори');
        $tabs->TAB('sens_SensorLogs', 'Логове');
        $tabs->TAB('sens_Limits', 'Лимити');
        $tabs->TAB('sens_Params', 'Параметри');
        $tabs->TAB('sens_Overviews', 'Мениджър изгледи');
        
        // $tpl = $tabs->renderHtml($tpl, $invoker->className);
        $tpl = $tabs->renderHtml($tpl, $invoker->tabName ? $invoker->tabName : $invoker->className);
        
        $tpl->replace('', 'NAV_BAR');
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}