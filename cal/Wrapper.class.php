<?php



/**
 * Клас 'cal_Wrapper'
 *
 * Опаковка на календара
 *
 *
 * @category  bgerp
 * @package   cal
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cal_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => $invoker->className));
        
        $tabs->TAB('cal_Agenda', 'Списък');
        $tabs->TAB('cal_Day', 'Ден');
        $tabs->TAB('cal_Week', 'Седмица');
        $tabs->TAB('cal_Month', 'Месец');
        $tabs->TAB('cal_Year', 'Година');
        $tabs->TAB('cal_Tasks', 'Задачи');

        $tpl = $tabs->renderHtml($tpl, $invoker->currentTab ? : $invoker->className);
        
        $tpl->prepend(tr($invoker->title) . " « ", 'PAGE_TITLE');
    }
}